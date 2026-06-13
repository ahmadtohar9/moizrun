/**
 * Frontend Application Logic - Running Health Tracker
 * 
 * Mengelola state aplikasi, routing halaman, pemanggilan AJAX API,
 * perhitungan real-time, dan evaluasi hasil kesehatan lari.
 */

// Application State
const state = {
    user: null,
    activeRun: null,
    history: [],
    currentGender: 'L', // Default registration gender selection
    timerInterval: null,
    secondsElapsed: 0,
    lapsCount: 0,
    caloriesBurned: 0,
    indicators: null
};

// Default Indicators fallback
const defaultIndicators = {
    'ind_imt_normal_min': 18.5,
    'ind_imt_normal_max': 22.9,
    'ind_imt_normal_score': 20,
    'ind_imt_overweight_min': 23.0,
    'ind_imt_overweight_max': 24.9,
    'ind_imt_overweight_score': 15,
    'ind_imt_underweight_score': 10,
    'ind_imt_obese_score': 5,
    
    'ind_bbi_ideal_min': 90,
    'ind_bbi_ideal_max': 110,
    'ind_bbi_ideal_score': 15,
    'ind_bbi_warning_score': 10,
    'ind_bbi_danger_score': 5,
    
    'ind_bp_normal_sys': 120,
    'ind_bp_normal_dia': 80,
    'ind_bp_normal_score': 15,
    'ind_bp_warning_sys': 139,
    'ind_bp_warning_dia': 89,
    'ind_bp_warning_score': 10,
    'ind_bp_danger_score': 5,
    
    'ind_hr_min': 60,
    'ind_hr_max': 100,
    'ind_hr_normal_score': 15,
    'ind_hr_abnormal_score': 8,
    
    'ind_spo2_min': 95,
    'ind_spo2_normal_score': 15,
    'ind_spo2_abnormal_score': 5,
    
    'ind_temp_min': 36.5,
    'ind_temp_max': 37.5,
    'ind_temp_normal_score': 10,
    'ind_temp_abnormal_score': 5,
    
    'ind_chol_max': 200,
    'ind_chol_normal_score': 10,
    'ind_chol_abnormal_score': 5,
    
    'ind_target_score': 10
};

function getIndicator(key) {
    if (state.indicators && state.indicators[key] !== undefined) {
        return state.indicators[key];
    }
    return defaultIndicators[key];
}

// DOM Elements & Routing
const pages = [
    'landing', 'login', 'register', 'dashboard', 
    'pre-run', 'post-run', 'evaluation', 'history'
];

function navigateTo(pageId) {
    if (!pages.includes(pageId)) return;
    
    // Show loading overlay
    showLoading(true);
    
    setTimeout(() => {
        // Auto populate inputs when ending a run
        if (pageId === 'post-run' && state.activeRun) {
            if (state.timerInterval) clearInterval(state.timerInterval);
            state.timerInterval = null;
            
            const durationMin = Math.max(1, Math.ceil(state.secondsElapsed / 60));
            const durInput = document.getElementById('post-duration');
            if (durInput) durInput.value = durationMin;
            
            const lapsInput = document.getElementById('post-laps');
            if (lapsInput) lapsInput.value = state.lapsCount;
        }
        
        // Hide all pages
        pages.forEach(p => {
            const el = document.getElementById(`page-${p}`);
            if (el) el.classList.remove('active');
        });
        
        // Show target page
        const targetEl = document.getElementById(`page-${pageId}`);
        if (targetEl) {
            targetEl.classList.add('active');
            // Trigger page-specific loads
            onPageShow(pageId);
        }
        
        // Manage active navigation menu
        updateActiveNav(pageId);
        
        // Hide loading overlay after rendering is complete
        setTimeout(() => {
            showLoading(false);
        }, 100);
    }, 200); // 200ms visual transition delay
}

function updateActiveNav(pageId) {
    const navBar = document.getElementById('bottom-nav-bar');
    if (!navBar) return;
    
    // Hide nav bar on auth pages
    if (['landing', 'login', 'register'].includes(pageId)) {
        navBar.style.display = 'none';
        return;
    }
    
    navBar.style.display = 'flex';
    
    // Reset active nav items
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Set active item based on page
    let activeNavId = '';
    if (pageId === 'dashboard' || pageId === 'pre-run' || pageId === 'post-run' || pageId === 'evaluation') {
        activeNavId = 'nav-dashboard';
    } else if (pageId === 'history') {
        activeNavId = 'nav-history';
    }
    
    const activeNav = document.getElementById(activeNavId);
    if (activeNav) activeNav.classList.add('active');
}

// Lifecycle Hooks when page renders
function onPageShow(pageId) {
    switch (pageId) {
        case 'dashboard':
            loadDashboard();
            break;
        case 'history':
            loadHistory();
            break;
        case 'pre-run':
            initPreRunForm();
            break;
        case 'post-run':
            initPostRunForm();
            break;
    }
}

// Show/Hide global loading spinner
function showLoading(show) {
    const loader = document.getElementById('loading-overlay');
    if (loader) {
        loader.style.opacity = show ? '1' : '0';
        loader.style.pointerEvents = show ? 'all' : 'none';
    }
}

// Alert utility (Custom styled dialog)
function showAlert(message, type = 'error') {
    // In premium app, standard alert is basic. We will create a non-blocking elegant popup notification if possible,
    // but for reliability, we can use alert or build a simple temporary toast overlay.
    const toast = document.createElement('div');
    toast.className = `badge badge-${type === 'error' ? 'danger' : type === 'success' ? 'normal' : 'warning'} animated-fade`;
    toast.style.position = 'fixed';
    toast.style.top = '20px';
    toast.style.left = '50%';
    toast.style.transform = 'translateX(-50%)';
    toast.style.zIndex = '99999';
    toast.style.padding = '12px 24px';
    toast.style.borderRadius = '12px';
    toast.style.boxShadow = '0 10px 25px rgba(0,0,0,0.5)';
    toast.style.fontSize = '14px';
    toast.style.fontWeight = '600';
    toast.style.textAlign = 'center';
    toast.style.maxWidth = '90%';
    toast.innerText = message;
    
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.5s ease';
        setTimeout(() => toast.remove(), 500);
    }, 3000);
}

// Fetch helper
async function apiCall(action, data = {}) {
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(Object.assign({ action }, data))
        });
        const result = await response.json();
        return result;
    } catch (err) {
        console.error('API Error:', err);
        return { status: 'error', message: 'Gagal menghubungi server. Periksa koneksi internet Anda.' };
    }
}

// --- CORE CALCULATIONS (RUMUS KESEHATAN) ---

/**
 * Hitung Indeks Massa Tubuh (IMT / BMI)
 * IMT = Berat Badan (kg) : [Tinggi Badan (m) x Tinggi Badan (m)]
 */
