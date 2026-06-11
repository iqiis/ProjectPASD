<?php
// =================================================================
// --- 1. KONEKSI KE DATABASE MYSQL ---
// =================================================================
$conn = mysqli_connect("127.0.0.1:3307", "root", "", "db_penerbangan");

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// =================================================================
// --- 2. LOGIKA PENGAMBILAN DATA (QUERY) ---
// =================================================================
// Mengambil 1 baris data paling baru yang baru saja diprediksi dari tabel
$query_latest = mysqli_query($conn, "SELECT * FROM riwayat_prediksi ORDER BY id DESC LIMIT 1");
$latest = mysqli_fetch_array($query_latest);

// Mencegah error 'Undefined Variable' dengan memberikan nilai default jika database kosong
$nama = $latest ? $latest['nama'] : "Pengguna Baru";
$pesawat = $latest ? $latest['pesawat'] : "-";
$rute = $latest ? $latest['asal'] . " → " . $latest['tujuan'] : "-";
$status = $latest ? $latest['status_prediksi'] : "Belum Ada";
$prob = $latest ? $latest['probabilitas'] . "%" : "0%";

// Menghitung total seluruh transaksi prediksi yang ada di sistem
$query_total = mysqli_query($conn, "SELECT COUNT(*) as total FROM riwayat_prediksi");
$data_total = mysqli_fetch_assoc($query_total);
$total_prediksi = $data_total['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BlekpinkFlight - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden">

    <aside class="w-64 bg-[#0F172A] text-white flex flex-col shadow-xl">
        <div class="p-6 text-2xl font-bold tracking-wider">BlekpinkFlight</div>
        <nav class="flex-1 mt-10 space-y-1">
            <a href="index.php" class="flex items-center px-6 py-3.5 bg-sky-600/10 border-r-4 border-sky-400 text-sky-400 font-semibold group">
                <i class="fas fa-th-large text-lg"></i><span class="ml-4">Dashboard</span>
            </a>
            <a href="form.html" class="flex items-center px-6 py-3.5 text-slate-400 hover:bg-slate-800/50 hover:text-white transition">
                <i class="fas fa-plane-departure text-lg"></i><span class="ml-4">Prediksi</span>
            </a>
            <a href="riwayat.php" class="flex items-center px-6 py-3.5 text-slate-400 hover:bg-slate-800/50 hover:text-white transition">
                <i class="fas fa-history text-lg"></i><span class="ml-4">Riwayat</span>
            </a>
            <a href="statistik.php" class="flex items-center px-6 py-3.5 text-slate-400 hover:bg-slate-800/50 hover:text-white transition">
                <i class="fas fa-chart-bar text-lg"></i><span class="ml-4">Statistik</span>
            </a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col bg-slate-50">
        
        <header class="bg-white p-4 pr-8 flex justify-end items-center space-x-4 shadow-sm border-b border-slate-100">
            <div class="text-right">
                <p class="text-sm font-bold text-slate-900"><?php echo $nama; ?></p>
                <p class="text-xs text-sky-500 font-medium">User</p>
            </div>
            <div class="w-11 h-11 bg-slate-200 rounded-lg flex items-center justify-center border border-slate-300">
                <i class="fas fa-user text-slate-400 text-xl"></i>
            </div>
        </header>

        <div class="p-10 overflow-y-auto">
            
            <div class="mb-10 flex justify-between items-end">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">Selamat Datang, <?php echo $nama; ?>! 👋</h1>
                    <p class="text-slate-500 mt-1">Berikut adalah ringkasan hasil prediksi penerbangan terbaru Anda.</p>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-6 mb-8">
                
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center">
                    <div class="w-14 h-14 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-500 mr-5">
                        <i class="fas fa-plane text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 font-medium mb-1">Hasil Prediksi Model</p>
                        <?php if($status == "Tepat Waktu") { ?>
                            <h3 class="text-2xl font-bold text-emerald-500"><?php echo $status; ?></h3>
                        <?php } else { ?>
                            <h3 class="text-2xl font-bold text-rose-500"><?php echo $status; ?></h3>
                        <?php } ?>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center">
                    <div class="w-14 h-14 rounded-full bg-sky-50 flex items-center justify-center text-sky-500 mr-5">
                        <i class="fas fa-percentage text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 font-medium mb-1">Tingkat Probabilitas</p>
                        <h3 class="text-2xl font-bold text-slate-800"><?php echo $prob; ?></h3>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center">
                    <div class="w-14 h-14 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-500 mr-5">
                        <i class="fas fa-route text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 font-medium mb-1">Rute Penerbangan</p>
                        <h3 class="text-2xl font-bold text-slate-800"><?php echo $rute; ?></h3>
                    </div>
                </div>
            </div>

            <div class="bg-slate-900 rounded-2xl p-8 text-white relative overflow-hidden">
                <div class="relative z-10 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold mb-2">Total Data Prediksi Sistem</h2>
                        <p class="text-slate-400">Total interaksi data yang telah diolah oleh BlekpinkFlight.</p>
                    </div>
                    <div class="text-5xl font-extrabold text-sky-400"><?php echo $total_prediksi; ?></div>
                </div>
                <div class="absolute right-0 top-0 w-64 h-full bg-gradient-to-l from-sky-500/20 to-transparent"></div>
            </div>

        </div>
    </main>
</body>
</html>