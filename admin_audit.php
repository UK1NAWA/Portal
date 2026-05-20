<?php
include 'db.php';
include 'session_config.php';

if(!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.html");
    exit();
}

// Filters
$filter_action = trim($_GET['action'] ?? '');
$filter_user   = trim($_GET['user']   ?? '');
$filter_date   = trim($_GET['date']   ?? '');
$page_num      = max(1, (int)($_GET['p'] ?? 1));
$per_page      = 50;
$offset        = ($page_num - 1) * $per_page;

// Build WHERE clause
$where  = "WHERE 1=1";
$params = [];
$types  = "";

if($filter_action !== ''){
    $where   .= " AND al.action = ?";
    $params[] = $filter_action;
    $types   .= "s";
}
if($filter_user !== ''){
    $where   .= " AND (al.username LIKE ? OR al.role = ?)";
    $like     = "%$filter_user%";
    $params[] = $like;
    $params[] = $filter_user;
    $types   .= "ss";
}
if($filter_date !== ''){
    $where   .= " AND DATE(al.created_at) = ?";
    $params[] = $filter_date;
    $types   .= "s";
}

// Count total
$count_stmt = $conn->prepare("SELECT COUNT(*) as c FROM audit_log al LEFT JOIN users u ON u.username = al.username $where");
if(!empty($params)) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()['c'];
$total_pages = ceil($total_rows / $per_page);

// Fetch logs
$log_stmt = $conn->prepare(
    "SELECT al.id, al.username, al.role, al.action, al.target, al.created_at,
            u.fullname
     FROM audit_log al
     LEFT JOIN users u ON u.username = al.username
     $where
     ORDER BY al.created_at DESC
     LIMIT ? OFFSET ?"
);
$params[] = $per_page;
$params[] = $offset;
$types   .= "ii";
$log_stmt->bind_param($types, ...$params);
$log_stmt->execute();
$logs = $log_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get distinct actions for filter dropdown
$actions_q = $conn->query("SELECT DISTINCT action FROM audit_log ORDER BY action");
$all_actions = $actions_q->fetch_all(MYSQLI_ASSOC);

