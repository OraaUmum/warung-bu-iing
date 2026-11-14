<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/aplikasi.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fungsi.php';

if (
    empty($_SESSION['pengguna_id']) ||
    ($_SESSION['pengguna_peran'] ?? '') !== 'admin'
) {
    header('Location: ' . URL_DASAR . '/login.php');
    exit;
}

$pesan = $_SESSION['pesan_menu'] ?? '';
unset($_SESSION['pesan_menu']);

$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
$menuEdit = null;

if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM menu WHERE id_menu = ?');
    $stmt->execute([$editId]);
    $menuEdit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$menuEdit) {
        $_SESSION['pesan_menu'] = 'Menu tidak ditemukan.';
        header('Location: menu.php');
        exit;
    }
}

$stmt = $pdo->query('SELECT * FROM menu ORDER BY id_menu DESC');
$daftarMenu = $stmt->fetchAll(PDO::FETCH_ASSOC);

$jumlahTersedia = 0;
$jumlahHabis = 0;
$jumlahNonaktif = 0;

foreach ($daftarMenu as $item) {
    if ((int) $item['tersedia'] !== 1) {
        $jumlahNonaktif++;
    } elseif ((int) $item['stok'] <= 0) {
        $jumlahHabis++;
    } else {
        $jumlahTersedia++;
    }
}

$judul_halaman = 'Manajemen Menu';

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>

<head>
    <link rel="stylesheet" type="text/css" href="/../aset/css/halaman.css">
</head>

<main class="container-fluid page-content px-3 px-lg-4">

    <!-- HEADER -->
<section class="menu-heading mb-4">
    <div class="row align-items-center g-3">
        <div class="col-md">
            <div class="small fw-semibold mb-2 text-warning-emphasis">
                <i class="bi bi-egg-fried me-1"></i>
                PENGELOLAAN PRODUK
            </div>

            <h1 class="h3 mb-2">Manajemen Menu</h1>

            <p class="mb-0">
                Kelola makanan, harga, stok, foto, dan ketersediaan menu.
            </p>
        </div>

        <div class="col-md-auto">
            <a href="<?= e(url('katalog.php')) ?>"
               target="_blank"
               rel="noopener noreferrer"
               class="btn btn-warung px-4 py-2">
                <i class="bi bi-shop me-1"></i>
                Lihat Katalog
            </a>
        </div>
    </div>
</section>

    <!-- NOTIFIKASI -->
    <?php if ($pesan !== ''): ?>
        <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-info-circle me-2"></i>
            <?= e($pesan) ?>

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Tutup"></button>
        </div>
    <?php endif; ?>

    <!-- RINGKASAN MENU -->
<section class="row g-3 mb-4">
    <div class="col-12 col-sm-4">
        <div class="menu-summary h-100">
            <div class="menu-summary-label">Total Menu</div>
            <div class="menu-summary-number mt-1">
                <?= count($daftarMenu) ?>
            </div>
            <i class="bi bi-grid text-secondary small"></i>
        </div>
    </div>

    <div class="col-12 col-sm-4">
        <div class="menu-summary h-100">
            <div class="menu-summary-label">Tersedia</div>
            <div class="menu-summary-number mt-1 text-success">
                <?= $jumlahTersedia ?>
            </div>
            <i class="bi bi-check-circle text-success small"></i>
        </div>
    </div>

    <div class="col-12 col-sm-4">
        <div class="menu-summary h-100">
            <div class="menu-summary-label">Habis / Nonaktif</div>
            <div class="menu-summary-number mt-1 text-secondary">
                <?= $jumlahHabis + $jumlahNonaktif ?>
            </div>
            <i class="bi bi-exclamation-circle text-secondary small"></i>
        </div>
    </div>
