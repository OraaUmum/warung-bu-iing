<?php
declare(strict_types=1);

require_once __DIR__ . '/config/aplikasi.php';
require_once __DIR__ . '/config/database.php';

$keranjang = $_SESSION['keranjang'] ?? [];
$daftar = [];
$total = 0;

function eKeranjang(mixed $nilai): string
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES, 'UTF-8');
}

function rupiahKeranjang(float $angka): string
{
    return 'Rp' . number_format($angka, 0, ',', '.');
}

if (is_array($keranjang) && count($keranjang) > 0) {
    $ids = array_values(array_filter(
        array_map('intval', array_keys($keranjang)),
        fn($id) => $id > 0
    ));

    if ($ids) {
        $tanda = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $pdo->prepare("
            SELECT id_menu, nama_menu, harga, stok
            FROM menu
            WHERE id_menu IN ($tanda) AND tersedia = 1
            ORDER BY nama_menu ASC
        ");

        $stmt->execute($ids);
        $daftar = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

foreach ($daftar as &$item) {
    $id = (int) $item['id_menu'];
    $item['jumlah'] = (int) ($keranjang[$id] ?? 0);
    $item['subtotal'] = (float) $item['harga'] * $item['jumlah'];
    $total += $item['subtotal'];
}
unset($item);

$pesan = $_SESSION['pesan_keranjang'] ?? '';
unset($_SESSION['pesan_keranjang']);

$jumlahItem = count($daftar);
$jumlahProduk = array_sum(array_column($daftar, 'jumlah'));
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Keranjang Belanja | Warung Bu-Iing</title>

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
            background: rgba(255,255,255,.96);
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
            border-radius: 22px;
            padding: 28px;
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

        .warung-card {
            background: white;
            border: 1px solid #f0ece4;
            border-radius: 18px;
            box-shadow: 0 5px 20px rgba(60,45,20,.045);
        }

        .cart-item {
            padding: 20px;
            border-bottom: 1px solid #f0ece4;
        }

        .cart-item:last-child {
            border-bottom: 0;
        }

        .menu-icon {
            width: 54px;
            height: 54px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--warung-cream);
            color: #a77708;
            border-radius: 15px;
            font-size: 23px;
        }

        .menu-name {
            font-weight: 750;
            font-size: 16px;
        }

        .form-control {
            min-height: 42px;
            border-color: #e8e1d5;
            border-radius: 10px;
        }

        .form-control:focus {
            border-color: var(--warung-primary);
            box-shadow: 0 0 0 .2rem rgba(255,189,36,.17);
        }

        .btn-warung {
            background: var(--warung-primary);
            border: 0;
            color: #352500;
            font-weight: 700;
            border-radius: 12px;
        }

        .btn-warung:hover {
            background: var(--warung-primary-dark);
            color: #352500;
        }

        .summary-box {
            background: #fff9eb;
            border-radius: 15px;
            padding: 20px;
        }

        .empty-icon {
            width: 75px;
            height: 75px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--warung-cream);
            border-radius: 22px;
            color: #a77708;
            font-size: 32px;
            margin: 0 auto 18px;
        }

        footer {
            color: var(--warung-muted);
            font-size: 13px;
        }

        @media (max-width: 576px) {
            .page-header {
                padding: 22px 18px;
            }

            .cart-item {
                padding: 16px;
            }

            .menu-name {
                font-size: 15px;
            }
        }
    </style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-warung sticky-top border-bottom py-3">
    <div class="container">

        <a class="navbar-brand d-flex align-items-center gap-2 m-0"
           href="<?= eKeranjang(url('katalog.php')) ?>">

            <span class="brand-icon">🍲</span>

            <span class="navbar-brand-text fw-bold">
                Warung Bu-Iing
                <small class="d-block text-secondary fw-normal" style="font-size: 11px;">
                    Masakan Rumahan
                </small>
            </span>
        </a>

        <a href="<?= eKeranjang(url('katalog.php')) ?>"
           class="btn btn-light border rounded-pill btn-sm px-3">
            <i class="bi bi-arrow-left me-1"></i>
            <span class="d-none d-sm-inline">Kembali ke Menu</span>
            <span class="d-sm-none">Menu</span>
        </a>

    </div>
</nav>

<main class="container py-4 py-md-5" style="max-width: 950px;">

    <!-- HEADER -->
    <section class="page-header mb-4">

        <span class="page-label">
            <i class="bi bi-bag"></i>
            Belanjaanmu
        </span>

        <h1 class="fw-bold mt-3 mb-2">Keranjang Belanja</h1>

        <p class="text-secondary mb-0">
            Periksa kembali menu pilihanmu sebelum melanjutkan pesanan.
        </p>

    </section>

    <!-- PESAN -->
    <?php if ($pesan !== ''): ?>
        <div class="alert alert-info border-0 rounded-3">
            <i class="bi bi-info-circle me-1"></i>
            <?= eKeranjang($pesan) ?>
        </div>
    <?php endif; ?>

    <?php if (!$daftar): ?>

        <!-- KERANJANG KOSONG -->
        <section class="warung-card text-center p-4 p-md-5">

            <div class="empty-icon">
                <i class="bi bi-basket3"></i>
            </div>

            <h2 class="h5 fw-bold">Keranjangmu Masih Kosong</h2>

            <p class="text-secondary mb-4">
                Yuk, pilih masakan rumahan favoritmu dari menu yang tersedia.
            </p>

            <a href="<?= eKeranjang(url('katalog.php')) ?>"
               class="btn btn-warung px-4 py-2">
                <i class="bi bi-plus-lg me-1"></i>
                Mulai Belanja
            </a>

        </section>

    <?php else: ?>

        <div class="row g-4">

            <!-- DAFTAR MENU -->
            <div class="col-lg-7">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Menu Pilihan</h2>
                        <p class="small text-secondary mb-0">
                            <?= $jumlahItem ?> jenis menu · <?= $jumlahProduk ?> item
                        </p>
                    </div>

                    <a href="<?= eKeranjang(url('katalog.php')) ?>"
                       class="btn btn-outline-warning btn-sm rounded-pill">
                        <i class="bi bi-plus-lg me-1"></i>
                        Tambah Menu
                    </a>
                </div>

                <div class="warung-card">

                    <?php foreach ($daftar as $item): ?>

                        <div class="cart-item">

                            <div class="d-flex gap-3">

                                <div class="menu-icon">
                                    <i class="bi bi-egg-fried"></i>
                                </div>

                                <div class="flex-grow-1 min-w-0">

                                    <div class="menu-name">
                                        <?= eKeranjang($item['nama_menu']) ?>
                                    </div>

                                    <div class="small text-secondary mt-1">
                                        <?= eKeranjang(rupiahKeranjang((float) $item['harga'])) ?>
                                        / item
                                    </div>

                                    <div class="small text-secondary mt-1">
                                        Stok tersedia: <?= (int) $item['stok'] ?>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center gap-2 mt-3">

                                        <!-- FORM EDIT JUMLAH -->
                                        <form action="<?= eKeranjang(url('proses/ubah_keranjang.php')) ?>"
                                              method="post"
                                              class="d-flex align-items-center gap-2">

                                            <input type="hidden" name="aksi" value="ubah">
                                            <input type="hidden" name="id_menu"
                                                   value="<?= (int) $item['id_menu'] ?>">

                                            <input
                                                type="number"
                                                name="jumlah"
                                                class="form-control text-center"
                                                style="width: 75px;"
                                                min="1"
                                                max="<?= (int) $item['stok'] ?>"
                                                value="<?= (int) $item['jumlah'] ?>"
                                                required
                                                aria-label="Jumlah <?= eKeranjang($item['nama_menu']) ?>"
                                            >

                                            <button type="submit"
                                                    class="btn btn-outline-secondary btn-sm rounded-3"
                                                    title="Simpan jumlah">
                                                <i class="bi bi-check-lg"></i>
                                                <span class="d-none d-sm-inline">Simpan</span>
                                            </button>

                                        </form>

                                        <!-- FORM HAPUS -->
                                        <form action="<?= eKeranjang(url('proses/ubah_keranjang.php')) ?>"
                                              method="post"
                                              onsubmit="return confirm('Hapus menu ini dari keranjang?')">

                                            <input type="hidden" name="aksi" value="hapus">
                                            <input type="hidden" name="id_menu"
                                                   value="<?= (int) $item['id_menu'] ?>">

                                            <button type="submit"
                                                    class="btn btn-outline-danger btn-sm rounded-3"
                                                    title="Hapus menu">
                                                <i class="bi bi-trash"></i>
                                                <span class="d-none d-sm-inline">Hapus</span>
                                            </button>

                                        </form>

                                    </div>

                                    <div class="text-end fw-bold mt-3">
                                        Subtotal:
                                        <?= eKeranjang(rupiahKeranjang((float) $item['subtotal'])) ?>
                                    </div>

                                </div>
                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

                <div class="small text-secondary mt-3">
                    <i class="bi bi-info-circle me-1"></i>
                    Pastikan jumlah pesanan tidak melebihi stok yang tersedia.
                </div>

            </div>

            <!-- RINGKASAN -->
            <div class="col-lg-5">

                <div class="warung-card p-3 p-md-4">

                    <h2 class="h5 fw-bold mb-3">Ringkasan Belanja</h2>

                    <div class="d-flex justify-content-between small mb-3">
                        <span class="text-secondary">Jumlah menu</span>
                        <span class="fw-semibold"><?= $jumlahProduk ?> item</span>
                    </div>

                    <div class="d-flex justify-content-between small mb-3">
                        <span class="text-secondary">Jenis menu</span>
                        <span class="fw-semibold"><?= $jumlahItem ?> menu</span>
                    </div>

                    <hr>

                    <div class="summary-box mb-3">

                        <div class="small text-secondary mb-1">
                            Total Belanja
                        </div>

                        <div class="fs-3 fw-bold">
                            <?= eKeranjang(rupiahKeranjang($total)) ?>
                        </div>

                        <div class="small text-secondary mt-1">
                            Belum termasuk biaya pengantaran jika ada.
                        </div>

                    </div>

                    <a href="<?= eKeranjang(url('checkout.php')) ?>"
                       class="btn btn-warung w-100 py-3">
                        Lanjut Checkout
                        <i class="bi bi-arrow-right ms-1"></i>
                    </a>

                    <a href="<?= eKeranjang(url('katalog.php')) ?>"
                       class="btn btn-light border w-100 rounded-3 mt-2 py-2">
                        <i class="bi bi-plus-lg me-1"></i>
                        Tambah Pesanan Lagi
                    </a>

                </div>

            </div>

        </div>

    <?php endif; ?>

    <footer class="text-center mt-5 pb-3">
        <div class="fw-semibold">Warung Bu-Iing</div>
        <div>Masakan Rumahan, Makan Enak Praktis.</div>
    </footer>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
