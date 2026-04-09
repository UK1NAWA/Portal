<?php
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedIn'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit();
}

$role      = $_SESSION['role'];
$viewer_id = (int)$_SESSION['id'];

// Determine whose profile is being updated
// Students can only update their own. Teachers/Admins can update anyone (by passing user_id).
if (in_array($role, ['teacher', 'admin']) && isset($_POST['user_id']) && (int)$_POST['user_id'] > 0) {
    $target_id = (int)$_POST['user_id'];
} else {
    $target_id = $viewer_id;
}

// Fetch current target user
$chk = $conn->prepare("SELECT * FROM users WHERE id = ?");
$chk->bind_param("i", $target_id);
$chk->execute();
$target = $chk->get_result()->fetch_assoc();

if (!$target) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit();
}

// Students cannot edit other users
if ($role === 'student' && $target_id !== $viewer_id) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit();
}

// --- Collect and sanitize inputs ---
$fullname = trim($_POST['fullname'] ?? '');
$email    = trim($_POST['email']    ?? '');
$contact  = trim($_POST['contact']  ?? '');

// Fields only teacher/admin can change
$username   = trim($_POST['username']    ?? '');
$section    = trim($_POST['section']     ?? '');
$student_id = trim($_POST['student_id']  ?? '');
$year_level = trim($_POST['year_level']  ?? '');
$strand     = trim($_POST['strand']      ?? '');

// Basic validation
if ($fullname === '' || $email === '') {
    echo json_encode(['success' => false, 'message' => 'Full name and email are required.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit();
}

// Build query depending on role
if (in_array($role, ['teacher', 'admin'])) {
    // Check username uniqueness if changed
    if ($username !== '' && $username !== $target['username']) {
        $uq = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $uq->bind_param("si", $username, $target_id);
        $uq->execute();
        $uq->store_result();
        if ($uq->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Username already taken.']);
            exit();
        }
    }

    $stmt = $conn->prepare("
        UPDATE users
        SET fullname=?, email=?, contact_number=?, username=?,
            section=?, student_id_no=?, year_level=?, strand=?
        WHERE id=?
    ");
    $stmt->bind_param(
        "ssssssssi",
        $fullname, $email, $contact, $username,
        $section, $student_id, $year_level, $strand,
        $target_id
    );
} else {
    // Student: only fullname, email, contact
    $stmt = $conn->prepare("
        UPDATE users SET fullname=?, email=?, contact_number=? WHERE id=?
    ");
    $stmt->bind_param("sssi", $fullname, $email, $contact, $target_id);
}

if ($stmt->execute()) {
    // Refresh session values if updating own profile
    if ($target_id === $viewer_id) {
        $_SESSION['fullname'] = $fullname;
        $_SESSION['email']    = $email;
    }
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}
