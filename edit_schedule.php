<?php
include 'db.php';

if(!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'teacher'){
    header("Location: login.html");
    exit();
}

if(!isset($_GET['id'])){
    die("No student selected");
}
$student_id = (int)$_GET['id'];


// SAVE new schedule row
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $day = $_POST['day'];
    $period = $_POST['period'];
    $subject = $_POST['subject'];
    $instructor = $_POST['instructor'];
    $room = $_POST['room'];
    

    $stmt = $conn->prepare(
        "INSERT INTO schedules (student_id, day, period, subject, instructor, room) VALUES (?,?,?,?,?,?)"
    );
  $stmt->bind_param("issss", $student_id, $day, $period, $subject, $instructor, $room);
    $stmt->execute();
}

// DELETE a schedule row
if(isset($_GET['delete_id'])){
    $delete_id = $_GET['delete_id'];
    $delete_id = (int)$_GET['delete_id'];
    $conn->query("DELETE FROM schedules WHERE id=$delete_id");

}

// LOAD student info and schedules
$student_result = $conn->query("SELECT fullname, username, email FROM users WHERE id=$student_id");
$student = $student_result->fetch_assoc();

$schedule_result = $conn->query(
    "SELECT id, time, subject, instructor, room
     FROM schedules
     WHERE student_id=$student_id"
);
if(!$schedule_result){
    die($conn->error);
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Schedule - <?php echo htmlspecialchars($student['fullname']); ?></title>
    <style>
        input{padding:6px;margin:4px;}
        table{border-collapse:collapse;width:100%;}
        td, th{border:1px solid #ccc;padding:6px;}
        th{background:#eee;}
        a {text-decoration:none;color:#007bff;}
        a:hover {text-decoration:underline;}
        button{padding:6px 12px;}
    </style>
</head>
<body>

<h2>Edit Schedule for <?php echo htmlspecialchars($student['fullname']); ?></h2>
<p>Username: <?php echo htmlspecialchars($student['username']); ?> | Email: <?php echo htmlspecialchars($student['email']); ?></p>

<h3>Add New Row</h3>
<form method="POST">

<select name="day" required>
    <option value="">Day</option>
    <option>Monday</option>
    <option>Tuesday</option>
    <option>Wednesday</option>
    <option>Thursday</option>
    <option>Friday</option>
</select>

<select name="period" required>
    <option value="">Period</option>
    <option value="1">1</option>
    <option value="2">2</option>
    <option value="3">3</option>
    <option value="4">4</option>
    <option value="5">5</option>
    <option value="6">6</option>
</select>

<input name="subject" placeholder="Subject" required>
<input name="instructor" placeholder="Instructor" required>
<input name="room" placeholder="Room" required>

<button type="submit">Add Row</button>
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
while($row = $schedule_result->fetch_assoc()){
    echo "<tr>
        <td>{$row['id']}</td>
        <td>{$row['time']}</td>
        <td>{$row['subject']}</td>
        <td>{$row['instructor']}</td>
        <td>{$row['room']}</td>
        <td>
            <a href='edit_schedule_row.php?id={$row['id']}'>Edit</a> |
            <a href='edit_schedule.php?id={$student_id}&delete_id={$row['id']}' onclick='return confirm(\"Are you sure?\");'>Delete</a>
        </td>
    </tr>";
}
?>
</table>

<br>
<a href="teacher_portal.php">⬅ Back to Portal</a>

</body>
</html>
