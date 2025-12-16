<?php
include 'koneksi.php';
$total_buku = $conn->query("SELECT COUNT(*) FROM BUKU")->fetch_row()[0] ?? 0;
$total_mahasiswa = $conn->query("SELECT COUNT(*) FROM MAHASISWA")->fetch_row()[0] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Peminjaman Buku Perpustakaan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; color: #343a40; line-height: 1.6; }
        .container { width: 90%; max-width: 1200px; margin: 0 auto; padding: 20px 0; }
        .navbar { background-color: #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.05); padding: 15px 0; }
        .nav-content { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: 700; color: #007bff; text-decoration: none; }
        .nav-links a { text-decoration: none; color: #343a40; margin-left: 25px; font-weight: 600; transition: color 0.3s; }
        .nav-links a:hover { color: #007bff; }
        .hero { 
            background: linear-gradient(rgba(0, 123, 255, 0.7), rgba(0, 123, 255, 0.9)), url('img/library_bg.jpg'); /* Ganti dengan path gambar Anda */
            background-size: cover; 
            background-position: center; 
            color: white; 
            padding: 80px 0; 
            text-align: center;
        }
        .hero h1 { font-size: 3rem; margin-bottom: 15px; font-weight: 700; }
        .hero p { font-size: 1.2rem; margin-bottom: 30px; font-weight: 300; }
        .cta-button { 
            background-color: #28a745; 
            color: white; 
            padding: 12px 30px; 
            border-radius: 5px; 
            text-decoration: none; 
            font-size: 1.1rem; 
            font-weight: 600; 
            transition: background-color 0.3s;
        }
        .cta-button:hover { background-color: #218838; }
        .stats { background-color: #e9ecef; padding: 40px 0; text-align: center; }
        .stats-grid { display: flex; justify-content: space-around; }
        .stat-item { flex: 1; padding: 0 20px; }
        .stat-item h3 { font-size: 2.5rem; color: #007bff; margin-bottom: 5px; font-weight: 700; }
        .stat-item p { font-size: 1rem; color: #6c757d; }
        .fitur { padding: 60px 0; }
        .fitur h2 { text-align: center; font-size: 2rem; margin-bottom: 40px; color: #007bff; }
        .feature-grid { display: flex; flex-wrap: wrap; gap: 30px; justify-content: center; }
        .feature-card { 
            background: white; 
            padding: 25px; 
            border-radius: 8px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.08); 
            flex: 0 0 calc(33% - 20px); 
            min-width: 280px;
            text-align: center;
        }
        .feature-card i { font-size: 2rem; color: #007bff; margin-bottom: 15px; }
        .feature-card h4 { font-size: 1.2rem; margin-bottom: 10px; font-weight: 600; }
        .feature-card p { font-size: 0.9rem; color: #6c757d; }
        .footer { background-color: #343a40; color: white; padding: 20px 0; text-align: center; font-size: 0.9rem; }
        @media (max-width: 768px) {
            .nav-content { flex-direction: column; }
            .nav-links { margin-top: 15px; }
            .nav-links a { margin: 0 10px; }
            .hero h1 { font-size: 2.5rem; }
            .hero p { font-size: 1rem; }
            .stats-grid { flex-direction: column; gap: 30px; }
            .feature-grid { flex-direction: column; gap: 20px; }
            .feature-card { flex: 100%; }
        }
    </style>
</head>
<body>
    
    <header class="navbar">
        <div class="container nav-content">
            <a href="index.php" class="logo">Sistem Pinjaman Buku</a>
            <nav class="nav-links">
                <a href="#fitur">Fitur</a>
                <a href="login.php" class="cta-button" style="background-color: #007bff; color: white; padding: 8px 15px; margin-left: 20px;">Login</a>
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <h1>Akses Ribuan Koleksi Buku Digital & Fisik</h1>
            <p>Kelola peminjaman Anda, cari sumber referensi, dan tingkatkan pengetahuan Anda dengan mudah dan cepat.</p>
            <a href="login.php" class="cta-button">Mulai Sekarang & Login</a>
        </div>
    </section>

    <section class="stats">
        <div class="container stats-grid">
            <div class="stat-item">
                <h3><?=number_format($total_buku) ?></h3>
                <p>Total Koleksi Buku</p>
            </div>
            <div class="stat-item">
                <h3><?= number_format($total_mahasiswa) ?></h3>
                <p>Pengguna Terdaftar</p>
            </div>
            <div class="stat-item">
                <h3>7 Hari</h3>
                <p>Maksimal Masa Pinjam</p>
            </div>
        </div>
    </section>

    <section class="fitur" id="fitur">
        <div class="container">
            <h2>Fitur yang bisa anda akses</h2>
            <div class="feature-grid">
                
                <div class="feature-card">
                    <i style="font-size: 2.5rem;">📱</i>
                    <h4>Akses Penuh Mobile</h4>
                    <p>Cek status peminjaman dan ajukan permohonan baru dari perangkat mana pun.</p>
                </div>
                
                <div class="feature-card">
                    <i style="font-size: 2.5rem;">🔍</i>
                    <h4>Pencarian Cepat</h4>
                    <p>Temukan buku berdasarkan judul, ISBN, atau pengarang dalam hitungan detik.</p>
                </div>

                <div class="feature-card">
                    <i style="font-size: 2.5rem;">🔒</i>
                    <h4>Persetujuan Admin Cepat</h4>
                    <p>Permohonan pinjaman akan diproses oleh administrator perpustakaan dalam 1x24 jam.</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            &copy; <?= date('Y') ?> Sistem Peminjaman Buku
        </div>
    </footer>

</body>
</html>