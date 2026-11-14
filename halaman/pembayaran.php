<?php
require_once __DIR__ . '/../config/aplikasi.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fungsi.php';

if (
    !isset($_SESSION['pengguna_id']) ||
    ($_SESSION['pengguna_peran'] ?? '') !== 'admin'
) {
    header('Location: ' . URL_DASAR . '/login.php');
    exit;
}

$status_valid = [
    'belum_bayar',
    'menunggu_verifikasi',
    'lunas',
    'ditolak',
    'dikembalikan',
];

$filter = $_GET['status'] ?? '';

if (!in_array($filter, $status_valid, true)) {
    $filter = '';
}

$sql = "
    SELECT
        b.id_pembayaran,
        b.id_pesanan,
        b.metode,
        b.jumlah_bayar,
        b.status_pembayaran,
        b.waktu_bayar,
        p.kode_pesanan,
        p.nama_penerima,
        p.no_telepon,
        p.total_harga,
        p.status_pesanan
    FROM pembayaran b
    INNER JOIN pesanan p ON p.id_pesanan = b.id_pesanan
";

if ($filter !== '') {
    $sql .= " WHERE b.status_pembayaran = :status";
}

$sql .= " ORDER BY b.id_pembayaran DESC";

$stmt = $pdo->prepare($sql);

if ($filter !== '') {
    $stmt->bindValue(':status', $filter);
}

$stmt->execute();
$pembayaran = $stmt->fetchAll(PDO::FETCH_ASSOC);

$jumlah_status = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(status_pembayaran = 'belum_bayar') AS belum_bayar,
        SUM(status_pembayaran = 'menunggu_verifikasi') AS menunggu_verifikasi,
        SUM(status_pembayaran = 'lunas') AS lunas
    FROM pembayaran
")->fetch(PDO::FETCH_ASSOC);

function labelStatusPembayaran(string $status): string
{
    return match ($status) {
        'belum_bayar' => 'Belum Bayar',
        'menunggu_verifikasi' => 'Menunggu Verifikasi',
        'lunas' => 'Lunas',
        'ditolak' => 'Ditolak',
        'dikembalikan' => 'Dikembalikan',
        default => ucfirst($status),
    };
}

function warnaStatusPembayaran(string $status): string
{
    return match ($status) {
        'belum_bayar' => 'secondary',
        'menunggu_verifikasi' => 'warning',
        'lunas' => 'success',
        'ditolak' => 'danger',
        'dikembalikan' => 'info',
        default => 'secondary',
    };
}

// Memanggil kerangka utama admin
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>

<head>
    <link rel="stylesheet" type="text/css" href="/../aset/css/halaman.css">
</head>

<main class="container-fluid page-content px-3 px-lg-4">

    <!-- Header -->
<section class="pembayaran-heading mb-4">
    <div class="row align-items-center g-3">
        <div class="col-md">
            <div class="small fw-semibold mb-2 text-warning-emphasis">
                <i class="bi bi-shop me-1"></i>
                WARUNG BU-IING
            </div>

            <h1 class="h3 mb-2">Manajemen Pembayaran</h1>

            <p class="mb-0">
                Pantau transaksi dan kelola status pembayaran pelanggan.
            </p>
        </div>

        <div class="col-md-auto">
            <div class="pembayaran-total">
                <div class="small">Total Transaksi</div>
                <div class="fs-3 fw-bold">
                    <?= (int) ($jumlah_status['total'] ?? 0) ?>
                </div>
            </div>
        </div>
    </div>
