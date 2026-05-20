<?php
include 'db.php';
include 'session_config.php';
include_once __DIR__ . '/cache.php';
include_once __DIR__ . '/audit.php';

if(!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'teacher'){
    header("Location: login.html");
    exit();
}

$teacher_id = $_SESSION['id'];
$page = $_GET['page'] ?? 'dashboard';

// ============================================================
// ACTIONS
// ============================================================
if(isset($_POST['assign_student'])){
    $uid = (int)$_POST['user_id'];
    $sec = trim($_POST['section']);
    $stmt = $conn->prepare("UPDATE users SET section=? WHERE id=? AND role='student'");
    $stmt->bind_param("si", $sec, $uid);
    $stmt->execute();
    header("Location: teacher_portal.php?page=students&section=".urlencode($sec)."&msg=assigned");
    exit();
}

if(isset($_POST['remove_student'])){
    $uid = (int)$_POST['remove_student'];
    $sec = trim($_POST['section'] ?? '');
    $chk = $conn->prepare("SELECT id FROM sections WHERE section_name=? AND teacher_id=?");
    $chk->bind_param("si", $sec, $teacher_id);
    $chk->execute(); $chk->store_result();
    if($chk->num_rows > 0){
        $upd = $conn->prepare("UPDATE users SET section=NULL WHERE id=?");
        $upd->bind_param("i", $uid);
        $upd->execute();
    }
    header("Location: teacher_portal.php?page=students&section=".urlencode($sec)."&msg=removed");
    exit();
}

$msg = $_GET['msg'] ?? '';

