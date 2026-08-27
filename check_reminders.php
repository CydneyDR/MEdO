<?php
require 'db.php';
require_once 'email_calendar.php';

$currentDate = date('Y-m-d');
$targetDate2Days = date('Y-m-d', strtotime('+2 days')); // Sakto 2 days from now

// 1. Kunin ang mga events na magaganap pagkalipas ng 2 araw
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE event_date = ?");
$stmt->execute([$targetDate2Days]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($events as $event) {
    // Ilagay ang email kung saan ise-send (o kung may email column ka sa bookings table, gamitin iyon)
    $recipientEmail = 'Smiletech4edLab@taytayrizal.gov.ph';

    // Magpadala ng 2-day reminder
    sendEmailReminder(
        $recipientEmail,
        $event['title'],
        $event['event_date'],
        $event['start_time'],
        '2days',
        $event['office_type']
    );
}

// Nota para sa 30 minutes reminder: 
// Karaniwan itong pinapatakbo gamit ang CRON Job sa server (tulad ng cPanel Cron o Windows Task Scheduler) 
// na binubuksan ang file na ito bawat minuto o bawat oras para kusang masuri ang oras ng event.
?>