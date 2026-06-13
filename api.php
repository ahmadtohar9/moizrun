<?php
/**
 * API Endpoint - Running Health Tracker
 * 
 * Menangani semua request AJAX dari frontend (Auth, Input Data Kesehatan, Riwayat).
 * Output selalu berformat JSON.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Ambil input dari JSON raw body jika ada
$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true);

// Gabungkan input dari POST/GET biasa dan JSON
$requestData = array_merge($_GET, $_POST, is_array($jsonData) ? $jsonData : []);

$action = $requestData['action'] ?? '';

$db = getDBConnection();

// Response Helper
function sendResponse($status, $message, $data = []) {
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message
    ], $data));
    exit;
}

// Cek status login helper
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        sendResponse('unauthorized', 'Sesi Anda telah berakhir. Silakan login kembali.');
    }
    return $_SESSION['user_id'];
}

switch ($action) {
    case 'register':
        $fullname = trim($requestData['fullname'] ?? '');
        $dob = trim($requestData['dob'] ?? '');
        $gender = trim($requestData['gender'] ?? '');
        $address = trim($requestData['address'] ?? '');
        $phone = trim($requestData['phone'] ?? '');
        $email = trim($requestData['email'] ?? '');
        $username = strtolower(trim($requestData['username'] ?? ''));
        $password = $requestData['password'] ?? '';

        if (empty($fullname) || empty($dob) || empty($gender) || empty($address) || empty($phone) || empty($username) || empty($password)) {
            sendResponse('error', 'Semua kolom wajib diisi kecuali Email.');
        }

        if ($gender !== 'L' && $gender !== 'P') {
            sendResponse('error', 'Jenis kelamin harus Laki-laki (L) atau Perempuan (P).');
        }

        // Cek username unik
        try {
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                sendResponse('error', 'Username sudah digunakan oleh orang lain.');
            }

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Insert user baru
            $stmtInsert = $db->prepare("INSERT INTO users (fullname, dob, gender, address, phone, email, username, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtInsert->execute([$fullname, $dob, $gender, $address, $phone, empty($email) ? null : $email, $username, $hashedPassword]);

            sendResponse('success', 'Pendaftaran akun berhasil! Silakan login.');
        } catch (PDOException $e) {
            sendResponse('error', 'Gagal mendaftar: ' . $e->getMessage());
        }
        break;

    case 'login':
        $username = strtolower(trim($requestData['username'] ?? ''));
        $password = $requestData['password'] ?? '';

        if (empty($username) || empty($password)) {
            sendResponse('error', 'Username dan password wajib diisi.');
        }

        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                sendResponse('error', 'Username atau password salah.');
            }

            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['gender'] = $user['gender'];
            $_SESSION['dob'] = $user['dob'];

            // Hitung usia
            $birthDate = new DateTime($user['dob']);
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;
            $_SESSION['age'] = $age;

            sendResponse('success', 'Login berhasil!', [
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'fullname' => $user['fullname'],
                    'gender' => $user['gender'],
                    'dob' => $user['dob'],
                    'age' => $age,
                    'phone' => $user['phone'],
                    'email' => $user['email']
                ]
            ]);
        } catch (PDOException $e) {
            sendResponse('error', 'Gagal login: ' . $e->getMessage());
        }
        break;

    case 'verify_recovery':
        $username = strtolower(trim($requestData['username'] ?? ''));
        $phone = trim($requestData['phone'] ?? '');
        $dob = trim($requestData['dob'] ?? '');

        if (empty($username) || empty($phone) || empty($dob)) {
            sendResponse('error', 'Semua kolom verifikasi wajib diisi.');
        }

        try {
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND phone = ? AND dob = ?");
            $stmt->execute([$username, $phone, $dob]);
            $user = $stmt->fetch();

            if (!$user) {
                sendResponse('error', 'Data akun tidak cocok dengan data pendaftaran kami.');
            }

            $_SESSION['recovery_user_id'] = $user['id'];
            sendResponse('success', 'Akun berhasil diverifikasi.');
        } catch (PDOException $e) {
            sendResponse('error', 'Gagal memverifikasi: ' . $e->getMessage());
        }
        break;

    case 'reset_password':
        $password = $requestData['password'] ?? '';

        if (empty($password)) {
            sendResponse('error', 'Password baru wajib diisi.');
        }

        if (!isset($_SESSION['recovery_user_id'])) {
            sendResponse('error', 'Akses ditolak. Silakan lakukan verifikasi ulang.');
        }

        try {
            $userId = $_SESSION['recovery_user_id'];
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);

            unset($_SESSION['recovery_user_id']);
            sendResponse('success', 'Password Anda berhasil disetel ulang. Silakan login menggunakan password baru.');
        } catch (PDOException $e) {
            sendResponse('error', 'Gagal menyetel ulang password: ' . $e->getMessage());
        }
        break;

    case 'logout':
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        sendResponse('success', 'Logout berhasil.');
        break;

    case 'get_user':
        $userId = requireLogin();
        try {
            $stmt = $db->prepare("SELECT id, fullname, dob, gender, address, phone, email, username FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                sendResponse('error', 'User tidak ditemukan.');
            }

            // Hitung usia
            $birthDate = new DateTime($user['dob']);
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;

            sendResponse('success', 'User data loaded', [
                'user' => array_merge($user, ['age' => $age])
            ]);
        } catch (PDOException $e) {
            sendResponse('error', 'Database error: ' . $e->getMessage());
        }
        break;

    case 'get_active_run':
        $userId = requireLogin();
        try {
            $stmt = $db->prepare("SELECT * FROM runs WHERE user_id = ? AND status = 'started' ORDER BY id DESC LIMIT 1");
            $stmt->execute([$userId]);
            $run = $stmt->fetch();
            
            if ($run) {
                sendResponse('success', 'Ada sesi lari aktif', ['active_run' => $run]);
            } else {
                sendResponse('success', 'Tidak ada sesi lari aktif', ['active_run' => null]);
            }
        } catch (PDOException $e) {
            sendResponse('error', 'Database error: ' . $e->getMessage());
        }
        break;

    case 'get_active_locations':
        try {
            $stmt = $db->query("SELECT id, name, address, latitude, longitude, google_maps_url FROM locations ORDER BY name ASC");
            $locations = $stmt->fetchAll();
            $defaultLocationId = intval(getSetting('default_location_id', '1'));
            sendResponse('success', 'Locations loaded', [
                'locations' => $locations,
                'default_location_id' => $defaultLocationId
            ]);
        } catch (PDOException $e) {
            sendResponse('error', 'Database error: ' . $e->getMessage());
        }
        break;

    case 'start_run':
        $userId = requireLogin();
        
        $height = floatval($requestData['height'] ?? 0);
        $weight = floatval($requestData['weight'] ?? 0);
        $heartRate = intval($requestData['heart_rate'] ?? 0);
        $systolic = intval($requestData['systolic'] ?? 0);
        $diastolic = intval($requestData['diastolic'] ?? 0);
        $temperature = floatval($requestData['temperature'] ?? 0);
        $cholesterol = intval($requestData['cholesterol'] ?? 0);
        $spo2 = intval($requestData['spo2'] ?? 0);
        $defaultLocId = intval(getSetting('default_location_id', '1'));
        $locationId = intval($requestData['location_id'] ?? $defaultLocId);
        
        $targetType = trim($requestData['target_type'] ?? 'none');
        $targetValue = intval($requestData['target_value'] ?? 0);

        if ($height <= 0 || $weight <= 0 || $heartRate <= 0 || $systolic <= 0 || $diastolic <= 0 || $temperature <= 0 || $cholesterol < 0 || $spo2 <= 0 || $locationId <= 0) {
            sendResponse('error', 'Harap isi semua metrik kesehatan dan pilih lokasi lari yang valid.');
        }

        try {
            // Bersihkan sesi lari yang belum selesai sebelumnya
            $stmtClean = $db->prepare("DELETE FROM runs WHERE user_id = ? AND status = 'started'");
            $stmtClean->execute([$userId]);

            // Evaluasi apakah vital signs berada di luar batas normal
            $bpNormalSys = floatval(getSetting('ind_bp_normal_sys', '120'));
            $bpNormalDia = floatval(getSetting('ind_bp_normal_dia', '80'));
            $hrMin = floatval(getSetting('ind_hr_min', '60'));
            $hrMax = floatval(getSetting('ind_hr_max', '100'));
            $spo2Min = floatval(getSetting('ind_spo2_min', '95'));
            $tempMin = floatval(getSetting('ind_temp_min', '36.5'));
            $tempMax = floatval(getSetting('ind_temp_max', '37.5'));
            $cholMax = floatval(getSetting('ind_chol_max', '200'));

            $hasWarning = false;
            if ($systolic > $bpNormalSys || $diastolic > $bpNormalDia ||
                $heartRate < $hrMin || $heartRate > $hrMax ||
                $spo2 < $spo2Min ||
                $temperature < $tempMin || $temperature > $tempMax ||
                $cholesterol >= $cholMax) {
                $hasWarning = true;
            }
            $medicalStatus = $hasWarning ? 'pending' : 'normal';

            // Insert sesi baru dengan target, medical_status, dan location_id
            $stmt = $db->prepare("INSERT INTO runs 
                (user_id, status, pre_height, pre_weight, pre_heart_rate, pre_systolic, pre_diastolic, pre_temperature, pre_cholesterol, pre_spo2, pre_time, target_type, target_value, medical_status, location_id) 
                VALUES (?, 'started', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $currentTime = date('Y-m-d H:i:s');
            $stmt->execute([
                $userId, $height, $weight, $heartRate, $systolic, $diastolic, $temperature, $cholesterol, $spo2, $currentTime, $targetType, $targetValue, $medicalStatus, $locationId
            ]);

            $runId = $db->lastInsertId();
            sendResponse('success', 'Sesi lari berhasil dimulai. Selamat berolahraga!', ['run_id' => $runId]);
        } catch (PDOException $e) {
            sendResponse('error', 'Gagal memulai sesi lari: ' . $e->getMessage());
        }
        break;

    case 'complete_run':
        $userId = requireLogin();
        
        $runId = intval($requestData['run_id'] ?? 0);
        $heartRate = intval($requestData['heart_rate'] ?? 0);
        $systolic = isset($requestData['systolic']) && $requestData['systolic'] !== '' ? intval($requestData['systolic']) : null;
        $diastolic = isset($requestData['diastolic']) && $requestData['diastolic'] !== '' ? intval($requestData['diastolic']) : null;
        $temperature = isset($requestData['temperature']) && $requestData['temperature'] !== '' ? floatval($requestData['temperature']) : null;
        $spo2 = isset($requestData['spo2']) && $requestData['spo2'] !== '' ? intval($requestData['spo2']) : null;
        $duration = isset($requestData['duration']) && $requestData['duration'] !== '' ? intval($requestData['duration']) : null;
        
        $laps = intval($requestData['laps'] ?? 0);
        $distance = floatval($requestData['distance'] ?? 0.0);
        $calories = intval($requestData['calories'] ?? 0);

        if ($runId <= 0 || $heartRate <= 0) {
            sendResponse('error', 'Parameter tidak lengkap. Harap isi denyut nadi setelah lari.');
        }

        try {
            // Validasi sesi lari
            $stmtCheck = $db->prepare("SELECT * FROM runs WHERE id = ? AND user_id = ? AND status = 'started'");
            $stmtCheck->execute([$runId, $userId]);
            $run = $stmtCheck->fetch();

            if (!$run) {
                sendResponse('error', 'Sesi lari aktif tidak ditemukan atau sudah selesai.');
            }

            // Update sesi menjadi selesai dengan data laps, jarak, kalori
            $stmtUpdate = $db->prepare("UPDATE runs SET 
                status = 'completed', 
                post_heart_rate = ?, 
                post_systolic = ?, 
                post_diastolic = ?, 
                post_temperature = ?, 
                post_spo2 = ?, 
                post_duration = ?, 
                post_laps = ?,
                post_distance = ?,
                post_calories = ?,
                post_time = ? 
                WHERE id = ?");
            
            $currentTime = date('Y-m-d H:i:s');
            $stmtUpdate->execute([
                $heartRate, $systolic, $diastolic, $temperature, $spo2, $duration, $laps, $distance, $calories, $currentTime, $runId
            ]);

            sendResponse('success', 'Sesi lari berhasil diselesaikan! Lihat analisis kesehatan Anda.');
        } catch (PDOException $e) {
            sendResponse('error', 'Gagal menyelesaikan sesi lari: ' . $e->getMessage());
        }
        break;

    case 'get_history':
        $userId = requireLogin();
        try {
            // Ambil detail riwayat beserta data user (terutama dob & gender untuk kalkulasi di frontend atau backend)
            $stmt = $db->prepare("SELECT r.*, u.dob, u.gender, l.name as location_name, l.latitude as location_lat, l.longitude as location_lng, l.google_maps_url
                FROM runs r 
                JOIN users u ON r.user_id = u.id 
                LEFT JOIN locations l ON r.location_id = l.id
                WHERE r.user_id = ? AND r.status = 'completed' 
                ORDER BY r.post_time DESC");
            $stmt->execute([$userId]);
            $history = $stmt->fetchAll();

            sendResponse('success', 'Riwayat berhasil dimuat', ['history' => $history]);
        } catch (PDOException $e) {
            sendResponse('error', 'Gagal memuat riwayat: ' . $e->getMessage());
        }
        break;

    case 'get_run_detail':
        $userId = requireLogin();
        $runId = intval($requestData['run_id'] ?? 0);

        if ($runId <= 0) {
            sendResponse('error', 'Sesi lari tidak valid.');
        }

        try {
            $stmt = $db->prepare("SELECT r.*, u.dob, u.gender, l.name as location_name, l.latitude as location_lat, l.longitude as location_lng, l.google_maps_url
                FROM runs r 
                JOIN users u ON r.user_id = u.id 
                LEFT JOIN locations l ON r.location_id = l.id
                WHERE r.id = ? AND r.user_id = ?");
            $stmt->execute([$runId, $userId]);
            $run = $stmt->fetch();

            if (!$run) {
                sendResponse('error', 'Sesi lari tidak ditemukan.');
            }

            sendResponse('success', 'Detail sesi lari berhasil dimuat', ['run' => $run]);
        } catch (PDOException $e) {
            sendResponse('error', 'Gagal memuat detail sesi lari: ' . $e->getMessage());
        }
        break;

    case 'get_indicators':
        try {
            $stmt = $db->query("SELECT key_name, value_val FROM settings WHERE key_name LIKE 'ind_%'");
            $rows = $stmt->fetchAll();
            $indicators = [];
            foreach ($rows as $row) {
                $indicators[$row['key_name']] = floatval($row['value_val']);
            }
            sendResponse('success', 'Indikator berhasil dimuat', ['indicators' => $indicators]);
        } catch (PDOException $e) {
            sendResponse('error', 'Gagal memuat indikator: ' . $e->getMessage());
        }
        break;

    default:
        sendResponse('error', 'Aksi API tidak valid.');
        break;
}
