<?php
include 'db.php';

if(!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'teacher'){
    header("Location: login.html");
    exit();
}

$teacher_id = $_SESSION['id'];
$section    = trim($_GET['section'] ?? '');

// Get teacher's students for the section (optional filter)
if($section){
    $stmt = $conn->prepare("SELECT fullname FROM users WHERE role='student' AND section=? ORDER BY fullname");
    $stmt->bind_param("s", $section);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    // Get all students from teacher's sections
    $stmt = $conn->prepare("
        SELECT u.fullname, u.section
        FROM users u
        JOIN sections s ON s.section_name = u.section
        WHERE u.role='student' AND s.teacher_id=?
        ORDER BY u.section, u.fullname
    ");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="grade_template.csv"');

$out = fopen('php://output', 'w');

// Header row
fputcsv($out, ['Student Name', 'Subject', 'Grade', 'Remarks']);

// Sample rows — one per student, teacher fills in subject and grade
if(!empty($students)){
    foreach($students as $s){
        fputcsv($out, [$s['fullname'], '', '', '']);
    }
} else {
    // Blank sample rows if no students found
    fputcsv($out, ['Juan Dela Cruz', 'Mathematics', '88', 'Passed']);
    fputcsv($out, ['Maria Santos', 'Mathematics', '92', 'Passed']);
    fputcsv($out, ['Pedro Reyes', 'Mathematics', '74', 'Failed']);
}

fclose($out);
exit();
