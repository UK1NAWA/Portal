<?php
include 'db.php';
include 'session_config.php';
include_once __DIR__ . '/cache.php';
include_once __DIR__ . '/audit.php';

if (!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.html");
    exit();
}

// ── TEMPLATE DOWNLOAD ────────────────────────────────────────
if (isset($_GET['download_template'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="grade_template.csv"');
    header('Cache-Control: no-cache');
    echo "LRN,Subject,Grade,Remarks\n";
    echo "123456789012,Mathematics,88,Passed\n";
    echo "123456789013,Mathematics,72,Failed\n";
    echo "123456789014,Mathematics,91,Passed\n";
    exit();
}

$teacher_id = $_SESSION['id'];
$msg        = '';
$error      = '';

// ============================================================
// CHANGED: grade uploads now go OUTSIDE the web root so
// browsers cannot directly download them by guessing the URL.
//
// dirname(__DIR__) goes one level above your project folder.
// Example: if your project is at /var/www/html/myproject/
// then files land at      /var/www/html/grade_uploads_private/
//
// To serve a file to the teacher/admin, create a small PHP
// download script that checks $_SESSION before sending it.
// ============================================================
define('GRADE_UPLOAD_DIR', dirname(__DIR__) . '/grade_uploads_private/');

// Handle delete submission
if (isset($_POST['delete_submission'])) {
    csrf_verify(); // ADDED: CSRF check

    $sub_id  = (int)$_POST['sub_id'];
    $del_chk = $conn->prepare("SELECT file_path FROM grade_submissions WHERE id=? AND teacher_id=?");
    $del_chk->bind_param("ii", $sub_id, $teacher_id);
    $del_chk->execute();
    $del_row = $del_chk->get_result()->fetch_assoc();
    if ($del_row) {
        if ($del_row['file_path'] && file_exists($del_row['file_path'])) {
            unlink($del_row['file_path']);
        }
        $del_stmt = $conn->prepare("DELETE FROM grade_submissions WHERE id=? AND teacher_id=?");
        $del_stmt->bind_param("ii", $sub_id, $teacher_id);
        $del_stmt->execute();
        audit_log($conn, 'GRADE_DELETED', $del_row['file_path'] ?? '', 'Submission deleted');
        $msg = "Submission deleted successfully.";
    } else {
        $error = "Could not delete submission.";
    }
}

// Get teacher's sections
$sec_q = $conn->prepare("SELECT section_name FROM sections WHERE teacher_id=? ORDER BY section_name");
$sec_q->bind_param("i", $teacher_id);
$sec_q->execute();
$my_sections = $sec_q->get_result()->fetch_all(MYSQLI_ASSOC);

// Get open grading periods
$open_periods = $conn->query("SELECT * FROM grading_periods WHERE is_open=1 ORDER BY FIELD(quarter,'Q1','Q2','Q3','Q4')")->fetch_all(MYSQLI_ASSOC);
$all_periods  = $conn->query("SELECT * FROM grading_periods ORDER BY FIELD(quarter,'Q1','Q2','Q3','Q4')")->fetch_all(MYSQLI_ASSOC);

// Handle file upload
if (isset($_POST['upload_grades'])) {
    csrf_verify(); // ADDED: CSRF check

    $section = trim($_POST['section']);
    $quarter = $_POST['quarter'];

    // Validate section belongs to teacher
    $chk = $conn->prepare("SELECT id FROM sections WHERE section_name=? AND teacher_id=?");
    $chk->bind_param("si", $section, $teacher_id);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows === 0) {
        $error = "Invalid section.";
    } elseif (empty($_FILES['grade_file']['name'])) {
        $error = "Please choose a file to upload.";
    } elseif ($_FILES['grade_file']['error'] !== UPLOAD_ERR_OK) {
        $error = "File upload error.";
    } else {
        $ext = strtolower(pathinfo($_FILES['grade_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
            $error = "Only .xlsx, .xls, or .csv files are allowed.";
        } else {
            // Check if locked
            $lock_chk = $conn->prepare("SELECT status FROM grade_submissions WHERE section=? AND quarter=?");
            $lock_chk->bind_param("ss", $section, $quarter);
            $lock_chk->execute();
            $lock_result = $lock_chk->get_result()->fetch_assoc();
            if ($lock_result && $lock_result['status'] === 'locked') {
                $error = "This submission has been locked by the admin. Contact admin to unlock.";
            } else {
                // CHANGED: save outside the web root
                if (!is_dir(GRADE_UPLOAD_DIR)) {
                    mkdir(GRADE_UPLOAD_DIR, 0750, true);
                }
                $filename = GRADE_UPLOAD_DIR . 'grades_' . preg_replace('/[^a-z0-9]/i', '_', $section) . '_' . $quarter . '_' . time() . '.' . $ext;

                if (move_uploaded_file($_FILES['grade_file']['tmp_name'], $filename)) {
                    // Upsert submission record
                    $stmt = $conn->prepare("
                        INSERT INTO grade_submissions (teacher_id, section, quarter, file_path, submitted_at, status)
                        VALUES (?, ?, ?, ?, NOW(), 'submitted')
                        ON DUPLICATE KEY UPDATE file_path=VALUES(file_path), submitted_at=NOW(), status='submitted'
                    ");
                    $stmt->bind_param("isss", $teacher_id, $section, $quarter, $filename);
                    $stmt->execute();

                    // Parse grades from CSV or XLSX/XLS
                    if (in_array($ext, ['csv', 'xlsx', 'xls'])) {
                        $students_q = $conn->prepare("SELECT id, lrn FROM users WHERE role='student' AND section=? AND lrn IS NOT NULL");
                        $students_q->bind_param("s", $section);
                        $students_q->execute();
                        $students_list = $students_q->get_result()->fetch_all(MYSQLI_ASSOC);
                        $student_map   = [];
                        foreach ($students_list as $s) $student_map[trim($s['lrn'])] = $s['id'];

                        $saved   = 0;
                        $skipped = [];
                        $rows    = [];

                        if ($ext === 'csv') {
                            // CSV parsing
                            if (($handle = fopen($filename, 'r')) !== false) {
                                fgetcsv($handle); // skip header
                                while (($row = fgetcsv($handle)) !== false) {
                                    if (count($row) >= 3) $rows[] = $row;
                                }
                                fclose($handle);
                            }
                        } else {
                            // XLSX parsing (native ZIP+XML, no library needed)
                            if ($ext === 'xlsx') {
                                $zip = new ZipArchive();
                                if ($zip->open($filename) === true) {
                                    $shared = [];
                                    $ss = $zip->getFromName('xl/sharedStrings.xml');
                                    if ($ss !== false) {
                                        $ssxml = simplexml_load_string($ss);
                                        foreach ($ssxml->si as $si) {
                                            $t = '';
                                            foreach ($si->r as $r) $t .= (string)$r->t;
                                            if (empty((string)$si->r)) $t = (string)$si->t;
                                            $shared[] = $t;
                                        }
                                    }
                                    $sheet_xml = $zip->getFromName('xl/worksheets/sheet1.xml');
                                    $zip->close();
                                    if ($sheet_xml !== false) {
                                        $sxml           = simplexml_load_string($sheet_xml);
                                        $header_skipped = false;
                                        foreach ($sxml->sheetData->row as $row) {
                                            $cells = [];
                                            foreach ($row->c as $cell) {
                                                $val = '';
                                                $t   = (string)$cell['t'];
                                                $v   = (string)$cell->v;
                                                if ($t === 's') {
                                                    $val = isset($shared[(int)$v]) ? $shared[(int)$v] : '';
                                                } elseif ($t === 'inlineStr') {
                                                    $val = (string)$cell->is->t;
                                                } else {
                                                    $val = $v;
                                                }
                                                preg_match('/([A-Z]+)(\d+)/', (string)$cell['r'], $m);
                                                $col_idx = 0;
                                                foreach (str_split($m[1]) as $ch)
                                                    $col_idx = $col_idx * 26 + (ord($ch) - 64);
                                                $col_idx--;
                                                while (count($cells) < $col_idx) $cells[] = '';
                                                $cells[] = $val;
                                            }
                                            if (!$header_skipped) { $header_skipped = true; continue; }
                                            if (count($cells) >= 3) $rows[] = $cells;
                                        }
                                    }
                                }
                            } else {
                                // XLS (legacy) — not supported for auto-parsing
                                $error = "Legacy .xls format is not supported for auto-parsing. Please save your file as .xlsx or .csv and re-upload.";
                            }
                        }

                        // Save rows to DB
                        foreach ($rows as $row) {
                            $lrn_raw = preg_replace('/\D/', '', trim($row[0]));
                            $subject = trim($row[1]);
                            $grade   = is_numeric(trim($row[2])) ? (float)trim($row[2]) : null;
                            $remarks = isset($row[3]) && trim($row[3]) !== ''
                                ? trim($row[3])
                                : ($grade !== null ? ($grade >= 75 ? 'Passed' : 'Failed') : '');

                            if ($lrn_raw === '' || $subject === '') continue;

                            if (isset($student_map[$lrn_raw])) {
                                $sid = $student_map[$lrn_raw];
                                $ins = $conn->prepare("
                                    INSERT INTO student_grades (student_id, section, subject, quarter, grade, remarks)
                                    VALUES (?, ?, ?, ?, ?, ?)
                                    ON DUPLICATE KEY UPDATE grade=VALUES(grade), remarks=VALUES(remarks)
                                ");
                                $ins->bind_param("isssds", $sid, $section, $subject, $quarter, $grade, $remarks);
                                $ins->execute();
                                $saved++;
                            } else {
                                $skipped[] = $lrn_raw;
                            }
                        }

                        // Clear grade cache for all students in this section
                        $sid_q = $conn->prepare("SELECT id FROM users WHERE role='student' AND section=?");
                        $sid_q->bind_param("s", $section);
                        $sid_q->execute();
                        foreach ($sid_q->get_result()->fetch_all(MYSQLI_ASSOC) as $sid_row) {
                            Cache::delete('grades_' . $sid_row['id']);
                        }
                        audit_log($conn, 'GRADE_UPLOAD', $section, $quarter . ': ' . $saved . ' record(s) saved');
                        $msg = "Grades for $section ($quarter) submitted. $saved record(s) saved.";
                        if (!empty($skipped)) {
                            $unique_skipped = array_unique($skipped);
                            $msg .= " Warning: " . count($unique_skipped) . " LRN(s) not found in this section and were skipped: " . implode(', ', $unique_skipped) . ". Check that these students are enrolled and have an LRN assigned.";
                        }
                    } else {
                        $msg = "File uploaded successfully for $section ($quarter). Note: only CSV and XLSX files are parsed into student grades automatically.";
                    }
                } else {
                    $error = "Failed to save file. Check server permissions for: " . dirname(GRADE_UPLOAD_DIR);
                }
            }
        }
    }
}

// Get this teacher's submissions
$my_submissions = $conn->prepare("
    SELECT * FROM grade_submissions WHERE teacher_id=? ORDER BY submitted_at DESC
");
$my_submissions->bind_param("i", $teacher_id);
$my_submissions->execute();
$my_subs = $my_submissions->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Teacher - Grades</title>
<link rel="stylesheet" href="admin.css">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
.two-col { display: grid; grid-template-columns: 360px 1fr; gap: 20px; align-items: start; width: 100%; max-width: 100%; }
@media (max-width: 900px) { .two-col { grid-template-columns: 1fr; } }
.form-group { margin-bottom: 14px; }
.form-group label { display: block; font-size: 12px; color: var(--muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.05em; }
.form-group select,
.form-group input[type="file"] {
    width: 100%; background: var(--bg); border: 1px solid var(--border);
    color: var(--text); padding: 10px 12px; border-radius: 8px;
    font-size: 13px; font-family: 'DM Sans', sans-serif; outline: none; box-sizing: border-box;
}
.form-group select:focus { border-color: var(--accent); }
.form-group input[type="file"] { cursor: pointer; }
.btn-submit {
    width: 100%; padding: 10px; border-radius: 8px;
    background: var(--accent); color: #000; border: none;
    font-weight: 700; font-size: 13px; cursor: pointer;
    font-family: 'DM Sans', sans-serif;
}
.btn-submit:hover { opacity: 0.9; }
.btn-submit:disabled { opacity: 0.4; cursor: not-allowed; }

.sub-table { width: 100%; border-collapse: collapse; }
.sub-table th {
    background: var(--bg); color: var(--muted); font-size: 11px;
    letter-spacing: 0.07em; text-transform: uppercase;
    padding: 10px 14px; text-align: left; border-bottom: 1px solid var(--border);
}
.sub-table td { padding: 12px 14px; border-bottom: 1px solid var(--border); font-size: 13px; vertical-align: middle; }
.sub-table tr:last-child td { border-bottom: none; }
.sub-table tr:hover td { background: var(--bg); }

.act-btn {
    font-size: 12px; padding: 5px 12px; border-radius: 7px;
    border: 1px solid var(--border); background: transparent;
    color: var(--muted); cursor: pointer; text-decoration: none;
    font-family: 'DM Sans', sans-serif; transition: all 0.15s; display: inline-block;
}
.act-btn:hover { border-color: #444; color: var(--text); }

.alert { padding: 10px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; }
.alert-green { background: rgba(62,207,142,0.1); border: 1px solid rgba(62,207,142,0.3); color: var(--accent2); }
.alert-red   { background: rgba(226,92,92,0.1);  border: 1px solid rgba(226,92,92,0.3);  color: var(--accent3); }

.period-closed-note { font-size: 13px; color: var(--muted); padding: 16px 0; }
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
        <a href="teacher_grades.php" class="active"><span class="icon">◧</span> Grades</a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php"><span class="icon">◄</span> Logout</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <div class="topbar-left" style="display:flex;align-items:center;gap:12px;">
            <button class="hamburger-btn" onclick="toggleSidebar()" aria-label="Toggle menu"><span></span><span></span><span></span></button>
            <div><h1>Grades</h1></div>
        </div>
        <div class="topbar-right">
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

    <div class="two-col">

        <!-- UPLOAD FORM -->
        <div class="panel">
            <div class="panel-header"><h3>Submit Grades</h3></div>

            <?php if (empty($open_periods)): ?>
            <p class="period-closed-note">No grading period is currently open. Wait for the admin to open a quarter.</p>
            <?php elseif (empty($my_sections)): ?>
            <p class="period-closed-note">You have no sections assigned. Contact admin.</p>
            <?php else: ?>

            <form method="POST" enctype="multipart/form-data">
                <!-- ADDED: CSRF token -->
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                <div class="form-group">
                    <label>Section</label>
                    <select name="section" required>
                        <option value="">-- Select Section --</option>
                        <?php foreach ($my_sections as $s): ?>
                        <option value="<?php echo htmlspecialchars($s['section_name']); ?>">
                            <?php echo htmlspecialchars($s['section_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Quarter</label>
                    <select name="quarter" required>
                        <option value="">-- Select Quarter --</option>
                        <?php foreach ($open_periods as $p): ?>
                        <option value="<?php echo $p['quarter']; ?>"><?php echo $p['quarter']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Grade Sheet File (.xlsx, .xls, .csv)</label>
                    <input type="file" name="grade_file" accept=".xlsx,.xls,.csv" required>
                </div>

                <!-- FILE FORMAT INSTRUCTIONS -->
                <div style="background:var(--bg); border:1px solid var(--border); border-radius:8px; padding:12px 14px; margin-bottom:14px; font-size:12px; color:var(--muted); line-height:1.8;">
                    <strong style="color:var(--text); display:block; margin-bottom:6px;">Required column order:</strong>
                    <span style="font-family:monospace; background:var(--surface); padding:2px 6px; border-radius:4px; color:var(--text);">
                        Column A: LRN &nbsp;|&nbsp; Column B: Subject &nbsp;|&nbsp; Column C: Grade &nbsp;|&nbsp; Column D: Remarks (optional)
                    </span>
                    <div style="margin-top:8px;">
                        Rearrange your existing grade sheet to match this order before uploading.<br>
                        The first row is treated as a header and will be skipped automatically.<br>
                        <strong style="color:var(--accent3);">XLS files</strong> are accepted but grades will not be parsed — please use XLSX or CSV.
                    </div>
                </div>

                <a href="?download_template" style="display:inline-block; font-size:12px; padding:6px 14px; border-radius:7px; border:1px solid var(--border); color:var(--muted); text-decoration:none; margin-bottom:14px; font-family:'DM Sans',sans-serif;">
                    ↓ Download blank template
                </a>
                <button type="submit" name="upload_grades" class="btn-submit">Submit Grades</button>
            </form>
            <?php endif; ?>
        </div>

        <!-- MY SUBMISSIONS -->
        <div class="panel">
            <div class="panel-header"><h3>My Submissions</h3></div>
            <?php if (empty($my_subs)): ?>
            <p style="color:var(--muted); font-size:13px; padding:16px 0;">No submissions yet.</p>
            <?php else: ?>
            <table class="sub-table">
                <thead>
                    <tr>
                        <th>Section</th>
                        <th>Quarter</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($my_subs as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['section']); ?></td>
                    <td><?php echo $row['quarter']; ?></td>
                    <td><?php echo date('M d, Y', strtotime($row['submitted_at'])); ?></td>
                    <td>
                        <?php if ($row['status'] === 'locked'): ?>
                        <span style="color:var(--accent3); font-size:12px;">Locked</span>
                        <?php else: ?>
                        <span style="color:var(--accent2); font-size:12px;">Submitted</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['status'] !== 'locked'): ?>
                        <form method="POST" onsubmit="return confirm('Delete this submission?');" style="display:inline;">
                            <!-- ADDED: CSRF token -->
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <input type="hidden" name="sub_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="delete_submission" class="act-btn" style="color:var(--accent3); border-color:var(--accent3);">Delete</button>
                        </form>
                        <?php else: ?>
                        <span style="font-size:12px; color:var(--muted);">Locked</span>
                        <?php endif; ?>
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