// ============================================================
// SHARED DATA
// ============================================================
$my_sections_q = $conn->prepare("
    SELECT s.id, s.section_name, s.grade_level, s.strand,
        (SELECT COUNT(*) FROM users u WHERE u.role='student' AND u.section=s.section_name) as student_count
    FROM sections s WHERE s.teacher_id=? ORDER BY s.section_name
");
$my_sections_q->bind_param("i", $teacher_id);
$my_sections_q->execute();
$sections_arr = $my_sections_q->get_result()->fetch_all(MYSQLI_ASSOC);
$my_section_count = count($sections_arr);
$my_students = array_sum(array_column($sections_arr, 'student_count'));

// ============================================================
// PAGE-SPECIFIC DATA
// ============================================================
if($page === 'students'){
    $active_section = $_GET['section'] ?? ($sections_arr[0]['section_name'] ?? '');
    $unassigned = $conn->query("SELECT id, fullname, username FROM users WHERE role='student' AND (section IS NULL OR section='') ORDER BY fullname");
    if($active_section){
        $studs_q = $conn->prepare("SELECT id, fullname, username, student_id_no FROM users WHERE role='student' AND section=? ORDER BY fullname");
        $studs_q->bind_param("s", $active_section);
        $studs_q->execute();
        $section_students = $studs_q->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $section_students = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Teacher Portal</title>
<link rel="stylesheet" href="admin.css">
<style>
.form-group { margin-bottom:14px; }
.form-group label { display:block; font-size:12px; color:var(--muted); margin-bottom:5px; text-transform:uppercase; letter-spacing:.05em; }
.form-group input, .form-group select { width:100%; background:var(--bg); border:1px solid var(--border); color:var(--text); padding:9px 12px; border-radius:8px; font-size:14px; font-family:sans-serif; outline:none; box-sizing:border-box; }
.form-group input:focus, .form-group select:focus { border-color:var(--accent); }
.form-group select option { background:var(--surface); }
.act-btn { font-size:12px; padding:5px 12px; border-radius:7px; border:1px solid var(--border); background:transparent; color:var(--muted); cursor:pointer; text-decoration:none; font-family:sans-serif; transition:all .15s; display:inline-block; }
.act-btn:hover { border-color:#444; color:var(--text); }
.act-btn.danger:hover { border-color:var(--accent3); color:var(--accent3); }
.act-btn.primary { background:var(--accent); color:#000; border-color:var(--accent); font-weight:700; }
.pill { font-size:11px; padding:2px 9px; border-radius:20px; font-weight:500; }
.pill-green  { background:rgba(62,207,142,.12); color:var(--accent2); }
.pill-yellow { background:rgba(240,180,41,.12); color:var(--accent); }
.pill-muted  { background:var(--border); color:var(--muted); }
.alert { padding:10px 16px; border-radius:8px; font-size:13px; margin-bottom:20px; }
.alert-green { background:rgba(62,207,142,.1); border:1px solid rgba(62,207,142,.3); color:var(--accent2); }
.alert-red   { background:rgba(226,92,92,.1);  border:1px solid rgba(226,92,92,.3);  color:var(--accent3); }
.empty-state { color:var(--muted); font-size:14px; padding:24px 0; text-align:center; }
.data-table { width:100%; border-collapse:collapse; }
.data-table th { background:var(--bg); color:var(--muted); font-size:11px; letter-spacing:.07em; text-transform:uppercase; padding:10px 14px; text-align:left; border-bottom:1px solid var(--border); }
.data-table td { padding:12px 14px; border-bottom:1px solid var(--border); font-size:13px; vertical-align:middle; }
.data-table tr:last-child td { border-bottom:none; }
.data-table tr:hover td { background:var(--bg); }
.bar-track { height:4px; background:var(--border); border-radius:99px; width:80px; overflow:hidden; display:inline-block; vertical-align:middle; margin:0 8px; }
.bar-fill { height:100%; background:var(--accent2); border-radius:99px; }
.section-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px; }
.sec-tab { padding:7px 16px; border-radius:8px; border:1px solid var(--border); font-size:13px; text-decoration:none; color:var(--muted); transition:all .15s; }
.sec-tab:hover { border-color:#444; color:var(--text); }
.sec-tab.active { background:rgba(240,180,41,.12); border-color:var(--accent); color:var(--accent); font-weight:600; }
.sections-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(260px,1fr)); gap:14px; }
.sec-card { background:var(--bg); border:1px solid var(--border); border-radius:12px; padding:18px 20px; text-decoration:none; color:var(--text); display:block; transition:border-color .2s; }
.sec-card:hover { border-color:var(--accent); }
.sec-card-name { font-size:16px; font-weight:700; margin-bottom:4px; }
.sec-card-meta { font-size:12px; color:var(--muted); }
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:999; justify-content:center; align-items:center; }
.modal-overlay.open { display:flex; }
.modal { background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:28px; width:340px; }
.modal h3 { font-size:16px; font-weight:700; margin-bottom:16px; }
.modal-btns { display:flex; gap:8px; margin-top:16px; }
.modal-btns button { flex:1; padding:9px; border-radius:8px; font-size:13px; cursor:pointer; font-family:sans-serif; border:1px solid var(--border); }
.btn-cancel { background:transparent; color:var(--muted); }
.btn-save   { background:var(--accent); color:#000; border-color:var(--accent); font-weight:700; }
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
    <img src="logo.png" alt="BCT Logo" style="width:140px;height:140px;border-radius:10%;display:block;margin:0 auto 10px;">
    <h2>Admin Panel</h2>
    </div>
    <nav class="sidebar-nav">
        <a href="teacher_portal.php?page=dashboard" <?php if($page==='dashboard') echo 'class="active"'; ?>><span class="icon">◈</span> Dashboard</a>
        <a href="teacher_portal.php?page=students" <?php if($page==='students') echo 'class="active"'; ?>><span class="icon">◉</span> Students</a>
        <a href="teacher_grades.php"><span class="icon">◧</span> Grades</a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php"><span class="icon">&#x238B;</span> Logout</a>
    </div>
</div>

<!-- MAIN -->
<div class="main">

<?php
$alert_map = [
    'assigned' => ['green','Student added to section.'],
    'removed'  => ['green','Student removed from section.'],
];
if($msg && isset($alert_map[$msg])): [$color,$text] = $alert_map[$msg]; ?>
<div class="alert alert-<?php echo $color; ?>" style="margin-top:20px;"><?php echo $text; ?></div>
<?php endif; ?>


<?php if($page === 'dashboard'): ?>
<!-- ══ DASHBOARD ══ -->
<div class="topbar">
    <div class="topbar-left" style="display:flex;align-items:center;gap:12px;">
        <button class="hamburger-btn" onclick="toggleSidebar()" aria-label="Toggle menu"><span></span><span></span><span></span></button>
        <div><h1>Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?></p></div>
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


<!-- My Sections -->
<div class="panel">
    <div class="panel-header">
        <h3>My Sections</h3>
        <span class="badge badge-yellow"><?php echo $my_section_count; ?> sections</span>
    </div>
    <?php if(!empty($sections_arr)): ?>
    <div class="sections-grid">
        <?php foreach($sections_arr as $s): ?>
        <a href="teacher_portal.php?page=students&section=<?php echo urlencode($s['section_name']); ?>" class="sec-card">
            <div class="sec-card-name"><?php echo htmlspecialchars($s['section_name']); ?></div>
            <div class="sec-card-meta">
                <?php echo $s['student_count']; ?> student<?php echo $s['student_count']!=1?'s':''; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">No sections assigned yet.</div>
    <?php endif; ?>
</div>


<?php elseif($page === 'students'): ?>
<!-- ══ STUDENTS ══ -->
<div class="topbar">
    <div class="topbar-left" style="display:flex;align-items:center;gap:12px;"><button class="hamburger-btn" onclick="toggleSidebar()" aria-label="Toggle menu"><span></span><span></span><span></span></button><div><h1>Students</h1><p>Manage students in your sections</p></div></div>
    <div class="topbar-right">
        <div class="admin-badge">
            <div class="admin-avatar"><?php echo strtoupper(substr($_SESSION['fullname'],0,1)); ?></div>
            <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
        </div>
    </div>
</div>

<?php if(!empty($sections_arr)): ?>
<div class="section-tabs">
    <?php foreach($sections_arr as $s): ?>
    <a href="teacher_portal.php?page=students&section=<?php echo urlencode($s['section_name']); ?>"
       class="sec-tab <?php echo ($active_section === $s['section_name']) ? 'active' : ''; ?>">
        <?php echo htmlspecialchars($s['section_name']); ?>
        <span style="font-size:11px; margin-left:4px; opacity:.7;"><?php echo $s['student_count']; ?></span>
    </a>
    <?php endforeach; ?>
</div>

<?php if($active_section): ?>
<div class="panel">
    <div class="panel-header">
        <h3><?php echo htmlspecialchars($active_section); ?></h3>
        <button class="act-btn primary" onclick="document.getElementById('addModal').classList.add('open')">+ Add Student</button>
    </div>
    <?php if(!empty($section_students)): ?>
    <table class="data-table">
        <tr><th>Student</th><th>Student ID</th><th>Username</th><th>Actions</th></tr>
        <?php foreach($section_students as $st): ?>
        <tr>
            <td>
                <div style="display:flex; align-items:center; gap:10px;">
                    <div class="user-avatar ua-student"><?php echo strtoupper(substr($st['fullname'],0,1)); ?></div>
                    <span><?php echo htmlspecialchars($st['fullname']); ?></span>
                </div>
            </td>
            <td style="color:var(--muted); font-family:monospace; font-size:13px;"><?php echo htmlspecialchars($st['student_id_no'] ?? '—'); ?></td>
            <td style="color:var(--muted);">@<?php echo htmlspecialchars($st['username']); ?></td>
            <td>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this student from the section?')">
                    <input type="hidden" name="remove_student" value="<?php echo $st['id']; ?>">
                    <input type="hidden" name="section" value="<?php echo htmlspecialchars($active_section); ?>">
                    <button type="submit" class="act-btn danger">Remove</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
    <div class="empty-state">No students in this section yet. Click Add Student above.</div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="modal-overlay" id="addModal">
    <div class="modal">
        <h3>Add Student to <?php echo htmlspecialchars($active_section); ?></h3>
        <form method="POST">
            <input type="hidden" name="section" value="<?php echo htmlspecialchars($active_section); ?>">
            <div class="form-group">
                <label>Student</label>
                <select name="user_id" required>
                    <option value="">Select student</option>
                    <?php while($u = $unassigned->fetch_assoc()): ?>
                    <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['fullname']); ?> (@<?php echo htmlspecialchars($u['username']); ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="modal-btns">
                <button type="button" class="btn-cancel" onclick="document.getElementById('addModal').classList.remove('open')">Cancel</button>
                <button type="submit" name="assign_student" class="btn-save">Add</button>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('addModal').addEventListener('click', function(e){ if(e.target===this) this.classList.remove('open'); });
</script>

<?php else: ?>
<div class="empty-state">No sections assigned to you yet. Contact the admin.</div>
<?php endif; ?>



<?php endif; ?>

</div><!-- /main -->

<script>
(function(){
    if(localStorage.getItem('adminTheme') === 'light') document.body.classList.add('light-mode');
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