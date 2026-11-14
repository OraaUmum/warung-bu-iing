<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/aplikasi.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

$stmt = $pdo->prepare(
    'SELECT id_pengguna, nama, username, password, peran
     FROM pengguna
     WHERE username = :username
     LIMIT 1'
);

$stmt->execute(['username' => $username]);
$pengguna = $stmt->fetch();

if (
    !$pengguna ||
    !password_verify($password, $pengguna['password']) ||
    $pengguna['peran'] !== 'admin'
) {
    $_SESSION['login_error'] = 'Username atau password salah.';
    header('Location: ../login.php');
    exit;
}

session_regenerate_id(true);

$_SESSION['pengguna_id'] = $pengguna['id_pengguna'];
$_SESSION['pengguna_nama'] = $pengguna['nama'];
$_SESSION['pengguna_peran'] = $pengguna['peran'];

header('Location: ../halaman/dashboard.php');
exit;
