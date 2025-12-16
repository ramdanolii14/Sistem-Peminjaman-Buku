<?php
include '../koneksi.php';
include '../auth_check.php';

if ($_SESSION['role'] != 'Mahasiswa') {
    header("Location: ../admin/dashboard.php"); 
    exit;
}

$search_term = '';
$filter_clause = '';
$params = [];
$types = '';

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_term = '%' . trim($_GET['search']) . '%';
    $filter_clause = " WHERE (b.judul LIKE ? OR b.pengarang LIKE ? OR b.isbn LIKE ?) ";
    $params = [&$search_term, &$search_term, &$search_term];
    $types = 'sss';
}

// MODIFIKASI: Menambahkan b.cover_path ke dalam query SELECT
$query = "
    SELECT 
        b.id_buku, b.judul, b.pengarang, b.penerbit, b.tahun_terbit, b.isbn, b.stok_tersedia, b.cover_path
    FROM 
        BUKU b
    " . $filter_clause . "
    ORDER BY b.judul ASC
";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    array_unshift($params, $types);
    call_user_func_array([$stmt, 'bind_param'], $params);
}

$stmt->execute();
$buku_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Buku | Mahasiswa Perpustakaan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f0f4f8; color: #333; margin: 0; padding: 0; }
        .header { background-color: #3498db; color: white; padding: 25px 50px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 1.8em; }
        .nav a { color: white; text-decoration: none; margin-left: 20px; font-weight: 600; padding: 8px 15px; border-radius: 4px; transition: background-color 0.3s; }
        .nav a:hover, .nav a.active { background-color: #2980b9; }
        .content { padding: 40px 50px; }
        h2 { border-bottom: 3px solid #3498db; padding-bottom: 10px; margin-top: 20px; color: #2c3e50; }

        .search-container { margin-bottom: 30px; display: flex; gap: 10px; }
        .search-container input[type="text"] { flex-grow: 1; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 1em; }
        .search-container button { padding: 12px 20px; background-color: #2ecc71; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .search-container button:hover { background-color: #27ae60; }
        
        .book-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px; }
        
        /* MODIFIKASI CSS UNTUK TAMPILAN COVER DAN DETAIL */
        .book-item { 
            background-color: white; 
            padding: 25px; 
            border-radius: 10px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); 
            border-left: 5px solid #3498db; 
            display: flex; /* Aktifkan flexbox */
            gap: 20px; 
            align-items: flex-start;
        }

        .book-cover-container {
            flex-shrink: 0; 
            width: 100px; 
            height: 150px; 
            border: 1px solid #eee;
            border-radius: 4px;
            overflow: hidden;
        }

        .book-cover-container img {
            width: 100%;
            height: 100%;
            object-fit: cover; 
            display: block;
        }

        .book-details {
            flex-grow: 1; 
        }
        /* END MODIFIKASI CSS */

        .book-title { font-size: 1.3em; font-weight: 700; color: #2c3e50; margin-bottom: 5px; }
        .book-author { font-style: italic; color: #555; margin-bottom: 10px; }
        .book-info { font-size: 0.9em; margin-bottom: 15px; }
        .book-stock { font-weight: 600; color: #27ae60; }
        .book-stock.out { color: #e74c3c; }
        .action-button { 
            display: inline-block; 
            padding: 10px 15px; 
            background-color: #3498db; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px; 
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .action-button:hover { background-color: #2980b9; }
        .action-button.disabled { background-color: #95a5a6; cursor: not-allowed; }
        
        .empty-state { text-align: center; padding: 50px; background: #ecf0f1; border-radius: 8px; color: #7f8c8d; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Katalog Buku</h1>
        <div class="nav">
            <a href="dashboard.php">📚 Peminjaman Saya</a>
            <a href="katalog.php" class="active">📖 Katalog Buku</a>
            <a href="../logout.php" style="background-color: #e74c3c;">Keluar</a>
        </div>
    </div>

    <div class="content">
        <h2>Cari dan Pinjam Buku</h2>

        <div class="search-container">
            <form method="GET" action="katalog.php" style="display: flex; width: 100%; gap: 10px;">
                <input type="text" name="search" placeholder="Cari Judul, Pengarang, atau ISBN..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                <button type="submit">Cari</button>
            </form>
        </div>

        <?php if (!empty($buku_list)): ?>
            <div class="book-list">
                <?php foreach ($buku_list as $buku): 
                    $is_available = $buku['stok_tersedia'] > 0;
                    $stock_class = $is_available ? 'book-stock' : 'book-stock out';
                    $button_class = $is_available ? 'action-button' : 'action-button disabled';
                    $button_text = $is_available ? 'Ajukan Peminjaman' : 'Stok Kosong';
                    
                    // Tentukan path gambar cover
                    $cover_path = !empty($buku['cover_path']) ? 
                                  '../' . htmlspecialchars($buku['cover_path']) : 
                                  '../assets/images/no_cover.png'; // GANTI DENGAN PATH PLACEHOLDER ANDA!
                ?>
                    <div class="book-item">
                        
                        <div class="book-cover-container">
                            <img 
                                src="<?= $cover_path ?>" 
                                alt="Cover <?= htmlspecialchars($buku['judul']) ?>"
                            >
                        </div>
                        
                        <div class="book-details">
                            <div class="book-title"><?= htmlspecialchars($buku['judul']) ?></div>
                            <div class="book-author">Oleh: <?= htmlspecialchars($buku['pengarang']) ?></div>
                            <div class="book-info">
                                <p>Penerbit: <?= htmlspecialchars($buku['penerbit']) ?> (<?= htmlspecialchars($buku['tahun_terbit']) ?>)</p>
                                <p>ISBN: <?= htmlspecialchars($buku['isbn']) ?></p>
                                <p class="<?= $stock_class ?>">Stok Tersedia: <?= $buku['stok_tersedia'] ?></p>
                            </div>
                            
                            <?php if ($is_available): ?>
                                <a href="ajukan_pinjam.php?id_buku=<?= $buku['id_buku'] ?>" class="<?= $button_class ?>">
                                    <?= $button_text ?>
                                </a>
                            <?php else: ?>
                                 <span class="<?= $button_class ?>">
                                    <?= $button_text ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>Tidak ditemukan buku dengan kriteria pencarian **"<?= htmlspecialchars($_GET['search'] ?? '') ?>"**.</p>
                <a href="katalog.php" class="action-button" style="margin-top: 15px;">Tampilkan Semua Buku</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>