<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.html");
    exit();
}

$fullname = trim($_POST['fullname'] ?? '');
$email    = trim($_POST['email']    ?? '');
$username = trim($_POST['username'] ?? '');
$password = $_POST['password']      ?? '';
$confirm  = $_POST['confirm']       ?? '';
// Strip all non-digits so copy-pasting "100 362 900 001" or "100-362-900-001" still works
$lrn          = preg_replace('/\D/', '', trim($_POST['lrn'] ?? ''));
$student_id_no = trim($_POST['student_id_no'] ?? '');

$errors = [];

if ($fullname === '')                              $errors[] = "Full name is required.";
if ($student_id_no === '')                         $errors[] = "Student ID Number is required.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL))   $errors[] = "Invalid email address.";
if (strlen($username) < 3)                        $errors[] = "Username must be at least 3 characters.";
if (strlen($password) < 8)                        $errors[] = "Password must be at least 8 characters.";
if ($password !== $confirm)                       $errors[] = "Passwords do not match.";

// LRN is optional at registration — but if provided it must be exactly 12 digits
if ($lrn !== '' && strlen($lrn) !== 12) {
    $errors[] = "LRN must be exactly 12 digits (you entered " . strlen($lrn) . ").";
}

if (empty($errors)) {
    $chk1 = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $chk1->bind_param("s", $username);
    $chk1->execute();
    $chk1->store_result();

    $chk2 = $conn->prepare("SELECT id FROM pending_registrations WHERE username = ? AND status = 'pending'");
    $chk2->bind_param("s", $username);
    $chk2->execute();
    $chk2->store_result();

    if ($chk1->num_rows > 0) {
        $errors[] = "Username is already taken.";
    } elseif ($chk2->num_rows > 0) {
        $errors[] = "A registration with this username is already pending approval.";
    }

    // Check student ID not already taken
    if (empty($errors)) {
        $chk4 = $conn->prepare("SELECT id FROM users WHERE student_id_no = ?");
        $chk4->bind_param("s", $student_id_no);
        $chk4->execute();
        $chk4->store_result();
        if ($chk4->num_rows > 0) $errors[] = "This Student ID is already registered to another account.";

        $chk5 = $conn->prepare("SELECT id FROM pending_registrations WHERE student_id_no = ? AND status = 'pending'");
        $chk5->bind_param("s", $student_id_no);
        $chk5->execute();
        $chk5->store_result();
        if ($chk5->num_rows > 0) $errors[] = "This Student ID already has a pending registration.";
    }

    // If LRN was provided, make sure it isn't already in use
    if (empty($errors) && $lrn !== '') {
        $chk3 = $conn->prepare("SELECT id FROM users WHERE lrn = ?");
        $chk3->bind_param("s", $lrn);
        $chk3->execute();
        $chk3->store_result();
        if ($chk3->num_rows > 0) {
            $errors[] = "This LRN is already registered to another account.";
        }
    }
}

if (empty($errors)) {
    $hashed  = password_hash($password, PASSWORD_DEFAULT);
    $lrn_val = $lrn !== '' ? $lrn : null;

    $stmt = $conn->prepare(
        "INSERT INTO pending_registrations (fullname, email, username, password, lrn, student_id_no) VALUES (?,?,?,?,?,?)"
    );
    $stmt->bind_param("ssssss", $fullname, $email, $username, $hashed, $lrn_val, $student_id_no);

    if ($stmt->execute()) {
        echo "<script>
            sessionStorage.setItem('regSuccess', 'Registration submitted! An admin will review your request. You will be able to log in once approved.');
            window.location.href = 'index.html';
        </script>";
        exit();
    } else {
        $errors[] = "Registration failed. Please try again.";
    }
}

$err_str = implode(" ", $errors);
echo "<script>sessionStorage.setItem('regError', " . json_encode($err_str) . ");window.location.href='index.html';</script>";
exit();
