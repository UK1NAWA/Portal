<?php
include 'db.php';
include 'session_config.php';
include_once __DIR__ . '/cache.php';

if(!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.html");
    exit();
}

// Ensure upload folder exists
$upload_dir = __DIR__ . '/schedule_images/';
if(!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

$error   = '';
$success = '';

// ── UPLOAD schedule image ──────────────────────────────────────────
if(isset($_POST['upload_schedule'])){
    $sec = trim($_POST['section']);

    if($sec === ''){
        $error = "Please select a section.";
    } elseif(empty($_FILES['schedule_image']['name'])){
        $error = "Please choose an image to upload.";
    } else {
        $file     = $_FILES['schedule_image'];
        $allowed  = ['image/jpeg','image/png','image/webp','image/gif'];
        $ext_map  = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
        $mime     = mime_content_type($file['tmp_name']);

        if(!in_array($mime, $allowed)){
            $error = "Only JPG, PNG, WEBP, or GIF images are allowed.";
        } elseif($file['size'] > 10 * 1024 * 1024){
            $error = "File is too large. Maximum size is 10 MB.";
        } else {
            // Delete old image if exists
            $old = $conn->prepare("SELECT image_path FROM schedule_images WHERE section=?");
            $old->bind_param("s", $sec);
            $old->execute();
            $old_row = $old->get_result()->fetch_assoc();
            if($old_row && file_exists(__DIR__ . '/' . $old_row['image_path'])){
                unlink(__DIR__ . '/' . $old_row['image_path']);
            }

            // Save new image
            $filename   = 'schedule_images/' . md5($sec . time()) . '.' . $ext_map[$mime];
            $dest       = __DIR__ . '/' . $filename;

            if(move_uploaded_file($file['tmp_name'], $dest)){
                $stmt = $conn->prepare("
                    INSERT INTO schedule_images (section, image_path, uploaded_by)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE image_path=VALUES(image_path), uploaded_by=VALUES(uploaded_by), uploaded_at=NOW()
                ");
                $stmt->bind_param("sss", $sec, $filename, $_SESSION['fullname']);
                $stmt->execute();

                // Clear student schedule cache for this section
                Cache::delete('sched_img_' . md5($sec));

                $success = "Schedule image uploaded for section \"$sec\".";
            } else {
                $error = "Failed to save the file. Check server permissions.";
            }
        }
    }
}

// ── DELETE schedule image ──────────────────────────────────────────
if(isset($_POST['delete_schedule'])){
    $sec = trim($_POST['delete_section']);
    $old = $conn->prepare("SELECT image_path FROM schedule_images WHERE section=?");
    $old->bind_param("s", $sec);
    $old->execute();
    $old_row = $old->get_result()->fetch_assoc();
    if($old_row){
        if(file_exists(__DIR__ . '/' . $old_row['image_path'])){
            unlink(__DIR__ . '/' . $old_row['image_path']);
        }
        $del = $conn->prepare("DELETE FROM schedule_images WHERE section=?");
        $del->bind_param("s", $sec);
        $del->execute();
        Cache::delete('sched_img_' . md5($sec));
        $success = "Schedule image removed for section \"$sec\".";
    }
}

// ── Fetch all sections and their current schedule image ────────────
$rows = $conn->query("
    SELECT s.section_name,
        (SELECT COUNT(*) FROM users u WHERE u.role='student' AND u.section=s.section_name) as student_count,
        si.image_path,
        si.uploaded_by,
        si.uploaded_at
    FROM sections s
    LEFT JOIN schedule_images si ON si.section = s.section_name
    ORDER BY s.section_name
");
$sections_data = $rows ? $rows->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin – Schedules</title>
<link rel="stylesheet" href="admin.css">
<style>
.form-group { margin-bottom: 14px; }
.form-group label {
    display: block; font-size: 12px; color: var(--muted);
    margin-bottom: 5px; text-transform: uppercase; letter-spacing: .05em;
}
.form-group select,
.form-group input[type="file"] {
    width: 100%; background: var(--bg); border: 1px solid var(--border);
    color: var(--text); padding: 9px 12px; border-radius: 8px;
    font-size: 14px; font-family: sans-serif;
    outline: none; box-sizing: border-box;
}
.form-group select:focus { border-color: var(--accent); }
.btn-primary {
    width: 100%; background: var(--accent); color: #000; border: none;
    padding: 10px; border-radius: 8px; font-weight: 700;
    font-size: 14px; cursor: pointer;
}
.btn-primary:hover { opacity: .9; }
.alert { padding: 10px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; }
.alert-green { background: rgba(62,207,142,.1); border: 1px solid rgba(62,207,142,.3); color: var(--accent2); }
.alert-red   { background: rgba(226,92,92,.1);  border: 1px solid rgba(226,92,92,.3);  color: var(--accent3); }
.two-col { display: grid; grid-template-columns: 300px 1fr; gap: 20px; align-items: start; }
@media(max-width:900px){ .two-col { grid-template-columns: 1fr; } }
.sched-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 16px;
}
.sched-card {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 14px;
    overflow: hidden;
}
.sched-card-img {
    width: 100%;
    height: 160px;
    object-fit: cover;
    display: block;
    background: var(--border);
}
.sched-card-no-img {
    width: 100%;
    height: 160px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--surface);
    color: var(--muted);
    font-size: 13px;
}
.sched-card-body {
    padding: 12px 14px;
}
.sched-card-name {
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 4px;
}
.sched-card-meta {
    font-size: 11px;
    color: var(--muted);
    margin-bottom: 10px;
}
.sched-card-actions {
    display: flex;
    gap: 8px;
}
.act-btn {
    font-size: 12px; padding: 5px 12px; border-radius: 7px;
    border: 1px solid var(--border); background: transparent;
    color: var(--muted); cursor: pointer; text-decoration: none;
    font-family: sans-serif; transition: all .15s; display: inline-block;
}
.act-btn:hover { border-color: #444; color: var(--text); }
.act-btn.danger:hover { border-color: var(--accent3); color: var(--accent3); }
.empty-state { color: var(--muted); font-size: 13px; padding: 24px 0; text-align: center; }
/* Image preview */
#imgPreviewWrap { margin-top: 10px; display: none; }
#imgPreview { width: 100%; border-radius: 8px; max-height: 200px; object-fit: cover; border: 1px solid var(--border); }
</style>
<script>if(localStorage.getItem('adminTheme')==='light') document.body.classList.add('light-mode');</script>
</head>
<body>

<!-- SIDEBAR OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- SIDEBAR -->
<div class="sidebar" id="adminSidebar">
    <div class="sidebar-brand">
        <img src="logo.png" alt="Logo" style="width:140px;height:140px;border-radius:10%;display:block;margin:0 auto 10px;">
    </div>
    <nav class="sidebar-nav">
        <a href="admin.php"><span class="icon">◈</span> Dashboard</a>
        <a href="admin_users.php"><span class="icon">◉</span> Users</a>
        <a href="admin_pending.php"><span class="icon">◎</span> Pending</a>
        <a href="admin_sections.php"><span class="icon">▣</span> Sections</a>
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
            <button class="hamburger-btn" onclick="toggleSidebar()"><span></span><span></span><span></span></button>
            <div>
                <h1>Schedules</h1>
                <p>Upload a schedule image per section</p>
            </div>
        </div>
        <div class="topbar-right">
            <div class="admin-badge">
                <div class="admin-avatar"><?php echo strtoupper(substr($_SESSION['fullname'],0,1)); ?></div>
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
            <button class="theme-toggle-btn" onclick="toggleTheme()">
                <span class="tog-track"><span class="tog-thumb"></span></span>
                <span id="themeLabel">Light</span>
            </button>
        </div>
    </div>

    <?php if($success): ?>
    <div class="alert alert-green"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if($error): ?>
    <div class="alert alert-red"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="two-col">

        <!-- UPLOAD FORM -->
        <div class="panel">
            <div class="panel-header"><h3>Upload Schedule</h3></div>
            <form method="POST" enctype="multipart/form-data" style="padding:16px;">
                <div class="form-group">
                    <label>Section</label>
                    <select name="section" required>
                        <option value="">Select section</option>
                        <?php foreach($sections_data as $s): ?>
                        <option value="<?php echo htmlspecialchars($s['section_name']); ?>">
                            <?php echo htmlspecialchars($s['section_name']); ?>
                            <?php echo $s['image_path'] ? ' (has image)' : ''; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Schedule Image</label>
                    <input type="file" name="schedule_image" accept="image/*" required onchange="previewImage(this)">
                    <div id="imgPreviewWrap">
                        <img id="imgPreview" src="" alt="Preview">
                    </div>
                </div>
                <button type="submit" name="upload_schedule" class="btn-primary">Upload Schedule</button>
            </form>
        </div>

        <!-- SECTIONS GRID -->
        <div class="panel">
            <div class="panel-header">
                <h3>All Sections</h3>
                <span class="badge badge-yellow"><?php echo count($sections_data); ?> sections</span>
            </div>

            <?php if(empty($sections_data)): ?>
            <div class="empty-state">No sections found. <a href="admin_sections.php" style="color:var(--accent);">Add sections first.</a></div>
            <?php else: ?>
            <div class="sched-grid" style="padding:16px;">
                <?php foreach($sections_data as $s): ?>
                <div class="sched-card">
                    <?php if($s['image_path'] && file_exists(__DIR__ . '/' . $s['image_path'])): ?>
                    <img class="sched-card-img"
                         src="<?php echo htmlspecialchars($s['image_path']); ?>"
                         alt="Schedule for <?php echo htmlspecialchars($s['section_name']); ?>">
                    <?php else: ?>
                    <div class="sched-card-no-img">No schedule uploaded</div>
                    <?php endif; ?>

                    <div class="sched-card-body">
                        <div class="sched-card-name"><?php echo htmlspecialchars($s['section_name']); ?></div>
                        <div class="sched-card-meta">
                            <?php echo $s['student_count']; ?> student<?php echo $s['student_count'] != 1 ? 's' : ''; ?>
                            <?php if($s['uploaded_at']): ?>
                            &nbsp;·&nbsp; Updated <?php echo date('M d, Y', strtotime($s['uploaded_at'])); ?>
                            <?php endif; ?>
                        </div>
                        <?php if($s['image_path']): ?>
                        <div class="sched-card-actions">
                            <a href="<?php echo htmlspecialchars($s['image_path']); ?>" target="_blank" class="act-btn">View Full</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Remove schedule image for <?php echo htmlspecialchars($s['section_name']); ?>?')">
                                <input type="hidden" name="delete_section" value="<?php echo htmlspecialchars($s['section_name']); ?>">
                                <button type="submit" name="delete_schedule" class="act-btn danger">Remove</button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
function previewImage(input){
    const wrap = document.getElementById('imgPreviewWrap');
    const img  = document.getElementById('imgPreview');
    if(input.files && input.files[0]){
        const reader = new FileReader();
        reader.onload = e => { img.src = e.target.result; wrap.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}

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
