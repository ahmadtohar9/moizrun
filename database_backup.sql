-- Running Health Tracker Database Backup
-- Generated on: 2026-06-09 23:51:01
PRAGMA foreign_keys=OFF;
BEGIN TRANSACTION;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        fullname VARCHAR(100) NOT NULL,
        dob DATE NOT NULL,
        gender VARCHAR(1) NOT NULL, -- L / P
        address TEXT NOT NULL,
        phone VARCHAR(20) NOT NULL,
        email VARCHAR(100),
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

-- Dumping data for table `users`
INSERT INTO `users` (`id`, `fullname`, `dob`, `gender`, `address`, `phone`, `email`, `username`, `password`, `created_at`) VALUES ('1', 'Ahmad Tohar', '1993-09-20', 'L', 'Jln. Rawa Bening', '085365811832', NULL, 'tohar', '$2y$10$GumTdsvaP4eVrw.Qq6yhB.C2Oc9q6yJ4Hb/h9ohjbOTRvWG/62sEa', '2026-06-09 12:10:20');

-- --------------------------------------------------------
-- Table structure for table `runs`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `runs`;
CREATE TABLE runs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
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
        post_heart_rate INTEGER,
        post_systolic INTEGER,
        post_diastolic INTEGER,
        post_temperature FLOAT,
        post_spo2 INTEGER,
        post_duration INTEGER, -- dalam menit
        post_time DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, target_type VARCHAR(20) DEFAULT 'none', target_value INTEGER DEFAULT 0, post_laps INTEGER DEFAULT 0, post_distance FLOAT DEFAULT 0, post_calories INTEGER DEFAULT 0, medical_status VARCHAR(20) DEFAULT 'normal', location_id INTEGER DEFAULT 1,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

-- Dumping data for table `runs`
INSERT INTO `runs` (`id`, `user_id`, `status`, `pre_height`, `pre_weight`, `pre_heart_rate`, `pre_systolic`, `pre_diastolic`, `pre_temperature`, `pre_cholesterol`, `pre_spo2`, `pre_time`, `post_heart_rate`, `post_systolic`, `post_diastolic`, `post_temperature`, `post_spo2`, `post_duration`, `post_time`, `created_at`, `target_type`, `target_value`, `post_laps`, `post_distance`, `post_calories`, `medical_status`, `location_id`) VALUES ('1', '1', 'completed', '170.0', '60.0', '100', '120', '80', '36.0', '100', '95', '2026-06-09 19:11:15', '150', '80', '70', '37.0', '150', '120', '2026-06-09 19:11:49', '2026-06-09 12:11:15', 'none', '0', '0', '0.0', '0', 'normal', '1');
INSERT INTO `runs` (`id`, `user_id`, `status`, `pre_height`, `pre_weight`, `pre_heart_rate`, `pre_systolic`, `pre_diastolic`, `pre_temperature`, `pre_cholesterol`, `pre_spo2`, `pre_time`, `post_heart_rate`, `post_systolic`, `post_diastolic`, `post_temperature`, `post_spo2`, `post_duration`, `post_time`, `created_at`, `target_type`, `target_value`, `post_laps`, `post_distance`, `post_calories`, `medical_status`, `location_id`) VALUES ('2', '1', 'completed', '170.0', '65.0', '100', '120', '80', '36.5', '100', '95', '2026-06-09 19:21:47', '100', '150', '130', '38.0', '45', '60', '2026-06-09 19:27:33', '2026-06-09 12:21:47', 'time', '30', '5', '2.0', '47', 'normal', '1');
INSERT INTO `runs` (`id`, `user_id`, `status`, `pre_height`, `pre_weight`, `pre_heart_rate`, `pre_systolic`, `pre_diastolic`, `pre_temperature`, `pre_cholesterol`, `pre_spo2`, `pre_time`, `post_heart_rate`, `post_systolic`, `post_diastolic`, `post_temperature`, `post_spo2`, `post_duration`, `post_time`, `created_at`, `target_type`, `target_value`, `post_laps`, `post_distance`, `post_calories`, `medical_status`, `location_id`) VALUES ('3', '1', 'completed', '170.0', '60.0', '80', '100', '58', '36.0', '100', '95', '2026-06-09 22:44:08', '300', '150', '250', '40.0', '200', '150', '2026-06-09 22:46:14', '2026-06-09 15:44:08', 'laps', '3', '1', '0.4', '14', 'normal', '1');
INSERT INTO `runs` (`id`, `user_id`, `status`, `pre_height`, `pre_weight`, `pre_heart_rate`, `pre_systolic`, `pre_diastolic`, `pre_temperature`, `pre_cholesterol`, `pre_spo2`, `pre_time`, `post_heart_rate`, `post_systolic`, `post_diastolic`, `post_temperature`, `post_spo2`, `post_duration`, `post_time`, `created_at`, `target_type`, `target_value`, `post_laps`, `post_distance`, `post_calories`, `medical_status`, `location_id`) VALUES ('4', '1', 'completed', '100.0', '65.0', '60', '100', '80', '36.5', '150', '95', '2026-06-09 23:27:14', '200', '250', '250', '40.0', '200', '20', '2026-06-09 23:27:55', '2026-06-09 16:27:14', 'time', '30', '1', '0.4', '1', 'normal', '4');

