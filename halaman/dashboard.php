<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/aplikasi.php';
require_once __DIR__ . '/../config/database.php';

// Pastikan koneksi database tersedia.
if (!isset($pdo) || !$pdo instanceof PDO) {
    exit('Koneksi database belum tersedia. Periksa config/database.php.');
}

// Ringkasan seluruh pesanan.
$stmt = $pdo->query("
    SELECT
        COUNT(*) AS total_pesanan,
        COALESCE(SUM(status_pesanan = 'menunggu_konfirmasi'), 0)
            AS menunggu_konfirmasi,
        COALESCE(SUM(status_pesanan IN ('dikonfirmasi', 'diproses')), 0)
            AS sedang_diproses,
        COALESCE(SUM(status_pesanan IN ('siap_diambil', 'dikirim')), 0)
            AS siap_diselesaikan,
        COALESCE(SUM(status_pesanan = 'selesai'), 0)
            AS pesanan_selesai
    FROM pesanan
");

$ringkasan = $stmt->fetch(PDO::FETCH_ASSOC);

// Pesanan hari ini.
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM pesanan
    WHERE dibuat_pada >= CURDATE()
      AND dibuat_pada < CURDATE() + INTERVAL 1 DAY
");

$pesanan_hari_ini = (int) $stmt->fetchColumn();

// Menu tersedia.
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM menu
    WHERE tersedia = 1 AND stok > 0
");

$menu_tersedia = (int) $stmt->fetchColumn();

// Nilai pesanan selesai hari ini.
// Bukan total pembayaran terverifikasi.
$stmt = $pdo->query("
    SELECT COALESCE(SUM(total_harga), 0)
    FROM pesanan
    WHERE status_pesanan = 'selesai'
      AND dibuat_pada >= CURDATE()
      AND dibuat_pada < CURDATE() + INTERVAL 1 DAY
");

$pemasukan_hari_ini = (float) $stmt->fetchColumn();

function rupiahDashboard(float $jumlah): string
{
    return 'Rp ' . number_format($jumlah, 0, ',', '.');
}

function angkaDashboard(mixed $jumlah): string
{
    return number_format((int) $jumlah, 0, ',', '.');
}

$judul_halaman = 'Dashboard';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<head>
    <link rel="stylesheet" type="text/css" href="/../aset/css/halaman.css">
</head>

<main class="container-fluid page-content px-3 px-lg-4">

    <!-- HEADER DASHBOARD -->
    <section class="dashboard-welcome mb-4">

        <div class="row align-items-center g-3">

            <div class="col-md-8">
                <div class="small fw-bold mb-2" style="color: #98700e;">
                    <i class="bi bi-sun me-1"></i>
                    PANEL PENGELOLA WARUNG
                </div>

                <h1 class="h3 mb-2">
                    Selamat Datang, Admin!
                </h1>

                <p class="text-secondary mb-2">
                    Pantau dan kelola aktivitas Warung Bu-Iing melalui dashboard ini.
                </p>

                <div class="dashboard-date">
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= date('d-m-Y') ?>
                </div>
            </div>

            <div class="col-md-4 text-md-end">
                <a href="<?= htmlspecialchars(url('halaman/pesanan.php'), ENT_QUOTES, 'UTF-8') ?>"
                   class="btn btn-warung px-4 py-2">
                    <i class="bi bi-receipt me-1"></i>
                    Lihat Pesanan
                </a>
            </div>

        </div>

    </section>

    <!-- STATISTIK UTAMA -->
    <section class="mb-4">

        <div class="mb-3">
            <h2 class="dashboard-section-title mb-1">
                Ringkasan Hari Ini
            </h2>

            <p class="text-secondary small mb-0">
                Informasi singkat aktivitas warung.
            </p>
        </div>

        <div class="row g-3">

            <!-- MENU TERSEDIA -->
            <div class="col-6 col-lg-3">
                <div class="dashboard-stat">
                    <div class="stat-icon bg-warning-subtle text-warning">
                        <i class="bi bi-egg-fried"></i>
                    </div>

                    <div class="small text-secondary">Menu Tersedia</div>

                    <div class="stat-number">
                        <?= angkaDashboard($menu_tersedia) ?>
                    </div>

                    <a href="<?= htmlspecialchars(url('halaman/menu.php'), ENT_QUOTES, 'UTF-8') ?>"
                    class="small stat-link">
                        Kelola Menu <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- PESANAN HARI INI -->
            <div class="col-6 col-lg-3">
                <div class="dashboard-stat">
                    <div class="stat-icon bg-primary-subtle text-primary">
                        <i class="bi bi-receipt"></i>
                    </div>

                    <div class="small text-secondary">Pesanan Hari Ini</div>

                    <div class="stat-number">
                        <?= angkaDashboard($pesanan_hari_ini) ?>
                    </div>

                    <a href="<?= htmlspecialchars(url('halaman/pesanan.php'), ENT_QUOTES, 'UTF-8') ?>"
                    class="small stat-link">
                        Lihat Pesanan <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- MENUNGGU KONFIRMASI -->
            <div class="col-6 col-lg-3">
                <div class="dashboard-stat">
                    <div class="stat-icon bg-warning-subtle text-warning">
                        <i class="bi bi-hourglass-split"></i>
                    </div>

                    <div class="small text-secondary">Menunggu Konfirmasi</div>

                    <div class="stat-number">
                        <?= angkaDashboard($ringkasan['menunggu_konfirmasi'] ?? 0) ?>
                    </div>

                    <a href="<?= htmlspecialchars(url('halaman/pesanan.php?status=menunggu_konfirmasi'), ENT_QUOTES, 'UTF-8') ?>"
                    class="small stat-link">
                        Periksa Pesanan <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- NILAI PESANAN SELESAI -->
            <div class="col-6 col-lg-3">
                <div class="dashboard-stat">
                    <div class="stat-icon bg-success-subtle text-success">
                        <i class="bi bi-wallet2"></i>
                    </div>

                    <div class="small text-secondary">Nilai Pesanan Selesai</div>

                    <div class="stat-number">
                        <?= rupiahDashboard($pemasukan_hari_ini) ?>
                    </div>

                    <div class="small text-secondary">
                        Berdasarkan tanggal pesanan
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- RINGKASAN STATUS PESANAN -->
    <section class="card mb-4">

        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2 class="dashboard-section-title mb-1">
                    Status Pesanan
                </h2>

                <p class="text-secondary small mb-0 fw-normal">
                    Ringkasan seluruh pesanan yang tercatat.
                </p>
            </div>

            <a href="<?= htmlspecialchars(url('halaman/pesanan.php'), ENT_QUOTES, 'UTF-8') ?>"
               class="btn btn-outline-warung btn-sm">
                Semua Pesanan
                <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="card-body">

            <div class="row g-3">

                <div class="col-6 col-lg-3">
                    <div class="status-summary">
                        <div class="status-label">
                            Total Pesanan
                        </div>

                        <div class="status-number mt-2">
                            <?= angkaDashboard($ringkasan['total_pesanan'] ?? 0) ?>
                        </div>

                        <div class="small text-secondary mt-1">
                            Semua status
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="status-summary">
                        <div class="status-label">
                            Sedang Diproses
                        </div>

                        <div class="status-number mt-2">
                            <?= angkaDashboard($ringkasan['sedang_diproses'] ?? 0) ?>
                        </div>

                        <div class="small text-secondary mt-1">
                            Dikonfirmasi dan diproses
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="status-summary">
                        <div class="status-label">
                            Siap Diambil / Dikirim
                        </div>

                        <div class="status-number mt-2">
                            <?= angkaDashboard($ringkasan['siap_diselesaikan'] ?? 0) ?>
                        </div>

                        <div class="small text-secondary mt-1">
                            Menunggu pelanggan
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="status-summary">
                        <div class="status-label">
                            Pesanan Selesai
                        </div>

                        <div class="status-number mt-2">
                            <?= angkaDashboard($ringkasan['pesanan_selesai'] ?? 0) ?>
                        </div>

                        <div class="small text-secondary mt-1">
                            Telah diselesaikan
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </section>

    <!-- AKSES CEPAT -->
    <section class="mb-4">

        <div class="mb-3">
            <h2 class="dashboard-section-title mb-1">
                Akses Cepat
            </h2>

            <p class="text-secondary small mb-0">
                Pintasan untuk mengelola warung.
            </p>
        </div>

        <div class="row g-3">

            <div class="col-12 col-md-4">
                <a href="<?= htmlspecialchars(url('halaman/menu.php'), ENT_QUOTES, 'UTF-8') ?>"
                   class="quick-action">

                    <div class="quick-action-icon">
                        <i class="bi bi-plus-circle"></i>
                    </div>

                    <div>
                        <div class="quick-action-title">
                            Kelola Menu
                        </div>

                        <div class="quick-action-description">
                            Tambah atau ubah menu warung.
                        </div>
                    </div>

                    <i class="bi bi-chevron-right ms-auto text-secondary"></i>
                </a>
            </div>

            <div class="col-12 col-md-4">
                <a href="<?= htmlspecialchars(url('halaman/pesanan.php'), ENT_QUOTES, 'UTF-8') ?>"
                   class="quick-action">

                    <div class="quick-action-icon">
                        <i class="bi bi-receipt"></i>
                    </div>

                    <div>
                        <div class="quick-action-title">
                            Kelola Pesanan
                        </div>

                        <div class="quick-action-description">
                            Periksa dan perbarui status pesanan.
                        </div>
                    </div>

                    <i class="bi bi-chevron-right ms-auto text-secondary"></i>
                </a>
            </div>

            <div class="col-12 col-md-4">
                <a href="<?= htmlspecialchars(url('halaman/laporan.php'), ENT_QUOTES, 'UTF-8') ?>"
                   class="quick-action">

                    <div class="quick-action-icon">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                    </div>

                    <div>
                        <div class="quick-action-title">
                            Lihat Laporan
                        </div>

                        <div class="quick-action-description">
                            Periksa laporan aktivitas warung.
                        </div>
                    </div>

                    <i class="bi bi-chevron-right ms-auto text-secondary"></i>
                </a>
            </div>

        </div>

    </section>

    <div class="text-secondary small text-center mt-4">
        <i class="bi bi-info-circle me-1"></i>
        Nilai pesanan selesai bukan pengganti laporan pembayaran terverifikasi.
    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>