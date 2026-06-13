<?php
/**
 * Admin Portal View - Running Health Tracker
 * 
 * Di-host di bawah folder /tukangketik/
 * Mengelola antarmuka dashboard untuk administrator sistem.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$isAdminLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(getSetting('app_name', 'Stadion Run')) ?> - Portal Admin</title>
    
    <!-- Meta SEO -->
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Google Fonts - Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Leaflet Map CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    
    <!-- Custom styling matching visual excellence parameters -->
    <style>
        :root {
            --bg-gradient: #ffffff;
            --primary: #ff007f; /* Neon Pink */
            --primary-glow: rgba(255, 0, 127, 0.12);
            --secondary: #8b5cf6; /* Vibrant Purple */
            --navy: #0f172a;
            --accent-green: #10b981;
            --accent-yellow: #f59e0b;
            --accent-red: #ef4444;
            --card-bg: rgba(255, 255, 255, 0.75);
            --card-border: rgba(255, 0, 127, 0.12);
            --card-hover-border: rgba(255, 0, 127, 0.3);
            --text-main: #0f172a;
            --text-muted: #475569;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --shadow: 0 10px 30px -10px rgba(255, 0, 127, 0.08);
            --shadow-glow: 0 4px 15px var(--primary-glow);
        }

        @keyframes alert-pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.15); }
            100% { transform: scale(1); }
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background: var(--bg-gradient);
            color: var(--text-main);
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
        }

        /* --- STYLES FOR AUTHENTICATION / LOGIN --- */
        .login-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            background: linear-gradient(135deg, rgba(255,0,127,0.02) 0%, rgba(139,92,246,0.02) 100%);
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 35px 30px;
            box-shadow: 0 20px 40px -15px rgba(255, 0, 127, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            transition: var(--transition);
        }

        .login-card:hover {
            border-color: var(--card-hover-border);
        }

        .login-card h1 {
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-align: center;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .login-card p {
            font-size: 13px;
            color: var(--text-muted);
            text-align: center;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 16px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 6px;
            padding-left: 4px;
        }

        .input-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-container svg {
            position: absolute;
            left: 16px;
            width: 20px;
            height: 20px;
            color: var(--text-muted);
            transition: var(--transition);
        }

        .input-container input {
            width: 100%;
            padding: 12px 16px 12px 48px;
            background: #ffffff;
            border: 1.5px solid var(--card-border);
            border-radius: 12px;
            color: var(--text-main);
            font-size: 14px;
            outline: none;
            transition: var(--transition);
        }

        .input-container input:focus {
            border: 1.5px solid transparent;
            background-image: linear-gradient(#ffffff, #ffffff), linear-gradient(135deg, var(--primary), var(--secondary));
            background-origin: border-box;
            background-clip: padding-box, border-box;
            box-shadow: 0 4px 15px rgba(255, 0, 127, 0.08);
        }

        .input-container input:focus + svg {
            color: var(--primary);
        }

        .btn {
            width: 100%;
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            box-shadow: var(--shadow-glow);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 0, 127, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        /* --- STYLES FOR MAIN ADMIN DASHBOARD --- */
        .admin-shell {
            display: flex;
            min-height: 100vh;
            background: #f8fafc;
        }

        /* Sidebar Navigation */
        .sidebar {
            width: 260px;
            background: var(--navy);
            color: white;
            display: flex;
            flex-direction: column;
            padding: 30px 20px;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            height: 100vh;
            z-index: 100;
            transition: var(--transition);
        }

        .sidebar .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 40px;
            padding-left: 10px;
        }

        .sidebar .brand-logo {
            font-size: 24px;
        }

        .sidebar .brand-name {
            font-size: 18px;
            font-weight: 800;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }

        .sidebar-menu {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
        }

        .sidebar-item a {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #94a3b8;
            text-decoration: none;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            transition: var(--transition);
        }

        .sidebar-item.active a,
        .sidebar-item a:hover {
            background: rgba(255, 0, 127, 0.1);
            color: white;
        }

        .sidebar-item.active a {
            border-left: 3px solid var(--primary);
            border-radius: 0 12px 12px 0;
            background: linear-gradient(to right, rgba(255, 0, 127, 0.15), transparent);
        }

        .sidebar-footer {
            margin-top: auto;
            border-top: 1px solid rgba(255,255,255,0.08);
            padding-top: 20px;
        }

        .btn-logout {
            width: 100%;
            padding: 10px;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #f87171;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-logout:hover {
            background: #ef4444;
            color: white;
            border-color: #ef4444;
        }

        /* Main Workspace Content */
        .workspace {
            flex: 1;
            padding: 40px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding-bottom: 20px;
        }

        .admin-header h1 {
            font-size: 26px;
            font-weight: 800;
            color: var(--navy);
            background: none;
            -webkit-text-fill-color: initial;
        }

        .admin-header p {
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* Header action tools (print, filter) */
        .header-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .btn-action {
            background: white;
            border: 1.5px solid var(--card-border);
            border-radius: 10px;
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-main);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .btn-action:hover {
            border-color: var(--primary);
            color: var(--primary);
            box-shadow: var(--shadow);
        }

        /* Stats Dashboard Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 10px;
        }

        .stat-card {
            background: white;
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 24px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            border-color: var(--primary);
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary);
        }

        .stat-card.secondary::after {
            background: var(--secondary);
        }

        .stat-card.green::after {
            background: var(--accent-green);
        }

        .stat-card.yellow::after {
            background: var(--accent-yellow);
        }

        .stat-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 800;
            color: var(--navy);
            line-height: 1.1;
        }

        .stat-desc {
            font-size: 11px;
            color: var(--text-muted);
        }

        /* Two-Column Analytics Layout */
        .analytics-row {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 24px;
        }

        @media (max-width: 1024px) {
            .analytics-row {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: white;
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 24px;
            box-shadow: var(--shadow);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .card-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--navy);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title svg {
            width: 20px;
            height: 20px;
            color: var(--primary);
        }

        /* Leaflet Map Div */
        #map {
            height: 400px;
            width: 100%;
            border-radius: 16px;
            border: 1px solid var(--card-border);
            z-index: 5;
        }

        /* Custom marker popup styling */
        .leaflet-popup-content-wrapper {
            border-radius: 12px;
            box-shadow: var(--shadow);
            border: 1px solid var(--card-border);
        }

        .leaflet-popup-content {
            font-family: 'Outfit', sans-serif;
            font-size: 12px;
            color: var(--text-main);
        }

        /* Demographic charts container */
        .chart-container {
            position: relative;
            height: 180px;
            width: 100%;
            margin-bottom: 15px;
        }

        /* Table & Lists Styles */
        .table-card {
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .table-filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
        }

        .search-input {
            flex: 1;
            max-width: 320px;
            padding: 10px 16px;
            border: 1.5px solid var(--card-border);
            border-radius: 10px;
            font-size: 13px;
            outline: none;
            transition: var(--transition);
        }

        .search-input:focus {
            border-color: var(--primary);
            box-shadow: var(--shadow);
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--card-border);
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13.5px;
        }

        .table-responsive table {
            min-width: 950px;
        }

        th {
            background: #f1f5f9;
            color: var(--text-muted);
            font-weight: 600;
            padding: 14px 16px;
            border-bottom: 1.5px solid var(--card-border);
            font-size: 12px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid rgba(0,0,0,0.03);
            color: var(--text-main);
            white-space: nowrap;
        }

        .col-name {
            min-width: 180px;
            white-space: normal !important;
        }

        .col-location {
            min-width: 180px;
            white-space: normal !important;
        }

        .col-address {
            min-width: 220px;
            white-space: normal !important;
        }

        tr:hover td {
            background: rgba(255, 0, 127, 0.01);
        }

        tr:last-child td {
            border-bottom: none;
        }

        .btn-table-action {
            background: rgba(255, 0, 127, 0.05);
            border: 1px solid rgba(255, 0, 127, 0.15);
            color: var(--primary);
            font-size: 11px;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-table-action:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .gender-badge {
            font-weight: 700;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .gender-badge.m {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .gender-badge.f {
            background: rgba(236, 72, 153, 0.1);
            color: #ec4899;
        }

        /* Modal Profile Drawer Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(8px);
            z-index: 1000;
            display: flex;
            justify-content: flex-end;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.4s ease;
        }

        .modal.active {
            opacity: 1;
            pointer-events: all;
        }

        .modal-content {
            width: 100%;
            max-width: 550px;
            height: 100%;
            background: white;
            box-shadow: -10px 0 30px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            padding: 35px 30px;
            overflow-y: auto;
            transform: translateX(100%);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .modal.active .modal-content {
            transform: translateX(0);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding-bottom: 15px;
        }

        .modal-header h2 {
            font-size: 20px;
            color: var(--navy);
            font-weight: 700;
        }

        .btn-close {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            transition: var(--transition);
        }

        .btn-close:hover {
            color: var(--accent-red);
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 25px;
            background: #f8fafc;
            padding: 20px;
            border-radius: 16px;
            border: 1px solid var(--card-border);
        }

        .profile-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .profile-item .label {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .profile-item .value {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-main);
        }

        /* Toast notifications */
        .toast {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            padding: 12px 24px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            opacity: 0;
            transition: opacity 0.4s ease;
            pointer-events: none;
        }

        .toast.success { background: #10b981; color: white; }
        .toast.error { background: #ef4444; color: white; }
        .toast.warning { background: #f59e0b; color: white; }
        .toast.active { opacity: 1; }

        /* Badge status runs */
        .run-badge {
            display: inline-block;
            white-space: nowrap;
            font-size: 11px;
            font-weight: 700;
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
        }

        .run-badge.perfect { background: var(--accent-green); }
        .run-badge.warning { background: var(--accent-yellow); }
        .run-badge.danger { background: var(--accent-red); }

        /* Report print styling */
        #print-section {
            display: none;
        }

        @media print {
            body {
                background: white;
                color: #000;
                font-size: 12px;
            }
            
            /* Sembunyikan sidebar, modal, toast dan header-actions secara universal */
            .sidebar, .modal, .toast, .header-actions, .table-filter-bar {
                display: none !important;
            }
            
            /* Jika mencetak seluruh laporan (default print) */
            body:not(.print-runners-only):not(.print-runs-only):not(.print-qr-only) .workspace {
                display: none !important;
            }
            body:not(.print-runners-only):not(.print-runs-only):not(.print-qr-only) #print-section {
                display: block !important;
                padding: 20px;
            }
            
            /* Jika mencetak pelari saja */
            body.print-runners-only .workspace {
                display: block !important;
                padding: 0 !important;
                background: white;
            }
            body.print-runners-only .workspace > * {
                display: none !important;
            }
            body.print-runners-only #runners-list {
                display: block !important;
                width: 100% !important;
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
            body.print-runners-only #runners-list th, body.print-runners-only #runners-list td {
                border: 1px solid #cbd5e1;
            }
            body.print-runners-only #runners-list td:last-child, body.print-runners-only #runners-list th:last-child {
                display: none !important; /* Sembunyikan kolom Aksi */
            }
            
            /* Jika mencetak riwayat lari saja */
            body.print-runs-only .workspace {
                display: block !important;
                padding: 0 !important;
                background: white;
            }
            body.print-runs-only .workspace > * {
                display: none !important;
            }
            body.print-runs-only #recent-runs-sec {
                display: block !important;
                width: 100% !important;
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
            body.print-runs-only #recent-runs-sec th, body.print-runs-only #recent-runs-sec td {
                border: 1px solid #cbd5e1;
            }
            
            /* Reset min-width and wrapping for printing */
            .table-responsive table {
                min-width: 100% !important;
            }
            .table-responsive th, .table-responsive td {
                white-space: normal !important;
            }
            
            /* Jika mencetak QR saja */
            body.print-qr-only .workspace {
                display: block !important;
                padding: 0 !important;
                background: white;
            }
            body.print-qr-only .workspace > * {
                display: none !important;
            }
            body.print-qr-only #tab-qr-code {
                display: block !important;
                border: none !important;
                box-shadow: none !important;
                padding: 20px !important;
                max-width: none !important;
            }
            body.print-qr-only #tab-qr-code .btn {
                display: none !important; /* Jangan print tombol cetak */
            }
            
            .print-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            .print-table th, .print-table td {
                border: 1px solid #cbd5e1;
                padding: 10px;
                text-align: left;
            }
            .print-table th {
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        /* --- RESPONSIVE LAYOUT FOR TABLET & MOBILE --- */
        .mobile-navbar {
            display: none;
            background: var(--navy);
            color: white;
            padding: 15px 20px;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 900;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .mobile-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .mobile-brand-logo {
            font-size: 20px;
        }

        .mobile-brand-name {
            font-size: 16px;
            font-weight: 800;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }

        .btn-hamburger {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px;
        }

        .sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 998;
        }

        @media (max-width: 768px) {
            .mobile-navbar {
                display: flex;
            }

            .admin-shell {
                flex-direction: column;
            }

            .sidebar {
                position: fixed;
                left: -260px;
                top: 0;
                bottom: 0;
                height: 100vh;
                width: 260px;
                z-index: 999;
                box-shadow: 10px 0 30px rgba(0, 0, 0, 0.15);
                transform: translateX(0);
                transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .sidebar.active {
                left: 0;
            }

            .sidebar-backdrop.active {
                display: block;
            }

            .workspace {
                padding: 20px 15px;
            }

            .admin-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .header-actions {
                width: 100%;
            }

            .header-actions .btn-action {
                width: 100%;
                justify-content: center;
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .card-header div {
                width: 100%;
                flex-direction: column;
                align-items: stretch !important;
                gap: 8px !important;
            }

            .card-header .btn-action {
                width: 100%;
                justify-content: center;
                margin-top: 0;
            }

            .card-header .search-input {
                width: 100% !important;
                max-width: none !important;
                margin-left: 0 !important;
            }

            .profile-grid {
                grid-template-columns: 1fr;
                padding: 15px;
            }

            .profile-grid .profile-item {
                grid-column: span 1 !important;
            }
        }

        /* SweetAlert2 visual excellence styling override */
        .swal2-custom-popup {
            border-radius: 24px !important;
            border: 1px solid var(--card-border) !important;
            box-shadow: 0 20px 40px -15px rgba(255, 0, 127, 0.1) !important;
            font-family: 'Outfit', sans-serif !important;
        }

        /* Responsive locations management grid style */
        .locations-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 24px;
            align-items: start;
        }

        @media (max-width: 1024px) {
            .locations-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .locations-grid {
                gap: 16px;
            }
            .lat-lng-row {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</head>
<body>

    <!-- Toast popup -->
    <div id="toast-el" class="toast"></div>

    <?php if (!$isAdminLoggedIn): ?>
        <!-- ================= LOGIN WINDOW ================= -->
        <div class="login-wrapper">
            <div class="login-card">
                <h1>ADMIN <?= htmlspecialchars(strtoupper(getSetting('app_name', 'STADION RUN'))) ?></h1>
                <p>Silakan masuk untuk mengelola data atlet dan pelari sore.</p>
                
                <form id="admin-login-form" autocomplete="off">
                    <div class="form-group">
                        <label for="admin-user">Username</label>
                        <div class="input-container">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            <input type="text" id="admin-user" placeholder="Username admin" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin-pass">Password</label>
                        <div class="input-container">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            <input type="password" id="admin-pass" placeholder="Password admin" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn mt-4">
                        <span>Masuk Portal</span>
                    </button>
                </form>
            </div>
        </div>

        <script>
            // JavaScript login admin
            document.getElementById('admin-login-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const username = document.getElementById('admin-user').value;
                const password = document.getElementById('admin-pass').value;

                try {
                    const res = await fetch('admin-api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'login', username, password })
                    });
                    const data = await res.json();
                    
                    if (data.status === 'success') {
                        showToast(data.message, 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showToast(data.message, 'error');
                    }
                } catch (err) {
                    showToast('Gagal menghubungi server.', 'error');
                }
            });

            function showToast(msg, type = 'error') {
                const toast = document.getElementById('toast-el');
                toast.innerText = msg;
                toast.className = `toast ${type} active`;
                setTimeout(() => {
                    toast.classList.remove('active');
                }, 3000);
            }
        </script>

    <?php else: ?>
        <!-- Sidebar Backdrop Overlay on Mobile -->
        <div class="sidebar-backdrop" onclick="toggleSidebar()"></div>

        <!-- Mobile Sticky Top Navbar -->
        <div class="mobile-navbar">
            <div class="mobile-brand">
                <span class="mobile-brand-logo" id="mobile-app-logo"><?= htmlspecialchars(getSetting('app_logo', '🏃‍♂️')) ?></span>
                <span class="mobile-brand-name" id="mobile-app-name"><?= htmlspecialchars(strtoupper(getSetting('app_name', 'STADION RUN'))) ?></span>
            </div>
            <button class="btn-hamburger" onclick="toggleSidebar()">
                <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </div>

        <!-- ================= MAIN DASHBOARD WINDOW ================= -->
        <div class="admin-shell">
            
            <!-- Sidebar Panel -->
            <div class="sidebar">
                <div class="brand">
                    <span class="brand-logo" id="sidebar-app-logo"><?= htmlspecialchars(getSetting('app_logo', '🏃‍♂️')) ?></span>
                    <span class="brand-name" id="sidebar-app-name"><?= htmlspecialchars(strtoupper(getSetting('app_name', 'STADION RUN'))) ?></span>
                </div>
                
                <ul class="sidebar-menu">
                    <li class="sidebar-item active" id="menu-dashboard">
                        <a href="javascript:void(0)" onclick="switchTab('dashboard')">
                            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"></path></svg>
                            <span>Dashboard Utama</span>
                        </a>
                    </li>
                    <li class="sidebar-item" id="menu-runners">
                        <a href="javascript:void(0)" onclick="switchTab('runners')">
                            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            <span>Daftar Pelari</span>
                        </a>
                    </li>
                    <li class="sidebar-item" id="menu-recent-runs">
                        <a href="javascript:void(0)" onclick="switchTab('recent-runs')">
                            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                            <span>Riwayat Sesi Lari</span>
                        </a>
                    </li>
                    <li class="sidebar-item" id="menu-locations">
                        <a href="javascript:void(0)" onclick="switchTab('locations')">
                            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <span>Kelola Lokasi</span>
                        </a>
                    </li>
                    <li class="sidebar-item" id="menu-map-indonesia">
                        <a href="javascript:void(0)" onclick="switchTab('map-indonesia')">
                            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                            <span>Peta Indonesia</span>
                        </a>
                    </li>
                    <li class="sidebar-item" id="menu-settings">
                        <a href="javascript:void(0)" onclick="switchTab('settings')">
                            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <span>Profil & Pengaturan</span>
                        </a>
                    </li>
                    <li class="sidebar-item" id="menu-qr-code">
                        <a href="javascript:void(0)" onclick="switchTab('qr-code')">
                            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                            <span>QR Code Stadion</span>
                        </a>
                    </li>
                </ul>
                
                <div class="sidebar-footer">
                    <button class="btn-logout" onclick="handleAdminLogout()">
                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        <span>Logout Admin</span>
                    </button>
                </div>
            </div>
            
            <!-- Main Content Area -->
            <div class="workspace">
                
                <div class="admin-header">
                    <div>
                        <h1 id="page-title">Dashboard Analisis Pelari</h1>
                        <p>Pemantauan real-time aktivitas atlet lari Stadion.</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn-action" onclick="exportPDF('all')">
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            <span>Cetak Laporan Keseluruhan</span>
                        </button>
                    </div>
                </div>

                <!-- Date Filter Bar -->
                <div class="card" style="padding: 16px; margin-bottom: 10px;">
                    <div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 13px; font-weight: 600; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                                <svg style="width: 18px; height: 18px; color: var(--primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Filter Rentang Tanggal:
                            </span>
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <div class="input-container no-icon">
                                    <input type="date" id="filter-start-date" style="padding: 6px 12px; font-size: 13px; border-radius: 8px; width: auto;" onchange="applyFilters()">
                                </div>
                            </div>
                            <span style="font-size: 13px; color: var(--text-muted);">s/d</span>
                            <div class="form-group" style="margin-bottom: 0;">
                                <div class="input-container no-icon">
                                    <input type="date" id="filter-end-date" style="padding: 6px 12px; font-size: 13px; border-radius: 8px; width: auto;" onchange="applyFilters()">
                                </div>
                            </div>
                            <button type="button" onclick="resetDateFilter()" class="btn-action" style="padding: 6px 14px; font-size: 12px; margin-top: 0; background: transparent; border-color: var(--card-border);">Reset</button>
                        </div>
                    </div>
                </div>
                
                <!-- Overview Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-label">Total Pelari Stadion</span>
                        <span class="stat-value" id="stat-total-runners">0</span>
                        <span class="stat-desc">Orang terdaftar di sistem</span>
                    </div>
                    <div class="stat-card yellow">
                        <span class="stat-label">Pelari Aktif (Saat Ini)</span>
                        <span class="stat-value" id="stat-active-runners">0</span>
                        <span class="stat-desc">Sedang berlari di lapangan</span>
                    </div>
                    <div class="stat-card green">
                        <span class="stat-label">Total Sesi Lari Selesai</span>
                        <span class="stat-value" id="stat-completed-runs">0</span>
                        <span class="stat-desc">Terekam & dievaluasi</span>
                    </div>
                    <div class="stat-card secondary">
                        <span class="stat-label">Rata-Rata Statistik Lari</span>
                        <span class="stat-value" id="stat-avg-metrics">0</span>
                        <span class="stat-desc" id="stat-avg-sub">0 m | 0.00 km | 0 kcal</span>
                    </div>
                </div>
                
                <!-- Tab 1: Dashboard Utama -->
                <div id="tab-dashboard">
                    <!-- Panel Notifikasi / Alarm Medis Hari Ini -->
                    <div id="medical-alerts-panel" style="display: none; margin-bottom: 20px;">
                        <div class="card" style="border: 2px solid #ef4444; background: rgba(239, 68, 68, 0.05); padding: 20px; border-radius: 16px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px dashed rgba(239, 68, 68, 0.2); padding-bottom: 10px; flex-wrap: wrap; gap: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-size: 24px; animation: alert-pulse 1.5s infinite; display: inline-block;">🚨</span>
                                    <h3 style="margin: 0; color: #b91c1c; font-size: 16px; font-weight: 700;">ALARM MEDIS HARI INI: Pelari dengan Vital Sign Abnormal</h3>
                                </div>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <button type="button" id="btn-mute-siren" onclick="toggleMuteSiren()" style="background: #ffffff; color: #ef4444; border: 1.5px solid #ef4444; padding: 4px 12px; border-radius: 8px; font-size: 11px; font-weight: bold; cursor: pointer; transition: all 0.2s;">🔇 Matikan Suara</button>
                                    <span class="badge" style="background: #ef4444; color: white; padding: 4px 10px; border-radius: 99px; font-size: 11px; font-weight: bold;" id="medical-alerts-count">0 Peringatan</span>
                                </div>
                            </div>
                            <div id="medical-alerts-list" style="display: flex; flex-direction: column; gap: 12px;">
                                <!-- Dinamis diisi oleh JS -->
                            </div>
                        </div>
                    </div>

                    <!-- Demographics Map & Address Distribution Section -->
                    <div class="analytics-row" id="mapping-section">
                        
                        <!-- Left: Interactive Leaflet.js Map -->
                        <div class="card">
                            <div class="card-header">
                                <span class="card-title">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    Peta Sebaran Lokasi Pelari
                                </span>
                                <span style="font-size: 11px; color: var(--text-muted);">Simulasi Geocoding Stadion</span>
                            </div>
                            <div id="map"></div>
                        </div>
                        
                        <!-- Right: Demographic Chart & Details -->
                        <div class="card">
                            <div class="card-header">
                                <span class="card-title">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                                    Sebaran Demografi Pelari
                                </span>
                            </div>
                            <div class="chart-container">
                                <canvas id="ageChart"></canvas>
                            </div>
                            <div style="margin-top: 15px; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 15px;">
                                <h4 style="font-size: 12px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px;">Top 3 Domisili Pelari:</h4>
                                <div id="top-address-list" style="font-size: 13px; display: flex; flex-direction: column; gap: 6px;">
                                    <!-- Dinamis dimasukkan oleh JS -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Leaderboard Section -->
                    <div class="analytics-row" style="margin-top: 20px;">
                        <!-- Left: Top Distance Leaderboard -->
                        <div class="card">
                            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(0,0,0,0.05); padding-bottom: 12px;">
                                <span class="card-title" style="color: var(--navy); font-weight: 700; display: flex; align-items: center; gap: 6px;">
                                    🏆 Top 5 Jarak Terjauh
                                </span>
                                <span style="font-size: 11px; color: var(--text-muted); font-weight: 600; background: var(--card-border); padding: 2px 8px; border-radius: 12px;">Prestasi Stadion</span>
                            </div>
                            <div style="overflow-x: auto; padding-top: 10px;">
                                <table class="print-table" style="margin-top: 0; border: none; font-size: 13px; width: 100%;">
                                    <thead>
                                        <tr style="border-bottom: 2px solid var(--card-border);">
                                            <th style="width: 50px; text-align: center; border: none; padding: 10px 8px;">Pos</th>
                                            <th style="border: none; padding: 10px 8px;">Nama Pelari</th>
                                            <th style="text-align: center; border: none; padding: 10px 8px; width: 80px;">Gender</th>
                                            <th style="text-align: right; border: none; padding: 10px 8px; width: 100px;">Total Jarak</th>
                                            <th style="text-align: center; border: none; padding: 10px 8px; width: 60px;">Sesi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="leaderboard-distance-body">
                                        <!-- Dinamis dimasukkan oleh JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Right: Top Laps Leaderboard -->
                        <div class="card">
                            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(0,0,0,0.05); padding-bottom: 12px;">
                                <span class="card-title" style="color: var(--navy); font-weight: 700; display: flex; align-items: center; gap: 6px;">
                                    🏃 Top 5 Lap Terbanyak
                                </span>
                                <span style="font-size: 11px; color: var(--text-muted); font-weight: 600; background: var(--card-border); padding: 2px 8px; border-radius: 12px;">Prestasi Stadion</span>
                            </div>
                            <div style="overflow-x: auto; padding-top: 10px;">
                                <table class="print-table" style="margin-top: 0; border: none; font-size: 13px; width: 100%;">
                                    <thead>
                                        <tr style="border-bottom: 2px solid var(--card-border);">
                                            <th style="width: 50px; text-align: center; border: none; padding: 10px 8px;">Pos</th>
                                            <th style="border: none; padding: 10px 8px;">Nama Pelari</th>
                                            <th style="text-align: center; border: none; padding: 10px 8px; width: 80px;">Gender</th>
                                            <th style="text-align: right; border: none; padding: 10px 8px; width: 100px;">Total Lap</th>
                                            <th style="text-align: center; border: none; padding: 10px 8px; width: 60px;">Sesi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="leaderboard-laps-body">
                                        <!-- Dinamis dimasukkan oleh JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Laporan Analisis & Rekomendasi Edukasi Medis -->
                    <div class="card" style="margin-top: 20px;">
                        <div class="card-header" style="border-bottom: 1px solid rgba(0,0,0,0.05); padding-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                            <span class="card-title" style="color: var(--navy); font-weight: 700;">
                                📋 Laporan Analisis Kesehatan & Panduan Edukasi Pelari
                            </span>
                        </div>
                        <div style="padding: 15px 0;">
                            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; flex-wrap: wrap;">
                                <!-- Left side: Vitals warning statistics -->
                                <div style="background: rgba(139, 92, 246, 0.05); padding: 20px; border-radius: 12px; border: 1px solid rgba(139, 92, 246, 0.15);">
                                    <h4 style="font-size: 13px; font-weight: 700; color: var(--secondary); margin-bottom: 12px; text-transform: uppercase;">Akumulasi Anomali Terdeteksi</h4>
                                    <div style="display: flex; flex-direction: column; gap: 8px; font-size: 12px;" id="medical-stats-breakdown">
                                        <!-- Dinamis dimasukkan oleh JS -->
                                    </div>
                                    <div style="font-size: 10px; color: var(--text-muted); margin-top: 15px; border-top: 1px dashed rgba(0,0,0,0.1); padding-top: 10px;" id="medical-analysis-analyzed-label">
                                        Menganalisis 0 sesi lari.
                                    </div>
                                </div>
                                <!-- Right side: Educational and preventive suggestions -->
                                <div>
                                    <h4 style="font-size: 13px; font-weight: 700; color: var(--navy); margin-bottom: 12px; text-transform: uppercase;">Rekomendasi Edukasi & Tindak Lanjut</h4>
                                    <div id="medical-education-tips" style="font-size: 13px; color: var(--text-muted); line-height: 1.6;">
                                        <!-- Dinamis dimasukkan oleh JS -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tab 2: Daftar Pelari -->
                <div id="tab-runners" style="display: none;">
                    <!-- Runners Table Card -->
                    <div class="card table-card" id="runners-list">
                        <div class="card-header">
                            <span class="card-title">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                Daftar Pelari Stadion
                            </span>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <button class="btn-action" onclick="exportRunnersExcel()" style="padding: 6px 12px; font-size: 12px; border-color: #10b981; color: #10b981;">🟢 Excel</button>
                                <button class="btn-action" onclick="exportPDF('runners')" style="padding: 6px 12px; font-size: 12px; border-color: #ef4444; color: #ef4444;">🔴 PDF / Print</button>
                                <input type="text" id="runner-search" oninput="filterRunnersTable()" class="search-input" style="margin-left: 10px;" placeholder="Cari nama atau alamat...">
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width: 50px; text-align: center;">No.</th>
                                        <th class="col-name">Nama Pelari</th>
                                        <th>Usia</th>
                                        <th>Gender</th>
                                        <th>No. Handphone</th>
                                        <th class="col-address">Alamat Domisili</th>
                                        <th>Terdaftar Pada</th>
                                        <th style="text-align: center;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="runners-table-body">
                                    <!-- Dinamis dimasukkan oleh JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Riwayat Sesi Lari -->
                <div id="tab-recent-runs" style="display: none;">
                    <!-- Recent Runs Table Card -->
                    <div class="card table-card" id="recent-runs-sec">
                        <div class="card-header">
                            <span class="card-title">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                Log Sesi Lari Terakhir
                            </span>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <button class="btn-action" onclick="exportRunsExcel()" style="padding: 6px 12px; font-size: 12px; border-color: #10b981; color: #10b981;">🟢 Excel</button>
                                <button class="btn-action" onclick="exportPDF('runs')" style="padding: 6px 12px; font-size: 12px; border-color: #ef4444; color: #ef4444;">🔴 PDF / Print</button>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width: 50px; text-align: center;">No.</th>
                                        <th class="col-name">Nama Pelari</th>
                                        <th class="col-location">Lokasi Lari</th>
                                        <th>Tanggal Lari</th>
                                        <th>Lap</th>
                                        <th>Jarak</th>
                                        <th>Durasi</th>
                                        <th>Kalori</th>
                                        <th style="text-align: center;">Skor</th>
                                    </tr>
                                </thead>
                                <tbody id="runs-table-body">
                                    <!-- Dinamis dimasukkan oleh JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Tab 4: Profil & Pengaturan -->
                <div id="tab-settings" style="display: none;">
                    <div class="analytics-row">
                        <!-- Form Ubah Kredensial Profil Admin -->
                        <div class="card">
                            <div class="card-header">
                                <span class="card-title">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    Ubah Kredensial Admin
                                </span>
                            </div>
                            <form id="admin-profile-form">
                                <div class="form-group">
                                    <label for="profile-username">Username Admin Baru</label>
                                    <div class="input-container no-icon">
                                        <input type="text" id="profile-username" placeholder="Username admin baru" value="<?= htmlspecialchars(getSetting('admin_user', 'admin')) ?>" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="profile-old-pass">Password Saat Ini (Konfirmasi Keamanan)</label>
                                    <div class="input-container no-icon">
                                        <input type="password" id="profile-old-pass" placeholder="Masukkan password sekarang" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="profile-new-pass">Password Baru (Opsional)</label>
                                    <div class="input-container no-icon">
                                        <input type="password" id="profile-new-pass" placeholder="Biarkan kosong jika tidak diubah">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="profile-confirm-pass">Konfirmasi Password Baru</label>
                                    <div class="input-container no-icon">
                                        <input type="password" id="profile-confirm-pass" placeholder="Ketik ulang password baru">
                                    </div>
                                </div>
                                <button type="submit" class="btn mt-4">
                                    <span>Simpan Profil</span>
                                </button>
                            </form>
                        </div>

                        <!-- Form Ubah Branding Aplikasi -->
                        <div class="card">
                            <div class="card-header">
                                <span class="card-title">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    Ubah Branding Aplikasi
                                </span>
                            </div>
                            <form id="app-settings-form">
                                <div class="form-group">
                                    <label for="settings-app-name">Nama Aplikasi</label>
                                    <div class="input-container no-icon">
                                        <input type="text" id="settings-app-name" placeholder="Nama aplikasi (cth: Stadion Run)" value="<?= htmlspecialchars(getSetting('app_name', 'Stadion Run')) ?>" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="settings-app-logo">Logo Aplikasi (Karakter / Emoji)</label>
                                    <div class="input-container no-icon">
                                        <input type="text" id="settings-app-logo" placeholder="Logo aplikasi (cth: 🏃)" value="<?= htmlspecialchars(getSetting('app_logo', '🏃')) ?>" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn mt-4">
                                    <span>Simpan Branding</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Card 3: Form Kustomisasi Indikator Penilaian Kesehatan -->
                    <div class="card" style="margin-top: 24px;">
                        <div class="card-header">
                            <span class="card-title">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Konfigurasi Indikator Penilaian & Skor Kesehatan
                            </span>
                        </div>
                        <form id="health-indicators-form">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
                                
                                <!-- Kolom 1: Indeks Massa Tubuh (IMT) & BBI -->
                                <div>
                                    <h4 style="font-size: 14px; color: var(--primary); margin-bottom: 12px; border-bottom: 1px solid var(--card-border); padding-bottom: 6px;">1. Indeks Massa Tubuh (IMT)</h4>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;" class="form-group">
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Normal Min</label>
                                            <input type="number" step="0.1" id="ind-imt-normal-min" value="<?= htmlspecialchars(getSetting('ind_imt_normal_min', '18.5')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Normal Max</label>
                                            <input type="number" step="0.1" id="ind-imt-normal-max" value="<?= htmlspecialchars(getSetting('ind_imt_normal_max', '22.9')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;" class="form-group">
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Overweight Min</label>
                                            <input type="number" step="0.1" id="ind-imt-overweight-min" value="<?= htmlspecialchars(getSetting('ind_imt_overweight_min', '23.0')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Overweight Max</label>
                                            <input type="number" step="0.1" id="ind-imt-overweight-max" value="<?= htmlspecialchars(getSetting('ind_imt_overweight_max', '24.9')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;" class="form-group">
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Skor Normal (Maks 20)</label>
                                            <input type="number" id="ind-imt-normal-score" value="<?= htmlspecialchars(getSetting('ind_imt_normal_score', '20')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Skor Overweight</label>
                                            <input type="number" id="ind-imt-overweight-score" value="<?= htmlspecialchars(getSetting('ind_imt_overweight_score', '15')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;" class="form-group">
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Skor Underweight</label>
                                            <input type="number" id="ind-imt-underweight-score" value="<?= htmlspecialchars(getSetting('ind_imt_underweight_score', '10')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Skor Obesitas</label>
                                            <input type="number" id="ind-imt-obese-score" value="<?= htmlspecialchars(getSetting('ind_imt_obese_score', '5')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                    </div>

                                    <h4 style="font-size: 14px; color: var(--primary); margin-top: 18px; margin-bottom: 12px; border-bottom: 1px solid var(--card-border); padding-bottom: 6px;">2. Berat Badan Ideal (BBI)</h4>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;" class="form-group">
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Ideal Min (%)</label>
                                            <input type="number" id="ind-bbi-ideal-min" value="<?= htmlspecialchars(getSetting('ind_bbi_ideal_min', '90')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Ideal Max (%)</label>
                                            <input type="number" id="ind-bbi-ideal-max" value="<?= htmlspecialchars(getSetting('ind_bbi_ideal_max', '110')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label style="font-size: 11px; font-weight: 600;">Skor BBI Ideal (Maks 15)</label>
                                        <input type="number" id="ind-bbi-ideal-score" value="<?= htmlspecialchars(getSetting('ind_bbi_ideal_score', '15')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;" class="form-group">
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Skor BBI Sedang</label>
                                            <input type="number" id="ind-bbi-warning-score" value="<?= htmlspecialchars(getSetting('ind_bbi_warning_score', '10')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Skor Obesitas</label>
                                            <input type="number" id="ind-bbi-danger-score" value="<?= htmlspecialchars(getSetting('ind_bbi_danger_score', '5')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                    </div>
                                </div>

                                <!-- Kolom 2: Tekanan Darah (Tensi) & Denyut Jantung Awal -->
                                <div>
                                    <h4 style="font-size: 14px; color: var(--primary); margin-bottom: 12px; border-bottom: 1px solid var(--card-border); padding-bottom: 6px;">3. Tekanan Darah (Tensi)</h4>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;" class="form-group">
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Normal Sistolik (<=)</label>
                                            <input type="number" id="ind-bp-normal-sys" value="<?= htmlspecialchars(getSetting('ind_bp_normal_sys', '120')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Normal Diastolik (<=)</label>
                                            <input type="number" id="ind-bp-normal-dia" value="<?= htmlspecialchars(getSetting('ind_bp_normal_dia', '80')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label style="font-size: 11px; font-weight: 600;">Skor Normal (Maks 15)</label>
                                        <input type="number" id="ind-bp-normal-score" value="<?= htmlspecialchars(getSetting('ind_bp_normal_score', '15')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;" class="form-group">
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Pre-Hipt. Sistolik (<=)</label>
                                            <input type="number" id="ind-bp-warning-sys" value="<?= htmlspecialchars(getSetting('ind_bp_warning_sys', '139')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Pre-Hipt. Diastolik (<=)</label>
                                            <input type="number" id="ind-bp-warning-dia" value="<?= htmlspecialchars(getSetting('ind_bp_warning_dia', '89')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;" class="form-group">
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Skor Pre-Hipt.</label>
                                            <input type="number" id="ind-bp-warning-score" value="<?= htmlspecialchars(getSetting('ind_bp_warning_score', '10')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Skor Hipertensi</label>
                                            <input type="number" id="ind-bp-danger-score" value="<?= htmlspecialchars(getSetting('ind_bp_danger_score', '5')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                    </div>

                                    <h4 style="font-size: 14px; color: var(--primary); margin-top: 18px; margin-bottom: 12px; border-bottom: 1px solid var(--card-border); padding-bottom: 6px;">4. Detak Jantung Awal (Resting HR)</h4>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;" class="form-group">
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Normal Min</label>
                                            <input type="number" id="ind-hr-min" value="<?= htmlspecialchars(getSetting('ind_hr_min', '60')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Normal Max</label>
                                            <input type="number" id="ind-hr-max" value="<?= htmlspecialchars(getSetting('ind_hr_max', '100')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;" class="form-group">
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Skor Normal (Maks 15)</label>
                                            <input type="number" id="ind-hr-normal-score" value="<?= htmlspecialchars(getSetting('ind_hr_normal_score', '15')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Skor Abnormal</label>
                                            <input type="number" id="ind-hr-abnormal-score" value="<?= htmlspecialchars(getSetting('ind_hr_abnormal_score', '8')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                    </div>
                                </div>

                                <!-- Kolom 3: SpO2, Suhu, Kolesterol, & Target -->
                                <div>
                                    <h4 style="font-size: 14px; color: var(--primary); margin-bottom: 12px; border-bottom: 1px solid var(--card-border); padding-bottom: 6px;">5. Oksigen Darah (SpO2)</h4>
                                    <div class="form-group">
                                        <label style="font-size: 11px; font-weight: 600;">Normal Min (%)</label>
                                        <input type="number" id="ind-spo2-min" value="<?= htmlspecialchars(getSetting('ind_spo2_min', '95')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;" class="form-group">
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Skor Normal (15)</label>
                                            <input type="number" id="ind-spo2-normal-score" value="<?= htmlspecialchars(getSetting('ind_spo2_normal_score', '15')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Skor Abnormal</label>
                                            <input type="number" id="ind-spo2-abnormal-score" value="<?= htmlspecialchars(getSetting('ind_spo2_abnormal_score', '5')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                    </div>

                                    <h4 style="font-size: 14px; color: var(--primary); margin-top: 18px; margin-bottom: 12px; border-bottom: 1px solid var(--card-border); padding-bottom: 6px;">6. Suhu Tubuh</h4>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;" class="form-group">
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Normal Min (°C)</label>
                                            <input type="number" step="0.1" id="ind-temp-min" value="<?= htmlspecialchars(getSetting('ind_temp_min', '36.5')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Normal Max (°C)</label>
                                            <input type="number" step="0.1" id="ind-temp-max" value="<?= htmlspecialchars(getSetting('ind_temp_max', '37.5')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;" class="form-group">
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Skor Normal (10)</label>
                                            <input type="number" id="ind-temp-normal-score" value="<?= htmlspecialchars(getSetting('ind_temp_normal_score', '10')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Skor Abnormal</label>
                                            <input type="number" id="ind-temp-abnormal-score" value="<?= htmlspecialchars(getSetting('ind_temp_abnormal_score', '5')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                    </div>

                                    <h4 style="font-size: 14px; color: var(--primary); margin-top: 18px; margin-bottom: 12px; border-bottom: 1px solid var(--card-border); padding-bottom: 6px;">7. Kolesterol & Target</h4>
                                    <div class="form-group">
                                        <label style="font-size: 11px; font-weight: 600;">Normal Max Kolesterol</label>
                                        <input type="number" id="ind-chol-max" value="<?= htmlspecialchars(getSetting('ind_chol_max', '200')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;" class="form-group">
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Skor Normal (10)</label>
                                            <input type="number" id="ind-chol-normal-score" value="<?= htmlspecialchars(getSetting('ind_chol_normal_score', '10')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600;">Skor Tinggi</label>
                                            <input type="number" id="ind-chol-abnormal-score" value="<?= htmlspecialchars(getSetting('ind_chol_abnormal_score', '5')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label style="font-size: 11px; font-weight: 600;">Skor Bonus Target Tercapai (10)</label>
                                        <input type="number" id="ind-target-score" value="<?= htmlspecialchars(getSetting('ind_target_score', '10')) ?>" required style="width: 100%; padding: 8px; border: 1.5px solid var(--card-border); border-radius: 8px; font-size: 13px;">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn mt-6" style="background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);">
                                <span>Simpan Indikator Penilaian</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Tab: Kelola Lokasi Lari -->
                <div id="tab-locations" style="display: none;">
                    <div class="card">
                        <div class="card-header">
                            <span class="card-title">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                Kelola Lokasi Lari
                            </span>
                            <span style="font-size: 11px; color: var(--text-muted);">Tambah dan hapus lokasi stadion lari</span>
                        </div>
                        
                        <div class="locations-grid">
                            <!-- Form Tambah Lokasi -->
                            <form id="add-location-form" style="display: flex; flex-direction: column; gap: 12px;">
                                <div class="form-group">
                                    <label for="loc-name">Nama Lokasi / Stadion</label>
                                    <div class="input-container no-icon">
                                        <input type="text" id="loc-name" placeholder="Contoh: Stadion Gelora Bung Tomo" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="loc-address">Alamat Singkat</label>
                                    <div class="input-container no-icon">
                                        <input type="text" id="loc-address" placeholder="Contoh: Pakal, Surabaya" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Pilih Koordinat dari Peta (Klik / Geser Penanda)</label>
                                    <div id="map-picker" style="height: 220px; border-radius: 12px; border: 1.5px solid var(--card-border); margin-bottom: 8px; z-index: 5;"></div>
                                </div>
                                <div class="lat-lng-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    <div class="form-group">
                                        <label for="loc-lat">Lintang (Latitude)</label>
                                        <div class="input-container no-icon">
                                            <input type="number" step="0.000001" id="loc-lat" placeholder="-7.2285" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="loc-lng">Bujur (Longitude)</label>
                                        <div class="input-container no-icon">
                                            <input type="number" step="0.000001" id="loc-lng" placeholder="112.6322" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="loc-maps">Tautan Google Maps (Opsional)</label>
                                    <div class="input-container no-icon">
                                        <input type="url" id="loc-maps" placeholder="https://maps.google.com/?q=-7.2285,112.6322">
                                    </div>
                                </div>
                                <button type="submit" class="btn mt-2">
                                    <span>Tambah Lokasi</span>
                                </button>
                            </form>
                            
                            <!-- Tabel Lokasi Aktif -->
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Nama Lokasi</th>
                                            <th>Koordinat</th>
                                            <th>Tautan Maps</th>
                                            <th style="text-align: center;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="locations-table-body">
                                        <!-- Dinamis diisi oleh JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Peta Indonesia -->
                <div id="tab-map-indonesia" style="display: none;">
                    <div class="card">
                        <div class="card-header" style="margin-bottom: 20px;">
                            <span class="card-title">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                                Sebaran Stadion Lari - Indonesia
                            </span>
                            <span style="font-size: 11px; color: var(--text-muted);">Pemetaan sebaran stadion lari di Indonesia beserta statistik pengguna</span>
                        </div>
                        
                        <div id="indonesia-map" style="height: 550px; border-radius: 20px; border: 1.5px solid var(--card-border); z-index: 5; box-shadow: var(--shadow);"></div>
                    </div>
                </div>

                <!-- Tab 5: QR Code Stadion -->
                <div id="tab-qr-code" style="display: none;">
                    <div class="card" style="max-width: 500px; margin: 0 auto; text-align: center; padding: 35px 30px;">
                        <span style="font-size: 48px; display: block; margin-bottom: 15px;">🏟️</span>
                        <h2 style="font-size: 20px; color: var(--navy); font-weight: 700; margin-bottom: 8px;">QR Code Stadion</h2>
                        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 25px;">
                            Cetak QR Code ini dan bagikan dalam bentuk brosur atau poster. Pelari dapat memindai kode ini untuk membuka aplikasi di ponsel mereka secara instan.
                        </p>
                        
                        <div style="background: white; border: 1.5px dashed var(--card-border); border-radius: 16px; padding: 24px; display: inline-block; margin-bottom: 25px; box-shadow: var(--shadow);">
                            <div id="admin-qr-canvas" style="display: flex; justify-content: center; align-items: center; min-height: 200px; min-width: 200px; background: #ffffff;">
                                <!-- QR Code akan digenerate di sini -->
                            </div>
                        </div>
                        
                        <div style="width: 100%; text-align: left; margin-bottom: 25px;">
                            <label style="font-size: 12px; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 6px;">Tautan/Link QR Code:</label>
                            <div id="admin-qr-link-text" style="background: #f1f5f9; border: 1px solid var(--card-border); padding: 10px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; color: var(--text-main); word-break: break-all; font-family: monospace;">-</div>
                        </div>
                        
                        <button type="button" onclick="printAdminQR()" class="btn">
                            <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            <span>Cetak / Print QR Code</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= RUNNER PROFILE DETAILS MODAL (DRAWER) ================= -->
        <div class="modal" id="runner-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modal-runner-name">Profil Pelari</h2>
                    <button class="btn-close" onclick="closeRunnerModal()">
                        <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <div class="profile-grid">
                    <div class="profile-item">
                        <span class="label">Jenis Kelamin</span>
                        <span class="value" id="modal-gender">-</span>
                    </div>
                    <div class="profile-item">
                        <span class="label">Usia / Tgl Lahir</span>
                        <span class="value" id="modal-age">-</span>
                    </div>
                    <div class="profile-item">
                        <span class="label">No. Handphone</span>
                        <span class="value" id="modal-phone">-</span>
                    </div>
                    <div class="profile-item">
                        <span class="label">Email</span>
                        <span class="value" id="modal-email">-</span>
                    </div>
                    <div class="profile-item" style="grid-column: span 2;">
                        <span class="label">Alamat</span>
                        <span class="value" id="modal-address">-</span>
                    </div>
                </div>

                <div class="card" style="margin-bottom: 25px; padding: 15px;">
                    <div class="card-title" style="font-size: 14px; margin-bottom: 12px;">Grafik Skor Kesehatan Aktivitas</div>
                    <div style="height: 180px; position: relative; width: 100%;">
                        <canvas id="progressionChart"></canvas>
                    </div>
                </div>

                <h3 style="font-size: 15px; font-weight: 700; color: var(--navy); margin-bottom: 12px;">Riwayat Lari Atlet</h3>
                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;">No.</th>
                                <th>Tanggal</th>
                                <th>Lokasi</th>
                                <th>Durasi</th>
                                <th>Jarak</th>
                                <th>Skor</th>
                            </tr>
                        </thead>
                        <tbody id="modal-history-table">
                            <!-- Dinamis dimasukkan oleh JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ================= PRINT FRIENDLY REPORT SECTION ================= -->
        <div id="print-section">
            <h1 style="text-align: center; margin-bottom: 5px;">LAPORAN AKTIVITAS STADION RUN</h1>
            <p style="text-align: center; font-size: 14px; margin-bottom: 30px;" id="print-date-label">Tanggal Cetak: -</p>
            
            <h3 style="margin-bottom: 10px;">Ringkasan Data Stadion:</h3>
            <table class="print-table" style="margin-bottom: 30px;">
                <tr>
                    <th>Total Pelari Terdaftar</th>
                    <td id="print-total-runners">0</td>
                    <th>Rata-rata Durasi Lari</th>
                    <td id="print-avg-duration">0 menit</td>
                </tr>
                <tr>
                    <th>Total Sesi Lari Selesai</th>
                    <td id="print-total-runs">0</td>
                    <th>Rata-rata Jarak Tempuh</th>
                    <td id="print-avg-distance">0.00 km</td>
                </tr>
            </table>

            <h3 style="margin-bottom: 10px;">Daftar Pelari Terdaftar:</h3>
            <table class="print-table">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">No.</th>
                        <th>Nama Pelari</th>
                        <th>Gender</th>
                        <th>Usia</th>
                        <th>Alamat</th>
                        <th>Handphone</th>
                        <th>Tanggal Terdaftar</th>
                    </tr>
                </thead>
                <tbody id="print-runners-rows">
                    <!-- Dinamis dimasukkan oleh JS -->
                </tbody>
            </table>
        </div>

        <!-- Leaflet Map Library -->
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <!-- Chart.js Library -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <!-- QRCodeJS Library dari CDN -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <!-- SweetAlert2 Library -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            // Konfigurasi Indikator Penilaian Kesehatan dinamis dari database
            const healthIndicators = <?= json_encode(array_map('floatval', [
                'ind_imt_normal_min' => getSetting('ind_imt_normal_min', '18.5'),
                'ind_imt_normal_max' => getSetting('ind_imt_normal_max', '22.9'),
                'ind_imt_normal_score' => getSetting('ind_imt_normal_score', '20'),
                'ind_imt_overweight_min' => getSetting('ind_imt_overweight_min', '23.0'),
                'ind_imt_overweight_max' => getSetting('ind_imt_overweight_max', '24.9'),
                'ind_imt_overweight_score' => getSetting('ind_imt_overweight_score', '15'),
                'ind_imt_underweight_score' => getSetting('ind_imt_underweight_score', '10'),
                'ind_imt_obese_score' => getSetting('ind_imt_obese_score', '5'),
                'ind_bbi_ideal_min' => getSetting('ind_bbi_ideal_min', '90'),
                'ind_bbi_ideal_max' => getSetting('ind_bbi_ideal_max', '110'),
                'ind_bbi_ideal_score' => getSetting('ind_bbi_ideal_score', '15'),
                'ind_bbi_warning_score' => getSetting('ind_bbi_warning_score', '10'),
                'ind_bbi_danger_score' => getSetting('ind_bbi_danger_score', '5'),
                'ind_bp_normal_sys' => getSetting('ind_bp_normal_sys', '120'),
                'ind_bp_normal_dia' => getSetting('ind_bp_normal_dia', '80'),
                'ind_bp_normal_score' => getSetting('ind_bp_normal_score', '15'),
                'ind_bp_warning_sys' => getSetting('ind_bp_warning_sys', '139'),
                'ind_bp_warning_dia' => getSetting('ind_bp_warning_dia', '89'),
                'ind_bp_warning_score' => getSetting('ind_bp_warning_score', '10'),
                'ind_bp_danger_score' => getSetting('ind_bp_danger_score', '5'),
                'ind_hr_min' => getSetting('ind_hr_min', '60'),
                'ind_hr_max' => getSetting('ind_hr_max', '100'),
                'ind_hr_normal_score' => getSetting('ind_hr_normal_score', '15'),
                'ind_hr_abnormal_score' => getSetting('ind_hr_abnormal_score', '8'),
                'ind_spo2_min' => getSetting('ind_spo2_min', '95'),
                'ind_spo2_normal_score' => getSetting('ind_spo2_normal_score', '15'),
                'ind_spo2_abnormal_score' => getSetting('ind_spo2_abnormal_score', '5'),
                'ind_temp_min' => getSetting('ind_temp_min', '36.5'),
                'ind_temp_max' => getSetting('ind_temp_max', '37.5'),
                'ind_temp_normal_score' => getSetting('ind_temp_normal_score', '10'),
                'ind_temp_abnormal_score' => getSetting('ind_temp_abnormal_score', '5'),
                'ind_chol_max' => getSetting('ind_chol_max', '200'),
                'ind_chol_normal_score' => getSetting('ind_chol_normal_score', '10'),
                'ind_chol_abnormal_score' => getSetting('ind_chol_abnormal_score', '5'),
                'ind_target_score' => getSetting('ind_target_score', '10')
            ])) ?>;

            // State data admin panel
            const adminState = {
                runners: [],
                recentRuns: [],
                addressDistribution: [],
                stats: {},
                map: null,
                mapMarkers: [],
                ageChart: null,
                progressionChart: null,
                leaderboardDistance: [],
                leaderboardLaps: [],
                medicalAlerts: [],
                locations: [],
                activeRunnersList: [],
                defaultLocationId: 1
            };

            // Bootstrap
            document.addEventListener('DOMContentLoaded', () => {
                const today = new Date();
                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0');
                const day = String(today.getDate()).padStart(2, '0');
                const todayStr = `${year}-${month}-${day}`;
                
                document.getElementById('filter-start-date').value = todayStr;
                document.getElementById('filter-end-date').value = todayStr;

                loadDashboardData();
            });

            // Load all stats from database
            async function loadDashboardData() {
                try {
                    const res = await fetch('admin-api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'get_dashboard_data' })
                    });
                    const data = await res.json();
                    
                    if (data.status === 'success') {
                        adminState.runners = data.runners;
                        adminState.recentRuns = data.recent_runs;
                        adminState.addressDistribution = data.address_distribution;
                        adminState.stats = data.stats;
                        adminState.leaderboardDistance = data.leaderboard_distance || [];
                        adminState.leaderboardLaps = data.leaderboard_laps || [];
                        adminState.medicalAlerts = data.medical_alerts || [];
                        adminState.locations = data.locations || [];
                        adminState.activeRunnersList = data.active_runners_list || [];
                        adminState.defaultLocationId = data.default_location_id || 1;

                        populateStats();
                        populateRunnersTable(data.runners);
                        populateRecentRunsTable(data.recent_runs);
                        populateLocationsTable(data.locations);
                        renderDemographics();
                        initLeafletMap();
                        if (typeof updateIndonesiaMapMarkers === 'function') updateIndonesiaMapMarkers();
                        populateMedicalAlerts(adminState.medicalAlerts);
                        populateLeaderboards(adminState.leaderboardDistance, adminState.leaderboardLaps);
                        populateMedicalAnalysis(data.medical_issues, data.total_runs_analyzed);
                        applyFilters();
                    } else if (data.status === 'unauthorized') {
                        window.location.reload();
                    } else {
                        showToast(data.message, 'error');
                    }
                } catch (err) {
                    showToast('Gagal memuat data dari server.', 'error');
                }
            }

            // Tab Navigation Switcher SPA
            function switchTab(tabId) {
                // Sembunyikan semua konten tab
                document.getElementById('tab-dashboard').style.display = 'none';
                document.getElementById('tab-runners').style.display = 'none';
                document.getElementById('tab-recent-runs').style.display = 'none';
                document.getElementById('tab-locations').style.display = 'none';
                document.getElementById('tab-map-indonesia').style.display = 'none';
                document.getElementById('tab-settings').style.display = 'none';
                document.getElementById('tab-qr-code').style.display = 'none';
                
                // Hapus kelas aktif dari semua item menu sidebar
                document.getElementById('menu-dashboard').classList.remove('active');
                document.getElementById('menu-runners').classList.remove('active');
                document.getElementById('menu-recent-runs').classList.remove('active');
                document.getElementById('menu-locations').classList.remove('active');
                document.getElementById('menu-map-indonesia').classList.remove('active');
                document.getElementById('menu-settings').classList.remove('active');
                document.getElementById('menu-qr-code').classList.remove('active');
                
                // Tampilkan tab yang dipilih
                if (tabId === 'dashboard') {
                    document.getElementById('tab-dashboard').style.display = 'block';
                    document.getElementById('menu-dashboard').classList.add('active');
                    document.getElementById('page-title').innerText = 'Dashboard Analisis Pelari';
                    
                    // Invalidate size map Leaflet agar merender ulang dengan pas
                    if (adminState.map) {
                        setTimeout(() => adminState.map.invalidateSize(), 100);
                    }
                } else if (tabId === 'runners') {
                    document.getElementById('tab-runners').style.display = 'block';
                    document.getElementById('menu-runners').classList.add('active');
                    document.getElementById('page-title').innerText = 'Manajemen Daftar Pelari';
                } else if (tabId === 'recent-runs') {
                    document.getElementById('tab-recent-runs').style.display = 'block';
                    document.getElementById('menu-recent-runs').classList.add('active');
                    document.getElementById('page-title').innerText = 'Log Riwayat Lari Stadion';
                } else if (tabId === 'locations') {
                    document.getElementById('tab-locations').style.display = 'block';
                    document.getElementById('menu-locations').classList.add('active');
                    document.getElementById('page-title').innerText = 'Manajemen Lokasi Lari';
                    setTimeout(() => initMapPicker(), 100);
                } else if (tabId === 'map-indonesia') {
                    document.getElementById('tab-map-indonesia').style.display = 'block';
                    document.getElementById('menu-map-indonesia').classList.add('active');
                    document.getElementById('page-title').innerText = 'Peta Sebaran Stadion Indonesia';
                    setTimeout(() => initIndonesiaMap(), 100);
                } else if (tabId === 'settings') {
                    document.getElementById('tab-settings').style.display = 'block';
                    document.getElementById('menu-settings').classList.add('active');
                    document.getElementById('page-title').innerText = 'Profil & Pengaturan Aplikasi';
                } else if (tabId === 'qr-code') {
                    document.getElementById('tab-qr-code').style.display = 'block';
                    document.getElementById('menu-qr-code').classList.add('active');
                    document.getElementById('page-title').innerText = 'QR Code Stadion';
                    generateAdminQRCode();
                }

                // Close sidebar on mobile views
                const sidebar = document.querySelector('.sidebar');
                const backdrop = document.querySelector('.sidebar-backdrop');
                if (window.innerWidth <= 768) {
                    if (sidebar) sidebar.classList.remove('active');
                    if (backdrop) backdrop.classList.remove('active');
                }
            }

            let adminQRCodeInstance = null;
            function generateAdminQRCode() {
                // Tentukan URL halaman utama pelari dengan menghapus '/tukangketik/'
                let homeUrl = window.location.origin + window.location.pathname.replace(/\/tukangketik\/(index\.php)?$/, '');
                if (!homeUrl.endsWith('/')) {
                    homeUrl += '/';
                }
                
                document.getElementById('admin-qr-link-text').innerText = homeUrl;

                const qrContainer = document.getElementById('admin-qr-canvas');
                qrContainer.innerHTML = '';
                
                adminQRCodeInstance = new QRCode(qrContainer, {
                    text: homeUrl,
                    width: 256,
                    height: 256,
                    colorDark : "#0f172a",
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.H
                });
            }

            function printAdminQR() {
                document.body.classList.add('print-qr-only');
                window.print();
                setTimeout(() => {
                    document.body.classList.remove('print-qr-only');
                }, 1000);
            }

            // Apply start/end date range filters
            function applyFilters() {
                const startDate = document.getElementById('filter-start-date').value;
                const endDate = document.getElementById('filter-end-date').value;
                
                let filteredRunners = adminState.runners;
                let filteredRuns = adminState.recentRuns;
                
                if (startDate) {
                    const start = new Date(startDate + 'T00:00:00');
                    filteredRunners = filteredRunners.filter(r => new Date(r.created_at) >= start);
                    filteredRuns = filteredRuns.filter(r => new Date(r.post_time) >= start);
                }
                if (endDate) {
                    const end = new Date(endDate + 'T23:59:59');
                    filteredRunners = filteredRunners.filter(r => new Date(r.created_at) <= end);
                    filteredRuns = filteredRuns.filter(r => new Date(r.post_time) <= end);
                }
                
                // Update tabel
                populateRunnersTable(filteredRunners);
                populateRecentRunsTable(filteredRuns);
                
                // Update metrik statistik
                updateStatsForFiltered(filteredRunners, filteredRuns);
                
                // Update map dan Chart.js demografi
                updateMapAndChartForFiltered(filteredRunners, filteredRuns);
            }

            // Recalculate stats card metrics for filtered datasets
            function updateStatsForFiltered(runners, runs) {
                document.getElementById('stat-total-runners').innerText = runners.length;
                document.getElementById('stat-completed-runs').innerText = runs.length;
                
                let totalDur = 0, totalDist = 0, totalCal = 0;
                runs.forEach(r => {
                    totalDur += r.post_duration || 0;
                    totalDist += r.post_distance || 0;
                    totalCal += r.post_calories || 0;
                });
                
                const avgDur = runs.length > 0 ? (totalDur / runs.length).toFixed(1) : 0;
                const avgDist = runs.length > 0 ? (totalDist / runs.length).toFixed(2) : 0;
                const avgCal = runs.length > 0 ? (totalCal / runs.length).toFixed(1) : 0;
                
                document.getElementById('stat-avg-metrics').innerText = `${avgDur} m`;
                document.getElementById('stat-avg-sub').innerText = `${avgDur} m | ${avgDist} km | ${avgCal} kcal`;
            }

            // Update map markers & demographics charts for filtered datasets
            function updateMapAndChartForFiltered(runners, runs) {
                // 1. Leaflet map markers
                if (adminState.map) {
                    adminState.mapMarkers.forEach(m => adminState.map.removeLayer(m));
                    adminState.mapMarkers = [];
                    
                    runners.forEach(r => {
                        const coords = getMockCoords(r.address);
                        const marker = L.marker(coords).addTo(adminState.map);
                        const popupContent = `
                            <div style="padding: 4px;">
                                <b style="font-size: 13px; color: var(--primary);">${r.fullname} (${r.gender})</b><br>
                                Usia: <b>${r.age} Tahun</b><br>
                                No HP: <b>${r.phone}</b><br>
                                Alamat: <b>${r.address}</b><br>
                                <button onclick="openRunnerModal(${r.id})" class="btn-table-action" style="margin-top: 8px; font-size: 9px; padding: 4px 8px; width: 100%;">Lihat Statistik Rekam Medis</button>
                            </div>
                        `;
                        marker.bindPopup(popupContent);
                        adminState.mapMarkers.push(marker);
                    });
                }
                
                // 2. Chart.js demographics update
                let ageUnder20 = 0, age20_29 = 0, age30_39 = 0, age40_49 = 0, ageAbove50 = 0;
                runners.forEach(r => {
                    if (r.age < 20) ageUnder20++;
                    else if (r.age >= 20 && r.age < 30) age20_29++;
                    else if (r.age >= 30 && r.age < 40) age30_39++;
                    else if (r.age >= 40 && r.age < 50) age40_49++;
                    else ageAbove50++;
                });
                
                if (adminState.ageChart) {
                    adminState.ageChart.data.datasets[0].data = [ageUnder20, age20_29, age30_39, age40_49, ageAbove50];
                    adminState.ageChart.update();
                }
            }

            // Reset date filters
            function resetDateFilter() {
                document.getElementById('filter-start-date').value = '';
                document.getElementById('filter-end-date').value = '';
                applyFilters();
            }

            // Export Runners to Excel CSV format
            function exportRunnersExcel() {
                const query = document.getElementById('runner-search').value.toLowerCase();
                const startDate = document.getElementById('filter-start-date').value;
                const endDate = document.getElementById('filter-end-date').value;
                
                let filtered = adminState.runners;
                if (query) {
                    filtered = filtered.filter(r => 
                        r.fullname.toLowerCase().includes(query) || 
                        r.address.toLowerCase().includes(query)
                    );
                }
                if (startDate) {
                    const start = new Date(startDate + 'T00:00:00');
                    filtered = filtered.filter(r => new Date(r.created_at) >= start);
                }
                if (endDate) {
                    const end = new Date(endDate + 'T23:59:59');
                    filtered = filtered.filter(r => new Date(r.created_at) <= end);
                }

                let csv = "sep=,\r\nNama Pelari,Usia,Jenis Kelamin,No. Handphone,Alamat Domisili,Terdaftar Pada\n";
                filtered.forEach(r => {
                    const regDate = new Date(r.created_at).toLocaleDateString('id-ID', { year: 'numeric', month: 'numeric', day: 'numeric' });
                    const name = `"${r.fullname.replace(/"/g, '""')}"`;
                    const address = `"${r.address.replace(/"/g, '""')}"`;
                    csv += `${name},${r.age},${r.gender},'${r.phone},${address},${regDate}\n`;
                });

                downloadCSV(csv, `Daftar_Pelari_StadionRun_${new Date().toISOString().slice(0,10)}.csv`);
                showToast('Daftar Pelari berhasil diekspor ke Excel (CSV)!', 'success');
            }

            // Export Recent Runs to Excel CSV format
            function exportRunsExcel() {
                const startDate = document.getElementById('filter-start-date').value;
                const endDate = document.getElementById('filter-end-date').value;
                
                let filtered = adminState.recentRuns;
                if (startDate) {
                    const start = new Date(startDate + 'T00:00:00');
                    filtered = filtered.filter(r => new Date(r.post_time) >= start);
                }
                if (endDate) {
                    const end = new Date(endDate + 'T23:59:59');
                    filtered = filtered.filter(r => new Date(r.post_time) <= end);
                }

                let csv = "sep=,\r\nNama Pelari,Tanggal Selesai,Jumlah Lap,Jarak Tempuh (km),Durasi (menit),Kalori (kcal),Skor Kesehatan\n";
                filtered.forEach(r => {
                    const dateStr = new Date(r.post_time).toLocaleDateString('id-ID', { year: 'numeric', month: 'numeric', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                    const name = `"${r.fullname.replace(/"/g, '""')}"`;
                    const dist = parseFloat(r.post_distance || 0).toFixed(2);
                    const score = calculateMetricScore(r);
                    csv += `${name},${dateStr},${r.post_laps},${dist},${r.post_duration},${r.post_calories},${score}\n`;
                });

                downloadCSV(csv, `Riwayat_Lari_StadionRun_${new Date().toISOString().slice(0,10)}.csv`);
                showToast('Log Riwayat Lari berhasil diekspor ke Excel (CSV)!', 'success');
            }

            // Helper to download CSV with BOM header for Microsoft Excel UTF-8 support
            function downloadCSV(csvContent, filename) {
                const blob = new Blob([new Uint8Array([0xEF, 0xBB, 0xBF]), csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement("a");
                if (link.download !== undefined) {
                    const url = URL.createObjectURL(blob);
                    link.setAttribute("href", url);
                    link.setAttribute("download", filename);
                    link.style.visibility = 'hidden';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            }

            // Print Runners Table only as a PDF
            function printRunnersPDF() {
                document.body.classList.add('print-runners-only');
                window.print();
                setTimeout(() => {
                    document.body.classList.remove('print-runners-only');
                }, 1000);
            }

            // Print Recent Runs logs table only as a PDF
            function printRunsPDF() {
                document.body.classList.add('print-runs-only');
                window.print();
                setTimeout(() => {
                    document.body.classList.remove('print-runs-only');
                }, 1000);
            }

            // Populate dashboard metrics cards
            function populateStats() {
                document.getElementById('stat-total-runners').innerText = adminState.stats.total_runners;
                document.getElementById('stat-active-runners').innerText = adminState.stats.active_runners;
                document.getElementById('stat-completed-runs').innerText = adminState.stats.completed_runs;
                
                document.getElementById('stat-avg-metrics').innerText = `${adminState.stats.avg_duration} m`;
                document.getElementById('stat-avg-sub').innerText = `${adminState.stats.avg_duration} m | ${adminState.stats.avg_distance} km | ${adminState.stats.avg_calories} kcal`;

                // Print friendly placeholders
                document.getElementById('print-total-runners').innerText = adminState.stats.total_runners;
                document.getElementById('print-total-runs').innerText = adminState.stats.completed_runs;
                document.getElementById('print-avg-duration').innerText = `${adminState.stats.avg_duration} menit`;
                document.getElementById('print-avg-distance').innerText = `${adminState.stats.avg_distance} km`;
            }

            let audioCtx = null;
            let sirenInterval = null;
            let sirenOscillator = null;
            let sirenGain = null;
            let isMuted = false;

            function initAudio() {
                if (audioCtx) return;
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }

            function startSirenSound() {
                initAudio();
                if (!audioCtx || isMuted) return;
                if (sirenOscillator) return; // Siren is already playing

                if (audioCtx.state === 'suspended') {
                    audioCtx.resume();
                }

                sirenOscillator = audioCtx.createOscillator();
                sirenGain = audioCtx.createGain();

                sirenOscillator.type = 'sawtooth';
                sirenOscillator.frequency.setValueAtTime(440, audioCtx.currentTime);

                const filter = audioCtx.createBiquadFilter();
                filter.type = 'lowpass';
                filter.frequency.value = 1000;

                sirenGain.gain.setValueAtTime(0.15, audioCtx.currentTime);

                sirenOscillator.connect(filter);
                filter.connect(sirenGain);
                sirenGain.connect(audioCtx.destination);

                sirenOscillator.start();

                let toggle = true;
                sirenInterval = setInterval(() => {
                    if (!audioCtx || isMuted || audioCtx.state === 'suspended') return;
                    const now = audioCtx.currentTime;
                    sirenOscillator.frequency.exponentialRampToValueAtTime(toggle ? 850 : 500, now + 0.45);
                    toggle = !toggle;
                }, 500);
            }

            function stopSirenSound() {
                if (sirenOscillator) {
                    try {
                        sirenOscillator.stop();
                    } catch (e) {}
                    sirenOscillator.disconnect();
                    sirenOscillator = null;
                }
                if (sirenGain) {
                    sirenGain.disconnect();
                    sirenGain = null;
                }
                if (sirenInterval) {
                    clearInterval(sirenInterval);
                    sirenInterval = null;
                }
            }

            function toggleMuteSiren() {
                isMuted = !isMuted;
                const muteBtn = document.getElementById('btn-mute-siren');
                if (isMuted) {
                    stopSirenSound();
                    if (muteBtn) {
                        muteBtn.innerHTML = '🔊 Aktifkan Suara';
                        muteBtn.style.color = '#10b981';
                        muteBtn.style.borderColor = '#10b981';
                    }
                } else {
                    if (muteBtn) {
                        muteBtn.innerHTML = '🔇 Matikan Suara';
                        muteBtn.style.color = '#ef4444';
                        muteBtn.style.borderColor = '#ef4444';
                    }
                    if (adminState.medicalAlerts && adminState.medicalAlerts.length > 0) {
                        startSirenSound();
                    }
                }
            }

            // Bind click listener on first interaction to unlock AudioContext
            document.addEventListener('click', () => {
                initAudio();
            }, { once: true });

            function populateMedicalAlerts(alerts) {
                const panel = document.getElementById('medical-alerts-panel');
                const list = document.getElementById('medical-alerts-list');
                const count = document.getElementById('medical-alerts-count');
                
                // Filter alerts to only show those that match today's computer date
                const today = new Date();
                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0');
                const day = String(today.getDate()).padStart(2, '0');
                const todayStr = `${year}-${month}-${day}`;
                
                const todayAlerts = (alerts || []).filter(alert => {
                    if (!alert.created_at) return false;
                    const alertDate = alert.created_at.split(' ')[0].split('T')[0];
                    return alertDate === todayStr;
                });
                
                if (todayAlerts.length === 0) {
                    panel.style.display = 'none';
                    list.innerHTML = '';
                    count.innerText = '0 Peringatan';
                    stopSirenSound();
                } else {
                    panel.style.display = 'block';
                    count.innerText = `${todayAlerts.length} Peringatan Medis`;
                    list.innerHTML = '';
                    
                    todayAlerts.forEach(alert => {
                        const statusColor = alert.status === 'started' ? 'background: #fef3c7; color: #92400e;' : 'background: #d1fae5; color: #065f46;';
                        const statusText = alert.status === 'started' ? 'Sedang Berlari' : 'Selesai';
                        const warningsHTML = alert.warnings.map(w => `
                            <span style="font-size: 11px; font-weight: 700; color: #b91c1c; background: #fee2e2; padding: 3px 8px; border-radius: 6px; display: inline-block;">⚠️ ${w}</span>
                        `).join(' ');
                        
                        const timeStr = new Date(alert.created_at.replace(/-/g, '/')).toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'});
                        
                        list.innerHTML += `
                            <div style="display: flex; justify-content: space-between; align-items: center; background: white; padding: 12px 16px; border-radius: 12px; border: 1px solid rgba(239, 68, 68, 0.15); box-shadow: 0 1px 3px rgba(0,0,0,0.05); flex-wrap: wrap; gap: 10px;">
                                <div>
                                    <span style="font-weight: 700; color: var(--navy);">${alert.fullname} (${alert.gender === 'L' ? 'L' : 'P'}, ${alert.age} Thn)</span>
                                    <span class="badge" style="margin-left: 8px; font-size: 10px; border-radius: 6px; padding: 2px 6px; ${statusColor}">${statusText}</span>
                                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">No. HP: <b>${alert.phone}</b> | Jam Mulai: <b>${timeStr}</b></div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                    <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center;">
                                        ${warningsHTML}
                                    </div>
                                    <button type="button" onclick="approveMedicalAlert(${alert.id})" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 6px 14px; font-size: 11px; font-weight: 700; border-radius: 8px; cursor: pointer; border: none; margin: 0; transition: all 0.2s; white-space: nowrap;">✅ Tangani / Approve</button>
                                </div>
                            </div>
                        `;
                    });
                    
                    // Bunyikan sirine
                    startSirenSound();
                }
            }

            async function approveMedicalAlert(runId) {
                if (!confirm('Apakah Anda yakin ingin menandai alarm medis pelari ini sebagai "Selesai Ditangani"? Tindakan ini akan menghentikan sirine dan mencatat status penanganan.')) return;

                try {
                    const res = await fetch('admin-api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'approve_medical_alert', run_id: runId })
                    });
                    const data = await res.json();
                    if (data.status === 'success') {
                        showToast(data.message, 'success');
                        loadDashboardData();
                    } else {
                        showToast(data.message, 'error');
                    }
                } catch (err) {
                    showToast('Gagal memproses persetujuan alarm.', 'error');
                }
            }

            function populateLeaderboards(distanceList, lapsList) {
                const distanceBody = document.getElementById('leaderboard-distance-body');
                const lapsBody = document.getElementById('leaderboard-laps-body');
                
                distanceBody.innerHTML = '';
                lapsBody.innerHTML = '';
                
                if (!distanceList || distanceList.length === 0) {
                    distanceBody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: var(--text-muted); font-style: italic; padding: 15px;">Belum ada data prestasi lari</td></tr>';
                } else {
                    distanceList.forEach((item, index) => {
                        let trophy = '';
                        if (index === 0) trophy = '🥇 ';
                        else if (index === 1) trophy = '🥈 ';
                        else if (index === 2) trophy = '🥉 ';
                        
                        const genderBadge = `<span class="gender-badge ${item.gender.toLowerCase()}">${item.gender}</span>`;
                        const distanceKm = parseFloat(item.total_distance).toFixed(2);
                        
                        distanceBody.innerHTML += `
                            <tr style="border-bottom: 1px solid rgba(0,0,0,0.03); transition: all 0.2s;">
                                <td style="text-align: center; font-weight: 700; color: var(--primary); padding: 10px 8px;">${trophy || (index + 1)}</td>
                                <td style="font-weight: 600; color: var(--navy); padding: 10px 8px;">${item.fullname}</td>
                                <td style="text-align: center; padding: 10px 8px;">${genderBadge}</td>
                                <td style="text-align: right; font-weight: 700; color: var(--accent-green); padding: 10px 8px;">${distanceKm} km</td>
                                <td style="text-align: center; color: var(--text-muted); padding: 10px 8px;">${item.total_runs}x</td>
                            </tr>
                        `;
                    });
                }
                
                if (!lapsList || lapsList.length === 0) {
                    lapsBody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: var(--text-muted); font-style: italic; padding: 15px;">Belum ada data prestasi lap</td></tr>';
                } else {
                    lapsList.forEach((item, index) => {
                        let trophy = '';
                        if (index === 0) trophy = '🥇 ';
                        else if (index === 1) trophy = '🥈 ';
                        else if (index === 2) trophy = '🥉 ';
                        
                        const genderBadge = `<span class="gender-badge ${item.gender.toLowerCase()}">${item.gender}</span>`;
                        
                        lapsBody.innerHTML += `
                            <tr style="border-bottom: 1px solid rgba(0,0,0,0.03); transition: all 0.2s;">
                                <td style="text-align: center; font-weight: 700; color: var(--secondary); padding: 10px 8px;">${trophy || (index + 1)}</td>
                                <td style="font-weight: 600; color: var(--navy); padding: 10px 8px;">${item.fullname}</td>
                                <td style="text-align: center; padding: 10px 8px;">${genderBadge}</td>
                                <td style="text-align: right; font-weight: 700; color: var(--accent-blue); padding: 10px 8px;">${item.total_laps} lap</td>
                                <td style="text-align: center; color: var(--text-muted); padding: 10px 8px;">${item.total_runs}x</td>
                            </tr>
                        `;
                    });
                }
            }

            function populateMedicalAnalysis(issues, totalRuns) {
                const statsBreakdown = document.getElementById('medical-stats-breakdown');
                const analyzedLabel = document.getElementById('medical-analysis-analyzed-label');
                const educationTips = document.getElementById('medical-education-tips');
                
                if (!statsBreakdown || !analyzedLabel || !educationTips) return;
                
                analyzedLabel.innerText = `Menganalisis total ${totalRuns || 0} sesi lari di database.`;
                
                const highBP = issues?.high_bp || 0;
                const abnormalHR = issues?.abnormal_hr || 0;
                const lowSpO2 = issues?.low_spo2 || 0;
                const abnormalTemp = issues?.abnormal_temp || 0;
                const highChol = issues?.high_chol || 0;
                
                statsBreakdown.innerHTML = `
                    <div style="display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px dashed rgba(0,0,0,0.05);">
                        <span>❤️ Tensi Darah Tinggi:</span>
                        <span style="font-weight: 700; color: ${highBP > 0 ? 'var(--accent-red)' : 'var(--text-muted)'};">${highBP} Kasus</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px dashed rgba(0,0,0,0.05);">
                        <span>💓 Detak Jantung Abnormal:</span>
                        <span style="font-weight: 700; color: ${abnormalHR > 0 ? 'var(--accent-red)' : 'var(--text-muted)'};">${abnormalHR} Kasus</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px dashed rgba(0,0,0,0.05);">
                        <span>🫁 Oksigen SpO2 Rendah:</span>
                        <span style="font-weight: 700; color: ${lowSpO2 > 0 ? 'var(--accent-red)' : 'var(--text-muted)'};">${lowSpO2} Kasus</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px dashed rgba(0,0,0,0.05);">
                        <span>🌡️ Suhu Tubuh Abnormal:</span>
                        <span style="font-weight: 700; color: ${abnormalTemp > 0 ? 'var(--accent-red)' : 'var(--text-muted)'};">${abnormalTemp} Kasus</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 4px 0;">
                        <span>🧪 Kolesterol Tinggi:</span>
                        <span style="font-weight: 700; color: ${highChol > 0 ? 'var(--accent-red)' : 'var(--text-muted)'};">${highChol} Kasus</span>
                    </div>
                `;
                
                // Cari isu terbanyak untuk menentukan saran prioritas
                let maxIssue = 'none';
                let maxCount = 0;
                
                if (highBP > maxCount) { maxCount = highBP; maxIssue = 'bp'; }
                if (abnormalHR > maxCount) { maxCount = abnormalHR; maxIssue = 'hr'; }
                if (lowSpO2 > maxCount) { maxCount = lowSpO2; maxIssue = 'spo2'; }
                if (abnormalTemp > maxCount) { maxCount = abnormalTemp; maxIssue = 'temp'; }
                if (highChol > maxCount) { maxCount = highChol; maxIssue = 'chol'; }
                
                let educationHTML = '';
                if (maxCount === 0) {
                    educationHTML = `
                        <div style="display: flex; align-items: flex-start; gap: 10px; background: rgba(16, 185, 129, 0.05); padding: 15px; border-radius: 10px; border: 1px solid rgba(16, 185, 129, 0.15);">
                            <span style="font-size: 20px;">✅</span>
                            <div>
                                <b style="color: #065f46; display: block; margin-bottom: 4px;">Kondisi Pelari Sangat Sehat!</b>
                                <p style="margin: 0; font-size: 12.5px;">Belum terdeteksi adanya insiden vital sign abnormal dari pelari hari ini. Teruskan edukasi mengenai pentingnya pemanasan rutin, hidrasi cukup (minimal 250ml sebelum lari), dan penggunaan sepatu lari yang sesuai.</p>
                            </div>
                        </div>
                    `;
                } else {
                    let issueTitle = '';
                    let actionPlan = '';
                    
                    if (maxIssue === 'bp') {
                        issueTitle = 'Hipertensi / Tekanan Darah Tinggi Pra-Lari';
                        actionPlan = `
                            <li><b>Edukasi Pembatasan Kafein & Energi Drink:</b> Imbau pelari agar tidak mengonsumsi minuman berkafein tinggi atau suplemen <i>pre-workout</i> instan minimal 2 jam sebelum berlari di stadion karena dapat melonjakkan tekanan darah secara berbahaya.</li>
                            <li><b>Lakukan Pemanasan Ringan (Warm-Up):</b> Edukasi pelari untuk melakukan pemanasan statis dan dinamis selama 10-15 menit untuk melebarkan pembuluh darah secara bertahap sehingga jantung tidak bekerja terlalu berat.</li>
                            <li><b>Skrining Mandiri:</b> Sarankan pelari dengan riwayat darah tinggi untuk membawa obat pribadi dan menghindari latihan dengan denyut jantung maksimum.</li>
                        `;
                    } else if (maxIssue === 'hr') {
                        issueTitle = 'Detak Jantung Awal (Resting Heart Rate) Abnormal';
                        actionPlan = `
                            <li><b>Pentingnya Istirahat Cukup:</b> Sosialisasikan bahwa detak jantung awal yang terlalu tinggi (>100 bpm) atau terlalu rendah (<60 bpm tanpa riwayat atlet terlatih) sering dipicu oleh kurang tidur, dehidrasi parah, atau kelelahan kronis (*overtraining*). Pelari disarankan untuk tidak memaksakan latihan berat jika kurang tidur dari 6 jam.</li>
                            <li><b>Latihan Menurunkan Detak Jantung:</b> Ajarkan teknik pernapasan lambat <i>(box breathing)</i> sebelum memulai sesi untuk menenangkan sistem saraf.</li>
                        `;
                    } else if (maxIssue === 'spo2') {
                        issueTitle = 'Saturasi Oksigen (SpO2) Rendah';
                        actionPlan = `
                            <li><b>Edukasi Saluran Pernapasan & Asma:</b> Oksigen rendah di bawah 95% sangat berisiko memicu hipoksia saat lari. Pasang brosur edukasi mengenai bahaya berlari saat mengalami flu berat, asma aktif, atau pasca-sakit paru.</li>
                            <li><b>Imbauan Pengurangan Kecepatan:</b> Sarankan pelari untuk segera melambat, berjalan kaki, atau berhenti sepenuhnya jika merasakan sesak napas dada menyempit, pusing berputar, atau bibir terasa dingin/pucat.</li>
                        `;
                    } else if (maxIssue === 'temp') {
                        issueTitle = 'Suhu Tubuh Tidak Normal (Demam / Hipotermia)';
                        actionPlan = `
                            <li><b>Risiko Heat Stroke:</b> Suhu tubuh tinggi (>37.5°C) sebelum lari sangat rawan memicu <i>Heat Stroke</i> (serangan panas) yang fatal. Petugas admin harus menyarankan pelari untuk menunda lari jika sedang demam/meriang.</li>
                            <li><b>Hidrasi Berwarna & Elektrolit:</b> Berikan edukasi tentang pentingnya minum air kelapa atau minuman isotonik di area stadion pada cuaca panas untuk mendinginkan suhu inti tubuh secara cepat.</li>
                        `;
                    } else if (maxIssue === 'chol') {
                        issueTitle = 'Kadar Kolesterol Tinggi Pra-Lari';
                        actionPlan = `
                            <li><b>Penyumbatan Pembuluh Darah (Aterosklerosis):</b> Kadar kolesterol tinggi (>200 mg/dL) meningkatkan risiko serangan jantung mendadak akibat lepasnya plak pembuluh darah saat tekanan darah naik selama berlari.</li>
                            <li><b>Imbauan Latihan Intensitas Sedang:</b> Edukasi pelari dengan kolesterol tinggi agar memilih olahraga kardio dengan zona denyut jantung 2 (Jogging ringan / Jalan cepat) alih-alih berlari sprint berat.</li>
                        `;
                    }
                    
                    educationHTML = `
                        <div style="background: #fffbeb; border: 1.5px solid #fef3c7; border-radius: 10px; padding: 16px; margin-bottom: 12px;">
                            <span style="font-size: 11px; font-weight: 700; color: #b45309; text-transform: uppercase; display: block; margin-bottom: 4px;">Penyebab Terbanyak Saat Ini:</span>
                            <b style="color: #92400e; font-size: 14px; display: block;">⚠️ ${issueTitle} (${maxCount} Kejadian)</b>
                        </div>
                        <p style="margin-bottom: 10px; font-weight: 600; color: var(--text-main);">Tindakan Edukasi & Rekomendasi Admin:</p>
                        <ul style="padding-left: 20px; display: flex; flex-direction: column; gap: 8px;">
                            ${actionPlan}
                            <li><b>Poster & Brosur Stadion:</b> Cetak panduan pencegahan ini untuk ditempel di papan informasi stadion atau bagikan dalam bentuk brosur edukasi bersamaan dengan brosur QR Code pendaftaran.</li>
                        </ul>
                    `;
                }
                
                educationTips.innerHTML = educationHTML;
            }

            // Render Chart.js and Domisili Lists
            function renderDemographics() {
                // Calculate age categories
                let ageUnder20 = 0, age20_29 = 0, age30_39 = 0, age40_49 = 0, ageAbove50 = 0;
                
                adminState.runners.forEach(r => {
                    if (r.age < 20) ageUnder20++;
                    else if (r.age >= 20 && r.age < 30) age20_29++;
                    else if (r.age >= 30 && r.age < 40) age30_39++;
                    else if (r.age >= 40 && r.age < 50) age40_49++;
                    else ageAbove50++;
                });

                // Chart.js age categories rendering
                const ctx = document.getElementById('ageChart').getContext('2d');
                if (adminState.ageChart) adminState.ageChart.destroy();
                
                adminState.ageChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['<20 Thn', '20-29 Thn', '30-39 Thn', '40-49 Thn', '>=50 Thn'],
                        datasets: [{
                            data: [ageUnder20, age20_29, age30_39, age40_49, ageAbove50],
                            backgroundColor: ['#ff007f', '#8b5cf6', '#a855f7', '#3b82f6', '#10b981'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: { font: { family: 'Outfit', size: 11 } }
                            }
                        }
                    }
                });

                // Top Address List (Domisili)
                const listEl = document.getElementById('top-address-list');
                listEl.innerHTML = '';
                
                const topAddr = adminState.addressDistribution.slice(0, 3);
                if (topAddr.length === 0) {
                    listEl.innerHTML = '<span style="color: var(--text-muted); font-style: italic;">Belum ada data alamat</span>';
                } else {
                    topAddr.forEach(addr => {
                        const percent = Math.round((addr.count / adminState.stats.total_runners) * 100);
                        listEl.innerHTML += `
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-weight: 500;">📍 ${addr.address}</span>
                                <span style="color: var(--primary); font-weight: 600;">${addr.count} Orang (${percent}%)</span>
                            </div>
                        `;
                    });
                }
            }

            // Populate locations table
            function populateLocationsTable(list) {
                const body = document.getElementById('locations-table-body');
                if (!body) return;
                
                body.innerHTML = '';
                if (list.length === 0) {
                    body.innerHTML = '<tr><td colspan="4" style="text-align: center; font-style: italic; color: var(--text-muted);">Tidak ada lokasi lari terdaftar</td></tr>';
                    return;
                }
                
                list.forEach(loc => {
                    const isDefault = (loc.id == adminState.defaultLocationId);
                    
                    const defaultAction = isDefault 
                        ? '<span class="badge" style="font-size: 11px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); color: var(--accent-green); padding: 4px 8px; border-radius: 6px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">⭐ Default</span>'
                        : `<button class="btn-table-action" style="background: rgba(245, 158, 11, 0.05); border-color: rgba(245, 158, 11, 0.2); color: var(--accent-yellow); font-size: 11px; padding: 4px 8px; font-weight: 500;" onclick="setDefaultLocation(${loc.id})">Set Default</button>`;

                    const delBtn = isDefault 
                        ? '<span style="font-size: 11px; color: var(--text-muted); font-style: italic;">Default Aktif</span>' 
                        : `<button class="btn-table-action" style="background: rgba(239, 68, 68, 0.05); border-color: rgba(239, 68, 68, 0.15); color: var(--accent-red); font-size: 11px; padding: 4px 8px;" onclick="deleteLocation(${loc.id})">Hapus</button>`;
                    
                    body.innerHTML += `
                        <tr>
                            <td style="font-weight: 600;">${loc.name}<br><span style="font-size: 11px; color: var(--text-muted); font-weight: normal;">${loc.address}</span></td>
                            <td style="font-family: monospace; font-size: 12px;">${parseFloat(loc.latitude).toFixed(4)}, ${parseFloat(loc.longitude).toFixed(4)}</td>
                            <td><a href="${loc.google_maps_url}" target="_blank" class="btn-table-action" style="text-decoration: none; display: inline-block; font-size: 11px; padding: 4px 8px;">Google Maps</a></td>
                            <td style="text-align: center;">
                                <div style="display: inline-flex; gap: 6px; align-items: center; justify-content: center;">
                                    ${defaultAction}
                                    ${delBtn}
                                </div>
                            </td>
                        </tr>
                    `;
                });
            }

            // Set Default Location
            async function setDefaultLocation(id) {
                try {
                    const res = await fetch('admin-api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'set_default_location',
                            id: id
                        })
                    });
                    const data = await res.json();
                    if (data.status === 'success') {
                        showToast(data.message, 'success');
                        loadDashboardData(); // Reload all stats and tables
                    } else {
                        showToast(data.message, 'error');
                    }
                } catch (err) {
                    showToast('Gagal mengubah lokasi default.', 'error');
                }
            }

            // Delete Location
            async function deleteLocation(id) {
                Swal.fire({
                    title: 'Hapus Lokasi?',
                    text: 'Apakah Anda yakin ingin menghapus lokasi lari ini?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--primary)',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    background: '#ffffff',
                    color: 'var(--navy)',
                    customClass: {
                        popup: 'swal2-custom-popup'
                    }
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        try {
                            const res = await fetch('admin-api.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    action: 'delete_location',
                                    id: id
                                })
                            });
                            const data = await res.json();
                            if (data.status === 'success') {
                                Swal.fire({
                                    title: 'Terhapus!',
                                    text: data.message,
                                    icon: 'success',
                                    confirmButtonColor: 'var(--primary)',
                                    background: '#ffffff',
                                    color: 'var(--navy)',
                                    customClass: {
                                        popup: 'swal2-custom-popup'
                                    }
                                });
                                loadDashboardData(); // Reload all stats and tables
                            } else {
                                Swal.fire({
                                    title: 'Gagal!',
                                    text: data.message,
                                    icon: 'error',
                                    confirmButtonColor: 'var(--primary)',
                                    background: '#ffffff',
                                    color: 'var(--navy)',
                                    customClass: {
                                        popup: 'swal2-custom-popup'
                                    }
                                });
                            }
                        } catch (err) {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Gagal menghubungi server.',
                                icon: 'error',
                                confirmButtonColor: 'var(--primary)',
                                background: '#ffffff',
                                color: 'var(--navy)',
                                customClass: {
                                    popup: 'swal2-custom-popup'
                                }
                            });
                        }
                    }
                });
            }

            // Map Picker instance
            let mapPicker = null;
            let pickerMarker = null;

            function initMapPicker() {
                const latInput = document.getElementById('loc-lat');
                const lngInput = document.getElementById('loc-lng');
                if (!latInput || !lngInput) return;

                // Center coords: use current inputs, or default stadium, or Surabaya central
                let lat = parseFloat(latInput.value) || -7.2622;
                let lng = parseFloat(lngInput.value) || 112.7425;

                // If not set yet, use the first stadium's coordinates if available
                if (!latInput.value && !lngInput.value && adminState.locations && adminState.locations.length > 0) {
                    const defaultLoc = adminState.locations.find(l => l.id === 1) || adminState.locations[0];
                    lat = parseFloat(defaultLoc.latitude);
                    lng = parseFloat(defaultLoc.longitude);
                    
                    // Auto fill initial values
                    latInput.value = lat.toFixed(6);
                    lngInput.value = lng.toFixed(6);
                }

                if (mapPicker) {
                    mapPicker.setView([lat, lng]);
                    if (pickerMarker) {
                        pickerMarker.setLatLng([lat, lng]);
                    }
                    setTimeout(() => {
                        if (mapPicker) mapPicker.invalidateSize();
                    }, 200);
                    return;
                }

                mapPicker = L.map('map-picker').setView([lat, lng], 13);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap contributors'
                }).addTo(mapPicker);

                // Add draggable marker
                pickerMarker = L.marker([lat, lng], { draggable: true }).addTo(mapPicker);

                // Update inputs on drag end
                pickerMarker.on('dragend', function (e) {
                    const position = pickerMarker.getLatLng();
                    latInput.value = position.lat.toFixed(6);
                    lngInput.value = position.lng.toFixed(6);
                    updateGoogleMapsUrlPreview(position.lat, position.lng);
                });

                // Update inputs and marker on map click
                mapPicker.on('click', function (e) {
                    const coords = e.latlng;
                    pickerMarker.setLatLng(coords);
                    latInput.value = coords.lat.toFixed(6);
                    lngInput.value = coords.lng.toFixed(6);
                    updateGoogleMapsUrlPreview(coords.lat, coords.lng);
                });

                // Sync inputs back to map picker if typed manually
                const syncInputsToMap = () => {
                    const currentLat = parseFloat(latInput.value);
                    const currentLng = parseFloat(lngInput.value);
                    if (!isNaN(currentLat) && !isNaN(currentLng)) {
                        pickerMarker.setLatLng([currentLat, currentLng]);
                        mapPicker.panTo([currentLat, currentLng]);
                        updateGoogleMapsUrlPreview(currentLat, currentLng);
                    }
                };

                latInput.addEventListener('input', syncInputsToMap);
                lngInput.addEventListener('input', syncInputsToMap);

                // Invalidate size to ensure it renders correctly on initial load
                setTimeout(() => {
                    if (mapPicker) mapPicker.invalidateSize();
                }, 200);
            }

            function updateGoogleMapsUrlPreview(lat, lng) {
                const mapsInput = document.getElementById('loc-maps');
                if (mapsInput && (!mapsInput.value || mapsInput.value.includes('maps.google.com'))) {
                    mapsInput.value = `https://maps.google.com/?q=${parseFloat(lat).toFixed(6)},${parseFloat(lng).toFixed(6)}`;
                }
            }

            // Init Leaflet maps and markers
            function initLeafletMap() {
                if (adminState.map) {
                    adminState.map.remove();
                    adminState.map = null;
                }
                
                // Center map dynamically: use coords of default location (ID 1) if available,
                // otherwise Surabaya central
                let centerLat = -7.2622;
                let centerLng = 112.7425;
                if (adminState.locations && adminState.locations.length > 0) {
                    const defaultLoc = adminState.locations.find(l => l.id === 1) || adminState.locations[0];
                    centerLat = parseFloat(defaultLoc.latitude);
                    centerLng = parseFloat(defaultLoc.longitude);
                }
                
                adminState.map = L.map('map').setView([centerLat, centerLng], 12);
                
                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap contributors'
                }).addTo(adminState.map);

                // Add stadium markers
                if (adminState.locations) {
                    adminState.locations.forEach(loc => {
                        const stadiumMarker = L.marker([parseFloat(loc.latitude), parseFloat(loc.longitude)], {
                            icon: L.divIcon({
                                className: 'custom-div-icon',
                                html: `<div style='background-color: var(--primary); color: white; padding: 6px 12px; border-radius: 99px; font-weight: 800; font-size: 11px; box-shadow: 0 4px 12px rgba(255, 0, 127, 0.4); border: 2px solid white; width: max-content; white-space: nowrap;'>🏟️ ${loc.name}</div>`,
                                iconSize: [100, 24],
                                iconAnchor: [50, 12]
                            })
                        }).addTo(adminState.map);
                        stadiumMarker.bindPopup(`<b>${loc.name}</b><br>${loc.address}<br><a href="${loc.google_maps_url}" target="_blank" style="color: var(--primary); font-weight: 600;">Buka Google Maps</a>`);
                    });
                }

                // Plot all registered runners clustered around their correct location
                adminState.mapMarkers = [];
                adminState.runners.forEach(r => {
                    const coords = getMockCoords(r);
                    const marker = L.marker(coords).addTo(adminState.map);
                    
                    // Check if they are currently active (in activeRunnersList)
                    const isActive = adminState.activeRunnersList.some(ar => ar.user_id === r.id);
                    const statusBadge = isActive 
                        ? '<span class="badge badge-normal" style="font-size: 8px; padding: 1px 6px; background: var(--accent-green); color: white; font-weight: 700; border-radius: 4px;">LARI AKTIF</span>' 
                        : '<span class="badge badge-normal" style="font-size: 8px; padding: 1px 6px; background: var(--text-muted); color: white; font-weight: 700; border-radius: 4px;">OFFLINE</span>';
                    
                    const popupContent = `
                        <div style="padding: 4px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; gap: 8px;">
                                <b style="font-size: 13px; color: var(--primary);">${r.fullname} (${r.gender})</b>
                                ${statusBadge}
                            </div>
                            Lokasi Terakhir: <b>${r.location_name || 'Lokasi Umum'}</b><br>
                            Usia: <b>${r.age} Tahun</b><br>
                            No HP: <b>${r.phone}</b><br>
                            Alamat: <b>${r.address}</b><br>
                            <button onclick="openRunnerModal(${r.id})" class="btn-table-action" style="margin-top: 8px; font-size: 9px; padding: 4px 8px; width: 100%;">Lihat Statistik Rekam Medis</button>
                        </div>
                    `;
                    marker.bindPopup(popupContent);
                    adminState.mapMarkers.push(marker);
                });
            }

            // Hashing function to simulate coordinate geocoding around the stadium
            function getMockCoords(r) {
                // If the runner has location coordinates in DB, use them as base!
                const baseLat = parseFloat(r.location_lat) || -7.2622;
                const baseLng = parseFloat(r.location_lng) || 112.7425;
                
                // Hash name/address to get consistent random offset
                let hash = 0;
                const cleanStr = String(r.fullname) + String(r.id);
                for (let i = 0; i < cleanStr.length; i++) {
                    hash = cleanStr.charCodeAt(i) + ((hash << 5) - hash);
                }
                
                // Spread coordinates around 1.5km offset from stadium/base
                const latOffset = ((Math.abs(hash) % 1000) / 1000) * 0.016 - 0.008;
                const lngOffset = (((Math.abs(hash) >> 3) % 1000) / 1000) * 0.016 - 0.008;
                
                return [baseLat + latOffset, baseLng + lngOffset];
            }

            // Interactive Indonesia Map functionality
            let indonesiaMap = null;
            let indonesiaMarkers = [];

            function initIndonesiaMap() {
                if (indonesiaMap) {
                    setTimeout(() => {
                        if (indonesiaMap) indonesiaMap.invalidateSize();
                    }, 200);
                    return;
                }

                // Centered on central Indonesia geographic bounds
                indonesiaMap = L.map('indonesia-map').setView([-2.5489, 118.0149], 5);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap contributors'
                }).addTo(indonesiaMap);

                updateIndonesiaMapMarkers();
                
                setTimeout(() => {
                    if (indonesiaMap) indonesiaMap.invalidateSize();
                }, 200);
            }

            function updateIndonesiaMapMarkers() {
                if (!indonesiaMap) return;
                
                indonesiaMarkers.forEach(marker => indonesiaMap.removeLayer(marker));
                indonesiaMarkers = [];

                if (adminState.locations) {
                    adminState.locations.forEach(loc => {
                        const userCount = loc.user_count || 0;
                        const runCount = loc.run_count || 0;
                        
                        const colorHex = userCount > 10 ? 'var(--primary)' : (userCount > 0 ? 'var(--secondary)' : 'var(--accent-green)');
                        
                        const marker = L.marker([parseFloat(loc.latitude), parseFloat(loc.longitude)], {
                            icon: L.divIcon({
                                className: 'indonesia-div-icon',
                                html: `<div style='background-color: ${colorHex}; color: white; padding: 6px 12px; border-radius: 99px; font-weight: 800; font-size: 11px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border: 2px solid white; width: max-content; white-space: nowrap;'>🏟️ ${loc.name} (${userCount} User)</div>`,
                                iconSize: [120, 24],
                                iconAnchor: [60, 12]
                            })
                        }).addTo(indonesiaMap);
                        
                        const popupHtml = `
                            <div style="padding: 6px; font-family: 'Outfit', sans-serif; min-width: 200px;">
                                <h3 style="margin-bottom: 8px; color: var(--navy); font-size: 13px; font-weight: 700; border-bottom: 1px solid var(--card-border); padding-bottom: 4px;">🏟️ ${loc.name}</h3>
                                <p style="margin: 4px 0; font-size: 11px; color: var(--text-muted); line-height: 1.4;">${loc.address}</p>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin: 8px 0; background: rgba(255, 0, 127, 0.02); padding: 8px; border-radius: 8px; border: 1px solid var(--card-border);">
                                    <div style="text-align: center;">
                                        <span style="font-size: 9px; color: var(--text-muted); display: block;">USER</span>
                                        <b style="font-size: 14px; color: var(--primary);">${userCount} Pelari</b>
                                    </div>
                                    <div style="text-align: center; border-left: 1px solid var(--card-border);">
                                        <span style="font-size: 9px; color: var(--text-muted); display: block;">SESI</span>
                                        <b style="font-size: 14px; color: var(--secondary);">${runCount} Run</b>
                                    </div>
                                </div>
                                <a href="${loc.google_maps_url}" target="_blank" class="btn-table-action" style="text-decoration: none; display: block; text-align: center; font-size: 10px; padding: 5px 10px; margin-top: 8px; background: rgba(255, 0, 127, 0.05); color: var(--primary); font-weight: 600; border-radius: 6px; border: 1px solid var(--card-border);">Buka Google Maps</a>
                            </div>
                        `;
                        marker.bindPopup(popupHtml);
                        indonesiaMarkers.push(marker);
                    });
                }
            }

            // Populate runners table
            function populateRunnersTable(list) {
                const body = document.getElementById('runners-table-body');
                body.innerHTML = '';

                const printBody = document.getElementById('print-runners-rows');
                printBody.innerHTML = '';
                
                if (list.length === 0) {
                    body.innerHTML = '<tr><td colspan="8" style="text-align: center; font-style: italic; color: var(--text-muted);">Tidak ada pelari terdaftar</td></tr>';
                    printBody.innerHTML = '<tr><td colspan="7" style="text-align: center; font-style: italic;">Tidak ada data</td></tr>';
                    return;
                }

                list.forEach((r, idx) => {
                    const regDate = new Date(r.created_at).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' });
                    
                    // Main layout row
                    body.innerHTML += `
                        <tr>
                            <td style="text-align: center; font-weight: 600;">${idx + 1}.</td>
                            <td style="font-weight: 600;" class="col-name">${r.fullname}</td>
                            <td>${r.age} Thn</td>
                            <td><span class="gender-badge ${r.gender.toLowerCase()}">${r.gender === 'L' ? 'L' : 'P'}</span></td>
                            <td>${r.phone}</td>
                            <td class="col-address">${r.address}</td>
                            <td>${regDate}</td>
                            <td style="text-align: center;">
                                <button class="btn-table-action" onclick="openRunnerModal(${r.id})">Buka Profil</button>
                            </td>
                        </tr>
                    `;

                    // Print layout row
                    printBody.innerHTML += `
                        <tr>
                            <td style="text-align: center;">${idx + 1}.</td>
                            <td>${r.fullname}</td>
                            <td>${r.gender === 'L' ? 'Laki-laki' : 'Perempuan'}</td>
                            <td>${r.age} Tahun</td>
                            <td>${r.address}</td>
                            <td>${r.phone}</td>
                            <td>${regDate}</td>
                        </tr>
                    `;
                });
            }

            // Filter runners table by search input
            function filterRunnersTable() {
                const query = document.getElementById('runner-search').value.toLowerCase();
                const filtered = adminState.runners.filter(r => 
                    r.fullname.toLowerCase().includes(query) || 
                    r.address.toLowerCase().includes(query)
                );
                populateRunnersTable(filtered);
            }

            // Populate recent completed runs logs table
            function populateRecentRunsTable(list) {
                const body = document.getElementById('runs-table-body');
                body.innerHTML = '';

                if (list.length === 0) {
                    body.innerHTML = '<tr><td colspan="9" style="text-align: center; font-style: italic; color: var(--text-muted);">Belum ada riwayat sesi lari</td></tr>';
                    return;
                }

                list.forEach((r, idx) => {
                    const dateStr = new Date(r.post_time).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                    const distanceKm = parseFloat(r.post_distance || 0).toFixed(2);
                    
                    // Hitung Score untuk display
                    const score = calculateMetricScore(r);
                    let scoreClass = 'danger';
                    if (score >= 80) scoreClass = 'perfect';
                    else if (score >= 60) scoreClass = 'warning';

                    let medBadge = '';
                    if (r.medical_status === 'approved') {
                        medBadge = '<span style="display:inline-block; font-size:9px; background:#d1fae5; color:#065f46; padding:1px 6px; border-radius:4px; margin-left:8px; font-weight:700; border: 1px solid rgba(6, 95, 70, 0.15);">⚠️ Ditangani</span>';
                    } else if (r.medical_status === 'pending') {
                        medBadge = '<span style="display:inline-block; font-size:9px; background:#fee2e2; color:#b91c1c; padding:1px 6px; border-radius:4px; margin-left:8px; font-weight:700; border: 1px solid rgba(185, 28, 28, 0.15); animation: alert-pulse 1.5s infinite;">⚠️ Peringatan</span>';
                    }

                    body.innerHTML += `
                        <tr>
                            <td style="text-align: center; font-weight: 600;">${idx + 1}.</td>
                            <td style="font-weight: 600;" class="col-name">${r.fullname}${medBadge}</td>
                            <td class="col-location">📍 <b>${r.location_name || 'Lokasi Umum'}</b></td>
                            <td>${dateStr}</td>
                            <td>${r.post_laps} lap</td>
                            <td>${distanceKm} km</td>
                            <td>${r.post_duration} mnt</td>
                            <td>${r.post_calories} kcal</td>
                            <td style="text-align: center;"><span class="run-badge ${scoreClass}">${score} / 100</span></td>
                        </tr>
                    `;
                });
            }

            // Calculate metric score manually helper
            function calculateMetricScore(run) {
                let score = 0;
                
                // 1. BMI (pre-weight, pre-height)
                const heightM = run.pre_height / 100;
                const imt = run.pre_weight / (heightM * heightM);
                
                const imtNormalMin = healthIndicators.ind_imt_normal_min;
                const imtNormalMax = healthIndicators.ind_imt_normal_max;
                const imtOverweightMin = healthIndicators.ind_imt_overweight_min;
                const imtOverweightMax = healthIndicators.ind_imt_overweight_max;
                
                if (imt >= imtNormalMin && imt <= imtNormalMax) {
                    score += healthIndicators.ind_imt_normal_score;
                } else if (imt >= imtOverweightMin && imt <= imtOverweightMax) {
                    score += healthIndicators.ind_imt_overweight_score;
                } else if (imt < imtNormalMin) {
                    score += healthIndicators.ind_imt_underweight_score;
                } else {
                    score += healthIndicators.ind_imt_obese_score;
                }

                // 2. BBI
                const baseBBI = run.pre_height - 100;
                const factor = (run.gender === 'L') ? 0.10 : 0.15;
                const bbi = baseBBI - (baseBBI * factor);
                const percentBBI = (run.pre_weight / bbi) * 100;
                
                const bbiIdealMin = healthIndicators.ind_bbi_ideal_min;
                const bbiIdealMax = healthIndicators.ind_bbi_ideal_max;
                
                if (percentBBI >= bbiIdealMin && percentBBI <= bbiIdealMax) {
                    score += healthIndicators.ind_bbi_ideal_score;
                } else if ((percentBBI >= 80 && percentBBI < bbiIdealMin) || (percentBBI > bbiIdealMax && percentBBI <= 120)) {
                    score += healthIndicators.ind_bbi_warning_score;
                } else {
                    score += healthIndicators.ind_bbi_danger_score;
                }

                // 3. Blood pressure pre
                const sys = run.pre_systolic;
                const dia = run.pre_diastolic;
                const bpNormalSys = healthIndicators.ind_bp_normal_sys;
                const bpNormalDia = healthIndicators.ind_bp_normal_dia;
                const bpWarningSys = healthIndicators.ind_bp_warning_sys;
                const bpWarningDia = healthIndicators.ind_bp_warning_dia;
                
                if (sys <= bpNormalSys && dia <= bpNormalDia) {
                    score += healthIndicators.ind_bp_normal_score;
                } else if ((sys > bpNormalSys && sys <= bpWarningSys) || (dia > bpNormalDia && dia <= bpWarningDia)) {
                    score += healthIndicators.ind_bp_warning_score;
                } else {
                    score += healthIndicators.ind_bp_danger_score;
                }

                // 4. Pre Heart rate
                const hrMin = healthIndicators.ind_hr_min;
                const hrMax = healthIndicators.ind_hr_max;
                if (run.pre_heart_rate >= hrMin && run.pre_heart_rate <= hrMax) {
                    score += healthIndicators.ind_hr_normal_score;
                } else {
                    score += healthIndicators.ind_hr_abnormal_score;
                }

                // 5. SpO2 pre
                const spo2Min = healthIndicators.ind_spo2_min;
                if (run.pre_spo2 >= spo2Min) {
                    score += healthIndicators.ind_spo2_normal_score;
                } else {
                    score += healthIndicators.ind_spo2_abnormal_score;
                }

                // 6. Pre temp
                const tempMin = healthIndicators.ind_temp_min;
                const tempMax = healthIndicators.ind_temp_max;
                if (run.pre_temperature >= tempMin && run.pre_temperature <= tempMax) {
                    score += healthIndicators.ind_temp_normal_score;
                } else {
                    score += healthIndicators.ind_temp_abnormal_score;
                }

                // 7. Cholesterol
                const cholMax = healthIndicators.ind_chol_max;
                if (run.pre_cholesterol < cholMax) {
                    score += healthIndicators.ind_chol_normal_score;
                } else {
                    score += healthIndicators.ind_chol_abnormal_score;
                }

                // 8. Target target
                const targetType = run.target_type || 'none';
                const targetVal = parseInt(run.target_value) || 0;
                if (targetType !== 'none' && targetVal > 0) {
                    if (targetType === 'time' && (run.post_duration || 0) >= targetVal) {
                        score += healthIndicators.ind_target_score;
                    } else if (targetType === 'laps' && (run.post_laps || 0) >= targetVal) {
                        score += healthIndicators.ind_target_score;
                    }
                }

                return Math.min(100, score);
            }

            // Open Runner profile details modal drawer
            async function openRunnerModal(runnerId) {
                try {
                    const res = await fetch('admin-api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'get_runner_details', runner_id: runnerId })
                    });
                    const data = await res.json();
                    
                    if (data.status === 'success') {
                        const r = data.runner;
                        const history = data.history;

                        document.getElementById('modal-runner-name').innerText = `Profil Rekam Medis: ${r.fullname}`;
                        document.getElementById('modal-gender').innerText = r.gender === 'L' ? 'Laki-Laki' : 'Perempuan';
                        document.getElementById('modal-age').innerText = `${r.age} Tahun (${r.dob})`;
                        document.getElementById('modal-phone').innerText = r.phone;
                        document.getElementById('modal-email').innerText = r.email ? r.email : '-';
                        document.getElementById('modal-address').innerText = r.address;

                        // Populate modal table
                        const mTable = document.getElementById('modal-history-table');
                        mTable.innerHTML = '';

                        const scoreProgression = [];
                        const runDates = [];

                        if (history.length === 0) {
                            mTable.innerHTML = '<tr><td colspan="6" style="text-align: center; font-style: italic; color: var(--text-muted);">Belum ada riwayat lari selesai</td></tr>';
                        } else {
                            // Reverse history for chronologic chart display
                            const reversedHistory = [...history].reverse();
                            reversedHistory.forEach(run => {
                                const score = calculateMetricScore(Object.assign({}, run, { gender: r.gender, dob: r.dob }));
                                scoreProgression.push(score);
                                
                                const d = new Date(run.post_time);
                                runDates.push(`${d.getDate()}/${d.getMonth()+1}`);
                            });

                            history.forEach((run, idx) => {
                                const score = calculateMetricScore(Object.assign({}, run, { gender: r.gender, dob: r.dob }));
                                const dStr = new Date(run.post_time).toLocaleDateString('id-ID', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                                const dist = parseFloat(run.post_distance || 0).toFixed(2);
                                
                                mTable.innerHTML += `
                                    <tr>
                                        <td style="text-align: center; font-weight: 600;">${idx + 1}.</td>
                                        <td>${dStr}</td>
                                        <td>📍 <b>${run.location_name || 'Lokasi Umum'}</b></td>
                                        <td>${run.post_duration} mnt</td>
                                        <td>${dist} km</td>
                                        <td><b>${score}</b></td>
                                    </tr>
                                `;
                            });
                        }

                        // Render Progression Line Chart
                        renderProgressionChart(runDates, scoreProgression);

                        // Show Drawer
                        document.getElementById('runner-modal').classList.add('active');
                    } else {
                        showToast(data.message, 'error');
                    }
                } catch (err) {
                    showToast('Gagal memuat detail rekam medis.', 'error');
                }
            }

            // Close Runner profile modal
            function closeRunnerModal() {
                document.getElementById('runner-modal').classList.remove('active');
            }

            // Progression line chart rendering
            function renderProgressionChart(labels, data) {
                const ctx = document.getElementById('progressionChart').getContext('2d');
                if (adminState.progressionChart) adminState.progressionChart.destroy();

                if (labels.length === 0) {
                    labels = ['Mulai'];
                    data = [0];
                }

                adminState.progressionChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Skor Kebugaran',
                            data: data,
                            borderColor: '#ff007f',
                            backgroundColor: 'rgba(255, 0, 127, 0.1)',
                            fill: true,
                            tension: 0.3,
                            borderWidth: 2,
                            pointRadius: 4,
                            pointBackgroundColor: '#8b5cf6'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { min: 0, max: 100, ticks: { font: { family: 'Outfit' } } },
                            x: { ticks: { font: { family: 'Outfit' } } }
                        }
                    }
                });
            }

            // Print friendly action handler
            function printReport() {
                const now = new Date();
                document.getElementById('print-date-label').innerText = `Tanggal Cetak Laporan: ${now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}`;
                window.print();
            }

            // Export reports to PDF via mPDF
            function exportPDF(type) {
                const startDate = document.getElementById('filter-start-date').value;
                const endDate = document.getElementById('filter-end-date').value;
                const url = `export-pdf.php?type=${type}&start_date=${startDate}&end_date=${endDate}`;
                window.open(url, '_blank');
            }

            // AJAX Form Submit untuk Kredensial Profil Admin
            document.addEventListener('submit', async (e) => {
                if (e.target && e.target.id === 'admin-profile-form') {
                    e.preventDefault();
                    const username = document.getElementById('profile-username').value;
                    const old_password = document.getElementById('profile-old-pass').value;
                    const new_password = document.getElementById('profile-new-pass').value;
                    const confirm_password = document.getElementById('profile-confirm-pass').value;

                    if (new_password && new_password.length < 6) {
                        showToast('Password baru minimal harus 6 karakter.', 'error');
                        return;
                    }
                    if (new_password && new_password !== confirm_password) {
                        showToast('Konfirmasi password baru tidak cocok.', 'error');
                        return;
                    }

                    try {
                        const res = await fetch('admin-api.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'update_admin_profile',
                                username,
                                old_password,
                                new_password,
                                confirm_password
                            })
                        });
                        const data = await res.json();
                        if (data.status === 'success') {
                            showToast(data.message, 'success');
                            document.getElementById('profile-old-pass').value = '';
                            document.getElementById('profile-new-pass').value = '';
                            document.getElementById('profile-confirm-pass').value = '';
                        } else {
                            showToast(data.message, 'error');
                        }
                    } catch (err) {
                        showToast('Gagal menghubungi server.', 'error');
                    }
                }
            });

            // AJAX Form Submit untuk Branding Aplikasi
            document.addEventListener('submit', async (e) => {
                if (e.target && e.target.id === 'app-settings-form') {
                    e.preventDefault();
                    const app_name = document.getElementById('settings-app-name').value;
                    const app_logo = document.getElementById('settings-app-logo').value;

                    try {
                        const res = await fetch('admin-api.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'update_app_settings',
                                app_name,
                                app_logo
                            })
                        });
                        const data = await res.json();
                        if (data.status === 'success') {
                            showToast(data.message, 'success');
                            
                            // Update sidebar branding
                            const logoEl = document.getElementById('sidebar-app-logo');
                            const nameEl = document.getElementById('sidebar-app-name');
                            if (logoEl) logoEl.innerText = app_logo;
                            if (nameEl) nameEl.innerText = app_name.toUpperCase();

                            // Update mobile navbar branding
                            const mLogoEl = document.getElementById('mobile-app-logo');
                            const mNameEl = document.getElementById('mobile-app-name');
                            if (mLogoEl) mLogoEl.innerText = app_logo;
                            if (mNameEl) mNameEl.innerText = app_name.toUpperCase();
                        } else {
                            showToast(data.message, 'error');
                        }
                    } catch (err) {
                        showToast('Gagal menghubungi server.', 'error');
                    }
                }
            });

            // AJAX Form Submit untuk Indikator Penilaian Kesehatan
            document.addEventListener('submit', async (e) => {
                if (e.target && e.target.id === 'health-indicators-form') {
                    e.preventDefault();
                    
                    const keys = [
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
                    
                    const payload = { action: 'update_health_indicators' };
                    keys.forEach(key => {
                        const inputId = key.replace(/_/g, '-');
                        const inputEl = document.getElementById(inputId);
                        if (inputEl) {
                            payload[key] = inputEl.value;
                        }
                    });
                    
                    try {
                        const res = await fetch('admin-api.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload)
                        });
                        const data = await res.json();
                        if (data.status === 'success') {
                            showToast(data.message, 'success');
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            showToast(data.message, 'error');
                        }
                    } catch (err) {
                        showToast('Gagal menghubungi server.', 'error');
                    }
                }
            });

            // AJAX Form Submit untuk Tambah Lokasi Lari
            document.addEventListener('submit', async (e) => {
                if (e.target && e.target.id === 'add-location-form') {
                    e.preventDefault();
                    
                    const name = document.getElementById('loc-name').value.trim();
                    const address = document.getElementById('loc-address').value.trim();
                    const latitude = document.getElementById('loc-lat').value;
                    const longitude = document.getElementById('loc-lng').value;
                    const google_maps_url = document.getElementById('loc-maps').value.trim();
                    
                    try {
                        const res = await fetch('admin-api.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'add_location',
                                name,
                                address,
                                latitude,
                                longitude,
                                google_maps_url
                            })
                        });
                        const data = await res.json();
                        if (data.status === 'success') {
                            showToast(data.message, 'success');
                            document.getElementById('add-location-form').reset();
                            loadDashboardData(); // Reload all stats and tables
                            setTimeout(() => initMapPicker(), 100);
                        } else {
                            showToast(data.message, 'error');
                        }
                    } catch (err) {
                        showToast('Gagal menghubungi server.', 'error');
                    }
                }
            });

            // Toggle sidebar open/close on mobile
            function toggleSidebar() {
                const sidebar = document.querySelector('.sidebar');
                const backdrop = document.querySelector('.sidebar-backdrop');
                if (sidebar) sidebar.classList.toggle('active');
                if (backdrop) backdrop.classList.toggle('active');
            }

            // Logout administrator
            async function handleAdminLogout() {
                if (!confirm('Apakah Anda yakin ingin logout dari portal administrator?')) return;

                try {
                    const res = await fetch('admin-api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'logout' })
                    });
                    const data = await res.json();
                    if (data.status === 'success') {
                        window.location.reload();
                    } else {
                        showToast(data.message, 'error');
                    }
                } catch (err) {
                    showToast('Gagal menghubungi server.', 'error');
                }
            }

            // Global toast utility
            function showToast(msg, type = 'error') {
                const toast = document.getElementById('toast-el');
                toast.innerText = msg;
                toast.className = `toast ${type} active`;
                setTimeout(() => {
                    toast.classList.remove('active');
                }, 3000);
            }
        </script>
    <?php endif; ?>
</body>
</html>
