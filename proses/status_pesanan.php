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
    header('Location: ' . URL_DASAR . '/halaman/pesanan.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id_pesanan', FILTER_VALIDATE_INT);
$statusBaru = $_POST['status_pesanan'] ?? '';

if (!$id || $id < 1) {
    $_SESSION['pesan_status'] = 'ID pesanan tidak valid.';
    header('Location: ' . URL_DASAR . '/halaman/pesanan.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Kunci pesanan agar tidak diproses bersamaan.
    $stmt = $pdo->prepare(
        'SELECT status_pesanan, jenis_pesanan
         FROM pesanan
         WHERE id_pesanan = ?
         FOR UPDATE'
    );
    $stmt->execute([$id]);
    $pesanan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pesanan) {
        throw new RuntimeException('Pesanan tidak ditemukan.');
    }

    $statusLama = $pesanan['status_pesanan'];

    // Aturan transisi status.
    $transisi = [
        'menunggu_konfirmasi' => ['dikonfirmasi', 'dibatalkan'],
        'dikonfirmasi' => ['diproses', 'dibatalkan'],
        'diproses' => (
            $pesanan['jenis_pesanan'] === 'diantar'
                ? ['dikirim']
                : ['siap_diambil']
        ),
        'siap_diambil' => ['selesai'],
        'dikirim' => ['selesai'],
        'selesai' => [],
        'dibatalkan' => [],
    ];

    if (!in_array($statusBaru, $transisi[$statusLama] ?? [], true)) {
        throw new RuntimeException(
            'Perubahan status tidak valid untuk pesanan ini.'
        );
    }

    // Pesanan hanya boleh selesai jika pembayaran sudah lunas.
    if ($statusBaru === 'selesai') {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM pembayaran
             WHERE id_pesanan = ?
               AND status_pembayaran = 'lunas'"
        );
        $stmt->execute([$id]);

        if ((int) $stmt->fetchColumn() < 1) {
            throw new RuntimeException(
                'Pesanan belum dapat diselesaikan karena pembayaran belum lunas.'
            );
        }
    }

    // Pesanan lunas tidak boleh langsung dibatalkan.
    if ($statusBaru === 'dibatalkan') {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM pembayaran
             WHERE id_pesanan = ?
               AND status_pembayaran = 'lunas'"
        );
        $stmt->execute([$id]);

        if ((int) $stmt->fetchColumn() > 0) {
            throw new RuntimeException(
                'Pesanan sudah lunas. Selesaikan penanganan pengembalian dana sebelum membatalkan pesanan.'
            );
        }

        // Ambil detail yang benar-benar tersimpan pada pesanan.
        $stmt = $pdo->prepare(
            'SELECT id_menu, jumlah
             FROM detail_pesanan
             WHERE id_pesanan = ?
             ORDER BY id_detail
             FOR UPDATE'
        );
        $stmt->execute([$id]);
        $detailPesanan = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$detailPesanan) {
            throw new RuntimeException(
                'Detail pesanan tidak ditemukan. Pembatalan dibatalkan agar stok tidak salah.'
            );
        }

        // Kembalikan stok setiap item.
        $stmtStok = $pdo->prepare(
            'UPDATE menu
             SET stok = stok + ?
             WHERE id_menu = ?'
        );

        foreach ($detailPesanan as $item) {
            $idMenu = (int) $item['id_menu'];
            $jumlah = (int) $item['jumlah'];

            if ($idMenu < 1 || $jumlah < 1) {
                throw new RuntimeException(
                    'Detail stok pesanan tidak valid.'
                );
            }

            $stmtStok->execute([$jumlah, $idMenu]);

            if ($stmtStok->rowCount() !== 1) {
                throw new RuntimeException(
                    'Menu pada detail pesanan tidak ditemukan. '
                    . 'Pembatalan dibatalkan agar stok tidak salah.'
                );
            }
        }
    }

    // Perbarui status setelah seluruh pemeriksaan selesai.
    $stmt = $pdo->prepare(
        'UPDATE pesanan
         SET status_pesanan = ?
         WHERE id_pesanan = ?'
    );
    $stmt->execute([$statusBaru, $id]);

    $pdo->commit();

    $_SESSION['pesan_status'] = $statusBaru === 'dibatalkan'
        ? 'Pesanan dibatalkan dan stok menu berhasil dikembalikan.'
        : 'Status pesanan berhasil diperbarui.';

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Gagal memperbarui status pesanan: ' . $e->getMessage());

    $_SESSION['pesan_status'] = $e instanceof RuntimeException
        ? $e->getMessage()
        : 'Terjadi kesalahan saat memperbarui status pesanan.';
}

header(
    'Location: ' . URL_DASAR
    . '/halaman/detail_pesanan.php?id=' . $id
);
exit;
