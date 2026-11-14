<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/aplikasi.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fungsi.php';

// Pastikan hanya admin yang dapat mengakses halaman ini.
if (
    !isset($_SESSION['pengguna_id']) ||
    ($_SESSION['pengguna_peran'] ?? '') !== 'admin'
) {
    header('Location: ' . URL_DASAR . '/login.php');
    exit;
}

$tanggal_awal = $_GET['tanggal_awal'] ?? date('Y-m-01');
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');
$jenis_filter = $_GET['jenis'] ?? '';

$validTanggal = static function ($tanggal): bool {
    if (!is_string($tanggal)) {
        return false;
    }

    $hasil = DateTime::createFromFormat('!Y-m-d', $tanggal);

    return $hasil !== false
        && $hasil->format('Y-m-d') === $tanggal;
};

if (
    !$validTanggal($tanggal_awal) ||
    !$validTanggal($tanggal_akhir) ||
    $tanggal_awal > $tanggal_akhir
) {
    $tanggal_awal = date('Y-m-01');
    $tanggal_akhir = date('Y-m-d');
}

if (!in_array($jenis_filter, ['', 'pemasukan', 'pengeluaran'], true)) {
    $jenis_filter = '';
}

// Ringkasan transaksi manual.
$stmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(
            CASE WHEN jenis_transaksi = 'pemasukan'
                 THEN jumlah ELSE 0 END
        ), 0) AS pemasukan,
        COALESCE(SUM(
            CASE WHEN jenis_transaksi = 'pengeluaran'
                 THEN jumlah ELSE 0 END
        ), 0) AS pengeluaran,
        COUNT(*) AS jumlah_transaksi
    FROM transaksi_keuangan
    WHERE tanggal_transaksi >= ?
      AND tanggal_transaksi < DATE_ADD(?, INTERVAL 1 DAY)
      AND id_pesanan IS NULL
");
$stmt->execute([$tanggal_awal, $tanggal_akhir]);
$ringkasan = $stmt->fetch(PDO::FETCH_ASSOC);

$pemasukan = (float) $ringkasan['pemasukan'];
$pengeluaran = (float) $ringkasan['pengeluaran'];
$saldo = $pemasukan - $pengeluaran;

// Ambil daftar transaksi manual.
$sql = "
    SELECT
        id_transaksi,
        jenis_transaksi,
        kategori,
        keterangan,
        jumlah,
        tanggal_transaksi
    FROM transaksi_keuangan
    WHERE tanggal_transaksi >= ?
      AND tanggal_transaksi < DATE_ADD(?, INTERVAL 1 DAY)
      AND id_pesanan IS NULL
";

$params = [$tanggal_awal, $tanggal_akhir];

if ($jenis_filter !== '') {
    $sql .= ' AND jenis_transaksi = ?';
    $params[] = $jenis_filter;
}

$sql .= ' ORDER BY tanggal_transaksi DESC, id_transaksi DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transaksi = $stmt->fetchAll(PDO::FETCH_ASSOC);

$jumlah_transaksi = (int) ($ringkasan['jumlah_transaksi'] ?? 0);

$judul_halaman = 'Keuangan';

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>

<head>
    <link rel="stylesheet" type="text/css" href="/../aset/css/halaman.css">
</head>

