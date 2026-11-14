<?php
declare(strict_types=1);

require_once __DIR__ . '/config/aplikasi.php';
require_once __DIR__ . '/config/database.php';

$stmt = $pdo->query("
    SELECT id_menu, nama_menu, deskripsi, harga, stok, foto
    FROM menu
    WHERE tersedia = 1 AND stok > 0
    ORDER BY nama_menu ASC
");

$daftar_menu = $stmt->fetchAll(PDO::FETCH_ASSOC);

function rupiah(float $angka): string
{
    return 'Rp' . number_format($angka, 0, ',', '.');
}

function e(mixed $nilai): string
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES, 'UTF-8');
}

$jumlahKeranjang = 0;
foreach (($_SESSION['keranjang'] ?? []) as $jumlah) {
    $jumlahKeranjang += max(0, (int) $jumlah);
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fffaf0">

    <title>Menu Makanan | Warung Bu-Iing</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #fffaf2;
            color: #352b22;
        }

        .navbar-warung {
            background: #ffffff;
        }
        .nav-actions {
            width: 100%;
            margin-top: 12px;
        }

        .nav-link-item {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 14px;
            font-weight: 600;
            white-space: nowrap;
        }

        .nav-link-item i {
            font-size: 17px;
        }

        @media (min-width: 768px) {
            .nav-actions {
                width: auto;
                margin-top: 0;
                gap: 20px !important;
            }
        }
        .brand-icon {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff0c2;
            border-radius: 13px;
            font-size: 22px;
        }

        .hero {
            background: linear-gradient(135deg, #fff1c9, #fffaf0);
            border-bottom: 1px solid #f2e5c9;
        }

        .hero-title {
            letter-spacing: -1px;
            line-height: 1.15;
        }

        .menu-card {
            border: 1px solid #f0e9df;
            border-radius: 18px;
            overflow: hidden;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .menu-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(55, 40, 20, .09) !important;
        }

        .menu-image {
            width: 100%;
            height: 210px;
            object-fit: cover;
            background: #f1ece3;
        }

        .menu-image-placeholder {
            height: 210px;
            background: #f1ece3;
            color: #968b7d;
        }

        .menu-price {
            color: #b66a0a;
            font-size: 1.2rem;
            font-weight: 800;
        }

        .btn-warning {
            background-color: #f5bd42;
            border-color: #f5bd42;
            color: #352b22;
            font-weight: 600;
        }

        .btn-warning:hover {
            background-color: #e9aa24;
            border-color: #e9aa24;
            color: #352b22;
        }

        .quantity-input {
            min-height: 46px;
            border-radius: 10px;
        }

        .btn-order {
            min-height: 46px;
            border-radius: 10px;
        }

        .section-heading {
            letter-spacing: -.5px;
        }

        @media (max-width: 575.98px) {
            .hero {
                padding-top: 35px !important;
                padding-bottom: 35px !important;
            }

            .hero-title {
                font-size: 2rem;
            }

            .menu-image,
            .menu-image-placeholder {
                height: 190px;
            }

            .menu-card .card-body {
                padding: 16px;
            }

            .navbar-brand-text {
                font-size: .95rem;
            }

            .nav-actions .btn {
                font-size: .78rem;
                padding: 8px 10px;
            }
        }
    </style>
</head>

<body>


<nav class="navbar navbar-warung sticky-top border-bottom py-3">
    <div class="container">

        <div class="w-100 d-flex flex-wrap align-items-center justify-content-between">

            <!-- Identitas Warung -->
            <a class="navbar-brand d-flex align-items-center gap-2 m-0"
               href="<?= e(url('katalog.php')) ?>">
                <span class="brand-icon">🍲</span>
                <span class="navbar-brand-text fw-bold">
                    Warung Bu-Iing
                    <small class="d-block text-secondary fw-normal" style="font-size: 11px;">
                        Masakan Rumahan
                    </small>
                </span>
            </a>

            <!-- Navigasi -->
            <div class="d-flex align-items-center justify-content-between gap-2 nav-actions">

                <a href="<?= e(url('status_pesanan.php')) ?>"
                   class="nav-link-item text-decoration-none text-dark">
                    <i class="bi bi-receipt"></i>
                    <span>Status</span>
                </a>

                <a href="<?= e(url('keranjang.php')) ?>"
                   class="btn btn-warning rounded-pill px-3 py-2 fw-semibold position-relative">
                    <i class="bi bi-bag me-1"></i>
                    Keranjang

                    <?php if ($jumlahKeranjang > 0): ?>
                        <span class="badge rounded-pill text-bg-danger ms-1">
                            <?= (int) $jumlahKeranjang ?>
                        </span>
                    <?php endif; ?>
                </a>

                <a href="<?= e(url('login.php')) ?>"
                   class="nav-link-item text-decoration-none text-secondary">
                    <i class="bi bi-person-lock"></i>
                    <span>Login</span>
                </a>

            </div>
        </div>
    </div>
</nav>


<header class="hero py-5">
    <div class="container py-lg-3">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="badge rounded-pill text-bg-warning mb-3 px-3 py-2">
                    🍽️ Masakan Rumahan
                </span>

                <h1 class="hero-title fw-bold mb-3">
                    Makan Enak,<br>
                    Praktis dan Bersahabat.
                </h1>

                <p class="text-secondary mb-4" style="max-width: 520px;">
                    Nikmati aneka masakan rumahan favorit.
                    Pilih menu, masukkan ke keranjang, dan pesan dengan mudah.
                </p>

                <a href="#daftar-menu" class="btn btn-warning btn-lg px-4">
                    Lihat Menu
                    <span class="ms-1">↓</span>
                </a>
            </div>
        </div>
    </div>
</header>

<main class="container py-4 py-lg-5" id="daftar-menu">

    <div class="mb-4">
        <span class="text-uppercase text-secondary small fw-semibold">
            Pilihan Hari Ini
        </span>
        <h2 class="section-heading h3 fw-bold mt-1 mb-1">
            Menu Warung Bu-Iing
        </h2>
        <p class="text-secondary mb-0">
            Pilih makanan favoritmu sebelum kehabisan.
        </p>
    </div>

    <div class="row g-3 g-lg-4">

        <?php if (count($daftar_menu) === 0): ?>

            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body text-center py-5">
                        <div class="fs-1 mb-3">🍱</div>
                        <h3 class="h5 fw-bold">Belum Ada Menu Tersedia</h3>
                        <p class="text-secondary mb-0">
                            Silakan kembali lagi nanti untuk melihat menu terbaru.
                        </p>
                    </div>
                </div>
            </div>

        <?php else: ?>

            <?php foreach ($daftar_menu as $menu): ?>

                <div class="col-12 col-sm-6 col-lg-4">

                    <div class="card menu-card h-100 shadow-sm">

                        <?php if (!empty($menu['foto'])): ?>
                            <img
                                src="<?= e(url($menu['foto'])) ?>"
                                class="menu-image"
                                alt="<?= e($menu['nama_menu']) ?>"
                                loading="lazy"
                            >
                        <?php else: ?>
                            <div class="menu-image-placeholder d-flex align-items-center justify-content-center">
                                <div class="text-center">
                                    <div class="fs-1 mb-2">🍲</div>
                                    <small>Foto belum tersedia</small>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="card-body d-flex flex-column">

                            <h3 class="h5 fw-bold mb-2">
                                <?= e($menu['nama_menu']) ?>
                            </h3>

                            <p class="text-secondary small mb-3 flex-grow-1">
                                <?= nl2br(e($menu['deskripsi'] ?? '')) ?>
                            </p>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="menu-price">
                                    <?= rupiah((float) $menu['harga']) ?>
                                </span>

                                <span class="badge rounded-pill text-bg-light border text-secondary">
                                    Stok <?= (int) $menu['stok'] ?>
                                </span>
                            </div>

                            <form method="POST"
                                  action="<?= e(url('proses/keranjang.php')) ?>">

                                <input
                                    type="hidden"
                                    name="id_menu"
                                    value="<?= (int) $menu['id_menu'] ?>"
                                >

                                <label
                                    class="form-label small fw-semibold"
                                    for="jumlah-<?= (int) $menu['id_menu'] ?>">
                                    Jumlah Porsi
                                </label>

                                <input
                                    type="number"
                                    id="jumlah-<?= (int) $menu['id_menu'] ?>"
                                    name="jumlah"
                                    class="form-control quantity-input mb-2"
                                    value="1"
                                    min="1"
                                    max="<?= (int) $menu['stok'] ?>"
                                    required
                                >

                                <button type="submit"
                                        class="btn btn-warning btn-order w-100">
                                    + Tambah ke Keranjang
                                </button>
                            </form>

                        </div>
                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>
</main>

<footer class="bg-white border-top mt-4 py-4">
    <div class="container text-center">
        <div class="fw-bold mb-1">Warung Bu-Iing 🍲</div>
        <p class="text-secondary small mb-0">
            Masakan rumahan dengan rasa istimewa.
            <br>
            &copy; <?= date('Y') ?> Warung Bu-Iing
        </p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
