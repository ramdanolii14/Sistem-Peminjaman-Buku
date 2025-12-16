<?php
include '../koneksi.php';
include '../auth_check.php';

if ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Kepala Perpustakaan') {
    header("Location: ../login.php");
    exit;
}


$query = "
    SELECT 
        p.id_peminjaman, p.tanggal_pinjam, p.tanggal_kembali_harus, p.status_pinjam, 
        m.nama_lengkap AS nama_mahasiswa, m.nim,
        b.judul AS judul_buku
    FROM 
        PEMINJAMAN p
    JOIN 
        MAHASISWA m ON p.id_mahasiswa = m.id_mahasiswa
    JOIN 
        DETAIL_PEMINJAMAN dp ON p.id_peminjaman = dp.id_peminjaman
    JOIN 
        BUKU b ON dp.id_buku = b.id_buku
    ORDER BY 
        p.status_pinjam DESC, p.tanggal_pinjam ASC
";

$result = $conn->query($query);
$peminjaman_list = $result->fetch_all(MYSQLI_ASSOC);

$message = isset($_GET['message']) ? htmlspecialchars(urldecode($_GET['message'])) : '';
$error = isset($_GET['error']) ? htmlspecialchars(urldecode($_GET['error'])) : '';

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Peminjaman | Admin Perpustakaan</title>
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
        
        .table-container { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px 15px; border: 1px solid #ddd; text-align: left; font-size: 0.9em; }
        th { background-color: #2c3e50; color: white; font-weight: 600; }
        tr:nth-child(even) { background-color: #f9f9f9; }

        .status-tag { padding: 5px 10px; border-radius: 15px; font-weight: 600; font-size: 0.8em; text-align: center; }
        .status-pending { background-color: #f39c12; color: white; }
        .status-dipinjam { background-color: #2ecc71; color: white; }
        .status-dikembalikan { background-color: #3498db; color: white; }
        .status-ditolak { background-color: #e74c3c; color: white; }

        .action-button { padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9em; font-weight: 600; text-decoration: none; display: inline-block; margin-right: 5px; }
        .btn-setujui { background-color: #27ae60; color: white; }
        .btn-tolak { background-color: #c0392b; color: white; }
        .btn-kembali { background-color: #2980b9; color: white; }
        
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; font-weight: 600; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="tambah_buku.php">📚 Tambah Buku</a>
        <a href="daftar_buku.php">📖 Daftar Buku</a>
        <a href="daftar_mahasiswa.php">👥 Kelola Mahasiswa</a>
        <a href="daftar_peminjaman.php" class="active">📝 Kelola Peminjaman</a>
        <a href="laporan.php">⚙️ Laporan</a>
        <a href="../logout.php" style="position: absolute; bottom: 20px; width: 100%; text-align: center; background: #e74c3c;">Keluar</a>
    </div>

    <div class="content">
        <div class="header">
            <h1>Kelola Peminjaman Buku</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">Gagal: <?= $error ?></div>
        <?php endif; ?>

        <div class="table-container">
            <?php if (count($peminjaman_list) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID Pinjam</th>
                        <th>Mahasiswa (NIM)</th>
                        <th>Buku</th>
                        <th>Tgl Pinjam</th>
                        <th>Batas Kembali</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($peminjaman_list as $p): 
                        // Tentukan kelas status
                        $status_class = 'status-' . strtolower(str_replace(' ', '-', $p['status_pinjam']));
                    ?>
                    <tr>
                        <td><?= $p['id_peminjaman'] ?></td>
                        <td><?= htmlspecialchars($p['nama_mahasiswa']) ?> (<?= htmlspecialchars($p['nim']) ?>)</td>
                        <td><?= htmlspecialchars($p['judul_buku']) ?></td>
                        <td><?= date('d M Y', strtotime($p['tanggal_pinjam'])) ?></td>
                        <td><?= date('d M Y', strtotime($p['tanggal_kembali_harus'])) ?></td>
                        <td><span class="status-tag <?= $status_class ?>"><?= htmlspecialchars($p['status_pinjam']) ?></span></td>
                        <td>
                            <?php if ($p['status_pinjam'] == 'Pending'): ?>
                                <a href="proses_peminjaman.php?action=setujui&id=<?= $p['id_peminjaman'] ?>" class="action-button btn-setujui" onclick="return confirm('Yakin menyetujui peminjaman ini?')">Setujui</a>
                                <a href="proses_peminjaman.php?action=tolak&id=<?= $p['id_peminjaman'] ?>" class="action-button btn-tolak" onclick="return confirm('Yakin menolak peminjaman ini? Stok akan dikembalikan.')">Tolak</a>
                            <?php elseif ($p['status_pinjam'] == 'Dipinjam'): ?>
                                <a href="proses_peminjaman.php?action=kembali&id=<?= $p['id_peminjaman'] ?>" class="action-button btn-kembali" onclick="return confirm('Yakin buku sudah dikembalikan?')">Tandai Kembali</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>Belum ada data peminjaman yang tercatat.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>