</section>

    <!-- Notifikasi -->
    <?php if (!empty($_SESSION['pesan_sukses'])): ?>
        <div class="alert alert-success alert-dismissible fade show alert-pembayaran">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= e($_SESSION['pesan_sukses']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['pesan_sukses']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['pesan_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show alert-pembayaran">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            <?= e($_SESSION['pesan_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['pesan_error']); ?>
    <?php endif; ?>

    <!-- Ringkasan -->
    <div class="row g-3 mb-4">

        <div class="col-6 col-lg-3">
            <div class="pembayaran-stat">
                <div class="stat-icon bg-warning-subtle text-warning">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="small text-secondary">Total Pembayaran</div>
                <div class="stat-number">
                    <?= (int) ($jumlah_status['total'] ?? 0) ?>
                </div>
                <div class="small text-secondary">Semua transaksi</div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="pembayaran-stat">
                <div class="stat-icon bg-secondary-subtle text-secondary">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div class="small text-secondary">Belum Bayar</div>
                <div class="stat-number">
                    <?= (int) ($jumlah_status['belum_bayar'] ?? 0) ?>
                </div>
                <div class="small text-secondary">Menunggu pembayaran</div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="pembayaran-stat">
                <div class="stat-icon bg-warning-subtle text-warning">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="small text-secondary">Menunggu Verifikasi</div>
                <div class="stat-number">
                    <?= (int) ($jumlah_status['menunggu_verifikasi'] ?? 0) ?>
                </div>
                <div class="small text-secondary">Perlu diperiksa</div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="pembayaran-stat">
                <div class="stat-icon bg-success-subtle text-success">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="small text-secondary">Lunas</div>
                <div class="stat-number">
                    <?= (int) ($jumlah_status['lunas'] ?? 0) ?>
                </div>
                <div class="small text-secondary">Pembayaran selesai</div>
            </div>
        </div>

    </div>

    <!-- Daftar Pembayaran -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h5 laporan-section-title mb-1">
            <i class="bi bi-credit-card me-2"></i>
            Daftar Pembayaran
        </h2>
        <p class="text-secondary small mb-0">
            Periksa transaksi dan perbarui status pembayaran.
        </p>
    </div>

    <form method="GET" class="d-flex gap-2 pembayaran-filter">
        <select name="status" class="form-select">
            <option value="">Semua Status</option>

            <?php foreach ($status_valid as $item_status): ?>
                <option
                    value="<?= e($item_status) ?>"
                    <?= $filter === $item_status ? 'selected' : '' ?>
                >
                    <?= e(labelStatusPembayaran($item_status)) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button class="btn btn-pembayaran" type="submit">
            <i class="bi bi-funnel me-1"></i>
            Filter
        </button>

        <?php if ($filter !== ''): ?>
            <a href="pembayaran.php"
               class="btn btn-outline-secondary"
               title="Reset filter">
                <i class="bi bi-arrow-counterclockwise"></i>
            </a>
        <?php endif; ?>
    </form>
</div>
</main>

<!-- Modal diletakkan di luar tabel -->
<?php foreach ($pembayaran as $item): ?>
    <div
        class="modal fade"
        id="modalPembayaran<?= (int) $item['id_pembayaran'] ?>"
        tabindex="-1"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <form
                    method="POST"
                    action="<?= e(URL_DASAR) ?>/proses/status_pembayaran.php"
                >
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title fs-5">Kelola Pembayaran</h2>
                            <div class="small text-secondary">
                                <?= e($item['kode_pesanan']) ?>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                        ></button>
                    </div>

                    <div class="modal-body">

                        <input
                            type="hidden"
                            name="id_pembayaran"
                            value="<?= (int) $item['id_pembayaran'] ?>"
                        >

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pelanggan</label>
                            <input
                                class="form-control"
                                value="<?= e($item['nama_penerima']) ?>"
                                readonly
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Total Tagihan</label>
                            <input
                                class="form-control fw-bold"
                                value="<?= e(rupiah($item['total_harga'])) ?>"
                                readonly
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Metode Pembayaran
                            </label>

                            <select name="metode" class="form-select" required>
                                <option value="tunai" <?= $item['metode'] === 'tunai' ? 'selected' : '' ?>>
                                    Tunai
                                </option>
                                <option value="transfer" <?= $item['metode'] === 'transfer' ? 'selected' : '' ?>>
                                    Transfer
                                </option>
                                <option value="qris" <?= $item['metode'] === 'qris' ? 'selected' : '' ?>>
                                    QRIS
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Status Pembayaran
                            </label>

                            <select name="status_pembayaran" class="form-select" required>
                                <?php foreach ($status_valid as $item_status): ?>
                                    <option
                                        value="<?= e($item_status) ?>"
                                        <?= $item['status_pembayaran'] === $item_status ? 'selected' : '' ?>
                                    >
                                        <?= e(labelStatusPembayaran($item_status)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="alert alert-info small mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Pilih status Lunas hanya setelah pembayaran benar-benar diterima atau diverifikasi.
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal"
                        >
                            Batal
                        </button>

                        <button type="submit" class="btn btn-pembayaran">
                            <i class="bi bi-save me-1"></i>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
<?php endforeach; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
