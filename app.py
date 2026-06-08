from flask import Flask, request, jsonify
import joblib
import numpy as np
from datetime import datetime

# =================================================================
# --- INISIALISASI FLASK ---
# =================================================================
app = Flask(__name__)

# =================================================================
# --- LOAD SEMUA KOMPONEN MODEL & PREPROCESSING ---
# =================================================================
try:
    model = joblib.load('model_random_forest_flight.pkl')
    scaler = joblib.load('scaler_flight.pkl')
    le_airline = joblib.load('le_airline.pkl')
    le_airport = joblib.load('le_airport.pkl')
    print("✅ SUCCESS: Semua file model & preprocessing berhasil dimuat!")
except Exception as e:
    print(f"❌ ERROR: Gagal memuat file .pkl! Pastikan file ada di folder yang sama. Detail: {e}")

# =================================================================
# --- ROUTE: HALAMAN UTAMA ---
# =================================================================
@app.route('/', methods=['GET'])
def home():
    return "<h1>API BlekpinkFlight Aktif</h1><p>Server backend Machine Learning berjalan dengan baik.</p>"

# =================================================================
# --- ROUTE: API PREDIKSI MODEL ---
# =================================================================
@app.route('/predict_api', methods=['POST'])
def predict_api():
    try:
        # --- MENERIMA DATA DARI FORM (INPUT PENGGUNA) ---
        data = request.get_json()
        
        maskapai = data.get('maskapai', '')
        asal = data.get('asal', '')
        tujuan = data.get('tujuan', '')
        jam = data.get('jam', '12:00')
        
        # --- REKAYASA FITUR (FEATURE ENGINEERING) ---
        # Mengubah format jam dari form (misal "14:30") menjadi mapping waktu untuk model
        jam_inti = int(jam.split(':')[0])
        
        if 5 <= jam_inti < 12:
            dep_hour = 9.0       # Pagi (Morning)
        elif 12 <= jam_inti < 17:
            dep_hour = 14.0      # Siang/Sore (Afternoon)
        elif 17 <= jam_inti < 22:
            dep_hour = 19.0      # Petang (Evening)
        else:
            dep_hour = 2.0       # Malam/Dini Hari (Night)

        # Menentukan apakah hari ini akhir pekan (Sabtu=6, Minggu=7)
        hari_ini = datetime.now().weekday() + 1
        is_weekend = 1.0 if hari_ini >= 6 else 0.0
        
        # Menentukan apakah ini jam sibuk (Peak Hour)
        is_peak_hour = 1.0 if ((dep_hour >= 6 and dep_hour <= 9) or (dep_hour >= 16 and dep_hour <= 19)) else 0.0
        
        # Fitur statis rata-rata penerbangan harian bandara
        airport_daily_flights = 45.0
        
        # --- HANDLING LABEL ENCODER (ANTI-CRASH) ---
        # Memastikan maskapai yang dipilih ada di dalam ingatan model (le_airline)
        if maskapai in le_airline.classes_:
            carrier_code = float(le_airline.transform([maskapai])[0])
        else:
            # Jika tidak ada, gunakan maskapai pertama dari dataset sebagai default
            carrier_code = float(le_airline.transform([le_airline.classes_[0]])[0])
            
        # Memastikan bandara yang dipilih ada di dalam ingatan model (le_airport)
        if asal in le_airport.classes_:
            origin_code = float(le_airport.transform([asal])[0])
        else:
            # Jika tidak ada, gunakan bandara pertama dari dataset sebagai default
            origin_code = float(le_airport.transform([le_airport.classes_[0]])[0])

        # --- PARAMETER CUACA KONDISIONAL ---
        # Mengisi data cuaca yang logis berdasarkan waktu keberangkatan
        if dep_hour == 9.0:
            temp_mean, wind_speed, pressure = 24.0, 3.5, 1011.0
        elif dep_hour == 14.0:
            temp_mean, wind_speed, pressure = 31.0, 5.0, 1008.0
        else:
            temp_mean, wind_speed, pressure = 26.0, 2.5, 1010.0

        # --- REKONSTRUKSI & NORMALISASI FITUR (SCALING) ---
        # WAJIB SAMA PERSIS urutannya dengan X_train di Jupyter Notebook
        features_raw = np.array([[
            dep_hour, is_weekend, is_peak_hour, airport_daily_flights,
            carrier_code, origin_code, temp_mean, wind_speed, pressure
        ]])
        
        # Normalisasi menggunakan scaler asli dari dataset
        features_scaled = scaler.transform(features_raw)
        
        # --- EKSEKUSI PREDIKSI MODEL ---
        # Melakukan prediksi kelas (0 atau 1) dan probabilitas persentasenya
        prediction = model.predict(features_scaled)[0]
        probabilities = model.predict_proba(features_scaled)[0]
        prob_score = int(max(probabilities) * 100)
        
        # Konversi hasil angka menjadi teks untuk ditampilkan di Dashboard
        status_hasil = "Tepat Waktu" if prediction == 0 else "Terlambat"
        
        # Print log di terminal VS Code untuk monitoring
        print(f"💡 LOG PREDIKSI: {maskapai} jam {jam} -> Hasil: {status_hasil} ({prob_score}%)")
        
        # --- PENGEMBALIAN HASIL KE FORM (JSON) ---
        # Mengemas data dalam format JSON agar bisa dibaca oleh PHP
        return jsonify({
            'status': 'success',
            'status_prediksi': status_hasil,
            'probabilitas': prob_score
        })
        
    except Exception as e:
        # Jika terjadi error, catat di terminal dan kirim pesan error ke web
        print(f"⚠️ LOG ERROR SAAT PREDIKSI: {e}")
        return jsonify({'status': 'error', 'message': str(e)})

# =================================================================
# --- MENJALANKAN SERVER FLASK ---
# =================================================================
if __name__ == '__main__':
    # Mengaktifkan server di port 5000 (Localhost)
    app.run(port=5000, debug=True)