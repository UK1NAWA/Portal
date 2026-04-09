<?php
include 'db.php';
include 'session_config.php';
include_once __DIR__ . '/audit.php';
include_once __DIR__ . '/cache.php';

if (!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

$msg   = '';
$error = '';

// ── GET CURRENT SCHOOL YEAR FROM grading_periods ────────────
$sy_row  = $conn->query("SELECT school_year FROM grading_periods LIMIT 1")->fetch_assoc();
$current_sy = $sy_row['school_year'] ?? date('Y') . '-' . (date('Y') + 1);

// ── ARCHIVE END-OF-YEAR ──────────────────────────────────────
if (isset($_POST['run_archive'])) {
    csrf_verify();

    $sy = trim($_POST['school_year']);
    if (!preg_match('/^\d{4}-\d{4}$/', $sy)) {
        $error = "Invalid school year format. Use YYYY-YYYY (e.g. 2025-2026).";
    } else {

        // Check archive tables exist
        $tbl_check = $conn->query("SHOW TABLES LIKE 'archived_grades'");
        if (!$tbl_check || $tbl_check->num_rows === 0) {
            $error = "Archive tables do not exist. Please run migration_archive.sql in phpMyAdmin first.";
        } else {

            // Check not already archived
            $already = $conn->prepare("SELECT COUNT(*) as c FROM archived_grades WHERE school_year=?");
            $already->bind_param("s", $sy);
            $already->execute();
            $already_count = (int)$already->get_result()->fetch_assoc()['c'];

            if ($already_count > 0) {
                $error = "School year $sy has already been archived ($already_count grade records found). To re-archive, manually delete those records from archived_grades first.";
            } else {

                // ── 1. Archive grades ──
                $grade_ins = $conn->prepare("
                    INSERT INTO archived_grades
                        (school_year, student_id, fullname, lrn, section, subject, quarter, grade, remarks)
                    SELECT ?, sg.student_id, u.fullname, u.lrn, sg.section, sg.subject, sg.quarter, sg.grade, sg.remarks
                    FROM student_grades sg
                    JOIN users u ON u.id = sg.student_id
                ");
                $grade_ins->bind_param("s", $sy);
                $grade_ins->execute();
                $grades_archived = $grade_ins->affected_rows;

                // ── 2. Archive attendance ──
                $att_ins = $conn->prepare("
                    INSERT INTO archived_attendance
                        (school_year, student_id, fullname, lrn, section, date, status)
                    SELECT ?, a.student_id, u.fullname, u.lrn, a.section, a.date, a.status
                    FROM attendance a
                    JOIN users u ON u.id = a.student_id
                ");
                $att_ins->bind_param("s", $sy);
                $att_ins->execute();
                $att_archived = $att_ins->affected_rows;

                // ── 3. Reset live tables ──
                $conn->query("DELETE FROM student_grades");
                $conn->query("DELETE FROM attendance");
                $conn->query("DELETE FROM grade_submissions");
                $conn->query("DELETE FROM tasks");
                $conn->query("DELETE FROM announcements");

                // ── 4. Reset grading periods for new school year ──
                [$y1, $y2] = explode('-', $sy);
                $new_sy = ($y1 + 1) . '-' . ($y2 + 1);
                $conn->prepare("UPDATE grading_periods SET school_year=?, is_open=0, opened_at=NULL")
                     ->bind_param("s", $new_sy)->execute();

                // ── 5. Clear all cache ──
                Cache::flush();

                audit_log($conn, 'YEAR_END_ARCHIVE', $sy,
                    "Grades: $grades_archived records, Attendance: $att_archived records archived. Live tables reset.");

                $msg = "School year $sy archived successfully. "
                     . "$grades_archived grade records and $att_archived attendance records saved. "
                     . "Live tables have been cleared and grading periods reset to $new_sy.";
            }
        }
    }
}

// ── LOAD ARCHIVE SUMMARY ─────────────────────────────────────
$archive_years = [];
$tbl_check2 = $conn->query("SHOW TABLES LIKE 'archived_grades'");
if ($tbl_check2 && $tbl_check2->num_rows > 0) {
    $rows = $conn->query("
        SELECT school_year,
               COUNT(*) as grade_records,
               COUNT(DISTINCT student_id) as students,
               MIN(archived_at) as archived_at
        FROM archived_grades
        GROUP BY school_year
        ORDER BY school_year DESC
    ");
    while ($r = $rows->fetch_assoc()) $archive_years[] = $r;
}

// ── EXPORT ARCHIVED GRADES AS CSV ───────────────────────────
if (isset($_GET['export_grades']) && preg_match('/^\d{4}-\d{4}$/', $_GET['export_grades'])) {
    $sy = $_GET['export_grades'];
    header('Content-Type: text/csv');
    header("Content-Disposition: attachment; filename=\"grades_archive_{$sy}.csv\"");
    echo "School Year,Student ID,Full Name,LRN,Section,Subject,Quarter,Grade,Remarks\n";
    $rows = $conn->prepare("SELECT * FROM archived_grades WHERE school_year=? ORDER BY section, fullname, subject, quarter");
    $rows->bind_param("s", $sy);
    $rows->execute();
    foreach ($rows->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
        echo implode(',', [
            $r['school_year'], $r['student_id'],
            '"' . str_replace('"', '""', $r['fullname']) . '"',
            $r['lrn'] ?? '',
            '"' . str_replace('"', '""', $r['section']) . '"',
            '"' . str_replace('"', '""', $r['subject']) . '"',
            $r['quarter'], $r['grade'], $r['remarks']
        ]) . "\n";
    }
    exit();
}

// ── EXPORT ARCHIVED ATTENDANCE AS CSV ───────────────────────
if (isset($_GET['export_attendance']) && preg_match('/^\d{4}-\d{4}$/', $_GET['export_attendance'])) {
    $sy = $_GET['export_attendance'];
    header('Content-Type: text/csv');
    header("Content-Disposition: attachment; filename=\"attendance_archive_{$sy}.csv\"");
    echo "School Year,Student ID,Full Name,LRN,Section,Date,Status\n";
    $rows = $conn->prepare("SELECT * FROM archived_attendance WHERE school_year=? ORDER BY section, fullname, date");
    $rows->bind_param("s", $sy);
    $rows->execute();
    foreach ($rows->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
        echo implode(',', [
            $r['school_year'], $r['student_id'],
            '"' . str_replace('"', '""', $r['fullname']) . '"',
            $r['lrn'] ?? '',
            '"' . str_replace('"', '""', $r['section']) . '"',
            $r['date'], $r['status']
        ]) . "\n";
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - Archive</title>
<link rel="stylesheet" href="admin.css">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
.warn-box {
    background: rgba(240,180,41,0.08);
    border: 1px solid rgba(240,180,41,0.35);
    border-radius: 10px;
    padding: 16px 18px;
    font-size: 13px;
    color: var(--text);
    margin-bottom: 20px;
    line-height: 1.7;
}
.warn-box strong { color: var(--accent); display: block; margin-bottom: 4px; font-size: 14px; }
.info-box {
    background: rgba(91,142,240,0.07);
    border: 1px solid rgba(91,142,240,0.25);
    border-radius: 10px;
    padding: 14px 18px;
    font-size: 13px;
    color: var(--muted);
    margin-bottom: 20px;
    line-height: 1.7;
}
.form-group { margin-bottom: 14px; }
.form-group label { display: block; font-size: 12px; color: var(--muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.05em; }
.form-group input[type="text"] {
    width: 100%; background: var(--bg); border: 1px solid var(--border);
    color: var(--text); padding: 10px 12px; border-radius: 8px;
    font-size: 14px; font-family: 'DM Sans', sans-serif; outline: none; box-sizing: border-box;
}
.form-group input:focus { border-color: var(--accent); }
.btn-archive {
    width: 100%; padding: 11px; border-radius: 8px;
    background: rgba(226,92,92,0.12); color: var(--accent3);
    border: 1px solid rgba(226,92,92,0.4);
    font-weight: 700; font-size: 13px; cursor: pointer;
    font-family: 'DM Sans', sans-serif; transition: 0.15s;
}
.btn-archive:hover { background: rgba(226,92,92,0.2); }

.arch-table { width: 100%; border-collapse: collapse; }
.arch-table th {
    background: var(--bg); color: var(--muted); font-size: 11px;
    letter-spacing: 0.07em; text-transform: uppercase;
    padding: 10px 14px; text-align: left; border-bottom: 1px solid var(--border);
}
.arch-table td { padding: 12px 14px; border-bottom: 1px solid var(--border); font-size: 13px; vertical-align: middle; }
.arch-table tr:last-child td { border-bottom: none; }
.arch-table tr:hover td { background: var(--bg); }

.act-btn {
    font-size: 12px; padding: 5px 12px; border-radius: 7px;
    border: 1px solid var(--border); background: transparent;
    color: var(--muted); cursor: pointer; text-decoration: none;
    font-family: 'DM Sans', sans-serif; transition: all 0.15s; display: inline-block;
}
.act-btn:hover { border-color: var(--accent); color: var(--accent); }

.alert { padding: 10px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; }
.alert-green { background: rgba(62,207,142,0.1); border: 1px solid rgba(62,207,142,0.3); color: var(--accent2); }
.alert-red   { background: rgba(226,92,92,0.1);  border: 1px solid rgba(226,92,92,0.3);  color: var(--accent3); }
</style>
<script>if(localStorage.getItem('adminTheme')==='light') document.body.classList.add('light-mode');</script>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="sidebar" id="adminSidebar">
    <div class="sidebar-brand">
        <img src="logo.png" alt="BCT Logo" style="width:140px;height:140px;border-radius:10%;display:block;margin:0 auto 10px;">
    </div>
    <nav class="sidebar-nav">
        <a href="admin.php"><span class="icon">◈</span> Dashboard</a>
        <a href="admin_users.php"><span class="icon">◉</span> Users</a>
        <a href="admin_pending.php"><span class="icon">◎</span> Pending</a>
        <a href="admin_sections.php"><span class="icon">▣</span> Sections</a>
        <a href="admin_timeslots.php"><span class="icon">◫</span> Time Slots</a>
        <a href="admin_schedules.php"><span class="icon">▦</span> Schedules</a>
        <a href="admin_grades.php"><span class="icon">◧</span> Grades</a>
        <a href="admin_archive.php" class="active"><span class="icon">◑</span> Archive</a>
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
                <h1>Year-End Archive</h1>
                <p>Preserve and export academic records before resetting for a new school year</p>
            </div>
        </div>
        <div class="topbar-right">
            <div class="admin-badge">
                <div class="admin-avatar"><?php echo strtoupper(substr($_SESSION['fullname'], 0, 1)); ?></div>
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

    <div style="display:grid; grid-template-columns: 380px 1fr; gap:20px; align-items:start;">

        <!-- ARCHIVE FORM -->
        <div class="panel">
            <div class="panel-header"><h3>Run Year-End Archive</h3></div>

            <div class="warn-box">
                <strong>⚠ Read before running</strong>
                This action will copy all current grades and attendance records into permanent archive tables,
                then <strong>clear the live tables</strong> so the system is clean for the new school year.
                Tasks, announcements, and grade submissions will also be cleared.
                <br><br>
                <strong>This cannot be undone.</strong> Make sure all Q4 grades are finalized and all teachers
                have submitted before running the archive.
            </div>

            <div class="info-box">
                Current school year detected: <strong><?php echo htmlspecialchars($current_sy); ?></strong><br>
                After archiving, grading periods will automatically reset to the next school year.
            </div>

            <form method="POST" onsubmit="return confirm('Archive school year ' + document.getElementById('sy_input').value + '? This will clear all live grade and attendance data. This cannot be undone.')">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <div class="form-group">
                    <label>School Year to Archive</label>
                    <input type="text" name="school_year" id="sy_input"
                           value="<?php echo htmlspecialchars($current_sy); ?>"
                           placeholder="e.g. 2025-2026" required>
                </div>
                <button type="submit" name="run_archive" class="btn-archive">Archive & Reset for New School Year</button>
            </form>
        </div>

        <!-- ARCHIVED YEARS -->
        <div class="panel">
            <div class="panel-header">
                <h3>Archived School Years</h3>
                <span class="badge badge-yellow"><?php echo count($archive_years); ?> year(s)</span>
            </div>

            <?php if (empty($archive_years)): ?>
            <p style="color:var(--muted); font-size:13px; padding:16px 0;">
                No archives yet.
                <?php if ($tbl_check2->num_rows === 0): ?>
                <br><strong style="color:var(--accent3);">Archive tables not found.</strong>
                Run <code>migration_archive.sql</code> in phpMyAdmin first.
                <?php endif; ?>
            </p>
            <?php else: ?>
            <table class="arch-table">
                <thead>
                    <tr>
                        <th>School Year</th>
                        <th>Students</th>
                        <th>Grade Records</th>
                        <th>Archived On</th>
                        <th>Export</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($archive_years as $ay): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($ay['school_year']); ?></strong></td>
                    <td><?php echo $ay['students']; ?></td>
                    <td><?php echo $ay['grade_records']; ?></td>
                    <td style="color:var(--muted);"><?php echo date('M d, Y', strtotime($ay['archived_at'])); ?></td>
                    <td>
                        <a href="?export_grades=<?php echo urlencode($ay['school_year']); ?>" class="act-btn">Grades CSV</a>
                        <a href="?export_attendance=<?php echo urlencode($ay['school_year']); ?>" class="act-btn" style="margin-left:4px;">Attendance CSV</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
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
