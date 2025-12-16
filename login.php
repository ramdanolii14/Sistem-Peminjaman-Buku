<?php
session_start();
ob_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'koneksi.php';

if (isset($_POST['submit'])) {
    $username_email = trim($_POST['username_email']);
    $password_input = $_POST['password'];
    $password_md5 = md5($password_input);

    try {
        $stmt = $conn->prepare("
            SELECT u.id_user, r.nama_role 
            FROM USER u 
            JOIN USER_ROLE ur ON u.id_user = ur.id_user 
            JOIN ROLE r ON ur.id_role = r.id_role
            WHERE (u.username = ? OR u.email = ?) AND u.password = ?
        ");
        $stmt->bind_param("sss", $username_email, $username_email, $password_md5);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
            $_SESSION['id_user'] = $user_data['id_user'];
            $_SESSION['role'] = $user_data['nama_role'];

            ob_end_clean();
            
            if ($user_data['nama_role'] == 'Admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: mahasiswa/dashboard.php");
            }
            exit;
        } else {
            echo "<script>alert('Username/Email atau Kata Sandi salah!');</script>";
        }
        $stmt->close();

    } catch (Exception $e) {
        echo "<script>alert('Login Gagal (Fatal Error): " . htmlspecialchars($e->getMessage()) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login Page - Perpustakaan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", sans-serif; }
        body { height: 100vh; display: flex; background: #f4f7fb; }
        .login-side { flex: 1; display: flex; justify-content: center; align-items: center; }
        .login-box { width: 350px; padding: 40px; border-radius: 16px; background: #fff; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .login-box h2 { text-align: center; margin-bottom: 30px; color: #2563eb; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; }
        input { width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid #ccc; }
        button { width: 100%; padding: 12px; background: #2563eb; border: none; border-radius: 8px; color: white; font-size: 1rem; font-weight: 600; cursor: pointer; }
        button:hover { background: #1d4ed8; }
        .bottom-text { text-align: center; margin-top: 15px; font-size: 0.9rem; }
        .bottom-text a { color: #2563eb; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="login-side">
        <div class="login-box">
            <h2>Masuk ke Akun Anda</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username_email">Username atau Email</label>
                    <input type="text" name="username_email" placeholder="Username atau Email" required>
                </div>
                <div class="form-group">
                    <label for="password">Kata Sandi</label>
                    <input type="password" name="password" placeholder="Masukkan kata sandi" required>
                </div>
                <button type="submit" name="submit">Login</button>
                <div class="bottom-text">
                    Belum punya akun? <a href="register.php">Daftar sekarang</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>