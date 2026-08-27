<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];

    $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = :id");
    $result = $stmt->execute(['id' => $id]);

    if ($result) {
        echo "success";
    } else {
        echo "error";
    }
}
?>
