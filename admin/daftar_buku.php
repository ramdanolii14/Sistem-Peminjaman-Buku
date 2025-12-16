<?php
include '../koneksi.php';
include '../auth_check.php';
if ($_SESSION['role'] != 'Admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';
$error = '';

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_buku = (int)$_GET['id'];

    try {
        $stmt_del = $conn->prepare("DELETE FROM BUKU WHERE id_buku = ?");
        $stmt_del->bind_param("i", $id_buku);

        if ($stmt_del->execute()) {
            if ($stmt_del->affected_rows > 0) {
                $message = "Buku berhasil dihapus dari katalog.";
            } else {
                $error = "Gagal menghapus buku. Buku tidak ditemukan.";
            }
        } else {
            if ($conn->errno == 1451) { 
                 $error = "Gagal menghapus: Buku ini sedang dipinjam dan tidak bisa dihapus.";
            } else {
                 $error = "Terjadi kesalahan saat menghapus data: " . $conn->error;
            }
        }
        $stmt_del->close();

    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}


$query = "SELECT * FROM BUKU ORDER BY id_buku ASC";
$result = $conn->query($query);
$daftar_buku = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $daftar_buku[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Buku | Admin Perpustakaan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f4f7fb; color: #333; margin: 0; padding: 0; }
        .sidebar { height: 100%; width: 250px; position: fixed; z-index: 1; top: 0; left: 0; background-color: #2c3e50; padding-top: 20px; color: white; }
        .sidebar h2 { text-align: center; margin-bottom: 30px; color: #1abc9c; }
        .sidebar a { padding: 10px 15px; text-decoration: none; font-size: 16px; color: #ecf0f1; display: block; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; color: #1abc9c; }
        .content { margin-left: 250px; padding: 40px; }
        .header { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; color: #2c3e50; }
        .btn-tambah { background-color: #1abc9c; color: white; padding: 10px 15px; border-radius: 4px; text-decoration: none; font-weight: 600; }
        .btn-tambah:hover { background-color: #16a085; }
        .table-container { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; font-size: 0.9em; }
        th { background-color: #f2f2f2; font-weight: 600; color: #2c3e50; }
        .action-links a { margin-right: 5px; text-decoration: none; font-weight: 600; padding: 5px 8px; border-radius: 4px; display: inline-block; }
        .action-links .edit { color: #2980b9; border: 1px solid #2980b9; }
        .action-links .delete { color: #e74c3c; border: 1px solid #e74c3c; }
        .action-links .edit:hover { background-color: #2980b9; color: white; }
        .action-links .delete:hover { background-color: #e74c3c; color: white; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; font-weight: 600; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
    <script>
        function confirmDelete(judul) {
            return confirm("Apakah Anda yakin ingin menghapus buku: '" + judul + "'?\nPenghapusan tidak bisa dilakukan jika buku sedang dipinjam!");
        }
    </script>
</head>
<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="tambah_buku.php">📚 Tambah Buku</a>
        <a href="daftar_buku.php" class="active">📖 Daftar Buku</a>
        <a href="daftar_mahasiswa.php">👥 Kelola Mahasiswa</a>
        <a href="daftar_peminjaman.php">📝 Kelola Peminjaman</a>
        <a href="laporan.php">⚙️ Laporan</a>
        <a href="../logout.php" style="position: absolute; bottom: 20px; width: 100%; text-align: center; background: #e74c3c;">Keluar</a>
    </div>

    <div class="content">
        <div class="header">
            <h1>Daftar Buku Katalog</h1>
            <a href="tambah_buku.php" class="btn-tambah">Tambah Buku Baru</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">Error: <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="table-container">
            <?php if (empty($daftar_buku)): ?>
                <p>Belum ada buku yang terdaftar di katalog saat ini.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Judul</th>
                            <th>Pengarang</th>
                            <th>Penerbit</th>
                            <th>Tahun</th>
                            <th>ISBN</th>
                            <th>Stok Total</th>
                            <th>Tersedia</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftar_buku as $buku): ?>
                            <tr>
                                <td><?= htmlspecialchars($buku['id_buku']) ?></td>
                                <td><?= htmlspecialchars($buku['judul']) ?></td>
                                <td><?= htmlspecialchars($buku['pengarang']) ?></td>
                                <td><?= htmlspecialchars($buku['penerbit']) ?></td>
                                <td><?= htmlspecialchars($buku['tahun_terbit']) ?></td>
                                <td><?= htmlspecialchars($buku['isbn']) ?></td>
                                <td><?= htmlspecialchars($buku['stok_total']) ?></td>
                                <td style="font-weight: 700; color: <?= $buku['stok_tersedia'] > 0 ? '#16a085' : '#e74c3c' ?>;">
                                    <?= htmlspecialchars($buku['stok_tersedia']) ?>
                                </td>
                                <td class="action-links">
                                    <a href="edit_buku.php?id=<?= $buku['id_buku'] ?>" class="edit">Edit</a>
                                    <a href="daftar_buku.php?action=delete&id=<?= $buku['id_buku'] ?>" class="delete" 
                                       onclick="return confirmDelete('<?= htmlspecialchars($buku['judul']) ?>');">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>