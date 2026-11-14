<?php
declare(strict_types=1);

$halamanAktif = basename($_SERVER['PHP_SELF']);

$menuNavigasi = [
    'dashboard.php' => [
        'label' => 'Dashboard',
        'ikon' => 'bi-grid-1x2',
        'url' => 'halaman/dashboard.php'
    ],
    'menu.php' => [
        'label' => 'Menu',
        'ikon' => 'bi-egg-fried',
        'url' => 'halaman/menu.php'
    ],
    'pesanan.php' => [
        'label' => 'Pesanan',
        'ikon' => 'bi-receipt',
        'url' => 'halaman/pesanan.php'
    ],
    'pembayaran.php' => [
        'label' => 'Pembayaran',
        'ikon' => 'bi-credit-card',
        'url' => 'halaman/pembayaran.php'
    ],
    'keuangan.php' => [
        'label' => 'Keuangan',
        'ikon' => 'bi-wallet2',
        'url' => 'halaman/keuangan.php'
    ],
    'laporan.php' => [
        'label' => 'Laporan',
        'ikon' => 'bi-file-earmark-bar-graph',
        'url' => 'halaman/laporan.php'
    ],
];
?>

<nav class="navbar navbar-expand-lg navbar-admin sticky-top">
    <div class="container">

        <!-- BRAND -->
        <a class="navbar-brand d-flex align-items-center"
           href="<?= htmlspecialchars(url('halaman/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>">

            <span class="brand-icon-admin">🍲</span>

            <span>
                Warung Bu-Iing
                <small class="d-block text-secondary fw-normal"
                       style="font-size: 11px;">
                    Panel Administrator
                </small>
            </span>
        </a>

        <!-- TOGGLER MOBILE -->
        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navigasiAdmin"
            aria-controls="navigasiAdmin"
            aria-expanded="false"
            aria-label="Buka navigasi"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- NAVIGASI -->
        <div class="collapse navbar-collapse" id="navigasiAdmin">

            <div class="navbar-nav ms-auto align-items-lg-center">

                <?php foreach ($menuNavigasi as $file => $menu): ?>

                    <a
                        class="nav-link <?= $halamanAktif === $file ? 'active' : '' ?>"
                        href="<?= htmlspecialchars(url($menu['url']), ENT_QUOTES, 'UTF-8') ?>"
                        <?= $halamanAktif === $file ? 'aria-current="page"' : '' ?>
                    >
                        <i class="bi <?= htmlspecialchars($menu['ikon'], ENT_QUOTES, 'UTF-8') ?> me-1"></i>
                        <?= htmlspecialchars($menu['label'], ENT_QUOTES, 'UTF-8') ?>
                    </a>

                <?php endforeach; ?>

                <!-- LOGOUT -->
                <div class="admin-nav-footer ms-lg-2">
                    <a
                        href="<?= htmlspecialchars(url('proses/logout.php'), ENT_QUOTES, 'UTF-8') ?>"
                        class="btn btn-danger btn-logout px-3"
                        onclick="return confirm('Yakin ingin keluar dari panel admin?')"
                    >
                        <i class="bi bi-box-arrow-right me-1"></i>
                        Logout
                    </a>
                </div>

            </div>
        </div>

    </div>
</nav>
