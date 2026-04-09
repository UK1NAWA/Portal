<?php
include 'db.php';
include 'session_config.php';
include_once __DIR__ . '/cache.php';

if(!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.html");
   exit();
    exit();
}

$error = '';

if(isset($_POST['create_schedule'])){
    csrf_verify();
    $sec = trim($_POST['section']);
    if($sec === ''){
        $error = "Please select a section.";
    } else {

        $chk = $conn->prepare("SELECT id FROM section_schedules WHERE section=? LIMIT 1");
        $chk->bind_param("s", $sec);
        $chk->execute();
        $chk->store_result();
        if($chk->num_rows > 0){
            $error = "A schedule for \"$sec\" already exists. Use View to edit it.";
        } else {
            $ins = $conn->prepare("INSERT INTO section_schedules (section, status) VALUES (?, 'draft')");
            $ins->bind_param("s", $sec);
            $ins->execute();
            header("Location: timetable.php?section=".urlencode($sec));
            exit();
        }
    }
}

// --- PUBLISH ALL for a section ---
if(isset($_POST['publish_section'])){
    csrf_verify();
    $sec = $_POST['publish_section'];
    $stmt = $conn->prepare("UPDATE section_schedules SET status='published' WHERE section=?");
    $stmt->bind_param("s", $sec);
    $stmt->execute();
    header("Location: admin_schedules.php?msg=published");
   exit();
    exit();
}

if(isset($_POST['draft_section'])){
    csrf_verify();
    $sec = $_POST['draft_section'];
    $stmt = $conn->prepare("UPDATE section_schedules SET status='draft' WHERE section=?");
    $stmt->bind_param("s", $sec);
    $stmt->execute();
    header("Location: admin_schedules.php?msg=drafted");
   exit();
    exit();
}

if(isset($_POST['delete_section'])){
    csrf_verify();
    $sec = $_POST['delete_section'];
    $stmt = $conn->prepare("DELETE FROM section_schedules WHERE section=?");
    $stmt->bind_param("s", $sec);
    $stmt->execute();
    header("Location: admin_schedules.php?msg=deleted");
   exit();
    exit();
}

