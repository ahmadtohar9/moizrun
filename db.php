<?php
/**
 * Koneksi Database & Inisialisasi - Running Health Tracker
 * 
 * Otomatis mendeteksi driver (SQLite/MySQL) dan membuat tabel-tabel
 * yang dibutuhkan jika belum ada.
 */

require_once __DIR__ . '/config.php';

function getDBConnection() {
    static $pdo = null;
    
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        if (DB_DRIVER === 'sqlite') {
            // Koneksi SQLite
            $dsn = "sqlite:" . SQLITE_FILE;
            $pdo = new PDO($dsn);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            // Aktifkan Foreign Key di SQLite
            $pdo->exec("PRAGMA foreign_keys = ON;");
        } else {
            // Koneksi MySQL
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            // Coba koneksi ke server MySQL dahulu untuk memastikan database ada
            try {
                $tempDsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
                $tempPdo = new PDO($tempDsn, DB_USER, DB_PASS, $options);
                $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET " . DB_CHARSET . " COLLATE utf8mb4_general_ci;");
                $tempPdo = null; // Tutup koneksi sementara
            } catch (PDOException $e) {
                // Biarkan koneksi utama yang menghandle error jika pembuatan database gagal
            }
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        }
        
        // Inisialisasi struktur tabel
        initializeDatabase($pdo);
        
        return $pdo;
    } catch (PDOException $e) {
        die(json_encode([
            'status' => 'error',
            'message' => 'Koneksi database gagal: ' . $e->getMessage()
        ]));
    }
}

