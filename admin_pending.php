<?php
include 'db.php';
include 'session_config.php';
include_once __DIR__ . '/cache.php';
include_once __DIR__ . '/audit.php';

if (!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

$msg   = '';
$error = '';

// ── Approve ───────────────────────────────────────────────────────────────────
if (isset($_POST['approve'])) {
    csrf_verify();
    $pid     = (int)$_POST['pending_id'];
    $section = trim($_POST['section'] ?? '');
    // Strip spaces/dashes so admin can paste "100 362 900 001" or "100-362-900-001"
    $lrn     = preg_replace('/\D/', '', trim($_POST['lrn'] ?? ''));

    if ($lrn === '' || strlen($lrn) !== 12) {
        $error = "LRN is required and must be exactly 12 digits. Please check and try again.";
    } else {
        $row = $conn->prepare("SELECT * FROM pending_registrations WHERE id = ? AND status = 'pending'");
        $row->bind_param("i", $pid);
        $row->execute();
        $pending = $row->get_result()->fetch_assoc();

        if (!$pending) {
            $error = "Request not found or already processed.";
        } else {
            // Check username not taken — use fetch instead of store_result to avoid buffering the connection
            $uchk = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $uchk->bind_param("s", $pending['username']);
            $uchk->execute();
            $username_taken = $uchk->get_result()->fetch_assoc();
            $uchk->close();

            // Check LRN not already assigned to another student
            $lchk = $conn->prepare("SELECT id FROM users WHERE lrn = ?");
            $lchk->bind_param("s", $lrn);
            $lchk->execute();
            $lrn_taken = $lchk->get_result()->fetch_assoc();
            $lchk->close();

            if ($username_taken) {
                $error = "Username '{$pending['username']}' was already taken. Reject and ask the student to register again.";
            } elseif ($lrn_taken) {
                $error = "LRN $lrn is already assigned to another student. Please double-check the number.";
            } else {
                $sec_val       = $section !== '' ? $section : null;
                $sid_val       = !empty($pending['student_id_no']) ? $pending['student_id_no'] : null;
                $ins = $conn->prepare(
                    "INSERT INTO users (fullname, email, username, password, role, section, lrn, student_id_no)
                     VALUES (?, ?, ?, ?, 'student', ?, ?, ?)"
                );
                $ins->bind_param("sssssss", $pending['fullname'], $pending['email'], $pending['username'], $pending['password'], $sec_val, $lrn, $sid_val);

                if ($ins->execute()) {
                    $ins->close();
                    $del = $conn->prepare("DELETE FROM pending_registrations WHERE id = ?");
                    $del->bind_param("i", $pid);
                    $del->execute();
                    $del->close();
                    // Clear admin dashboard cache so new user shows up
        Cache::delete('admin_dashboard_stats');
        Cache::delete('admin_recent_users');
        Cache::delete('admin_section_breakdown');
        Cache::delete('admin_pending_count');
        audit_log($conn, 'USER_APPROVED', $pending['fullname'], 'LRN: ' . $lrn . ', Section: ' . $section);
        $msg = "'{$pending['fullname']}' approved. Account created with LRN $lrn.";
                } else {
                    $error = "Failed to create account: " . $conn->error;
                }
            }
        }
    }
}

// ── Reject ────────────────────────────────────────────────────────────────────
if (isset($_POST['reject'])) {
    csrf_verify();
    $pid = (int)$_POST['pending_id'];

    $row = $conn->prepare("SELECT fullname FROM pending_registrations WHERE id = ? AND status = 'pending'");
    $row->bind_param("i", $pid);
    $row->execute();
    $pending = $row->get_result()->fetch_assoc();

    if ($pending) {
        $del = $conn->prepare("UPDATE pending_registrations SET status = 'rejected' WHERE id = ?");
        $del->bind_param("i", $pid);
        $del->execute();
        $del->close();
        Cache::delete('admin_pending_count');
        audit_log($conn, 'USER_REJECTED', $pending['fullname'], 'Registration rejected');
        $msg = "'{$pending['fullname']}' registration rejected.";
    } else {
        $error = "Request not found.";
    }
}

// ── Check migration has been run ──────────────────────────────────────────────
$tbl_check = $conn->query("SHOW TABLES LIKE 'pending_registrations'");
if (!$tbl_check || $tbl_check->num_rows === 0) {
    // Table doesn't exist yet — migration not run
    die('<div style="font-family:sans-serif;padding:40px;color:#f87171;background:#0a0c10;min-height:100vh;">
        <h2 style="margin-bottom:12px;">&#9888; Migration required</h2>
        <p style="color:#9ca3af;">The <strong>pending_registrations</strong> table does not exist yet.</p>
        <p style="color:#9ca3af;margin-top:8px;">Please run <strong>migration_pending_registrations.sql</strong> in phpMyAdmin first, then reload this page.</p>
    </div>');
}

// ── Fetch pending list ─────────────────────────────────────────────────────────
$pending_q    = $conn->query("SELECT * FROM pending_registrations WHERE status = 'pending' ORDER BY submitted_at ASC");
$pending_list = $pending_q ? $pending_q->fetch_all(MYSQLI_ASSOC) : [];

$pending_count = count($pending_list);

// ── Sections for assign dropdown ───────────────────────────────────────────────
$secs_r   = $conn->query("SELECT section_name FROM sections ORDER BY section_name");
$sections = [];
if ($secs_r) while ($s = $secs_r->fetch_assoc()) $sections[] = $s['section_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Pending Registrations</title>
<link rel="stylesheet" href="admin.css">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
.pending-table { width:100%; border-collapse:collapse; }
.pending-table th {
    background: var(--bg); color: var(--muted);
    font-size: 11px; letter-spacing:.07em; text-transform:uppercase;
    padding: 10px 14px; text-align:left; border-bottom:1px solid var(--border);
}
.pending-table td {
    padding: 14px; border-bottom:1px solid var(--border);
    font-size: 13px; vertical-align: middle;
}
.pending-table tr:last-child td { border-bottom: none; }
.pending-table tr:hover td { background: var(--bg); }

.act-btn {
    font-size:12px; padding:5px 14px; border-radius:7px;
    border:1px solid var(--border); background:transparent;
    color:var(--muted); cursor:pointer; font-family:'DM Sans',sans-serif;
    transition:all .15s; display:inline-block;
}
.act-btn.approve { border-color:rgba(62,207,142,.4); color:var(--accent2); }
.act-btn.approve:hover { background:rgba(62,207,142,.08); }
.act-btn.reject  { border-color:rgba(226,92,92,.4); color:var(--accent3); }
.act-btn.reject:hover  { background:rgba(226,92,92,.08); }

.alert { padding:10px 16px; border-radius:8px; font-size:13px; margin-bottom:20px; }
.alert-green { background:rgba(62,207,142,.1);  border:1px solid rgba(62,207,142,.3); color:var(--accent2); }
.alert-red   { background:rgba(226,92,92,.1);   border:1px solid rgba(226,92,92,.3);  color:var(--accent3); }

.empty-state { color:var(--muted); font-size:14px; padding:32px 0; text-align:center; }

.section-select {
    background:var(--bg); border:1px solid var(--border); color:var(--text);
    padding:6px 10px; border-radius:7px; font-size:12px;
    font-family:'DM Sans',sans-serif; outline:none; min-width:140px;
}
.section-select:focus { border-color:var(--accent); }

.time-ago { font-size:11px; color:var(--muted); }
.badge-pending {
    display:inline-block; background:rgba(240,180,41,.15);
    color:var(--accent); font-size:11px; font-weight:700;
    padding:2px 9px; border-radius:20px; letter-spacing:.04em;
}
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
        <a href="admin_pending.php" class="active">
            <span class="icon">◎</span> Pending
            <?php if($pending_count > 0): ?>
            <span style="background:#f0b429;color:#000;font-size:10px;font-weight:800;padding:1px 7px;border-radius:20px;margin-left:6px;"><?php echo $pending_count; ?></span>
            <?php endif; ?>
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
                <h1>Pending Registrations</h1>
                <p>Review and approve student account requests</p>
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

    <?php if ($msg): ?>
    <div class="alert alert-green"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-red"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="panel">
        <div class="panel-header">
            <h3>Awaiting Approval</h3>
            <span class="badge badge-yellow"><?php echo $pending_count; ?> pending</span>
        </div>

        <?php if (empty($pending_list)): ?>
        <div class="empty-state">
            ✓ No pending registrations. You're all caught up.
        </div>
        <?php else: ?>
        <table class="pending-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Submitted</th>
                    <th>Student ID</th>
                    <th>Assign Section</th>
                    <th>LRN <span style="font-weight:400;color:var(--accent3);font-size:10px;">required</span></th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pending_list as $p):
                $submitted = new DateTime($p['submitted_at']);
                $now       = new DateTime();
                $diff      = $now->diff($submitted);
                if ($diff->days > 0)       $ago = $diff->days . 'd ago';
                elseif ($diff->h > 0)      $ago = $diff->h . 'h ago';
                elseif ($diff->i > 0)      $ago = $diff->i . 'm ago';
                else                       $ago = 'Just now';
            ?>
            <tr>
                <td>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div class="user-avatar ua-student"><?php echo strtoupper(substr($p['fullname'],0,1)); ?></div>
                        <div>
                            <div style="font-weight:500;"><?php echo htmlspecialchars($p['fullname']); ?></div>
                            <span class="badge-pending">pending</span>
                        </div>
                    </div>
                </td>
                <td style="color:var(--muted);"><?php echo htmlspecialchars($p['email']); ?></td>
                <td style="color:var(--muted);">@<?php echo htmlspecialchars($p['username']); ?></td>
                <td><span class="time-ago"><?php echo $ago; ?></span></td>
                <td style="color:var(--muted); font-family:monospace; font-size:13px;">
                    <?php echo htmlspecialchars($p['student_id_no'] ?? '—'); ?>
                </td>
                <td>
                    <!-- Section dropdown lives inside the approve form -->
                    <select class="section-select" id="sec_<?php echo $p['id']; ?>">
                        <option value="">No section yet</option>
                        <?php foreach ($sections as $sec): ?>
                        <option value="<?php echo htmlspecialchars($sec); ?>"><?php echo htmlspecialchars($sec); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <!-- LRN input — paste-friendly: spaces and dashes are stripped server-side -->
                    <input
                        type="text"
                        id="lrn_<?php echo $p['id']; ?>"
                        value="<?php echo htmlspecialchars($p['lrn'] ?? ''); ?>"
                        placeholder="e.g. 100362900001"
                        maxlength="14"
                        class="section-select"
                        style="width:148px; letter-spacing:0.04em; font-family:monospace;"
                        oninput="lrnLive(this)"
                        title="Paste or type the 12-digit LRN. Spaces and dashes are ignored."
                    >
                    <div id="lrn_hint_<?php echo $p['id']; ?>" style="font-size:11px;margin-top:3px;color:var(--muted);"></div>
                </td>
                <td>
                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                        <!-- Approve -->
                        <form method="POST" style="display:inline;"
                              onsubmit="
                                document.getElementById('approve_sec_<?php echo $p['id']; ?>').value = document.getElementById('sec_<?php echo $p['id']; ?>').value;
                                document.getElementById('approve_lrn_<?php echo $p['id']; ?>').value = document.getElementById('lrn_<?php echo $p['id']; ?>').value;
                              ">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <input type="hidden" name="pending_id" value="<?php echo $p['id']; ?>">
                            <input type="hidden" name="section" id="approve_sec_<?php echo $p['id']; ?>">
                            <input type="hidden" name="lrn"     id="approve_lrn_<?php echo $p['id']; ?>">
                            <button type="submit" name="approve" class="act-btn approve">Approve</button>
                        </form>
                        <!-- Reject -->
                        <form method="POST" style="display:inline;"
                              onsubmit="return confirm('Reject registration for <?php echo htmlspecialchars(addslashes($p['fullname'])); ?>?')">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <input type="hidden" name="pending_id" value="<?php echo $p['id']; ?>">
                            <button type="submit" name="reject" class="act-btn reject">Reject</button>
                        </form>
                    </div>
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

function lrnLive(input) {
    // Strip everything that isn't a digit for the live count
    const digits = input.value.replace(/\D/g, '');
    const id     = input.id.replace('lrn_', '');
    const hint   = document.getElementById('lrn_hint_' + id);
    if (!hint) return;
    if (digits.length === 0) {
        hint.textContent = '';
        input.style.borderColor = '';
    } else if (digits.length === 12) {
        hint.textContent = '✓ 12 digits';
        hint.style.color = 'var(--accent2)';
        input.style.borderColor = 'var(--accent2)';
    } else {
        hint.textContent = digits.length + ' / 12 digits';
        hint.style.color = 'var(--accent3)';
        input.style.borderColor = 'var(--accent3)';
    }
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