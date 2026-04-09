<?php
include 'db.php';
include 'session_config.php';

if(!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'teacher'){
    header("Location: login.html");
    exit();
}

$teacher_id = (int)$_SESSION['id'];
$msg        = '';
$error      = '';

// ── POST TASK ────────────────────────────────────────────────
if(isset($_POST['post_task'])){
    csrf_verify();
    $section  = trim($_POST['section']);
    $title    = trim($_POST['title']);
    $desc     = trim($_POST['description'] ?? '');
    $due      = trim($_POST['due_days'] ?? '');
    $link     = trim($_POST['link'] ?? '');

    // Validate section belongs to this teacher
    $chk = $conn->prepare("SELECT id FROM sections WHERE section_name=? AND teacher_id=?");
    $chk->bind_param("si", $section, $teacher_id);
    $chk->execute();
    $chk->store_result();

    if($chk->num_rows === 0){
        $error = "Invalid section.";
    } elseif($title === ''){
        $error = "Task title is required.";
    } elseif(mb_strlen($title) > 150){
        $error = "Title is too long (max 150 characters).";
    } else {
        // Validate due days if provided
        $due_val = null;
        if($due !== ''){
            $days = (int)$due;
            if($days < 1 || $days > 365){
                $error = "Due days must be between 1 and 365.";
            } else {
                $due_val = date('Y-m-d', strtotime("+{$days} days"));
            }
        }

        // Validate link if provided
        $link_val = null;
        if($link !== ''){
            // Must start with http:// or https://
            if(!preg_match('/^https?:\/\/.+/i', $link)){
                $error = "Link must start with http:// or https://";
            } else {
                $link_val = $link;
            }
        }

        // Handle file upload if provided
        $file_path = null;
        if(!$error && !empty($_FILES['task_file']['name'])){
            $allowed_ext = ['pdf','doc','docx','ppt','pptx','xls','xlsx','txt','png','jpg','jpeg'];
            $ext = strtolower(pathinfo($_FILES['task_file']['name'], PATHINFO_EXTENSION));
            $max_size = 10 * 1024 * 1024; // 10MB

            if(!in_array($ext, $allowed_ext)){
                $error = "File type not allowed. Allowed: pdf, doc, docx, ppt, pptx, xls, xlsx, txt, png, jpg, jpeg.";
            } elseif($_FILES['task_file']['size'] > $max_size){
                $error = "File too large. Max size is 10MB.";
            } elseif($_FILES['task_file']['error'] !== UPLOAD_ERR_OK){
                $error = "File upload error.";
            } else {
                $upload_dir = 'task_files/';
                if(!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                // Sanitize filename - use random name to prevent overwrite attacks
                $safe_name  = bin2hex(random_bytes(8)) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['task_file']['name']));
                $file_path  = $upload_dir . $safe_name;
                if(!move_uploaded_file($_FILES['task_file']['tmp_name'], $file_path)){
                    $error = "Failed to save file.";
                    $file_path = null;
                }
            }
        }

        if(!$error){
            $ins = $conn->prepare("
                INSERT INTO tasks (section, title, description, due_date, posted_by, link, file_path)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->bind_param("ssssiss", $section, $title, $desc, $due_val, $teacher_id, $link_val, $file_path);
            $ins->execute();
            $msg = "Task posted.";
        }
    }
}

