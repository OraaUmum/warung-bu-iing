<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

define('NAMA_APLIKASI', 'Warung Bu-Iing');

/*
 * Untuk instalasi di subfolder, misalnya http://localhost/warung-bu-iing,
 * isi URL_DASAR dengan '/warung-bu-iing'.
 * Jika aplikasi berada di root domain, gunakan string kosong.
 */
define('URL_DASAR', '');

date_default_timezone_set('Asia/Jakarta');

function url(string $jalur = ''): string
{
    return rtrim(URL_DASAR, '/') . '/' . ltrim($jalur, '/');
}
