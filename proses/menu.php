<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/aplikasi.php';
require_once __DIR__ . '/../config/database.php';


if (
    empty($_SESSION['pengguna_id']) ||
    ($_SESSION['pengguna_peran'] ?? '') !== 'admin'
) {
    header('Location: ' . URL_DASAR . '/login.php');
    exit;
}

function kembali(string $pesan): never
{
    $_SESSION['pesan_menu'] = $pesan;
    header('Location: ' . URL_DASAR . '/halaman/menu.php');
    exit;
}

function hapusFotoMenu(?string $foto): void
{
    if (!$foto) {
        return;
    }

    // Hanya izinkan menghapus file di folder uploads/menu.
    if (!preg_match(
        '~\Auploads/menu/[a-f0-9]{32}\.(jpg|png|webp)\z~',
        $foto
    )) {
        return;
    }

    $path = __DIR__ . '/../' . $foto;

    if (is_file($path)) {
        @unlink($path);
    }
}

function uploadFotoMenu(): ?string
{
    if (!isset($_FILES['foto'])) {
        return null;
    }

    $file = $_FILES['foto'];

    if (!is_array($file) || !isset($file['error'])) {
        throw new RuntimeException('Data upload foto tidak valid.');
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException(
            $file['error'] === UPLOAD_ERR_INI_SIZE ||
            $file['error'] === UPLOAD_ERR_FORM_SIZE
                ? 'Ukuran foto terlalu besar.'
                : 'Foto gagal diunggah. Silakan coba lagi.'
        );
    }

    if (
        !isset($file['tmp_name'], $file['size']) ||
        !is_uploaded_file($file['tmp_name'])
    ) {
        throw new RuntimeException('File upload tidak valid.');
    }

    if (
        (int) $file['size'] <= 0 ||
        (int) $file['size'] > 2 * 1024 * 1024
    ) {
        throw new RuntimeException(
            'Ukuran foto harus lebih dari 0 dan maksimal 2 MB.'
        );
    }

    if (!class_exists('finfo')) {
        throw new RuntimeException(
            'Ekstensi PHP fileinfo diperlukan untuk memvalidasi foto.'
        );
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $tipeDiizinkan = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($tipeDiizinkan[$mime])) {
        throw new RuntimeException(
            'Format foto harus JPG, PNG, atau WebP.'
        );
    }

    // Pastikan file benar-benar dapat dibaca sebagai gambar.
    if (@getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('File bukan gambar yang valid.');
    }

    $folder = __DIR__ . '/../uploads/menu';

    if (
        !is_dir($folder) &&
        !mkdir($folder, 0755, true) &&
        !is_dir($folder)
    ) {
        throw new RuntimeException('Folder upload foto gagal dibuat.');
    }

    $namaFile = bin2hex(random_bytes(16))
        . '.' . $tipeDiizinkan[$mime];

    $tujuan = $folder . '/' . $namaFile;

    if (!move_uploaded_file($file['tmp_name'], $tujuan)) {
        throw new RuntimeException('Foto gagal disimpan ke server.');
    }

    return 'uploads/menu/' . $namaFile;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . URL_DASAR . '/halaman/menu.php');
    exit;
}

$aksi = $_POST['aksi'] ?? '';
$fotoBaru = null;

