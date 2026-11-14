<?php
declare(strict_types=1);

require_once __DIR__ . '/config/aplikasi.php';

if (isset($_SESSION['pengguna_id'])) {
    header('Location: ' . url('halaman/dashboard.php'));
    exit;
}

header('Location: ' . url('katalog.php'));
exit;
