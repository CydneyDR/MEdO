<?php
require 'db.php';
require_once 'email_routing.php';

// Kunin ang lahat ng routing papers na hindi pa 'Approved' o 'Retrieved'
$stmt = $pdo->query("SELECT * FROM routing_papers WHERE status NOT IN ('Approved', 'Retrieved')");
$papers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$currentDate = new DateTime();

foreach ($papers as $paper) {
    // Kunin ang huling petsa ng galaw o date_routed kung kailan huling na-update
    $lastRoutedDate = new DateTime($paper['date_routed']);
    $interval = $lastRoutedDate->diff($currentDate);
    $daysPassed = $interval->days;

    // Kung umabot o lumagpas na ng 5 araw
    if ($daysPassed >= 5) {
        // Email kung saan ipapadala ang alert (maaari mong baguhin o kunin sa database)
        $adminEmail = 'Smiletech4edLab@taytayrizal.gov.ph';

        // I-send ang modern blue notification email
        sendEmailRouting(
            $adminEmail,
            $paper['document_name'],
            $paper['assigned_to'],
            $paper['status'],
            $daysPassed,
            $paper['routed_by']
        );
    }
}
echo "Routing delay check completed successfully.";
?>