try {
    if ($aksi === 'tambah' || $aksi === 'edit') {
        $nama = trim($_POST['nama_menu'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $hargaInput = $_POST['harga'] ?? '';
        $stokInput = $_POST['stok'] ?? '';

        if (
            $nama === '' ||
            mb_strlen($nama) > 150 ||
            !is_numeric($hargaInput) ||
            !is_finite((float) $hargaInput) ||
            (float) $hargaInput < 0 ||
            (float) $hargaInput > 9999999999.99 ||
            filter_var($stokInput, FILTER_VALIDATE_INT) === false ||
            (int) $stokInput < 0 ||
            (float) $stokInput > 4294967295
        ) {
            kembali('Data menu tidak valid. Periksa nama, harga, dan stok.');
        }

        $harga = (float) $hargaInput;
        $stok = (int) $stokInput;
        $deskripsiDB = $deskripsi !== '' ? $deskripsi : null;

        if ($aksi === 'tambah') {
            $tersedia = isset($_POST['tersedia']) ? 1 : 0;

            $fotoBaru = uploadFotoMenu();

            $stmt = $pdo->prepare(
                'INSERT INTO menu
                    (nama_menu, deskripsi, harga, stok, foto, tersedia)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );

            $stmt->execute([
                $nama,
                $deskripsiDB,
                $harga,
                $stok,
                $fotoBaru,
                $tersedia,
            ]);

            $fotoBaru = null;

            kembali('Menu berhasil ditambahkan.');
        }

        $id = filter_var(
            $_POST['id_menu'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$id || $id < 1) {
            kembali('ID menu tidak valid.');
        }

        $stmt = $pdo->prepare(
            'SELECT foto FROM menu WHERE id_menu = ?'
        );
        $stmt->execute([$id]);
        $menuLama = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$menuLama) {
            kembali('Menu tidak ditemukan.');
        }

        $fotoLama = $menuLama['foto'] ?? null;
        $tersedia = isset($_POST['tersedia']) ? 1 : 0;

        // Foto lama tetap digunakan jika admin tidak memilih foto baru.
        $fotoBaru = uploadFotoMenu();
        $fotoSimpan = $fotoBaru ?? $fotoLama;

        $stmt = $pdo->prepare(
            'UPDATE menu
             SET nama_menu = ?,
                 deskripsi = ?,
                 harga = ?,
                 stok = ?,
                 foto = ?,
                 tersedia = ?
             WHERE id_menu = ?'
        );

        $stmt->execute([
            $nama,
            $deskripsiDB,
            $harga,
            $stok,
            $fotoSimpan,
            $tersedia,
            $id,
        ]);

        // Database berhasil diperbarui; foto lama baru boleh dihapus.
        if ($fotoBaru !== null) {
            hapusFotoMenu($fotoLama);
            $fotoBaru = null;
        }

        kembali('Perubahan menu berhasil disimpan.');
    }

    if ($aksi === 'hapus') {
        $id = filter_var(
            $_POST['id_menu'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$id || $id < 1) {
            kembali('ID menu tidak valid.');
        }

        $stmt = $pdo->prepare(
            'SELECT foto FROM menu WHERE id_menu = ?'
        );
        $stmt->execute([$id]);
        $menuLama = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$menuLama) {
            kembali('Menu tidak ditemukan.');
        }

        // Hapus menu terlebih dahulu. Jika gagal karena relasi database,
        // foto tidak ikut dihapus.
        $stmt = $pdo->prepare('DELETE FROM menu WHERE id_menu = ?');
        $stmt->execute([$id]);

        if ($stmt->rowCount() !== 1) {
            kembali('Menu tidak ditemukan atau gagal dihapus.');
        }

        hapusFotoMenu($menuLama['foto'] ?? null);

        kembali('Menu dan foto terkait berhasil dihapus.');
    }

    kembali('Aksi tidak dikenali.');

} catch (Throwable $e) {
    // Jika upload baru sudah dilakukan tetapi penyimpanan gagal,
    // bersihkan file baru agar tidak menjadi file yatim.
    if ($fotoBaru !== null) {
        hapusFotoMenu($fotoBaru);
    }

    error_log('Proses menu gagal: ' . $e->getMessage());

    $_SESSION['pesan_menu'] = $e instanceof RuntimeException
        ? $e->getMessage()
        : 'Operasi menu gagal. Periksa log aplikasi dan relasi database.';

    header('Location: ' . URL_DASAR . '/halaman/menu.php');
    exit;
}
