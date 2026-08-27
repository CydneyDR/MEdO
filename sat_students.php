<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['sat_logged_in'])) {
    header("Location: sat_login.php");
    exit;
}

$sat_id = $_SESSION['sat_id'];
$sat_name = $_SESSION['sat_name'];
$current_page = basename($_SERVER['PHP_SELF']);

// Active tab determination (log, history, or dashboard)
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'log';
$error_message = "";

// 1. PROCESS TIME-IN
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_time_in'])) {
    $student_name = trim($_POST['student_name']);

    $check_active = $pdo->prepare("SELECT id FROM satellite_students WHERE satellite_id = ? AND student_name = ? AND status = 'In-Session'");
    $check_active->execute([$sat_id, $student_name]);
    
    if ($check_active->rowCount() > 0) {
        $error_message = "Ang kliyenteng si '" . htmlspecialchars($student_name) . "' ay may active session pa (In-Session). Kailangan muna nilang mag-Time-Out bago makapag-Time-In muli.";
    } else {
        $student_photo = isset($_POST['student_photo']) ? $_POST['student_photo'] : '';

        $stmt = $pdo->prepare("INSERT INTO satellite_students (
            satellite_id, student_name, age, sex, barangay, sector, 
            time_in, specific_program, student_photo, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'In-Session')");

        $stmt->execute([
            $sat_id,
            $student_name,
            $_POST['age'],
            $_POST['sex'],
            $_POST['barangay'],
            $_POST['sector'],
            $_POST['time_in'],
            $_POST['specific_program'],
            $student_photo
        ]);

        header("Location: sat_students.php?success=in");
        exit;
    }
}

// 2. PROCESS TIME-OUT
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_time_out'])) {
    $services = isset($_POST['service_availed']) ? implode(', ', $_POST['service_availed']) : '';
    $printed_pages = isset($_POST['printed_pages']) ? intval($_POST['printed_pages']) : 0;

    $stmt = $pdo->prepare("UPDATE satellite_students SET 
        time_out = ?, 
        length_of_stay = ?, 
        service_availed = ?, 
        printed_pages = ?, 
        status = 'Completed' 
        WHERE id = ?");

    $stmt->execute([
        $_POST['time_out'],
        $_POST['length_of_stay'],
        $services,
        $printed_pages,
        $_POST['record_id']
    ]);

    header("Location: sat_students.php?success=out");
    exit;
}

