<?php
declare(strict_types=1);

$judul_halaman = $judul_halaman ?? NAMA_APLIKASI;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        <?= htmlspecialchars($judul_halaman, ENT_QUOTES, 'UTF-8') ?>
        | <?= htmlspecialchars(NAMA_APLIKASI, ENT_QUOTES, 'UTF-8') ?>
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <link
        href="<?= htmlspecialchars(url('aset/css/aplikasi.css'), ENT_QUOTES, 'UTF-8') ?>"
        rel="stylesheet"
    >
</head>

<body>
