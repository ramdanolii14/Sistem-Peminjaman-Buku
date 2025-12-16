<?php
include 'koneksi.php';

$query_buku = "SELECT judul, pengarang, penerbit, tahun_terbit, stok_tersedia FROM BUKU ORDER BY judul ASC LIMIT 20";
$result_buku = $conn->query($query_buku);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Buku - Perpustakaan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f4f7fb; color: #333; margin: 0; padding: 0; }
        .navbar { background: #2563eb; color: white; padding: 15px 50px; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: white; text-decoration: none; margin-left: 20px; font-weight: 500; }
        .container { max-width: 1200px; margin: 40px auto; padding: 20px; background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #2563eb; margin-bottom: 30px; }
        .book-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        .book-card { border: 1px solid #ddd; padding: 20px; border-radius: 8px; transition: box-shadow 0.3s; background: #fff; }
        .book-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.15); }
        .book-card h3 { color: #000; margin-top: 0; font-size: 1.2rem; }
        .book-card p { margin: 5px 0; font-size: 0.9rem; color: #555; }
        .stok { font-weight: 600; color: <?= ($row['stok_tersedia'] > 0) ? '#10b981' : '#dc2626'; ?>; }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="logo">
            <a href="index.php">Perpustakaan Digital</a>
        </div>
        <div class="nav-links">
            <a href="katalog.php">Katalog</a>
            <a href="login.php">Login</a>
            <a href="register.php">Daftar</a>
        </div>
    </div>

    <div class="container">
        <h1>Katalog Buku Perpustakaan</h1>
        <?php if ($result_buku->num_rows > 0): ?>
            <div class="book-list">
                <?php while ($row = $result_buku->fetch_assoc()): ?>
                    <div class="book-card">
                        <h3><?= htmlspecialchars($row['judul']); ?></h3>
                        <p><strong>Penulis:</strong> <?= htmlspecialchars($row['pengarang']); ?></p>
                        <p><strong>Penerbit:</strong> <?= htmlspecialchars($row['penerbit']); ?> (<?= htmlspecialchars($row['tahun_terbit']); ?>)</p>
                        <p><strong>Stok:</strong> <span class="stok"><?= htmlspecialchars($row['stok_tersedia']); ?></span></p>
                        </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center;">Maaf, belum ada data buku dalam katalog.</p>
        <?php endif; ?>
    </div>
</body>
</html>