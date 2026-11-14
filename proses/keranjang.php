<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/aplikasi.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . URL_DASAR . '/katalog.php');
    exit;
}

$id_menu = filter_input(INPUT_POST, 'id_menu', FILTER_VALIDATE_INT);
$jumlah = filter_input(INPUT_POST, 'jumlah', FILTER_VALIDATE_INT);

if (!$id_menu || $id_menu < 1 || !$jumlah || $jumlah < 1) {
    $_SESSION['pesan_keranjang'] = 'Menu atau jumlah tidak valid.';
    header('Location: ' . URL_DASAR . '/katalog.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT id_menu, nama_menu, stok
     FROM menu
     WHERE id_menu = ? AND tersedia = 1'
);
$stmt->execute([$id_menu]);
$menu = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$menu || (int) $menu['stok'] < 1) {
    $_SESSION['pesan_keranjang'] = 'Menu sudah tidak tersedia.';
    header('Location: ' . URL_DASAR . '/katalog.php');
    exit;
}

$jumlah_lama = (int) ($_SESSION['keranjang'][$id_menu] ?? 0);
$jumlah_baru = $jumlah_lama + $jumlah;

if ($jumlah_baru > (int) $menu['stok']) {
    $_SESSION['pesan_keranjang'] =
        'Jumlah ' . $menu['nama_menu'] . ' melebihi stok yang tersedia.';
    header('Location: ' . URL_DASAR . '/katalog.php');
    exit;
}

$_SESSION['keranjang'][$id_menu] = $jumlah_baru;
$_SESSION['pesan_keranjang'] = 'Menu berhasil ditambahkan ke keranjang.';

header('Location: ' . URL_DASAR . '/keranjang.php');
exit;
