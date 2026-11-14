<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/aplikasi.php';
require_once __DIR__ . '/../config/database.php';

function gagalPesanan(string $pesan): never
{
    $_SESSION['pesan_checkout'] = $pesan;
    header('Location: ' . URL_DASAR . '/checkout.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . URL_DASAR . '/katalog.php');
    exit;
}

$keranjang = $_SESSION['keranjang'] ?? [];

$nama = trim($_POST['nama_penerima'] ?? '');
$telepon = trim($_POST['no_telepon'] ?? '');
$jenis = $_POST['jenis_pesanan'] ?? '';
$alamat = trim($_POST['alamat_pengiriman'] ?? '');
$catatan = trim($_POST['catatan'] ?? '');

if (
    !$keranjang ||
    $nama === '' ||
    $telepon === '' ||
    !in_array($jenis, ['ambil_sendiri', 'diantar'], true)
) {
    gagalPesanan('Data pesanan belum lengkap atau tidak valid.');
}

if (mb_strlen($nama) > 100 || mb_strlen($telepon) > 20) {
    gagalPesanan('Nama atau nomor telepon terlalu panjang.');
}

if ($jenis === 'diantar' && $alamat === '') {
    gagalPesanan('Alamat wajib diisi untuk pesanan yang diantar.');
}

if (mb_strlen($alamat) > 10000 || mb_strlen($catatan) > 10000) {
    gagalPesanan('Alamat atau catatan terlalu panjang.');
}

try {
    $pdo->beginTransaction();

    $ids = array_map('intval', array_keys($keranjang));

    if (!$ids || in_array(0, $ids, true) || min($ids) < 1) {
        throw new RuntimeException('Ada menu tidak valid di keranjang.');
    }

    // Kunci baris menu agar pesanan bersamaan tidak memakai stok yang sama.
    sort($ids);
    $tanda = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare(
        "SELECT id_menu, nama_menu, harga, stok
         FROM menu
         WHERE id_menu IN ($tanda) AND tersedia = 1
         ORDER BY id_menu
         FOR UPDATE"
    );
    $stmt->execute($ids);
    $menu = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($menu) !== count($ids)) {
        throw new RuntimeException(
            'Sebagian menu sudah tidak tersedia. Silakan periksa keranjang.'
        );
    }

    $subtotal = 0;
    $detail = [];

    foreach ($menu as $item) {
        $idMenu = (int) $item['id_menu'];

        $jumlah = filter_var(
            $keranjang[$idMenu] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($jumlah === false || $jumlah === null || $jumlah < 1) {
            throw new RuntimeException('Jumlah menu tidak valid.');
        }

        if ($jumlah > (int) $item['stok']) {
            throw new RuntimeException(
                'Stok ' . $item['nama_menu'] . ' tidak mencukupi. '
                . 'Stok tersedia: ' . (int) $item['stok'] . '.'
            );
        }

        $harga = (float) $item['harga'];
        $nilai = $harga * $jumlah;
        $subtotal += $nilai;

        $detail[] = [
            'id_menu' => $idMenu,
            'nama_menu' => $item['nama_menu'],
            'harga' => $harga,
            'jumlah' => $jumlah,
            'subtotal' => $nilai,
        ];
    }

    // Ambil biaya pengiriman.
    $stmt = $pdo->query(
        'SELECT biaya_pengiriman
         FROM pengaturan_warung
         ORDER BY id_pengaturan DESC
         LIMIT 1'
    );
    $pengaturan = $stmt->fetch(PDO::FETCH_ASSOC);

    $biaya = $jenis === 'diantar'
        ? (float) ($pengaturan['biaya_pengiriman'] ?? 0)
        : 0;

    $total = $subtotal + $biaya;
    $kode = 'WB-' . date('YmdHis') . '-'
        . strtoupper(bin2hex(random_bytes(3)));

    // Buat pesanan.
    $stmt = $pdo->prepare(
        "INSERT INTO pesanan (
            kode_pesanan, nama_penerima, no_telepon,
            alamat_pengiriman, jenis_pesanan, status_pesanan,
            catatan, subtotal, biaya_pengiriman, total_harga
        ) VALUES (?, ?, ?, ?, ?, 'menunggu_konfirmasi', ?, ?, ?, ?)"
    );

    $stmt->execute([
        $kode,
        $nama,
        $telepon,
        $jenis === 'diantar' ? $alamat : null,
        $jenis,
        $catatan !== '' ? $catatan : null,
        $subtotal,
        $biaya,
        $total,
    ]);

    $idPesanan = (int) $pdo->lastInsertId();

    // Simpan rincian pesanan.
    $stmtDetail = $pdo->prepare(
        "INSERT INTO detail_pesanan (
            id_pesanan, id_menu, nama_menu,
            harga_satuan, jumlah, subtotal
        ) VALUES (?, ?, ?, ?, ?, ?)"
    );

    foreach ($detail as $item) {
        $stmtDetail->execute([
            $idPesanan,
            $item['id_menu'],
            $item['nama_menu'],
            $item['harga'],
            $item['jumlah'],
            $item['subtotal'],
        ]);
    }

    // Kurangi stok setiap menu. Kondisi stok >= jumlah menjadi pengaman tambahan.
    $stmtStok = $pdo->prepare(
        'UPDATE menu
         SET stok = stok - ?
         WHERE id_menu = ?
           AND tersedia = 1
           AND stok >= ?'
    );

    foreach ($detail as $item) {
        $stmtStok->execute([
            $item['jumlah'],
            $item['id_menu'],
            $item['jumlah'],
        ]);

        if ($stmtStok->rowCount() !== 1) {
            throw new RuntimeException(
                'Stok ' . $item['nama_menu']
                . ' berubah atau tidak mencukupi. Silakan pesan kembali.'
            );
        }
    }

    // Buat catatan pembayaran awal.
    $stmtPembayaran = $pdo->prepare(
        "INSERT INTO pembayaran (
            id_pesanan, metode, jumlah_bayar, status_pembayaran
        ) VALUES (?, 'tunai', ?, 'belum_bayar')"
    );

    $stmtPembayaran->execute([$idPesanan, $total]);

    // Pesanan, detail, stok, dan pembayaran berhasil disimpan bersama.
    $pdo->commit();

    unset($_SESSION['keranjang']);
    $_SESSION['kode_pesanan_terakhir'] = $kode;

    header('Location: ' . URL_DASAR . '/pesanan_berhasil.php');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($e instanceof RuntimeException) {
        $_SESSION['pesan_checkout'] = $e->getMessage();
    } else {
        error_log('Gagal membuat pesanan: ' . $e->getMessage());
        $_SESSION['pesan_checkout'] =
            'Pesanan gagal disimpan. Silakan coba kembali.';
    }

    header('Location: ' . URL_DASAR . '/checkout.php');
    exit;
}
