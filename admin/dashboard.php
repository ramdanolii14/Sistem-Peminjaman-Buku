<?php
include '../koneksi.php';
include '../auth_check.php';

if ($_SESSION['role'] != 'Admin') {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$query = $conn->prepare("
    SELECT u.username, u.email, r.nama_role 
    FROM USER u
    JOIN USER_ROLE ur ON u.id_user = ur.id_user
    JOIN ROLE r ON ur.id_role = r.id_role
    WHERE u.id_user = ?
");
$query->bind_param("i", $id_user);
$query->execute();
$result = $query->get_result();
$user_data = $result->fetch_assoc();
$query->close();

$total_buku = $conn->query("SELECT SUM(stok_tersedia) FROM BUKU")->fetch_row()[0] ?? 0;

$buku_dipinjam = $conn->query("SELECT COUNT(*) FROM PEMINJAMAN WHERE status_pinjam = 'Dipinjam'")->fetch_row()[0] ?? 0;

$total_mahasiswa = $conn->query("SELECT COUNT(*) FROM MAHASISWA")->fetch_row()[0] ?? 0;

$buku_terlambat = $conn->query("
    SELECT COUNT(*) 
    FROM PEMINJAMAN 
    WHERE status_pinjam = 'Dipinjam' AND tanggal_kembali_harus < CURDATE()
")->fetch_row()[0] ?? 0;

$query_log = "
    SELECT 
        p.id_peminjaman, p.tanggal_pinjam, p.status_pinjam,
        m.nama_lengkap AS nama_mahasiswa, b.judul AS judul_buku
    FROM 
        PEMINJAMAN p
    JOIN 
        MAHASISWA m ON p.id_mahasiswa = m.id_mahasiswa
    JOIN 
        DETAIL_PEMINJAMAN dp ON p.id_peminjaman = dp.id_peminjaman
    JOIN 
        BUKU b ON dp.id_buku = b.id_buku
    ORDER BY 
        p.id_peminjaman DESC
    LIMIT 5
";
$result_log = $conn->query($query_log);
$log_aktivitas = $result_log->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Perpustakaan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f4f7fb; color: #333; margin: 0; padding: 0; }
        .sidebar { height: 100%; width: 250px; position: fixed; z-index: 1; top: 0; left: 0; background-color: #2c3e50; padding-top: 20px; color: white; }
        .sidebar h2 { text-align: center; margin-bottom: 30px; color: #1abc9c; }
        .sidebar a { padding: 10px 15px; text-decoration: none; font-size: 16px; color: #ecf0f1; display: block; transition: 0.3s; }
        .sidebar a:hover, .sidebar a[href*="dashboard.php"] { background-color: #34495e; color: #1abc9c; }
        .content { margin-left: 250px; padding: 40px; }
        .header { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; color: #2c3e50; }
        .user-info { text-align: right; }
        .user-info span { display: block; font-weight: 600; }
        .logout-btn { background-color: #e74c3c; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-weight: 600; margin-top: 10px; display: inline-block; }
        .logout-btn:hover { background-color: #c0392b; }
        .card-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .card { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .card h3 { color: #1abc9c; margin-top: 0; }
        .log-table th, .log-table td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        .log-table th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <a href="dashboard.php" class="active">🏠 Dashboard</a>
        <a href="tambah_buku.php">📚 Tambah Buku</a>
        <a href="daftar_buku.php">📖 Daftar Buku</a>
        <a href="daftar_mahasiswa.php">👥 Kelola Mahasiswa</a>
        <a href="daftar_peminjaman.php">📝 Kelola Peminjaman</a>
        <a href="laporan.php">⚙️ Laporan</a>
        <a href="../logout.php" style="position: absolute; bottom: 20px; width: 100%; text-align: center; background: #e74c3c;">Keluar</a>
    </div>

    <div class="content">
        <div class="header">
            <h1>Selamat Datang, <?php echo htmlspecialchars($user_data['username']); ?></h1>
            <div class="user-info">
                <span>Role: <?php echo htmlspecialchars($user_data['nama_role']); ?></span>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
        
        <div class="card-container">
            <div class="card">
                <h3>Total Buku (Stok Tersedia)</h3>
                <p style="font-size: 2em; font-weight: 700;"><?= number_format($total_buku) ?></p>
            </div>
            <div class="card">
                <h3>Buku Dipinjam</h3>
                <p style="font-size: 2em; font-weight: 700;"><?= number_format($buku_dipinjam) ?></p>
            </div>
            <div class="card">
                <h3>Total Mahasiswa</h3>
                <p style="font-size: 2em; font-weight: 700;"><?= number_format($total_mahasiswa) ?></p>
            </div>
            <div class="card">
                <h3>Buku Terlambat</h3>
                <p style="font-size: 2em; font-weight: 700; color: #e74c3c;"><?= number_format($buku_terlambat) ?></p>
            </div>
        </div>

        <div class="card" style="margin-top: 30px;">
            <h3>Aktivitas Peminjaman Terbaru (5 Data Terakhir)</h3>
            <?php if (!empty($log_aktivitas)): ?>
            <table class="log-table" style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                <thead>
                    <tr>
                        <th>ID Pinjam</th>
                        <th>Mahasiswa</th>
                        <th>Buku</th>
                        <th>Tgl. Pinjam</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($log_aktivitas as $log): 
                        $status_color = '#2c3e50';
                        if ($log['status_pinjam'] == 'Dipinjam') $status_color = '#2ecc71';
                        elseif ($log['status_pinjam'] == 'Pending') $status_color = '#f39c12';
                        elseif ($log['status_pinjam'] == 'Ditolak') $status_color = '#e74c3c';
                    ?>
                    <tr>
                        <td><?= $log['id_peminjaman'] ?></td>
                        <td><?= htmlspecialchars($log['nama_mahasiswa']) ?></td>
                        <td><?= htmlspecialchars($log['judul_buku']) ?></td>
                        <td><?= date('d M Y', strtotime($log['tanggal_pinjam'])) ?></td>
                        <td style="font-weight: 600; color: <?= $status_color ?>;">
                            <?= htmlspecialchars($log['status_pinjam']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>Belum ada aktivitas peminjaman yang tercatat.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>