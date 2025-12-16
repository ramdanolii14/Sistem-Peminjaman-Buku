<?php
include '../koneksi.php';
include '../auth_check.php';

if ($_SESSION['role'] != 'Admin') {
    header("Location: ../login.php");
    exit;
}

$stats = [];

$q_buku = "SELECT COUNT(id_buku) AS total_buku, SUM(stok_tersedia) AS stok_tersedia FROM BUKU";
$r_buku = $conn->query($q_buku)->fetch_assoc();
$stats['total_buku'] = $r_buku['total_buku'] ?? 0;
$stats['stok_tersedia'] = $r_buku['stok_tersedia'] ?? 0;

$q_mhs = "SELECT COUNT(id_mahasiswa) AS total_mahasiswa FROM MAHASISWA";
$stats['total_mahasiswa'] = $conn->query($q_mhs)->fetch_assoc()['total_mahasiswa'] ?? 0;

$q_pinjam_aktif = "SELECT COUNT(id_peminjaman) AS aktif FROM PEMINJAMAN WHERE status_pinjam = 'Dipinjam'";
$stats['buku_dipinjam'] = $conn->query($q_pinjam_aktif)->fetch_assoc()['aktif'] ?? 0;

$q_terlambat = "SELECT COUNT(id_peminjaman) AS terlambat FROM PEMINJAMAN WHERE status_pinjam = 'Dipinjam' AND tanggal_kembali_harus < CURDATE()";
$stats['buku_terlambat'] = $conn->query($q_terlambat)->fetch_assoc()['terlambat'] ?? 0;


$query_riwayat = "
    SELECT 
        p.id_peminjaman, p.tanggal_pinjam, p.tanggal_kembali_harus, p.status_pinjam, 
        m.nim, m.nama_lengkap, b.judul
    FROM 
        PEMINJAMAN p
    JOIN MAHASISWA m ON p.id_mahasiswa = m.id_mahasiswa
    JOIN DETAIL_PEMINJAMAN dp ON p.id_peminjaman = dp.id_peminjaman
    JOIN BUKU b ON dp.id_buku = b.id_buku
    ORDER BY 
        p.id_peminjaman DESC
    LIMIT 10
";
$riwayat_peminjaman = $conn->query($query_riwayat)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan & Statistik | Admin Perpustakaan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f4f7fb; color: #333; margin: 0; padding: 0; }
        .sidebar { height: 100%; width: 250px; position: fixed; z-index: 1; top: 0; left: 0; background-color: #2c3e50; padding-top: 20px; color: white; }
        .sidebar h2 { text-align: center; margin-bottom: 30px; color: #1abc9c; }
        .sidebar a { padding: 10px 15px; text-decoration: none; font-size: 16px; color: #ecf0f1; display: block; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; color: #1abc9c; }
        .content { margin-left: 250px; padding: 40px; }
        .header { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .header h1 { margin: 0; color: #2c3e50; }

        .card-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .card { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; }
        .card-title { font-size: 1em; color: #555; margin-bottom: 5px; }
        .card-number { font-size: 2.5em; font-weight: 700; color: #1abc9c; }
        .card.danger .card-number { color: #e74c3c; }
        .card.warning .card-number { color: #f39c12; }

        .table-card { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; font-size: 0.9em; }
        th { background-color: #f2f2f2; font-weight: 600; color: #2c3e50; }
        .status-dipinjam { background-color: #f39c12; color: white; padding: 3px 8px; border-radius: 4px; font-weight: 600; }
        .status-selesai { background-color: #27ae60; color: white; padding: 3px 8px; border-radius: 4px; font-weight: 600; }
        .status-terlambat { background-color: #e74c3c; color: white; padding: 3px 8px; border-radius: 4px; font-weight: 600; }

    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="tambah_buku.php">📚 Tambah Buku</a>
        <a href="daftar_buku.php">📖 Daftar Buku</a>
        <a href="daftar_mahasiswa.php">👥 Kelola Mahasiswa</a>
        <a href="daftar_peminjaman.php">📝 Kelola Peminjaman</a>
        <a href="laporan.php" class="active">⚙️ Laporan</a>
        <a href="../logout.php" style="position: absolute; bottom: 20px; width: 100%; text-align: center; background: #e74c3c;">Keluar</a>
    </div>

    <div class="content">
        <div class="header">
            <h1>Laporan & Statistik Perpustakaan</h1>
        </div>
        
        <div class="card-container">
            <div class="card">
                <div class="card-title">Total Buku Katalog</div>
                <div class="card-number"><?= $stats['total_buku'] ?></div>
            </div>
            <div class="card warning">
                <div class="card-title">Stok Tersedia</div>
                <div class="card-number"><?= $stats['stok_tersedia'] ?></div>
            </div>
            <div class="card">
                <div class="card-title">Total Mahasiswa Terdaftar</div>
                <div class="card-number"><?= $stats['total_mahasiswa'] ?></div>
            </div>
            <div class="card">
                <div class="card-title">Buku Sedang Dipinjam</div>
                <div class="card-number"><?= $stats['buku_dipinjam'] ?></div>
            </div>
            <div class="card danger">
                <div class="card-title">Buku Terlambat</div>
                <div class="card-number"><?= $stats['buku_terlambat'] ?></div>
            </div>
        </div>

        <div class="table-card">
            <h2>Riwayat 10 Transaksi Terakhir</h2>
            <?php if (empty($riwayat_peminjaman)): ?>
                <p>Belum ada riwayat peminjaman.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID Transaksi</th>
                            <th>Mahasiswa</th>
                            <th>Judul Buku</th>
                            <th>Tgl Pinjam</th>
                            <th>Harus Kembali</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($riwayat_peminjaman as $pjm): 
                            $status_class = strtolower($pjm['status_pinjam']);
                            $status_class = str_replace(' ', '-', $status_class);
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($pjm['id_peminjaman']) ?></td>
                                <td><?= htmlspecialchars($pjm['nama_lengkap']) ?> (<?= htmlspecialchars($pjm['nim']) ?>)</td>
                                <td><?= htmlspecialchars($pjm['judul']) ?></td>
                                <td><?= date('d M Y', strtotime($pjm['tanggal_pinjam'])) ?></td>
                                <td><?= date('d M Y', strtotime($pjm['tanggal_kembali_harus'])) ?></td>
                                <td><span class="status-<?= $status_class ?>"><?= htmlspecialchars($pjm['status_pinjam']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>