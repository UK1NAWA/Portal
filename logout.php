<?php
include 'db.php';
include_once __DIR__ . '/audit.php';

// Prevent browser from caching this response
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");

audit_log($conn, 'LOGOUT', $_SESSION['username'] ?? 'unknown', '');

// Fully destroy the session
$_SESSION = [];

// Delete the session cookie
if(ini_get("session.use_cookies")){
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
header("Location: login.html");
exit();
?>

