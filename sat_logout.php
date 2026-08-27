<?php
session_start();
session_unset();
session_destroy();
header("Location: sat_login.php");
exit;
?>
