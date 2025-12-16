<?php
include '../koneksi.php';
include '../auth_check.php';

// Pastikan hanya Mahasiswa yang bisa mengakses
if ($_SESSION['role'] != 'Mahasiswa') {
    header("Location: ../admin/dashboard.php"); 
    exit;
}

$id_user = $_SESSION['id_user'];

// --- 1. Ambil Data Mahasiswa ---
$stmt_mhs = $conn->prepare("SELECT id_mahasiswa, nama_lengkap, nim FROM MAHASISWA WHERE id_user = ?");
$stmt_mhs->bind_param("i", $id_user);
$stmt_mhs->execute();
$mahasiswa_info = $stmt_mhs->get_result()->fetch_assoc();
$id_mahasiswa = $mahasiswa_info['id_mahasiswa'] ?? 0;
$nama_mahasiswa = $mahasiswa_info['nama_lengkap'] ?? 'Pengguna';
$stmt_mhs->close();

// --- 2. Ambil Data Buku Dipinjam (Aktif) ---
$buku_dipinjam = [];

// MODIFIKASI: Menambahkan b.cover_path
$query_pinjam = "
    SELECT 
        p.id_peminjaman, p.tanggal_pinjam, p.tanggal_kembali_harus, p.status_pinjam, 
        b.judul, b.pengarang, b.isbn, b.cover_path,
        CASE 
            WHEN p.tanggal_kembali_harus < CURDATE() THEN 
                DATEDIFF(CURDATE(), p.tanggal_kembali_harus)
            ELSE 
                0 
        END AS denda_hari
    FROM 
        PEMINJAMAN p
    JOIN DETAIL_PEMINJAMAN dp ON p.id_peminjaman = dp.id_peminjaman
    JOIN BUKU b ON dp.id_buku = b.id_buku
    WHERE 
        p.id_mahasiswa = ? AND p.status_pinjam = 'Dipinjam'
    ORDER BY 
        denda_hari DESC, p.tanggal_kembali_harus ASC
";

$stmt_pinjam = $conn->prepare($query_pinjam);
$stmt_pinjam->bind_param("i", $id_mahasiswa);
$stmt_pinjam->execute();
$buku_dipinjam = $stmt_pinjam->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_pinjam->close();

$total_dipinjam = count($buku_dipinjam);
$total_terlambat = 0;
$total_denda = 0; 
$DENDA_PER_HARI = 1000; 

foreach($buku_dipinjam as $buku) {
    if ($buku['denda_hari'] > 0) {
        $total_terlambat++;
        $total_denda += $buku['denda_hari'] * $DENDA_PER_HARI;
    }
}

