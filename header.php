<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STTI Training Office System</title>
    <link rel="stylesheet" href="style.css">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Ensures the logos align nicely in a row */
        .logos {
            display: flex;
            justify-content: space-evenly;
            align-items: center;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="logos">
            <!-- Inline styles for uniform size and object-fit to prevent stretching -->
            <img src="logo/sttilogo.png" alt="STTI" style="width: 60px; height: 60px; object-fit: cover;"
                onerror="this.src='https://via.placeholder.com/60'">
            <img src="logo/logorizal.avif" alt="Taytay" style="width: 60px; height: 60px; object-fit: cover;"
                onerror="this.src='https://via.placeholder.com/60'">
            <!-- Added border-radius: 50% to make logo2 perfectly round -->
            <img src="logo/logo2.png" alt="Smile"
                style="width: 60px; height: 60px; object-fit: cover; border-radius: 50%;"
                onerror="this.src='https://via.placeholder.com/60'">
        </div>

        <nav style="display: flex; flex-direction: column;">
            <a href="index.php" class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Dashboard
                & Notes</a>
            <a href="calendar.php"
                class="nav-link <?php echo ($current_page == 'calendar.php') ? 'active' : ''; ?>">Event Calendar</a>
            <a href="staff.php" class="nav-link <?php echo ($current_page == 'staff.php') ? 'active' : ''; ?>">Staff
                Schedule</a>
            <a href="routing.php"
                class="nav-link <?php echo ($current_page == 'routing.php') ? 'active' : ''; ?>">Routing Papers</a>
            <a href="satellite.php"
                class="nav-link <?php echo ($current_page == 'satellite.php') ? 'active' : ''; ?>">Satellite
                Offices</a>
            <a href="inventory.php"
                class="nav-link <?php echo ($current_page == 'inventory.php') ? 'active' : ''; ?>">Inventory System</a>

            <!-- BAGONG DAGDAG: QA TRACKER LINK -->
            <a href="qa_tracker.php"
                class="nav-link <?php echo ($current_page == 'qa_tracker.php') ? 'active' : ''; ?>">QA Tracker</a>
        </nav>


        <div class="sidebar-bottom" style="margin-top: auto; padding: 20px;">
            <div class="clock-container">
                <div class="clock-time"><span id="hour-min">00:00</span><span id="sec-ampm">00 AM</span></div>
                <div class="clock-date" id="live-date">Loading...</div>
            </div>
            <a href="logout.php" class="logout-btn">Log Out Session</a>
        </div>
    </div>

    <div class="main-content">