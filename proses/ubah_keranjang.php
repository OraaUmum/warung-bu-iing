<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/aplikasi.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('keranjang.php'));
    exit;
}

$aksi = $_POST['aksi'] ?? '';
$idMenu = filter_var($_POST['id_menu'] ?? null, FILTER_VALIDATE_INT);

if (!$idMenu || $idMenu < 1) {
    $_SESSION['pesan_keranjang'] = 'Menu tidak valid.';
    header('Location: ' . url('keranjang.php'));
    exit;
}

if (!isset($_SESSION['keranjang']) || !is_array($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

if ($aksi === 'hapus') {
    unset($_SESSION['keranjang'][$idMenu]);

    $_SESSION['pesan_keranjang'] = 'Menu berhasil dihapus dari keranjang.';

    header('Location: ' . url('keranjang.php'));
    exit;
}

if ($aksi === 'ubah') {
    $jumlah = filter_var($_POST['jumlah'] ?? null, FILTER_VALIDATE_INT);

    if ($jumlah === false || $jumlah === null || $jumlah < 1) {
        $_SESSION['pesan_keranjang'] = 'Jumlah pesanan harus minimal 1.';
        header('Location: ' . url('keranjang.php'));
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT nama_menu, stok
        FROM menu
        WHERE id_menu = ? AND tersedia = 1
        LIMIT 1
    ");

    $stmt->execute([$idMenu]);
    $menu = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$menu) {
        unset($_SESSION['keranjang'][$idMenu]);

        $_SESSION['pesan_keranjang'] = 'Menu sudah tidak tersedia dan dihapus dari keranjang.';
        header('Location: ' . url('keranjang.php'));
        exit;
    }

    if ($jumlah > (int) $menu['stok']) {
        $_SESSION['pesan_keranjang'] =
            'Jumlah ' . $menu['nama_menu'] .
            ' melebihi stok yang tersedia. Stok saat ini: ' .
            (int) $menu['stok'] . '.';

        header('Location: ' . url('keranjang.php'));
        exit;
    }

    $_SESSION['keranjang'][$idMenu] = $jumlah;

    $_SESSION['pesan_keranjang'] = 'Jumlah pesanan berhasil diperbarui.';

    header('Location: ' . url('keranjang.php'));
    exit;
}

$_SESSION['pesan_keranjang'] = 'Aksi tidak dikenali.';
header('Location: ' . url('keranjang.php'));
exit;