// --- 3. Hitung Riwayat Transaksi ---
$q_riwayat = "SELECT COUNT(DISTINCT id_peminjaman) AS total FROM PEMINJAMAN WHERE id_mahasiswa = ?";
$stmt_riwayat = $conn->prepare($q_riwayat);
$stmt_riwayat->bind_param("i", $id_mahasiswa);
$stmt_riwayat->execute();
$total_riwayat = $stmt_riwayat->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_riwayat->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa | Perpustakaan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f0f4f8; color: #333; margin: 0; padding: 0; }
        .header { background-color: #3498db; color: white; padding: 25px 50px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 1.8em; }
        .nav a { color: white; text-decoration: none; margin-left: 20px; font-weight: 600; padding: 8px 15px; border-radius: 4px; transition: background-color 0.3s; }
        .nav a:hover { background-color: #2980b9; }
        .content { padding: 40px 50px; }

        .card-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; margin-bottom: 40px; }
        .card { background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: transform 0.2s; }
        .card:hover { transform: translateY(-5px); }
        .card-title { font-size: 1em; color: #555; margin-bottom: 5px; }
        .card-number { font-size: 2.5em; font-weight: 700; color: #3498db; }
        .card.danger .card-number, .card.danger .card-number small { color: #e74c3c; }
        .card.warning .card-number, .card.warning .card-number small { color: #f39c12; }
        
        h2 { border-bottom: 3px solid #3498db; padding-bottom: 10px; margin-top: 40px; color: #2c3e50; }
        .book-list { display: flex; flex-direction: column; gap: 20px; }
        
        /* CSS BARU untuk Cover dan Layout Item */
        .book-item { 
            background-color: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
            border-left: 5px solid #3498db; 
            display: flex; /* Menggunakan Flexbox */
            gap: 20px;
        }
        .book-item.terlambat { border-left: 5px solid #e74c3c; }
        
        .book-cover { flex-shrink: 0; } /* Cover tidak mengecil */
        .book-cover img { max-width: 80px; height: auto; border-radius: 4px; border: 1px solid #ddd; }
        
        .book-info { flex-grow: 1; } /* Info buku mengambil sisa ruang */
        /* Akhir CSS BARU */
        
        .book-title { font-size: 1.2em; font-weight: 600; color: #2c3e50; margin-bottom: 5px; }
        .book-meta { font-size: 0.9em; color: #777; margin-bottom: 10px; }
        .book-due { display: flex; justify-content: space-between; align-items: center; padding-top: 10px; border-top: 1px dashed #eee; margin-top: 5px; }
        .due-date { font-weight: 600; color: #27ae60; }
        .terlambat .due-date { color: #e74c3c; font-size: 1.1em; }
        .status-tag { background-color: #f39c12; color: white; padding: 4px 10px; border-radius: 15px; font-size: 0.8em; font-weight: 600; }
        .terlambat .status-tag { background-color: #c0392b; }
        
        .empty-state { text-align: center; padding: 50px; background: #ecf0f1; border-radius: 8px; color: #7f8c8d; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Selamat Datang, <?= htmlspecialchars($nama_mahasiswa) ?>!</h1>
        <div class="nav">
            <a href="dashboard.php" class="active">📚 Peminjaman Saya</a>
            <a href="katalog.php">📖 Katalog Buku</a>
            <a href="../logout.php" style="background-color: #e74c3c;">Keluar</a>
        </div>
    </div>

    <div class="content">
        
        <div class="card-container">
            <div class="card">
                <div class="card-title">Buku Aktif Dipinjam</div>
                <div class="card-number"><?= $total_dipinjam ?></div>
                <small>Buku yang wajib Anda kembalikan.</small>
            </div>
            <div class="card danger">
                <div class="card-title">Status Terlambat</div>
                <div class="card-number"><?= $total_terlambat ?></div>
                <small>Buku yang sudah melewati batas waktu.</small>
            </div>
            <div class="card warning">
                <div class="card-title">Estimasi Denda (IDR)</div>
                <div class="card-number">
                    <?= number_format($total_denda, 0, ',', '.') ?>
                    <small style="font-size: 0.5em; display: block; font-weight: 600;">(Rp <?= number_format($DENDA_PER_HARI, 0, ',', '.') ?> / hari)</small>
                </div>
            </div>
            <div class="card">
                <div class="card-title">Riwayat Transaksi</div>
                <div class="card-number"><?= $total_riwayat ?></div>
                <small>Total peminjaman yang pernah Anda lakukan.</small>
            </div>
        </div>

        <h2>Buku yang Sedang Anda Pinjam Saat Ini</h2>
        
        <?php if ($total_dipinjam > 0): ?>
            <div class="book-list">
                <?php foreach ($buku_dipinjam as $buku): 
                    $is_terlambat = $buku['denda_hari'] > 0;
                    $class = $is_terlambat ? 'terlambat' : '';
                    $tgl_kembali = date('d F Y', strtotime($buku['tanggal_kembali_harus']));
                    $status_text = $is_terlambat ? 'TERLAMBAT (' . $buku['denda_hari'] . ' hari)' : 'AKTIF';
                    $due_color = $is_terlambat ? '#e74c3c' : '#27ae60';
                    
                    $cover_path = !empty($buku['cover_path']) ? 
                                    '../' . htmlspecialchars($buku['cover_path']) : 
                                    '../assets/images/no_cover.png';
                ?>
                    <div class="book-item <?= $class ?>">
                        
                        <div class="book-cover">
                            <img src="<?= $cover_path ?>" alt="Cover <?= htmlspecialchars($buku['judul']) ?>">
                        </div>
                        <div class="book-info">
                            <div class="book-title"><?= htmlspecialchars($buku['judul']) ?></div>
                            <div class="book-meta">
                                Oleh: <?= htmlspecialchars($buku['pengarang']) ?> | ISBN: <?= htmlspecialchars($buku['isbn']) ?>
                            </div>
                            <div class="book-due">
                                <span>Dipinjam Sejak: <?= date('d F Y', strtotime($buku['tanggal_pinjam'])) ?></span>
                                <span class="status-tag"><?= $status_text ?></span>
                            </div>
                            <div class="book-due">
                                <span>**Batas Pengembalian**</span>
                                <span class="due-date" style="color: <?= $due_color ?>;"><?= $tgl_kembali ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>Yah, tidak ada buku yang sedang Anda pinjam saat ini.</p>
                <p>Silakan kunjungi **Katalog Buku** untuk menemukan dan memulai peminjaman.</p>
                <a href="katalog.php" style="display: inline-block; margin-top: 15px; padding: 10px 20px; background-color: #3498db; color: white; text-decoration: none; border-radius: 4px; font-weight: 600;">Lihat Katalog Buku</a>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>