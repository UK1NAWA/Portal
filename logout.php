<?php
include 'db.php';
include_once __DIR__ . '/audit.php';

audit_log($conn, 'LOGOUT', $_SESSION['username'] ?? 'unknown', '');
session_destroy();
header("Location: login.html");
exit();
?>
