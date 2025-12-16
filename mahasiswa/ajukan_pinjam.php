<?php
include '../koneksi.php';
include '../auth_check.php';

if ($_SESSION['role'] != 'Mahasiswa') {
    header("Location: ../admin/dashboard.php");
    exit;
}

$message = '';
$error = '';
$id_user = $_SESSION['id_user'];

$stmt_mhs = $conn->prepare("SELECT id_mahasiswa FROM MAHASISWA WHERE id_user = ?");
$stmt_mhs->bind_param("i", $id_user);
$stmt_mhs->execute();
$mahasiswa_info = $stmt_mhs->get_result()->fetch_assoc();
$id_mahasiswa = $mahasiswa_info['id_mahasiswa'] ?? 0;
$stmt_mhs->close();

if ($id_mahasiswa === 0) {
    $error = "Error: Data Mahasiswa tidak ditemukan.";
    header("Location: katalog.php?error=" . urlencode($error));
    exit;
}

if (!isset($_GET['id_buku']) || empty($_GET['id_buku'])) {
    $error = "ID Buku tidak valid.";
    header("Location: katalog.php?error=" . urlencode($error));
    exit;
}

$id_buku = (int)$_GET['id_buku'];
$buku_data = null;

$stmt_buku = $conn->prepare("SELECT judul, stok_tersedia FROM BUKU WHERE id_buku = ?");
$stmt_buku->bind_param("i", $id_buku);
$stmt_buku->execute();
$result_buku = $stmt_buku->get_result();

if ($result_buku->num_rows > 0) {
    $buku_data = $result_buku->fetch_assoc();
    if ($buku_data['stok_tersedia'] < 1) {
        $error = "Maaf, stok buku **" . htmlspecialchars($buku_data['judul']) . "** sudah habis.";
        header("Location: katalog.php?error=" . urlencode($error));
        exit;
    }
} else {
    $error = "Buku tidak ditemukan.";
    header("Location: katalog.php?error=" . urlencode($error));
    exit;
}
$stmt_buku->close();

$tanggal_pinjam = date('Y-m-d');
$tanggal_kembali_harus = date('Y-m-d', strtotime($tanggal_pinjam . ' + 7 days'));
$status_pinjam = 'Pending';

$conn->begin_transaction();

try {
    $check_active = $conn->prepare("
        SELECT COUNT(*) 
        FROM PEMINJAMAN p
        JOIN DETAIL_PEMINJAMAN dp ON p.id_peminjaman = dp.id_peminjaman
        WHERE p.id_mahasiswa = ? AND dp.id_buku = ? AND p.status_pinjam IN ('Dipinjam', 'Pending')
    ");
    $check_active->bind_param("ii", $id_mahasiswa, $id_buku);
    $check_active->execute();
    $active_count = $check_active->get_result()->fetch_row()[0];
    $check_active->close();

    if ($active_count > 0) {
        throw new Exception("Anda sudah memiliki buku **" . htmlspecialchars($buku_data['judul']) . "** dengan status aktif (Dipinjam atau Menunggu Persetujuan).");
    }

    $stmt_pinjam = $conn->prepare("
        INSERT INTO PEMINJAMAN (id_mahasiswa, tanggal_pinjam, tanggal_kembali_harus, status_pinjam)
        VALUES (?, ?, ?, ?) 
    ");
    
    $stmt_pinjam->bind_param("isss", 
        $id_mahasiswa, 
        $tanggal_pinjam, 
        $tanggal_kembali_harus, 
        $status_pinjam
    );
    $stmt_pinjam->execute();
    
    $id_peminjaman_baru = $conn->insert_id;
    $stmt_pinjam->close();

    $stmt_detail = $conn->prepare("
        INSERT INTO DETAIL_PEMINJAMAN (id_peminjaman, id_buku)
        VALUES (?, ?)
    ");
    $stmt_detail->bind_param("ii", $id_peminjaman_baru, $id_buku);
    $stmt_detail->execute();
    $stmt_detail->close();

    $stmt_update_stok = $conn->prepare("
        UPDATE BUKU SET stok_tersedia = stok_tersedia - 1 WHERE id_buku = ?
    ");
    $stmt_update_stok->bind_param("i", $id_buku);
    $stmt_update_stok->execute();
    $stmt_update_stok->close();

    $conn->commit();
    $message = "Pengajuan peminjaman buku **" . htmlspecialchars($buku_data['judul']) . "** berhasil! Menunggu persetujuan Admin.";


} catch (Exception $e) {
    $conn->rollback();
    $error = "Gagal mengajukan pinjaman: " . $e->getMessage();
}

if ($error) {
    $redirect_url = "katalog.php?error=" . urlencode($error);
} else {
    $redirect_url = "dashboard.php?success=" . urlencode($message);
}

header("Location: " . $redirect_url);
exit;

?>