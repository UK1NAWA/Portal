<?php
include 'db.php';
include 'session_config.php';

// Auth FIRST — before any DB queries
if(!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'teacher'){
    header("Location: login.html");
    exit();
}

$slot_query = $conn->query("SELECT * FROM time_slots");

if(!isset($_GET['section'])){
    die("No section selected");
}

$section = $_GET['section'];

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $day = $_POST['day'];
    $time_slot_id = $_POST['time_slot_id'];
    $subject = $_POST['subject'];
    $instructor = $_POST['instructor'];
    $room = $_POST['room'];

    $stmt = $conn->prepare(
        "INSERT INTO section_schedules
        (section, day, time_slot_id, subject, instructor, room)
        VALUES (?,?,?,?,?,?)"
    );

    $stmt->bind_param(
        "ssisss",
        $section,
        $day,
        $time_slot_id,
        $subject,
        $instructor,
        $room
    );

    $stmt->execute();
}

if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM section_schedules WHERE id=$id");
}

$stmt2 = $conn->prepare("SELECT * FROM section_schedules WHERE section=? ORDER BY day, time_slot_id");
$stmt2->bind_param("s", $section);
$stmt2->execute();
$result = $stmt2->get_result();

if(!$result){
    die($conn->error);
}

?>
<!DOCTYPE html>
<html>
<head>
<title>Edit Section Schedule</title>

<style>
body{font-family:Arial}
table{border-collapse:collapse;width:100%}
td,th{border:1px solid #ccc;padding:8px}
th{background:#eee}
input, select{padding:6px;margin:4px}
button{padding:6px 12px}
</style>

</head>
<body>

<h2>Editing Schedule for Section:
    <?php echo htmlspecialchars($section); ?>
</h2>

<h3>Add Class</h3>

<form method="POST">

<select name="day" required>
    <option value="">Day</option>
    <option>Monday</option>
    <option>Tuesday</option>
    <option>Wednesday</option>
    <option>Thursday</option>
    <option>Friday</option>
</select>

<select name="time_slot_id" required>
<option value="">Select Time</option>

<?php
while($slot = $slot_query->fetch_assoc()){
    echo "<option value='{$slot['id']}'>{$slot['label']}</option>";
}
?>
</select>
<input name="subject" placeholder="Subject" required>
<input name="instructor" placeholder="Instructor" required>
<input name="room" placeholder="Room" required>

<button>Add</button>

</form>

<h3>Current Schedule</h3>

<table>
<tr>
    <th>ID</th>
    <th>Day</th>
    <th>Period</th>
    <th>Subject</th>
    <th>Instructor</th>
    <th>Room</th>
    <th>Action</th>
</tr>

<?php
while($row = $result->fetch_assoc()){
    echo "<tr>
        <td>{$row['id']}</td>
        <td>{$row['day']}</td>
        <td>{$row['time_slot_id']}</td>
        <td>{$row['subject']}</td>
        <td>{$row['instructor']}</td>
        <td>{$row['room']}</td>
        <td>
            <a href='edit_section_schedule.php?section=$section&delete={$row['id']}'
            onclick='return confirm(\"Delete?\");'>
            Delete
            </a>
        </td>
    </tr>";
}
?>

</table>

<br>
<a href="teacher_portal.php">⬅ Back</a>

</body>
</html>
