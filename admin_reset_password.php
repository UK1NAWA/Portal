<?php
include 'db.php';

header('Content-Type: application/json');

// Admin only
if (!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit();
}

$target_id  = (int)($_POST['user_id'] ?? 0);
$temp_pw    = trim($_POST['temp_password'] ?? '');

if ($target_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user.']);
    exit();
}

// Admin cannot reset their own password through this tool (use change_password.php)
if ($target_id === (int)$_SESSION['id']) {
    echo json_encode(['success' => false, 'message' => 'Use the Change Password option to update your own password.']);
    exit();
}

// Validate temp password
if (strlen($temp_pw) < 8) {
    echo json_encode(['success' => false, 'message' => 'Temporary password must be at least 8 characters.']);
    exit();
}

// Verify target user exists
$chk = $conn->prepare("SELECT id, fullname, role FROM users WHERE id = ?");
$chk->bind_param("i", $target_id);
$chk->execute();
$target = $chk->get_result()->fetch_assoc();

if (!$target) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit();
}

// Hash and save, set force_password_change = 1
$hashed = password_hash($temp_pw, PASSWORD_DEFAULT);
$upd = $conn->prepare("UPDATE users SET password = ?, force_password_change = 1 WHERE id = ?");
$upd->bind_param("si", $hashed, $target_id);

if ($upd->execute()) {
    // Log the reset action
    $admin_name = $_SESSION['fullname'];
    $conn->prepare("
        INSERT INTO password_resets (user_id, token_hash, expires_at, used)
        VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR), 1)
    ")->bind_param("is", $target_id, $admin_name) && true; // token_hash stores who reset it

    echo json_encode([
        'success' => true,
        'message' => "Password reset for {$target['fullname']}. They will be required to change it on next login."
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
}
