<?php
declare(strict_types=1);

require_once __DIR__ . '/config/aplikasi.php';

$hasil = $_SESSION['hasil_cek_status'] ?? [];
$pesan = $_SESSION['pesan_cek_status'] ?? '';

unset($_SESSION['pesan_cek_status']);

function eStatus(mixed $nilai): string
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES, 'UTF-8');
}

function rupiahStatus(float $angka): string
{
    return 'Rp' . number_format($angka, 0, ',', '.');
}

function tanggalStatus(?string $tanggal): string
{
    if (!$tanggal) {
        return '-';
    }

    $timestamp = strtotime($tanggal);

    if ($timestamp === false) {
        return $tanggal;
    }

    return date('d M Y, H:i', $timestamp) . ' WIB';
}

$labelStatus = [
    'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
    'dikonfirmasi' => 'Dikonfirmasi',
    'diproses' => 'Sedang Diproses',
    'siap_diambil' => 'Siap Diambil',
    'dikirim' => 'Sedang Dikirim',
    'selesai' => 'Selesai',
    'dibatalkan' => 'Dibatalkan',
];

$warnaStatus = [
    'menunggu_konfirmasi' => 'warning',
    'dikonfirmasi' => 'info',
    'diproses' => 'primary',
    'siap_diambil' => 'success',
    'dikirim' => 'primary',
    'selesai' => 'success',
    'dibatalkan' => 'danger',
];

$deskripsiStatus = [
    'menunggu_konfirmasi' => 'Pesananmu sedang menunggu konfirmasi dari warung.',
    'dikonfirmasi' => 'Pesanan sudah dikonfirmasi dan akan segera diproses.',
    'diproses' => 'Pesananmu sedang disiapkan oleh Warung Bu-Iing.',
    'siap_diambil' => 'Pesanan sudah siap. Silakan datang untuk mengambilnya.',
    'dikirim' => 'Pesanan sedang dalam perjalanan menuju alamatmu.',
    'selesai' => 'Pesanan telah selesai. Terima kasih sudah memesan!',
    'dibatalkan' => 'Pesanan ini telah dibatalkan.',
];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Cek Status Pesanan | Warung Bu-Iing</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --warung-primary: #ffbd24;
            --warung-primary-dark: #e5a500;
            --warung-cream: #fff7e5;
            --warung-text: #382b20;
            --warung-muted: #7b746b;
        }

        body {
            background: #fffdf8;
            color: var(--warung-text);
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
        }

        .navbar-warung {
            background: rgba(255, 255, 255, .96);
            backdrop-filter: blur(10px);
        }

        .brand-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff0c2;
            border-radius: 16px;
            font-size: 25px;
        }

        .navbar-brand-text {
            color: var(--warung-text);
            font-size: 17px;
        }

        .page-header {
            background: linear-gradient(135deg, #fff1c9, #fff9ed);
            border-radius: 24px;
            padding: 32px;
        }

        .page-label {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: var(--warung-primary);
            color: #352500;
            padding: 8px 15px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 700;
        }

        .page-header h1 {
            font-size: clamp(26px, 5vw, 36px);
            font-weight: 800;
            line-height: 1.2;
            margin-top: 18px;
        }

        .page-header p {
            color: var(--warung-muted);
            margin-bottom: 0;
            line-height: 1.7;
        }

        .warung-card {
            background: #fff;
            border: 1px solid #f0ece4;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(60, 45, 20, .045);
        }

        .search-icon {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--warung-cream);
            color: #9b6c00;
            border-radius: 14px;
            font-size: 20px;
        }

        .form-control {
            min-height: 48px;
            border-radius: 12px;
            border-color: #e8e1d5;
        }

        .form-control:focus {
            border-color: var(--warung-primary);
            box-shadow: 0 0 0 .2rem rgba(255, 189, 36, .17);
        }

        .btn-warung {
            background: var(--warung-primary);
            border: 0;
            color: #352500;
            font-weight: 700;
            border-radius: 12px;
            min-height: 48px;
        }

        .btn-warung:hover {
            background: var(--warung-primary-dark);
            color: #352500;
        }

        .order-card {
            background: white;
            border: 1px solid #f0ece4;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 5px 18px rgba(60, 45, 20, .04);
        }

        .order-card-header {
            padding: 20px;
            background: #fffefa;
            border-bottom: 1px solid #f0ece4;
        }

        .order-card-body {
            padding: 20px;
        }

        .order-code {
            font-size: 16px;
            font-weight: 800;
            color: var(--warung-text);
            overflow-wrap: anywhere;
        }

        .order-date {
            font-size: 13px;
            color: var(--warung-muted);
        }

        .status-description {
            background: var(--warung-cream);
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 13px;
            color: #72531b;
            line-height: 1.6;
        }

        .menu-item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 9px 0;
            border-bottom: 1px dashed #eee8de;
            font-size: 14px;
        }

        .menu-item:last-child {
            border-bottom: 0;
        }

        .total-area {
            background: #fff9eb;
            border-radius: 14px;
            padding: 15px;
        }

        .section-title {
            font-weight: 800;
            color: var(--warung-text);
        }

        .empty-icon {
            width: 65px;
            height: 65px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--warung-cream);
            border-radius: 20px;
            font-size: 28px;
            color: #a77708;
            margin: 0 auto 15px;
        }

        footer {
            color: var(--warung-muted);
            font-size: 13px;
        }

        @media (max-width: 576px) {
            .page-header {
                padding: 24px 20px;
                border-radius: 18px;
            }

            .order-card-header,
            .order-card-body {
                padding: 16px;
            }

            .order-code {
                font-size: 14px;
            }
        }
    </style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-warung sticky-top border-bottom py-3">
    <div class="container">

        <a class="navbar-brand d-flex align-items-center gap-2 m-0"
           href="<?= eStatus(url('katalog.php')) ?>">

            <span class="brand-icon">🍲</span>

            <span class="navbar-brand-text fw-bold">
                Warung Bu-Iing
                <small class="d-block text-secondary fw-normal" style="font-size: 11px;">
                    Masakan Rumahan
                </small>
            </span>
        </a>

        <a href="<?= eStatus(url('katalog.php')) ?>"
           class="btn btn-light border rounded-pill btn-sm px-3">
            <i class="bi bi-arrow-left me-1"></i>
            <span class="d-none d-sm-inline">Kembali ke Menu</span>
            <span class="d-sm-none">Menu</span>
        </a>

    </div>