// ── DELETE TASK ──────────────────────────────────────────────
if(isset($_POST['delete_task'])){
    csrf_verify();
    $task_id = (int)$_POST['delete_task'];

    // Get file path before deleting so we can remove the file
    $get_file = $conn->prepare("
        SELECT t.file_path FROM tasks t
        JOIN sections s ON s.section_name = t.section
        WHERE t.id = ? AND s.teacher_id = ?
    ");
    $get_file->bind_param("ii", $task_id, $teacher_id);
    $get_file->execute();
    $file_row = $get_file->get_result()->fetch_assoc();
    if($file_row && $file_row['file_path'] && file_exists($file_row['file_path'])){
        unlink($file_row['file_path']);
    }

    $del = $conn->prepare("
        DELETE t FROM tasks t
        JOIN sections s ON s.section_name = t.section
        WHERE t.id = ? AND s.teacher_id = ?
    ");
    $del->bind_param("ii", $task_id, $teacher_id);
    $del->execute();
    header("Location: teacher_tasks.php?msg=deleted");
    exit();
}

if(isset($_GET['msg']) && $_GET['msg'] === 'deleted') $msg = "Task deleted.";

// ── FETCH DATA ───────────────────────────────────────────────
$sec_q = $conn->prepare("SELECT section_name FROM sections WHERE teacher_id=? ORDER BY section_name");
$sec_q->bind_param("i", $teacher_id);
$sec_q->execute();
$my_sections = $sec_q->get_result()->fetch_all(MYSQLI_ASSOC);

$page_num = max(1, (int)($_GET['p'] ?? 1));
$limit    = 30;
$offset   = ($page_num - 1) * $limit;

$tasks_q = $conn->prepare("
    SELECT t.id, t.section, t.title, t.description, t.due_date, t.created_at,
           t.link, t.file_path, u.fullname as teacher_name
    FROM tasks t
    JOIN sections s ON s.section_name = t.section AND s.teacher_id = ?
    JOIN users u ON u.id = t.posted_by
    ORDER BY t.due_date IS NULL, t.due_date ASC, t.created_at DESC
    LIMIT ? OFFSET ?
");
$tasks_q->bind_param("iii", $teacher_id, $limit, $offset);
$tasks_q->execute();
$tasks = $tasks_q->get_result()->fetch_all(MYSQLI_ASSOC);

$count_q = $conn->prepare("
    SELECT COUNT(*) as c FROM tasks t
    JOIN sections s ON s.section_name = t.section AND s.teacher_id = ?
");
$count_q->bind_param("i", $teacher_id);
$count_q->execute();
$total_tasks = $count_q->get_result()->fetch_assoc()['c'];
$total_pages = ceil($total_tasks / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Teacher - Tasks</title>
<link rel="stylesheet" href="admin.css">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
.two-col { display: grid; grid-template-columns: 300px 1fr; gap: 20px; align-items: start; }

.form-group { margin-bottom: 14px; }
.form-group label {
    display: block; font-size: 12px; color: var(--muted);
    margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.05em;
}
.form-group input[type="text"],
.form-group input[type="number"],
.form-group input[type="url"],
.form-group input[type="file"],
.form-group textarea,
.form-group select {
    width: 100%; background: var(--bg); border: 1px solid var(--border);
    color: var(--text); padding: 10px 12px; border-radius: 8px;
    font-size: 13px; font-family: 'DM Sans', sans-serif;
    outline: none; box-sizing: border-box; resize: vertical;
}
.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus { border-color: var(--accent); }
.form-group select option { background: var(--surface); }
.form-group input[type="file"] { cursor: pointer; }
.form-hint { font-size: 11px; color: var(--muted); margin-top: 4px; }

.btn-submit {
    width: 100%; padding: 10px; border-radius: 8px;
    background: var(--accent); color: #000; border: none;
    font-weight: 700; font-size: 13px; cursor: pointer;
    font-family: 'DM Sans', sans-serif;
}
.btn-submit:hover { opacity: 0.9; }

.task-table { width: 100%; border-collapse: collapse; }
.task-table th {
    background: var(--bg); color: var(--muted); font-size: 11px;
    letter-spacing: 0.07em; text-transform: uppercase;
    padding: 10px 14px; text-align: left; border-bottom: 1px solid var(--border);
}
.task-table td {
    padding: 11px 14px; border-bottom: 1px solid var(--border);
    font-size: 13px; vertical-align: middle;
}
.task-table tr:last-child td { border-bottom: none; }
.task-table tr:hover td { background: var(--bg); }

.attach-link {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 12px; color: var(--accent); text-decoration: none;
    border: 1px solid rgba(240,180,41,0.3); border-radius: 6px;
    padding: 3px 8px; margin-top: 4px;
}
.attach-link:hover { background: rgba(240,180,41,0.08); }

.act-btn {
    font-size: 12px; padding: 5px 12px; border-radius: 7px;
    border: 1px solid var(--border); background: transparent;
    color: var(--muted); cursor: pointer; text-decoration: none;
    font-family: 'DM Sans', sans-serif; transition: all 0.15s; display: inline-block;
}
.act-btn:hover { border-color: #444; color: var(--text); }
.act-btn.danger:hover { border-color: var(--accent3); color: var(--accent3); }

.alert { padding: 10px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; }
.alert-green { background: rgba(62,207,142,0.1); border: 1px solid rgba(62,207,142,0.3); color: var(--accent2); }
.alert-red   { background: rgba(226,92,92,0.1);  border: 1px solid rgba(226,92,92,0.3);  color: var(--accent3); }

.overdue  { color: var(--accent3); font-size: 12px; }
.due-soon { color: var(--accent);  font-size: 12px; }
.due-ok   { color: var(--muted);   font-size: 12px; }

.pagination { display:flex; gap:6px; margin-top:16px; }
.pagination a, .pagination span {
    padding: 5px 11px; border-radius: 6px; font-size: 12px;
    border: 1px solid var(--border); text-decoration: none;
    color: var(--muted); font-family: 'DM Sans', sans-serif;
}
.pagination a:hover { color: var(--text); border-color: #444; }
.pagination .current { background: var(--accent); color: #000; border-color: var(--accent); font-weight: 700; }

.divider { border: none; border-top: 1px solid var(--border); margin: 14px 0; }
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
        <a href="teacher_attendance.php"><span class="icon">▣</span> Attendance</a>
        <a href="teacher_tasks.php" class="active"><span class="icon">◐</span> Tasks</a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php"><span class="icon">◄</span> Logout</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <div class="topbar-left" style="display:flex;align-items:center;gap:12px;">
            <button class="hamburger-btn" onclick="toggleSidebar()" aria-label="Toggle menu"><span></span><span></span><span></span></button>
            <div><h1>Tasks</h1></div>
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
    <?php if($error): ?>
    <div class="alert alert-red"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="two-col">

        <!-- POST FORM -->
        <div class="panel">
            <div class="panel-header"><h3>New Task</h3></div>
            <?php if(empty($my_sections)): ?>
            <p style="color:var(--muted); font-size:13px; padding:12px 0;">No sections assigned. Contact admin.</p>
            <?php else: ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <div class="form-group">
                    <label>Section</label>
                    <select name="section" required>
                        <?php foreach($my_sections as $s): ?>
                        <option value="<?php echo htmlspecialchars($s['section_name']); ?>">
                            <?php echo htmlspecialchars($s['section_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" maxlength="150" placeholder="e.g. Research Paper" required>
                </div>
                <div class="form-group">
                    <label>Description <span style="color:var(--muted); font-size:11px;">(optional)</span></label>
                    <textarea name="description" rows="3" maxlength="1000" placeholder="Additional details..."></textarea>
                </div>
                <div class="form-group">
                    <label>Due in (days) <span style="color:var(--muted); font-size:11px;">(optional)</span></label>
                    <input type="number" name="due_days" min="1" max="365" placeholder="e.g. 7">
                </div>

                <hr class="divider">

                <div class="form-group">
                    <label>Google Classroom / Link <span style="color:var(--muted); font-size:11px;">(optional)</span></label>
                    <input type="text" name="link" maxlength="500" placeholder="https://classroom.google.com/...">
                    <div class="form-hint">Paste any link — Google Classroom, Drive, YouTube, etc.</div>
                </div>
                <div class="form-group">
                    <label>Attach File <span style="color:var(--muted); font-size:11px;">(optional, max 10MB)</span></label>
                    <input type="file" name="task_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.png,.jpg,.jpeg">
                    <div class="form-hint">pdf, doc, docx, ppt, pptx, xls, xlsx, txt, images</div>
                </div>

                <button type="submit" name="post_task" class="btn-submit">Post Task</button>
            </form>
            <?php endif; ?>
        </div>

        <!-- TASK LIST -->
        <div class="panel">
            <div class="panel-header">
                <h3>Posted Tasks</h3>
                <span class="badge badge-yellow"><?php echo $total_tasks; ?> total</span>
            </div>

            <?php if(empty($tasks)): ?>
            <p style="color:var(--muted); font-size:13px; padding:16px 0;">No tasks posted yet.</p>
            <?php else: ?>
            <table class="task-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Section</th>
                        <th>Due</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($tasks as $t):
                    $due_label = '—';
                    $due_class = 'due-ok';
                    if($t['due_date']){
                        $diff = (new DateTime())->diff(new DateTime($t['due_date']))->days;
                        $past = new DateTime($t['due_date']) < new DateTime('today');
                        if($past){
                            $due_label = 'Overdue';
                            $due_class = 'overdue';
                        } elseif($diff <= 3){
                            $due_label = $diff === 0 ? 'Today' : $diff . 'd left';
                            $due_class = 'due-soon';
                        } else {
                            $due_label = $diff . ' days';
                        }
                    }
                ?>
                <tr>
                    <td>
                        <div style="font-weight:500;"><?php echo htmlspecialchars($t['title']); ?></div>
                        <?php if($t['description']): ?>
                        <div style="font-size:12px; color:var(--muted); margin-top:2px;">
                            <?php echo htmlspecialchars(mb_strimwidth($t['description'], 0, 60, '...')); ?>
                        </div>
                        <?php endif; ?>
                        <?php if($t['link']): ?>
                        <a href="<?php echo htmlspecialchars($t['link']); ?>" target="_blank" rel="noopener noreferrer" class="attach-link">
                            ↗ Link
                        </a>
                        <?php endif; ?>
                        <?php if($t['file_path'] && file_exists($t['file_path'])): ?>
                        <a href="<?php echo htmlspecialchars($t['file_path']); ?>" target="_blank" class="attach-link">
                            ↓ <?php echo htmlspecialchars(pathinfo($t['file_path'], PATHINFO_EXTENSION)); ?> file
                        </a>
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--muted);"><?php echo htmlspecialchars($t['section']); ?></td>
                    <td class="<?php echo $due_class; ?>"><?php echo $due_label; ?></td>
                    <td>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this task?')">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <input type="hidden" name="delete_task" value="<?php echo $t['id']; ?>">
                            <button type="submit" class="act-btn danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php if($total_pages > 1): ?>
            <div class="pagination">
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if($i == $page_num): ?>
                    <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                    <a href="teacher_tasks.php?p=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

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
