<?php
declare(strict_types=1);

require_once __DIR__ . '/config/aplikasi.php';

if (isset($_SESSION['pengguna_id'])) {
    header('Location: ' . url('halaman/dashboard.php'));
    exit;
}

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

function eLogin(mixed $nilai): string
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Login Admin | Warung Bu-Iing</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --warung-primary: #ffbd24;
            --warung-primary-dark: #e5a500;
            --warung-cream: #fff7e5;
            --warung-text: #382b20;
            --warung-muted: #7b746b;
        }

        body {
            min-height: 100vh;
            background: #fffdf8;
            color: var(--warung-text);
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
        }
        
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 15px;
        }

        .login-container {
            width: 100%;
            max-width: 430px;
        }

        .brand-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .brand-icon {
            width: 65px;
            height: 65px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff0c2;
            border-radius: 20px;
            font-size: 34px;
        }

        .brand-info {
            text-align: left;
        }

        .brand-title {
            font-size: 23px;
            font-weight: 800;
            color: var(--warung-text);
        }

        .brand-description {
            color: var(--warung-muted);
            font-size: 14px;
        }

        .login-card {
            width: 100%;
            background: #fff;
            border: 1px solid #f0ece4;
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 12px 40px rgba(60, 45, 20, .07);
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 25px 21px;
                border-radius: 20px;
            }

            .brand-icon {
                width: 58px;
                height: 58px;
                font-size: 30px;
            }

            .brand-title {
                font-size: 21px;
            }

            .brand-description {
                font-size: 12px;
            }
        }
        .login-card {
            width: 100%;
            max-width: 430px;
            background: #fff;
            border: 1px solid #f0ece4;
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 12px 40px rgba(60, 45, 20, .07);
        }

        .admin-label {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: var(--warung-cream);
            color: #8b650b;
            padding: 7px 13px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .form-label {
            font-size: 14px;
            font-weight: 650;
            margin-bottom: 8px;
        }

        .form-control {
            min-height: 49px;
            border-color: #e8e1d5;
            border-radius: 12px;
            font-size: 14px;
        }

        .form-control:focus {
            border-color: var(--warung-primary);
            box-shadow: 0 0 0 .2rem rgba(255, 189, 36, .17);
        }

        .password-group .form-control {
            border-radius: 12px 0 0 12px;
        }

        .password-group .btn {
            border: 1px solid #e8e1d5;
            border-left: 0;
            border-radius: 0 12px 12px 0;
            color: var(--warung-muted);
        }

        .btn-warung {
            min-height: 49px;
            background: var(--warung-primary);
            border: 0;
            border-radius: 12px;
            color: #352500;
            font-weight: 750;
        }

        .btn-warung:hover {
            background: var(--warung-primary-dark);
            color: #352500;
        }

        .back-link {
            color: var(--warung-muted);
            text-decoration: none;
            font-size: 13px;
        }

        .back-link:hover {
            color: var(--warung-text);
        }

        .login-footer {
            color: var(--warung-muted);
            font-size: 12px;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 25px 21px;
                border-radius: 20px;
            }
        }
    </style>
</head>

<body>

<main class="login-wrapper">
    <div class="login-container">

    <!-- LOGO DAN IDENTITAS DI LUAR KARTU -->
    <div class="brand-header">
        <div class="brand-icon">🍲</div>

        <div class="brand-info">
            <div class="brand-title">Warung Bu-Iing</div>
            <p class="brand-description mb-0">
                Masakan Rumahan, Makan Enak Praktis.
            </p>
        </div>
    </div>

    <section class="login-card">

        <h1 class="h4 fw-bold mb-2">Selamat Datang!</h1>

        <p class="text-secondary small mb-4">
            Silakan masuk menggunakan akun admin untuk mengelola warung.
        </p>

        <!-- PESAN ERROR -->
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger border-0 rounded-3 small">
                <i class="bi bi-exclamation-circle me-1"></i>
                <?= eLogin($error) ?>
            </div>
        <?php endif; ?>

        <!-- FORM LOGIN -->
        <form action="<?= eLogin(url('proses/login.php')) ?>" method="POST">

            <div class="mb-3">
                <label for="username" class="form-label">
                    Username
                </label>

                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"
                          style="border-color: #e8e1d5; border-radius: 12px 0 0 12px;">
                        <i class="bi bi-person text-secondary"></i>
                    </span>

                    <input
                        type="text"
                        class="form-control border-start-0"
                        id="username"
                        name="username"
                        placeholder="Masukkan username"
                        autocomplete="username"
                        required
                        autofocus
                        style="border-radius: 0 12px 12px 0;"
                    >
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">
                    Password
                </label>

                <div class="input-group password-group">
                    <span class="input-group-text bg-white border-end-0"
                          style="border-color: #e8e1d5; border-radius: 12px 0 0 12px;">
                        <i class="bi bi-lock text-secondary"></i>
                    </span>

                    <input
                        type="password"
                        class="form-control border-start-0"
                        id="password"
                        name="password"
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        class="btn btn-light"
                        type="button"
                        id="togglePassword"
                        aria-label="Tampilkan password"
                    >
                        <i class="bi bi-eye" id="passwordIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-warung w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i>
                Masuk ke Dashboard
            </button>

        </form>

        <div class="text-center mt-4">
            <a href="<?= eLogin(url('katalog.php')) ?>" class="back-link">
                <i class="bi bi-arrow-left me-1"></i>
                Kembali ke Halaman Menu
            </a>
        </div>

    </section>

</main>

<footer class="text-center pb-4 login-footer">
    &copy; <?= date('Y') ?> Warung Bu-Iing. All rights reserved.
</footer>

<script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const passwordIcon = document.getElementById('passwordIcon');

    togglePassword.addEventListener('click', function () {
        const sedangTersembunyi = passwordInput.type === 'password';

        passwordInput.type = sedangTersembunyi ? 'text' : 'password';

        passwordIcon.classList.toggle('bi-eye', !sedangTersembunyi);
        passwordIcon.classList.toggle('bi-eye-slash', sedangTersembunyi);

        togglePassword.setAttribute(
            'aria-label',
            sedangTersembunyi ? 'Sembunyikan password' : 'Tampilkan password'
        );
    });
</script>

</body>
</html>