function initializeDatabase($pdo) {
    $isSQLite = (DB_DRIVER === 'sqlite');
    
    $pkSyntax = $isSQLite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
    $textSyntax = $isSQLite ? 'TEXT' : 'VARCHAR(255)';
    $longTextSyntax = $isSQLite ? 'TEXT' : 'TEXT';
    
    // 1. Buat Tabel Users
    $sqlUsers = "CREATE TABLE IF NOT EXISTS users (
        id $pkSyntax,
        fullname VARCHAR(100) NOT NULL,
        dob DATE NOT NULL,
        gender VARCHAR(1) NOT NULL, -- L / P
        address $longTextSyntax NOT NULL,
        phone VARCHAR(20) NOT NULL,
        email VARCHAR(100),
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );";
    $pdo->exec($sqlUsers);
    
    // 2. Buat Tabel Runs
    $sqlRuns = "CREATE TABLE IF NOT EXISTS runs (
        id $pkSyntax,
        user_id INTEGER NOT NULL,
        status VARCHAR(20) NOT NULL, -- 'started', 'completed'
        pre_height FLOAT NOT NULL,
        pre_weight FLOAT NOT NULL,
        pre_heart_rate INTEGER NOT NULL,
        pre_systolic INTEGER NOT NULL,
        pre_diastolic INTEGER NOT NULL,
        pre_temperature FLOAT NOT NULL,
        pre_cholesterol INTEGER NOT NULL,
        pre_spo2 INTEGER NOT NULL,
        pre_time DATETIME NOT NULL,
        target_type VARCHAR(20) DEFAULT 'none',
        target_value INTEGER DEFAULT 0,
        post_heart_rate INTEGER,
        post_systolic INTEGER,
        post_diastolic INTEGER,
        post_temperature FLOAT,
        post_spo2 INTEGER,
        post_duration INTEGER, -- dalam menit
        post_laps INTEGER DEFAULT 0,
        post_distance FLOAT DEFAULT 0,
        post_calories INTEGER DEFAULT 0,
        post_time DATETIME,
        medical_status VARCHAR(20) DEFAULT 'normal',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );";
    $pdo->exec($sqlRuns);
    
    // 3. Buat Tabel Settings
    $sqlSettings = "CREATE TABLE IF NOT EXISTS settings (
        key_name VARCHAR(50) PRIMARY KEY,
        value_val TEXT NOT NULL
    );";
    $pdo->exec($sqlSettings);
    
    // 4. Buat Tabel Locations
    $sqlLocations = "CREATE TABLE IF NOT EXISTS locations (
        id $pkSyntax,
        name VARCHAR(100) NOT NULL,
        address $longTextSyntax NOT NULL,
        latitude FLOAT NOT NULL,
        longitude FLOAT NOT NULL,
        google_maps_url $longTextSyntax,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );";
    $pdo->exec($sqlLocations);
    
    // Seed default settings jika belum ada (diperiksa satu per satu)
    $defaultSettings = [
        'app_name' => 'Stadion Run',
        'app_logo' => '🏃',
        'admin_user' => 'admin',
        'admin_pass' => password_hash('adminStadion123', PASSWORD_BCRYPT),
        'default_location_id' => '1',
        
        // Indikator Penilaian IMT
        'ind_imt_normal_min' => '18.5',
        'ind_imt_normal_max' => '22.9',
        'ind_imt_normal_score' => '20',
        'ind_imt_overweight_min' => '23.0',
        'ind_imt_overweight_max' => '24.9',
        'ind_imt_overweight_score' => '15',
        'ind_imt_underweight_score' => '10',
        'ind_imt_obese_score' => '5',
        
        // Indikator Penilaian BBI
        'ind_bbi_ideal_min' => '90',
        'ind_bbi_ideal_max' => '110',
        'ind_bbi_ideal_score' => '15',
        'ind_bbi_warning_score' => '10',
        'ind_bbi_danger_score' => '5',
        
        // Indikator Penilaian Tekanan Darah (Tensi)
        'ind_bp_normal_sys' => '120',
        'ind_bp_normal_dia' => '80',
        'ind_bp_normal_score' => '15',
        'ind_bp_warning_sys' => '139',
        'ind_bp_warning_dia' => '89',
        'ind_bp_warning_score' => '10',
        'ind_bp_danger_score' => '5',
        
        // Indikator Penilaian Detak Jantung Awal (Resting HR)
        'ind_hr_min' => '60',
        'ind_hr_max' => '100',
        'ind_hr_normal_score' => '15',
        'ind_hr_abnormal_score' => '8',
        
        // Indikator Penilaian Oksigen Darah (SpO2)
        'ind_spo2_min' => '95',
        'ind_spo2_normal_score' => '15',
        'ind_spo2_abnormal_score' => '5',
        
        // Indikator Penilaian Suhu Tubuh
        'ind_temp_min' => '36.5',
        'ind_temp_max' => '37.5',
        'ind_temp_normal_score' => '10',
        'ind_temp_abnormal_score' => '5',
        
        // Indikator Penilaian Kolesterol
        'ind_chol_max' => '200',
        'ind_chol_normal_score' => '10',
        'ind_chol_abnormal_score' => '5',
        
        // Indikator Penilaian Target Lari
        'ind_target_score' => '10'
    ];
    
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) as count FROM settings WHERE key_name = ?");
    $stmtInsert = $pdo->prepare("INSERT INTO settings (key_name, value_val) VALUES (?, ?)");
    foreach ($defaultSettings as $key => $val) {
        $stmtCheck->execute([$key]);
        if ($stmtCheck->fetch()['count'] == 0) {
            $stmtInsert->execute([$key, $val]);
        }
    }

    // Seed default locations jika belum ada
    $stmtCheckLoc = $pdo->query("SELECT COUNT(*) as count FROM locations");
    if ($stmtCheckLoc->fetch()['count'] == 0) {
        $defaultLocations = [
            ['name' => 'Stadion Gelora Bung Tomo', 'address' => 'Pakal, Surabaya', 'latitude' => -7.2285, 'longitude' => 112.6322, 'google_maps_url' => 'https://maps.google.com/?q=-7.2285,112.6322'],
            ['name' => 'Stadion Gelora 10 Nopember', 'address' => 'Tambaksari, Surabaya', 'latitude' => -7.2520, 'longitude' => 112.7578, 'google_maps_url' => 'https://maps.google.com/?q=-7.2520,112.7578'],
            ['name' => 'Lapangan THOR', 'address' => 'Wonokromo, Surabaya', 'latitude' => -7.2891, 'longitude' => 112.7354, 'google_maps_url' => 'https://maps.google.com/?q=-7.2891,112.7354']
        ];
        $stmtInsertLoc = $pdo->prepare("INSERT INTO locations (name, address, latitude, longitude, google_maps_url) VALUES (?, ?, ?, ?, ?)");
        foreach ($defaultLocations as $loc) {
            $stmtInsertLoc->execute([$loc['name'], $loc['address'], $loc['latitude'], $loc['longitude'], $loc['google_maps_url']]);
        }
    }
    
    // Migrasi Kolom Tambahan (Untuk DB yang sudah terlanjur dibuat sebelumnya)
    $columnsToMigrate = [
        'target_type' => "VARCHAR(20) DEFAULT 'none'",
        'target_value' => "INTEGER DEFAULT 0",
        'post_laps' => "INTEGER DEFAULT 0",
        'post_distance' => "FLOAT DEFAULT 0",
        'post_calories' => "INTEGER DEFAULT 0",
        'medical_status' => "VARCHAR(20) DEFAULT 'normal'",
        'location_id' => "INTEGER DEFAULT 1"
    ];
    
    foreach ($columnsToMigrate as $colName => $colDef) {
        try {
            $pdo->exec("ALTER TABLE runs ADD COLUMN {$colName} {$colDef};");
        } catch (PDOException $e) {
            // Abaikan jika kolom sudah ada
        }
    }
}

/**
 * Helper global untuk mengambil nilai pengaturan aplikasi dari database settings.
 */
function getSetting($key, $default = '') {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT value_val FROM settings WHERE key_name = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['value_val'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}
