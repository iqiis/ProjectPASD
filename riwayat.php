<?php
// Melakukan koneksi ke database
$conn = mysqli_connect("127.0.0.1:3307", "root", "", "db_penerbangan");

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Menarik seluruh data riwayat dari yang paling terbaru
$query = mysqli_query($conn, "SELECT * FROM riwayat_prediksi ORDER BY id DESC");

// Mengambil nama user terakhir untuk ditampilkan di pojok kanan atas
$query_user = mysqli_query($conn, "SELECT nama FROM riwayat_prediksi ORDER BY id DESC LIMIT 1");
$user_data = mysqli_fetch_array($query_user);
$nama_user = $user_data ? $user_data['nama'] : "Pengguna";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BlekpinkFlight - Riwayat</title>
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
            <a href="index.php" class="flex items-center px-6 py-3.5 text-slate-400 hover:bg-slate-800/50 hover:text-white transition">
                <i class="fas fa-th-large text-lg"></i><span class="ml-4">Dashboard</span>
            </a>
            <a href="form.html" class="flex items-center px-6 py-3.5 text-slate-400 hover:bg-slate-800/50 hover:text-white transition">
                <i class="fas fa-plane-departure text-lg"></i><span class="ml-4">Prediksi</span>
            </a>
            <a href="riwayat.php" class="flex items-center px-6 py-3.5 bg-sky-600/10 border-r-4 border-sky-400 text-sky-400 font-semibold group">
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
                <p class="text-sm font-bold text-slate-900"><?php echo $nama_user; ?></p>
                <p class="text-xs text-sky-500 font-medium">User</p>
            </div>
            <div class="w-11 h-11 bg-slate-200 rounded-lg flex items-center justify-center border border-slate-300">
                <i class="fas fa-user text-slate-400 text-xl"></i>
            </div>
        </header>

        <div class="p-10 overflow-y-auto">
            <div class="mb-8 border-b border-slate-100 pb-5 flex justify-between items-end">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">Log Riwayat Prediksi</h1>
                    <p class="text-slate-500 mt-2">Daftar keseluruhan data masukan dan hasil keputusan algoritma.</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-sm uppercase tracking-wider border-b border-slate-100">
                            <th class="p-5 font-bold">Waktu Sistem</th>
                            <th class="p-5 font-bold">Tanggal Terbang</th>
                            <th class="p-5 font-bold">Maskapai</th>
                            <th class="p-5 font-bold">Rute</th>
                            <th class="p-5 font-bold">Jam</th>
                            <th class="p-5 font-bold">Musim & Hujan</th>
                            <th class="p-5 font-bold text-center">Status (Model)</th>
                            <th class="p-5 font-bold text-center">Probabilitas</th>
                        </tr>
                    </thead>
                    <tbody class="text-slate-700 text-sm">
                        <?php while($row = mysqli_fetch_array($query)) { ?>
                        <tr class="border-b border-slate-50 hover:bg-slate-50 transition">
                            <td class="p-5"><?php echo $row['created_at']; ?></td>
                            <td class="p-5"><?php echo $row['tanggal_penerbangan'] ? $row['tanggal_penerbangan'] : '-'; ?></td>
                            <td class="p-5 font-semibold"><?php echo $row['maskapai']; ?> <br><span class="text-xs text-slate-400 font-normal"><?php echo $row['pesawat']; ?></span></td>
                            <td class="p-5"><?php echo $row['asal']; ?> &rarr; <?php echo $row['tujuan']; ?></td>
                            <td class="p-5"><?php echo $row['jam_terbang']; ?></td>
                            <td class="p-5 font-medium"><?php echo $row['musim'] ? $row['musim'] : '-'; ?> <br><span class="text-xs text-slate-400 font-normal"><?php echo $row['kategori_hujan'] ? $row['kategori_hujan'] : '-'; ?></span></td>
                            <td class="p-5 text-center">
                                <?php if($row['status_prediksi'] == 'Tepat Waktu') { ?>
                                    <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-xs font-bold">Tepat Waktu</span>
                                <?php } else { ?>
                                    <span class="bg-rose-100 text-rose-700 px-3 py-1 rounded-full text-xs font-bold">Terlambat</span>
                                <?php } ?>
                            </td>
                            <td class="p-5 text-center font-bold"><?php echo $row['probabilitas']; ?>%</td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>