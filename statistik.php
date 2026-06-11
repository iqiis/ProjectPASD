<?php
$conn = mysqli_connect("127.0.0.1:3307", "root", "", "db_penerbangan");

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// =================================================================
// --- 1. PENGUMPULAN DATA STATISTIK UNTUK GRAFIK ---
// =================================================================
// Menghitung jumlah penerbangan yang diprediksi Tepat Waktu
$query_ontime = mysqli_query($conn, "SELECT COUNT(*) as total FROM riwayat_prediksi WHERE status_prediksi = 'Tepat Waktu'");
$data_ontime = mysqli_fetch_assoc($query_ontime);
$total_ontime = $data_ontime['total'];

// Menghitung jumlah penerbangan yang diprediksi Terlambat
$query_delay = mysqli_query($conn, "SELECT COUNT(*) as total FROM riwayat_prediksi WHERE status_prediksi = 'Terlambat'");
$data_delay = mysqli_fetch_assoc($query_delay);
$total_delay = $data_delay['total'];

// Mengambil nama untuk header profil
$query_user = mysqli_query($conn, "SELECT nama FROM riwayat_prediksi ORDER BY id DESC LIMIT 1");
$user_data = mysqli_fetch_array($query_user);
$nama_user = $user_data ? $user_data['nama'] : "Pengguna";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BlekpinkFlight - Statistik</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden">

    <aside class="w-64 bg-[#0F172A] text-white flex flex-col shadow-xl">
        <div class="p-6 text-2xl font-bold tracking-wider">BlekpinkFlight</div>
        <nav class="flex-1 mt-10 space-y-1">
            <a href="index.php" class="flex items-center px-6 py-3.5 text-slate-400 hover:bg-slate-800/50 hover:text-white transition">
                <i class="fas fa-th-large text-lg"></i><span class="ml-4">Dashboard</span>
            </a>
            <a href="form.html" class="flex items-center px-6 py-3.5 text-slate-400 hover:bg-slate-800/50 hover:text-white transition">
                <i class="fas fa-plane-departure text-lg"></i><span class="ml-4">Prediksi</span>
            </a>
            <a href="riwayat.php" class="flex items-center px-6 py-3.5 text-slate-400 hover:bg-slate-800/50 hover:text-white transition">
                <i class="fas fa-history text-lg"></i><span class="ml-4">Riwayat</span>
            </a>
            <a href="statistik.php" class="flex items-center px-6 py-3.5 bg-sky-600/10 border-r-4 border-sky-400 text-sky-400 font-semibold group">
                <i class="fas fa-chart-bar text-lg"></i><span class="ml-4">Statistik</span>
            </a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col bg-slate-50">
        <header class="bg-white p-4 pr-8 flex justify-end items-center space-x-4 shadow-sm border-b border-slate-100">
            <div class="text-right">
                <p class="text-sm font-bold text-slate-900"><?php echo $nama_user; ?></p>
                <p class="text-xs text-sky-500 font-medium">User</p>
            </div>
            <div class="w-11 h-11 bg-slate-200 rounded-lg flex items-center justify-center border border-slate-300">
                <i class="fas fa-user text-slate-400 text-xl"></i>
            </div>
        </header>

        <div class="p-10 overflow-y-auto">
            <div class="mb-10 border-b border-slate-100 pb-5">
                <h1 class="text-3xl font-bold text-slate-900">Analisis Statistik Model</h1>
                <p class="text-slate-500 mt-1">Visualisasi distribusi data riset dan rekam metrik dari database nyata.</p>
            </div>

            <div class="grid grid-cols-2 gap-8 mb-8">
                <div class="bg-white p-7 rounded-2xl shadow-sm border border-slate-100">
                    <h2 class="font-bold text-slate-700 mb-6 uppercase text-xs tracking-widest flex items-center">
                        <i class="fas fa-chart-pie mr-2 text-sky-400"></i> Distribusi Ketepatan Waktu
                    </h2>
                    <div class="h-64 flex justify-center items-center">
                        <canvas id="pieChart" style="max-height: 100%; max-width: 250px;"></canvas>
                    </div>
                </div>

                <div class="bg-white p-7 rounded-2xl shadow-sm border border-slate-100">
                    <h2 class="font-bold text-slate-700 mb-6 uppercase text-xs tracking-widest flex items-center">
                        <i class="fas fa-chart-line mr-2 text-sky-400"></i> Tren Prediksi Bulanan
                    </h2>
                    <div class="h-64 flex justify-center items-center">
                        <canvas id="lineChart" style="max-height: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // 1. Menerapkan Data PHP ke dalam Pie Chart
        const ctxPie = document.getElementById('pieChart').getContext('2d');
        new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: ['Tepat Waktu', 'Terlambat'],
                datasets: [{
                    // Mengambil nilai komputasi langsung dari Query MySQL
                    data: [<?php echo $total_ontime; ?>, <?php echo $total_delay; ?>],
                    backgroundColor: ['#10B981', '#EF4444'], // Hijau Zamrud untuk Ontime, Merah untuk Terlambat
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        // 2. Menerapkan Data Dummy dan Real-Time ke Line Chart
        const ctxLine = document.getElementById('lineChart').getContext('2d');
        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Bulan Ini'],
                datasets: [{
                    label: 'Total Aktivitas Prediksi',
                    // Menyatukan data simulasi dan data rill ($total_ontime + $total_delay)
                    data: [15, 22, 18, 45, <?php echo ($total_ontime + $total_delay); ?>],
                    borderColor: '#38BDF8',
                    backgroundColor: 'rgba(56, 189, 248, 0.1)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    </script>
</body>
</html>