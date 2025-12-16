<?php
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'koneksi.php';

if (isset($_POST['submit'])) {
    $username    = trim($_POST['username']);
    $email       = trim($_POST['email']);
    $password_raw= $_POST['password'];
    $namalengkap = trim($_POST['namalengkap']);
    $nim         = trim($_POST['nim']);
    $fakultas    = trim($_POST['fakultas']);
    $jurusan     = trim($_POST['jurusan']); 
    $angkatan    = trim($_POST['angkatan']); 

    $password_hash = md5($password_raw); 
    $berhasil = false;
    $conn->begin_transaction();

    try {
        $stmt_role = $conn->prepare("SELECT id_role FROM ROLE WHERE nama_role = 'Mahasiswa'");
        $stmt_role->execute();
        $result_role = $stmt_role->get_result();

        if ($result_role->num_rows == 0) {
            throw new Exception("Role 'Mahasiswa' tidak ditemukan di database. Hubungi Admin.");
        }
        $id_role_mhs = $result_role->fetch_assoc()['id_role'];
        $stmt_role->close();

        $stmt_user = $conn->prepare("INSERT INTO USER (username, email, password) VALUES (?, ?, ?)");
        $stmt_user->bind_param("sss", $username, $email, $password_hash);
        
        if (!$stmt_user->execute()) {
             throw new Exception("Gagal mendaftar User! Cek username/email: " . $stmt_user->error);
        }
        $id_user = $stmt_user->insert_id;
        $stmt_user->close();

        $stmt_mhs = $conn->prepare("INSERT INTO MAHASISWA (nim, id_user, nama_lengkap, fakultas, jurusan, angkatan) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_mhs->bind_param("sisssi", $nim, $id_user, $namalengkap, $fakultas, $jurusan, $angkatan);

        if (!$stmt_mhs->execute()) {
             throw new Exception("Gagal mendaftar Mahasiswa! Cek NIM: " . $stmt_mhs->error);
        }
        $stmt_mhs->close();

        $stmt_user_role = $conn->prepare("INSERT INTO USER_ROLE (id_user, id_role) VALUES (?, ?)");
        $stmt_user_role->bind_param("ii", $id_user, $id_role_mhs);

        if (!$stmt_user_role->execute()) {
             throw new Exception("Gagal menetapkan peran User!");
        }
        $stmt_user_role->close();
        
        $conn->commit();
        $berhasil = true;

    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Gagal mendaftar: " . htmlspecialchars($e->getMessage()) . "'); history.back();</script>";
        exit;
    }

    if ($berhasil) {
        echo "<script>alert('Registrasi berhasil! Silakan login.'); window.location='login.php';</script>";
        ob_end_flush();
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register Page - Perpustakaan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", sans-serif; }
        body { height: 100vh; display: flex; background: #f4f7fb; }
        .container { display: flex; width: 100%; height: 100vh; }
        .login-side { margin-left: 0; flex: 1; background: #ffffff; height: 100vh; overflow-y: auto; display: flex; justify-content: center; align-items: flex-start; padding: 40px; }
        .login-box { width: 400px; background: #fff; padding: 40px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .login-box h2 { text-align: center; margin-bottom: 30px; color: rgba(255, 0, 0, 1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; }
        input, select { width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid #ccc; }
        button { width: 100%; padding: 12px; background: rgba(255, 0, 0, 1); border: none; border-radius: 8px; color: white; font-size: 1rem; font-weight: 600; cursor: pointer; }
        button:hover { background: hsla(0, 100%, 37%, 1.00); }
        .bottom-text { text-align: center; margin-top: 15px; font-size: 0.9rem; }
        .bottom-text a { color: #2563eb; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-side">
            <div class="login-box">
                <h2>Daftar Akun Mahasiswa</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" name="username" placeholder="Username" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" placeholder="Email Aktif" required>
                    </div>
                    <div class="form-group">
                        <label for="namalengkap">Nama Lengkap</label>
                        <input type="text" name="namalengkap" placeholder="Nama Lengkap" required>
                    </div>
                    <div class="form-group">
                        <label for="nim">NIM</label>
                        <input type="text" name="nim" placeholder="Nomor Induk Mahasiswa" required>
                    </div>
                    <div class="form-group">
                        <label for="fakultas">Fakultas</label>
                        <select name="fakultas" required>
                            <option value="" disabled selected>Pilih Fakultas</option>
                            <option value="Teknik">Fakultas Teknik</option>
                            <option value="MIPA">Fakultas MIPA</option>
                            <option value="Pertanian">Fakultas Pertanian</option>
                            <option value="Sastra">Fakultas Sastra</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="jurusan">Jurusan</label>
                        <input type="text" name="jurusan" placeholder="Jurusan Anda" required>
                    </div>
                    <div class="form-group">
                        <label for="angkatan">Tahun Angkatan</label>
                        <input type="number" name="angkatan" placeholder="Contoh: 2022" required min="1900" max="<?= date('Y') ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Kata Sandi</label>
                        <input type="password" name="password" placeholder="Masukkan kata sandi" required>
                    </div>
                    <button type="submit" name="submit">Daftar</button>
                    <div class="bottom-text">
                        Sudah punya akun? <a href="login.php">Login sekarang</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>