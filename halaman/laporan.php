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

// Ringkasan pesanan selesai berdasarkan tanggal pesanan dibuat.
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS jumlah_pesanan,
        COALESCE(SUM(total_harga), 0) AS total_omzet
    FROM pesanan
    WHERE status_pesanan = 'selesai'
      AND dibuat_pada >= ?
      AND dibuat_pada < DATE_ADD(?, INTERVAL 1 DAY)
");
$stmt->execute([$tanggal_awal, $tanggal_akhir]);
$ringkasan = $stmt->fetch(PDO::FETCH_ASSOC);

$jumlah_pesanan = (int) ($ringkasan['jumlah_pesanan'] ?? 0);
$total_omzet = (float) ($ringkasan['total_omzet'] ?? 0);

// Pembayaran lunas berdasarkan waktu pembayaran.
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(jumlah_bayar), 0)
    FROM pembayaran
    WHERE status_pembayaran = 'lunas'
      AND waktu_bayar >= ?
      AND waktu_bayar < DATE_ADD(?, INTERVAL 1 DAY)
");
$stmt->execute([$tanggal_awal, $tanggal_akhir]);
$total_pembayaran = (float) $stmt->fetchColumn();

// Pengembalian dana berdasarkan waktu refund.
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(jumlah_bayar), 0)
    FROM pembayaran
    WHERE status_pembayaran = 'dikembalikan'
      AND waktu_pengembalian >= ?
      AND waktu_pengembalian < DATE_ADD(?, INTERVAL 1 DAY)
");
$stmt->execute([$tanggal_awal, $tanggal_akhir]);
$total_refund = (float) $stmt->fetchColumn();

// Pemasukan manual.
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(jumlah), 0)
    FROM transaksi_keuangan
    WHERE jenis_transaksi = 'pemasukan'
      AND id_pesanan IS NULL
      AND tanggal_transaksi >= ?
      AND tanggal_transaksi < DATE_ADD(?, INTERVAL 1 DAY)
");
$stmt->execute([$tanggal_awal, $tanggal_akhir]);
$pemasukan_manual = (float) $stmt->fetchColumn();

// Pengeluaran manual.
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(jumlah), 0)
    FROM transaksi_keuangan
    WHERE jenis_transaksi = 'pengeluaran'
      AND id_pesanan IS NULL
      AND tanggal_transaksi >= ?
      AND tanggal_transaksi < DATE_ADD(?, INTERVAL 1 DAY)
");
$stmt->execute([$tanggal_awal, $tanggal_akhir]);
$pengeluaran_manual = (float) $stmt->fetchColumn();

