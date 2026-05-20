<?php
include 'db.php';
include 'session_config.php';
include_once __DIR__ . '/cache.php';
include_once __DIR__ . '/audit.php';

if(!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.html");
    exit();
}

if(isset($_POST['delete_user'])){
    csrf_verify();
    $id = (int)$_POST['delete_user'];
    if($id !== (int)$_SESSION['id'] && $id > 0){
        $del = $conn->prepare("DELETE FROM users WHERE id=? AND role != 'admin'");
        $del->bind_param("i", $id);
        $del->execute();
    }
    header("Location: admin_users.php?msg=deleted");
    exit();
}

if(isset($_POST['assign_section'])){
    csrf_verify();
    $uid = (int)$_POST['user_id'];
    $sec = trim($_POST['section']);
    $stmt = $conn->prepare("UPDATE users SET section=? WHERE id=?");
    $stmt->bind_param("si", $sec, $uid);
    $stmt->execute();
    Cache::delete('admin_dashboard_stats');
    Cache::delete('admin_recent_users');
    Cache::delete('admin_section_breakdown');
    audit_log($conn, 'USER_SECTION_ASSIGNED', 'User ID: ' . $uid, 'Section: ' . $sec);
    header("Location: admin_users.php?msg=updated");
    exit();
}

$msg         = $_GET['msg'] ?? '';
$search      = trim($_GET['search'] ?? '');
$role_filter = $_GET['role'] ?? '';

$where  = "WHERE 1=1";
$params = [];
$types  = "";

if($search !== ''){
    $where   .= " AND (fullname LIKE ? OR username LIKE ? OR email LIKE ? OR student_id_no LIKE ?)";
    $like     = "%$search%";
    $params   = [$like, $like, $like, $like];
    $types    = "ssss";
}
if($role_filter !== ''){
    $where  .= " AND role=?";
    $params[] = $role_filter;
    $types   .= "s";
}