</section>

    <!-- FORM TAMBAH / EDIT -->
    <section class="card menu-card mb-4">

        <div class="card-header d-flex align-items-center justify-content-between gap-2">
            <div>
                <h2 class="h5 mb-1">
                    <i class="bi <?= $menuEdit ? 'bi-pencil-square' : 'bi-plus-circle' ?> me-2"
                       style="color:#b47a00;"></i>

                    <?= $menuEdit ? 'Edit Menu' : 'Tambah Menu Baru' ?>
                </h2>

                <small class="text-secondary fw-normal">
                    <?= $menuEdit
                        ? 'Perbarui informasi menu yang dipilih.'
                        : 'Masukkan informasi makanan atau minuman baru.' ?>
                </small>
            </div>

            <?php if ($menuEdit): ?>
                <a href="menu.php"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i>
                    Batal
                </a>
            <?php endif; ?>
        </div>

        <div class="card-body">

            <form action="../proses/menu.php"
                  method="POST"
                  enctype="multipart/form-data">

                <input type="hidden"
                       name="aksi"
                       value="<?= $menuEdit ? 'edit' : 'tambah' ?>">

                <?php if ($menuEdit): ?>
                    <input type="hidden"
                           name="id_menu"
                           value="<?= (int) $menuEdit['id_menu'] ?>">
                <?php endif; ?>

                <div class="row g-3">

                    <!-- NAMA MENU -->
                    <div class="col-12 col-lg-6">
                        <label for="nama_menu" class="form-label">
                            Nama Menu <span class="text-danger">*</span>
                        </label>

                        <input type="text"
                               class="form-control"
                               id="nama_menu"
                               name="nama_menu"
                               maxlength="150"
                               placeholder="Contoh: Ayam Goreng"
                               required
                               value="<?= e($menuEdit['nama_menu'] ?? '') ?>">
                    </div>

                    <!-- HARGA -->
                    <div class="col-6 col-lg-3">
                        <label for="harga" class="form-label">
                            Harga (Rp) <span class="text-danger">*</span>
                        </label>

                        <input type="number"
                               class="form-control"
                               id="harga"
                               name="harga"
                               min="0"
                               max="999999999999"
                               step="1"
                               placeholder="15000"
                               required
                               value="<?= e($menuEdit['harga'] ?? '') ?>">
                    </div>

                    <!-- STOK -->
                    <div class="col-6 col-lg-3">
                        <label for="stok" class="form-label">
                            Stok <span class="text-danger">*</span>
                        </label>

                        <input type="number"
                               class="form-control"
                               id="stok"
                               name="stok"
                               min="0"
                               max="4294967295"
                               step="1"
                               required
                               value="<?= e($menuEdit['stok'] ?? '0') ?>">
                    </div>

                    <!-- DESKRIPSI -->
                    <div class="col-12">
                        <label for="deskripsi" class="form-label">
                            Deskripsi Menu
                        </label>

                        <textarea class="form-control"
                                  id="deskripsi"
                                  name="deskripsi"
                                  rows="3"
                                  placeholder="Tuliskan deskripsi singkat menu..."><?= e($menuEdit['deskripsi'] ?? '') ?></textarea>
                    </div>

                    <!-- FOTO -->
                    <div class="col-12 col-lg-6">
                        <label for="foto" class="form-label">
                            Foto Menu
                        </label>

                        <input type="file"
                               class="form-control"
                               id="foto"
                               name="foto"
                               accept="image/jpeg,image/png,image/webp">

                        <div class="form-text">
                            JPG, PNG, atau WebP. Maksimal 2 MB.
                            <?php if ($menuEdit): ?>
                                Foto boleh dikosongkan jika tidak ingin menggantinya.
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- FOTO SAAT INI -->
                    <?php if ($menuEdit && !empty($menuEdit['foto'])): ?>
                        <div class="col-12 col-lg-6">
                            <label class="form-label d-block">
                                Foto Saat Ini
                            </label>

                            <img src="<?= e(url($menuEdit['foto'])) ?>"
                                 alt="<?= e($menuEdit['nama_menu']) ?>"
                                 class="menu-preview">
                        </div>
                    <?php endif; ?>

                    <!-- KETERSEDIAAN -->
                    <div class="col-12">
                        <div class="menu-form-section">
                            <div class="form-check">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="tersedia"
                                       id="tersedia"
                                       value="1"
                                       <?= !$menuEdit || (int) $menuEdit['tersedia'] === 1
                                           ? 'checked'
                                           : '' ?>>

                                <label class="form-check-label fw-semibold"
                                       for="tersedia">
                                    Menu tersedia untuk dipesan
                                </label>
                            </div>

                            <small class="text-secondary ms-4">
                                Hilangkan centang jika menu ingin disembunyikan dari katalog.
                            </small>
                        </div>
                    </div>

                    <!-- TOMBOL -->
                    <div class="col-12 d-flex flex-wrap gap-2">

                        <button type="submit" class="btn btn-warung px-4">
                            <i class="bi <?= $menuEdit ? 'bi-save' : 'bi-plus-circle' ?> me-1"></i>

                            <?= $menuEdit ? 'Simpan Perubahan' : 'Tambah Menu' ?>
                        </button>

                        <?php if ($menuEdit): ?>
                            <a href="menu.php"
                               class="btn btn-light border px-4">
                                Batal
                            </a>
                        <?php endif; ?>

                    </div>

                </div>
            </form>
        </div>
    </section>

    <!-- DAFTAR MENU -->
    <section class="card menu-card">

        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">

            <div>
                <h2 class="h5 mb-1">
                    <i class="bi bi-list-ul me-2" style="color:#b47a00;"></i>
                    Daftar Menu
                </h2>

                <small class="text-secondary fw-normal">
                    Semua menu yang tercatat dalam sistem.
                </small>
            </div>

            <span class="badge rounded-pill"
                  style="background:#fff0c2;color:#76540b;">
                <?= count($daftarMenu) ?> Menu
            </span>

        </div>

        <div class="card-body p-0">

            <?php if (!$daftarMenu): ?>

                <div class="text-center py-5 px-3">
                    <i class="bi bi-egg-fried display-4 text-secondary"></i>

                    <h3 class="h6 fw-bold mt-3">
                        Belum Ada Menu
                    </h3>

                    <p class="text-secondary small mb-0">
                        Tambahkan menu pertama menggunakan formulir di atas.
                    </p>
                </div>

            <?php else: ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">

                        <thead>
                            <tr>
                                <th class="ps-3">Foto</th>
                                <th>Nama Menu</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php foreach ($daftarMenu as $item): ?>

                            <?php
                            if ((int) $item['tersedia'] !== 1) {
                                $statusLabel = 'Nonaktif';
                                $statusClass = 'text-bg-secondary';
                            } elseif ((int) $item['stok'] <= 0) {
                                $statusLabel = 'Stok Habis';
                                $statusClass = 'text-bg-warning';
                            } else {
                                $statusLabel = 'Tersedia';
                                $statusClass = 'text-bg-success';
                            }
                            ?>

                            <tr>

                                <!-- FOTO -->
                                <td class="ps-3">
                                    <?php if (!empty($item['foto'])): ?>

                                        <img src="<?= e(url($item['foto'])) ?>"
                                             alt="<?= e($item['nama_menu']) ?>"
                                             loading="lazy"
                                             class="menu-photo">

                                    <?php else: ?>

                                        <div class="menu-photo-placeholder">
                                            <i class="bi bi-image"></i>
                                        </div>

                                    <?php endif; ?>
                                </td>

                                <!-- NAMA -->
                                <td>
                                    <div class="menu-name">
                                        <?= e($item['nama_menu']) ?>
                                    </div>

                                    <?php if (!empty($item['deskripsi'])): ?>
                                        <div class="menu-description mt-1">
                                            <?= e($item['deskripsi']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- HARGA -->
                                <td class="text-nowrap fw-semibold">
                                    <?= rupiah($item['harga']) ?>
                                </td>

                                <!-- STOK -->
                                <td>
                                    <?= number_format((int) $item['stok'], 0, ',', '.') ?>
                                </td>

                                <!-- STATUS -->
                                <td>
                                    <span class="badge <?= e($statusClass) ?>">
                                        <?= e($statusLabel) ?>
                                    </span>
                                </td>

                                <!-- AKSI -->
                                <td class="text-end pe-3">
                                    <div class="menu-actions">

                                        <a href="menu.php?edit=<?= (int) $item['id_menu'] ?>"
                                           class="btn btn-sm btn-warung">
                                            <i class="bi bi-pencil me-1"></i>
                                            Edit
                                        </a>

                                        <form action="../proses/menu.php"
                                              method="POST"
                                              onsubmit="return confirm('Yakin ingin menghapus menu <?= e($item['nama_menu']) ?>?')">

                                            <input type="hidden"
                                                   name="aksi"
                                                   value="hapus">

                                            <input type="hidden"
                                                   name="id_menu"
                                                   value="<?= (int) $item['id_menu'] ?>">

                                            <button type="submit"
                                                    class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash me-1"></i>
                                                Hapus
                                            </button>

                                        </form>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

        </div>
    </section>

</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