// AJAX HISTORY ENDPOINT PARA SA CLIENT HISTORY
if (isset($_GET['get_history']) && !empty($_GET['client_name'])) {
    $cname = $_GET['client_name'];
    $hist_stmt = $pdo->prepare("SELECT * FROM satellite_students WHERE satellite_id = ? AND student_name = ? ORDER BY date_registered DESC");
    $hist_stmt->execute([$sat_id, $cname]);
    echo json_encode($hist_stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// KUNIN ANG MGA DATA PARA SA DATABASE DISPLAY
$repeating_clients = $pdo->prepare("SELECT DISTINCT student_name, age, sex, barangay, sector, student_photo FROM satellite_students WHERE satellite_id = ? ORDER BY student_name ASC");
$repeating_clients->execute([$sat_id]);
$clients_list = $repeating_clients->fetchAll(PDO::FETCH_ASSOC);

$students_query = $pdo->prepare("SELECT * FROM satellite_students WHERE satellite_id = ? ORDER BY date_registered DESC");
$students_query->execute([$sat_id]);
$students = $students_query->fetchAll(PDO::FETCH_ASSOC);
$count_students = count($students);

// --- DASHBOARD FILTER & COMPUTATIONS ---
$filter_from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01');
$filter_to = isset($_GET['to']) ? $_GET['to'] : date('Y-m-t');

$dash_query = $pdo->prepare("SELECT * FROM satellite_students WHERE satellite_id = ? AND DATE(date_registered) BETWEEN ? AND ?");
$dash_query->execute([$sat_id, $filter_from, $filter_to]);
$dash_records = $dash_query->fetchAll(PDO::FETCH_ASSOC);

$total_users_count = count($dash_records);
$name_frequencies = [];
foreach ($dash_records as $rec) {
    $n = trim($rec['student_name']);
    if (!isset($name_frequencies[$n])) $name_frequencies[$n] = 0;
    $name_frequencies[$n]++;
}

$unique_individuals_count = count($name_frequencies);
$repeat_clientele_count = $total_users_count - $unique_individuals_count;
if ($repeat_clientele_count < 0) $repeat_clientele_count = 0;

$sex_counts = ['Male' => 0, 'Female' => 0, 'Prefer Not to Say' => 0];
$age_brackets = ['0-16' => 0, '17-30' => 0, '31-45' => 0, 'Above 45' => 0];
$location_counts = [];
$services_counts = [];
$sectors_counts = [];

foreach ($dash_records as $rec) {
    $s = $rec['sex'];
    if (isset($sex_counts[$s])) $sex_counts[$s]++;
    else $sex_counts['Prefer Not to Say']++;

    $age = intval($rec['age']);
    if ($age <= 16) $age_brackets['0-16']++;
    elseif ($age <= 30) $age_brackets['17-30']++;
    elseif ($age <= 45) $age_brackets['31-45']++;
    else $age_brackets['Above 45']++;

    $loc = !empty($rec['barangay']) ? $rec['barangay'] : 'Unknown';
    if (!isset($location_counts[$loc])) $location_counts[$loc] = 0;
    $location_counts[$loc]++;

    $srvs = explode(',', $rec['service_availed']);
    foreach ($srvs as $srv) {
        $srv = trim($srv);
        if (!empty($srv)) {
            if (!isset($services_counts[$srv])) $services_counts[$srv] = 0;
            $services_counts[$srv]++;
        }
    }

    $sec = !empty($rec['sector']) ? $rec['sector'] : 'Others';
    if (!isset($sectors_counts[$sec])) $sectors_counts[$sec] = 0;
    $sectors_counts[$sec]++;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMILE IT LAB - <?php echo htmlspecialchars($sat_name); ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            margin: 0;
            padding: 0;
            color: #334155;
            display: flex;
            min-height: 100vh;
            width: 100vw;
            max-width: 100%;
            overflow-x: hidden;
        }

        /* SIDEBAR */
        .sidebar {
            width: 260px;
            background: #1e293b;
            color: #f1f5f9;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 100;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
        }

        .sidebar-brand {
            padding: 24px;
            border-bottom: 1px solid #334155;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-brand-icon {
            background: #3b82f6;
            padding: 8px;
            border-radius: 8px;
            display: flex;
        }

        .sidebar-brand h3 {
            margin: 0;
            font-size: 1.1rem;
            color: #ffffff;
            line-height: 1.2;
        }

        .sidebar-brand span {
            font-size: 0.75rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .sidebar-menu {
            padding: 20px 15px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #cbd5e1;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .menu-item:hover {
            background: #334155;
            color: #ffffff;
        }

        .menu-item.active {
            background: #3b82f6;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid #334155;
        }

        /* EXACT REFERENCE CLOCK BOX DESIGN */
        .sidebar-clock {
            background: #111827;
            border: 1px solid #334155;
            padding: 14px;
            border-radius: 12px;
            margin-bottom: 15px;
            text-align: center;
        }

        .clock-time {
            font-size: 1.25rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .clock-date {
            font-size: 0.75rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-logout {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 12px;
            background: transparent;
            border: 1px solid #ef4444;
            color: #ef4444;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
            box-sizing: border-box;
        }

        .btn-logout:hover {
            background: #ef4444;
            color: #ffffff;
        }

        /* TOP NAVBAR */
        .top-navbar {
            position: fixed;
            top: 0;
            left: 260px;
            width: calc(100% - 260px);
            height: 70px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            box-sizing: border-box;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            z-index: 90;
        }

        .school-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* MODERNIZED SUB-NAVIGATION BUTTON TABS */
        .sub-nav-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 28px;
        }

        .sub-nav-tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 22px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            color: #475569;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
            transition: all 0.25s ease;
        }

        .sub-nav-tab:hover {
            border-color: #3b82f6;
            color: #2563eb;
            background: #f8fafc;
        }

        .sub-nav-tab.active {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.3);
        }

        /* MAIN CONTENT */
        .main-content {
            margin-left: 260px;
            margin-top: 70px;
            flex: 1;
            padding: 30px;
            width: calc(100% - 260px);
            max-width: calc(100% - 260px);
            box-sizing: border-box;
            overflow-x: hidden;
        }

        .page-header {
            font-size: 1.8rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 12px;
        }

        .modern-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
            padding: 28px;
            margin-bottom: 24px;
            width: 100%;
            box-sizing: border-box;
        }

        .card-title {
            margin-top: 0;
            color: #0f172a;
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 12px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            max-height: 450px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: #f8fafc;
            padding: 14px 16px;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }

        .data-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            font-size: 0.9rem;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
        }

        .badge-enrolled {
            background: #ede9fe;
            color: #8b5cf6;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 8px;
            color: #475569;
        }

        .input-group {
            display: flex;
            align-items: center;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.2s ease;
            width: 100%;
        }

        .input-group:focus-within {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .input-group .form-control {
            border: none !important;
            background: transparent !important;
            box-shadow: none !important;
            padding: 10px 14px;
            font-size: 0.95rem;
            outline: none;
            flex: 1;
            color: #0f172a;
        }

        .btn-inline-action {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 0 18px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            height: 100%;
            min-height: 42px;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-inline-action:hover {
            background: #2563eb;
        }

        .form-control {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 0.95rem;
            box-sizing: border-box;
            background: #ffffff;
            font-family: inherit;
            transition: all 0.2s;
            color: #0f172a;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .btn {
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-align: center;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }
        .btn-primary:hover { background: #2563eb; }

        .btn-success {
            background: #8b5cf6;
            color: white;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.2);
        }
        .btn-success:hover { background: #7c3aed; }

        .btn-sm-action {
            background: #ea580c;
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 0.8rem;
            transition: 0.2s;
        }
        .btn-sm-action:hover { background: #c2410c; }

        .btn-history {
            background: #0284c7;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 0.8rem;
            transition: 0.2s;
        }
        .btn-history:hover { background: #0369a1; }

        dialog {
            border: none;
            border-radius: 16px;
            padding: 0;
            width: 100%;
            max-width: 650px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        dialog::backdrop {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
        }

        .modal-header {
            padding: 16px 20px;
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 700;
            font-size: 1.1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 24px;
            max-height: 450px;
            overflow-y: auto;
        }

        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            padding: 15px 20px;
            border-radius: 12px;
            border: 1px solid #fecaca;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .alert-success {
            background: #dcfce7;
            color: #16a34a;
            padding: 15px 20px;
            border-radius: 12px;
            border: 1px solid #bbf7d0;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            background: #f8fafc;
            padding: 16px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-sizing: border-box;
            width: 100%;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: #334155;
            cursor: pointer;
            font-weight: normal !important;
            word-break: break-word;
        }

        .camera-container {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.01);
            height: 100%;
        }

        .dash-summary-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 24px;
        }
        .dash-box {
            background: #1e293b;
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .dash-box h4 {
            margin: 0 0 8px 0;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
        }
        .dash-box .number {
            font-size: 2rem;
            font-weight: 800;
            color: #38bdf8;
            margin: 0;
        }
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }
        .chart-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .chart-card h3 {
            font-size: 1rem;
            color: #0f172a;
            margin-top: 0;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
            width: 100%;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 8px;
        }
        .chart-container {
            position: relative;
            width: 100%;
            max-width: 320px;
            height: 260px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
    </style>
</head>

<body>

    <!-- DARK SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">
                <svg width="24" height="24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                </svg>
            </div>
            <div>
                <span>Menu Portal</span>
                <h3>SMILE IT LAB</h3>
            </div>
        </div>

        <div class="sidebar-menu">
            <a href="sat_index.php" class="menu-item">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                Supplies & Requisition
            </a>
            <a href="sat_maintenance.php" class="menu-item">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                Maintenance & Repair
            </a>
            <a href="sat_students.php" class="menu-item active">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                Student Registration
            </a>
            <a href="sat_calendar.php" class="menu-item">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                Event Calendar
            </a>
        </div>

        <div class="sidebar-footer">
            <div class="sidebar-clock">
                <div id="clock-time" class="clock-time">00:00:00 AM</div>
                <div id="clock-date" class="clock-date">MON, AUG 24, 2026</div>
            </div>

            <a href="sat_logout.php" class="btn-logout">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Log Out
            </a>
        </div>
    </div>

    <!-- WHITE TOP NAVBAR -->
    <div class="top-navbar">
        <div class="school-name">
            <svg width="22" height="22" fill="none" stroke="#2563eb" stroke-width="2" viewBox="0 0 24 24">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            </svg>
            <?php echo htmlspecialchars($sat_name); ?>
        </div>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            let hours = now.getHours();
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12;
            const formattedTime = `${String(hours).padStart(2, '0')}:${minutes}:${seconds} ${ampm}`;
            
            const options = { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' };
            const formattedDate = now.toLocaleDateString('en-US', options).toUpperCase();

            document.getElementById('clock-time').textContent = formattedTime;
            document.getElementById('clock-date').textContent = formattedDate;
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <?php if (!empty($error_message)): ?>
            <div class="alert-error">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert-success">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                Record successfully updated!
            </div>
        <?php endif; ?>

        <div class="page-header">Student & Client Registration / Log</div>

        <!-- MODERNIZED SUB-NAVIGATION BUTTON TABS -->
        <div class="sub-nav-tabs">
            <a href="sat_students.php?tab=log" class="sub-nav-tab <?php echo ($active_tab == 'log') ? 'active' : ''; ?>">
                Registration & Logs
            </a>
            <a href="sat_students.php?tab=history" class="sub-nav-tab <?php echo ($active_tab == 'history') ? 'active' : ''; ?>">
                Client Visit History
            </a>
            <a href="sat_students.php?tab=dashboard" class="sub-nav-tab <?php echo ($active_tab == 'dashboard') ? 'active' : ''; ?>">
                Analytics & Dashboard
            </a>
        </div>

        <?php if ($active_tab == 'log'): ?>
            <!-- REGISTRATION & LOG VIEW -->
            <div class="modern-card" style="border-top: 4px solid #8b5cf6;">
                <div class="card-title"><span style="color: #8b5cf6;">Client Time-In Log & Capture</span></div>

                <div style="background: #f8fafc; padding: 16px; border-radius: 10px; margin-bottom: 24px; border: 1px dashed #cbd5e1;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; display: block; margin-bottom: 6px;">Search & Quick Fill for Returning Client (Repeater):</label>
                    <input type="text" id="clientSearchInput" class="form-control" list="repeatClientsList"
                        placeholder="Type name to search returning client..." oninput="checkAndFillClient(this)">
                    <datalist id="repeatClientsList">
                        <?php foreach ($clients_list as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['student_name']); ?>"
                                data-age="<?php echo $c['age']; ?>" 
                                data-sex="<?php echo htmlspecialchars($c['sex']); ?>"
                                data-barangay="<?php echo htmlspecialchars($c['barangay']); ?>"
                                data-sector="<?php echo htmlspecialchars($c['sector']); ?>"
                                data-photo="<?php echo htmlspecialchars($c['student_photo']); ?>">
                                <?php echo htmlspecialchars($c['student_name']); ?> (<?php echo htmlspecialchars($c['barangay']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </datalist>
                    <small style="color: #64748b; font-size: 0.75rem; margin-top: 6px; display: block;">Mag-type lang para hanapin ang dati nang client (automatic na hihiramin ang impormasyon at litrato kung meron).</small>
                </div>

                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start;">
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                            <div class="form-group"><label>Name</label><input type="text" id="student_name" name="student_name" class="form-control" required placeholder="Full Name"></div>
                            <div class="form-group"><label>Age</label><input type="number" id="age" name="age" class="form-control" required placeholder="Age"></div>
                            <div class="form-group"><label>Sex</label>
                                <select id="sex" name="sex" class="form-control" required>
                                    <option value="" disabled selected>Select Sex</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>

                            <div class="form-group"><label>Barangay</label>
                                <select id="barangay" name="barangay" class="form-control" required>
                                    <option value="" disabled selected>Select Barangay</option>
                                    <option value="Dolores">Dolores</option>
                                    <option value="Muzon">Muzon</option>
                                    <option value="San Isidro">San Isidro</option>
                                    <option value="San Juan">San Juan</option>
                                    <option value="Sta. Ana">Sta. Ana</option>
                                    <option value="Other City/ Municipality">Other City/ Municipality</option>
                                    <option value="Municipal Government">Municipal Government</option>
                                </select>
                            </div>

                            <div class="form-group"><label>Sector</label>
                                <select id="sector" name="sector" class="form-control" required>
                                    <option value="" disabled selected>Select Sector</option>
                                    <option value="Student">Student</option>
                                    <option value="Out-of-School Youth">Out-of-School Youth</option>
                                    <option value="PWD">PWD</option>
                                    <option value="Senior Citizen">Senior Citizen</option>
                                    <option value="Employed">Employed</option>
                                    <option value="Unemployed">Unemployed</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Time In</label>
                                <div class="input-group">
                                    <input type="time" id="time_in_input" name="time_in" class="form-control" required>
                                    <button type="button" class="btn-inline-action" onclick="setCurrentTime('time_in_input')">Now</button>
                                </div>
                            </div>

                            <div class="form-group" style="grid-column: 1 / -1;"><label>Specific Program Conducted in Satellite Offices</label><textarea name="specific_program" class="form-control" rows="2" placeholder="Details of the program..."></textarea></div>
                        </div>

                        <!-- CAMERA BOX -->
                        <div class="camera-container">
                            <label style="font-weight: 700; font-size: 0.9rem; margin-bottom: 14px; color: #1e293b; text-align: center;">Client Photo Capture</label>
                            <div style="position: relative; margin-bottom: 16px; width: 100%; display: flex; justify-content: center; background: #0f172a; border-radius: 10px; overflow: hidden; border: 2px solid #cbd5e1;">
                                <video id="webcam" autoplay playsinline width="280" height="210" style="background:#0f172a; object-fit:cover; display:block; width: 100%; height: 210px;"></video>
                                <canvas id="canvas" width="280" height="210" style="display:none;"></canvas>
                                <img id="photoPreview" src="" alt="Captured Photo" style="display:none; width: 100%; height: 210px; object-fit:cover;" />
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 10px; width: 100%;">
                                <button type="button" class="btn btn-primary" onclick="startCamera()" style="padding: 10px 16px; font-size: 0.9rem;">Start Camera</button>
                                <button type="button" class="btn btn-success" onclick="capturePhoto()" style="padding: 10px 16px; font-size: 0.9rem;">Capture Photo</button>
                                <button type="button" class="btn" style="background: #e2e8f0; color: #334155; padding: 10px 16px; font-size: 0.9rem;" onclick="retakePhoto()">Retake</button>
                            </div>
                            <input type="hidden" name="student_photo" id="student_photo_input">
                        </div>

                    </div>

                    <button type="submit" name="submit_time_in" class="btn btn-success" style="margin-top: 24px; width: 100%; padding: 14px; font-size: 1rem;">Save Time-In (Check-In)</button>
                </form>
            </div>

            <!-- TABLE RECORD -->
            <div class="modern-card">
                <div class="card-title"><span>Registered Logs & Records (<span style="color:#8b5cf6;"><?php echo $count_students; ?></span>)</span></div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Name / Age / Sex</th>
                                <th>Barangay / Sector</th>
                                <th>Time In / Out</th>
                                <th>Length of Stay</th>
                                <th>Service Availed</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($students) > 0): ?>
                                <?php foreach ($students as $st): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($st['student_photo'])): ?>
                                                <img src="<?php echo $st['student_photo']; ?>" alt="Photo" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #cbd5e1; cursor: pointer;" onclick="openImageModal('<?php echo $st['student_photo']; ?>')" title="Click to enlarge">
                                            <?php else: ?>
                                                <div style="width: 45px; height: 45px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; color: #64748b;">No Photo</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong style="color: #0f172a;"><?php echo htmlspecialchars($st['student_name']); ?></strong><br>
                                            <span style="font-size: 0.8rem; color:#64748b;"><?php echo $st['age']; ?> yrs old, <?php echo htmlspecialchars($st['sex']); ?></span>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($st['barangay']); ?><br>
                                            <span style="font-size: 0.8rem; color:#64748b; font-style: italic;"><?php echo htmlspecialchars($st['sector']); ?></span>
                                        </td>
                                        <td>
                                            <strong style="color: #2563eb;"><?php echo date('h:i A', strtotime($st['time_in'])); ?></strong> to
                                            <?php if (!empty($st['time_out'])): ?>
                                                <strong style="color: #ea580c;"><?php echo date('h:i A', strtotime($st['time_out'])); ?></strong>
                                            <?php else: ?>
                                                <span style="color: #f97316; font-weight: bold;">(In-Session)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo !empty($st['length_of_stay']) ? htmlspecialchars($st['length_of_stay']) : '-'; ?></td>
                                        <td><span class="badge badge-enrolled"><?php echo !empty($st['service_availed']) ? htmlspecialchars($st['service_availed']) : '-'; ?></span></td>
                                        <td>
                                            <?php if (empty($st['time_out'])): ?>
                                                <button type="button" class="btn-sm-action" onclick="openTimeoutModal(<?php echo $st['id']; ?>, '<?php echo addslashes($st['student_name']); ?>', '<?php echo $st['time_in']; ?>')">Time Out</button>
                                            <?php else: ?>
                                                <span style="color: #16a34a; font-weight: bold; font-size: 0.85rem;">Completed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: #94a3b8; padding: 30px;">No records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($active_tab == 'history'): ?>
            <!-- CLIENT VISIT HISTORY DIRECTORY VIEW -->
            <div class="modern-card" style="border-top: 4px solid #0284c7;">
                <div class="card-title">
                    <span>Client Directory & Complete Visit History</span>
                    <span style="font-size: 0.85rem; color: #64748b; font-weight: normal;">Total Registered Clients: <?php echo count($clients_list); ?></span>
                </div>

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Client Name</th>
                                <th>Age / Sex</th>
                                <th>Barangay</th>
                                <th>Sector</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($clients_list) > 0): ?>
                                <?php foreach ($clients_list as $cl): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($cl['student_photo'])): ?>
                                                <img src="<?php echo $cl['student_photo']; ?>" alt="Photo" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #cbd5e1; cursor: pointer;" onclick="openImageModal('<?php echo $cl['student_photo']; ?>')" title="Click to enlarge">
                                            <?php else: ?>
                                                <div style="width: 45px; height: 45px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; color: #64748b;">No Photo</div>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong style="color: #0f172a; font-size: 1rem;"><?php echo htmlspecialchars($cl['student_name']); ?></strong></td>
                                        <td><?php echo $cl['age']; ?> yrs old, <?php echo htmlspecialchars($cl['sex']); ?></td>
                                        <td><?php echo htmlspecialchars($cl['barangay']); ?></td>
                                        <td><span style="font-style: italic; color: #64748b;"><?php echo htmlspecialchars($cl['sector']); ?></span></td>
                                        <td>
                                            <button type="button" class="btn-history" onclick="viewClientHistory('<?php echo addslashes($cl['student_name']); ?>')">View Full History</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #94a3b8; padding: 30px;">No client history found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php else: ?>
            <!-- ANALYTICS & DATA VISUALIZATION DASHBOARD VIEW -->
            <div class="modern-card" style="border-top: 4px solid #3b82f6;">
                <div class="card-title">
                    <span>Data Visualization Dashboard & Summary</span>
                </div>

                <!-- FILTER FORM -->
                <form method="GET" action="sat_students.php" style="display: flex; gap: 15px; align-items: flex-end; background: #f8fafc; padding: 16px; border-radius: 10px; margin-bottom: 24px; border: 1px solid #e2e8f0;">
                    <input type="hidden" name="tab" value="dashboard">
                    <div style="flex: 1;">
                        <label style="font-size: 0.8rem; font-weight: 700; color: #475569; margin-bottom: 4px; display: block;">From:</label>
                        <input type="date" name="from" value="<?php echo htmlspecialchars($filter_from); ?>" class="form-control">
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 0.8rem; font-weight: 700; color: #475569; margin-bottom: 4px; display: block;">To:</label>
                        <input type="date" name="to" value="<?php echo htmlspecialchars($filter_to); ?>" class="form-control">
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary" style="padding: 11px 20px;">Filter Data</button>
                    </div>
                </form>

                <!-- SUMMARY NUMBERS BOXES -->
                <div class="dash-summary-row">
                    <div class="dash-box">
                        <h4>Total Users</h4>
                        <p class="number"><?php echo $total_users_count; ?></p>
                    </div>
                    <div class="dash-box">
                        <h4>New / Unique Individuals</h4>
                        <p class="number"><?php echo $unique_individuals_count; ?></p>
                    </div>
                    <div class="dash-box">
                        <h4>Repeat Clientele</h4>
                        <p class="number"><?php echo $repeat_clientele_count; ?></p>
                    </div>
                </div>

                <!-- CHARTS GRID -->
                <div class="chart-grid">
                    
                    <!-- 1. Total Users Breakdown (Bar Chart) -->
                    <div class="chart-card" style="grid-column: 1 / -1;">
                        <h3>Total Users Breakdown</h3>
                        <div style="width: 100%; height: 180px; position: relative;">
                            <canvas id="totalUsersBar"></canvas>
                        </div>
                    </div>

                    <!-- 2. Sex-Disaggregated Data (Pie Chart) -->
                    <div class="chart-card">
                        <h3>Sex-Disaggregated Data of Clienteles</h3>
                        <div class="chart-container">
                            <canvas id="sexPieChart"></canvas>
                        </div>
                    </div>

                    <!-- 3. Location (Doughnut Chart) -->
                    <div class="chart-card">
                        <h3>Location Breakdown</h3>
                        <div class="chart-container">
                            <canvas id="locationPieChart"></canvas>
                        </div>
                    </div>

                    <!-- 4. Age Bracket (Bar Chart) -->
                    <div class="chart-card">
                        <h3>Age Bracket</h3>
                        <div class="chart-container">
                            <canvas id="ageBarChart"></canvas>
                        </div>
                    </div>

                    <!-- 5. Services Availed (Bar Chart) -->
                    <div class="chart-card">
                        <h3>Services Availed</h3>
                        <div class="chart-container">
                            <canvas id="servicesBarChart"></canvas>
                        </div>
                    </div>

                    <!-- 6. Sectors (Bar Chart) -->
                    <div class="chart-card" style="grid-column: 1 / -1;">
                        <h3>Sectors</h3>
                        <div style="width: 100%; height: 220px; position: relative;">
                            <canvas id="sectorsBarChart"></canvas>
                        </div>
                    </div>

                </div>

            </div>
        <?php endif; ?>

    </div>

    <!-- CLIENT VISIT HISTORY MODAL -->
    <dialog id="historyModal">
        <div class="modal-header">
            <span id="historyModalTitle" style="font-weight: bold; color: #0f172a;">Client Visit History</span>
            <button type="button" style="background:none; border:none; font-size:1.2rem; cursor:pointer;" onclick="document.getElementById('historyModal').close()">✕</button>
        </div>
        <div class="modal-body">
            <div id="historyModalContent">Loading history...</div>
        </div>
    </dialog>

    <!-- TIME OUT MODAL -->
    <dialog id="timeoutModal">
        <div class="modal-header">
            <span>Record Time Out & Services</span>
            <button type="button" style="background:none; border:none; font-size:1.2rem; cursor:pointer;" onclick="document.getElementById('timeoutModal').close()">✕</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="record_id" id="timeout_record_id">
                <input type="hidden" id="modal_time_in">
                <p id="timeout_client_name" style="font-weight: bold; color: #0f172a; margin-bottom: 15px;"></p>

                <div class="form-group">
                    <label>Time Out</label>
                    <div class="input-group">
                        <input type="time" id="timeout_input" name="time_out" class="form-control" required onchange="calculateLengthOfStay()">
                        <button type="button" class="btn-inline-action" onclick="setCurrentTimeout()">Now</button>
                    </div>
                </div>

                <div class="form-group"><label>Length of Stay (Automatic)</label><input type="text" id="length_of_stay_input" name="length_of_stay" class="form-control" required readonly></div>

                <div class="form-group">
                    <label>Service Availed (Pwedeng mamili ng isa o higit pa)</label>
                    <div class="checkbox-grid">
                        <label class="checkbox-label"><input type="checkbox" name="service_availed[]" value="Computer Usage"> Computer Usage</label>
                        <label class="checkbox-label"><input type="checkbox" name="service_availed[]" value="Co-working space"> Co-working space</label>
                        <label class="checkbox-label"><input type="checkbox" name="service_availed[]" value="Printing" onchange="togglePrintedPages()"> Printing</label>
                        <label class="checkbox-label"><input type="checkbox" name="service_availed[]" value="Training and Capacity Building"> Training and Capacity Building</label>
                        <label class="checkbox-label"><input type="checkbox" name="service_availed[]" value="Shared Service Facility"> Shared Service Facility</label>
                        <label class="checkbox-label"><input type="checkbox" name="service_availed[]" value="E-Government Services"> E-Government Services</label>
                        <label class="checkbox-label"><input type="checkbox" name="service_availed[]" value="Photocopy" onchange="togglePrintedPages()"> Photocopy</label>
                        <label class="checkbox-label"><input type="checkbox" name="service_availed[]" value="Scanning"> Scanning</label>
                    </div>
                </div>

                <!-- Lalabas lamang kung ang Printing o Photocopy ay naka-check -->
                <div class="form-group" id="printedPagesGroup" style="display: none;">
                    <label>Number of Printed Pages</label>
                    <input type="number" name="printed_pages" id="printed_pages_input" class="form-control" value="0" min="0">
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn" style="background:#e2e8f0; color:#475569; flex:1;" onclick="document.getElementById('timeoutModal').close()">Cancel</button>
                    <button type="submit" name="submit_time_out" class="btn btn-primary" style="flex:1;">Save Time Out</button>
                </div>
            </form>
        </div>
    </dialog>

    <!-- IMAGE VIEWER MODAL -->
    <dialog id="imageModal" style="max-width: 500px; text-align: center; padding: 20px;">
        <div class="modal-header" style="border: none; padding: 0 0 10px 0;">
            <span>Client Photo Preview</span>
            <button type="button" style="background:none; border:none; font-size:1.2rem; cursor:pointer;" onclick="document.getElementById('imageModal').close()">✕</button>
        </div>
        <div class="modal-body" style="padding: 0;">
            <img id="modalFullImage" src="" alt="Full Size Photo" style="width: 100%; max-height: 400px; object-fit: contain; border-radius: 8px;">
        </div>
    </dialog>

    <script>
        let videoStream = null;

        function startCamera() {
            const video = document.getElementById('webcam');
            navigator.mediaDevices.getUserMedia({ video: true })
                .then(stream => {
                    videoStream = stream;
                    video.srcObject = stream;
                    video.style.display = 'block';
                    document.getElementById('photoPreview').style.display = 'none';
                })
                .catch(err => {
                    alert("Hindi ma-access ang camera. Siguraduhing nakabukas ang pahintulot.");
                });
        }

        function capturePhoto() {
            const video = document.getElementById('webcam');
            const canvas = document.getElementById('canvas');
            const preview = document.getElementById('photoPreview');
            const photoInput = document.getElementById('student_photo_input');

            if (!video.srcObject) {
                alert("I-click muna ang 'Start Camera'.");
                return;
            }

            const context = canvas.getContext('2d');
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            const dataURL = canvas.toDataURL('image/png');

            preview.src = dataURL;
            preview.style.display = 'block';
            video.style.display = 'none';
            photoInput.value = dataURL;

            if (videoStream) {
                videoStream.getTracks().forEach(track => track.stop());
            }
        }

        function retakePhoto() {
            document.getElementById('photoPreview').style.display = 'none';
            document.getElementById('student_photo_input').value = '';
            startCamera();
        }

        function setCurrentTime(elementId) {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            document.getElementById(elementId).value = `${hours}:${minutes}`;
        }

        function setCurrentTimeout() {
            setCurrentTime('timeout_input');
            calculateLengthOfStay();
        }

        function calculateLengthOfStay() {
            const timeInStr = document.getElementById('modal_time_in').value;
            const timeOutStr = document.getElementById('timeout_input').value;

            if (!timeInStr || !timeOutStr) return;

            const [inHours, inMinutes] = timeInStr.split(':').map(Number);
            const [outHours, outMinutes] = timeOutStr.split(':').map(Number);

            let inTotalMins = inHours * 60 + inMinutes;
            let outTotalMins = outHours * 60 + outMinutes;

            let diffMins = outTotalMins - inTotalMins;
            if (diffMins < 0) diffMins += 24 * 60;

            const hours = Math.floor(diffMins / 60);
            const minutes = diffMins % 60;

            let resultText = "";
            if (hours > 0) resultText += `${hours} hr${hours > 1 ? 's' : ''} `;
            if (minutes > 0 || hours === 0) resultText += `${minutes} min${minutes > 1 ? 's' : ''}`;

            document.getElementById('length_of_stay_input').value = resultText.trim();
        }

        function togglePrintedPages() {
            const checkboxes = document.querySelectorAll('#timeoutModal input[name="service_availed[]"]');
            let show = false;
            checkboxes.forEach(cb => {
                if ((cb.value === 'Printing' || cb.value === 'Photocopy') && cb.checked) {
                    show = true;
                }
            });

            const group = document.getElementById('printedPagesGroup');
            const inputField = document.getElementById('printed_pages_input');
            if (show) {
                group.style.display = 'block';
                inputField.required = true;
            } else {
                group.style.display = 'none';
                inputField.required = false;
                inputField.value = '0';
            }
        }

        function checkAndFillClient(inputObj) {
            const val = inputObj.value;
            const datalist = document.getElementById('repeatClientsList');
            for (let option of datalist.options) {
                if (option.value === val) {
                    document.getElementById('student_name').value = option.value;
                    document.getElementById('age').value = option.getAttribute('data-age');
                    document.getElementById('sex').value = option.getAttribute('data-sex');
                    document.getElementById('barangay').value = option.getAttribute('data-barangay');
                    document.getElementById('sector').value = option.getAttribute('data-sector');
                    
                    const savedPhoto = option.getAttribute('data-photo');
                    if (savedPhoto) {
                        document.getElementById('photoPreview').src = savedPhoto;
                        document.getElementById('photoPreview').style.display = 'block';
                        document.getElementById('webcam').style.display = 'none';
                        document.getElementById('student_photo_input').value = savedPhoto;
                    } else {
                        document.getElementById('photoPreview').style.display = 'none';
                        document.getElementById('student_photo_input').value = '';
                    }
                    break;
                }
            }
        }

        function viewClientHistory(clientName) {
            document.getElementById('historyModalTitle').innerText = "Visit History: " + clientName;
            document.getElementById('historyModalContent').innerHTML = "Loading...";
            document.getElementById('historyModal').showModal();

            fetch('sat_students.php?get_history=1&client_name=' + encodeURIComponent(clientName))
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) {
                        document.getElementById('historyModalContent').innerHTML = "<p style='color:#64748b; text-align:center;'>Walang nakitang kasaysayan ng pagbisita.</p>";
                        return;
                    }

                    let html = `<table class="data-table">
                        <thead>
                            <tr>
                                <th>Date Registered</th>
                                <th>Time In / Out</th>
                                <th>Service Availed</th>
                                <th>Length of Stay</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>`;

                    data.forEach(row => {
                        let timeOutText = row.time_out ? new Date('1970-01-01T' + row.time_out + 'Z').toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '(In-Session)';
                        let timeInText = new Date('1970-01-01T' + row.time_in + 'Z').toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                        html += `<tr>
                            <td>${row.date_registered}</td>
                            <td><strong>${timeInText}</strong> to ${timeOutText}</td>
                            <td><span class="badge badge-enrolled">${row.service_availed || '-'}</span></td>
                            <td>${row.length_of_stay || '-'}</td>
                            <td><span style="font-weight:bold; color:${row.status === 'Completed' ? '#16a34a' : '#f97316'};">${row.status}</span></td>
                        </tr>`;
                    });

                    html += `</tbody></table>`;
                    document.getElementById('historyModalContent').innerHTML = html;
                })
                .catch(err => {
                    document.getElementById('historyModalContent').innerHTML = "<p style='color:#ef4444; text-align:center;'>May naganap na error sa pagkuha ng history.</p>";
                });
        }

        function openTimeoutModal(id, name, timeIn) {
            document.getElementById('timeout_record_id').value = id;
            document.getElementById('modal_time_in').value = timeIn;
            document.getElementById('timeout_client_name').innerText = "Client: " + name;
            document.getElementById('timeout_input').value = "";
            document.getElementById('length_of_stay_input').value = "";
            
            const checkboxes = document.querySelectorAll('#timeoutModal input[name="service_availed[]"]');
            checkboxes.forEach(cb => cb.checked = false);
            document.getElementById('printedPagesGroup').style.display = 'none';
            document.getElementById('printed_pages_input').value = '0';

            document.getElementById('timeoutModal').showModal();
        }

        function openImageModal(imageSrc) {
            document.getElementById('modalFullImage').src = imageSrc;
            document.getElementById('imageModal').showModal();
        }

        <?php if ($active_tab == 'dashboard'): ?>
        window.addEventListener('DOMContentLoaded', () => {
            const ctxBar = document.getElementById('totalUsersBar').getContext('2d');
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: ['Unique Individuals', 'Repeat Clientele'],
                    datasets: [{
                        data: [<?php echo $unique_individuals_count; ?>, <?php echo $repeat_clientele_count; ?>],
                        backgroundColor: ['#3b82f6', '#ef4444'],
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: { legend: { display: false } }
                }
            });

            const ctxSex = document.getElementById('sexPieChart').getContext('2d');
            new Chart(ctxSex, {
                type: 'pie',
                data: {
                    labels: ['Male', 'Female', 'Prefer Not to Say'],
                    datasets: [{
                        data: [<?php echo $sex_counts['Male']; ?>, <?php echo $sex_counts['Female']; ?>, <?php echo $sex_counts['Prefer Not to Say']; ?>],
                        backgroundColor: ['#3b82f6', '#ef4444', '#eab308']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            const ctxLoc = document.getElementById('locationPieChart').getContext('2d');
            new Chart(ctxLoc, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode(array_keys($location_counts)); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_values($location_counts)); ?>,
                        backgroundColor: ['#10b981', '#f97316', '#3b82f6', '#8b5cf6', '#ec4899', '#64748b']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            const ctxAge = document.getElementById('ageBarChart').getContext('2d');
            new Chart(ctxAge, {
                type: 'bar',
                data: {
                    labels: Object.keys(<?php echo json_encode($age_brackets); ?>),
                    datasets: [{
                        data: Object.values(<?php echo json_encode($age_brackets); ?>),
                        backgroundColor: '#3b82f6',
                        borderRadius: 6
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
            });

            const ctxSrv = document.getElementById('servicesBarChart').getContext('2d');
            new Chart(ctxSrv, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_keys($services_counts)); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_values($services_counts)); ?>,
                        backgroundColor: '#10b981',
                        borderRadius: 6
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
            });

            const ctxSec = document.getElementById('sectorsBarChart').getContext('2d');
            new Chart(ctxSec, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_keys($sectors_counts)); ?>,
                    datasets: [{
                        data: Object.values(<?php echo json_encode($sectors_counts); ?>),
                        backgroundColor: '#8b5cf6',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: { legend: { display: false } }
                }
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>