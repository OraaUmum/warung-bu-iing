<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/aplikasi.php';

// Hapus seluruh data sesi pengguna.
$_SESSION = [];

// Hapus cookie sesi jika digunakan.
if (ini_get('session.use_cookies')) {
    $parameter = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $parameter['path'],
        $parameter['domain'],
        $parameter['secure'],
        $parameter['httponly']
    );
}

// Akhiri sesi.
session_destroy();

// Kembali ke halaman login.
header('Location: ../katalog.php');
exit;
