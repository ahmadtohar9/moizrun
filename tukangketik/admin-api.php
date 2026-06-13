<?php
/**
 * Admin API Endpoint - Running Health Tracker
 * 
 * Menangani semua request AJAX untuk dashboard administrator.
 * Di-host di bawah folder /tukangketik/
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// Ambil input JSON raw body jika ada
$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true);

// Gabungkan input POST/GET dan JSON
$requestData = array_merge($_GET, $_POST, is_array($jsonData) ? $jsonData : []);
$action = $requestData['action'] ?? '';

$db = getDBConnection();

// Response Helper
function sendAdminResponse($status, $message, $data = []) {
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message
    ], $data));
    exit;
}

// Cek Login Admin Helper
function requireAdminLogin() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        sendAdminResponse('unauthorized', 'Sesi admin telah berakhir. Silakan login kembali.');
    }
}

// Aksi Login
if ($action === 'login') {
    $username = trim($requestData['username'] ?? '');
    $password = $requestData['password'] ?? '';

    if (empty($username) || empty($password)) {
        sendAdminResponse('error', 'Username dan password wajib diisi.');
    }

    $dbUser = getSetting('admin_user', 'admin');
    $dbPassHash = getSetting('admin_pass');

    if (empty($dbPassHash)) {
        $dbPassHash = password_hash(ADMIN_PASSWORD, PASSWORD_BCRYPT);
    }

    if ($username === $dbUser && password_verify($password, $dbPassHash)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        sendAdminResponse('success', 'Login administrator berhasil!');
    } else {
        sendAdminResponse('error', 'Username atau password administrator salah.');
    }
}

// Aksi lainnya membutuhkan pengecekan autentikasi admin
requireAdminLogin();

switch ($action) {
    case 'logout':
        unset($_SESSION['admin_logged_in']);
        unset($_SESSION['admin_username']);
        sendAdminResponse('success', 'Logout admin berhasil.');
        break;

    case 'get_locations':
        try {
            $stmt = $db->query("SELECT id, name, address, latitude, longitude, google_maps_url,
                                       (SELECT COUNT(DISTINCT user_id) FROM runs WHERE location_id = locations.id) as user_count,
                                       (SELECT COUNT(*) FROM runs WHERE location_id = locations.id) as run_count
                                FROM locations ORDER BY name ASC");
            $locations = $stmt->fetchAll();
            sendAdminResponse('success', 'Locations loaded', ['locations' => $locations]);
        } catch (PDOException $e) {
            sendAdminResponse('error', 'Gagal memuat lokasi: ' . $e->getMessage());
        }
        break;

    case 'add_location':
        $name = trim($requestData['name'] ?? '');
        $address = trim($requestData['address'] ?? '');
        $latitude = isset($requestData['latitude']) ? floatval($requestData['latitude']) : null;
        $longitude = isset($requestData['longitude']) ? floatval($requestData['longitude']) : null;
        $mapsUrl = trim($requestData['google_maps_url'] ?? '');

        if (empty($name) || empty($address) || $latitude === null || $longitude === null) {
            sendAdminResponse('error', 'Nama, alamat, lintang, dan bujur wajib diisi.');
        }

        if (empty($mapsUrl)) {
            $mapsUrl = "https://maps.google.com/?q={$latitude},{$longitude}";
        }

        try {
            $stmt = $db->prepare("INSERT INTO locations (name, address, latitude, longitude, google_maps_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $address, $latitude, $longitude, $mapsUrl]);
            sendAdminResponse('success', 'Lokasi lari baru berhasil ditambahkan.');
        } catch (PDOException $e) {
            sendAdminResponse('error', 'Gagal menambahkan lokasi: ' . $e->getMessage());
        }
        break;

    case 'delete_location':
        $locationId = intval($requestData['id'] ?? 0);
        if ($locationId <= 0) {
            sendAdminResponse('error', 'ID Lokasi tidak valid.');
        }
        
        $defaultLocId = intval(getSetting('default_location_id', '1'));
        if ($locationId === $defaultLocId) {
            sendAdminResponse('error', 'Lokasi default utama tidak boleh dihapus. Silakan tentukan lokasi default lain terlebih dahulu.');
        }

        try {
            $stmt = $db->prepare("DELETE FROM locations WHERE id = ?");
            $stmt->execute([$locationId]);
            sendAdminResponse('success', 'Lokasi lari berhasil dihapus.');
        } catch (PDOException $e) {
            sendAdminResponse('error', 'Gagal menghapus lokasi: ' . $e->getMessage());
        }
        break;

    case 'set_default_location':
        $locationId = intval($requestData['id'] ?? 0);
        if ($locationId <= 0) {
            sendAdminResponse('error', 'ID Lokasi tidak valid.');
        }

        try {
            // Cek apakah lokasi ada
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM locations WHERE id = ?");
            $stmt->execute([$locationId]);
            if ($stmt->fetch()['count'] == 0) {
                sendAdminResponse('error', 'Lokasi tidak ditemukan.');
            }

            // Update setting
            $stmt = $db->prepare("SELECT COUNT(*) FROM settings WHERE key_name = 'default_location_id'");
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                $stmt = $db->prepare("UPDATE settings SET value_val = ? WHERE key_name = 'default_location_id'");
            } else {
                $stmt = $db->prepare("INSERT INTO settings (value_val, key_name) VALUES (?, 'default_location_id')");
            }
            $stmt->execute([strval($locationId)]);

            sendAdminResponse('success', 'Lokasi default utama berhasil diubah.');
        } catch (PDOException $e) {
            sendAdminResponse('error', 'Gagal mengubah lokasi default: ' . $e->getMessage());
        }
        break;

    case 'get_dashboard_data':
        try {
            // 1. Ringkasan Statistik Utama
            // Total Pelari Terdaftar
            $stmt = $db->query("SELECT COUNT(*) as total FROM users");
            $totalRunners = $stmt->fetch()['total'] ?? 0;

            // Pelari Aktif (yang sedang berjalan/started)
            $stmt = $db->query("SELECT COUNT(*) as total FROM runs WHERE status = 'started'");
            $activeRunners = $stmt->fetch()['total'] ?? 0;

            // Total Sesi Lari Selesai
            $stmt = $db->query("SELECT COUNT(*) as total FROM runs WHERE status = 'completed'");
            $completedRuns = $stmt->fetch()['total'] ?? 0;

            // Rata-rata Durasi Lari
            $stmt = $db->query("SELECT AVG(post_duration) as avg_dur FROM runs WHERE status = 'completed'");
            $avgDuration = round($stmt->fetch()['avg_dur'] ?? 0, 1);

            // Rata-rata Jarak Tempuh
            $stmt = $db->query("SELECT AVG(post_distance) as avg_dist FROM runs WHERE status = 'completed'");
            $avgDistance = round($stmt->fetch()['avg_dist'] ?? 0, 2);

            // Rata-rata Kalori Terbakar
            $stmt = $db->query("SELECT AVG(post_calories) as avg_cal FROM runs WHERE status = 'completed'");
            $avgCalories = round($stmt->fetch()['avg_cal'] ?? 0, 1);

            // 2. Daftar Semua Pelari (Untuk Mapping dan Table) beserta info lokasi terakhir
            $stmt = $db->query("SELECT u.id, u.fullname, u.dob, u.gender, u.address, u.phone, u.email, u.created_at,
                                       (SELECT location_id FROM runs WHERE user_id = u.id ORDER BY id DESC LIMIT 1) as last_location_id,
                                       l.name as location_name, l.latitude as location_lat, l.longitude as location_lng
                FROM users u
                LEFT JOIN locations l ON (SELECT location_id FROM runs WHERE user_id = u.id ORDER BY id DESC LIMIT 1) = l.id
                ORDER BY u.created_at DESC");
            $runners = $stmt->fetchAll();

            // Hitung Usia untuk masing-masing pelari
            $today = new DateTime();
            foreach ($runners as &$r) {
                $birth = new DateTime($r['dob']);
                $r['age'] = $today->diff($birth)->y;
            }
            unset($r);

            // 3. Distribusi Alamat Pelari (Pemetaan)
            $stmt = $db->query("SELECT address, COUNT(*) as count FROM users GROUP BY address ORDER BY count DESC");
            $addressDistribution = $stmt->fetchAll();

            // 4. Riwayat Aktivitas Lari Terakhir (Limit 15)
            $stmt = $db->query("SELECT r.*, u.fullname, u.username, u.gender, u.dob, l.name as location_name, l.latitude as location_lat, l.longitude as location_lng, l.google_maps_url
                FROM runs r 
                JOIN users u ON r.user_id = u.id 
                LEFT JOIN locations l ON r.location_id = l.id
                WHERE r.status = 'completed' 
                ORDER BY r.post_time DESC 
                LIMIT 15");
            $recentRuns = $stmt->fetchAll();

            // Tambahkan umur saat lari pada riwayat aktivitas
            foreach ($recentRuns as &$run) {
                $birth = new DateTime($run['dob']);
                $runDate = new DateTime($run['post_time']);
                $run['age'] = $runDate->diff($birth)->y;
            }
            unset($run);

            // 5. Leaderboard (Top 5 pelari berdasarkan jarak tempuh total)
            $leaderboardDistanceQuery = "SELECT u.id, u.fullname, u.gender, SUM(r.post_distance) as total_distance, COUNT(r.id) as total_runs
                FROM runs r
                JOIN users u ON r.user_id = u.id
                WHERE r.status = 'completed'
                GROUP BY u.id, u.fullname, u.gender
                ORDER BY total_distance DESC
                LIMIT 5";
            $stmt = $db->query($leaderboardDistanceQuery);
            $leaderboardDistance = $stmt->fetchAll();

            // Leaderboard (Top 5 pelari berdasarkan jumlah lap total)
            $leaderboardLapsQuery = "SELECT u.id, u.fullname, u.gender, SUM(r.post_laps) as total_laps, COUNT(r.id) as total_runs
                FROM runs r
                JOIN users u ON r.user_id = u.id
                WHERE r.status = 'completed'
                GROUP BY u.id, u.fullname, u.gender
                ORDER BY total_laps DESC
                LIMIT 5";
            $stmt = $db->query($leaderboardLapsQuery);
            $leaderboardLaps = $stmt->fetchAll();

            $todayStart = date('Y-m-d 00:00:00');
            $stmtAlerts = $db->prepare("SELECT r.*, u.fullname, u.phone, u.gender, u.dob
                FROM runs r
                JOIN users u ON r.user_id = u.id
                WHERE r.pre_time >= ? AND r.medical_status = 'pending'
                ORDER BY r.pre_time DESC");
            $stmtAlerts->execute([$todayStart]);
            $todayRuns = $stmtAlerts->fetchAll();

            $bpNormalSys = floatval(getSetting('ind_bp_normal_sys', '120'));
            $bpNormalDia = floatval(getSetting('ind_bp_normal_dia', '80'));
            $hrMin = floatval(getSetting('ind_hr_min', '60'));
            $hrMax = floatval(getSetting('ind_hr_max', '100'));
            $spo2Min = floatval(getSetting('ind_spo2_min', '95'));
            $tempMin = floatval(getSetting('ind_temp_min', '36.5'));
            $tempMax = floatval(getSetting('ind_temp_max', '37.5'));
            $cholMax = floatval(getSetting('ind_chol_max', '200'));

            $medicalAlerts = [];
            foreach ($todayRuns as $run) {
                $warnings = [];
                
                // Cek Blood Pressure
                if ($run['pre_systolic'] > $bpNormalSys || $run['pre_diastolic'] > $bpNormalDia) {
                    $warnings[] = "Tensi Tinggi: " . $run['pre_systolic'] . "/" . $run['pre_diastolic'] . " mmHg";
                }
                // Cek Heart Rate
                if ($run['pre_heart_rate'] < $hrMin || $run['pre_heart_rate'] > $hrMax) {
                    $warnings[] = "Detak Jantung Awal Abnormal: " . $run['pre_heart_rate'] . " bpm";
                }
                // Cek SpO2
                if ($run['pre_spo2'] < $spo2Min) {
                    $warnings[] = "Saturasi Oksigen Rendah: " . $run['pre_spo2'] . "%";
                }
                // Cek Suhu Tubuh
                if ($run['pre_temperature'] < $tempMin || $run['pre_temperature'] > $tempMax) {
                    $warnings[] = "Suhu Tubuh Abnormal: " . $run['pre_temperature'] . "°C";
                }
                // Cek Kolesterol
                if ($run['pre_cholesterol'] >= $cholMax) {
                    $warnings[] = "Kolesterol Tinggi: " . $run['pre_cholesterol'] . " mg/dL";
                }

                if (!empty($warnings)) {
                    $birth = new DateTime($run['dob']);
                    $today = new DateTime();
                    $age = $today->diff($birth)->y;
                    
                    $medicalAlerts[] = [
                        'id' => $run['id'],
                        'user_id' => $run['user_id'],
                        'fullname' => $run['fullname'],
                        'gender' => $run['gender'],
                        'age' => $age,
                        'phone' => $run['phone'],
                        'status' => $run['status'],
                        'warnings' => $warnings,
                        'created_at' => $run['pre_time']
                    ];
                }
            }

            // 6. Get all locations with user stats
            $stmtLoc = $db->query("SELECT id, name, address, latitude, longitude, google_maps_url,
                                          (SELECT COUNT(DISTINCT user_id) FROM runs WHERE location_id = locations.id) as user_count,
                                          (SELECT COUNT(*) FROM runs WHERE location_id = locations.id) as run_count
                                   FROM locations ORDER BY name ASC");
            $locations = $stmtLoc->fetchAll();

            // Pelari Aktif (yang sedang berjalan/started) beserta lokasi dan infonya
            $stmtActive = $db->query("SELECT r.id as run_id, r.user_id, r.location_id, u.fullname, u.gender, u.dob, u.phone, u.address,
                                       l.name as location_name, l.latitude as location_lat, l.longitude as location_lng, l.google_maps_url
                FROM runs r
                JOIN users u ON r.user_id = u.id
                LEFT JOIN locations l ON r.location_id = l.id
                WHERE r.status = 'started'");
            $activeRunnersList = $stmtActive->fetchAll();
            
            // Hitung usia pelari aktif
            foreach ($activeRunnersList as &$ar) {
                $birth = new DateTime($ar['dob']);
                $ar['age'] = $today->diff($birth)->y;
            }
            unset($ar);

            // 7. Statistik Medis Keseluruhan untuk Analisis Edukasi (semua runs)
            $stmtVitals = $db->query("SELECT pre_systolic, pre_diastolic, pre_heart_rate, pre_spo2, pre_temperature, pre_cholesterol FROM runs");
            $allVitals = $stmtVitals->fetchAll();

            $issues = [
                'high_bp' => 0,
                'abnormal_hr' => 0,
                'low_spo2' => 0,
                'abnormal_temp' => 0,
                'high_chol' => 0
            ];

            foreach ($allVitals as $v) {
                if ($v['pre_systolic'] > $bpNormalSys || $v['pre_diastolic'] > $bpNormalDia) {
                    $issues['high_bp']++;
                }
                if ($v['pre_heart_rate'] < $hrMin || $v['pre_heart_rate'] > $hrMax) {
                    $issues['abnormal_hr']++;
                }
                if ($v['pre_spo2'] < $spo2Min) {
                    $issues['low_spo2']++;
                }
                if ($v['pre_temperature'] < $tempMin || $v['pre_temperature'] > $tempMax) {
                    $issues['abnormal_temp']++;
                }
                if ($v['pre_cholesterol'] >= $cholMax) {
                    $issues['high_chol']++;
                }
            }

            $defaultLocationId = intval(getSetting('default_location_id', '1'));

            sendAdminResponse('success', 'Data dashboard admin berhasil dimuat', [
                'stats' => [
                    'total_runners' => $totalRunners,
                    'active_runners' => $activeRunners,
                    'completed_runs' => $completedRuns,
                    'avg_duration' => $avgDuration,
                    'avg_distance' => $avgDistance,
                    'avg_calories' => $avgCalories
                ],
                'runners' => $runners,
                'address_distribution' => $addressDistribution,
                'recent_runs' => $recentRuns,
                'leaderboard_distance' => $leaderboardDistance,
                'leaderboard_laps' => $leaderboardLaps,
                'medical_alerts' => $medicalAlerts,
                'medical_issues' => $issues,
                'total_runs_analyzed' => count($allVitals),
                'locations' => $locations,
                'active_runners_list' => $activeRunnersList,
                'default_location_id' => $defaultLocationId
            ]);
        } catch (PDOException $e) {
            sendAdminResponse('error', 'Gagal memuat data: ' . $e->getMessage());
        }
        break;

    case 'approve_medical_alert':
        $runId = intval($requestData['run_id'] ?? 0);
        if ($runId <= 0) {
            sendAdminResponse('error', 'ID Sesi Lari tidak valid.');
        }

        try {
            $stmt = $db->prepare("UPDATE runs SET medical_status = 'approved' WHERE id = ?");
            $stmt->execute([$runId]);
            sendAdminResponse('success', 'Persetujuan alarm medis berhasil diproses.');
        } catch (PDOException $e) {
            sendAdminResponse('error', 'Gagal memproses persetujuan alarm: ' . $e->getMessage());
        }
        break;

    case 'get_runner_details':
        $runnerId = intval($requestData['runner_id'] ?? 0);
        if ($runnerId <= 0) {
            sendAdminResponse('error', 'ID Pelari tidak valid.');
        }

        try {
            // Data Profil Pelari
            $stmt = $db->prepare("SELECT id, fullname, dob, gender, address, phone, email, created_at FROM users WHERE id = ?");
            $stmt->execute([$runnerId]);
            $runner = $stmt->fetch();

            if (!$runner) {
                sendAdminResponse('error', 'Pelari tidak ditemukan.');
            }

            // Hitung usia
            $birth = new DateTime($runner['dob']);
            $today = new DateTime();
            $runner['age'] = $today->diff($birth)->y;

            // Histori Aktivitas Lari Pelari Tersebut
            $stmtRuns = $db->prepare("SELECT r.*, l.name as location_name, l.latitude as location_lat, l.longitude as location_lng, l.google_maps_url
                FROM runs r
                LEFT JOIN locations l ON r.location_id = l.id
                WHERE r.user_id = ? AND r.status = 'completed'
                ORDER BY r.post_time DESC");
            $stmtRuns->execute([$runnerId]);
            $history = $stmtRuns->fetchAll();

            sendAdminResponse('success', 'Detail pelari berhasil dimuat', [
                'runner' => $runner,
                'history' => $history
            ]);
        } catch (PDOException $e) {
            sendAdminResponse('error', 'Gagal memuat detail pelari: ' . $e->getMessage());
        }
        break;

    case 'update_admin_profile':
        $newUsername = trim($requestData['username'] ?? '');
        $oldPassword = $requestData['old_password'] ?? '';
        $newPassword = $requestData['new_password'] ?? '';
        $confirmPassword = $requestData['confirm_password'] ?? '';

        if (empty($newUsername)) {
            sendAdminResponse('error', 'Username baru tidak boleh kosong.');
        }

        $currentPassHash = getSetting('admin_pass');
        if (empty($currentPassHash)) {
            $currentPassHash = password_hash(ADMIN_PASSWORD, PASSWORD_BCRYPT);
        }

        if (!password_verify($oldPassword, $currentPassHash)) {
            sendAdminResponse('error', 'Password saat ini salah.');
        }

        try {
            $stmt = $db->prepare("UPDATE settings SET value_val = ? WHERE key_name = 'admin_user'");
            $stmt->execute([$newUsername]);
            $_SESSION['admin_username'] = $newUsername;

            if (!empty($newPassword)) {
                if (strlen($newPassword) < 6) {
                    sendAdminResponse('error', 'Password baru minimal harus 6 karakter.');
                }
                if ($newPassword !== $confirmPassword) {
                    sendAdminResponse('error', 'Konfirmasi password baru tidak cocok.');
                }
                
                $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $stmt = $db->prepare("UPDATE settings SET value_val = ? WHERE key_name = 'admin_pass'");
                $stmt->execute([$newHash]);
            }

            sendAdminResponse('success', 'Profil administrator berhasil diperbarui.');
        } catch (PDOException $e) {
            sendAdminResponse('error', 'Gagal memperbarui profil: ' . $e->getMessage());
        }
        break;

    case 'update_app_settings':
        $appName = trim($requestData['app_name'] ?? '');
        $appLogo = trim($requestData['app_logo'] ?? '');

        if (empty($appName)) {
            sendAdminResponse('error', 'Nama aplikasi tidak boleh kosong.');
        }
        if (empty($appLogo)) {
            sendAdminResponse('error', 'Logo aplikasi tidak boleh kosong.');
        }

        try {
            $stmt = $db->prepare("UPDATE settings SET value_val = ? WHERE key_name = 'app_name'");
            $stmt->execute([$appName]);

            $stmt = $db->prepare("UPDATE settings SET value_val = ? WHERE key_name = 'app_logo'");
            $stmt->execute([$appLogo]);

            sendAdminResponse('success', 'Pengaturan branding aplikasi berhasil diperbarui.');
        } catch (PDOException $e) {
            sendAdminResponse('error', 'Gagal memperbarui branding: ' . $e->getMessage());
        }
        break;

    case 'update_health_indicators':
        $keys = [
            'ind_imt_normal_min', 'ind_imt_normal_max', 'ind_imt_normal_score',
            'ind_imt_overweight_min', 'ind_imt_overweight_max', 'ind_imt_overweight_score',
            'ind_imt_underweight_score', 'ind_imt_obese_score',
            'ind_bbi_ideal_min', 'ind_bbi_ideal_max', 'ind_bbi_ideal_score', 'ind_bbi_warning_score', 'ind_bbi_danger_score',
            'ind_bp_normal_sys', 'ind_bp_normal_dia', 'ind_bp_normal_score',
            'ind_bp_warning_sys', 'ind_bp_warning_dia', 'ind_bp_warning_score', 'ind_bp_danger_score',
            'ind_hr_min', 'ind_hr_max', 'ind_hr_normal_score', 'ind_hr_abnormal_score',
            'ind_spo2_min', 'ind_spo2_normal_score', 'ind_spo2_abnormal_score',
            'ind_temp_min', 'ind_temp_max', 'ind_temp_normal_score', 'ind_temp_abnormal_score',
            'ind_chol_max', 'ind_chol_normal_score', 'ind_chol_abnormal_score',
            'ind_target_score'
        ];

        try {
            $stmt = $db->prepare("UPDATE settings SET value_val = ? WHERE key_name = ?");
            foreach ($keys as $key) {
                $val = trim(strval($requestData[$key] ?? ''));
                if ($val === '') {
                    sendAdminResponse('error', 'Semua kolom indikator wajib diisi.');
                }
                $stmt->execute([$val, $key]);
            }
            sendAdminResponse('success', 'Konfigurasi indikator penilaian berhasil disimpan.');
        } catch (PDOException $e) {
            sendAdminResponse('error', 'Gagal menyimpan konfigurasi indikator: ' . $e->getMessage());
        }
        break;

    default:
        sendAdminResponse('error', 'Aksi API admin tidak valid.');
        break;
}
