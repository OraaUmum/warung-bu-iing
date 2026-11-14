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

$judul_halaman = 'Pesanan';

$status_valid = [
    'menunggu_konfirmasi',
    'dikonfirmasi',
    'diproses',
    'siap_diambil',
    'dikirim',
    'selesai',
    'dibatalkan'
];

$status = $_GET['status'] ?? '';
$cari = trim($_GET['cari'] ?? '');

$sql = "
    SELECT
        p.id_pesanan,
        p.kode_pesanan,
        p.nama_penerima,
        p.no_telepon,
        p.jenis_pesanan,
        p.status_pesanan,
        p.total_harga,
        p.dibuat_pada,
        COALESCE(SUM(dp.jumlah), 0) AS jumlah_item
    FROM pesanan p
    LEFT JOIN detail_pesanan dp
        ON p.id_pesanan = dp.id_pesanan
    WHERE 1 = 1
";

$parameter = [];

if ($cari !== '') {
    $sql .= "
        AND (
            p.kode_pesanan LIKE :cari_kode
            OR p.nama_penerima LIKE :cari_nama
            OR p.no_telepon LIKE :cari_telepon
        )
    ";

    $parameter['cari_kode'] = "%{$cari}%";
    $parameter['cari_nama'] = "%{$cari}%";
    $parameter['cari_telepon'] = "%{$cari}%";
}

if (in_array($status, $status_valid, true)) {
    $sql .= " AND p.status_pesanan = :status";
    $parameter['status'] = $status;
}

$sql .= "
    GROUP BY p.id_pesanan
    ORDER BY p.dibuat_pada DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($parameter);
$pesanan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mengambil informasi lengkap dan daftar item setiap pesanan
$stmtDetailPesanan = $pdo->prepare(
    'SELECT * FROM pesanan WHERE id_pesanan = ?'
);

$stmtItemPesanan = $pdo->prepare(
    'SELECT * FROM detail_pesanan WHERE id_pesanan = ?'
);

$detailPesanan = [];

foreach ($pesanan as $p) {
    $idPesanan = (int) $p['id_pesanan'];

    $stmtDetailPesanan->execute([$idPesanan]);
    $dataPesanan = $stmtDetailPesanan->fetch(PDO::FETCH_ASSOC);

    $stmtItemPesanan->execute([$idPesanan]);
    $dataItem = $stmtItemPesanan->fetchAll(PDO::FETCH_ASSOC);

    $detailPesanan[$idPesanan] = [
        'pesanan' => $dataPesanan,
        'item' => $dataItem
    ];
}
function labelStatusPesanan(string $status): string
{
    return match ($status) {
        'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
        'dikonfirmasi' => 'Dikonfirmasi',
        'diproses' => 'Sedang Diproses',
        'siap_diambil' => 'Siap Diambil',
        'dikirim' => 'Sedang Dikirim',
        'selesai' => 'Selesai',
        'dibatalkan' => 'Dibatalkan',
        default => ucfirst($status),
    };
}

function warnaStatusPesanan(string $status): string
{
    return match ($status) {
        'menunggu_konfirmasi' => 'warning',
        'dikonfirmasi' => 'info',
        'diproses' => 'primary',
        'siap_diambil', 'dikirim' => 'success',
        'selesai' => 'secondary',
        'dibatalkan' => 'danger',
        default => 'light',
    };
}

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<head>
    <link rel="stylesheet" type="text/css" href="/../aset/css/halaman.css">
