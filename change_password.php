<?php
include 'db.php';

header('Content-Type: application/json');

// Must be logged in
if (!isset($_SESSION['loggedIn'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit();
}

$user_id     = (int)$_SESSION['id'];
$current_pw  = $_POST['current_password']  ?? '';
$new_pw      = $_POST['new_password']      ?? '';
$confirm_pw  = $_POST['confirm_password']  ?? '';

// ── Validate inputs ────────────────────────────────────────────────────────
if ($current_pw === '' || $new_pw === '' || $confirm_pw === '') {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit();
}

if (strlen($new_pw) < 8) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters.']);
    exit();
}

if ($new_pw !== $confirm_pw) {
    echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
    exit();
}

if ($new_pw === $current_pw) {
    echo json_encode(['success' => false, 'message' => 'New password must be different from your current password.']);
    exit();
}

// ── Verify current password ────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row || !password_verify($current_pw, $row['password'])) {
    // Small delay to slow down brute force on this endpoint
    usleep(400000);
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
    exit();
}

// ── Update password ────────────────────────────────────────────────────────
$new_hash = password_hash($new_pw, PASSWORD_DEFAULT);
$upd = $conn->prepare("UPDATE users SET password = ?, force_password_change = 0 WHERE id = ?");
$upd->bind_param("si", $new_hash, $user_id);

if ($upd->execute()) {
    // Regenerate session for security
    session_regenerate_id(true);
    echo json_encode(['success' => true, 'message' => 'Password changed successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update password. Please try again.']);
}