// Rincian pesanan selesai.
$stmt = $pdo->prepare("
    SELECT
        kode_pesanan,
        nama_penerima,
        jenis_pesanan,
        total_harga,
        dibuat_pada
    FROM pesanan
    WHERE status_pesanan = 'selesai'
      AND dibuat_pada >= ?
      AND dibuat_pada < DATE_ADD(?, INTERVAL 1 DAY)
    ORDER BY dibuat_pada DESC
");
$stmt->execute([$tanggal_awal, $tanggal_akhir]);
$transaksi = $stmt->fetchAll(PDO::FETCH_ASSOC);

function tanggalIndonesia(string $tanggal): string
{
    return date('d/m/Y H:i', strtotime($tanggal));
}

$judul_halaman = 'Laporan';

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>

<head>
    <link rel="stylesheet" type="text/css" href="/../aset/css/halaman.css">
</head>

<main class="container-fluid page-content px-3 px-lg-4" id="area-laporan">

    <!-- Header -->
    <div class="laporan-heading mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md">
                <div class="small fw-semibold mb-2 text-warning-emphasis">
                    <i class="bi bi-shop me-1"></i>
                    WARUNG BU-IING
                </div>

                <h1 class="h3 mb-2">Laporan Penjualan dan Keuangan</h1>
                <p class="mb-0">
                    Pantau ringkasan penjualan, pembayaran, refund, dan operasional warung.
                </p>
            </div>

            <div class="col-md-auto no-print">
                <button type="button" class="btn btn-laporan"
                        onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>
                    Cetak / Simpan PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Filter Periode -->
    <div class="laporan-card mb-4 no-print">
        <div class="card-body">
            <div class="mb-3">
                <h2 class="h5 laporan-section-title mb-1">
                    <i class="bi bi-calendar-range me-2"></i>
                    Filter Periode Laporan
                </h2>
                <p class="small text-secondary mb-0">
                    Pilih rentang tanggal untuk menampilkan laporan.
                </p>
            </div>

            <form method="get" class="row g-3 align-items-end laporan-filter">
                <div class="col-md-4">
                    <label for="tanggal_awal" class="form-label fw-semibold">
                        Tanggal Awal
                    </label>
                    <input type="date" class="form-control"
                           id="tanggal_awal" name="tanggal_awal"
                           value="<?= e($tanggal_awal) ?>" required>
                </div>

                <div class="col-md-4">
                    <label for="tanggal_akhir" class="form-label fw-semibold">
                        Tanggal Akhir
                    </label>
                    <input type="date" class="form-control"
                           id="tanggal_akhir" name="tanggal_akhir"
                           value="<?= e($tanggal_akhir) ?>" required>
                </div>

                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-laporan flex-grow-1">
                        <i class="bi bi-funnel me-1"></i>
                        Tampilkan
                    </button>

                    <a href="<?= e(url('halaman/laporan.php')) ?>"
                       class="btn btn-outline-secondary"
                       title="Reset filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Informasi Periode -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <span class="text-secondary small">Periode laporan:</span>
            <span class="fw-semibold">
                <?= e(date('d/m/Y', strtotime($tanggal_awal))) ?>
                –
                <?= e(date('d/m/Y', strtotime($tanggal_akhir))) ?>
            </span>
        </div>

        <div class="small text-secondary">
            <i class="bi bi-clock me-1"></i>
            Dicetak pada <?= date('d/m/Y H:i') ?> WIB
        </div>
    </div>

    <!-- Ringkasan Penjualan -->
    <section class="mb-4">
        <h2 class="h5 laporan-section-title mb-3">
            <i class="bi bi-receipt me-2"></i>
            Ringkasan Penjualan
        </h2>

        <div class="row g-3">
            <div class="col-6 col-xl-6">
                <div class="laporan-card h-100">
                    <div class="card-body">
                        <div class="laporan-stat-icon bg-warning-subtle text-warning">
                            <i class="bi bi-bag-check"></i>
                        </div>

                        <div class="small text-secondary">Pesanan Selesai</div>
                        <div class="laporan-stat-number">
                            <?= number_format($jumlah_pesanan, 0, ',', '.') ?>
                        </div>
                        <div class="small text-secondary mt-1">
                            Total pesanan berstatus selesai
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-xl-6">
                <div class="laporan-card h-100">
                    <div class="card-body">
                        <div class="laporan-stat-icon bg-success-subtle text-success">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>

                        <div class="small text-secondary">Omzet Pesanan Selesai</div>
                        <div class="laporan-stat-number text-success">
                            <?= rupiah($total_omzet) ?>
                        </div>
                        <div class="small text-secondary mt-1">
                            Nilai pesanan yang telah selesai
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Ringkasan Keuangan -->
    <section class="mb-4">
        <h2 class="h5 laporan-section-title mb-3">
            <i class="bi bi-wallet2 me-2"></i>
            Ringkasan Keuangan
        </h2>

        <div class="row g-3">
            <div class="col-6 col-xl-3">
                <div class="laporan-card h-100">
                    <div class="card-body">
                        <div class="laporan-stat-icon bg-success-subtle text-success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="small text-secondary">Pembayaran Lunas</div>
                        <div class="laporan-stat-number text-success">
                            <?= rupiah($total_pembayaran) ?>
                        </div>
                        <div class="small text-secondary mt-1">
                            Berdasarkan waktu pembayaran
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-xl-3">
                <div class="laporan-card h-100">
                    <div class="card-body">
                        <div class="laporan-stat-icon bg-danger-subtle text-danger">
                            <i class="bi bi-arrow-return-left"></i>
                        </div>
                        <div class="small text-secondary">Pengembalian Dana</div>
                        <div class="laporan-stat-number text-danger">
                            <?= rupiah($total_refund) ?>
                        </div>
                        <div class="small text-secondary mt-1">
                            Berdasarkan waktu refund
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-xl-3">
                <div class="laporan-card h-100">
                    <div class="card-body">
                        <div class="laporan-stat-icon bg-primary-subtle text-primary">
                            <i class="bi bi-arrow-down-left-circle"></i>
                        </div>
                        <div class="small text-secondary">Pemasukan Manual</div>
                        <div class="laporan-stat-number">
                            <?= rupiah($pemasukan_manual) ?>
                        </div>
                        <div class="small text-secondary mt-1">
                            Pemasukan di luar pesanan
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-xl-3">
                <div class="laporan-card h-100">
                    <div class="card-body">
                        <div class="laporan-stat-icon bg-warning-subtle text-warning">
                            <i class="bi bi-arrow-up-right-circle"></i>
                        </div>
                        <div class="small text-secondary">Pengeluaran Manual</div>
                        <div class="laporan-stat-number text-danger">
                            <?= rupiah($pengeluaran_manual) ?>
                        </div>
                        <div class="small text-secondary mt-1">
                            Bahan baku dan operasional
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Rincian Pesanan -->
    <div class="laporan-card mb-4">
        <div class="card-body">
            <div class="mb-4">
                <h2 class="h5 laporan-section-title mb-1">
                    <i class="bi bi-list-ul me-2"></i>
                    Rincian Pesanan Selesai
                </h2>
                <p class="small text-secondary mb-0">
                    Daftar pesanan yang telah selesai pada periode laporan.
                </p>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle laporan-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Tanggal Pesanan</th>
                            <th>Kode Pesanan</th>
                            <th>Nama Penerima</th>
                            <th>Jenis Pesanan</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!$transaksi): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="bi bi-clipboard-data fs-1 text-secondary"></i>
                                    <h3 class="h6 fw-bold mt-3">
                                        Belum Ada Data Penjualan
                                    </h3>
                                    <p class="text-secondary small mb-0">
                                        Tidak ada pesanan selesai pada periode ini.
                                    </p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transaksi as $i => $item): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>

                                    <td class="text-nowrap">
                                        <?= e(tanggalIndonesia($item['dibuat_pada'])) ?>
                                    </td>

                                    <td class="fw-semibold laporan-kode">
                                        <?= e($item['kode_pesanan']) ?>
                                    </td>

                                    <td><?= e($item['nama_penerima']) ?></td>

                                    <td>
                                        <?php if ($item['jenis_pesanan'] === 'diantar'): ?>
                                            <span class="badge text-bg-info">
                                                <i class="bi bi-truck me-1"></i>
                                                Diantar
                                            </span>
                                        <?php else: ?>
                                            <span class="badge text-bg-secondary">
                                                <i class="bi bi-shop me-1"></i>
                                                Ambil Sendiri
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-end fw-bold text-nowrap">
                                        <?= rupiah($item['total_harga']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>

                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="5" class="text-end">Total Omzet</td>
                            <td class="text-end text-success text-nowrap">
                                <?= rupiah($total_omzet) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Catatan Laporan -->
    <div class="laporan-catatan mb-4">
        <div class="d-flex gap-2">
            <i class="bi bi-info-circle-fill mt-1"></i>
            <div>
                <div class="fw-bold mb-1">Catatan Laporan</div>
                <div class="small">
                    Omzet pesanan selesai, pembayaran lunas, pengembalian dana,
                    pemasukan manual, dan pengeluaran manual merupakan indikator
                    yang berbeda. Angka-angka tersebut tidak boleh langsung
                    dijumlahkan sebagai satu total pendapatan.
                </div>
            </div>
        </div>
    </div>

</main>


<?php require __DIR__ . '/../includes/footer.php'; ?>
