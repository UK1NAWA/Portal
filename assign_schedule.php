<?php
include 'db.php';
include 'session_config.php';

if(!isset($_SESSION['loggedIn']) || !in_array($_SESSION['role'], ['teacher','admin'])){
    header("Location: login.html");
   exit();
    exit();
}

if(!isset($_GET['section']) || !isset($_GET['day']) || !isset($_GET['time_slot_id'])){
    header("Location: teacher_portal.php");
   exit();
    exit();
}

$section      = $_GET['section'];
$day          = $_GET['day'];
$time_slot_id = (int)$_GET['time_slot_id'];

// Get time slot label
$ts = $conn->prepare("SELECT label FROM time_slots WHERE id=?");
$ts->bind_param("i", $time_slot_id);
$ts->execute();
$ts_row    = $ts->get_result()->fetch_assoc();
$time_label = $ts_row ? $ts_row['label'] : "Slot $time_slot_id";

// Check existing entry
$ex = $conn->prepare("SELECT * FROM section_schedules WHERE section=? AND day=? AND time_slot_id=?");
$ex->bind_param("ssi", $section, $day, $time_slot_id);
$ex->execute();
$existing = $ex->get_result()->fetch_assoc();

// Clear slot
if(isset($_GET['clear'])){
    $del = $conn->prepare("DELETE FROM section_schedules WHERE section=? AND day=? AND time_slot_id=?");
    $del->bind_param("ssi", $section, $day, $time_slot_id);
    $del->execute();
    header("Location: timetable.php?section=".urlencode($section));
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $subject    = trim($_POST['subject']);
    $instructor = trim($_POST['instructor']);
    $room       = trim($_POST['room']);
    $status = 'published';

    $ct = $conn->prepare("SELECT id FROM section_schedules WHERE instructor=? AND day=? AND time_slot_id=? AND section!=?");
    $ct->bind_param("ssis", $instructor, $day, $time_slot_id, $section);
    $ct->execute();
    $ct->store_result();

    if($ct->num_rows > 0){
        $error = "That teacher already has a class at this time on $day.";
    } else {

        $cr = $conn->prepare("SELECT id FROM section_schedules WHERE room=? AND day=? AND time_slot_id=? AND section!=?");
        $cr->bind_param("ssis", $room, $day, $time_slot_id, $section);
        $cr->execute();
        $cr->store_result();

        if($cr->num_rows > 0){
            $error = "Room $room is already occupied at this time on $day.";
        } else {
            $del = $conn->prepare("DELETE FROM section_schedules WHERE section=? AND day=? AND time_slot_id=?");
            $del->bind_param("ssi", $section, $day, $time_slot_id);
            $del->execute();

            $ins = $conn->prepare("INSERT INTO section_schedules (section,day,time_slot_id,subject,instructor,room,status) VALUES (?,?,?,?,?,?,?)");
            $ins->bind_param("ssissss", $section, $day, $time_slot_id, $subject, $instructor, $room, $status);
            $ins->execute();

            header("Location: timetable.php?section=".urlencode($section));
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assign Class</title>
<link rel="stylesheet" href="admin.css">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
body { justify-content: center; align-items: flex-start; padding: 60px 16px; }
.card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 32px;
    width: 100%;
    max-width: 440px;
}
.card h2 {
    font-family: 'Syne', sans-serif;
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 6px;
}
.meta-tags {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-bottom: 24px;
}
.meta-tag {
    font-size: 11px;
    background: var(--border);
    color: var(--muted);
    padding: 3px 10px;
    border-radius: 20px;
}
.form-group { margin-bottom: 16px; }
.form-group label {
    display: block;
    font-size: 12px;
    color: var(--muted);
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.form-group input,
.form-group select {
    width: 100%;
    background: var(--bg);
    border: 1px solid var(--border);
    color: var(--text);
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 14px;
    font-family: 'DM Sans', sans-serif;
    outline: none;
    box-sizing: border-box;
    transition: border-color 0.15s;
}
.form-group input:focus,
.form-group select:focus { border-color: var(--accent); }
.form-group select option { background: var(--surface); }
.btn-row { display: flex; gap: 10px; margin-top: 20px; }
.btn-save {
    background: var(--accent);
    color: #000;
    border: none;
    padding: 10px 0;
    border-radius: 8px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    flex: 1;
    font-family: 'DM Sans', sans-serif;
}
.btn-save:hover { opacity: 0.9; }
.btn-back {
    background: transparent;
    color: var(--muted);
    border: 1px solid var(--border);
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 13px;
    text-decoration: none;
    display: flex;
    align-items: center;
}
.btn-back:hover { color: var(--text); border-color: #444; }
.btn-clear {
    background: transparent;
    color: var(--accent3);
    border: 1px solid var(--border);
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 13px;
    text-decoration: none;
    display: flex;
    align-items: center;
    cursor: pointer;
}
.btn-clear:hover { border-color: var(--accent3); }
.error-box {
    background: rgba(226,92,92,0.1);
    border: 1px solid var(--accent3);
    color: var(--accent3);
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 13px;
    margin-bottom: 16px;
}
.existing-note {
    background: rgba(62,207,142,0.08);
    border: 1px solid rgba(62,207,142,0.2);
    color: var(--accent2);
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 12px;
    margin-bottom: 16px;
}
</style>
</head>
<body>
<div class="card">
    <h2><?php echo $existing ? 'Edit Class' : 'Assign Class'; ?></h2>
    <div class="meta-tags">
        <span class="meta-tag"><?php echo htmlspecialchars($section); ?></span>
        <span class="meta-tag"><?php echo htmlspecialchars($day); ?></span>
        <span class="meta-tag"><?php echo htmlspecialchars($time_label); ?></span>
    </div>

    <?php if($existing): ?>
    <div class="existing-note">Currently: <strong><?php echo htmlspecialchars($existing['subject']); ?></strong> &mdash; <?php echo htmlspecialchars($existing['instructor']); ?></div>
    <?php endif; ?>

    <?php if($error): ?>
    <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Subject</label>
            <input name="subject" placeholder="e.g. Mathematics" value="<?php echo htmlspecialchars($existing['subject'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label>Instructor</label>
            <input name="instructor" placeholder="e.g. Mr. Santos" value="<?php echo htmlspecialchars($existing['instructor'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label>Room</label>
            <input name="room" placeholder="e.g. 204" value="<?php echo htmlspecialchars($existing['room'] ?? ''); ?>" required>
        </div>
<div class="btn-row">
            <a href="timetable.php?section=<?php echo urlencode($section); ?>" class="btn-back">&larr;</a>
            <button type="submit" class="btn-save">Save</button>
            <?php if($existing): ?>
            <a href="assign_schedule.php?section=<?php echo urlencode($section); ?>&day=<?php echo urlencode($day); ?>&time_slot_id=<?php echo $time_slot_id; ?>&clear=1"
               class="btn-clear" onclick="return confirm('Clear this slot?')">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>
</body>
</html>
