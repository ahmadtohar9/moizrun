<?php
/**
 * Main Web View - Running Health Tracker
 * 
 * Halaman utama aplikasi yang berfungsi sebagai Single Page Application (SPA).
 * Memuat semua halaman modal/container dan memproses perpindahan halaman via js/app.js.
 */

// Enforce trailing slash for subfolder hosting to ensure Service Worker and manifest scope work properly
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];
$dirName = rtrim(dirname($scriptName), '/\\');

if ($dirName !== '' && $dirName !== '/') {
    $requestPath = parse_url($requestUri, PHP_URL_PATH);
    if ($requestPath === $dirName) {
        $queryString = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '';
        header('Location: ' . $dirName . '/' . $queryString, true, 301);
        exit;
    }
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(getSetting('app_name', 'Stadion Run')) ?> - Running Health Tracker</title>
    
    <!-- Meta SEO -->
    <meta name="description" content="Aplikasi monitoring kesehatan atlet dan pelari sore di Stadion. Pantau denyut nadi, tekanan darah, IMT, BBI, dan tingkat intensitas latihan secara real-time.">
    <meta name="keywords" content="running health tracker, stadion run, kesehatan lari, hitung bmi, detak jantung, bbi broca">
    
    <!-- Styling -->
    <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">
    
    <!-- PWA Manifest -->
    <?php
    $base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    if ($base_path !== '' && $base_path[0] !== '/') {
        $base_path = '/' . $base_path;
    }
    ?>
    <link rel="manifest" href="<?= $base_path ?>/manifest.json">
    <meta name="theme-color" content="#ff007f">
    <link rel="apple-touch-icon" href="<?= $base_path ?>/css/images/icon-192.png">
</head>
<body>

    <!-- Loading Spinner Overlay -->
    <div id="loading-overlay">
        <div class="spinner" id="main-spinner"></div>
        <p class="mt-4" style="color: var(--primary); font-weight: 500;">Memuat Aplikasi...</p>
    </div>

    <!-- App Shell Container (Mobile-First Frame) -->
    <div class="app-container" id="app-shell">
        
        <!-- Background Glow Blobs for Premium Feel -->
        <div class="glow-blob blob-1"></div>
        <div class="glow-blob blob-2"></div>
        
        <!-- ================= PAGE 1: LANDING ================= -->
        <div class="page active" id="page-landing">
            <div class="auth-header text-center mb-6" style="margin-top: 20px;">
                <div class="avatar-ring mb-3 animate-pulse-slow" style="width: 80px; height: 80px; padding: 3px; margin: 0 auto;">
                    <div class="avatar-inner" style="font-size: 36px;"><?= htmlspecialchars(getSetting('app_logo', '🏃')) ?></div>
                </div>
                <h1><?= htmlspecialchars(getSetting('app_name', 'STADION RUN')) ?></h1>
                <p style="font-size: 14px; color: var(--text-muted); margin-top: 4px; margin-bottom: 0; line-height: 1.4;">
                    Aplikasi Monitoring Kesehatan & Evaluasi Lari Sore Anda
                </p>
            </div>
            
            <div class="card auth-card text-center">
                <p style="color: var(--text-main); font-size: 14px; margin-bottom: 24px; line-height: 1.6;">
                    Pindai QR Code di stadion, isi data vital sebelum dan sesudah lari, dan dapatkan skor kesehatan latihan Anda secara otomatis!
                </p>
                
                <button id="btn-landing-login" onclick="navigateTo('login')" class="btn btn-pulse mb-4">
                    <span>Masuk ke Akun</span>
                    <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                </button>
                <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 0;">
                    Belum punya akun? <a id="link-landing-register" onclick="navigateTo('register')" class="link-action">Daftar Sekarang</a>
                </p>
            </div>
            
            <!-- Install App Prompts (Only shown if PWA is installable) -->
            <div id="pwa-install-container" class="card mt-4" style="display: none; text-align: center; border: 1px dashed var(--primary); background: rgba(255, 0, 127, 0.05); padding: 16px; border-radius: 16px;">
                <p style="font-size: 14px; color: var(--text-main); margin-bottom: 12px; font-weight: 500;">
                    📲 Pasang aplikasi di HP Anda untuk akses lebih cepat & offline!
                </p>
                <button id="btn-pwa-install" class="btn" style="background: linear-gradient(135deg, var(--primary), #ff0055); width: auto; font-size: 14px; padding: 8px 16px; margin: 0 auto;">
                    <span>Pasang Aplikasi</span>
                </button>
            </div>
        </div>

        <!-- ================= PAGE 2: LOGIN ================= -->
        <div class="page" id="page-login">
            <div class="auth-header text-center mb-6">
                <div class="avatar-ring mb-3 animate-pulse-slow" style="width: 64px; height: 64px; padding: 2px; margin: 0 auto;">
                    <div class="avatar-inner" style="font-size: 28px;"><?= htmlspecialchars(getSetting('app_logo', '🏃')) ?></div>
                </div>
                <h2>Masuk ke MoizRun</h2>
                <p>Silakan login untuk mulai merekam aktivitas lari Anda.</p>
            </div>
            
            <div class="card auth-card">
                <form id="login-form" autocomplete="off">
                    <div class="form-group">
                        <label for="login-username">Username</label>
                        <div class="input-container">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            <input type="text" id="login-username" placeholder="Masukkan username" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <div class="input-container">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            <input type="password" id="login-password" placeholder="Masukkan password" required>
                        </div>
                    </div>
                    
                    <button type="submit" id="btn-login-submit" class="btn btn-pulse mt-6">
                        <span>Masuk</span>
                        <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                    </button>
                </form>
            </div>
            
            <p class="text-center mt-6">
                Belum memiliki akun? <a id="link-login-register" onclick="navigateTo('register')" class="link-action">Daftar di sini</a>
                <br><br>
                <a onclick="navigateTo('forgot-password')" class="link-action" style="font-size: 13px; color: var(--text-muted); font-weight: 500;">Lupa Password?</a>
            </p>

            <!-- Install App Prompts for Login Page -->
            <div id="pwa-install-container-login" class="card mt-6" style="display: none; text-align: center; border: 1px dashed var(--primary); background: rgba(255, 0, 127, 0.05); padding: 16px; border-radius: 16px;">
                <p style="font-size: 14px; color: var(--text-main); margin-bottom: 12px; font-weight: 500;">
                    📲 Pasang aplikasi di HP Anda untuk akses lebih cepat & offline!
                </p>
                <button id="btn-pwa-install-login" class="btn" style="background: linear-gradient(135deg, var(--primary), #ff0055); width: auto; font-size: 14px; padding: 8px 16px; margin: 0 auto;">
                    <span>Pasang Aplikasi</span>
                </button>
            </div>
        </div>

        <!-- ================= PAGE: FORGOT PASSWORD ================= -->
        <div class="page" id="page-forgot-password">
            <div class="auth-header text-center mb-6" style="margin-top: 20px;">
                <div class="avatar-ring mb-3 animate-pulse-slow" style="width: 64px; height: 64px; padding: 2px; margin: 0 auto;">
                    <div class="avatar-inner" style="font-size: 28px;">🔑</div>
                </div>
                <h2>Lupa Password</h2>
                <p>Verifikasi data akun Anda untuk menyetel ulang password.</p>
            </div>
            
            <!-- Step 1: Verification Form -->
            <div class="card auth-card" id="recovery-step-1">
                <form id="forgot-password-form" autocomplete="off">
                    <div class="form-group">
                        <label for="forgot-phone">Nomor HP</label>
                        <div class="input-container">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            <input type="tel" id="forgot-phone" placeholder="Contoh: 08123456789" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="forgot-dob">Tanggal Lahir</label>
                        <div class="input-container">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <input type="date" id="forgot-dob" required>
                        </div>
                    </div>
                    
                    <button type="submit" id="btn-forgot-submit" class="btn btn-pulse mt-6">
                        <span>Verifikasi Akun</span>
                        <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </button>
                </form>
            </div>
            
            <!-- Step 2: Reset Form (Hidden initially) -->
            <div class="card auth-card" id="recovery-step-2" style="display: none;">
                <form id="reset-password-form" autocomplete="off">
                    <div class="form-group">
                        <label for="reset-new-password">Password Baru</label>
                        <div class="input-container">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            <input type="password" id="reset-new-password" placeholder="Masukkan password baru" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="reset-confirm-password">Konfirmasi Password Baru</label>
                        <div class="input-container">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            <input type="password" id="reset-confirm-password" placeholder="Ulangi password baru" required>
                        </div>
                    </div>
                    
                    <button type="submit" id="btn-reset-submit" class="btn btn-pulse mt-6">
                        <span>Simpan Password</span>
                        <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                    </button>
                </form>
            </div>
            
            <p class="text-center mt-6"><a id="link-forgot-back" onclick="navigateTo('login')" class="link-action">Kembali ke Login</a></p>
        </div>

        <!-- ================= PAGE 3: REGISTER ================= -->
        <div class="page" id="page-register">
            <h2>Daftar Akun Baru</h2>
            <p class="mb-6">Isi formulir pendaftaran akun pelari secara lengkap.</p>
            
            <form id="register-form" autocomplete="off">
                <div class="form-group">
                    <label for="reg-fullname">Nama Lengkap</label>
                    <div class="input-container">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        <input type="text" id="reg-fullname" placeholder="Nama lengkap sesuai KTP" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reg-dob">Tanggal Lahir</label>
                    <div class="input-container">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <input type="date" id="reg-dob" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Jenis Kelamin (Penting untuk Hitung Berat Ideal)</label>
                    <div class="gender-select-container">
                        <div class="gender-box selected" id="gender-male">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            <span>Laki-Laki</span>
                        </div>
                        <div class="gender-box" id="gender-female">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            <span>Perempuan</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reg-address">Alamat Tinggal</label>
                    <div class="input-container">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <input type="text" id="reg-address" placeholder="Alamat lengkap saat ini" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reg-phone">Nomor Handphone</label>
                    <div class="input-container">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                        <input type="tel" id="reg-phone" placeholder="Contoh: 08123456789" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reg-email">Email (Opsional)</label>
                    <div class="input-container">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        <input type="email" id="reg-email" placeholder="Masukkan alamat email (jika ada)">
                    </div>
                </div>

                <div class="form-group">
                    <label for="reg-username">Username Pilihan</label>
                    <div class="input-container">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <input type="text" id="reg-username" placeholder="Gunakan huruf kecil/angka tanpa spasi" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reg-password">Password</label>
                    <div class="input-container">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        <input type="password" id="reg-password" placeholder="Minimal 6 karakter" required>
                    </div>
                </div>

                <button type="submit" id="btn-register-submit" class="btn mt-6">
                    <span>Buat Akun Pelari</span>
                </button>
            </form>
            
            <p class="text-center mt-6">Sudah memiliki akun? <a id="link-register-login" onclick="navigateTo('login')" class="link-action">Log in disini</a></p>
        </div>

        <!-- ================= PAGE 4: DASHBOARD ================= -->
        <div class="page" id="page-dashboard">
            <!-- Header User Info -->
            <div class="user-header-panel">
                <div>
                    <h3 style="margin-bottom: 2px;">Halo, Atlet!</h3>
                    <h1 id="dash-user-name" style="font-size: 22px; margin-bottom: 0;">Pelari Stadion</h1>
                </div>
                <div class="avatar-ring">
                    <div class="avatar-inner" id="dash-avatar-letter">U</div>
                </div>
            </div>

            <!-- Quick Profile Data widget -->
            <div class="quick-info-grid">
                <div class="info-box">
                    <span class="label">Jenis Kelamin</span>
                    <span class="value" id="dash-user-gender">-</span>
                </div>
                <div class="info-box">
                    <span class="label">Usia Anda</span>
                    <span class="value" id="dash-user-age">-</span>
                </div>
            </div>

            <!-- Kontrol Utama Sesi Lari (Dibuat Dinamis oleh JS) -->
            <div class="card status-tracker-card" id="run-control-card">
                <!-- Konten dinamis masuk dari app.js: loadDashboard() -->
            </div>

            <!-- Workout Music Playlist (Spotify & YouTube Switcher) -->
            <div id="workout-music-card" class="card" style="display: none; margin-top: 10px; padding: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h3 style="color: var(--primary); font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 6px; margin-bottom: 0;">
                        <svg style="width: 18px; height: 18px; color: var(--primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path></svg>
                        Workout Music
                    </h3>
                    <div style="display: flex; gap: 6px;">
                        <button type="button" id="btn-music-spotify" onclick="switchMusicSource('spotify')" class="badge badge-normal" style="border-radius: 6px; padding: 3px 8px; cursor: pointer; border: 1px solid var(--primary); background: rgba(255, 0, 127, 0.08);">Spotify</button>
                        <button type="button" id="btn-music-youtube" onclick="switchMusicSource('youtube')" class="badge" style="border-radius: 6px; padding: 3px 8px; cursor: pointer; border: 1px solid var(--card-border); background: transparent; color: var(--text-muted);">YouTube</button>
                    </div>
                </div>

                <!-- Playlist & Custom Controls for Spotify -->
                <div id="controls-spotify" style="margin-bottom: 10px;">
                    <div class="input-container no-icon" style="margin-bottom: 6px;">
                        <select id="select-spotify-playlist" onchange="handleSpotifyPlaylistChange()" style="font-size: 13px; padding: 8px 12px; border-radius: 10px; border: 1.5px solid var(--card-border); width: 100%; outline: none; background: white; color: var(--text-main);">
                            <option value="37i9dQZF1DX4sWSpwq3LiO">🏃 Spotify Running Beats (Default)</option>
                            <option value="37i9dQZF1DX76t638V6eg8">🔥 Gym Beats</option>
                            <option value="37i9dQZF1DWZdq4J2geJPA">⚡ Phonk Workout</option>
                            <option value="37i9dQZF1DX84uJ7v37Rgc">🎵 Lofi Workout</option>
                            <option value="custom">✍️ Kustom Playlist Link / ID</option>
                        </select>
                    </div>
                    <div id="custom-spotify-container" style="display: none; display: flex; gap: 6px; margin-bottom: 6px;">
                        <input type="text" id="input-custom-spotify" placeholder="Tempel URL / ID Playlist Spotify" style="flex: 1; font-size: 13px; padding: 8px 12px; border-radius: 10px; border: 1.5px solid var(--card-border); outline: none; background: white; color: var(--text-main);">
                        <button type="button" onclick="loadCustomSpotifyPlaylist()" class="btn" style="width: auto; padding: 8px 16px; font-size: 13px; margin: 0; background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%); color: white;">Load</button>
                    </div>
                </div>

                <!-- Playlist & Custom Controls for YouTube -->
                <div id="controls-youtube" style="margin-bottom: 10px; display: none;">
                    <div class="input-container no-icon" style="margin-bottom: 6px;">
                        <select id="select-youtube-video" onchange="handleYoutubeVideoChange()" style="font-size: 13px; padding: 8px 12px; border-radius: 10px; border: 1.5px solid var(--card-border); width: 100%; outline: none; background: white; color: var(--text-main);">
                            <option value="3nQNiWdeH2Q">🏃 Janji - Heroes Tonight (Default)</option>
                            <option value="K4YPv-IpLcI">🔥 Cartoon - On & On (NCS)</option>
                            <option value="l91k6m-o_Gg">⚡ Synthwave Cardio Beats</option>
                            <option value="jfKfPfyJRdk">🎵 Lofi Girl Live Radio</option>
                            <option value="custom">✍️ Kustom Video Link / ID</option>
                        </select>
                    </div>
                    <div id="custom-youtube-container" style="display: none; display: flex; gap: 6px; margin-bottom: 6px;">
                        <input type="text" id="input-custom-youtube" placeholder="Tempel URL Video / ID YouTube" style="flex: 1; font-size: 13px; padding: 8px 12px; border-radius: 10px; border: 1.5px solid var(--card-border); outline: none; background: white; color: var(--text-main);">
                        <button type="button" onclick="loadCustomYoutubeVideo()" class="btn" style="width: auto; padding: 8px 16px; font-size: 13px; margin: 0; background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%); color: white;">Load</button>
                    </div>
                </div>
                
                <div id="player-spotify">
                    <iframe id="iframe-spotify" style="border-radius:12px" src="https://open.spotify.com/embed/playlist/37i9dQZF1DX4sWSpwq3LiO?utm_source=generator&theme=0" width="100%" height="80" frameBorder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>
                </div>
                <div id="player-youtube" style="display: none;">
                    <iframe id="iframe-youtube" style="border-radius:12px" width="100%" height="180" src="https://www.youtube.com/embed/3nQNiWdeH2Q" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                </div>
            </div>

            <!-- Card Informasi Stadion / Tips Olahraga -->
            <div class="card" style="margin-top: 10px;">
                <h3 style="color: var(--primary); font-weight: 600; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                    <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Panduan Lari Sore Aman
                </h3>
                <ul style="font-size: 13px; color: var(--text-muted); padding-left: 18px; line-height: 1.6;">
                    <li>Lakukan pemanasan minimal 5-10 menit sebelum lari.</li>
                    <li>Pastikan denyut nadi istirahat Anda di bawah 100 bpm sebelum mulai.</li>
                    <li>Segera melambat / jalan kaki jika detak jantung melampaui batas 85% HRmaks.</li>
                    <li>Minum air putih secukupnya demi mencegah dehidrasi.</li>
                </ul>
            </div>
        </div>

        <!-- ================= PAGE 5: PRE-RUN DATA FORM ================= -->
        <div class="page" id="page-pre-run">
            <h2>Data Kesehatan Awal (Pre-Run)</h2>
            <p class="mb-6">Isi data kesehatan fisik Anda sebelum memulai aktivitas lari sore.</p>
            
            <form id="pre-run-form">
                <!-- Tinggi Badan & Berat Badan -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label for="pre-height">Tinggi Badan</label>
                        <div class="input-container no-icon">
                            <input type="number" id="pre-height" placeholder="Contoh: 170" step="0.1" class="has-unit" required>
                            <span class="input-unit">cm</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="pre-weight">Berat Badan</label>
                        <div class="input-container no-icon">
                            <input type="number" id="pre-weight" placeholder="Contoh: 65" step="0.1" class="has-unit" required>
                            <span class="input-unit">kg</span>
                        </div>
                    </div>
                </div>

                <!-- Heart Rate -->
                <div class="form-group">
                    <label for="pre-heart-rate">Denyut Nadi Istirahat (Heart Rate)</label>
                    <div class="input-container">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                        <input type="number" id="pre-heart-rate" placeholder="Rentang normal: 60 - 100" class="has-unit" required>
                        <span class="input-unit">x/mnt</span>
                    </div>
                </div>

                <!-- Tekanan Darah (Sistolik / Diastolik) -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label for="pre-systolic">Tensi Sistolik</label>
                        <div class="input-container no-icon">
                            <input type="number" id="pre-systolic" placeholder="Sistolik (cth: 120)" class="has-unit" required>
                            <span class="input-unit">mmHg</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="pre-diastolic">Tensi Diastolik</label>
                        <div class="input-container no-icon">
                            <input type="number" id="pre-diastolic" placeholder="Diastolik (cth: 80)" class="has-unit" required>
                            <span class="input-unit">mmHg</span>
                        </div>
                    </div>
                </div>

                <!-- Suhu Tubuh -->
                <div class="form-group">
                    <label for="pre-temperature">Suhu Tubuh</label>
                    <div class="input-container no-icon">
                        <input type="number" id="pre-temperature" placeholder="Rentang normal: 36.5 - 37.5" step="0.01" class="has-unit" required>
                        <span class="input-unit">°C</span>
                    </div>
                </div>

                <!-- Kolesterol -->
                <div class="form-group">
                    <label for="pre-cholesterol">Kadar Kolesterol</label>
                    <div class="input-container no-icon">
                        <input type="number" id="pre-cholesterol" placeholder="Rentang normal: < 200" class="has-unit" required>
                        <span class="input-unit">mg/dl</span>
                    </div>
                </div>

                <!-- SpO2 -->
                <div class="form-group">
                    <label for="pre-spo2">Kandungan Oksigen Darah (SpO2)</label>
                    <div class="input-container no-icon">
                        <input type="number" id="pre-spo2" placeholder="Rentang normal: 95 - 100" class="has-unit" required>
                        <span class="input-unit">%</span>
                    </div>
                </div>

                <!-- Pilihan Lokasi Lari -->
                <div class="form-group">
                    <label for="start-run-location">Lokasi Lari (Stadion/Tempat)</label>
                    <div class="input-container no-icon">
                        <select id="start-run-location" required>
                            <!-- Dinamis via JS -->
                        </select>
                    </div>
                </div>

                <!-- Target Lari (Waktu atau Lap) -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 16px;">
                    <div class="form-group">
                        <label for="pre-target-type">Target Lari Sore</label>
                        <div class="input-container no-icon">
                            <select id="pre-target-type">
                                <option value="none">Tanpa Target</option>
                                <option value="time">Target Waktu</option>
                                <option value="laps">Target Putaran (Lap)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" id="target-value-group" style="display: none;">
                        <label for="pre-target-value" id="target-value-label">Target Durasi</label>
                        <div class="input-container no-icon">
                            <input type="number" id="pre-target-value" placeholder="0" class="has-unit">
                            <span class="input-unit" id="target-value-unit">menit</span>
                        </div>
                    </div>
                </div>

                <!-- Real-time Preview Panel -->
                <div class="realtime-preview" id="pre-realtime-calc" style="display: none;"></div>

                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px; margin-top: 24px;">
                    <button type="button" onclick="navigateTo('dashboard')" class="btn btn-secondary">Batal</button>
                    <button type="submit" id="btn-pre-submit" class="btn">Mulai Lari Sore</button>
                </div>
            </form>
        </div>

        <!-- ================= PAGE 6: POST-RUN DATA FORM ================= -->
        <div class="page" id="page-post-run">
            <h2>Data Kesehatan Akhir (Post-Run)</h2>
            <p class="mb-6">Isi metrik vital tubuh Anda setelah selesai berolahraga lari untuk dievaluasi.</p>
            
            <form id="post-run-form">
                <!-- Heart Rate Pasca Aktivitas (WAJIB) -->
                <div class="form-group">
                    <label for="post-heart-rate">Denyut Nadi Setelah Aktivitas (Wajib)</label>
                    <div class="input-container">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                        <input type="number" id="post-heart-rate" placeholder="Masukkan denyut nadi setelah lari" class="has-unit" required>
                        <span class="input-unit">x/mnt</span>
                    </div>
                </div>

                <!-- Durasi Lari & Putaran (Auto-populated atau manual) -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label for="post-duration">Durasi Aktivitas Lari</label>
                        <div class="input-container no-icon">
                            <input type="number" id="post-duration" placeholder="Total menit" class="has-unit" required>
                            <span class="input-unit">menit</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="post-laps">Jumlah Putaran (Lap)</label>
                        <div class="input-container no-icon">
                            <input type="number" id="post-laps" placeholder="Putaran" class="has-unit" required>
                            <span class="input-unit">lap</span>
                        </div>
                    </div>
                </div>

                <div class="card" style="padding: 14px; margin-bottom: 16px; background: rgba(255,255,255,0.01); border-color: rgba(255,255,255,0.04);">
                    <p style="font-size: 12px; color: var(--text-muted);">
                        Isian di bawah ini bersifat opsional untuk memantau perubahan kondisi organ tubuh Anda pasca lari sore.
                    </p>
                </div>

                <!-- Tensi Pasca Lari -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label for="post-systolic">Tensi Sistolik Akhir</label>
                        <div class="input-container no-icon">
                            <input type="number" id="post-systolic" placeholder="Sistolik (opsional)" class="has-unit">
                            <span class="input-unit">mmHg</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="post-diastolic">Tensi Diastolik Akhir</label>
                        <div class="input-container no-icon">
                            <input type="number" id="post-diastolic" placeholder="Diastolik (opsional)" class="has-unit">
                            <span class="input-unit">mmHg</span>
                        </div>
                    </div>
                </div>

                <!-- Suhu Tubuh Akhir -->
                <div class="form-group">
                    <label for="post-temperature">Suhu Tubuh Akhir</label>
                    <div class="input-container no-icon">
                        <input type="number" id="post-temperature" placeholder="Suhu setelah lari (opsional)" step="0.01" class="has-unit">
                        <span class="input-unit">°C</span>
                    </div>
                </div>

                <!-- SpO2 Akhir -->
                <div class="form-group">
                    <label for="post-spo2">Kandungan Oksigen Darah Akhir (SpO2)</label>
                    <div class="input-container no-icon">
                        <input type="number" id="post-spo2" placeholder="SpO2 setelah lari (opsional)" class="has-unit">
                        <span class="input-unit">%</span>
                    </div>
                </div>

                <!-- Real-time Preview Panel -->
                <div class="realtime-preview" id="post-realtime-calc" style="display: none;"></div>

                <button type="submit" id="btn-post-submit" class="btn mt-6" style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);">
                    <span>Selesaikan Lari & Lihat Nilai</span>
                </button>
            </form>
        </div>

        <!-- ================= PAGE 7: EVALUATION & ANALYSIS ================= -->
        <div class="page" id="page-evaluation">
            <h2 class="text-center" style="margin-bottom: 4px;">Evaluasi Kesehatan Lari</h2>
            <p id="eval-date" class="text-center mb-1" style="font-size: 13px; color: var(--text-muted);"></p>
            <p id="eval-location" class="text-center mb-6" style="font-size: 14px; color: var(--primary); font-weight: 600;"></p>
            
            <!-- Circular Gauge -->
            <div class="evaluation-header">
                <div class="gauge-container">
                    <svg class="gauge-svg" viewBox="0 0 140 140">
                        <defs>
                            <linearGradient id="gauge-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="var(--secondary)" />
                                <stop offset="100%" stop-color="var(--primary)" />
                            </linearGradient>
                        </defs>
                        <circle class="gauge-track" cx="70" cy="70" r="60" />
                        <circle id="eval-gauge-circle" class="gauge-fill" cx="70" cy="70" r="60" />
                    </svg>
                    <div class="gauge-text">
                        <span class="score-val" id="eval-score">0</span>
                        <span class="score-lbl">Skor</span>
                    </div>
                </div>
                <h3>Kualitas Kebugaran Aktivitas Anda</h3>
            </div>

            <!-- Analisis Rekomendasi/Saran -->
            <div class="card">
                <h3 style="color: var(--text-main); font-weight: 600; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                    <svg style="width: 20px; height: 20px; color: var(--primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Rekomendasi Latihan & Analisis
                </h3>
                <div class="rec-list" id="eval-recommendations">
                    <!-- Dinamis dimasukkan oleh js -->
                </div>
            </div>

            <!-- Perbandingan Vital Parameter Grid -->
            <h2>Metrik Perbandingan Fisik</h2>
            <div class="comparison-grid" id="eval-comparison-grid">
                <!-- Dinamis dimasukkan oleh js -->
            </div>

            <button onclick="navigateTo('dashboard')" class="btn mb-4">Kembali ke Dashboard</button>
        </div>

        <!-- ================= PAGE 8: HISTORY LIST ================= -->
        <div class="page" id="page-history">
            <h2>Riwayat Aktivitas</h2>
            <p class="subtitle">Daftar rekaman latihan lari sore Anda yang telah diselesaikan di stadion.</p>
            
            <div class="history-list" id="history-list-container">
                <!-- Dinamis dimasukkan oleh js -->
            </div>
        </div>        <!-- ================= BOTTOM NAVIGATION BAR ================= -->
        <nav class="bottom-nav" id="bottom-nav-bar">
            <button class="nav-item active" id="nav-dashboard" onclick="navigateTo('dashboard')">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span>Beranda</span>
            </button>
            <button class="nav-item" id="nav-history" onclick="navigateTo('history')">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                <span>Riwayat</span>
            </button>
            <button class="nav-item" id="nav-logout" onclick="handleLogout()">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                <span>Keluar</span>
            </button>
        </nav>

    </div>

    <!-- Application Script -->
    <script src="js/app.js?v=<?= filemtime(__DIR__ . '/js/app.js') ?>"></script>
    
    <!-- Register PWA Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?= $base_path ?>/sw.js?v=<?= filemtime(__DIR__ . '/sw.js') ?>')
                    .then((reg) => {
                        console.log('PWA Service Worker registered successfully:', reg.scope);
                        
                        // Detect updates to service worker and auto reload page to break cache
                        reg.onupdatefound = () => {
                            const installingWorker = reg.installing;
                            installingWorker.onstatechange = () => {
                                if (installingWorker.state === 'installed') {
                                    if (navigator.serviceWorker.controller) {
                                        console.log('New update available, reloading to apply changes...');
                                        window.location.reload();
                                    }
                                }
                            };
                        };
                    })
                    .catch((err) => console.error('PWA Service Worker registration failed:', err));
            });
        }
    </script>
</body>
</html>