$stmt = $conn->prepare("SELECT * FROM users $where ORDER BY id DESC");
if(!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result();

$secs_r   = $conn->query("SELECT section_name FROM sections ORDER BY section_name");
$sections = [];
while($s = $secs_r->fetch_assoc()) $sections[] = $s['section_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - Users</title>
<link rel="stylesheet" href="admin.css">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
.filter-bar { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
.filter-bar input,
.filter-bar select {
    background: var(--bg);
    border: 1px solid var(--border);
    color: var(--text);
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 13px;
    font-family: 'DM Sans', sans-serif;
    outline: none;
}
.filter-bar input { width: 220px; }
.filter-bar input:focus,
.filter-bar select:focus { border-color: var(--accent); }
.filter-bar button {
    background: var(--accent);
    color: #000;
    border: none;
    padding: 8px 18px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    font-family: 'DM Sans', sans-serif;
}

.users-table { width:100%; border-collapse:collapse; }
.users-table th {
    background: var(--bg);
    color: var(--muted);
    font-size: 11px;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    padding: 10px 14px;
    text-align: left;
    border-bottom: 1px solid var(--border);
}
.users-table td {
    padding: 12px 14px;
    border-bottom: 1px solid var(--border);
    font-size: 13px;
    vertical-align: middle;
}
.users-table tr:last-child td { border-bottom: none; }
.users-table tr:hover td { background: var(--bg); }

.pill { font-size: 11px; padding: 2px 9px; border-radius: 20px; font-weight: 500; }
.pill-student { background: rgba(91,142,240,0.12); color: #5b8ef0; }
.pill-teacher { background: rgba(62,207,142,0.12); color: var(--accent2); }
.pill-admin   { background: rgba(240,180,41,0.12);  color: var(--accent); }
.pill-muted   { background: var(--border); color: var(--muted); }

.act-btn {
    font-size: 12px;
    padding: 5px 12px;
    border-radius: 7px;
    border: 1px solid var(--border);
    background: transparent;
    color: var(--muted);
    cursor: pointer;
    text-decoration: none;
    font-family: 'DM Sans', sans-serif;
    transition: all 0.15s;
    display: inline-block;
}
.act-btn:hover        { border-color: #444; color: var(--text); }
.act-btn.danger:hover { border-color: var(--accent3); color: var(--accent3); }

.alert { padding: 10px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; }
.alert-green { background: rgba(62,207,142,0.1); border: 1px solid rgba(62,207,142,0.3); color: var(--accent2); }
.alert-red   { background: rgba(226,92,92,0.1);  border: 1px solid rgba(226,92,92,0.3);  color: var(--accent3); }

.modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,0.6); z-index: 999;
    justify-content: center; align-items: center;
}
.modal-overlay.open { display: flex; }
.modal {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 28px;
    width: 320px;
}
.modal h3 { font-family: 'Syne', sans-serif; font-size: 16px; font-weight: 700; margin-bottom: 6px; }
.modal p  { font-size: 13px; color: var(--muted); margin-bottom: 16px; }
.modal select,
.modal input {
    width: 100%;
    background: var(--bg);
    border: 1px solid var(--border);
    color: var(--text);
    padding: 10px 12px;
    border-radius: 8px;
    font-size: 14px;
    font-family: 'DM Sans', sans-serif;
    box-sizing: border-box;
    outline: none;
    margin-bottom: 12px;
}
.modal select:focus, .modal input:focus { border-color: var(--accent); }
.modal select option { background: var(--surface); }
.modal-btns { display: flex; gap: 8px; }
.modal-btns button,
.modal-btns .btn-save {
    flex: 1; padding: 9px; border-radius: 8px;
    font-size: 13px; cursor: pointer; font-family: 'DM Sans', sans-serif;
    border: 1px solid var(--border);
}
.btn-cancel { background: transparent; color: var(--muted); }
.btn-cancel:hover { color: var(--text); }
.btn-save { background: var(--accent); color: #000; border-color: var(--accent); font-weight: 700; }
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
        <a href="admin_users.php" class="active"><span class="icon">◉</span> Users</a>
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
        <a href="admin_schedules.php"><span class="icon">▦</span> Schedules</a>
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
                <h1>Users</h1>
                <p>Manage students, teachers, and admins</p>
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

    <?php if($msg === 'deleted'): ?>
    <div class="alert alert-red">User deleted.</div>
    <?php elseif($msg === 'updated'): ?>
    <div class="alert alert-green">Section updated.</div>
    <?php endif; ?>

    <div class="panel">
        <div class="panel-header">
            <h3>All Users</h3>
            <span class="badge badge-yellow"><?php echo $users->num_rows; ?> results</span>
        </div>

        <form method="GET" class="filter-bar">
            <input name="search" placeholder="Search name, username, email..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="role">
                <option value="">All roles</option>
                <option value="student" <?php echo $role_filter==='student'?'selected':''; ?>>Students</option>
                <option value="teacher" <?php echo $role_filter==='teacher'?'selected':''; ?>>Teachers</option>
                <option value="admin"   <?php echo $role_filter==='admin'  ?'selected':''; ?>>Admins</option>
            </select>
            <button type="submit">Filter</button>
        </form>

        <table class="users-table">
            <tr>
                <th>User</th>
                <th>Student ID</th>
                <th>Email</th>
                <th>Role</th>
                <th>Section</th>
                <th>Actions</th>
            </tr>
            <?php while($u = $users->fetch_assoc()): ?>
            <tr>
                <td>
                    <div class="user-row" style="padding:0; border:none;">
                        <div class="user-avatar ua-<?php echo $u['role']; ?>"><?php echo strtoupper(substr($u['fullname'],0,1)); ?></div>
                        <div class="user-info">
                            <div class="name"><?php echo htmlspecialchars($u['fullname']); ?></div>
                            <div class="uname">@<?php echo htmlspecialchars($u['username']); ?></div>
                        </div>
                    </div>
                </td>
                <td style="color:var(--muted); font-family:monospace; font-size:13px;"><?php echo htmlspecialchars($u['student_id_no'] ?? '—'); ?></td>
                <td style="color:var(--muted);"><?php echo htmlspecialchars($u['email'] ?? '—'); ?></td>
                <td><span class="pill pill-<?php echo $u['role']; ?>"><?php echo $u['role']; ?></span></td>
                <td>
                    <?php if($u['section']): ?>
                        <span style="color:var(--accent); font-size:13px;"><?php echo htmlspecialchars($u['section']); ?></span>
                    <?php else: ?>
                        <span style="color:var(--border);">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex; gap:6px;">
                        <?php if($u['role']==='student'): ?>
                        <button class="act-btn" onclick="openModal(<?php echo $u['id']; ?>,'<?php echo htmlspecialchars($u['fullname']); ?>','<?php echo htmlspecialchars($u['section']??''); ?>')">Assign Section</button>
                        <?php endif; ?>
                        <?php if($u['id'] != $_SESSION['id']): ?>
                        <button class="act-btn" onclick="openResetModal(<?php echo $u['id']; ?>,'<?php echo htmlspecialchars(addslashes($u['fullname'])); ?>')">Reset Password</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete <?php echo htmlspecialchars(addslashes($u['fullname'])); ?>? This cannot be undone.')">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <input type="hidden" name="delete_user" value="<?php echo $u['id']; ?>">
                            <button type="submit" class="act-btn danger">Delete</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

<div class="modal-overlay" id="modal">
    <div class="modal">
        <h3>Assign Section</h3>
        <p id="modal-name"></p>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="user_id" id="modal-uid">
            <select name="section" id="modal-select">
                <option value="">No section</option>
                <?php foreach($sections as $sec): ?>
                <option value="<?php echo htmlspecialchars($sec); ?>"><?php echo htmlspecialchars($sec); ?></option>
                <?php endforeach; ?>
                <option value="__custom">Enter manually...</option>
            </select>
            <input type="text" name="section" id="modal-custom" placeholder="ICT-11B" style="display:none;">
            <div class="modal-btns">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" name="assign_section" class="btn-save">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal-overlay" id="resetModal">
    <div class="modal">
        <h3>Reset Password</h3>
        <p id="reset-modal-name" style="font-size:13px; color:var(--muted); margin-bottom:16px;"></p>
        <div id="resetSuccessMsg" style="display:none; background:rgba(62,207,142,0.1); border:1px solid rgba(62,207,142,0.3); color:var(--accent2); border-radius:8px; padding:9px 12px; font-size:13px; margin-bottom:12px;"></div>
        <div id="resetErrorMsg"   style="display:none; background:rgba(226,92,92,0.1); border:1px solid rgba(226,92,92,0.3); color:var(--accent3); border-radius:8px; padding:9px 12px; font-size:13px; margin-bottom:12px;"></div>
        <input type="hidden" id="reset-uid">
        <div style="margin-bottom:10px;">
            <label style="font-size:12px; color:var(--muted); display:block; margin-bottom:5px; text-transform:uppercase; letter-spacing:.05em;">Temporary Password</label>
            <input type="text" id="reset-temp-pw" placeholder="Min 8 characters"
                style="width:100%; background:var(--bg); border:1px solid var(--border); color:var(--text);
                       padding:10px 12px; border-radius:8px; font-size:14px; font-family:sans-serif;
                       box-sizing:border-box; outline:none;">
        </div>
        <p style="font-size:11px; color:var(--muted); margin-bottom:16px;">
            The user will be required to change this password on their next login.
        </p>
        <div class="modal-btns">
            <button type="button" class="btn-cancel" onclick="closeResetModal()">Cancel</button>
            <button type="button" class="btn-save" id="resetSaveBtn" onclick="submitReset()">Reset</button>
        </div>
    </div>
</div>

<script>
function openModal(id, name, section){
    document.getElementById('modal-uid').value = id;
    document.getElementById('modal-name').textContent = name;
    const sel = document.getElementById('modal-select');
    for(let o of sel.options) if(o.value === section) o.selected = true;
    document.getElementById('modal').classList.add('open');
}
function closeModal(){
    document.getElementById('modal').classList.remove('open');
}
document.getElementById('modal-select').addEventListener('change', function(){
    const custom = document.getElementById('modal-custom');
    if(this.value === '__custom'){
        custom.style.display = 'block';
        custom.required = true;
        this.name = '';
    } else {
        custom.style.display = 'none';
        custom.required = false;
        this.name = 'section';
    }
});
document.getElementById('modal').addEventListener('click', function(e){
    if(e.target === this) closeModal();
});

function openResetModal(id, name) {
    document.getElementById('reset-uid').value          = id;
    document.getElementById('reset-modal-name').textContent = 'User: ' + name;
    document.getElementById('reset-temp-pw').value      = '';
    document.getElementById('resetSuccessMsg').style.display = 'none';
    document.getElementById('resetErrorMsg').style.display   = 'none';
    document.getElementById('resetModal').classList.add('open');
}
function closeResetModal() {
    document.getElementById('resetModal').classList.remove('open');
}
document.getElementById('resetModal').addEventListener('click', function(e){
    if (e.target === this) closeResetModal();
});

function submitReset() {
    const uid    = document.getElementById('reset-uid').value;
    const tempPw = document.getElementById('reset-temp-pw').value.trim();
    const sucEl  = document.getElementById('resetSuccessMsg');
    const errEl  = document.getElementById('resetErrorMsg');
    const btn    = document.getElementById('resetSaveBtn');

    sucEl.style.display = 'none';
    errEl.style.display = 'none';

    if (tempPw.length < 8) {
        errEl.textContent = '✗ Temporary password must be at least 8 characters.';
        errEl.style.display = 'block'; return;
    }

    btn.disabled    = true;
    btn.textContent = 'Resetting…';

    fetch('admin_reset_password.php', {
        method: 'POST',
        body: new URLSearchParams({ user_id: uid, temp_password: tempPw })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            sucEl.textContent = '✓ ' + data.message;
            sucEl.style.display = 'block';
            document.getElementById('reset-temp-pw').value = '';
        } else {
            errEl.textContent = '✗ ' + data.message;
            errEl.style.display = 'block';
        }
    })
    .catch(() => {
        errEl.textContent = '✗ Network error. Please try again.';
        errEl.style.display = 'block';
    })
    .finally(() => {
        btn.disabled    = false;
        btn.textContent = 'Reset';
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
