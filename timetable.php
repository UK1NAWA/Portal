<?php
include 'db.php';
include 'session_config.php';

if(!isset($_SESSION['loggedIn']) || !in_array($_SESSION['role'], ['teacher','admin'])){
    header("Location: login.html");
    exit();
}

if(!isset($_GET['section'])){
    die("No section selected.");
}

$section = $_GET['section'];
$days    = ["Mon","Tue","Wed","Thu","Fri","Sat"];

$slot_result = $conn->query("SELECT * FROM time_slots ORDER BY id");
$time_slots  = [];
while($slot = $slot_result->fetch_assoc()) $time_slots[] = $slot;

$schedule = [];
$stmt = $conn->prepare("SELECT * FROM section_schedules WHERE section=?");
$stmt->bind_param("s", $section);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()){
    $schedule[$row['day']][$row['time_slot_id']] = $row;
}

$back_url = ($_SESSION['role'] === 'admin') ? 'admin_schedules.php' : 'teacher_portal.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Timetable - <?php echo htmlspecialchars($section); ?></title>
<link rel="stylesheet" href="admin.css">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
.page-wrap { padding: 32px 36px; }
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
}
.page-header h1 {
    font-family: 'Syne', sans-serif;
    font-size: 22px;
    font-weight: 800;
}
.page-header span {
    font-size: 13px;
    color: var(--muted);
    margin-left: 10px;
}
.back-link {
    font-size: 13px;
    color: var(--muted);
    text-decoration: none;
}
.back-link:hover { color: var(--text); }

.table-wrap { overflow-x: auto; }
table {
    border-collapse: collapse;
    width: 100%;
    min-width: 640px;
}
th {
    background: var(--surface);
    color: var(--muted);
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    padding: 12px 14px;
    border: 1px solid var(--border);
    text-align: center;
}
td {
    border: 1px solid var(--border);
    padding: 0;
    vertical-align: middle;
    text-align: center;
}
.time-cell {
    background: var(--surface);
    color: var(--muted);
    font-size: 12px;
    font-weight: 500;
    padding: 12px 14px;
    white-space: nowrap;
}
.slot-cell { height: 74px; }
.slot-cell a {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    height: 74px;
    text-decoration: none;
    color: var(--text);
    gap: 3px;
    padding: 6px;
    transition: background 0.15s;
}
.slot-cell a:hover { background: var(--border); }
.slot-empty a { color: var(--border); font-size: 18px; }
.slot-empty a:hover { color: var(--muted); }
.slot-filled { background: var(--surface); }
.s-subj { font-size: 12px; font-weight: 600; color: var(--text); }
.s-instr { font-size: 11px; color: var(--muted); }
.s-room {
    font-size: 10px;
    background: var(--border);
    color: var(--accent);
    padding: 1px 8px;
    border-radius: 20px;
}
.s-status { font-size: 10px; color: var(--muted); display: flex; align-items: center; gap: 4px; }
.dot { width: 5px; height: 5px; border-radius: 50%; display: inline-block; }
.dot-published { background: var(--accent2); }
.dot-draft     { background: var(--muted); }
.legend {
    display: flex;
    gap: 16px;
    font-size: 12px;
    color: var(--muted);
    margin-top: 14px;
}
.legend span { display: flex; align-items: center; gap: 5px; }
</style>
</head>
<body>
<div class="page-wrap">
    <div class="page-header">
        <div>
            <h1>Timetable <span><?php echo htmlspecialchars($section); ?></span></h1>
        </div>
        <a href="<?php echo $back_url; ?>" class="back-link">&larr; Back</a>
    </div>

    <div class="table-wrap">
    <table>
        <tr>
            <th>Time</th>
            <?php foreach($days as $d) echo "<th>$d</th>"; ?>
        </tr>
        <?php foreach($time_slots as $slot): ?>
        <tr>
            <td class="time-cell"><?php echo htmlspecialchars($slot['label']); ?></td>
            <?php foreach($days as $d):
                $entry = $schedule[$d][$slot['id']] ?? null;
            ?>
            <td class="slot-cell <?php echo $entry ? 'slot-filled' : 'slot-empty'; ?>">
                <a href="assign_schedule.php?section=<?php echo urlencode($section); ?>&day=<?php echo urlencode($d); ?>&time_slot_id=<?php echo $slot['id']; ?>">
                    <?php if($entry): ?>
                        <span class="s-subj"><?php echo htmlspecialchars($entry['subject']); ?></span>
                        <span class="s-instr"><?php echo htmlspecialchars($entry['instructor']); ?></span>
                        <span class="s-room"><?php echo htmlspecialchars($entry['room']); ?></span>
                        <span class="s-status">
                            <span class="dot <?php echo $entry['status']==='published'?'dot-published':'dot-draft'; ?>"></span>
                            <?php echo $entry['status']; ?>
                        </span>
                    <?php else: ?>
                        +
                    <?php endif; ?>
                </a>
            </td>
            <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>

    <div class="legend">
        <span><span class="dot dot-published"></span> Published</span>
        <span><span class="dot dot-draft"></span> Draft</span>
        <span>Click any cell to assign or edit a class</span>
    </div>
</div>
</body>
</html>
