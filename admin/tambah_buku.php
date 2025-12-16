<?php
include '../koneksi.php';
include '../auth_check.php';

if ($_SESSION['role'] != 'Admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';
$error = '';
$database_path = NULL; // Variabel untuk menyimpan path cover di database

if (isset($_POST['submit'])) {
    $judul      = trim($_POST['judul']);
    $pengarang  = trim($_POST['pengarang']);
    $penerbit   = trim($_POST['penerbit']);
    $tahun      = trim($_POST['tahun_terbit']);
    $stok       = (int)trim($_POST['stok_total']);
    $isbn       = trim($_POST['isbn']);
    
    // --- START: LOGIKA UPLOAD FILE COVER ---
    if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] == UPLOAD_ERR_OK) {
        
        $file_info = $_FILES['cover_file'];
        $file_name = basename($file_info['name']);
        $file_size = $file_info['size'];
        $file_tmp = $file_info['tmp_name'];

        // Folder tujuan upload (pastikan '../assets/covers/' sudah ada dan writable)
        $target_dir = "../assets/covers/"; 
        
        // Cek ekstensi dan ukuran
        $max_size = 8 * 1024 * 1024; // 8 MB
        $allowed_ext = ['jpg', 'jpeg', 'png'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_ext)) {
            $error = "Hanya file JPG, JPEG, dan PNG yang diizinkan untuk cover.";
        } elseif ($file_size > $max_size) {
            $error = "Ukuran file maksimal adalah 8MB. File Anda: " . round($file_size / 1048576, 2) . " MB.";
        } else {
            // Buat nama file unik untuk keamanan
            $new_file_name = uniqid('cover_') . '.' . $file_ext;
            $target_file = $target_dir . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $target_file)) {
                // Path yang disimpan di database (relatif dari root)
                $database_path = "assets/covers/" . $new_file_name; 
            } else {
                $error = "Gagal memindahkan file cover ke server.";
            }
        }
    }
    // --- END: LOGIKA UPLOAD FILE COVER ---

    // Lanjutkan ke proses database hanya jika tidak ada error upload
    if (!$error) {
        try {
            // 1. Cek ISBN
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM BUKU WHERE isbn = ?");
            $check_stmt->bind_param("s", $isbn);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result()->fetch_row()[0];
            $check_stmt->close();

            if ($check_result > 0) {
                throw new Exception("ISBN $isbn sudah terdaftar dalam katalog buku.");
            }

            // 2. INSERT DATA BUKU termasuk cover_path
            $stmt = $conn->prepare("
                INSERT INTO BUKU (judul, pengarang, penerbit, tahun_terbit, stok_total, stok_tersedia, isbn, cover_path)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            // Perhatikan urutan dan tipe data: 's' terakhir untuk cover_path
            $stmt->bind_param("ssssiiss", $judul, $pengarang, $penerbit, $tahun, $stok, $stok, $isbn, $database_path);

            if ($stmt->execute()) {
                $message = "Buku **" . htmlspecialchars($judul) . "** berhasil ditambahkan ke katalog.";
            } else {
                throw new Exception("Gagal menyimpan data buku. Error: " . $stmt->error);
            }

            $stmt->close();

        } catch (Exception $e) {
            // Rollback: Jika INSERT gagal, hapus file yang sudah ter-upload
            if ($database_path && file_exists("../" . $database_path)) {
                 unlink("../" . $database_path);
            }
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Buku | Admin Perpustakaan</title>
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
        /* Style khusus untuk input file agar tetap rapi */
        .form-group input[type="file"] { border: none; padding: 0; } 
        button[type="submit"] { background-color: #1abc9c; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; font-weight: 600; }
        button[type="submit"]:hover { background-color: #16a085; }
        
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; font-weight: 600; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="tambah_buku.php" class="active">📚 Tambah Buku</a>
        <a href="daftar_buku.php">📖 Daftar Buku</a>
        <a href="daftar_mahasiswa.php">👥 Kelola Mahasiswa</a>
        <a href="daftar_peminjaman.php">📝 Kelola Peminjaman</a>
        <a href="laporan.php">⚙️ Laporan</a>
        <a href="../logout.php" style="position: absolute; bottom: 20px; width: 100%; text-align: center; background: #e74c3c;">Keluar</a>
    </div>

    <div class="content">
        <div class="header">
            <h1>Tambah Buku Baru</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">Gagal: <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="judul">Judul Buku</label>
                    <input type="text" id="judul" name="judul" required value="<?= htmlspecialchars($_POST['judul'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="pengarang">Pengarang</label>
                    <input type="text" id="pengarang" name="pengarang" required value="<?= htmlspecialchars($_POST['pengarang'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="penerbit">Penerbit</label>
                    <input type="text" id="penerbit" name="penerbit" required value="<?= htmlspecialchars($_POST['penerbit'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="isbn">ISBN (International Standard Book Number)</label>
                    <input type="text" id="isbn" name="isbn" required value="<?= htmlspecialchars($_POST['isbn'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="cover_file">Cover Buku (Max 8MB, JPG/PNG)</label>
                    <input type="file" id="cover_file" name="cover_file" accept=".jpg, .jpeg, .png">
                </div>
                
                <div class="form-group">
                    <label for="tahun_terbit">Tahun Terbit</label>
                    <input type="number" id="tahun_terbit" name="tahun_terbit" min="1900" max="<?= date('Y') ?>" required value="<?= htmlspecialchars($_POST['tahun_terbit'] ?? date('Y')) ?>">
                </div>
                
                <div class="form-group">
                    <label for="stok_total">Jumlah Stok Awal</label>
                    <input type="number" id="stok_total" name="stok_total" min="1" required value="<?= htmlspecialchars($_POST['stok_total'] ?? '') ?>">
                    <small>Stok Tersedia akan sama dengan Stok Awal.</small>
                </div>
                
                <button type="submit" name="submit">Simpan Buku</button>
            </form>
        </div>
    </div>
</body>
</html>