function calculateIMT(weight, heightCm) {
    const heightM = heightCm / 100;
    if (heightM <= 0) return { val: 0, status: '-', class: '' };
    
    const imt = weight / (heightM * heightM);
    
    // Kategori Asia-Pacific dinamis
    let status = '';
    let cssClass = '';
    
    const normalMin = getIndicator('ind_imt_normal_min');
    const normalMax = getIndicator('ind_imt_normal_max');
    const overweightMin = getIndicator('ind_imt_overweight_min');
    const overweightMax = getIndicator('ind_imt_overweight_max');
    
    if (imt < normalMin) {
        status = 'Kekurangan Berat Badan (Underweight)';
        cssClass = 'badge-warning';
    } else if (imt >= normalMin && imt <= normalMax) {
        status = 'Normal';
        cssClass = 'badge-normal';
    } else if (imt >= overweightMin && imt <= overweightMax) {
        status = 'Kelebihan Berat Badan (Overweight / Berisiko Obesitas)';
        cssClass = 'badge-warning';
    } else {
        status = 'Obesitas (Obese)';
        cssClass = 'badge-danger';
    }
    
    return {
        val: parseFloat(imt.toFixed(1)),
        status: status,
        class: cssClass
    };
}

/**
 * Hitung Berat Badan Ideal (BBI) menggunakan rumus Broca
 * Laki-laki: BBI = (TB - 100) - ((TB - 100) * 10%)
 * Perempuan: BBI = (TB - 100) - ((TB - 100) * 15%)
 */
function calculateBBI(heightCm, gender) {
    if (heightCm <= 100) return 0;
    const base = heightCm - 100;
    const factor = (gender === 'L') ? 0.10 : 0.15;
    return parseFloat((base - (base * factor)).toFixed(1));
}

/**
 * Hitung Persentase BBI
 * %BBI = (BB Aktual / BB Ideal) * 100%
 */
function calculateBBIPercentage(weight, heightCm, gender) {
    const bbi = calculateBBI(heightCm, gender);
    if (bbi <= 0) return { percent: 0, bbi: 0, status: '-', class: '' };
    
    const percent = (weight / bbi) * 100;
    
    let status = '';
    let cssClass = '';
    
    const idealMin = getIndicator('ind_bbi_ideal_min');
    const idealMax = getIndicator('ind_bbi_ideal_max');
    
    if (percent < 70) {
        status = 'Gizi Berat (Sangat Kurang)';
        cssClass = 'badge-danger';
    } else if (percent >= 70 && percent < 80) {
        status = 'Gizi Sedang';
        cssClass = 'badge-warning';
    } else if (percent >= 80 && percent < idealMin) {
        status = 'Gizi Ringan';
        cssClass = 'badge-warning';
    } else if (percent >= idealMin && percent <= idealMax) {
        status = 'Gizi Normal (Ideal)';
        cssClass = 'badge-normal';
    } else if (percent > idealMax && percent <= 120) {
        status = 'Kelebihan Gizi (Overweight)';
        cssClass = 'badge-warning';
    } else {
        status = 'Obesitas';
        cssClass = 'badge-danger';
    }
    
    return {
        percent: parseFloat(percent.toFixed(1)),
        bbi: bbi,
        status: status,
        class: cssClass
    };
}

/**
 * Hitung skor kebugaran sesi lari secara dinamis berdasarkan data konfigurasi indikator.
 */
function calculateRunScore(run) {
    let score = 0;
    
    // 1. BMI / IMT
    const heightM = run.pre_height / 100;
    const imt = run.pre_weight / (heightM * heightM);
    
    const imtNormalMin = getIndicator('ind_imt_normal_min');
    const imtNormalMax = getIndicator('ind_imt_normal_max');
    const imtOverweightMin = getIndicator('ind_imt_overweight_min');
    const imtOverweightMax = getIndicator('ind_imt_overweight_max');
    
    if (imt >= imtNormalMin && imt <= imtNormalMax) {
        score += getIndicator('ind_imt_normal_score');
    } else if (imt >= imtOverweightMin && imt <= imtOverweightMax) {
        score += getIndicator('ind_imt_overweight_score');
    } else if (imt < imtNormalMin) {
        score += getIndicator('ind_imt_underweight_score');
    } else {
        score += getIndicator('ind_imt_obese_score');
    }
    
    // 2. BBI
    const baseBBI = run.pre_height - 100;
    const gender = run.gender || (state.user ? state.user.gender : 'L');
    const factor = (gender === 'L') ? 0.10 : 0.15;
    const bbi = baseBBI - (baseBBI * factor);
    const percentBBI = (run.pre_weight / bbi) * 100;
    
    const bbiIdealMin = getIndicator('ind_bbi_ideal_min');
    const bbiIdealMax = getIndicator('ind_bbi_ideal_max');
    
    if (percentBBI >= bbiIdealMin && percentBBI <= bbiIdealMax) {
        score += getIndicator('ind_bbi_ideal_score');
    } else if ((percentBBI >= 80 && percentBBI < bbiIdealMin) || (percentBBI > bbiIdealMax && percentBBI <= 120)) {
        score += getIndicator('ind_bbi_warning_score');
    } else {
        score += getIndicator('ind_bbi_danger_score');
    }
    
    // 3. Tekanan Darah (Tensi)
    const sys = run.pre_systolic;
    const dia = run.pre_diastolic;
    
    const bpNormalSys = getIndicator('ind_bp_normal_sys');
    const bpNormalDia = getIndicator('ind_bp_normal_dia');
    const bpWarningSys = getIndicator('ind_bp_warning_sys');
    const bpWarningDia = getIndicator('ind_bp_warning_dia');
    
    if (sys <= bpNormalSys && dia <= bpNormalDia) {
        score += getIndicator('ind_bp_normal_score');
    } else if ((sys > bpNormalSys && sys <= bpWarningSys) || (dia > bpNormalDia && dia <= bpWarningDia)) {
        score += getIndicator('ind_bp_warning_score');
    } else {
        score += getIndicator('ind_bp_danger_score');
    }
    
    // 4. Detak Jantung Istirahat (Resting Heart Rate)
    const hrMin = getIndicator('ind_hr_min');
    const hrMax = getIndicator('ind_hr_max');
    
    if (run.pre_heart_rate >= hrMin && run.pre_heart_rate <= hrMax) {
        score += getIndicator('ind_hr_normal_score');
    } else {
        score += getIndicator('ind_hr_abnormal_score');
    }
    
    // 5. Oksigen Darah (SpO2)
    const spo2Min = getIndicator('ind_spo2_min');
    if (run.pre_spo2 >= spo2Min) {
        score += getIndicator('ind_spo2_normal_score');
    } else {
        score += getIndicator('ind_spo2_abnormal_score');
    }
    
    // 6. Suhu Tubuh Awal
    const tempMin = getIndicator('ind_temp_min');
    const tempMax = getIndicator('ind_temp_max');
    if (run.pre_temperature >= tempMin && run.pre_temperature <= tempMax) {
        score += getIndicator('ind_temp_normal_score');
    } else {
        score += getIndicator('ind_temp_abnormal_score');
    }
    
    // 7. Kolesterol Awal
    const cholMax = getIndicator('ind_chol_max');
    if (run.pre_cholesterol < cholMax) {
        score += getIndicator('ind_chol_normal_score');
    } else {
        score += getIndicator('ind_chol_abnormal_score');
    }
    
    // 8. Evaluasi Target Lari
    const targetType = run.target_type || 'none';
    const targetVal = parseInt(run.target_value) || 0;
    if (targetType !== 'none' && targetVal > 0) {
        if (targetType === 'time') {
            const actualMin = run.post_duration || 0;
            if (actualMin >= targetVal) {
                score += getIndicator('ind_target_score');
            }
        } else if (targetType === 'laps') {
            const actualLaps = run.post_laps || 0;
            if (actualLaps >= targetVal) {
                score += getIndicator('ind_target_score');
            }
        }
    }
    
    return Math.min(100, score);
}

