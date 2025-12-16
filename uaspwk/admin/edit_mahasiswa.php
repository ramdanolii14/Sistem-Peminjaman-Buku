<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../koneksi.php';
include '../auth_check.php';

if ($_SESSION['role'] != 'Admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';
$error = '';
$mahasiswa_data = null;

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: daftar_mahasiswa.php");
    exit;
}

$id_user = (int)$_GET['id'];
$id_mahasiswa = 0;
if (isset($_POST['submit'])) {
    $id_mahasiswa = (int)$_POST['id_mahasiswa'];
    $username     = trim($_POST['username']);
    $email        = trim($_POST['email']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $nim          = trim($_POST['nim']);
    $fakultas     = trim($_POST['fakultas']);
    $jurusan      = trim($_POST['jurusan']);
    $angkatan     = trim($_POST['angkatan']);
    $password_new = trim($_POST['password_new']);
    
    $conn->begin_transaction();
    
    try {
        $check_nim = $conn->prepare("SELECT COUNT(*) FROM MAHASISWA WHERE nim = ? AND id_mahasiswa != ?");
        $check_nim->bind_param("si", $nim, $id_mahasiswa);
        $check_nim->execute();
        $nim_count = $check_nim->get_result()->fetch_row()[0];
        $check_nim->close();

        if ($nim_count > 0) {
            throw new Exception("NIM $nim sudah terdaftar pada mahasiswa lain.");
        }

        $check_email = $conn->prepare("SELECT COUNT(*) FROM USER WHERE email = ? AND id_user != ?");
        $check_email->bind_param("si", $email, $id_user);
        $check_email->execute();
        $email_count = $check_email->get_result()->fetch_row()[0];
        $check_email->close();

        if ($email_count > 0) {
            throw new Exception("Email $email sudah digunakan oleh akun lain.");
        }
        
        $update_user_query = "UPDATE USER SET username=?, email=? ";
        $params_user = "ss";
        $params_array = [&$username, &$email];
        
        if (!empty($password_new)) {
            $hashed_password = password_hash($password_new, PASSWORD_DEFAULT);
            $update_user_query .= ", password=? ";
            $params_user .= "s";
            $params_array[] = &$hashed_password;
        }

        $update_user_query .= "WHERE id_user=?";
        $params_user .= "i";
        $params_array[] = &$id_user;
        
        $stmt_user = $conn->prepare($update_user_query);
        
        array_unshift($params_array, $params_user);
        call_user_func_array([$stmt_user, 'bind_param'], $params_array);

        $stmt_user->execute();
        $stmt_user->close();

        $stmt_mhs = $conn->prepare("
            UPDATE MAHASISWA 
            SET nim=?, nama_lengkap=?, fakultas=?, jurusan=?, angkatan=?
            WHERE id_mahasiswa=?
        ");
        $stmt_mhs->bind_param("sssssi", $nim, $nama_lengkap, $fakultas, $jurusan, $angkatan, $id_mahasiswa);
        $stmt_mhs->execute();
        $stmt_mhs->close();

        $conn->commit();
        $message = "Data mahasiswa **" . htmlspecialchars($nama_lengkap) . "** berhasil diperbarui.";

    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}
$stmt_get = $conn->prepare("
    SELECT 
        u.id_user, u.username, u.email,
        r.nama_role AS role, /* KOREKSI: Mengganti r.role menjadi r.nama_role */
        m.id_mahasiswa, m.nim, m.nama_lengkap, m.fakultas, m.jurusan, m.angkatan
    FROM 
        USER u
    JOIN 
        MAHASISWA m ON u.id_user = m.id_user
    JOIN 
        USER_ROLE ur ON u.id_user = ur.id_user 
    JOIN 
        ROLE r ON ur.id_role = r.id_role
    WHERE 
        u.id_user = ? AND r.nama_role = 'Mahasiswa' /* KOREKSI: Mengganti r.role menjadi r.nama_role */
");
$stmt_get->bind_param("i", $id_user);
$stmt_get->execute();
$result_get = $stmt_get->get_result();

if ($result_get->num_rows > 0) {
    $mahasiswa_data = $result_get->fetch_assoc();
    $id_mahasiswa = $mahasiswa_data['id_mahasiswa'];
} else {
    header("Location: daftar_mahasiswa.php");
    exit;
}
$stmt_get->close();

if (isset($_POST['submit']) && !$error) {
    $stmt_reload = $conn->prepare("
        SELECT 
            u.id_user, u.username, u.email, 
            r.nama_role AS role, /* KOREKSI: Mengganti r.role menjadi r.nama_role */
            m.id_mahasiswa, m.nim, m.nama_lengkap, m.fakultas, m.jurusan, m.angkatan
        FROM 
            USER u
        JOIN 
            MAHASISWA m ON u.id_user = m.id_user
        JOIN 
            USER_ROLE ur ON u.id_user = ur.id_user 
        JOIN 
            ROLE r ON ur.id_role = r.id_role
        WHERE 
            u.id_user = ?
    ");
    $stmt_reload->bind_param("i", $id_user);
    $stmt_reload->execute();
    $mahasiswa_data = $stmt_reload->get_result()->fetch_assoc();
    $stmt_reload->close();
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Mahasiswa | Admin Perpustakaan</title>
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
        
        .form-card { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .form-group small { color: #888; font-size: 0.8em; }
        button[type="submit"] { background-color: #2980b9; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; font-weight: 600; }
        button[type="submit"]:hover { background-color: #2563eb; }
        
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
        <a href="daftar_mahasiswa.php" class="active">👥 Kelola Mahasiswa</a>
        <a href="daftar_peminjaman.php">📝 Kelola Peminjaman</a>
        <a href="laporan.php">⚙️ Laporan</a>
        <a href="../logout.php" style="position: absolute; bottom: 20px; width: 100%; text-align: center; background: #e74c3c;">Keluar</a>
    </div>

    <div class="content">
        <div class="header">
            <h1>Edit Akun Mahasiswa: <?= htmlspecialchars($mahasiswa_data['nama_lengkap']) ?></h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">Gagal: <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" action="edit_mahasiswa.php?id=<?= $id_user ?>">
                <input type="hidden" name="id_mahasiswa" value="<?= htmlspecialchars($mahasiswa_data['id_mahasiswa']) ?>">

                <h2>Data Mahasiswa (Tabel MAHASISWA)</h2>
                
                <div class="form-group">
                    <label for="nama_lengkap">Nama Lengkap</label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" value="<?= htmlspecialchars($mahasiswa_data['nama_lengkap']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="nim">NIM</label>
                    <input type="text" id="nim" name="nim" value="<?= htmlspecialchars($mahasiswa_data['nim']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="fakultas">Fakultas</label>
                    <input type="text" id="fakultas" name="fakultas" value="<?= htmlspecialchars($mahasiswa_data['fakultas']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="jurusan">Jurusan</label>
                    <input type="text" id="jurusan" name="jurusan" value="<?= htmlspecialchars($mahasiswa_data['jurusan']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="angkatan">Angkatan</label>
                    <input type="number" id="angkatan" name="angkatan" min="1990" max="<?= date('Y') ?>" value="<?= htmlspecialchars($mahasiswa_data['angkatan']) ?>" required>
                </div>

                <hr style="margin: 30px 0;">

                <h2>Data Akun (Tabel USER)</h2>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($mahasiswa_data['username']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($mahasiswa_data['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="password_new">Ganti Password (Kosongkan jika tidak ingin diubah)</label>
                    <input type="password" id="password_new" name="password_new" placeholder="Masukkan password baru">
                    <small>Minimal 8 karakter.</small>
                </div>
                
                <button type="submit" name="submit">Simpan Perubahan Akun & Data</button>
            </form>
        </div>
    </div>
</body>
</html>