<main class="container-fluid page-content px-3 px-lg-4">

    <!-- HEADER HALAMAN -->
    <section class="keuangan-heading mb-4">
        <div class="row align-items-center g-3">

            <div class="col-md-8">
                <div class="small fw-bold mb-2" style="color: #98700e;">
                    <i class="bi bi-shop me-1"></i>
                    PANEL PENGELOLA WARUNG
                </div>

                <h1 class="h3 mb-2">
                    Manajemen Keuangan
                </h1>

                <p class="text-secondary mb-2">
                    Catat dan pantau pemasukan serta pengeluaran operasional warung.
                </p>

                <div class="dashboard-date">
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= date('d-m-Y') ?>
                </div>
            </div>

            <div class="col-md-4 text-md-end">
                <div class="small text-secondary">Jumlah Transaksi</div>

                <div class="fs-3 fw-bold" style="color: #79552c;">
                    <?= $jumlah_transaksi ?>
                </div>

                <div class="small text-secondary">
                    Pada periode terpilih
                </div>
            </div>

        </div>
    </section>


    <!-- NOTIFIKASI -->
    <?php if (!empty($_SESSION['pesan_sukses'])): ?>
        <div class="alert alert-success alert-dismissible fade show keuangan-alert mb-4">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= e($_SESSION['pesan_sukses']) ?>

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Tutup">
            </button>
        </div>

        <?php unset($_SESSION['pesan_sukses']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['pesan_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show keuangan-alert mb-4">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            <?= e($_SESSION['pesan_error']) ?>

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Tutup">
            </button>
        </div>

        <?php unset($_SESSION['pesan_error']); ?>
    <?php endif; ?>


    <!-- RINGKASAN KEUANGAN -->
    <section class="mb-4">

        <div class="mb-3">
            <h2 class="dashboard-section-title mb-1">
                Ringkasan Keuangan
            </h2>

            <p class="text-secondary small mb-0">
                Informasi pemasukan dan pengeluaran transaksi manual.
            </p>
        </div>

        <div class="row g-3">

            <!-- PEMASUKAN -->
            <div class="col-6 col-xl-4">
                <div class="card dashboard-stat keuangan-stat">

                    <div class="stat-icon green">
                        <i class="bi bi-arrow-down-left-circle"></i>
                    </div>

                    <div class="stat-label">
                        Pemasukan Manual
                    </div>

                    <div class="stat-value text-success">
                        <?= rupiah($pemasukan) ?>
                    </div>

                    <div class="small text-secondary mt-2">
                        Total uang masuk
                    </div>

                </div>
            </div>


            <!-- PENGELUARAN -->
            <div class="col-6 col-xl-4">
                <div class="card dashboard-stat keuangan-stat">

                    <div class="stat-icon orange">
                        <i class="bi bi-arrow-up-right-circle"></i>
                    </div>

                    <div class="stat-label">
                        Pengeluaran Manual
                    </div>

                    <div class="stat-value text-danger">
                        <?= rupiah($pengeluaran) ?>
                    </div>

                    <div class="small text-secondary mt-2">
                        Total uang keluar
                    </div>

                </div>
            </div>


            <!-- SELISIH -->
            <div class="col-12 col-xl-4">
                <div class="card dashboard-stat keuangan-stat">

                    <div class="stat-icon yellow">
                        <i class="bi bi-wallet2"></i>
                    </div>

                    <div class="stat-label">
                        Selisih Pemasukan dan Pengeluaran
                    </div>

                    <div class="stat-value <?= $saldo < 0 ? 'text-danger' : 'text-primary' ?>">
                        <?= rupiah($saldo) ?>
                    </div>

                    <div class="small text-secondary mt-2">
                        Pemasukan dikurangi pengeluaran
                    </div>

                </div>
            </div>

        </div>
    </section>


    <!-- FORM TAMBAH TRANSAKSI -->
    <section class="card keuangan-card mb-4">

        <div class="card-header d-flex align-items-center gap-3 flex-wrap">

            <div class="quick-action-icon">
                <i class="bi bi-plus-circle"></i>
            </div>

            <div>
                <h2 class="dashboard-section-title mb-1">
                    Tambah Transaksi
                </h2>

                <p class="text-secondary small mb-0">
                    Masukkan catatan pemasukan atau pengeluaran warung.
                </p>
            </div>

        </div>

        <div class="card-body">

            <form method="post"
                  action="<?= e(url('proses/simpan_keuangan.php')) ?>"
                  class="keuangan-form">

                <div class="row g-3">

                    <div class="col-md-4">
                        <label for="jenis_transaksi" class="form-label fw-semibold">
                            Jenis Transaksi
                        </label>

                        <select class="form-select"
                                id="jenis_transaksi"
                                name="jenis_transaksi"
                                required>

                            <option value="">Pilih jenis transaksi</option>
                            <option value="pemasukan">Pemasukan</option>
                            <option value="pengeluaran">Pengeluaran</option>

                        </select>
                    </div>


                    <div class="col-md-4">
                        <label for="kategori" class="form-label fw-semibold">
                            Kategori
                        </label>

                        <select class="form-select"
                                id="kategori"
                                name="kategori"
                                required>

                            <option value="">Pilih kategori</option>
                            <option value="Penjualan lain">Penjualan lain</option>
                            <option value="Modal tambahan">Modal tambahan</option>
                            <option value="Bahan baku">Bahan baku</option>
                            <option value="Gas">Gas</option>
                            <option value="Listrik dan air">Listrik dan air</option>
                            <option value="Transportasi">Transportasi</option>
                            <option value="Peralatan">Peralatan</option>
                            <option value="Operasional">Operasional</option>
                            <option value="Lain-lain">Lain-lain</option>

                        </select>
                    </div>


                    <div class="col-md-4">
                        <label for="jumlah" class="form-label fw-semibold">
                            Jumlah (Rp)
                        </label>

                        <input type="number"
                               class="form-control"
                               id="jumlah"
                               name="jumlah"
                               min="1"
                               max="999999999999"
                               step="1"
                               placeholder="Contoh: 50000"
                               required>
                    </div>


                    <div class="col-md-6">
                        <label for="tanggal_transaksi" class="form-label fw-semibold">
                            Tanggal Transaksi
                        </label>

                        <input type="datetime-local"
                               class="form-control"
                               id="tanggal_transaksi"
                               name="tanggal_transaksi"
                               value="<?= date('Y-m-d\TH:i') ?>"
                               required>
                    </div>


                    <div class="col-md-6">
                        <label for="keterangan" class="form-label fw-semibold">
                            Keterangan
                            <span class="text-secondary fw-normal">(Opsional)</span>
                        </label>

                        <input type="text"
                               class="form-control"
                               id="keterangan"
                               name="keterangan"
                               maxlength="2000"
                               placeholder="Contoh: membeli bahan baku untuk besok">
                    </div>


                    <div class="col-12">
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-keuangan px-4">
                                <i class="bi bi-save me-1"></i>
                                Simpan Transaksi
                            </button>
                        </div>
                    </div>

                </div>
            </form>

        </div>
    </section>


    <!-- RIWAYAT TRANSAKSI -->
    <section class="card keuangan-card mb-4">

        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">

            <div>
                <h2 class="dashboard-section-title mb-1">
                    Riwayat Transaksi
                </h2>

                <p class="text-secondary small mb-0">
                    Daftar pemasukan dan pengeluaran berdasarkan periode.
                </p>
            </div>

            <div class="pesanan-count">
                <i class="bi bi-receipt me-1"></i>
                <?= count($transaksi) ?> Transaksi
            </div>

        </div>


        <div class="card-body">

            <!-- FILTER TRANSAKSI -->
            <form method="get"
                  class="row g-3 align-items-end mb-4 keuangan-filter">

                <div class="col-md-3">
                    <label for="tanggal_awal" class="form-label fw-semibold">
                        Tanggal Awal
                    </label>

                    <input type="date"
                           class="form-control"
                           id="tanggal_awal"
                           name="tanggal_awal"
                           value="<?= e($tanggal_awal) ?>"
                           required>
                </div>


                <div class="col-md-3">
                    <label for="tanggal_akhir" class="form-label fw-semibold">
                        Tanggal Akhir
                    </label>

                    <input type="date"
                           class="form-control"
                           id="tanggal_akhir"
                           name="tanggal_akhir"
                           value="<?= e($tanggal_akhir) ?>"
                           required>
                </div>


                <div class="col-md-3">
                    <label for="jenis" class="form-label fw-semibold">
                        Jenis Transaksi
                    </label>

                    <select class="form-select" id="jenis" name="jenis">
                        <option value="">Semua jenis</option>

                        <option value="pemasukan"
                            <?= $jenis_filter === 'pemasukan' ? 'selected' : '' ?>>
                            Pemasukan
                        </option>

                        <option value="pengeluaran"
                            <?= $jenis_filter === 'pengeluaran' ? 'selected' : '' ?>>
                            Pengeluaran
                        </option>
                    </select>
                </div>


                <div class="col-md-3">
                    <div class="d-flex gap-2">

                        <button type="submit"
                                class="btn btn-keuangan flex-grow-1">
                            <i class="bi bi-funnel me-1"></i>
                            Filter
                        </button>

                        <a href="<?= e(url('halaman/keuangan.php')) ?>"
                           class="btn btn-outline-secondary"
                           title="Reset filter"
                           aria-label="Reset filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>

                    </div>
                </div>

            </form>


            <!-- TABEL TRANSAKSI -->
            <div class="table-responsive">

                <table class="table table-hover align-middle keuangan-table">

                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Kategori</th>
                            <th>Keterangan</th>
                            <th class="text-end">Jumlah</th>
                        </tr>
                    </thead>


                    <tbody>

                        <?php if (!$transaksi): ?>

                            <tr>
                                <td colspan="6" class="text-center py-5">

                                    <i class="bi bi-wallet2 fs-1 text-secondary"></i>

                                    <h3 class="h6 fw-bold mt-3">
                                        Belum Ada Transaksi
                                    </h3>

                                    <p class="text-secondary small mb-0">
                                        Tidak ada transaksi pada periode yang dipilih.
                                    </p>

                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($transaksi as $i => $item): ?>

                                <tr>

                                    <td>
                                        <?= $i + 1 ?>
                                    </td>


                                    <td class="text-nowrap">
                                        <?= e(date(
                                            'd/m/Y',
                                            strtotime($item['tanggal_transaksi'])
                                        )) ?>

                                        <div class="small text-secondary">
                                            <?= e(date(
                                                'H:i',
                                                strtotime($item['tanggal_transaksi'])
                                            )) ?> WIB
                                        </div>
                                    </td>


                                    <td>
                                        <?php if ($item['jenis_transaksi'] === 'pemasukan'): ?>

                                            <span class="badge text-bg-success keuangan-badge">
                                                <i class="bi bi-arrow-down-left me-1"></i>
                                                Pemasukan
                                            </span>

                                        <?php else: ?>

                                            <span class="badge text-bg-danger keuangan-badge">
                                                <i class="bi bi-arrow-up-right me-1"></i>
                                                Pengeluaran
                                            </span>

                                        <?php endif; ?>
                                    </td>


                                    <td class="fw-semibold">
                                        <?= e($item['kategori']) ?>
                                    </td>


                                    <td>
                                        <?= e($item['keterangan'] ?: '-') ?>
                                    </td>


                                    <td class="text-end fw-bold text-nowrap <?= $item['jenis_transaksi'] === 'pemasukan' ? 'text-success' : 'text-danger' ?>">
                                        <?= rupiah($item['jumlah']) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>
                </table>

            </div>

            <div class="small text-secondary mt-3">
                Menampilkan <?= count($transaksi) ?> transaksi.
            </div>

        </div>
    </section>


    <!-- CATATAN -->
    <div class="text-secondary small text-center mt-4 mb-3">
        <i class="bi bi-info-circle me-1"></i>
        Ringkasan ini hanya mencakup transaksi keuangan manual.
    </div>

</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