$schedules_query = $conn->query("
    SELECT
        ss.section,
        COUNT(CASE WHEN ss.subject IS NOT NULL AND ss.subject != '' THEN 1 END) as filled_slots,
        SUM(CASE WHEN ss.status='published' THEN 1 ELSE 0 END) as published_count,
        SUM(CASE WHEN ss.status='draft' THEN 1 ELSE 0 END) as draft_count,
        (SELECT COUNT(*) FROM users u WHERE u.role='student' AND u.section=ss.section) as student_count
    FROM section_schedules ss
    GROUP BY ss.section
    ORDER BY ss.section
");

$available = $conn->query("
    SELECT section_name FROM sections
    WHERE section_name NOT IN (SELECT DISTINCT section FROM section_schedules)
    ORDER BY section_name
");

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - Schedules</title>
<link rel="stylesheet" href="admin.css">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
.two-col { display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: start; }

.form-group { margin-bottom: 14px; }
.form-group label {
    display: block; font-size: 12px; color: var(--muted);
    margin-bottom: 5px; text-transform: uppercase; letter-spacing: .05em;
}
.form-group select {
    width: 100%; background: var(--bg); border: 1px solid var(--border);
    color: var(--text); padding: 9px 12px; border-radius: 8px;
    font-size: 14px; font-family: 'DM Sans', sans-serif;
    outline: none; box-sizing: border-box; transition: border-color .15s;
}
.form-group select:focus { border-color: var(--accent); }
.form-group select option { background: var(--surface); }

.btn-primary {
    width: 100%; background: var(--accent); color: #000; border: none;
    padding: 10px; border-radius: 8px; font-weight: 700; font-size: 14px;
    cursor: pointer; font-family: 'DM Sans', sans-serif;
}
.btn-primary:hover { opacity: .9; }

.sched-table { width: 100%; border-collapse: collapse; }
.sched-table th {
    background: var(--bg); color: var(--muted); font-size: 11px;
    letter-spacing: .07em; text-transform: uppercase;
    padding: 10px 14px; text-align: left; border-bottom: 1px solid var(--border);
}
.sched-table td {
    padding: 13px 14px; border-bottom: 1px solid var(--border);
    font-size: 13px; vertical-align: middle;
}
.sched-table tr:last-child td { border-bottom: none; }
.sched-table tr:hover td { background: var(--bg); }

.bar-track {
    height: 4px; background: var(--border); border-radius: 99px;
    width: 80px; overflow: hidden; display: inline-block;
    vertical-align: middle; margin: 0 8px;
}
.bar-fill { height: 100%; background: var(--accent2); border-radius: 99px; }

.pill { font-size: 11px; padding: 2px 9px; border-radius: 20px; font-weight: 500; margin-right: 4px; }
.pill-green  { background: rgba(62,207,142,.12); color: var(--accent2); }
.pill-yellow { background: rgba(240,180,41,.12);  color: var(--accent); }
.pill-muted  { background: var(--border); color: var(--muted); }

.act-btn {
    font-size: 12px; padding: 5px 12px; border-radius: 7px;
    border: 1px solid var(--border); background: transparent;
    color: var(--muted); cursor: pointer; text-decoration: none;
    font-family: 'DM Sans', sans-serif; transition: all .15s; display: inline-block;
}
.act-btn:hover        { border-color: #444; color: var(--text); }
.act-btn.danger:hover { border-color: var(--accent3); color: var(--accent3); }

.alert { padding: 10px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; }
.alert-green { background: rgba(62,207,142,.1); border: 1px solid rgba(62,207,142,.3); color: var(--accent2); }
.alert-red   { background: rgba(226,92,92,.1);  border: 1px solid rgba(226,92,92,.3);  color: var(--accent3); }

.empty-state { color: var(--muted); font-size: 13px; padding: 24px 0; text-align: center; }
</style>
<script>
if(localStorage.getItem('adminTheme')==='light') document.body.classList.add('light-mode');
</script>
</head>
<body>

<!-- SIDEBAR OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- SIDEBAR -->
<div class="sidebar" id="adminSidebar">
    <div class="sidebar-brand">
    <img src="logo.png" alt="BCT Logo" style="width:140px;height:140px;border-radius:10%;display:block;margin:0 auto 10px;">

    </div>
        <nav class="sidebar-nav">
        <a href="admin.php"><span class="icon">◈</span> Dashboard</a>
        <a href="admin_users.php"><span class="icon">◉</span> Users</a>
        <a href="admin_pending.php">
            <span class="icon">◎</span> Pending
            <?php
            $__tbl = $conn->query("SHOW TABLES LIKE 'pending_registrations'");
            $__pc = ($__tbl && $__tbl->num_rows > 0)
                ? (int)$conn->query("SELECT COUNT(*) as c FROM pending_registrations WHERE status='pending'")->fetch_assoc()['c']
                : 0;
            if($__pc > 0) echo '<span style="background:#f0b429;color:#000;font-size:10px;font-weight:800;padding:1px 7px;border-radius:20px;margin-left:6px;">'.$__pc.'</span>';
            ?>
        </a>
        <a href="admin_sections.php"><span class="icon">▣</span> Sections</a>
        <a href="admin_timeslots.php"><span class="icon">◫</span> Time Slots</a>
        <a href="admin_schedules.php" class="active"><span class="icon">▦</span> Schedules</a>
        <a href="admin_grades.php"><span class="icon">◧</span> Grades</a>
        <a href="admin_audit.php">Audit Log</a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php"><span class="icon">&#x238B;</span> Logout</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <div class="topbar-left" style="display:flex;align-items:center;gap:12px;">
            <button class="hamburger-btn" onclick="toggleSidebar()" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
            <div>
                <h1>Schedules</h1>
                <p>Create, manage, and publish section schedules</p>
            </div>
        </div>
        <div class="topbar-right">
            <div class="admin-badge">
                <div class="admin-avatar"><?php echo strtoupper(substr($_SESSION['fullname'],0,1)); ?></div>
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
            <button class="theme-toggle-btn" onclick="toggleTheme()" title="Toggle light/dark mode">
                <span class="tog-track"><span class="tog-thumb"></span></span>
                <span id="themeLabel">Light</span>
            </button>
        </div>
    </div>

    <?php if($msg === 'published'): ?>
    <div class="alert alert-green">Schedule published. Students can now view it.</div>
    <?php elseif($msg === 'drafted'): ?>
    <div class="alert alert-green">Schedule moved back to draft.</div>
    <?php elseif($msg === 'deleted'): ?>
    <div class="alert alert-red">Section schedule deleted.</div>
    <?php endif; ?>

    <?php if($error): ?>
    <div class="alert alert-red"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="two-col">

        <div>
            <div class="panel">
                <div class="panel-header"><h3>Create Schedule</h3></div>

                <?php if($available && $available->num_rows > 0): ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <div class="form-group">
                        <label>Section</label>
                        <select name="section" required>
                            <option value="">Select section</option>
                            <?php while($s = $available->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($s['section_name']); ?>">
                                <?php echo htmlspecialchars($s['section_name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" name="create_schedule" class="btn-primary">Create &rarr; Open Timetable</button>
                </form>
                <?php else: ?>
                <p style="color:var(--muted); font-size:13px;">
                    All sections already have schedules, or no sections have been added yet.
                    <a href="admin_sections.php" style="color:var(--accent);">Add a section</a>
                </p>
                <?php endif; ?>
            </div>

        </div>

        <!-- SCHEDULES TABLE -->
        <div class="panel">
            <div class="panel-header">
                <h3>All Schedules</h3>
                <span class="badge badge-yellow"><?php echo $schedules_query->num_rows; ?> sections</span>
            </div>

            <?php if($schedules_query->num_rows > 0): ?>
            <table class="sched-table">
                <tr>
                    <th>Section</th>
                    <th>Students</th>
                    <th>Filled</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                <?php
                $total_slots = $conn->query("SELECT COUNT(*) as c FROM time_slots")->fetch_assoc()['c'];
                $days = 6; 
                $max_slots = $total_slots * $days;
                while($row = $schedules_query->fetch_assoc()):
                    $pct = $max_slots > 0 ? min(100, round(($row['filled_slots'] / $max_slots) * 100)) : 0;
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($row['section']); ?></strong></td>
                    <td><span class="pill pill-muted"><?php echo $row['student_count']; ?></span></td>
                    <td>
                        <?php echo $row['filled_slots']; ?> / <?php echo $max_slots; ?>
                        <span class="bar-track"><span class="bar-fill" style="width:<?php echo $pct; ?>%"></span></span>
                    </td>
                    <td>
                        <?php if($row['published_count'] > 0): ?>
                        <span class="pill pill-green"><?php echo $row['published_count']; ?> published</span>
                        <?php endif; ?>
                        <?php if($row['draft_count'] > 0): ?>
                        <span class="pill pill-yellow"><?php echo $row['draft_count']; ?> draft</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                            <a href="timetable.php?section=<?php echo urlencode($row['section']); ?>" class="act-btn">View</a>

                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                <input type="hidden" name="publish_section" value="<?php echo htmlspecialchars($row['section']); ?>">
                                <button type="submit" class="act-btn">Publish All</button>
                            </form>

                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                <input type="hidden" name="draft_section" value="<?php echo htmlspecialchars($row['section']); ?>">
                                <button type="submit" class="act-btn">Draft All</button>
                            </form>

                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete all schedules for <?php echo htmlspecialchars($row['section']); ?>?')">
                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                <input type="hidden" name="delete_section" value="<?php echo htmlspecialchars($row['section']); ?>">
                                <button type="submit" class="act-btn danger">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
            <?php else: ?>
            <div class="empty-state">No schedules yet. Create one on the left.</div>
            <?php endif; ?>
        </div>

    </div>
</div>

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