</head>
<main class="ontainer-fluid page-content px-3 px-lg-4">
    <!-- Header halaman -->
    <div class="pesanan-heading mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="small fw-semibold mb-2" style="color:#a77a35;">
                    <i class="bi bi-shop me-1"></i>
                    WARUNG BU-IING
                </div>

                <h1 class="h3 mb-2">Manajemen Pesanan</h1>

                <p class="mb-0">
                    Pantau dan kelola seluruh pesanan pelanggan dengan mudah.
                </p>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="pesanan-card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-funnel text-warning fs-5"></i>
                <h2 class="h6 fw-bold mb-0">Cari dan Filter Pesanan</h2>
            </div>

            <form method="GET" class="row g-3 pesanan-filter">
                <div class="col-lg-5 col-md-5">
                    <input
                        type="text"
                        name="cari"
                        class="form-control"
                        placeholder="Cari kode, nama, atau nomor telepon..."
                        value="<?= e($cari) ?>"
                    >
                </div>

                <div class="col-lg-4 col-md-4">
                    <select name="status" class="form-select">
                        <option value="">Semua Status Pesanan</option>

                        <?php foreach ($status_valid as $item_status): ?>
                            <option
                                value="<?= e($item_status) ?>"
                                <?= $status === $item_status ? 'selected' : '' ?>
                            >
                                <?= e(labelStatusPesanan($item_status)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-lg-3 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-pesanan flex-fill">
                        <i class="bi bi-search me-1"></i>
                        Cari
                    </button>

                    <a href="pesanan.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span class="d-none d-sm-inline">Reset</span>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Daftar Pesanan -->
    <div class="pesanan-card">
        <div class="card-body">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">Daftar Pesanan</h2>
                    <p class="text-secondary small mb-0">
                        Berikut daftar pesanan yang masuk ke warung.
                    </p>
                </div>

                <span class="small text-secondary">
                    <i class="bi bi-clock-history me-1"></i>
                    Terbaru ditampilkan lebih dahulu
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle pesanan-table">
                    <thead>
                        <tr>
                            <th>Kode Pesanan</th>
                            <th>Penerima</th>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th class="text-center">Item</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (empty($pesanan)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="bi bi-receipt-cutoff fs-1 text-secondary"></i>
                                <h3 class="h6 fw-bold mt-3">Pesanan Tidak Ditemukan</h3>
                                <p class="text-secondary small mb-0">
                                    Belum ada pesanan yang sesuai dengan pencarian.
                                </p>
                            </td>
                        </tr>
                    <?php else: ?>

                        <?php foreach ($pesanan as $p): ?>
                            <tr>
                                <td>
                                    <span class="kode-pesanan">
                                        <?= e($p['kode_pesanan']) ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="fw-semibold">
                                        <?= e($p['nama_penerima']) ?>
                                    </div>
                                    <div class="small text-secondary">
                                        <i class="bi bi-telephone me-1"></i>
                                        <?= e($p['no_telepon']) ?>
                                    </div>
                                </td>

                                <td class="text-nowrap">
                                    <?= date('d/m/Y', strtotime($p['dibuat_pada'])) ?>
                                    <div class="small text-secondary">
                                        <?= date('H:i', strtotime($p['dibuat_pada'])) ?> WIB
                                    </div>
                                </td>

                                <td>
                                    <?php if ($p['jenis_pesanan'] === 'diantar'): ?>
                                        <span class="text-nowrap">
                                            <i class="bi bi-bicycle text-success me-1"></i>
                                            Diantar
                                        </span>
                                    <?php else: ?>
                                        <span class="text-nowrap">
                                            <i class="bi bi-bag-check text-warning me-1"></i>
                                            Ambil Sendiri
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-center">
                                    <?= (int) $p['jumlah_item'] ?>
                                </td>

                                <td class="text-nowrap fw-semibold">
                                    <?= rupiah($p['total_harga']) ?>
                                </td>

                                <td>
                                    <span class="badge text-bg-<?= e(warnaStatusPesanan($p['status_pesanan'])) ?> badge-status">
                                        <?= e(labelStatusPesanan($p['status_pesanan'])) ?>
                                    </span>
                                </td>

                                <td class="text-center">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary btn-detail"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalPesanan<?= (int) $p['id_pesanan'] ?>"
                                    >
                                        <i class="bi bi-eye me-1"></i>
                                        Detail
                                    </button>
                                </td>

                            </tr>
                                                        
                        <?php endforeach; ?>

                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Modal detail ditempatkan di luar tabel -->
            <?php foreach ($pesanan as $p): ?>
                <?php
$idPesanan = (int) $p['id_pesanan'];
$data = $detailPesanan[$idPesanan]['pesanan'];
$itemPesanan = $detailPesanan[$idPesanan]['item'];

$statusSekarang = $data['status_pesanan'];

$labelStatus = [
    'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
    'dikonfirmasi' => 'Dikonfirmasi',
    'diproses' => 'Sedang Diproses',
    'siap_diambil' => 'Siap Diambil',
    'dikirim' => 'Sedang Dikirim',
    'selesai' => 'Selesai',
    'dibatalkan' => 'Dibatalkan'
];

$pilihanStatus = match ($statusSekarang) {
    'menunggu_konfirmasi' => ['dikonfirmasi', 'dibatalkan'],
    'dikonfirmasi' => ['diproses', 'dibatalkan'],
    'diproses' => (
        $data['jenis_pesanan'] === 'diantar'
        ? ['dikirim']
        : ['siap_diambil']
    ),
    'siap_diambil', 'dikirim' => ['selesai'],
    default => []
};
?>

<div
    class="modal fade"
    id="modalPesanan<?= $idPesanan ?>"
    tabindex="-1"
    aria-hidden="true"
    aria-labelledby="judulModalPesanan<?= $idPesanan ?>"
>
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-detail-pesanan">
        <div class="modal-content pesanan-modal-content">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold" id="judulModalPesanan<?= $idPesanan ?>">
                        <i class="bi bi-receipt me-2"></i>
                        Detail Pesanan
                    </h5>
                    <div class="small text-secondary mt-1">
                        <?= e($data['kode_pesanan']) ?>
                    </div>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Tutup"
                ></button>
            </div>

            <div class="modal-body p-4">

                <!-- Informasi Pelanggan -->
                <h6 class="fw-bold mb-3">
                    <i class="bi bi-person-circle me-2 text-warning"></i>
                    Informasi Pelanggan
                </h6>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="small text-secondary">Nama Penerima</div>
                        <div class="fw-semibold"><?= e($data['nama_penerima']) ?></div>
                    </div>

                    <div class="col-md-6">
                        <div class="small text-secondary">Nomor Telepon</div>
                        <div class="fw-semibold"><?= e($data['no_telepon']) ?></div>
                    </div>

                    <div class="col-12">
                        <div class="small text-secondary">Alamat Pengiriman</div>
                        <div class="fw-semibold">
                            <?= nl2br(e($data['alamat_pengiriman'] ?? '-')) ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="small text-secondary">Jenis Pesanan</div>
                        <div class="fw-semibold">
                            <?= $data['jenis_pesanan'] === 'diantar'
                                ? 'Diantar'
                                : 'Ambil Sendiri' ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="small text-secondary">Tanggal Pesanan</div>
                        <div class="fw-semibold">
                            <?= date('d/m/Y H:i', strtotime($data['dibuat_pada'])) ?> WIB
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="small text-secondary">Catatan</div>
                        <div><?= nl2br(e($data['catatan'] ?: '-')) ?></div>
                    </div>
                </div>

                <hr>

                <!-- Daftar Menu -->
                <h6 class="fw-bold mb-3">
                    <i class="bi bi-basket2 me-2 text-warning"></i>
                    Daftar Makanan
                </h6>

                <div class="table-responsive">
                    <table class="table tabel-detail-pesanan">
                        <thead class="table-light">
                            <tr>
                                <th class="kolom-menu">Menu</th>
                                <th class="kolom-angka">Harga</th>
                                <th class="kolom-jumlah">Jumlah</th>
                                <th class="kolom-angka">Subtotal</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($itemPesanan as $item): ?>
                                <tr>
                                    <td class="kolom-menu fw-semibold"><?= e($item['nama_menu']) ?></td>
                                    <td class="kolom-angka">
                                        <?= rupiah($item['harga_satuan']) ?>
                                    </td>
                                    <td class="kolom-jumlah">
                                        <?= (int) $item['jumlah'] ?>
                                    </td>
                                    <td class="kolom-angka fw-semibold">
                                        <?= rupiah($item['subtotal']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Ringkasan -->
                <div class="pesanan-total ms-auto mt-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span><?= rupiah($data['subtotal']) ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Biaya Pengiriman</span>
                        <span><?= rupiah($data['biaya_pengiriman']) ?></span>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between fw-bold fs-5">
                        <span>Total</span>
                        <span class="pesanan-total-value">
                            <?= rupiah($data['total_harga']) ?>
                        </span>
                    </div>
                </div>

                <hr>

                <!-- Kelola Status -->
                <h6 class="fw-bold mb-3">
                    <i class="bi bi-arrow-repeat me-2 text-warning"></i>
                    Kelola Status Pesanan
                </h6>

                <?php if (empty($pilihanStatus)): ?>

                    <div class="alert alert-secondary mb-0">
                        Pesanan ini sudah
                        <strong><?= e($labelStatus[$statusSekarang] ?? $statusSekarang) ?></strong>.
                        Status tidak dapat diubah lagi.
                    </div>

                <?php else: ?>

                    <form
                        method="POST"
                        action="<?= URL_DASAR ?>/proses/status_pesanan.php"
                        onsubmit="return confirm('Yakin ingin mengubah status pesanan ini?')"
                    >
                        <input
                            type="hidden"
                            name="id_pesanan"
                            value="<?= $idPesanan ?>"
                        >

                        <label class="form-label">
                            Pilih status berikutnya
                        </label>

                        <select
                            name="status_pesanan"
                            class="form-select mb-3"
                            required
                        >
                            <?php foreach ($pilihanStatus as $pilih): ?>
                                <option value="<?= e($pilih) ?>">
                                    <?= e($labelStatus[$pilih]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" class="btn btn-pesanan">
                            <i class="bi bi-check-circle me-1"></i>
                            Simpan Status
                        </button>
                    </form>

                <?php endif; ?>

            </div>

            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    data-bs-dismiss="modal"
                >
                    Tutup
                </button>
            </div>

        </div>
    </div>
</div>
            <?php endforeach; ?>

            <div class="small text-secondary mt-3">
                Menampilkan <?= count($pesanan) ?> pesanan.
            </div>

        </div>
    </div>

</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
