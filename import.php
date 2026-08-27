<?php
require_once 'db.php'; // Kokonekta ito sa Railway database mo gamit ang db.php

$sqlFile = 'taytay_training_db.sql';

if (!file_exists($sqlFile)) {
    die("Wala ang taytay_training_db.sql file!");
}

$sql = file_get_contents($sqlFile);

try {
    // I-execute ang buong SQL file sa Railway database
    $pdo->exec($sql);
    echo "<h2>Tagumpay! Na-import na ang iyong database sa Railway!</h2>";
} catch (PDOException $e) {
    echo "Error sa pag-import: " . $e->getMessage();
}
?>