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
    le_departure_airport = joblib.load('le_departure_airport.pkl')
    le_arrival_airport = joblib.load('le_arrival_airport.pkl')
    le_rain_category = joblib.load('le_rain_category.pkl')
    le_winter = joblib.load('le_winter.pkl')
    
    # Load airports.csv database for coordinates and distance calculation
    import pandas as pd
    airports_df = pd.read_csv('airports.csv')
    airports_df = airports_df[airports_df['iata_code'].notnull()]
    iata_map = dict(zip(airports_df['iata_code'], zip(airports_df['latitude_deg'], airports_df['longitude_deg'])))
    print("SUCCESS: Semua file model & preprocessing berhasil dimuat!")
except Exception as e:
    print(f"ERROR: Gagal memuat file .pkl atau airports.csv! Detail: {e}")
    iata_map = {}

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
        # 1. Month (parsed from date input 'YYYY-MM-DD' as float)
        tanggal_str = data.get('tanggal', '')
        if tanggal_str:
            try:
                dt = datetime.strptime(tanggal_str, "%Y-%m-%d")
                month_val = float(dt.month)
            except Exception:
                month_val = float(datetime.now().month)
        else:
            month_val = float(datetime.now().month)
        
        # 2. Rain Category (passed from form select)
        rain_category = data.get('kategori_hujan', 'Tidak Hujan')
        
        # 3. Winter (user-selected season: 'Dingin' / Winter maps to 'Ya', otherwise 'Tidak')
        musim_input = data.get('musim', '')
        winter = 'Ya' if musim_input == 'Dingin' else 'Tidak'
        
        # 4. Geodesic Distance via Haversine
        def haversine_distance(lat1, lon1, lat2, lon2):
            lat1, lon1, lat2, lon2 = map(np.radians, [lat1, lon1, lat2, lon2])
            dlat = lat2 - lat1
            dlon = lon2 - lon1
            a = np.sin(dlat/2.0)**2 + np.cos(lat1) * np.cos(lat2) * np.sin(dlon/2.0)**2
            c = 2.0 * np.arcsin(np.sqrt(a))
            r = 6371.0 # km
            return c * r

        distance = 1200.0 # Default fallback
        if asal in iata_map and tujuan in iata_map:
            lat1, lon1 = iata_map[asal]
            lat2, lon2 = iata_map[tujuan]
            distance = haversine_distance(lat1, lon1, lat2, lon2)
        
        # --- HANDLING LABEL ENCODER (ANTI-CRASH) ---
        # Airline
        if maskapai in le_airline.classes_:
            airline_code = le_airline.transform([maskapai])[0]
        else:
            airline_code = le_airline.transform([le_airline.classes_[0]])[0]
            
        # Departure Airport
        if asal in le_departure_airport.classes_:
            dep_airport_code = le_departure_airport.transform([asal])[0]
        else:
            dep_airport_code = le_departure_airport.transform([le_departure_airport.classes_[0]])[0]
            
        # Arrival Airport
        if tujuan in le_arrival_airport.classes_:
            arr_airport_code = le_arrival_airport.transform([tujuan])[0]
        else:
            arr_airport_code = le_arrival_airport.transform([le_arrival_airport.classes_[0]])[0]
            
        # Rain Category
        if rain_category in le_rain_category.classes_:
            rain_code = le_rain_category.transform([rain_category])[0]
        else:
            rain_code = le_rain_category.transform([le_rain_category.classes_[0]])[0]
            
        # Winter
        if winter in le_winter.classes_:
            winter_code = le_winter.transform([winter])[0]
        else:
            winter_code = le_winter.transform([le_winter.classes_[0]])[0]

        # --- REKONSTRUKSI & NORMALISASI FITUR (SCALING) ---
        # Scale Distance and Month
        scaled_vals = scaler.transform([[distance, month_val]])[0]
        scaled_distance = scaled_vals[0]
        scaled_month = scaled_vals[1]
        
        # Model input: Airline, Departure Airport, Arrival Airport, Distance, Rain Category, Winter, Month
        features_raw = np.array([[
            airline_code, dep_airport_code, arr_airport_code,
            scaled_distance, rain_code, winter_code, scaled_month
        ]])
        
        # --- EKSEKUSI PREDIKSI MODEL ---
        prediction = model.predict(features_raw)[0]
        probabilities = model.predict_proba(features_raw)[0]
        prob_score = int(max(probabilities) * 100)
        
        status_hasil = "Tepat Waktu" if prediction == 0 else "Terlambat"
        
        print(f"LOG PREDIKSI: {maskapai} dari {asal} ke {tujuan} -> Jarak: {distance:.1f} km -> Hasil: {status_hasil} ({prob_score}%)")
        
        return jsonify({
            'status': 'success',
            'status_prediksi': status_hasil,
            'probabilitas': prob_score
        })
        
    except Exception as e:
        print(f"LOG ERROR SAAT PREDIKSI: {e}")
        return jsonify({'status': 'error', 'message': str(e)})

# =================================================================
# --- MENJALANKAN SERVER FLASK ---
# =================================================================
if __name__ == '__main__':
    # Mengaktifkan server di port 5000 (Localhost)
    app.run(port=5000, debug=True)