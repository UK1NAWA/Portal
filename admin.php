<?php
include 'db.php';
include 'session_config.php';

if(!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.html");
    exit();
}

include_once __DIR__ . '/cache.php';

// Handle cache actions
$cache_msg = '';
if(isset($_POST['clear_cache'])){
    csrf_verify();
    Cache::flush();
    $cache_msg = 'All cache cleared.';
}
if(isset($_POST['gc_cache'])){
    csrf_verify();
    $deleted = Cache::gc();
    $cache_msg = "Garbage collected: $deleted expired file(s) removed.";
}

// Run gc automatically ~1 in every 50 admin dashboard loads to keep cache folder tidy
if(rand(1, 50) === 1) Cache::gc();

$stats = Cache::get('admin_dashboard_stats');
if($stats === null){
    $stats = [
        'total_users'         => (int)$conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'],
        'total_teachers'      => (int)$conn->query("SELECT COUNT(*) as c FROM users WHERE role='teacher'")->fetch_assoc()['c'],
        'total_students'      => (int)$conn->query("SELECT COUNT(*) as c FROM users WHERE role='student'")->fetch_assoc()['c'],
        'total_sections'      => (int)$conn->query("SELECT COUNT(*) as c FROM sections")->fetch_assoc()['c'],
        'published_schedules' => (int)$conn->query("SELECT COUNT(*) as c FROM section_schedules WHERE status='published'")->fetch_assoc()['c'],
    ];
    Cache::set('admin_dashboard_stats', $stats, 60);
}
$total_users         = $stats['total_users'];
$total_teachers      = $stats['total_teachers'];
$total_students      = $stats['total_students'];
$total_sections      = $stats['total_sections'];
$published_schedules = $stats['published_schedules'];

// Pending count — short cache (30s) since it's action-critical
$pending_reg_count = Cache::get('admin_pending_count');
if($pending_reg_count === null){
    $pending_reg_count = (int)$conn->query("SELECT COUNT(*) as c FROM pending_registrations WHERE status='pending'")->fetch_assoc()['c'];
    Cache::set('admin_pending_count', $pending_reg_count, 30);
}

// Recent users — cache 60s
$recent_users_data = Cache::get('admin_recent_users');
if($recent_users_data === null){
    $r = $conn->query("SELECT fullname, username, role FROM users ORDER BY id DESC LIMIT 5");
    $recent_users_data = $r->fetch_all(MYSQLI_ASSOC);
    Cache::set('admin_recent_users', $recent_users_data, 60);
}