/**
 * Hitung % HRmaks (Heart Rate Maksimum)
 * HRmaks = 220 - Usia
 * % HRmaks = (Heart Rate saat aktivitas / HRmaks) * 100%
 */
function calculateHRmaxPercentage(heartRate, age) {
    const hrMax = 220 - age;
    if (hrMax <= 0) return { percent: 0, max: 0, status: '-', class: '' };
    
    const percent = (heartRate / hrMax) * 100;
    
    let status = '';
    let cssClass = '';
    
    if (percent < 50) {
        status = 'Intensitas Sangat Ringan';
        cssClass = 'badge-normal';
    } else if (percent >= 50 && percent < 70) {
        status = 'Intensitas Ringan-Sedang';
        cssClass = 'badge-normal';
    } else if (percent >= 70 && percent < 85) {
        status = 'Intensitas Sedang-Berat';
        cssClass = 'badge-warning';
    } else {
        status = 'Intensitas Berat';
        cssClass = 'badge-danger';
    }
    
    return {
        percent: parseFloat(percent.toFixed(1)),
        max: hrMax,
        status: status,
        class: cssClass
    };
}

// --- AUTHENTICATION FLOWS ---

// Cek Sesi User saat startup
async function checkAuth() {
    showLoading(true);
    
    // Ambil data konfigurasi indikator penilaian kesehatan
    const indRes = await apiCall('get_indicators');
    if (indRes.status === 'success') {
        state.indicators = indRes.indicators;
    }

    const res = await apiCall('get_user');
    
    if (res.status === 'success') {
        state.user = res.user;
        
        // Cek apakah ada sesi lari yang sedang aktif
        const runRes = await apiCall('get_active_run');
        if (runRes.status === 'success' && runRes.active_run) {
            state.activeRun = runRes.active_run;
            navigateTo('post-run');
        } else {
            navigateTo('dashboard');
        }
    } else {
        navigateTo('landing');
    }
    showLoading(false);
}

// Handler Registrasi
async function handleRegister(e) {
    e.preventDefault();
    
    const fullname = document.getElementById('reg-fullname').value;
    const dob = document.getElementById('reg-dob').value;
    const gender = state.currentGender;
    const address = document.getElementById('reg-address').value;
    const phone = document.getElementById('reg-phone').value;
    const email = document.getElementById('reg-email').value;
    const username = document.getElementById('reg-username').value;
    const password = document.getElementById('reg-password').value;
    
    showLoading(true);
    const res = await apiCall('register', {
        fullname, dob, gender, address, phone, email, username, password
    });
    showLoading(false);
    
    if (res.status === 'success') {
        showAlert(res.message, 'success');
        document.getElementById('register-form').reset();
        navigateTo('login');
    } else {
        showAlert(res.message, 'error');
    }
}

// Handler Login
async function handleLogin(e) {
    e.preventDefault();
    
    const username = document.getElementById('login-username').value;
    const password = document.getElementById('login-password').value;
    
    showLoading(true);
    const res = await apiCall('login', { username, password });
    showLoading(false);
    
    if (res.status === 'success') {
        state.user = res.user;
        
        // Ambil data konfigurasi indikator penilaian kesehatan
        showLoading(true);
        const indRes = await apiCall('get_indicators');
        if (indRes.status === 'success') {
            state.indicators = indRes.indicators;
        }
        
        showAlert(res.message, 'success');
        document.getElementById('login-form').reset();
        
        // Cek Sesi Lari Aktif
        const runRes = await apiCall('get_active_run');
        showLoading(false);
        
        if (runRes.status === 'success' && runRes.active_run) {
            state.activeRun = runRes.active_run;
            navigateTo('post-run');
        } else {
            navigateTo('dashboard');
        }
    } else {
        showAlert(res.message, 'error');
    }
}

// Handler Logout
async function handleLogout() {
    if (!confirm('Apakah Anda yakin ingin keluar?')) return;
    
    showLoading(true);
    const res = await apiCall('logout');
    showLoading(false);
    
    if (res.status === 'success') {
        state.user = null;
        state.activeRun = null;
        state.history = [];
        showAlert(res.message, 'success');
        navigateTo('landing');
    } else {
        showAlert(res.message, 'error');
    }
}

// --- PAGE INITIALIZERS & LOADERS ---

// Dashboard Page
function loadDashboard() {
    if (!state.user) return navigateTo('landing');
    
    // Set Profile UI
    document.getElementById('dash-user-name').innerText = state.user.fullname;
    document.getElementById('dash-avatar-letter').innerText = state.user.fullname.charAt(0).toUpperCase();
    document.getElementById('dash-user-gender').innerText = state.user.gender === 'L' ? 'Laki-laki' : 'Perempuan';
    document.getElementById('dash-user-age').innerText = `${state.user.age} Tahun`;
    
    const runControlCard = document.getElementById('run-control-card');
    
    // Cek apakah ada sesi lari yang menggantung
    if (state.activeRun) {
        runControlCard.innerHTML = `
            <div class="status-tracker-card" style="padding: 16px;">
                <div class="tracker-title">Sesi Lari Sore Berjalan</div>
                <div class="timer-display" style="text-align: center; margin: 15px 0;">
                    <span id="active-timer" style="font-size: 40px; font-weight: 800; color: var(--primary); font-family: monospace;">00:00</span>
                </div>
                
                <!-- Metrics Grid -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                    <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--card-border); border-radius: 12px; padding: 10px; text-align: center;">
                        <div style="font-size: 11px; color: var(--text-muted);">Jarak Tempuh</div>
                        <div id="active-distance" style="font-size: 16px; font-weight: 700; color: var(--text-main);">0.00 km</div>
                        <div id="active-laps-lbl" style="font-size: 10px; color: var(--text-muted);">0 Lap</div>
                    </div>
                    <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--card-border); border-radius: 12px; padding: 10px; text-align: center;">
                        <div style="font-size: 11px; color: var(--text-muted);">Kalori Terbakar</div>
                        <div id="active-calories" style="font-size: 16px; font-weight: 700; color: var(--text-main);">0 kcal</div>
                        <div style="font-size: 10px; color: var(--text-muted);">Estimasi</div>
                    </div>
                </div>
                
                <!-- Target Progress Bar -->
                <div id="active-target-panel" style="display: none; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-muted); margin-bottom: 4px;">
                        <span id="active-target-desc">Target: 0</span>
                        <span id="active-target-percent">0%</span>
                    </div>
                    <div style="width: 100%; height: 6px; background: rgba(0,0,0,0.05); border-radius: 99px; overflow: hidden; border: 1px solid var(--card-border);">
                        <div id="active-target-bar" style="width: 0%; height: 100%; background: linear-gradient(to right, var(--primary), var(--secondary)); border-radius: 99px; transition: width 0.3s ease;"></div>
                    </div>
                </div>
                
                <!-- Controls -->
                <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 12px;">
                    <button type="button" onclick="handleIncrementLap()" class="btn" style="background: var(--card-bg); color: var(--primary); border: 1px solid var(--primary); box-shadow: none;">
                        + LAP
                    </button>
                    <button type="button" onclick="navigateTo('post-run')" class="btn btn-danger">
                        Selesai Lari
                    </button>
                </div>
            </div>
        `;
        startActiveTimer();
    } else {
        const musicCard = document.getElementById('workout-music-card');
        if (musicCard) musicCard.style.display = 'none';
        
        if (state.timerInterval) clearInterval(state.timerInterval);
        state.timerInterval = null;
        
        runControlCard.innerHTML = `
            <div>
                <h2 style="font-size: 18px; margin-bottom: 8px;">Siap untuk Lari Sore?</h2>
                <p class="mb-4">Sebelum memulai aktivitas lari di stadion, harap isi terlebih dahulu data kesehatan awal Anda.</p>
                <button onclick="navigateTo('pre-run')" class="btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    Mulai Sesi Lari (Isi Data Kesehatan)
                </button>
            </div>
        `;
    }
}

