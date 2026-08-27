<?php
session_start();
if (!isset($_SESSION['sat_logged_in'])) {
    header("Location: sat_login.php");
    exit;
}

$sat_id = $_SESSION['sat_id'];
$sat_name = $_SESSION['sat_name'];

// Para malaman kung anong page ang naka-active sa sidebar
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMILE IT LAB - <?php echo htmlspecialchars($sat_name); ?></title>
    <style>
        /* MAIN LAYOUT */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            margin: 0;
            color: #334155;
            display: flex;
            min-height: 100vh;
        }

        /* ================== DARK SIDEBAR ================== */
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

        /* SIDEBAR FOOTER & CLOCK */
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid #334155;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .sidebar-clock {
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid #334155;
            padding: 10px 12px;
            border-radius: 8px;
            text-align: center;
        }

        #sidebar-time {
            font-size: 0.95rem;
            font-weight: 700;
            color: #f8fafc;
            letter-spacing: 0.5px;
        }

        #sidebar-date {
            font-size: 0.7rem;
            color: #94a3b8;
            margin-top: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        /* ================== TOP NAVBAR ================== */
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

        /* ================== MAIN CONTENT AREA ================== */
        .main-content {
            margin-left: 260px;
            margin-top: 70px;
            flex: 1;
            padding: 40px;
            width: calc(100% - 260px);
            box-sizing: border-box;
        }

        .page-header {
            font-size: 1.8rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 24px;
        }

        /* LAYOUT ELEMENTS */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 30px;
        }

        @media (max-width: 992px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }

        .modern-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
            padding: 24px;
            margin-bottom: 24px;
            transition: 0.2s ease;
        }

        .modern-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
        }

        .card-title {
            margin-top: 0;
            color: #0f172a;
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 12px;
        }

        /* TABLES & BADGES */
        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            max-height: 350px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 500px;
        }

        .data-table th {
            background: #f8fafc;
            padding: 12px 16px;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            border-bottom: 2px solid #e2e8f0;
        }

        .data-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            font-size: 0.9rem;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
        }

        .badge-pending {
            background: #ffedd5;
            color: #ea580c;
        }

        .badge-approved {
            background: #dcfce7;
            color: #16a34a;
        }

        .badge-partial {
            background: #fef08a;
            color: #b45309;
        }

        .badge-declined {
            background: #fee2e2;
            color: #b91c1c;
        }

        .badge-inprogress {
            background: #fef3c7;
            color: #d97706;
        }

        .badge-resolved {
            background: #dbeafe;
            color: #2563eb;
        }

        .badge-enrolled {
            background: #ede9fe;
            color: #8b5cf6;
        }

        /* FORMS & BUTTONS */
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

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.95rem;
            box-sizing: border-box;
            background: #f8fafc;
            font-family: inherit;
            transition: 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn {
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-align: center;
            width: 100%;
            transition: 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(37, 99, 235, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #b91c1c);
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(239, 68, 68, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #8b5cf6, #6d28d9);
            color: white;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(139, 92, 246, 0.4);
        }

        .notes-box {
            background: #f8fafc;
            border-left: 3px solid #3b82f6;
            padding: 10px 14px;
            border-radius: 0 8px 8px 0;
            font-size: 0.85rem;
            color: #475569;
            font-style: italic;
            margin-top: 8px;
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
            <a href="sat_index.php" class="menu-item <?php echo ($current_page == 'sat_index.php') ? 'active' : ''; ?>">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" viewBox="0 0 24 24">
                    <path
                        d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                    </path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                </svg>
                Supplies & Requisition
            </a>
            <a href="sat_maintenance.php"
                class="menu-item <?php echo ($current_page == 'sat_maintenance.php') ? 'active' : ''; ?>">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" viewBox="0 0 24 24">
                    <path
                        d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z">
                    </path>
                </svg>
                Maintenance & Repair
            </a>
            <a href="sat_students.php"
                class="menu-item <?php echo ($current_page == 'sat_students.php') ? 'active' : ''; ?>">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Student Registration
            </a>
            <a href="sat_calendar.php"
                class="menu-item <?php echo ($current_page == 'sat_calendar.php') ? 'active' : ''; ?>">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" viewBox="0 0 24 24">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                Event Calendar
            </a>
        </div>

        <div class="sidebar-footer">
            <!-- SIDEBAR CLOCK WIDGET -->
            <div class="sidebar-clock">
                <div id="sidebar-time">--:--:-- --</div>
                <div id="sidebar-date">Loading...</div>
            </div>

            <a href="sat_logout.php" class="btn-logout">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Log Out
            </a>
        </div>
    </div>

    <!-- WHITE TOP NAVBAR (SCHOOL NAME ONLY) -->
    <div class="top-navbar">
        <div class="school-name">
            <svg width="22" height="22" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            <?php echo htmlspecialchars($sat_name); ?>
        </div>
    </div>

    <!-- SIDEBAR CLOCK SCRIPT -->
    <script>
        function updateSidebarClock() {
            const now = new Date();
            const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
            const dateOptions = { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' };

            document.getElementById('sidebar-time').textContent = now.toLocaleTimeString('en-US', timeOptions);
            document.getElementById('sidebar-date').textContent = now.toLocaleDateString('en-US', dateOptions);
        }
        setInterval(updateSidebarClock, 1000);
        updateSidebarClock(); // Initial call
    </script>

    <!-- MAIN CONTENT START -->
    <div class="main-content">