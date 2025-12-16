<?php
include '../koneksi.php';
include '../auth_check.php';

// Pastikan hanya Admin atau Kepala Perpustakaan yang bisa mengakses
if ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Kepala Perpustakaan') {
    header("Location: ../login.php");
    exit;
}

$id_peminjaman = (int)$_GET['id'] ?? 0;
$action = $_GET['action'] ?? '';

if ($id_peminjaman == 0 || !in_array($action, ['setujui', 'tolak', 'kembali'])) {
    $error = "Aksi atau ID Peminjaman tidak valid.";
    header("Location: daftar_peminjaman.php?error=" . urlencode($error));
    exit;
}

$conn->begin_transaction();

try {
    $message = '';

    // 1. Ambil ID Buku yang terkait
    $stmt_detail = $conn->prepare("SELECT id_buku FROM DETAIL_PEMINJAMAN WHERE id_peminjaman = ?");
    $stmt_detail->bind_param("i", $id_peminjaman);
    $stmt_detail->execute();
    $id_buku = $stmt_detail->get_result()->fetch_assoc()['id_buku'] ?? 0;
    $stmt_detail->close();

    if ($id_buku === 0) {
        throw new Exception("Detail buku untuk peminjaman ini tidak ditemukan.");
    }

    if ($action == 'setujui') {
        // 2a. Setujui Peminjaman (Ubah Status ke 'Dipinjam')
        $stmt_update = $conn->prepare("UPDATE PEMINJAMAN SET status_pinjam = 'Dipinjam' WHERE id_peminjaman = ? AND status_pinjam = 'Pending'");
        $stmt_update->bind_param("i", $id_peminjaman);
        $stmt_update->execute();
        
        if ($conn->affected_rows === 0) {
             throw new Exception("Peminjaman tidak dapat disetujui. Mungkin sudah diproses sebelumnya.");
        }
        $stmt_update->close();
        $message = "Peminjaman berhasil disetujui. Buku siap diambil.";

    } elseif ($action == 'tolak') {
        // 2b. Tolak Peminjaman (Ubah Status ke 'Ditolak' & Kembalikan Stok)
        $stmt_update = $conn->prepare("UPDATE PEMINJAMAN SET status_pinjam = 'Ditolak' WHERE id_peminjaman = ? AND status_pinjam = 'Pending'");
        $stmt_update->bind_param("i", $id_peminjaman);
        $stmt_update->execute();
        $stmt_update->close();

        // Kembalikan Stok Buku
        $stmt_stok = $conn->prepare("UPDATE BUKU SET stok_tersedia = stok_tersedia + 1 WHERE id_buku = ?");
        $stmt_stok->bind_param("i", $id_buku);
        $stmt_stok->execute();
        $stmt_stok->close();
        
        $message = "Peminjaman berhasil ditolak. Stok buku dikembalikan.";

    } elseif ($action == 'kembali') {
        // 2c. Tandai Pengembalian (Ubah Status ke 'Dikembalikan' & Catat Tgl Kembali & Kembalikan Stok)
        $tanggal_kembali_aktual = date('Y-m-d');
        
        $stmt_update = $conn->prepare("UPDATE PEMINJAMAN SET status_pinjam = 'Dikembalikan', tanggal_kembali_aktual = ? WHERE id_peminjaman = ? AND status_pinjam = 'Dipinjam'");
        $stmt_update->bind_param("si", $tanggal_kembali_aktual, $id_peminjaman);
        $stmt_update->execute();

        if ($conn->affected_rows === 0) {
             throw new Exception("Buku tidak dapat ditandai kembali. Status tidak 'Dipinjam' atau sudah dikembalikan.");
        }
        $stmt_update->close();

        // Kembalikan Stok Buku
        $stmt_stok = $conn->prepare("UPDATE BUKU SET stok_tersedia = stok_tersedia + 1 WHERE id_buku = ?");
        $stmt_stok->bind_param("i", $id_buku);
        $stmt_stok->execute();
        $stmt_stok->close();
        
        $message = "Buku berhasil dikembalikan. Stok bertambah.";
    }
    
    // Commit Transaction jika semua langkah berhasil
    $conn->commit();
    header("Location: daftar_peminjaman.php?message=" . urlencode($message));
    exit;

} catch (Exception $e) {
    // Rollback Transaction jika ada kegagalan
    $conn->rollback();
    $error = "Gagal memproses aksi: " . $e->getMessage();
    header("Location: daftar_peminjaman.php?error=" . urlencode($error));
    exit;
}
?>