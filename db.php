<?php
$host = 'sql300.infinityfree.com';
$dbname = 'if0_42760649_taytay_training_db';
$username = 'if0_42760649';
$password = 'Cydneng09'; // Ang iyong hosting password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>