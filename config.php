<?php
/**
 * Konfigurasi Database - Running Health Tracker
 * 
 * Default menggunakan SQLite agar aplikasi dapat langsung dijalankan tanpa setup database.
 * Jika ingin beralih ke MySQL, ubah DB_DRIVER menjadi 'mysql' dan sesuaikan parameter lainnya.
 */

define('DB_DRIVER', 'sqlite'); // Pilihan: 'sqlite' atau 'mysql'

// Konfigurasi SQLite
define('SQLITE_FILE', __DIR__ . '/database.sqlite');

// Konfigurasi MySQL (Hanya digunakan jika DB_DRIVER = 'mysql')
define('DB_HOST', 'localhost');
define('DB_NAME', 'running_health_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Konfigurasi Sesi & Waktu
date_default_timezone_set('Asia/Jakarta');

// Konfigurasi Kredensial Portal Admin (tukangketik)
define('ADMIN_USER', 'admin');
define('ADMIN_PASSWORD', 'adminStadion123');

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
