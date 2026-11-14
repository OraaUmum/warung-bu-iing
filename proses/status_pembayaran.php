<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/aplikasi.php';
require_once __DIR__ . '/../config/database.php';

if (
    !isset($_SESSION['pengguna_id']) ||
    ($_SESSION['pengguna_peran'] ?? '') !== 'admin'
) {
    header('Location: ' . URL_DASAR . '/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . URL_DASAR . '/halaman/pembayaran.php');
    exit;
}

$id_pembayaran = filter_input(
    INPUT_POST,
    'id_pembayaran',
    FILTER_VALIDATE_INT
);

$metode = $_POST['metode'] ?? '';
$status = $_POST['status_pembayaran'] ?? '';

$metode_valid = ['tunai', 'transfer', 'qris'];

$status_valid = [
    'belum_bayar',
    'menunggu_verifikasi',
    'lunas',
    'ditolak',
    'dikembalikan',
];

if (
    !$id_pembayaran ||
    $id_pembayaran < 1 ||
    !in_array($metode, $metode_valid, true) ||
    !in_array($status, $status_valid, true)
) {
    $_SESSION['pesan_error'] = 'Data pembayaran tidak valid.';
    header('Location: ' . URL_DASAR . '/halaman/pembayaran.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT
            p.id_pembayaran,
            p.status_pembayaran,
            p.waktu_bayar,
            p.waktu_pengembalian,
            ps.status_pesanan
         FROM pembayaran p
         INNER JOIN pesanan ps
            ON ps.id_pesanan = p.id_pesanan
         WHERE p.id_pembayaran = ?
         FOR UPDATE'
    );

    $stmt->execute([$id_pembayaran]);
    $pembayaran = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pembayaran) {
        throw new RuntimeException(
            'Data pembayaran atau pesanan tidak ditemukan.'
        );
    }

    $status_lama = $pembayaran['status_pembayaran'];
    $status_pesanan = $pembayaran['status_pesanan'];

    if (
        $status === 'lunas' &&
        $status_pesanan === 'dibatalkan'
    ) {
        throw new RuntimeException(
            'Pesanan sudah dibatalkan. Pembayaran tidak dapat ditandai lunas.'
        );
    }

    if (
        $status_lama === 'lunas' &&
        !in_array($status, ['lunas', 'dikembalikan'], true)
    ) {
        throw new RuntimeException(
            'Pembayaran sudah lunas. Gunakan status dikembalikan jika dana sudah dikembalikan.'
        );
    }

    if (
        $status === 'dikembalikan' &&
        !in_array($status_lama, ['lunas', 'dikembalikan'], true)
    ) {
        throw new RuntimeException(
            'Pengembalian dana hanya dapat dicatat untuk pembayaran yang sudah lunas.'
        );
    }

    // Pembayaran lunas: simpan waktu pembayaran.
    if ($status === 'lunas') {
        $waktu_bayar = $pembayaran['waktu_bayar']
            ?? date('Y-m-d H:i:s');

        $stmt = $pdo->prepare(
            'UPDATE pembayaran
             SET metode = ?,
                 status_pembayaran = ?,
                 waktu_bayar = ?,
                 waktu_pengembalian = NULL
             WHERE id_pembayaran = ?'
        );

        $stmt->execute([
            $metode,
            $status,
            $waktu_bayar,
            $id_pembayaran,
        ]);
    } elseif ($status === 'dikembalikan') {
        // Catat waktu refund dan pertahankan waktu pembayaran asli.
        $waktu_pengembalian = $pembayaran['waktu_pengembalian']
            ?? date('Y-m-d H:i:s');

        $stmt = $pdo->prepare(
            'UPDATE pembayaran
             SET metode = ?,
                 status_pembayaran = ?,
                 waktu_pengembalian = ?
             WHERE id_pembayaran = ?'
        );

        $stmt->execute([
            $metode,
            $status,
            $waktu_pengembalian,
            $id_pembayaran,
        ]);
    } else {
        $stmt = $pdo->prepare(
            'UPDATE pembayaran
             SET metode = ?,
                 status_pembayaran = ?,
                 waktu_bayar = NULL,
                 waktu_pengembalian = NULL
             WHERE id_pembayaran = ?'
        );

        $stmt->execute([
            $metode,
            $status,
            $id_pembayaran,
        ]);
    }

    $pdo->commit();

    $_SESSION['pesan_sukses'] = 'Pembayaran berhasil diperbarui.';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Gagal memperbarui pembayaran: ' . $e->getMessage()
    );

    $_SESSION['pesan_error'] = $e instanceof RuntimeException
        ? $e->getMessage()
        : 'Pembayaran gagal diperbarui. Silakan coba lagi.';
}

header('Location: ' . URL_DASAR . '/halaman/pembayaran.php');
exit;