<?php
include 'db.php';
include 'session_config.php';
include_once __DIR__ . '/audit.php';

if(!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'teacher'){
    header("Location: login.html");
    exit();
}

$teacher_id = $_SESSION['id'];
$msg        = '';

// Get teacher's sections
$sec_q = $conn->prepare("SELECT section_name FROM sections WHERE teacher_id=? ORDER BY section_name");
$sec_q->bind_param("i", $teacher_id);
$sec_q->execute();
$my_sections = $sec_q->get_result()->fetch_all(MYSQLI_ASSOC);

$selected_section = trim($_GET['section'] ?? ($my_sections[0]['section_name'] ?? ''));
$selected_date    = $_GET['date'] ?? date('Y-m-d');

// Save attendance
if(isset($_POST['save_attendance'])){
    csrf_verify();
    $sec  = trim($_POST['section']);
    $date = $_POST['date'];
    $statuses = $_POST['status'] ?? [];

    foreach($statuses as $student_id => $status){
        $student_id = (int)$student_id;
        if(!in_array($status, ['present','absent','late'])) continue;
        $stmt = $conn->prepare("
            INSERT INTO attendance (student_id, section, date, status, marked_by)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE status=VALUES(status), marked_by=VALUES(marked_by)
        ");
        $stmt->bind_param("isssi", $student_id, $sec, $date, $status, $teacher_id);
        $stmt->execute();
    }
    $msg = "Attendance saved for " . date('F d, Y', strtotime($date)) . ".";
    $selected_section = $sec;
    $selected_date    = $date;
}

// Get students in selected section
$students = [];
if($selected_section){
    $stmt = $conn->prepare("SELECT id, fullname, student_id_no FROM users WHERE role='student' AND section=? ORDER BY fullname");
    $stmt->bind_param("s", $selected_section);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get existing attendance for selected date+section
$existing = [];
if($selected_section && $selected_date){
    $stmt = $conn->prepare("SELECT student_id, status FROM attendance WHERE section=? AND date=?");
    $stmt->bind_param("ss", $selected_section, $selected_date);
    $stmt->execute();
    foreach($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row){
        $existing[$row['student_id']] = $row['status'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Teacher - Attendance</title>
<link rel="stylesheet" href="admin.css">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
.filter-bar { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px; align-items:center; }
.filter-bar select,
.filter-bar input[type="date"] {
    background: var(--bg); border: 1px solid var(--border);
    color: var(--text); padding: 8px 12px; border-radius: 8px;
    font-size: 13px; font-family: 'DM Sans', sans-serif; outline: none;
}
.filter-bar select:focus,
.filter-bar input[type="date"]:focus { border-color: var(--accent); }
.filter-bar button {
    background: var(--accent); color: #000; border: none;
    padding: 8px 18px; border-radius: 8px;
    font-weight: 700; font-size: 13px; cursor: pointer;
    font-family: 'DM Sans', sans-serif;
}

.att-table { width:100%; border-collapse:collapse; }
.att-table th {
    background: var(--bg); color: var(--muted);
    font-size: 11px; letter-spacing: 0.07em; text-transform: uppercase;
    padding: 10px 14px; text-align: left; border-bottom: 1px solid var(--border);
}
.att-table td {
    padding: 11px 14px; border-bottom: 1px solid var(--border);
    font-size: 13px; vertical-align: middle;
}
.att-table tr:last-child td { border-bottom: none; }
.att-table tr:hover td { background: var(--bg); }

.status-group { display:flex; gap:6px; }
.status-btn {
    padding: 5px 14px; border-radius: 6px; font-size: 12px;
    border: 1px solid var(--border); background: transparent;
    color: var(--muted); cursor: pointer; font-family: 'DM Sans', sans-serif;
    transition: 0.15s;
}
.status-btn.selected-present { background: rgba(62,207,142,0.15); color: var(--accent2); border-color: rgba(62,207,142,0.4); }
.status-btn.selected-absent  { background: rgba(226,92,92,0.15);  color: var(--accent3); border-color: rgba(226,92,92,0.4); }
.status-btn.selected-late    { background: rgba(240,180,41,0.15); color: var(--accent);  border-color: rgba(240,180,41,0.4); }

.btn-save {
    margin-top: 16px; padding: 10px 28px; border-radius: 8px;
    background: var(--accent); color: #000; border: none;
    font-weight: 700; font-size: 13px; cursor: pointer;
    font-family: 'DM Sans', sans-serif;
}
.btn-save:hover { opacity: 0.9; }

.mark-all { display:flex; gap:8px; margin-bottom:14px; align-items:center; }
.mark-all span { font-size:12px; color:var(--muted); }
.mark-all button {
    padding: 4px 12px; border-radius: 6px; font-size: 12px;
    border: 1px solid var(--border); background: transparent;
    color: var(--muted); cursor: pointer; font-family: 'DM Sans', sans-serif;
}
.mark-all button:hover { color: var(--text); border-color: #444; }

.alert { padding: 10px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; }
.alert-green { background: rgba(62,207,142,0.1); border: 1px solid rgba(62,207,142,0.3); color: var(--accent2); }
</style>
</head>
<body>
<script>
if(localStorage.getItem('adminTheme')==='light') document.body.classList.add('light-mode');
</script>

<!-- SIDEBAR OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- SIDEBAR -->
<div class="sidebar" id="adminSidebar">
    <div class="sidebar-brand">
        <div class="logo-mark">T</div>
        <h2>Teacher Panel</h2>
        <p>Student Portal System</p>
    </div>
        <nav class="sidebar-nav">
        <a href="teacher_portal.php?page=dashboard"><span class="icon">◈</span> Dashboard</a>
        <a href="teacher_portal.php?page=students"><span class="icon">◉</span> Students</a>
        <a href="teacher_portal.php?page=schedules"><span class="icon">▦</span> Schedules</a>
        <a href="teacher_portal.php?page=announcements"><span class="icon">◫</span> Announcements</a>
        <a href="teacher_grades.php"><span class="icon">◧</span> Grades</a>
        <a href="teacher_attendance.php" class="active"><span class="icon">▣</span> Attendance</a>
        <a href="teacher_tasks.php"><span class="icon">◐</span> Tasks</a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php"><span class="icon">◄</span> Logout</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <div class="topbar-left" style="display:flex;align-items:center;gap:12px;">
            <button class="hamburger-btn" onclick="toggleSidebar()" aria-label="Toggle menu"><span></span><span></span><span></span></button>
            <div><h1>Attendance</h1></div>
        </div>
        <div class="topbar-right">
            <button class="theme-toggle-btn" onclick="toggleTheme()" title="Toggle light/dark mode">
                <span class="tog-track"><span class="tog-thumb"></span></span>
                <span id="themeLabel">Light</span>
            </button>
        </div>
    </div>

    <?php if($msg): ?>
    <div class="alert alert-green"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <!-- FILTER BAR -->
    <form method="GET" class="filter-bar">
        <select name="section">
            <?php foreach($my_sections as $s): ?>
            <option value="<?php echo htmlspecialchars($s['section_name']); ?>"
                <?php if($s['section_name'] === $selected_section) echo 'selected'; ?>>
                <?php echo htmlspecialchars($s['section_name']); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="date" value="<?php echo htmlspecialchars($selected_date); ?>">
        <button type="submit">Load</button>
    </form>

    <div class="panel">
        <div class="panel-header">
            <h3><?php echo htmlspecialchars($selected_section); ?> &mdash; <?php echo date('F d, Y', strtotime($selected_date)); ?></h3>
        </div>

        <?php if(empty($students)): ?>
        <p style="color:var(--muted); font-size:13px; padding:16px 0;">No students in this section yet.</p>
        <?php else: ?>

        <!-- MARK ALL SHORTCUT -->
        <div class="mark-all">
            <span>Mark all:</span>
            <button type="button" onclick="markAll('present')">Present</button>
            <button type="button" onclick="markAll('absent')">Absent</button>
            <button type="button" onclick="markAll('late')">Late</button>
        </div>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="section" value="<?php echo htmlspecialchars($selected_section); ?>">
            <input type="hidden" name="date" value="<?php echo htmlspecialchars($selected_date); ?>">

            <table class="att-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($students as $i => $student):
                    $current = $existing[$student['id']] ?? 'present';
                ?>
                <tr>
                    <td style="color:var(--muted);"><?php echo $i+1; ?></td>
                    <td>
                        <div style="font-weight:500;"><?php echo htmlspecialchars($student['fullname']); ?></div>
                        <?php if(!empty($student['student_id_no'])): ?>
                        <div style="font-size:11px; color:var(--muted); font-family:monospace;"><?php echo htmlspecialchars($student['student_id_no']); ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="status-group" data-student="<?php echo $student['id']; ?>">
                            <input type="hidden" name="status[<?php echo $student['id']; ?>]"
                                   id="val_<?php echo $student['id']; ?>"
                                   value="<?php echo $current; ?>">
                            <button type="button"
                                class="status-btn <?php echo $current === 'present' ? 'selected-present' : ''; ?>"
                                onclick="setStatus(<?php echo $student['id']; ?>, 'present', this)">
                                Present
                            </button>
                            <button type="button"
                                class="status-btn <?php echo $current === 'absent' ? 'selected-absent' : ''; ?>"
                                onclick="setStatus(<?php echo $student['id']; ?>, 'absent', this)">
                                Absent
                            </button>
                            <button type="button"
                                class="status-btn <?php echo $current === 'late' ? 'selected-late' : ''; ?>"
                                onclick="setStatus(<?php echo $student['id']; ?>, 'late', this)">
                                Late
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <button type="submit" name="save_attendance" class="btn-save">Save Attendance</button>
        </form>

        <?php endif; ?>
    </div>
</div>

<script>
function setStatus(studentId, status, btn){
    // Update hidden input
    document.getElementById('val_' + studentId).value = status;
    // Update button styles
    const group = btn.closest('.status-group');
    group.querySelectorAll('.status-btn').forEach(b => {
        b.className = 'status-btn';
    });
    btn.classList.add('selected-' + status);
}

function markAll(status){
    document.querySelectorAll('.status-group').forEach(group => {
        const studentId = group.dataset.student;
        document.getElementById('val_' + studentId).value = status;
        group.querySelectorAll('.status-btn').forEach(b => b.className = 'status-btn');
        group.querySelectorAll('.status-btn').forEach(b => {
            if(b.textContent.trim().toLowerCase() === status){
                b.classList.add('selected-' + status);
            }
        });
    });
}
</script>



<script>
(function(){
    if(localStorage.getItem('adminTheme') === 'light'){
        document.body.classList.add('light-mode');
    }
})();

function toggleTheme(){
    const isLight = document.body.classList.toggle('light-mode');
    localStorage.setItem('adminTheme', isLight ? 'light' : 'dark');
    const lbl = document.getElementById('themeLabel');
    if(lbl) lbl.textContent = isLight ? 'Dark' : 'Light';
}

document.addEventListener('DOMContentLoaded', function(){
    const lbl = document.getElementById('themeLabel');
    if(lbl) lbl.textContent = document.body.classList.contains('light-mode') ? 'Dark' : 'Light';
});

function toggleSidebar(){
    document.getElementById('adminSidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}
function closeSidebar(){
    document.getElementById('adminSidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('active');
}
document.querySelectorAll('.sidebar-nav a, .sidebar-footer a').forEach(function(link){
    link.addEventListener('click', function(){
        if(window.innerWidth <= 768) closeSidebar();
    });
});
</script>
</body>
</html>
