# Warung Bu-Iing

Kerangka awal aplikasi administrasi Warung Bu-Iing menggunakan PHP native, PDO, MySQL, dan Bootstrap 5.

## Kebutuhan
- PHP 8.1 atau lebih baru
- Ekstensi PDO MySQL
- MySQL atau MariaDB
- Apache atau server PHP lokal

## Menjalankan di XAMPP
1. Ekstrak folder `warung-bu-iing` ke direktori `htdocs`.
2. Pastikan Apache dan MySQL berjalan.
3. Buat database bernama `warung_bu_iing` melalui phpMyAdmin.
4. Sesuaikan `config/aplikasi.php`: nilai `URL_DASAR` harus `/warung-bu-iing` jika dijalankan dari `http://localhost/warung-bu-iing`.
5. Sesuaikan akun database pada `config/database.php`.
6. Buka `http://localhost/warung-bu-iing/`.

## Catatan
- Kerangka ini belum memiliki autentikasi yang berfungsi dan belum terhubung ke tabel database.
- Jangan gunakan untuk produksi sebelum login, kontrol akses, perlindungan CSRF, validasi input, dan konfigurasi keamanan diselesaikan.
- Untuk hosting di root domain, ubah `URL_DASAR` menjadi string kosong.
- Direktori unggahan dan konfigurasi harus dilindungi sebelum digunakan untuk data nyata.
