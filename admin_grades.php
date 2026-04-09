<?php
include 'db.php';
include 'session_config.php';

if(!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.html");
    exit();
}

$msg   = '';
$error = '';

// Toggle grading period open/close
if(isset($_POST['toggle_period'])){
    csrf_verify();
    $id      = (int)$_POST['period_id'];
    $current = (int)$_POST['current_state'];
    $new     = $current ? 0 : 1;
    $opened  = $new ? date('Y-m-d H:i:s') : null;
    $stmt = $conn->prepare("UPDATE grading_periods SET is_open=?, opened_at=? WHERE id=?");
    $stmt->bind_param("isi", $new, $opened, $id);
    $stmt->execute();
    $msg = $new ? "Grading period opened." : "Grading period closed.";
}

// Lock a submission
if(isset($_POST['lock_submission'])){
    csrf_verify();
    $id = (int)$_POST['submission_id'];
    $conn->prepare("UPDATE grade_submissions SET status='locked' WHERE id=?")->bind_param("i",$id)->execute();
    $msg = "Submission locked.";
}

// Unlock a submission
if(isset($_POST['unlock_submission'])){
    csrf_verify();
    $id = (int)$_POST['submission_id'];
    $conn->prepare("UPDATE grade_submissions SET status='submitted' WHERE id=?")->bind_param("i",$id)->execute();
    $msg = "Submission unlocked.";
}

$periods = $conn->query("SELECT * FROM grading_periods ORDER BY FIELD(quarter,'Q1','Q2','Q3','Q4')")->fetch_all(MYSQLI_ASSOC);

$submissions = $conn->query("
    SELECT gs.*, u.fullname as teacher_name
    FROM grade_submissions gs
    JOIN users u ON u.id = gs.teacher_id
    ORDER BY gs.submitted_at DESC
");

// Count sections that have submitted vs total per open quarter
$open_quarter = null;
foreach($periods as $p){ if($p['is_open']) $open_quarter = $p['quarter']; }

$total_sections   = $conn->query("SELECT COUNT(*) as c FROM sections")->fetch_assoc()['c'];
$submitted_count  = $open_quarter
    ? $conn->query("SELECT COUNT(*) as c FROM grade_submissions WHERE quarter='$open_quarter'")->fetch_assoc()['c']
    : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - Grades</title>
<link rel="stylesheet" href="admin.css">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
.periods-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 28px;
}
.period-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 18px;
    text-align: center;
}
.period-card .q-label { font-size: 22px; font-weight: 800; font-family: 'Syne', sans-serif; }
.period-card .q-status { font-size: 12px; margin: 6px 0 14px; }
.open-pill   { color: var(--accent2); }
.closed-pill { color: var(--muted); }
.btn-toggle {
    width: 100%;
    padding: 7px;
    border-radius: 7px;
    border: 1px solid var(--border);
    font-size: 12px;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    background: transparent;
    color: var(--muted);
    transition: 0.15s;
}
.btn-toggle.open-btn  { background: rgba(62,207,142,0.1); color: var(--accent2); border-color: rgba(62,207,142,0.3); }
.btn-toggle.close-btn { background: rgba(226,92,92,0.1);  color: var(--accent3); border-color: rgba(226,92,92,0.3); }

.sub-table { width: 100%; border-collapse: collapse; }
.sub-table th {
    background: var(--bg); color: var(--muted);
    font-size: 11px; letter-spacing: 0.07em; text-transform: uppercase;
    padding: 10px 14px; text-align: left;
    border-bottom: 1px solid var(--border);
}
.sub-table td {
    padding: 12px 14px; border-bottom: 1px solid var(--border);
    font-size: 13px; vertical-align: middle;
}
.sub-table tr:last-child td { border-bottom: none; }
.sub-table tr:hover td { background: var(--bg); }

