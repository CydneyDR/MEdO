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

// 1. TOTAL USERS / LOGS
$total_users_query = $pdo->prepare("SELECT COUNT(*) FROM satellite_students WHERE satellite_id = ?");
$total_users_query->execute([$sat_id]);
$total_users = $total_users_query->fetchColumn();

// 2. SEX DISAGGREGATED DATA
$sex_query = $pdo->prepare("SELECT sex, COUNT(*) as count FROM satellite_students WHERE satellite_id = ? GROUP BY sex");
$sex_query->execute([$sat_id]);
$sex_data = $sex_query->fetchAll(PDO::FETCH_KEY_PAIR); // ['Male' => X, 'Female' => Y]
$male_count = $sex_data['Male'] ?? 0;
$female_count = $sex_data['Female'] ?? 0;

// 3. SERVICES AVAILED BREAKDOWN
$services_query = $pdo->prepare("SELECT service_availed, COUNT(*) as count FROM satellite_students WHERE satellite_id = ? GROUP BY service_availed");
$services_query->execute([$sat_id]);
$services_data = $services_query->fetchAll(PDO::FETCH_ASSOC);

// 4. SECTORS BREAKDOWN
$sectors_query = $pdo->prepare("SELECT sector, COUNT(*) as count FROM satellite_students WHERE satellite_id = ? GROUP BY sector");
$sectors_query->execute([$sat_id]);
$sectors_data = $sectors_query->fetchAll(PDO::FETCH_ASSOC);

// 5. BARANGAY BREAKDOWN
$barangay_query = $pdo->prepare("SELECT barangay, COUNT(*) as count FROM satellite_students WHERE satellite_id = ? GROUP BY barangay");
$barangay_query->execute([$sat_id]);
$barangay_data = $barangay_query->fetchAll(PDO::FETCH_ASSOC);

// 6. REPEAT CLIENTELE VS UNIQUE
$unique_query = $pdo->prepare("SELECT COUNT(DISTINCT student_name) FROM satellite_students WHERE satellite_id = ?");
$unique_query->execute([$sat_id]);
$unique_clients = $unique_query->fetchColumn();
$repeat_clients = $total_users - $unique_clients;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - SMILE IT LAB</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            margin: 0;
            color: #334155;
            display: flex;
            min-height: 100vh;
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

        /* MAIN CONTENT */
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

        /* DASHBOARD GRID CARDS */
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
        }

        .card h4 {
            margin: 0 0 15px 0;
            font-size: 1rem;
            color: #475569;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 8px;
            text-transform: uppercase;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #2563eb;
            margin: 0;
        }

        .data-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .data-list li {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f8fafc;
            font-size: 0.95rem;
        }

        .data-list li span:last-child {
            font-weight: 700;
            color: #0f172a;
        }
    </style>
</head>

<body>

    <!-- DARK SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">
                <svg width="24" height="24" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                </svg>
            </div>
            <div>
                <span>Menu Portal</span>
                <h3>SMILE IT LAB</h3>
            </div>
        </div>

        <div class="sidebar-menu">
            <a href="sat_index.php" class="menu-item"><svg width="18" height="18" fill="none" stroke="currentColor"
                    stroke-width="2" viewBox="0 0 24 24">
                    <path
                        d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                    </path>
                </svg>Supplies & Requisition</a>
            <a href="sat_maintenance.php" class="menu-item"><svg width="18" height="18" fill="none"
                    stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path
                        d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z">
                    </path>
                </svg>Maintenance & Repair</a>
            <a href="sat_students.php" class="menu-item"><svg width="18" height="18" fill="none" stroke="currentColor"
                    stroke-width="2" viewBox="0 0 24 24">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                </svg>Student Registration</a>
            <a href="sat_analytics.php" class="menu-item active"><svg width="18" height="18" fill="none"
                    stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M18 20V10M12 20V4M6 20v-6"></path>
                </svg>Data Visualization Dashboard</a>
            <a href="sat_calendar.php" class="menu-item"><svg width="18" height="18" fill="none" stroke="currentColor"
                    stroke-width="2" viewBox="0 0 24 24">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                </svg>Event Calendar</a>
        </div>

        <div class="sidebar-footer">
            <a href="sat_logout.php" class="btn-logout">Log Out</a>
        </div>
    </div>

    <!-- TOP NAVBAR -->
    <div class="top-navbar">
        <div class="school-name">
            <?php echo htmlspecialchars($sat_name); ?> - Analytics Dashboard
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="page-header">Data Visualization & Analytics Summary</div>

        <div class="analytics-grid">
            <!-- TOTAL USERS -->
            <div class="card">
                <h4>Total Users / Logs</h4>
                <p class="stat-number">
                    <?php echo $total_users; ?>
                </p>
            </div>

            <!-- UNIQUE VS REPEAT -->
            <div class="card">
                <h4>Client Demographics</h4>
                <ul class="data-list">
                    <li><span>New / Unique Individuals:</span> <span>
                            <?php echo $unique_clients; ?>
                        </span></li>
                    <li><span>Repeat Clientele:</span> <span>
                            <?php echo $repeat_clients; ?>
                        </span></li>
                </ul>
            </div>

            <!-- SEX DISAGGREGATED -->
            <div class="card">
                <h4>Sex-Disaggregated Data</h4>
                <ul class="data-list">
                    <li><span>Male:</span> <span>
                            <?php echo $male_count; ?>
                        </span></li>
                    <li><span>Female:</span> <span>
                            <?php echo $female_count; ?>
                        </span></li>
                </ul>
            </div>
        </div>

        <div class="analytics-grid">
            <!-- SERVICES AVAILED -->
            <div class="card">
                <h4>Services Availed</h4>
                <ul class="data-list">
                    <?php if (count($services_data) > 0): ?>
                        <?php foreach ($services_data as $srv): ?>
                            <li><span>
                                    <?php echo htmlspecialchars($srv['service_availed']); ?>
                                </span> <span>
                                    <?php echo $srv['count']; ?>
                                </span></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><span>No data yet</span></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- SECTORS -->
            <div class="card">
                <h4>Sectors Breakdown</h4>
                <ul class="data-list">
                    <?php if (count($sectors_data) > 0): ?>
                        <?php foreach ($sectors_data as $sec): ?>
                            <li><span>
                                    <?php echo htmlspecialchars($sec['sector']); ?>
                                </span> <span>
                                    <?php echo $sec['count']; ?>
                                </span></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><span>No data yet</span></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- LOCATION / BARANGAY -->
            <div class="card">
                <h4>Location / Barangay</h4>
                <ul class="data-list">
                    <?php if (count($barangay_data) > 0): ?>
                        <?php foreach ($barangay_data as $brg): ?>
                            <li><span>
                                    <?php echo htmlspecialchars($brg['barangay']); ?>
                                </span> <span>
                                    <?php echo $brg['count']; ?>
                                </span></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><span>No data yet</span></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

    </div>
</body>

</html>