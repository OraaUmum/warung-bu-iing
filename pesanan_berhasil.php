<?php
declare(strict_types=1);

require_once __DIR__ . '/config/aplikasi.php';

$kode = $_SESSION['kode_pesanan_terakhir'] ?? null;

if (!$kode) {
    header('Location: ' . URL_DASAR . '/katalog.php');
    exit;
}

unset($_SESSION['kode_pesanan_terakhir']);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pesanan Berhasil | Warung Bu-Iing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5 text-center" style="max-width: 600px">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-5">
                <div class="text-success mb-3">
                    <icon name="check-circle" size="2xl" />
                </div>
                <h1 class="h3">Pesanan Berhasil Dikirim!</h1>
                <p class="text-secondary">
                    Pesanan kamu sudah tercatat dan sedang menunggu konfirmasi admin.
                </p>
                <p>Kode pesanan:</p>
                <h2 class="h4 fw-bold"><?= htmlspecialchars($kode, ENT_QUOTES, 'UTF-8') ?></h2>
                <div class="d-grid gap-2 mt-4">
    <a href="<?= htmlspecialchars(url('status_pesanan.php'), ENT_QUOTES, 'UTF-8') ?>"
       class="btn btn-warning">
        Cek Status Pesanan
    </a>

    <a href="<?= htmlspecialchars(url('katalog.php'), ENT_QUOTES, 'UTF-8') ?>"
       class="btn btn-outline-secondary">
        Kembali ke Menu
    </a>
</div>
            </div>
        </div>
    </main>
</body>
</html>