// Load locations for pre-run dropdown
async function loadPreRunLocations() {
    const select = document.getElementById('start-run-location');
    if (!select) return;
    
    // Clear previous options
    select.innerHTML = '<option value="" disabled selected>Pilih Lokasi Lari...</option>';
    
    const res = await apiCall('get_active_locations');
    if (res.status === 'success' && res.locations) {
        const defaultLocId = res.default_location_id || 1;
        res.locations.forEach(loc => {
            const opt = document.createElement('option');
            opt.value = loc.id;
            opt.innerText = loc.name;
            if (loc.id == defaultLocId) {
                opt.selected = true;
            }
            select.appendChild(opt);
        });
    } else {
        showAlert('Gagal memuat daftar lokasi lari.', 'error');
    }
}

// Pre-Run Form Init
function initPreRunForm() {
    // Reset Form
    document.getElementById('pre-run-form').reset();
    
    // Load locations dynamically
    loadPreRunLocations();
    
    // Set Real-time Calculations Listeners
    const heightInput = document.getElementById('pre-height');
    const weightInput = document.getElementById('pre-weight');
    const heartInput = document.getElementById('pre-heart-rate');
    const previewDiv = document.getElementById('pre-realtime-calc');
    
    const updatePreview = () => {
        const h = parseFloat(heightInput.value);
        const w = parseFloat(weightInput.value);
        const hr = parseInt(heartInput.value);
        
        let textParts = [];
        
        if (h > 0 && w > 0) {
            const imt = calculateIMT(w, h);
            const bbi = calculateBBI(h, state.user.gender);
            textParts.push(`IMT: <b>${imt.val}</b> | BBI Anda: <b>${bbi} kg</b>`);
        }
        
        if (hr > 0) {
            const hrInfo = calculateHRmaxPercentage(hr, state.user.age);
            textParts.push(`Denyut Nadi: <b>${hr} bpm</b> (${hrInfo.percent}% dari HRmaks)`);
        }
        
        if (textParts.length > 0) {
            previewDiv.innerHTML = textParts.join('<br>');
            previewDiv.style.display = 'block';
        } else {
            previewDiv.style.display = 'none';
        }
    };
    
    heightInput.addEventListener('input', updatePreview);
    weightInput.addEventListener('input', updatePreview);
    heartInput.addEventListener('input', updatePreview);
    previewDiv.style.display = 'none';
    
    // Setup Target selector listeners
    const targetTypeSelect = document.getElementById('pre-target-type');
    const targetValGroup = document.getElementById('target-value-group');
    const targetValLabel = document.getElementById('target-value-label');
    const targetValUnit = document.getElementById('target-value-unit');
    const targetValInput = document.getElementById('pre-target-value');
    
    if (targetTypeSelect) {
        targetTypeSelect.addEventListener('change', () => {
            const val = targetTypeSelect.value;
            if (val === 'none') {
                targetValGroup.style.display = 'none';
                targetValInput.removeAttribute('required');
            } else if (val === 'time') {
                targetValGroup.style.display = 'block';
                targetValLabel.innerText = 'Target Durasi';
                targetValUnit.innerText = 'menit';
                targetValInput.placeholder = 'Contoh: 30';
                targetValInput.setAttribute('required', 'required');
            } else if (val === 'laps') {
                targetValGroup.style.display = 'block';
                targetValLabel.innerText = 'Target Putaran (Lap)';
                targetValUnit.innerText = 'lap';
                targetValInput.placeholder = 'Contoh: 10';
                targetValInput.setAttribute('required', 'required');
            }
        });
    }
}

// Submit Pre-Run
async function handlePreRunSubmit(e) {
    e.preventDefault();
    
    const height = parseFloat(document.getElementById('pre-height').value);
    const weight = parseFloat(document.getElementById('pre-weight').value);
    const heartRate = parseInt(document.getElementById('pre-heart-rate').value);
    const systolic = parseInt(document.getElementById('pre-systolic').value);
    const diastolic = parseInt(document.getElementById('pre-diastolic').value);
    const temperature = parseFloat(document.getElementById('pre-temperature').value);
    const cholesterol = parseInt(document.getElementById('pre-cholesterol').value);
    const spo2 = parseInt(document.getElementById('pre-spo2').value);
    const locationId = parseInt(document.getElementById('start-run-location').value);
    
    const targetType = document.getElementById('pre-target-type').value;
    const targetVal = targetType !== 'none' ? parseInt(document.getElementById('pre-target-value').value) : 0;
    
    if (!locationId) {
        showAlert('Silakan pilih lokasi lari Anda terlebih dahulu.', 'warning');
        return;
    }
    
    showLoading(true);
    const res = await apiCall('start_run', {
        height, weight, heart_rate: heartRate, systolic, diastolic, temperature, cholesterol, spo2,
        target_type: targetType, target_value: targetVal, location_id: locationId
    });
    showLoading(false);
    
    if (res.status === 'success') {
        showAlert(res.message, 'success');
        
        // Dapatkan waktu saat ini dalam format YYYY-MM-DD HH:MM:SS
        const now = new Date();
        const localTimeStr = now.getFullYear() + '-' + 
            (now.getMonth()+1).toString().padStart(2, '0') + '-' + 
            now.getDate().toString().padStart(2, '0') + ' ' + 
            now.getHours().toString().padStart(2, '0') + ':' + 
            now.getMinutes().toString().padStart(2, '0') + ':' + 
            now.getSeconds().toString().padStart(2, '0');

        // Set active run
        state.activeRun = {
            id: res.run_id,
            status: 'started',
            pre_height: height,
            pre_weight: weight,
            pre_heart_rate: heartRate,
            pre_systolic: systolic,
            pre_diastolic: diastolic,
            pre_temperature: temperature,
            pre_cholesterol: cholesterol,
            pre_spo2: spo2,
            target_type: targetType,
            target_value: targetVal,
            pre_time: localTimeStr,
            location_id: locationId
        };
        
        // Reset laps in LocalStorage
        localStorage.setItem('run_laps_' + res.run_id, '0');
        state.lapsCount = 0;
        state.secondsElapsed = 0;
        state.caloriesBurned = 0;
        
        navigateTo('dashboard'); // Redirect to dashboard to show live stopwatch!
    } else {
        showAlert(res.message, 'error');
    }
}

