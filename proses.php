<?php
// =================================================================
// --- 1. KONEKSI DATABASE MYSQL ---
// =================================================================
$conn = mysqli_connect("localhost", "root", "", "db_penerbangan");

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// =================================================================
// --- 2. MENANGKAP DATA DARI FORM HTML ---
// =================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama'];
    $maskapai = $_POST['maskapai'];
    $pesawat = $_POST['pesawat'];
    $asal = $_POST['asal'];
    $tujuan = $_POST['tujuan'];
    $jam = $_POST['jam'];

    // Mengemas data menjadi array asosiatif
    $data_input = array(
        "maskapai" => $maskapai,
        "pesawat" => $pesawat,
        "asal" => $asal,
        "tujuan" => $tujuan,
        "jam" => $jam
    );

    // =================================================================
    // --- 3. KOMUNIKASI API MENGGUNAKAN cURL KE FLASK (PYTHON) ---
    // =================================================================
    // Mengubah array PHP menjadi format JSON agar terbaca oleh Flask
    $payload = json_encode($data_input);

    // Menyiapkan jalur komunikasi ke server lokal Python di port 5000
    $ch = curl_init('http://127.0.0.1:5000/predict_api');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    
    // Memberikan instruksi bahwa data yang dikirim adalah JSON murni
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload))
    );

    // Mengeksekusi request dan menangkap jawaban (response) dari Flask
    $result = curl_exec($ch);
    curl_close($ch);

    // =================================================================
    // --- 4. PEMROSESAN HASIL PREDIKSI (RESPONSE HANDLING) ---
    // =================================================================
    $status_prediksi = "Gagal Prediksi";
    $probabilitas = 0;

    if ($result) {
        // Mengubah JSON dari Flask kembali menjadi array PHP
        $response = json_decode($result, true);
        if (isset($response['status']) && $response['status'] == 'success') {
            $status_prediksi = $response['status_prediksi'];
            $probabilitas = $response['probabilitas'];
        }
    }

    // =================================================================
    // --- 5. MENYIMPAN DATA (PERSISTENCE) KE MYSQL ---
    // =================================================================
    // Merekam seluruh jejak input pengguna beserta hasil komputasi model
    $query_insert = "INSERT INTO riwayat_prediksi (nama, maskapai, pesawat, asal, tujuan, jam_terbang, status_prediksi, probabilitas) 
                     VALUES ('$nama', '$maskapai', '$pesawat', '$asal', '$tujuan', '$jam', '$status_prediksi', '$probabilitas')";
    
    // Menyalakan alarm error eksekusi
    $eksekusi = mysqli_query($conn, $query_insert);

    if (!$eksekusi) {
        // Jika gagal kode akan berhenti di sini dan mencetak alasan penolakannya
        die("GAGAL MENYIMPAN KE DATABASE: " . mysqli_error($conn));
    }
    // =================================================================
    // --- 6. REDIRECT KE DASHBOARD ---
    // =================================================================
    // Mengembalikan pengguna ke halaman index.php untuk melihat hasil visualnya
    header("Location: index.php");
    exit();
}
?>