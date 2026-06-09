<?php
/**
 * Export PDF Report - Running Health Tracker
 * 
 * Menggunakan library mPDF untuk menghasilkan dokumen cetak PDF resmi.
 * Mendukung filter rentang tanggal.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Proteksi Akses Admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die("Akses ditolak. Silakan login kembali di portal admin.");
}

$type = $_GET['type'] ?? 'all';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

$db = getDBConnection();

// Set Up Filter Query
$runnerFilter = "";
$runFilter = "";
$params = [];

if (!empty($startDate)) {
    $runnerFilter .= " AND created_at >= :start_date";
    $runFilter .= " AND post_time >= :start_date_run";
    $params[':start_date'] = $startDate . ' 00:00:00';
    $params[':start_date_run'] = $startDate . ' 00:00:00';
}
if (!empty($endDate)) {
    $runnerFilter .= " AND created_at <= :end_date";
    $runFilter .= " AND post_time <= :end_date_run";
    $params[':end_date'] = $endDate . ' 23:59:59';
    $params[':end_date_run'] = $endDate . ' 23:59:59';
}

// 1. Ambil Statistik Ringkasan
// Total Pelari
$stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE 1=1" . $runnerFilter);
$execParams = [];
if (!empty($startDate)) $execParams[':start_date'] = $params[':start_date'];
if (!empty($endDate)) $execParams[':end_date'] = $params[':end_date'];
$stmt->execute($execParams);
$totalRunners = $stmt->fetch()['total'] ?? 0;

// Total Sesi Lari Selesai
$stmt = $db->prepare("SELECT COUNT(*) as total FROM runs WHERE status = 'completed'" . $runFilter);
$execParamsRuns = [];
if (!empty($startDate)) $execParamsRuns[':start_date_run'] = $params[':start_date_run'];
if (!empty($endDate)) $execParamsRuns[':end_date_run'] = $params[':end_date_run'];
$stmt->execute($execParamsRuns);
$completedRuns = $stmt->fetch()['total'] ?? 0;

// Rata-rata Durasi Lari
$stmt = $db->prepare("SELECT AVG(post_duration) as avg_dur FROM runs WHERE status = 'completed'" . $runFilter);
$stmt->execute($execParamsRuns);
$avgDuration = round($stmt->fetch()['avg_dur'] ?? 0, 1);

// Rata-rata Jarak Tempuh
$stmt = $db->prepare("SELECT AVG(post_distance) as avg_dist FROM runs WHERE status = 'completed'" . $runFilter);
$stmt->execute($execParamsRuns);
$avgDistance = round($stmt->fetch()['avg_dist'] ?? 0, 2);

// Rata-rata Kalori
$stmt = $db->prepare("SELECT AVG(post_calories) as avg_cal FROM runs WHERE status = 'completed'" . $runFilter);
$stmt->execute($execParamsRuns);
$avgCalories = round($stmt->fetch()['avg_cal'] ?? 0, 1);


// 2. Ambil Data Detail Pelari
$stmt = $db->prepare("SELECT id, fullname, dob, gender, address, phone, email, created_at FROM users WHERE 1=1" . $runnerFilter . " ORDER BY created_at DESC");
$stmt->execute($execParams);
$runnersList = $stmt->fetchAll();

$today = new DateTime();
foreach ($runnersList as &$r) {
    $birth = new DateTime($r['dob']);
    $r['age'] = $today->diff($birth)->y;
}
unset($r);


// 3. Ambil Data Detail Sesi Lari
$stmt = $db->prepare("SELECT r.*, u.fullname, u.username, u.gender, u.dob 
    FROM runs r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.status = 'completed'" . $runFilter . " 
    ORDER BY r.post_time DESC");
$stmt->execute($execParamsRuns);
$runsList = $stmt->fetchAll();

// Helper Hitung Skor Lari
function calculateMetricScorePHP($run) {
    $score = 0;
    
    // 1. BMI (pre-weight, pre-height)
    $heightM = $run['pre_height'] / 100;
    if ($heightM > 0) {
        $imt = $run['pre_weight'] / ($heightM * $heightM);
        
        $imtNormalMin = floatval(getSetting('ind_imt_normal_min', '18.5'));
        $imtNormalMax = floatval(getSetting('ind_imt_normal_max', '22.9'));
        $imtOverweightMin = floatval(getSetting('ind_imt_overweight_min', '23.0'));
        $imtOverweightMax = floatval(getSetting('ind_imt_overweight_max', '24.9'));
        
        if ($imt >= $imtNormalMin && $imt <= $imtNormalMax) {
            $score += floatval(getSetting('ind_imt_normal_score', '20'));
        } else if ($imt >= $imtOverweightMin && $imt <= $imtOverweightMax) {
            $score += floatval(getSetting('ind_imt_overweight_score', '15'));
        } else if ($imt < $imtNormalMin) {
            $score += floatval(getSetting('ind_imt_underweight_score', '10'));
        } else {
            $score += floatval(getSetting('ind_imt_obese_score', '5'));
        }
    }

    // 2. BBI
    $baseBBI = $run['pre_height'] - 100;
    $factor = ($run['gender'] === 'L') ? 0.10 : 0.15;
    $bbi = $baseBBI - ($baseBBI * $factor);
    if ($bbi > 0) {
        $percentBBI = ($run['pre_weight'] / $bbi) * 100;
        
        $bbiIdealMin = floatval(getSetting('ind_bbi_ideal_min', '90'));
        $bbiIdealMax = floatval(getSetting('ind_bbi_ideal_max', '110'));
        
        if ($percentBBI >= $bbiIdealMin && $percentBBI <= $bbiIdealMax) {
            $score += floatval(getSetting('ind_bbi_ideal_score', '15'));
        } else if (($percentBBI >= 80 && $percentBBI < $bbiIdealMin) || ($percentBBI > $bbiIdealMax && $percentBBI <= 120)) {
            $score += floatval(getSetting('ind_bbi_warning_score', '10'));
        } else {
            $score += floatval(getSetting('ind_bbi_danger_score', '5'));
        }
    }

    // 3. Blood pressure pre
    $sys = $run['pre_systolic'];
    $dia = $run['pre_diastolic'];
    $bpNormalSys = floatval(getSetting('ind_bp_normal_sys', '120'));
    $bpNormalDia = floatval(getSetting('ind_bp_normal_dia', '80'));
    $bpWarningSys = floatval(getSetting('ind_bp_warning_sys', '139'));
    $bpWarningDia = floatval(getSetting('ind_bp_warning_dia', '89'));
    
    if ($sys <= $bpNormalSys && $dia <= $bpNormalDia) {
        $score += floatval(getSetting('ind_bp_normal_score', '15'));
    } else if (($sys > $bpNormalSys && $sys <= $bpWarningSys) || ($dia > $bpNormalDia && $dia <= $bpWarningDia)) {
        $score += floatval(getSetting('ind_bp_warning_score', '10'));
    } else {
        $score += floatval(getSetting('ind_bp_danger_score', '5'));
    }

    // 4. Pre Heart rate
    $hrMin = floatval(getSetting('ind_hr_min', '60'));
    $hrMax = floatval(getSetting('ind_hr_max', '100'));
    if ($run['pre_heart_rate'] >= $hrMin && $run['pre_heart_rate'] <= $hrMax) {
        $score += floatval(getSetting('ind_hr_normal_score', '15'));
    } else {
        $score += floatval(getSetting('ind_hr_abnormal_score', '8'));
    }

    // 5. SpO2 pre
    $spo2Min = floatval(getSetting('ind_spo2_min', '95'));
    if ($run['pre_spo2'] >= $spo2Min) {
        $score += floatval(getSetting('ind_spo2_normal_score', '15'));
    } else {
        $score += floatval(getSetting('ind_spo2_abnormal_score', '5'));
    }

    // 6. Pre temp
    $tempMin = floatval(getSetting('ind_temp_min', '36.5'));
    $tempMax = floatval(getSetting('ind_temp_max', '37.5'));
    if ($run['pre_temperature'] >= $tempMin && $run['pre_temperature'] <= $tempMax) {
        $score += floatval(getSetting('ind_temp_normal_score', '10'));
    } else {
        $score += floatval(getSetting('ind_temp_abnormal_score', '5'));
    }

    // 7. Cholesterol
    $cholMax = floatval(getSetting('ind_chol_max', '200'));
    if ($run['pre_cholesterol'] < $cholMax) {
        $score += floatval(getSetting('ind_chol_normal_score', '10'));
    } else {
        $score += floatval(getSetting('ind_chol_abnormal_score', '5'));
    }

    // 8. Target
    $targetType = $run['target_type'] ?? 'none';
    $targetVal = intval($run['target_value'] ?? 0);
    if ($targetType !== 'none' && $targetVal > 0) {
        if ($targetType === 'time' && intval($run['post_duration'] ?? 0) >= $targetVal) {
            $score += floatval(getSetting('ind_target_score', '10'));
        } else if ($targetType === 'laps' && intval($run['post_laps'] ?? 0) >= $targetVal) {
            $score += floatval(getSetting('ind_target_score', '10'));
        }
    }

    return min(100, $score);
}

// Data Aplikasi Branding
$appName = getSetting('app_name', 'Stadion Run');
$appLogo = getSetting('app_logo', '🏃');

// Susun HTML untuk mPDF
$html = '
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.4;
        }
        .header {
            border-bottom: 2px solid #ff007f;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .brand-section {
            width: 100%;
        }
        .brand-name {
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
        }
        .report-title {
            font-size: 14px;
            color: #ff007f;
            font-weight: bold;
            margin-top: 4px;
        }
        .filter-label {
            font-size: 11px;
            color: #475569;
            margin-top: 5px;
        }
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .stats-table td {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px 14px;
            width: 20%;
        }
        .stats-label {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .stats-val {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 20px;
            margin-bottom: 8px;
            border-left: 3px solid #ff007f;
            padding-left: 8px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        table.data-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 8px 10px;
            font-size: 10px;
            border: 1px solid #334155;
        }
        table.data-table td {
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            font-size: 10px;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .badge {
            font-weight: bold;
            font-size: 9px;
            padding: 2px 6px;
            border-radius: 4px;
            text-align: center;
        }
        .gender-badge-l { color: #2563eb; background-color: #dbeafe; }
        .gender-badge-p { color: #db2777; background-color: #fce7f3; }
        
        .score-perfect { color: #15803d; background-color: #dcfce7; font-weight: bold; }
        .score-warning { color: #b45309; background-color: #fef3c7; font-weight: bold; }
        .score-danger { color: #b91c1c; background-color: #fee2e2; font-weight: bold; }
        
        .footer {
            text-align: right;
            font-size: 9px;
            color: #64748b;
            margin-top: 30px;
        }
    </style>
</head>
<body>

    <div class="header">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="width: 60px; font-size: 36px; vertical-align: middle; border: none;">' . htmlspecialchars($appLogo) . '</td>
                <td style="vertical-align: middle; border: none;">
                    <div class="brand-name">' . htmlspecialchars(strtoupper($appName)) . '</div>
                    <div class="report-title">LAPORAN AKTIVITAS KESEHATAN ATLET</div>
                    <div class="filter-label">
                        Tanggal Cetak: ' . date('d M Y - H:i') . ' WIB 
                        ' . (!empty($startDate) || !empty($endDate) ? ' | Rentang Tanggal: ' . (!empty($startDate) ? date('d/m/Y', strtotime($startDate)) : 'Awal') . ' s/d ' . (!empty($endDate) ? date('d/m/Y', strtotime($endDate)) : 'Akhir') : '') . '
                    </div>
                </td>
            </tr>
        </table>
    </div>
';

// Ringkasan Statistik
if ($type === 'all' || $type === 'runs') {
    $html .= '
    <div class="section-title">Ringkasan Aktivitas Lapangan</div>
    <table class="stats-table">
        <tr>
            <td>
                <div class="stats-label">Total Pelari Terdaftar</div>
                <div class="stats-val">' . $totalRunners . ' Orang</div>
            </td>
            <td>
                <div class="stats-label">Sesi Lari Selesai</div>
                <div class="stats-val">' . $completedRuns . ' Sesi</div>
            </td>
            <td>
                <div class="stats-label">Rerata Durasi</div>
                <div class="stats-val">' . $avgDuration . ' Menit</div>
            </td>
            <td>
                <div class="stats-label">Rerata Jarak</div>
                <div class="stats-val">' . $avgDistance . ' Km</div>
            </td>
            <td>
                <div class="stats-label">Rerata Kalori</div>
                <div class="stats-val">' . $avgCalories . ' Kcal</div>
            </td>
        </tr>
    </table>
    ';
}

// Laporan 1: Daftar Pelari
if ($type === 'all' || $type === 'runners') {
    $html .= '
    <div class="section-title">Daftar Pelari Stadion Terdaftar</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 40px; text-align: center;">No.</th>
                <th>Nama Pelari</th>
                <th style="width: 50px; text-align: center;">Gender</th>
                <th style="width: 55px; text-align: center;">Usia</th>
                <th>Alamat Domisili</th>
                <th style="width: 90px; text-align: center;">No. Handphone</th>
                <th style="width: 100px; text-align: center;">Terdaftar Pada</th>
            </tr>
        </thead>
        <tbody>';
    
    if (empty($runnersList)) {
        $html .= '<tr><td colspan="7" style="text-align: center; font-style: italic;">Tidak ada data pelari terdaftar dalam rentang ini.</td></tr>';
    } else {
        $no = 1;
        foreach ($runnersList as $r) {
            $genderLabel = ($r['gender'] === 'L') ? 'Laki-Laki' : 'Perempuan';
            $genderClass = ($r['gender'] === 'L') ? 'gender-badge-l' : 'gender-badge-p';
            $regDate = date('d M Y', strtotime($r['created_at']));
            
            $html .= '
            <tr>
                <td style="text-align: center;">' . $no++ . '.</td>
                <td style="font-weight: bold;">' . htmlspecialchars($r['fullname']) . '</td>
                <td style="text-align: center;"><span class="badge ' . $genderClass . '">' . ($r['gender'] === 'L' ? 'L' : 'P') . '</span></td>
                <td style="text-align: center;">' . $r['age'] . ' Thn</td>
                <td>' . htmlspecialchars($r['address']) . '</td>
                <td style="text-align: center;">' . htmlspecialchars($r['phone']) . '</td>
                <td style="text-align: center;">' . $regDate . '</td>
            </tr>';
        }
    }
    
    $html .= '
        </tbody>
    </table>
    ';
}

// Laporan 2: Riwayat Sesi Lari
if ($type === 'all' || $type === 'runs') {
    $html .= '
    <div class="section-title">Riwayat Log Sesi Lari Atlet</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 40px; text-align: center;">No.</th>
                <th>Nama Pelari</th>
                <th style="width: 100px; text-align: center;">Tanggal Sesi</th>
                <th style="width: 60px; text-align: center;">Lap</th>
                <th style="width: 70px; text-align: center;">Jarak (km)</th>
                <th style="width: 70px; text-align: center;">Durasi (mnt)</th>
                <th style="width: 75px; text-align: center;">Kalori (kcal)</th>
                <th style="width: 70px; text-align: center;">Skor Fisik</th>
            </tr>
        </thead>
        <tbody>';

    if (empty($runsList)) {
        $html .= '<tr><td colspan="8" style="text-align: center; font-style: italic;">Tidak ada data sesi lari terekam dalam rentang ini.</td></tr>';
    } else {
        $no = 1;
        foreach ($runsList as $run) {
            $score = calculateMetricScorePHP($run);
            $scoreClass = 'score-danger';
            if ($score >= 80) $scoreClass = 'score-perfect';
            else if ($score >= 60) $scoreClass = 'score-warning';
            
            $runDate = date('d M Y, H:i', strtotime($run['post_time']));
            $distance = number_format($run['post_distance'] ?? 0, 2);
            
            $html .= '
            <tr>
                <td style="text-align: center;">' . $no++ . '.</td>
                <td style="font-weight: bold;">' . htmlspecialchars($run['fullname']) . '</td>
                <td style="text-align: center;">' . $runDate . '</td>
                <td style="text-align: center;">' . $run['post_laps'] . ' lap</td>
                <td style="text-align: center;">' . $distance . ' km</td>
                <td style="text-align: center;">' . $run['post_duration'] . ' mnt</td>
                <td style="text-align: center;">' . $run['post_calories'] . ' kcal</td>
                <td style="text-align: center;"><span class="badge ' . $scoreClass . '">' . $score . ' / 100</span></td>
            </tr>';
        }
    }

    $html .= '
        </tbody>
    </table>
    ';
}

$html .= '
    <div class="footer">
        Dicetak secara otomatis oleh Sistem Monitoring Kesehatan ' . htmlspecialchars($appName) . '
    </div>
</body>
</html>
';

// Render ke mPDF
try {
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 15,
        'margin_bottom' => 15
    ]);
    
    // Set Document Properties
    $mpdf->SetTitle('Laporan Kesehatan ' . $appName);
    $mpdf->SetAuthor($appName . ' System');
    
    $mpdf->WriteHTML($html);
    $mpdf->Output('Laporan_Aktivitas_' . date('Ymd_His') . '.pdf', \Mpdf\Output\Destination::INLINE);
} catch (Exception $e) {
    die("Gagal mengekspor PDF: " . $e->getMessage());
}
