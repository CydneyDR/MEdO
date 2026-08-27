<?php
require 'db.php';
require_once 'email_calendar.php'; // Isama ang dedicated calendar email file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Kunin ang data mula sa AJAX/Fetch request
    $title = $_POST['title'];
    $date = $_POST['event_date'];
    $time = $_POST['start_time'];
    $desc = $_POST['description'];
    $office = $_POST['office_type']; // STTI or SMILE

    // I-insert sa database
    try {
        $stmt = $pdo->prepare("INSERT INTO bookings (title, event_date, start_time, description, office_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $date, $time, $desc, $office]);

        // --- MAGPADALA NG EMAIL NOTIFICATION ---
        // Palitan ito ng iyong totoong email address kung saan mo gustong matanggap ang alert
        $adminEmail = 'Smiletech4edLab@taytayrizal.gov.ph';

        // Tawagin ang function mula sa 'email_calendar.php'
        sendEmailCalendar($adminEmail, $title, $date, $time, $desc, $office);

        echo "success";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>