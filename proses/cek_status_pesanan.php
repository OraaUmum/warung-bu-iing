<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/aplikasi.php';
require_once __DIR__ . '/../config/database.php';

function kembaliCekStatus(string $pesan): never
{
    $_SESSION['pesan_cek_status'] = $pesan;
    unset($_SESSION['hasil_cek_status']);

    header('Location: ' . URL_DASAR . '/status_pesanan.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . URL_DASAR . '/status_pesanan.php');
    exit;
}

$telepon = trim($_POST['no_telepon'] ?? '');

if ($telepon === '' || mb_strlen($telepon) > 30) {
    kembaliCekStatus('Masukkan nomor WhatsApp yang valid.');
}

$normalisasiTelepon = static function (string $nomor): string {
    return preg_replace('/\D+/', '', $nomor) ?? '';
};

$teleponNormal = $normalisasiTelepon($telepon);

if (strlen($teleponNormal) < 8 || strlen($teleponNormal) > 15) {
    kembaliCekStatus('Periksa kembali nomor WhatsApp yang kamu masukkan.');
}

try {
    // Ambil pesanan berdasarkan nomor telepon.
    $stmt = $pdo->query(
        'SELECT
            id_pesanan,
            kode_pesanan,
            nama_penerima,
            no_telepon,
            jenis_pesanan,
            status_pesanan,
            subtotal,
            biaya_pengiriman,
            total_harga,
            dibuat_pada
         FROM pesanan
         ORDER BY id_pesanan DESC'
    );

    $semuaPesanan = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $hasil = [];

    foreach ($semuaPesanan as $pesanan) {
        if (
            $normalisasiTelepon((string) $pesanan['no_telepon'])
            === $teleponNormal
        ) {
            $hasil[] = $pesanan;
        }
    }

    if (!$hasil) {
        kembaliCekStatus(
            'Belum ada pesanan yang cocok dengan nomor WhatsApp tersebut.'
        );
    }

    // Ambil detail menu untuk seluruh pesanan yang cocok.
    $ids = array_map(
        static fn(array $pesanan): int => (int) $pesanan['id_pesanan'],
        $hasil
    );

    $tanda = implode(',', array_fill(0, count($ids), '?'));

    $stmtDetail = $pdo->prepare(
        "SELECT id_pesanan, nama_menu, jumlah, subtotal
         FROM detail_pesanan
         WHERE id_pesanan IN ($tanda)
         ORDER BY id_detail"
    );
    $stmtDetail->execute($ids);

    $detailPerPesanan = [];

    foreach ($stmtDetail->fetchAll(PDO::FETCH_ASSOC) as $detail) {
        $idPesanan = (int) $detail['id_pesanan'];
        $detailPerPesanan[$idPesanan][] = $detail;
    }

    foreach ($hasil as &$pesanan) {
        $idPesanan = (int) $pesanan['id_pesanan'];
        $pesanan['detail'] = $detailPerPesanan[$idPesanan] ?? [];
    }
    unset($pesanan);

    // Simpan hasil pencarian di session untuk ditampilkan.
    $_SESSION['hasil_cek_status'] = $hasil;
    unset($_SESSION['pesan_cek_status']);

    header('Location: ' . URL_DASAR . '/status_pesanan.php');
    exit;

} catch (Throwable $e) {
    error_log('Gagal mengecek status pesanan: ' . $e->getMessage());

    kembaliCekStatus(
        'Terjadi kesalahan saat memeriksa pesanan. Silakan coba kembali.'
    );
}
