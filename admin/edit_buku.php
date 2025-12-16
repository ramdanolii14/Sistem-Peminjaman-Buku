<?php
include '../koneksi.php';
include '../auth_check.php';

if ($_SESSION['role'] != 'Admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';
$error = '';
$buku_data = null;

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: daftar_buku.php");
    exit;
}

$id_buku = (int)$_GET['id'];

// 1. Ambil data buku saat ini (termasuk path cover)
$stmt_get = $conn->prepare("SELECT * FROM BUKU WHERE id_buku = ?");
$stmt_get->bind_param("i", $id_buku);
$stmt_get->execute();
$result_get = $stmt_get->get_result();

if ($result_get->num_rows > 0) {
    $buku_data = $result_get->fetch_assoc();
} else {
    header("Location: daftar_buku.php");
    exit;
}
$stmt_get->close();

$current_cover_path = $buku_data['cover_path'];
$new_cover_path = $current_cover_path; // Default: pertahankan path lama

if (isset($_POST['submit'])) {
    $judul      = trim($_POST['judul']);
    $pengarang  = trim($_POST['pengarang']);
    $penerbit   = trim($_POST['penerbit']);
    $tahun      = trim($_POST['tahun_terbit']);
    $stok_total = (int)trim($_POST['stok_total']);
    $stok_tersedia = (int)trim($_POST['stok_tersedia']);
    $isbn       = trim($_POST['isbn']);
    $hapus_cover = isset($_POST['hapus_cover']); // Cek apakah checkbox hapus dicentang

    if ($stok_tersedia > $stok_total) {
        $error = "Stok tersedia tidak boleh melebihi Stok Total.";
    } else {
        try {
            // --- START: LOGIKA UPLOAD/HAPUS FILE COVER ---
            $target_dir = "../assets/covers/"; 
            $max_size = 8 * 1024 * 1024; // 8 MB

            // Skenario 1: Pengguna mengunggah file baru
            if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] == UPLOAD_ERR_OK) {
                
                $file_info = $_FILES['cover_file'];
                $file_size = $file_info['size'];
                $file_tmp = $file_info['tmp_name'];
                $file_name = basename($file_info['name']);

                $allowed_ext = ['jpg', 'jpeg', 'png'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                if (!in_array($file_ext, $allowed_ext)) {
                    throw new Exception("Hanya file JPG, JPEG, dan PNG yang diizinkan untuk cover.");
                } elseif ($file_size > $max_size) {
                    throw new Exception("Ukuran file maksimal adalah 8MB. File Anda: " . round($file_size / 1048576, 2) . " MB.");
                }

                // Hapus file lama jika ada
                if ($current_cover_path && file_exists("../" . $current_cover_path)) {
                    unlink("../" . $current_cover_path);
                }

                // Simpan file baru
                $new_file_name = uniqid('cover_edit_') . '.' . $file_ext;
                $target_file = $target_dir . $new_file_name;
                
                if (move_uploaded_file($file_tmp, $target_file)) {
                    $new_cover_path = "assets/covers/" . $new_file_name; 
                } else {
                    throw new Exception("Gagal mengupload file cover baru.");
                }
            
            // Skenario 2: Pengguna ingin menghapus cover lama
            } elseif ($hapus_cover) {
                if ($current_cover_path && file_exists("../" . $current_cover_path)) {
                    unlink("../" . $current_cover_path);
                }
                $new_cover_path = NULL; // Set path menjadi NULL di database

            // Skenario 3: Tidak ada upload dan tidak ada hapus
            } else {
                $new_cover_path = $current_cover_path; // Pertahankan path lama
            }
            // --- END: LOGIKA UPLOAD/HAPUS FILE COVER ---
            
            // Cek ISBN unik
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM BUKU WHERE isbn = ? AND id_buku != ?");
            $check_stmt->bind_param("si", $isbn, $id_buku);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result()->fetch_row()[0];
            $check_stmt->close();

            if ($check_result > 0) {
                throw new Exception("ISBN $isbn sudah terdaftar pada buku lain.");
            }

            // MODIFIKASI: Menambahkan cover_path ke UPDATE statement
            $stmt = $conn->prepare("
                UPDATE BUKU 
                SET judul=?, pengarang=?, penerbit=?, tahun_terbit=?, stok_total=?, stok_tersedia=?, isbn=?, cover_path=?
                WHERE id_buku=?
            ");
            
            // KOREKSI PENTING: bind_param diset ke "ssssiissi" (s untuk isbn, s untuk cover_path)
            $stmt->bind_param("ssssiissi", $judul, $pengarang, $penerbit, $tahun, $stok_total, $stok_tersedia, $isbn, $new_cover_path, $id_buku);

            if ($stmt->execute()) {
                $message = "Data buku **" . htmlspecialchars($judul) . "** berhasil diperbarui.";
                
                // Update $buku_data agar form menampilkan data yang terbaru
                $buku_data['judul'] = $judul;
                $buku_data['pengarang'] = $pengarang;
                $buku_data['penerbit'] = $penerbit;
                $buku_data['tahun_terbit'] = $tahun;
                $buku_data['stok_total'] = $stok_total;
                $buku_data['stok_tersedia'] = $stok_tersedia;
                $buku_data['isbn'] = $isbn;
                $buku_data['cover_path'] = $new_cover_path;

            } else {
                throw new Exception("Gagal memperbarui data buku. Error: " . $stmt->error);
            }

            $stmt->close();

        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Jika form POST gagal (misalnya karena error ISBN atau stok), data buku yang ditampilkan 
// harus sesuai dengan data POST, kecuali $buku_data sudah di-update di blok success.
if (isset($_POST['submit']) && $error) {
    // Isi ulang form dengan data POST yang gagal (untuk UX)
    $buku_data['judul'] = $_POST['judul'];
    $buku_data['pengarang'] = $_POST['pengarang'];
    $buku_data['penerbit'] = $_POST['penerbit'];
    $buku_data['tahun_terbit'] = $_POST['tahun_terbit'];
    $buku_data['stok_total'] = $_POST['stok_total'];
    $buku_data['stok_tersedia'] = $_POST['stok_tersedia'];
    $buku_data['isbn'] = $_POST['isbn'];
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Buku | Admin Perpustakaan</title>
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
        .form-group input[type="file"] { border: none; padding-left: 0; } 
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
        <a href="daftar_buku.php" class="active">📖 Daftar Buku</a>
        <a href="daftar_mahasiswa.php">👥 Kelola Mahasiswa</a>
        <a href="daftar_peminjaman.php">📝 Kelola Peminjaman</a>
        <a href="laporan.php">⚙️ Laporan</a>
        <a href="../logout.php" style="position: absolute; bottom: 20px; width: 100%; text-align: center; background: #e74c3c;">Keluar</a>
    </div>

    <div class="content">
        <div class="header">
            <h1>Edit Data Buku: <?= htmlspecialchars($buku_data['judul']) ?></h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">Gagal: <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" action="edit_buku.php?id=<?= $id_buku ?>" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="judul">Judul Buku</label>
                    <input type="text" id="judul" name="judul" value="<?= htmlspecialchars($buku_data['judul']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="pengarang">Pengarang</label>
                    <input type="text" id="pengarang" name="pengarang" value="<?= htmlspecialchars($buku_data['pengarang']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="penerbit">Penerbit</label>
                    <input type="text" id="penerbit" name="penerbit" value="<?= htmlspecialchars($buku_data['penerbit']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="isbn">ISBN</label>
                    <input type="text" id="isbn" name="isbn" value="<?= htmlspecialchars($buku_data['isbn']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Cover Buku Saat Ini</label>
                    <div style="margin-bottom: 10px;">
                        <?php 
                        $display_path = !empty($buku_data['cover_path']) ? 
                                        '../' . htmlspecialchars($buku_data['cover_path']) : 
                                        '../assets/images/no_cover.png'; 
                        ?>
                        <img src="<?= $display_path ?>" alt="Cover Buku" style="max-width: 100px; height: auto; border: 1px solid #ccc; border-radius: 4px;">
                    </div>

                    <label for="cover_file">Ganti Cover (Max 8MB, JPG/PNG)</label>
                    <input type="file" id="cover_file" name="cover_file" accept=".jpg, .jpeg, .png">

                    <?php if (!empty($buku_data['cover_path'])): ?>
                        <div style="margin-top: 10px;">
                            <input type="checkbox" id="hapus_cover" name="hapus_cover" value="1">
                            <label for="hapus_cover" style="display: inline; font-weight: 400;">Hapus Cover Saat Ini</label>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="tahun_terbit">Tahun Terbit</label>
                    <input type="number" id="tahun_terbit" name="tahun_terbit" min="1900" max="<?= date('Y') ?>" value="<?= htmlspecialchars($buku_data['tahun_terbit']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="stok_total">Stok Total (Jumlah fisik keseluruhan)</label>
                    <?php $min_stok_total = $buku_data['stok_total'] - $buku_data['stok_tersedia']; ?>
                    <input type="number" id="stok_total" name="stok_total" min="<?= $min_stok_total ?>" value="<?= htmlspecialchars($buku_data['stok_total']) ?>" required>
                    <small>Nilai minimum adalah <?= $min_stok_total ?> (jumlah yang sedang dipinjam).</small>
                </div>

                <div class="form-group">
                    <label for="stok_tersedia">Stok Tersedia (Siap dipinjam)</label>
                    <input type="number" id="stok_tersedia" name="stok_tersedia" min="0" value="<?= htmlspecialchars($buku_data['stok_tersedia']) ?>" required>
                    <small>Pastikan Stok Tersedia ≤ Stok Total.</small>
                </div>
                
                <button type="submit" name="submit">Simpan Perubahan</button>
            </form>
        </div>
    </div>
</body>
</html>