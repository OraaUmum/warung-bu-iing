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
    header('Location: ' . URL_DASAR . '/halaman/keuangan.php');
    exit;
}

$jenis = $_POST['jenis_transaksi'] ?? '';
$kategori = trim($_POST['kategori'] ?? '');
$keterangan = trim($_POST['keterangan'] ?? '');
$jumlah_input = $_POST['jumlah'] ?? '';
$tanggal_input = $_POST['tanggal_transaksi'] ?? '';

$jenis_valid = ['pemasukan', 'pengeluaran'];

$kategori_valid = [
    'Penjualan lain',
    'Modal tambahan',
    'Bahan baku',
    'Gas',
    'Listrik dan air',
    'Transportasi',
    'Peralatan',
    'Operasional',
    'Lain-lain',
];

$jumlah_valid = is_string($jumlah_input)
    && preg_match('/^\d{1,12}$/', $jumlah_input) === 1
    && (float) $jumlah_input > 0;

$tanggal = is_string($tanggal_input)
    ? DateTime::createFromFormat('!Y-m-d\TH:i', $tanggal_input)
    : false;

if (
    !in_array($jenis, $jenis_valid, true) ||
    !in_array($kategori, $kategori_valid, true) ||
    !$jumlah_valid ||
    strlen($keterangan) > 2000 ||
    !$tanggal ||
    $tanggal->format('Y-m-d\TH:i') !== $tanggal_input
) {
    $_SESSION['pesan_error'] = 'Data transaksi tidak valid. Periksa kembali formulir.';
    header('Location: ' . URL_DASAR . '/halaman/keuangan.php');
    exit;
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO transaksi_keuangan (
            jenis_transaksi,
            kategori,
            keterangan,
            jumlah,
            tanggal_transaksi,
            id_pesanan
        ) VALUES (?, ?, ?, ?, ?, NULL)'
    );

    $stmt->execute([
        $jenis,
        $kategori,
        $keterangan !== '' ? $keterangan : null,
        $jumlah_input,
        $tanggal->format('Y-m-d H:i:s'),
    ]);

    $_SESSION['pesan_sukses'] = 'Transaksi keuangan berhasil disimpan.';
} catch (PDOException $e) {
    error_log('Gagal menyimpan transaksi keuangan: ' . $e->getMessage());
    $_SESSION['pesan_error'] = 'Transaksi gagal disimpan. Silakan coba lagi.';
}

header('Location: ' . URL_DASAR . '/halaman/keuangan.php');
exit;