.act-btn {
    font-size: 12px; padding: 5px 12px; border-radius: 7px;
    border: 1px solid var(--border); background: transparent;
    color: var(--muted); cursor: pointer; text-decoration: none;
    font-family: 'DM Sans', sans-serif; transition: all 0.15s; display: inline-block;
}
.act-btn:hover { border-color: #444; color: var(--text); }
.act-btn.danger { }
.act-btn.danger:hover { border-color: var(--accent3); color: var(--accent3); }
.act-btn.success:hover { border-color: var(--accent2); color: var(--accent2); }

.alert { padding: 10px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; }
.alert-green { background: rgba(62,207,142,0.1); border: 1px solid rgba(62,207,142,0.3); color: var(--accent2); }

.progress-info { font-size: 13px; color: var(--muted); margin-bottom: 8px; }
.progress-bar-wrap { background: var(--border); border-radius: 6px; height: 6px; margin-bottom: 20px; }
.progress-bar-fill { height: 6px; border-radius: 6px; background: var(--accent); transition: width 0.3s; }
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
        <a href="admin_schedules.php"><span class="icon">▦</span> Schedules</a>
        <a href="admin_grades.php" class="active"><span class="icon">◧</span> Grades</a>
        <a href="admin_audit.php">Audit Log</a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php"><span class="icon">◄</span> Logout</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <div class="topbar-left" style="display:flex;align-items:center;gap:12px;">
            <button class="hamburger-btn" onclick="toggleSidebar()" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
            <div>
                <h1>Grades</h1>
                <p>Manage grading periods and teacher submissions</p>
            </div>
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

    <!-- GRADING PERIODS -->
    <div class="panel" style="margin-bottom:20px;">
        <div class="panel-header">
            <h3>Grading Periods</h3>
            <span class="badge badge-yellow"><?php echo date('Y'); ?></span>
        </div>
        <div class="periods-grid">
            <?php foreach($periods as $p): ?>
            <div class="period-card">
                <div class="q-label"><?php echo $p['quarter']; ?></div>
                <div class="q-status <?php echo $p['is_open'] ? 'open-pill' : 'closed-pill'; ?>">
                    <?php echo $p['is_open'] ? '● Open' : '○ Closed'; ?>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="period_id" value="<?php echo $p['id']; ?>">
                    <input type="hidden" name="current_state" value="<?php echo $p['is_open']; ?>">
                    <button type="submit" name="toggle_period"
                        class="btn-toggle <?php echo $p['is_open'] ? 'close-btn' : 'open-btn'; ?>">
                        <?php echo $p['is_open'] ? 'Close' : 'Open'; ?>
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if($open_quarter): ?>
        <div class="progress-info">
            <?php echo $submitted_count; ?> of <?php echo $total_sections; ?> sections submitted for <?php echo $open_quarter; ?>
        </div>
        <div class="progress-bar-wrap">
            <div class="progress-bar-fill" style="width:<?php echo $total_sections > 0 ? round(($submitted_count/$total_sections)*100) : 0; ?>%"></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- SUBMISSIONS -->
    <div class="panel">
        <div class="panel-header">
            <h3>Grade Submissions</h3>
        </div>

        <?php
        $rows = $submissions->fetch_all(MYSQLI_ASSOC);
        if(empty($rows)):
        ?>
        <p style="color:var(--muted); font-size:13px; padding: 16px 0;">No submissions yet.</p>
        <?php else: ?>
        <table class="sub-table">
            <thead>
                <tr>
                    <th>Teacher</th>
                    <th>Section</th>
                    <th>Quarter</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($rows as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['teacher_name']); ?></td>
                <td><?php echo htmlspecialchars($row['section']); ?></td>
                <td><?php echo $row['quarter']; ?></td>
                <td><?php echo date('M d, Y g:i A', strtotime($row['submitted_at'])); ?></td>
                <td>
                    <?php if($row['status'] === 'locked'): ?>
                    <span style="color:var(--accent3); font-size:12px;">Locked</span>
                    <?php else: ?>
                    <span style="color:var(--accent2); font-size:12px;">Submitted</span>
                    <?php endif; ?>
                </td>
                <td style="display:flex; gap:6px; flex-wrap:wrap;">
                    <a href="<?php echo htmlspecialchars($row['file_path']); ?>" class="act-btn" download>Download</a>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="submission_id" value="<?php echo $row['id']; ?>">
                        <?php if($row['status'] === 'locked'): ?>
                        <button type="submit" name="unlock_submission" class="act-btn success">Unlock</button>
                        <?php else: ?>
                        <button type="submit" name="lock_submission" class="act-btn danger">Lock</button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
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
