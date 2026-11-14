<?php
declare(strict_types=1);

require_once __DIR__ . '/config/aplikasi.php';
require_once __DIR__ . '/config/database.php';

$keranjang = $_SESSION['keranjang'] ?? [];

if (!$keranjang) {
    header('Location: ' . URL_DASAR . '/keranjang.php');
    exit;
}

$ids = array_map('intval', array_keys($keranjang));
$tanda = implode(',', array_fill(0, count($ids), '?'));

$stmt = $pdo->prepare(
    "SELECT id_menu, nama_menu, harga, stok
     FROM menu
     WHERE id_menu IN ($tanda) AND tersedia = 1"
);
$stmt->execute($ids);
$menu = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($menu) !== count($keranjang)) {
    exit('Sebagian menu sudah tidak tersedia. Silakan periksa kembali keranjang.');
}

$total = 0;
foreach ($menu as $item) {
    $jumlah = (int) $keranjang[$item['id_menu']];

    if ($jumlah > (int) $item['stok']) {
        exit(
            'Stok ' . htmlspecialchars($item['nama_menu'], ENT_QUOTES, 'UTF-8')
            . ' tidak mencukupi. Silakan periksa kembali keranjang.'
        );
    }

    $total += (float) $item['harga'] * $jumlah;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout | Warung Bu-Iing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<main class="container py-5" style="max-width: 760px">
    <h1 class="h3 mb-4">Checkout Pesanan</h1>

<?php if (isset($_SESSION['pesan_checkout'])): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($_SESSION['pesan_checkout'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php unset($_SESSION['pesan_checkout']); ?>
<?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h2 class="h5 mb-3">Ringkasan Pesanan</h2>

            <?php foreach ($menu as $item): ?>
                <div class="d-flex justify-content-between gap-3 mb-2">
                    <span>
                        <?= htmlspecialchars($item['nama_menu'], ENT_QUOTES, 'UTF-8') ?>
                        × <?= (int) $keranjang[$item['id_menu']] ?>
                    </span>
                    <span>
                        Rp<?= number_format(
                            (float) $item['harga'] * (int) $keranjang[$item['id_menu']],
                            0, ',', '.'
                        ) ?>
                    </span>
                </div>
            <?php endforeach; ?>

            <hr>
            <p class="text-end fw-bold">
                Total makanan: Rp<?= number_format($total, 0, ',', '.') ?>
            </p>

            <form action="proses/buat_pesanan.php" method="POST">
                <div class="mb-3">
                    <label class="form-label" for="nama_penerima">Nama penerima</label>
                    <input class="form-control" id="nama_penerima" name="nama_penerima" maxlength="100" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="no_telepon">Nomor WhatsApp</label>
                    <input class="form-control" id="no_telepon" name="no_telepon" type="tel" maxlength="20" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="jenis_pesanan">Metode pesanan</label>
                    <select class="form-select" id="jenis_pesanan" name="jenis_pesanan" required>
                        <option value="ambil_sendiri">Ambil sendiri</option>
                        <option value="diantar">Diantar</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="alamat_pengiriman">Alamat pengiriman</label>
                    <textarea class="form-control" id="alamat_pengiriman" name="alamat_pengiriman" rows="3" placeholder="Isi alamat jika memilih diantar"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="catatan">Catatan (opsional)</label>
                    <textarea class="form-control" id="catatan" name="catatan" rows="2"></textarea>
                </div>

                <button type="submit" class="btn btn-warning w-100">
                    Kirim Pesanan
                </button>
                <a href="keranjang.php" class="btn btn-outline-secondary w-100 mt-2">
                    Kembali ke Keranjang
                </a>
            </form>
        </div>
    </div>
</main>

</body>
</html>
