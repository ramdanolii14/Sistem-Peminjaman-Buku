<?php
include '../koneksi.php';
include '../auth_check.php';

if ($_SESSION['role'] != 'Admin') {
    header("Location: dashboard.php");
    exit;
}

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = MD5($_POST['password']); 
    $nama_lengkap = $_POST['nama_lengkap'];
    $email = $_POST['email'];
    $role_id = (int)$_POST['role_id']; 

    $conn->begin_transaction();

    try {
        $stmt_user = $conn->prepare("INSERT INTO USER (username, password, email) VALUES (?, ?, ?)");
        $stmt_user->bind_param("sss", $username, $password, $email);
        $stmt_user->execute();
        
        $id_user_baru = $conn->insert_id; 
        $stmt_user->close();

        $stmt_role = $conn->prepare("INSERT INTO USER_ROLE (id_user, id_role) VALUES (?, ?)");
        $stmt_role->bind_param("ii", $id_user_baru, $role_id);
        $stmt_role->execute();
        $stmt_role->close();
        
        $conn->commit();
        $message = "Admin/Petugas Baru ('{$nama_lengkap}') dengan username '{$username}' berhasil ditambahkan!";

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Gagal menambahkan admin: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Admin Baru | Admin Perpustakaan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f4f7fb; color: #333; margin: 0; padding: 0; }
        .sidebar { height: 100%; width: 250px; position: fixed; z-index: 1; top: 0; left: 0; background-color: #2c3e50; padding-top: 20px; color: white; }
        .sidebar h2 { text-align: center; margin-bottom: 30px; color: #1abc9c; }
        .sidebar a { padding: 10px 15px; text-decoration: none; font-size: 16px; color: #ecf0f1; display: block; transition: 0.3s; }
        .sidebar a:hover, .sidebar a[href*="tambah_admin.php"] { background-color: #34495e; color: #1abc9c; }
        .content { margin-left: 250px; padding: 40px; }
        .card { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-submit { background-color: #27ae60; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .alert-success { background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .alert-error { background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="daftar_buku.php">📚 Daftar Buku</a>
        <a href="daftar_mahasiswa.php">👥 Kelola Mahasiswa</a>
        <a href="daftar_peminjaman.php">📝 Kelola Peminjaman</a>
        <a href="tambah_admin.php" class="active">👤 Tambah Admin</a>
        <a href="laporan.php">⚙️ Laporan</a>
        <a href="../logout.php" style="position: absolute; bottom: 20px; width: 100%; text-align: center; background: #e74c3c;">Keluar</a>
    </div>

    <div class="content">
        <h1>Tambah Admin/Kepala Perpustakaan Baru</h1>
        <p>Gunakan halaman ini untuk mendaftarkan akun staf perpustakaan.</p>
        
        <?php if ($message): ?>
            <div class="alert-success"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert-error">Gagal: <?= $error ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" action="">
                
                <div class="form-group">
                    <label for="username">Username (Untuk Login)</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="nama_lengkap">Nama Lengkap</label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="role_id">Pilih Peran</label>
                    <select id="role_id" name="role_id" required>
                        <option value="1">Admin/Petugas Perpustakaan</option>
                        <option value="2">Kepala Perpustakaan</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-submit">Daftarkan Admin Baru</button>
            </form>
        </div>
    </div>
</body>
</html>