-- --------------------------------------------------------
-- Table structure for table `settings`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE settings (
        key_name VARCHAR(50) PRIMARY KEY,
        value_val TEXT NOT NULL
    );

-- Dumping data for table `settings`
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('app_name', 'Moiz Run');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('app_logo', '🏃');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('admin_user', 'admin');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('admin_pass', '$2y$10$xhQJnlh5UpK0sMl3y4I8OeH4egyJo.PE3XGA1pQimCsBWnShORDC.');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_imt_normal_min', '18.5');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_imt_normal_max', '22.9');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_imt_normal_score', '20');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_imt_overweight_min', '23.0');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_imt_overweight_max', '24.9');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_imt_overweight_score', '15');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_imt_underweight_score', '10');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_imt_obese_score', '5');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_bbi_ideal_min', '90');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_bbi_ideal_max', '110');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_bbi_ideal_score', '15');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_bbi_warning_score', '10');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_bbi_danger_score', '5');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_bp_normal_sys', '120');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_bp_normal_dia', '80');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_bp_normal_score', '15');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_bp_warning_sys', '139');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_bp_warning_dia', '89');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_bp_warning_score', '10');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_bp_danger_score', '5');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_hr_min', '60');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_hr_max', '100');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_hr_normal_score', '15');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_hr_abnormal_score', '8');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_spo2_min', '95');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_spo2_normal_score', '15');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_spo2_abnormal_score', '5');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_temp_min', '36.5');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_temp_max', '37.5');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_temp_normal_score', '10');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_temp_abnormal_score', '5');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_chol_max', '200');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_chol_normal_score', '10');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_chol_abnormal_score', '5');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('ind_target_score', '10');
INSERT INTO `settings` (`key_name`, `value_val`) VALUES ('default_location_id', '4');

-- --------------------------------------------------------
-- Table structure for table `locations`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `locations`;
CREATE TABLE locations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(100) NOT NULL,
        address TEXT NOT NULL,
        latitude FLOAT NOT NULL,
        longitude FLOAT NOT NULL,
        google_maps_url TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

-- Dumping data for table `locations`
INSERT INTO `locations` (`id`, `name`, `address`, `latitude`, `longitude`, `google_maps_url`, `created_at`) VALUES ('4', 'Stadion Utama Riau', 'F9MQ+CRV, Jl. Naga Sakti, Simpang Baru, Kec. Tampan, Kota Pekanbaru, Riau 28292', '0.483491', '101.389501', 'https://maps.google.com/?q=0.483491,101.389501', '2026-06-09 16:14:38');

COMMIT;
PRAGMA foreign_keys=ON;
