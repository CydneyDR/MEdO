<?php
require 'db.php';

// Kunin ang office type mula sa URL (e.g., get_events.php?office=STTI)
$office = isset($_GET['office']) ? $_GET['office'] : 'STTI';

// I-filter ang query base sa office_type
$stmt = $pdo->prepare("SELECT id, title, event_date as start, start_time, description FROM bookings WHERE office_type = :office");
$stmt->execute(['office' => $office]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// I-format ang start date at time para sa FullCalendar
$formatted_events = [];
foreach ($events as $event) {
    $formatted_events[] = [
        'id' => $event['id'],
        'title' => $event['title'],
        'start' => $event['start'] . 'T' . $event['start_time'], // Pinagsama ang Date at Time
        'extendedProps' => [
            'description' => $event['description']
        ]
    ];
}

echo json_encode($formatted_events);
?>