// Post-Run Form Init
function initPostRunForm() {
    if (!state.activeRun) {
        showAlert('Tidak ada sesi lari aktif. Harap isi data Pre-Run dahulu.');
        return navigateTo('dashboard');
    }
    
    document.getElementById('post-run-form').reset();
    
    // Set Real-time Calculations Listener untuk Post-Run Heart Rate
    const hrPostInput = document.getElementById('post-heart-rate');
    const previewDiv = document.getElementById('post-realtime-calc');
    
    hrPostInput.addEventListener('input', () => {
        const hr = parseInt(hrPostInput.value);
        if (hr > 0) {
            const hrInfo = calculateHRmaxPercentage(hr, state.user.age);
            previewDiv.innerHTML = `Intensitas Latihan: <b>${hrInfo.percent}% HRmaks</b> (${hrInfo.status})`;
            previewDiv.style.display = 'block';
        } else {
            previewDiv.style.display = 'none';
        }
    });
    previewDiv.style.display = 'none';
}

// Submit Post-Run
async function handlePostRunSubmit(e) {
    e.preventDefault();
    if (!state.activeRun) return;
    
    const heartRate = parseInt(document.getElementById('post-heart-rate').value);
    const systolicVal = document.getElementById('post-systolic').value;
    const diastolicVal = document.getElementById('post-diastolic').value;
    const tempVal = document.getElementById('post-temperature').value;
    const spo2Val = document.getElementById('post-spo2').value;
    const durationVal = document.getElementById('post-duration').value;
    const lapsVal = document.getElementById('post-laps').value;
    
    const duration = durationVal !== '' ? parseInt(durationVal) : Math.max(1, Math.ceil(state.secondsElapsed / 60));
    const laps = lapsVal !== '' ? parseInt(lapsVal) : state.lapsCount;
    const distance = laps * 0.4;
    
    const postData = {
        run_id: state.activeRun.id,
        heart_rate: heartRate,
        systolic: systolicVal !== '' ? parseInt(systolicVal) : null,
        diastolic: diastolicVal !== '' ? parseInt(diastolicVal) : null,
        temperature: tempVal !== '' ? parseFloat(tempVal) : null,
        spo2: spo2Val !== '' ? parseInt(spo2Val) : null,
        duration: duration,
        laps: laps,
        distance: distance,
        calories: state.caloriesBurned
    };
    
    showLoading(true);
    const res = await apiCall('complete_run', postData);
    
    if (res.status === 'success') {
        const runId = state.activeRun.id;
        // Clean localstorage
        localStorage.removeItem('run_laps_' + runId);
        state.activeRun = null; // Clear active run
        
        // Load details for evaluation page
        const detailRes = await apiCall('get_run_detail', { run_id: runId });
        showLoading(false);
        
        if (detailRes.status === 'success') {
            showAlert(res.message, 'success');
            showEvaluationPage(detailRes.run);
        } else {
            showAlert('Gagal memuat hasil evaluasi.');
            navigateTo('dashboard');
        }
    } else {
        showLoading(false);
        showAlert(res.message, 'error');
    }
}