</nav>

<main class="container py-4 py-md-5" style="max-width: 850px;">

    <!-- HEADER -->
    <section class="page-header mb-4">

        <span class="page-label">
            <i class="bi bi-receipt"></i>
            Layanan Pelanggan
        </span>

        <h1>Cek Status Pesanan</h1>

        <p class="mt-2">
            Pantau pesananmu dengan mudah. Masukkan nomor WhatsApp
            yang kamu gunakan saat melakukan pemesanan.
        </p>

    </section>

    <!-- FORM PENCARIAN -->
    <section class="warung-card p-3 p-md-4 mb-4">

        <div class="d-flex align-items-center gap-3 mb-3">
            <div class="search-icon">
                <i class="bi bi-search"></i>
            </div>

            <div>
                <h2 class="h6 fw-bold mb-1">Cari Pesananmu</h2>
                <p class="small text-secondary mb-0">
                    Gunakan nomor WhatsApp saat checkout.
                </p>
            </div>
        </div>

        <?php if ($pesan !== ''): ?>
            <div class="alert alert-warning border-0 rounded-3 small">
                <i class="bi bi-info-circle me-1"></i>
                <?= eStatus($pesan) ?>
            </div>
        <?php endif; ?>

        <form action="<?= eStatus(url('proses/cek_status_pesanan.php')) ?>"
              method="post">

            <div class="mb-3">
                <label for="no_telepon" class="form-label fw-semibold small">
                    Nomor WhatsApp
                </label>

                <input
                    type="tel"
                    class="form-control"
                    id="no_telepon"
                    name="no_telepon"
                    maxlength="30"
                    placeholder="Contoh: 081234567890"
                    autocomplete="tel"
                    inputmode="tel"
                    required
                >

                <div class="form-text">
                    <i class="bi bi-shield-check me-1"></i>
                    Pastikan nomor sama dengan yang digunakan saat memesan.
                </div>
            </div>

            <button type="submit" class="btn btn-warung w-100">
                <i class="bi bi-search me-2"></i>
                Cari Pesanan
            </button>

        </form>
    </section>

    <!-- HASIL PENCARIAN -->
    <?php if (is_array($hasil) && count($hasil) > 0): ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h5 section-title mb-1">Riwayat Pesanan</h2>
                <p class="small text-secondary mb-0">
                    Berikut pesanan yang ditemukan.
                </p>
            </div>

            <span class="badge rounded-pill text-bg-warning px-3 py-2">
                <?= count($hasil) ?> Pesanan
            </span>
        </div>

        <?php foreach ($hasil as $pesanan): ?>

            <?php
            $status = $pesanan['status_pesanan'] ?? '';
            $label = $labelStatus[$status] ?? 'Status Tidak Diketahui';
            $warna = $warnaStatus[$status] ?? 'secondary';
            $deskripsi = $deskripsiStatus[$status] ?? 'Silakan hubungi warung untuk informasi lebih lanjut.';
            ?>

            <article class="order-card mb-3">

                <!-- HEADER PESANAN -->
                <div class="order-card-header">

                    <div class="d-flex justify-content-between align-items-start gap-3">

                        <div class="flex-grow-1">
                            <div class="small text-secondary mb-1">
                                Kode Pesanan
                            </div>

                            <div class="order-code">
                                <?= eStatus($pesanan['kode_pesanan'] ?? '-') ?>
                            </div>

                            <div class="order-date mt-2">
                                <i class="bi bi-calendar3 me-1"></i>
                                <?= eStatus(tanggalStatus($pesanan['dibuat_pada'] ?? null)) ?>
                            </div>
                        </div>

                        <span class="badge text-bg-<?= eStatus($warna) ?> rounded-pill px-3 py-2">
                            <?= eStatus($label) ?>
                        </span>

                    </div>

                </div>

                <!-- DETAIL PESANAN -->
                <div class="order-card-body">

                    <div class="status-description mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        <?= eStatus($deskripsi) ?>
                    </div>

                    <div class="small fw-bold text-secondary mb-2">
                        <i class="bi bi-basket2 me-1"></i>
                        Rincian Menu
                    </div>

                    <div class="mb-3">

                        <?php foreach (($pesanan['detail'] ?? []) as $item): ?>

                            <div class="menu-item">
                                <span>
                                    <?= eStatus($item['nama_menu'] ?? '-') ?>
                                    <span class="text-secondary">
                                        × <?= (int) ($item['jumlah'] ?? 0) ?>
                                    </span>
                                </span>

                                <span class="fw-semibold text-nowrap">
                                    <?= eStatus(rupiahStatus((float) ($item['subtotal'] ?? 0))) ?>
                                </span>
                            </div>

                        <?php endforeach; ?>

                    </div>

                    <!-- INFORMASI PESANAN -->
                    <div class="d-flex justify-content-between align-items-center small mb-3">
                        <span class="text-secondary">
                            <i class="bi bi-truck me-1"></i>
                            Jenis Pesanan
                        </span>

                        <span class="fw-semibold">
                            <?= ($pesanan['jenis_pesanan'] ?? '') === 'diantar'
                                ? 'Diantar'
                                : 'Ambil Sendiri' ?>
                        </span>
                    </div>

                    <!-- TOTAL -->
                    <div class="total-area">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Total Pesanan</span>

                            <span class="fw-bold fs-5">
                                <?= eStatus(rupiahStatus((float) ($pesanan['total_harga'] ?? 0))) ?>
                            </span>
                        </div>
                    </div>

                </div>

            </article>

        <?php endforeach; ?>

        <div class="small text-secondary mt-3">
            <i class="bi bi-clock-history me-1"></i>
            Informasi di atas merupakan data saat pencarian dilakukan.
            Cari kembali untuk memperbarui status pesanan.
        </div>

    <?php elseif (is_array($hasil) && count($hasil) === 0): ?>

        <section class="warung-card text-center p-4">
            <div class="empty-icon">
                <i class="bi bi-receipt"></i>
            </div>

            <h2 class="h5 fw-bold">Belum Ada Hasil Pencarian</h2>

            <p class="text-secondary small mb-0">
                Masukkan nomor WhatsApp untuk melihat riwayat pesananmu.
            </p>
        </section>

    <?php endif; ?>

    <!-- FOOTER -->
    <footer class="text-center mt-5 pb-3">
        <div class="fw-semibold">Warung Bu-Iing</div>
        <div>Masakan Rumahan, Makan Enak Praktis.</div>
    </footer>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