// Action color map
$action_colors = [
    'LOGIN'                  => '#3ecf8e',
    'LOGIN_FAILED'           => '#e25c5c',
    'LOGOUT'                 => '#6b7280',
    'USER_APPROVED'          => '#3ecf8e',
    'USER_REJECTED'          => '#e25c5c',
    'USER_SECTION_ASSIGNED'  => '#f0b429',
    'GRADE_UPLOAD'           => '#3ecf8e',
    'GRADE_DELETED'          => '#e25c5c',
    'ANNOUNCEMENT_POSTED'    => '#f0b429',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Audit Log — Admin</title>
<link rel="stylesheet" href="admin.css">
<style>
.filter-bar { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:24px; align-items:center; }
.filter-bar select,
.filter-bar input[type="date"],
.filter-bar input[type="text"] {
    background: var(--surface); border: 1px solid var(--border);
    color: var(--text); padding: 8px 12px; border-radius: 8px;
    font-size: 13px; font-family: sans-serif; outline: none;
}
.filter-bar select:focus,
.filter-bar input:focus { border-color: var(--accent); }
.filter-bar button {
    background: var(--accent); color: #000; border: none;
    padding: 8px 18px; border-radius: 8px;
    font-weight: 700; font-size: 13px; cursor: pointer;
    font-family: sans-serif;
}
.filter-bar a.clear-btn {
    font-size: 13px; color: var(--muted); text-decoration: none;
    padding: 8px 14px; border: 1px solid var(--border); border-radius: 8px;
}
.filter-bar a.clear-btn:hover { color: var(--text); border-color: #444; }
.log-table { width:100%; border-collapse:collapse; font-size:13px; }
.log-table th {
    background: var(--bg); color: var(--muted);
    font-size: 11px; letter-spacing: .07em; text-transform: uppercase;
    padding: 10px 14px; text-align: left; border-bottom: 1px solid var(--border);
}
.log-table td { padding: 11px 14px; border-bottom: 1px solid var(--border); vertical-align: middle; }
.log-table tr:last-child td { border-bottom: none; }
.log-table tr:hover td { background: var(--bg); }
.action-pill {
    font-size: 11px; font-weight: 600; padding: 2px 8px;
    border-radius: 20px; white-space: nowrap;
}
.pagination { display:flex; gap:6px; margin-top:20px; flex-wrap:wrap; }
.pagination a, .pagination span {
    padding: 6px 13px; border-radius: 7px; font-size: 13px;
    border: 1px solid var(--border); text-decoration: none; color: var(--muted);
}
.pagination a:hover { border-color: #444; color: var(--text); }
.pagination .current { background: var(--accent); color: #000; border-color: var(--accent); font-weight: 700; }
.empty-state { color: var(--muted); font-size:14px; padding: 40px 0; text-align: center; }
.summary-chips { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
.chip { background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 8px 16px; font-size: 13px; }
.chip strong { display:block; font-size:20px; font-weight:800; color:var(--text); }
.chip span { color: var(--muted); font-size:11px; }
body.light-mode .filter-bar select,
body.light-mode .filter-bar input { background: #f7f8fb; border-color: #d1d5db; color: #111318; }
body.light-mode .log-table th { background: #f7f8fb; }
body.light-mode .log-table tr:hover td { background: #f7f8fb; }
body.light-mode .chip { background: #fff; border-color: #e2e5ec; }
</style>
</head>
<body>
<script>if(localStorage.getItem('adminTheme')==='light') document.body.classList.add('light-mode');</script>

<!-- SIDEBAR OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- SIDEBAR -->
<div class="sidebar" id="adminSidebar">
    <div class="sidebar-brand">
    <img src="logo.png" alt="BCT Logo" style="width:140px;height:140px;border-radius:10%;display:block;margin:0 auto 10px;">

    </div>
    <nav class="sidebar-nav">
        <a href="admin.php">Dashboard</a>
        <a href="admin_users.php">Users</a>
        <a href="admin_pending.php">Pending</a>
        <a href="admin_sections.php">Sections</a>
        <a href="admin_schedules.php">Schedules</a>
        <a href="admin_grades.php">Grades</a>
        <a href="admin_audit.php" class="active">Audit Log</a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <div class="topbar-left" style="display:flex;align-items:center;gap:12px;">
            <button class="hamburger-btn" onclick="toggleSidebar()" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
            <div>
                <h1>Audit Log</h1>
                <p>Track all system actions and user activity</p>
            </div>
        </div>
        <div class="topbar-right">
            <button class="theme-toggle-btn" onclick="toggleTheme()">
                <span class="tog-track"><span class="tog-thumb"></span></span>
                <span id="themeLabel">Light</span>
            </button>
        </div>
    </div>

    <!-- SUMMARY CHIPS -->
    <?php
    $today_count  = $conn->query("SELECT COUNT(*) as c FROM audit_log WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['c'];
    $failed_logins= $conn->query("SELECT COUNT(*) as c FROM audit_log WHERE action='LOGIN_FAILED' AND DATE(created_at) = CURDATE()")->fetch_assoc()['c'];
    $total_count  = $conn->query("SELECT COUNT(*) as c FROM audit_log")->fetch_assoc()['c'];
    ?>
    <div class="summary-chips">
        <div class="chip"><strong><?php echo $total_count; ?></strong><span>Total Events</span></div>
        <div class="chip"><strong><?php echo $today_count; ?></strong><span>Today's Events</span></div>
        <div class="chip"><strong style="color:<?php echo $failed_logins > 0 ? '#e25c5c' : 'var(--text)'; ?>;"><?php echo $failed_logins; ?></strong><span>Failed Logins Today</span></div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h3>Activity Log</h3>
            <span class="badge badge-yellow"><?php echo number_format($total_rows); ?> records</span>
        </div>

        <!-- FILTERS -->
        <form method="GET" class="filter-bar">
            <select name="action">
                <option value="">All Actions</option>
                <?php foreach($all_actions as $a): ?>
                <option value="<?php echo $a['action']; ?>" <?php echo $filter_action === $a['action'] ? 'selected' : ''; ?>>
                    <?php echo $a['action']; ?>
                </option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="user" placeholder="Username or role" value="<?php echo htmlspecialchars($filter_user); ?>">
            <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>">
            <button type="submit">Filter</button>
            <?php if($filter_action || $filter_user || $filter_date): ?>
            <a href="admin_audit.php" class="clear-btn">Clear</a>
            <?php endif; ?>
        </form>

        <?php if(empty($logs)): ?>
        <p class="empty-state">No log entries found<?php echo ($filter_action || $filter_user || $filter_date) ? ' for the selected filters' : ' yet'; ?>.</p>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table class="log-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>FullName</th>
                    <th>Role</th>
                    <th>Action</th>
                    <th>Target</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($logs as $log):
                $color = $action_colors[$log['action']] ?? '#6b7280';
            ?>
            <tr>
                <td style="white-space:nowrap; color:var(--muted); font-size:12px;">
                    <?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?>
                </td>
                <td><?php echo htmlspecialchars($log['fullname'] ?? $log['username'] ?? '—'); ?></td>
                <td style="color:var(--muted); font-size:12px;"><?php echo htmlspecialchars($log['role'] ?? '—'); ?></td>
                <td>
                    <span class="action-pill" style="background:<?php echo $color; ?>22; color:<?php echo $color; ?>;">
                        <?php echo htmlspecialchars($log['action']); ?>
                    </span>
                </td>
                <td style="max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    <?php echo htmlspecialchars($log['target'] ?? '—'); ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <!-- PAGINATION -->
        <?php if($total_pages > 1): ?>
        <div class="pagination">
            <?php if($page_num > 1): ?>
            <a href="?p=<?php echo $page_num-1; ?>&action=<?php echo urlencode($filter_action); ?>&user=<?php echo urlencode($filter_user); ?>&date=<?php echo urlencode($filter_date); ?>">&larr; Prev</a>
            <?php endif; ?>

            <?php for($i = max(1,$page_num-2); $i <= min($total_pages,$page_num+2); $i++): ?>
            <?php if($i === $page_num): ?>
            <span class="current"><?php echo $i; ?></span>
            <?php else: ?>
            <a href="?p=<?php echo $i; ?>&action=<?php echo urlencode($filter_action); ?>&user=<?php echo urlencode($filter_user); ?>&date=<?php echo urlencode($filter_date); ?>"><?php echo $i; ?></a>
            <?php endif; ?>
            <?php endfor; ?>

            <?php if($page_num < $total_pages): ?>
            <a href="?p=<?php echo $page_num+1; ?>&action=<?php echo urlencode($filter_action); ?>&user=<?php echo urlencode($filter_user); ?>&date=<?php echo urlencode($filter_date); ?>">Next &rarr;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

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