/// Evaluation/Summary Page - Menghitung Skor & Memberikan Penilaian Visual
function showEvaluationPage(run) {
    navigateTo('evaluation');
    
    // Usia dan Jenis Kelamin dari user di dalam run record
    const dob = run.dob;
    const birthDate = new Date(dob);
    const runDate = new Date(run.post_time || run.created_at);
    
    // Usia saat lari
    const age = runDate.getFullYear() - birthDate.getFullYear();
    const gender = run.gender;
    
    // Perhitungan Medis
    const imtInfo = calculateIMT(run.pre_weight, run.pre_height);
    const bbiInfo = calculateBBIPercentage(run.pre_weight, run.pre_height, gender);
    const preHrInfo = calculateHRmaxPercentage(run.pre_heart_rate, age);
    const postHrInfo = calculateHRmaxPercentage(run.post_heart_rate, age);
    
    // --- SKOR KESEHATAN AKTIVITAS (Composite Score out of 100) ---
    const score = calculateRunScore(run);
    let feedback = [];
    
    // 1. Indeks Massa Tubuh (IMT)
    const imtNormalMin = getIndicator('ind_imt_normal_min');
    const imtNormalMax = getIndicator('ind_imt_normal_max');
    const imtOverweightMin = getIndicator('ind_imt_overweight_min');
    const imtOverweightMax = getIndicator('ind_imt_overweight_max');
    
    if (imtInfo.val >= imtNormalMin && imtInfo.val <= imtNormalMax) {
        feedback.push({ text: 'Status IMT Anda normal. Pertahankan komposisi tubuh ideal ini!', type: 'success' });
    } else if (imtInfo.val >= imtOverweightMin && imtInfo.val <= imtOverweightMax) {
        feedback.push({ text: 'Status IMT berada di rentang Overweight (Kelebihan BB). Rekomendasi: Kontrol kalori dan tingkatkan intensitas lari secara berkala.', type: 'warning' });
    } else if (imtInfo.val < imtNormalMin) {
        feedback.push({ text: 'Status IMT kurang (Underweight). Disarankan menambah asupan kalori bernutrisi tinggi.', type: 'warning' });
    } else {
        feedback.push({ text: 'Status IMT obesitas. Harap konsultasikan program latihan intensitas sedang ke berat demi keamanan jantung.', type: 'danger' });
    }
    
    // 3. Tekanan Darah Sebelum Lari
    const sys = run.pre_systolic;
    const dia = run.pre_diastolic;
    const bpNormalSys = getIndicator('ind_bp_normal_sys');
    const bpNormalDia = getIndicator('ind_bp_normal_dia');
    const bpWarningSys = getIndicator('ind_bp_warning_sys');
    const bpWarningDia = getIndicator('ind_bp_warning_dia');
    
    if (sys <= bpNormalSys && dia <= bpNormalDia) {
        feedback.push({ text: `Tekanan darah awal normal (${sys}/${dia} mmHg). Jantung dalam kondisi prima untuk beraktivitas.`, type: 'success' });
    } else if ((sys > bpNormalSys && sys <= bpWarningSys) || (dia > bpNormalDia && dia <= bpWarningDia)) {
        feedback.push({ text: 'Tekanan darah awal tergolong Pre-Hipertensi. Hindari latihan intensitas terlalu tinggi jika merasa lelah.', type: 'warning' });
    } else {
        feedback.push({ text: 'Tekanan darah awal tinggi (Hipertensi). Sebaiknya tidak memaksakan lari cepat. Lakukan jalan cepat saja.', type: 'danger' });
    }
    
    // 4. Detak Jantung Istirahat (Resting Heart Rate)
    const hrMin = getIndicator('ind_hr_min');
    const hrMax = getIndicator('ind_hr_max');
    if (run.pre_heart_rate < hrMin || run.pre_heart_rate > hrMax) {
        feedback.push({ text: `Denyut nadi sebelum lari berada di luar rentang normal ${hrMin}-${hrMax} bpm. Pastikan Anda sudah cukup beristirahat sebelum memulai.`, type: 'warning' });
    }
    
    // 5. Oksigen Darah (SpO2)
    const spo2Min = getIndicator('ind_spo2_min');
    if (run.pre_spo2 < spo2Min) {
        feedback.push({ text: `SpO2 sebelum lari di bawah ${spo2Min}%. Kandungan oksigen dalam darah Anda rendah. Hati-hati risiko sesak napas!`, type: 'danger' });
    }
    
    // 6. Suhu Tubuh Awal
    const tempMin = getIndicator('ind_temp_min');
    const tempMax = getIndicator('ind_temp_max');
    if (run.pre_temperature < tempMin || run.pre_temperature > tempMax) {
        feedback.push({ text: 'Suhu tubuh awal tidak normal. Kondisi tubuh kurang ideal untuk berolahraga.', type: 'warning' });
    }
    
    // 7. Kolesterol Awal
    const cholMax = getIndicator('ind_chol_max');
    if (run.pre_cholesterol >= cholMax) {
        feedback.push({ text: `Kadar kolesterol awal tinggi (>= ${cholMax} mg/dL). Latihan kardio secara teratur (lari santai) sangat disarankan untuk membantu menurunkannya.`, type: 'warning' });
    }
    
    // 8. Evaluasi Target Lari
    const targetType = run.target_type || 'none';
    const targetVal = parseInt(run.target_value) || 0;
    if (targetType !== 'none' && targetVal > 0) {
        if (targetType === 'time') {
            const actualMin = run.post_duration || 0;
            if (actualMin >= targetVal) {
                feedback.push({ text: `Selamat! Target waktu lari Anda (${targetVal} menit) berhasil tercapai! (Aktual: ${actualMin} menit)`, type: 'success' });
            } else {
                feedback.push({ text: `Target waktu lari Anda adalah ${targetVal} menit, namun Anda menyelesaikan dalam ${actualMin} menit. Terus berlatih untuk meningkatkan stamina!`, type: 'warning' });
            }
        } else if (targetType === 'laps') {
            const actualLaps = run.post_laps || 0;
            if (actualLaps >= targetVal) {
                feedback.push({ text: `Selamat! Target putaran stadion Anda (${targetVal} lap / ${(targetVal * 0.4).toFixed(1)} km) berhasil tercapai!`, type: 'success' });
            } else {
                feedback.push({ text: `Target putaran Anda adalah ${targetVal} lap, namun Anda baru mencapai ${actualLaps} lap. Semangat untuk sesi berikutnya!`, type: 'warning' });
            }
        }
    }

    // Evaluasi Efektivitas Latihan dari % HRmaks Aktivitas
    if (postHrInfo.percent >= 50 && postHrInfo.percent < 85) {
        feedback.push({ text: `Latihan selesai! Intensitas latihan masuk zona ideal (${postHrInfo.percent}% HRmaks - ${postHrInfo.status}). Sangat bagus untuk kesehatan jantung (kardio).`, type: 'success' });
    } else if (postHrInfo.percent >= 85) {
        feedback.push({ text: `Peringatan: Detak jantung pasca lari sangat tinggi (${postHrInfo.percent}% HRmaks - ${postHrInfo.status}). Kurangi intensitas lari Anda agar tidak membebani kerja jantung secara berlebihan.`, type: 'danger' });
    } else {
        feedback.push({ text: `Lari sore selesai, namun intensitas tergolong sangat ringan (${postHrInfo.percent}% HRmaks). Coba tingkatkan kecepatan/durasi secara bertahap pada sesi berikutnya.`, type: 'warning' });
    }
    
    // Tampilkan Skor di Ring Gaugue
    document.getElementById('eval-score').innerText = score;
    
    // Update SVG Circle Offset (r=60, circumference = 2 * PI * r = 377)
    const circle = document.getElementById('eval-gauge-circle');
    if (circle) {
        const offset = 377 - (377 * score / 100);
        circle.style.strokeDashoffset = offset;
    }
    
    // Atur Warna Teks Skor
    const scoreEl = document.getElementById('eval-score');
    if (score >= 80) scoreEl.style.color = 'var(--accent-green)';
    else if (score >= 60) scoreEl.style.color = 'var(--accent-yellow)';
    else scoreEl.style.color = 'var(--accent-red)';
    
    // Tampilkan Tanggal
    document.getElementById('eval-date').innerText = formatDateString(runDate);
    
    // Tampilkan Lokasi
    const evalLoc = document.getElementById('eval-location');
    if (evalLoc) {
        if (run.location_name) {
            evalLoc.innerHTML = `📍 ${run.location_name} ${run.google_maps_url ? `<a href="${run.google_maps_url}" target="_blank" class="link-action" style="font-size: 12px; margin-left: 6px;">(Google Maps)</a>` : ''}`;
            evalLoc.style.display = 'block';
        } else {
            evalLoc.style.display = 'none';
        }
    }
    
    // Render Parameter Vital Sebelum vs Sesudah
    const gridEl = document.getElementById('eval-comparison-grid');
    gridEl.innerHTML = `
        <!-- Heart Rate Row -->
        <div class="metric-comparison-row">
            <div class="details">
                <span class="title">Detak Jantung (Heart Rate)</span>
                <span class="desc">${preHrInfo.status} vs ${postHrInfo.status}</span>
            </div>
            <div class="metric-values">
                <div class="val-box pre">
                    <span class="time-lbl">Awal</span>
                    <span class="num">${run.pre_heart_rate}</span>
                </div>
                <div class="val-box post">
                    <span class="time-lbl">Akhir</span>
                    <span class="num">${run.post_heart_rate}</span>
                </div>
            </div>
        </div>
        
        <!-- Tekanan Darah -->
        <div class="metric-comparison-row">
            <div class="details">
                <span class="title">Tekanan Darah (Tensi)</span>
                <span class="desc">${run.pre_systolic}/${run.pre_diastolic} mmHg</span>
            </div>
            <div class="metric-values">
                <div class="val-box pre">
                    <span class="time-lbl">Awal</span>
                    <span class="num">${run.pre_systolic}/${run.pre_diastolic}</span>
                </div>
                <div class="val-box post">
                    <span class="time-lbl">Akhir</span>
                    <span class="num">${run.post_systolic && run.post_diastolic ? run.post_systolic + '/' + run.post_diastolic : '-'}</span>
                </div>
            </div>
        </div>

        <!-- SpO2 -->
        <div class="metric-comparison-row">
            <div class="details">
                <span class="title">Kandungan Oksigen (SpO2)</span>
                <span class="desc">Rentang Normal: >= ${getIndicator('ind_spo2_min')}%</span>
            </div>
            <div class="metric-values">
                <div class="val-box pre">
                    <span class="time-lbl">Awal</span>
                    <span class="num">${run.pre_spo2}%</span>
                </div>
                <div class="val-box post">
                    <span class="time-lbl">Akhir</span>
                    <span class="num">${run.post_spo2 ? run.post_spo2 + '%' : '-'}</span>
                </div>
            </div>
        </div>

        <!-- Suhu Tubuh -->
        <div class="metric-comparison-row">
            <div class="details">
                <span class="title">Suhu Tubuh</span>
                <span class="desc">Rentang Normal: ${getIndicator('ind_temp_min')} - ${getIndicator('ind_temp_max')}°C</span>
            </div>
            <div class="metric-values">
                <div class="val-box pre">
                    <span class="time-lbl">Awal</span>
                    <span class="num">${run.pre_temperature}°C</span>
                </div>
                <div class="val-box post">
                    <span class="time-lbl">Akhir</span>
                    <span class="num">${run.post_temperature ? run.post_temperature + '°' : '-'}</span>
                </div>
            </div>
        </div>
        
        <!-- BMI & BBI (Static) -->
        <div class="metric-comparison-row">
            <div class="details">
                <span class="title">IMT: ${imtInfo.val} (${imtInfo.status})</span>
                <span class="desc">Berat Ideal (BBI): ${bbiInfo.bbi} kg (%BBI: ${bbiInfo.percent}%)</span>
            </div>
            <div class="metric-values">
                <div class="val-box pre">
                    <span class="time-lbl">BB</span>
                    <span class="num">${run.pre_weight}kg</span>
                </div>
                <div class="val-box post" style="background: rgba(255, 255, 255, 0.05); border: none;">
                    <span class="time-lbl">TB</span>
                    <span class="num">${run.pre_height}cm</span>
                </div>
            </div>
        </div>

        <!-- Jarak & Lap -->
        <div class="metric-comparison-row">
            <div class="details">
                <span class="title">Jarak Tempuh & Putaran</span>
                <span class="desc">Berdasarkan putaran lintasan 400m</span>
            </div>
            <div class="metric-values">
                <div class="val-box pre">
                    <span class="time-lbl">Jarak</span>
                    <span class="num">${parseFloat(run.post_distance || 0).toFixed(2)}km</span>
                </div>
                <div class="val-box post">
                    <span class="time-lbl">Lap</span>
                    <span class="num">${run.post_laps || 0}</span>
                </div>
            </div>
        </div>

        <!-- Kolesterol & Kalori -->
        <div class="metric-comparison-row">
            <div class="details">
                <span class="title">Durasi, Kolesterol & Kalori</span>
                <span class="desc">Durasi: ${run.post_duration || 0} menit</span>
            </div>
            <div class="metric-values">
                <div class="val-box pre">
                    <span class="time-lbl">Kalori</span>
                    <span class="num">${run.post_calories || 0} kcal</span>
                </div>
                <div class="val-box post">
                    <span class="time-lbl">Kolest.</span>
                    <span class="num">${run.pre_cholesterol}</span>
                </div>
            </div>
        </div>
    `;
    
    // Render Feedback / Recommendations
    const recDiv = document.getElementById('eval-recommendations');
    recDiv.innerHTML = '';
    feedback.forEach(item => {
        const itemEl = document.createElement('div');
        itemEl.className = `rec-item ${item.type}`;
        
        // Pilih icon berdasarkan type
        let iconSvg = '';
        if (item.type === 'success') {
            iconSvg = `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
        } else if (item.type === 'warning') {
            iconSvg = `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>`;
        } else {
            iconSvg = `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
        }
        
        itemEl.innerHTML = `
            <div class="rec-item-icon">${iconSvg}</div>
            <div class="rec-item-text">${item.text}</div>
        `;
        recDiv.appendChild(itemEl);
    });
}


