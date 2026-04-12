<?php
// ============================================================
// session_config.php
// PURPOSE: Role-based session timeout
// Student : 2 days
// Teacher : 3 days
// Admin   : 3 days
//
// Idle timeout — the session expires if the user is inactive
// for this long. Active users who keep logging in stay alive.
// ============================================================

// Prevent browser from caching protected pages.
// This stops the back button from showing portal pages after logout.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");

$role = $_SESSION['role'] ?? 'student';

if ($role === 'student') {
    $timeout = 2 * 24 * 60 * 60;   // 2 days = 172800 seconds
} else {
    $timeout = 3 * 24 * 60 * 60;   // 3 days = 259200 seconds
}

if (isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] === true) {

    $last = $_SESSION['last_activity'] ?? null;

    if ($last !== null && (time() - $last) > $timeout) {
        session_unset();
        session_destroy();
        session_start();
        header("Location: login.html?reason=timeout");
        exit();
    }

    $_SESSION['last_activity'] = time();

    // Periodically clean expired sessions from DB.
    // Only runs ~1 in every 100 requests to avoid overhead.
    // FIXED: use a prepared statement instead of string interpolation.
    if (isset($conn) && rand(1, 100) === 1) {
        $min_expiry = time() - (2 * 24 * 60 * 60); // use student timeout (shortest) as floor
        $cleanup    = $conn->prepare("DELETE FROM php_sessions WHERE last_activity < ?");
        $cleanup->bind_param("i", $min_expiry);
        $cleanup->execute();
    }
}