// Section breakdown — cache 60s
$section_rows = Cache::get('admin_section_breakdown');
if($section_rows === null){
    $r2 = $conn->query(
        "SELECT s.section_name, COUNT(u.id) as count
         FROM sections s
         LEFT JOIN users u ON u.section = s.section_name AND u.role = 'student'
         GROUP BY s.section_name
         ORDER BY count DESC"
    );
    $section_rows = $r2->fetch_all(MYSQLI_ASSOC);
    Cache::set('admin_section_breakdown', $section_rows, 60);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="admin.css">
</head>
<body>
<script>
if(localStorage.getItem('adminTheme')==='light') document.body.classList.add('light-mode');
</script>

<!-- SIDEBAR OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
    <img src="logo.png" alt="BCT Logo" style="width:140px;height:140px;border-radius:10%;display:block;margin:0 auto 10px;">

<!-- SIDEBAR -->
<div class="sidebar" id="adminSidebar">
    <div class="sidebar-brand">
    <img src="logo.png" alt="BCT Logo" style="width:140px;height:140px;border-radius:10%;display:block;margin:0 auto 10px;">

    </div>
    <nav class="sidebar-nav">
        <a href="admin.php" class="active">Dashboard</a>
        <a href="admin_users.php">Users</a>
        <a href="admin_pending.php">
            Pending
            <?php if($pending_reg_count > 0): ?>
            <span style="background:var(--accent);color:#000;font-size:10px;font-weight:800;padding:1px 7px;border-radius:20px;margin-left:6px;"><?php echo $pending_reg_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="admin_sections.php">Sections</a>
        <a href="admin_timeslots.php">Time Slots</a>
        <a href="admin_schedules.php">Schedules</a>
        <a href="admin_grades.php">Grades</a>
        <a href="admin_audit.php">Audit Log</a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="main">

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="topbar-left" style="display:flex;align-items:center;gap:12px;">
            <button class="hamburger-btn" onclick="toggleSidebar()" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
            <div>
                <h1>Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['fullname']); ?></p>
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

    <!-- STAT CARDS -->
    <div class="stats-grid">
        <div class="stat-card yellow">
            <div class="stat-number"><?php echo $total_users; ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-card green">
            <div class="stat-number"><?php echo $total_teachers; ?></div>
            <div class="stat-label">Teachers</div>
        </div>
        <div class="stat-card blue">
            <div class="stat-number"><?php echo $total_students; ?></div>
            <div class="stat-label">Students</div>
        </div>
        <a href="admin_pending.php" style="text-decoration:none;">
        <div class="stat-card red" style="cursor:pointer;">
            <div class="stat-number"><?php echo $pending_reg_count; ?></div>
            <div class="stat-label">Pending Registrations</div>
        </div>
        </a>
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_sections; ?></div>
            <div class="stat-label">Sections</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $published_schedules; ?></div>
            <div class="stat-label">Published Schedules</div>
        </div>
    </div>

    <!-- BOTTOM GRID -->
    <div class="bottom-grid">

        <!-- RECENT USERS -->
        <div class="panel">
            <div class="panel-header">
                <h3>Recent Users</h3>
                <span class="badge badge-yellow">Latest 5</span>
            </div>
            <?php foreach($recent_users_data as $u):
                $pillClass = 'pill-' . $u['role'];
            ?>
            <div class="user-row">
                <div class="user-info">
                    <div class="name"><?php echo htmlspecialchars($u['fullname']); ?></div>
                    <div class="uname">@<?php echo htmlspecialchars($u['username']); ?></div>
                </div>
                <span class="role-pill <?php echo $pillClass; ?>"><?php echo $u['role']; ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- STUDENTS BY SECTION -->
        <div class="panel">
            <div class="panel-header">
                <h3>Students by Section</h3>
                <span class="badge badge-green"><?php echo $total_sections; ?> sections</span>
            </div>
            <?php
            $rows = $section_rows;
            $max  = max(1, max(array_column($rows, 'count') ?: [1]));
            if(!empty($rows)):
                foreach($rows as $row):
                    $pct = round(($row['count'] / $max) * 100);
            ?>
            <div class="section-row">
                <div class="section-meta">
                    <span class="name"><?php echo htmlspecialchars($row['section_name']); ?></span>
                    <span class="count"><?php echo $row['count']; ?> student<?php echo $row['count'] != 1 ? 's' : ''; ?></span>
                </div>
                <div class="bar-track">
                    <div class="bar-fill" style="width:<?php echo $pct; ?>%"></div>
                </div>
            </div>
            <?php endforeach; else: ?>
            <p style="color:var(--muted);font-size:13px;text-align:center;padding:20px 0;">No sections created yet.</p>
            <?php endif; ?>
        </div>

    </div>

    <!-- CACHE MANAGEMENT -->
    <?php $cache_stats = Cache::stats(); ?>
    <div class="panel" style="margin-top:20px;">
        <div class="panel-header">
            <h3>Cache</h3>
            <span class="badge badge-yellow"><?php echo $cache_stats['count']; ?> files &middot; <?php echo $cache_stats['size_kb']; ?> KB</span>
        </div>
        <?php if($cache_msg): ?>
        <div style="background:rgba(62,207,142,0.1); border:1px solid rgba(62,207,142,0.3); color:var(--accent2); border-radius:8px; padding:9px 14px; font-size:13px; margin-bottom:14px;">
            <?php echo htmlspecialchars($cache_msg); ?>
        </div>
        <?php endif; ?>
        <p style="font-size:13px; color:var(--muted); margin-bottom:14px;">
            Cache speeds up pages by storing query results temporarily. Clear it if data looks outdated, or run garbage collection to remove only expired files.
        </p>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <button type="submit" name="gc_cache" class="act-btn">Remove expired files</button>
            </form>
            <form method="POST" onsubmit="return confirm('Clear all cache? Pages will be slightly slower until cache rebuilds.')">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <button type="submit" name="clear_cache" class="act-btn danger">Clear all cache</button>
            </form>
        </div>
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