// History Page
async function loadHistory() {
    showLoading(true);
    const res = await apiCall('get_history');
    showLoading(false);
    
    const container = document.getElementById('history-list-container');
    
    if (res.status === 'success') {
        state.history = res.history;
        
        if (state.history.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    <h3>Belum Ada Riwayat Lari</h3>
                    <p>Mulai lari sore Anda hari ini dan catat perkembangan kesehatan Anda di sini!</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = '';
        state.history.forEach(run => {
            const date = new Date(run.post_time || run.created_at);
            
            // Perhitungan instan untuk history card
            const dob = run.dob;
            const birthDate = new Date(dob);
            const age = date.getFullYear() - birthDate.getFullYear();
            const imtInfo = calculateIMT(run.pre_weight, run.pre_height);
            const postHrInfo = calculateHRmaxPercentage(run.post_heart_rate, age);
            
            // Hitung Score untuk history card
            const score = calculateRunScore(run);
            
            const dist = parseFloat(run.post_distance || 0).toFixed(1);
            const cal = run.post_calories || 0;
            const itemEl = document.createElement('div');
            itemEl.className = 'history-item animated-fade';
            itemEl.onclick = () => showEvaluationPage(run);
            itemEl.innerHTML = `
                <div class="history-meta">
                    <div class="history-date">${formatDateString(date)}</div>
                    <div class="history-sub" style="margin-bottom: 4px;">
                        <span>📍 <b>${run.location_name || 'Lokasi Umum'}</b></span>
                    </div>
                    <div class="history-sub">
                        <span><b>${dist} km</b> (${run.post_laps || 0} lap)</span>
                        <span>•</span>
                        <span><b>${cal} kcal</b></span>
                        <span>•</span>
                        <span>Intensitas: <span class="badge ${postHrInfo.class}" style="font-size: 8px; padding: 1px 6px;">${postHrInfo.percent}%</span></span>
                    </div>
                </div>
                <div class="history-metrics">
                    <div class="history-score">${score} / 100</div>
                    <div class="history-sub">Skor Aktivitas</div>
                </div>
            `;
            container.appendChild(itemEl);
        });
    } else {
        showAlert(res.message, 'error');
    }
}

// --- WORKOUT STOPWATCH & ACTIVE UI TRACKING ---

function startActiveTimer() {
    if (state.timerInterval) clearInterval(state.timerInterval);
    
    // Calculate seconds already elapsed if they refreshed the page
    const startTimeStr = state.activeRun.pre_time;
    let startTime;
    if (startTimeStr.includes(' ')) {
        // Replace space with T to make it ISO compliant
        startTime = new Date(startTimeStr.replace(' ', 'T')).getTime();
    } else {
        startTime = new Date(startTimeStr).getTime();
    }
    const now = new Date().getTime();
    state.secondsElapsed = Math.max(0, Math.floor((now - startTime) / 1000));
    
    // Load laps from local storage to survive refreshes
    const savedLaps = localStorage.getItem('run_laps_' + state.activeRun.id);
    state.lapsCount = parseInt(savedLaps) || 0;
    
    // Show workout beats music card
    const musicCard = document.getElementById('workout-music-card');
    if (musicCard) musicCard.style.display = 'block';

    updateLiveWorkoutUI();

    state.timerInterval = setInterval(() => {
        state.secondsElapsed++;
        updateLiveWorkoutUI();
    }, 1000);
}

function updateLiveWorkoutUI() {
    const timerDisplay = document.getElementById('active-timer');
    if (!timerDisplay) return;
    
    // Format MM:SS or HH:MM:SS
    const hrs = Math.floor(state.secondsElapsed / 3600);
    const mins = Math.floor((state.secondsElapsed % 3600) / 60);
    const secs = state.secondsElapsed % 60;
    
    let timeStr = "";
    if (hrs > 0) {
        timeStr = `${hrs.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    } else {
        timeStr = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    timerDisplay.innerText = timeStr;
    
    // Calculate distance (1 lap = 0.4 km)
    const distanceKm = state.lapsCount * 0.4;
    document.getElementById('active-distance').innerText = `${distanceKm.toFixed(2)} km`;
    document.getElementById('active-laps-lbl').innerText = `${state.lapsCount} Lap`;
    
    // Calculate calories (MET=8.0 for running, weight in kg)
    const weight = state.activeRun.pre_weight || 70;
    const hours = state.secondsElapsed / 3600;
    state.caloriesBurned = Math.round(8.0 * weight * hours);
    document.getElementById('active-calories').innerText = `${state.caloriesBurned} kcal`;
    
    // Target calculations
    const targetType = state.activeRun.target_type || 'none';
    const targetVal = parseInt(state.activeRun.target_value) || 0;
    const targetPanel = document.getElementById('active-target-panel');
    
    if (targetType !== 'none' && targetVal > 0) {
        targetPanel.style.display = 'block';
        let progress = 0;
        let desc = '';
        
        if (targetType === 'time') {
            progress = (mins / targetVal) * 100;
            desc = `Target Waktu: ${mins}/${targetVal} m`;
        } else if (targetType === 'laps') {
            progress = (state.lapsCount / targetVal) * 100;
            desc = `Target Putaran: ${state.lapsCount}/${targetVal} lap`;
        }
        
        progress = Math.min(100, Math.round(progress));
        document.getElementById('active-target-desc').innerText = desc;
        document.getElementById('active-target-percent').innerText = `${progress}%`;
        document.getElementById('active-target-bar').style.width = `${progress}%`;
        
        // Target achieved visualization change
        if (progress >= 100) {
            document.getElementById('active-target-percent').innerHTML = '<span class="badge badge-normal" style="font-size: 9px; padding: 1px 6px;">Target Selesai!</span>';
        }
    } else {
        targetPanel.style.display = 'none';
    }
}

function handleIncrementLap() {
    if (!state.activeRun) return;
    state.lapsCount++;
    localStorage.setItem('run_laps_' + state.activeRun.id, state.lapsCount);
    updateLiveWorkoutUI();
    showAlert('Putaran (Lap) ditambah!', 'success');
}

function switchMusicSource(source) {
    const spotPlayer = document.getElementById('player-spotify');
    const ytPlayer = document.getElementById('player-youtube');
    const btnSpot = document.getElementById('btn-music-spotify');
    const btnYt = document.getElementById('btn-music-youtube');
    const controlsSpot = document.getElementById('controls-spotify');
    const controlsYt = document.getElementById('controls-youtube');
    
    if (!spotPlayer || !ytPlayer || !btnSpot || !btnYt) return;
    
    if (source === 'spotify') {
        spotPlayer.style.display = 'block';
        ytPlayer.style.display = 'none';
        if (controlsSpot) controlsSpot.style.display = 'block';
        if (controlsYt) controlsYt.style.display = 'none';
        
        btnSpot.className = 'badge badge-normal';
        btnSpot.style.background = 'rgba(255, 0, 127, 0.08)';
        btnSpot.style.borderColor = 'var(--primary)';
        
        btnYt.className = 'badge';
        btnYt.style.background = 'transparent';
        btnYt.style.borderColor = 'var(--card-border)';
        btnYt.style.color = 'var(--text-muted)';
    } else {
        spotPlayer.style.display = 'none';
        ytPlayer.style.display = 'block';
        if (controlsSpot) controlsSpot.style.display = 'none';
        if (controlsYt) controlsYt.style.display = 'block';
        
        btnYt.className = 'badge badge-normal';
        btnYt.style.background = 'rgba(255, 0, 127, 0.08)';
        btnYt.style.borderColor = 'var(--primary)';
        
        btnSpot.className = 'badge';
        btnSpot.style.background = 'transparent';
        btnSpot.style.borderColor = 'var(--card-border)';
        btnSpot.style.color = 'var(--text-muted)';
    }
}

function handleSpotifyPlaylistChange() {
    const select = document.getElementById('select-spotify-playlist');
    const customContainer = document.getElementById('custom-spotify-container');
    const iframe = document.getElementById('iframe-spotify');
    
    if (!select || !customContainer || !iframe) return;
    
    if (select.value === 'custom') {
        customContainer.style.display = 'flex';
    } else {
        customContainer.style.display = 'none';
        iframe.src = `https://open.spotify.com/embed/playlist/${select.value}?utm_source=generator&theme=0`;
    }
}

function loadCustomSpotifyPlaylist() {
    const inputEl = document.getElementById('input-custom-spotify');
    const iframe = document.getElementById('iframe-spotify');
    if (!inputEl || !iframe) return;
    
    const input = inputEl.value.trim();
    if (!input) {
        showAlert('Silakan masukkan Link Playlist atau ID Spotify terlebih dahulu.', 'warning');
        return;
    }
    
    let playlistId = input;
    if (input.includes('spotify.com')) {
        const parts = input.split('/playlist/');
        if (parts.length > 1) {
            playlistId = parts[1].split('?')[0];
        } else {
            showAlert('Format link Spotify tidak dikenali. Masukkan URL playlist yang valid.', 'error');
            return;
        }
    }
    
    iframe.src = `https://open.spotify.com/embed/playlist/${playlistId}?utm_source=generator&theme=0`;
    showAlert('Playlist Spotify kustom berhasil dimuat!', 'success');
}

function handleYoutubeVideoChange() {
    const select = document.getElementById('select-youtube-video');
    const customContainer = document.getElementById('custom-youtube-container');
    const iframe = document.getElementById('iframe-youtube');
    
    if (!select || !customContainer || !iframe) return;
    
    if (select.value === 'custom') {
        customContainer.style.display = 'flex';
    } else {
        customContainer.style.display = 'none';
        iframe.src = `https://www.youtube.com/embed/${select.value}`;
    }
}

function loadCustomYoutubeVideo() {
    const inputEl = document.getElementById('input-custom-youtube');
    const iframe = document.getElementById('iframe-youtube');
    if (!inputEl || !iframe) return;
    
    const input = inputEl.value.trim();
    if (!input) {
        showAlert('Silakan masukkan Link Video atau ID YouTube terlebih dahulu.', 'warning');
        return;
    }
    
    let videoId = input;
    if (input.includes('youtube.com') || input.includes('youtu.be')) {
        if (input.includes('watch?v=')) {
            const parts = input.split('watch?v=');
            videoId = parts[1].split('&')[0];
        } else if (input.includes('youtu.be/')) {
            const parts = input.split('youtu.be/');
            videoId = parts[1].split('?')[0];
        } else if (input.includes('embed/')) {
            const parts = input.split('embed/');
            videoId = parts[1].split('?')[0];
        } else if (input.includes('shorts/')) {
            const parts = input.split('shorts/');
            videoId = parts[1].split('?')[0];
        } else {
            showAlert('Format link YouTube tidak dikenali. Masukkan URL video yang valid.', 'error');
            return;
        }
    }
    
    iframe.src = `https://www.youtube.com/embed/${videoId}`;
    showAlert('Video YouTube kustom berhasil dimuat!', 'success');
}



// --- UTILITY FUNCTIONS ---

function formatDateString(dateObj) {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return dateObj.toLocaleDateString('id-ID', options);
}

// UI Event Listeners & Bootstrapping
document.addEventListener('DOMContentLoaded', () => {
    // 1. Cek Sesi Autentikasi Awal
    checkAuth();
    
    // 2. Setup Form Submissions
    const loginForm = document.getElementById('login-form');
    if (loginForm) loginForm.addEventListener('submit', handleLogin);
    
    const regForm = document.getElementById('register-form');
    if (regForm) regForm.addEventListener('submit', handleRegister);
    
    const preForm = document.getElementById('pre-run-form');
    if (preForm) preForm.addEventListener('submit', handlePreRunSubmit);
    
    const postForm = document.getElementById('post-run-form');
    if (postForm) postForm.addEventListener('submit', handlePostRunSubmit);
    
    // 3. Setup Gender Switch di Pendaftaran
    const maleBox = document.getElementById('gender-male');
    const femaleBox = document.getElementById('gender-female');
    
    if (maleBox && femaleBox) {
        maleBox.addEventListener('click', () => {
            state.currentGender = 'L';
            maleBox.classList.add('selected');
            femaleBox.classList.remove('selected');
        });
        femaleBox.addEventListener('click', () => {
            state.currentGender = 'P';
            femaleBox.classList.add('selected');
            maleBox.classList.remove('selected');
        });
    }
});
