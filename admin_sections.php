<?php
include 'db.php';
include 'session_config.php';

if(!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.html");
   exit();
    exit();
}

$msg   = '';
$error = '';

// Add section
if(isset($_POST['add_section'])){
    csrf_verify();
    $grade   = trim($_POST['grade_level']);
    $strand  = trim($_POST['strand']);
    $name    = trim($_POST['section_name']);

    if($name === ''){
        $error = "Section name is required.";
    } else {
        // Check duplicate
        $chk = $conn->prepare("SELECT id FROM sections WHERE section_name=?");
        $chk->bind_param("s", $name);
        $chk->execute();
        $chk->store_result();

        if($chk->num_rows > 0){
            $error = "Section \"$name\" already exists.";
        } else {
            $tid = (int)($_POST['teacher_id'] ?? 0) ?: null;
            $ins = $conn->prepare("INSERT INTO sections (grade_level, strand, section_name, teacher_id) VALUES (?,?,?,?)");
            $ins->bind_param("sssi", $grade, $strand, $name, $tid);
            $ins->execute();
            $msg = "Section \"$name\" added.";
        }
    }
}

// Delete section
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    $conn->prepare("DELETE FROM sections WHERE id=?")->bind_param("i",$id);
    $del = $conn->prepare("DELETE FROM sections WHERE id=?");
    $del->bind_param("i", $id);
    $del->execute();
    header("Location: admin_sections.php?msg=deleted");
   exit();
    exit();
}

if(isset($_GET['msg']) && $_GET['msg'] === 'deleted') $msg = "Section deleted.";

// Edit section
if(isset($_POST['edit_section'])){
    csrf_verify();
    $id     = (int)$_POST['edit_id'];
    $grade  = trim($_POST['grade_level']);
    $strand = trim($_POST['strand']);
    $name   = trim($_POST['section_name']);

    $tid = (int)($_POST['teacher_id'] ?? 0) ?: null;
    $upd = $conn->prepare("UPDATE sections SET grade_level=?, strand=?, section_name=?, teacher_id=? WHERE id=?");
    $upd->bind_param("sssii", $grade, $strand, $name, $tid, $id);
    $upd->execute();
    $msg = "Section updated.";
}

// Fetch all
$sections = $conn->query("
    SELECT s.*, u.fullname as teacher_name
    FROM sections s
    LEFT JOIN users u ON u.id = s.teacher_id
    ORDER BY s.grade_level, s.strand, s.section_name
");
$teachers = $conn->query("SELECT id, fullname FROM users WHERE role='teacher' ORDER BY fullname");
$teachers_arr = $teachers->fetch_all(MYSQLI_ASSOC);

// Count students per section
$student_counts = [];
$sc = $conn->query("SELECT section, COUNT(*) as c FROM users WHERE role='student' AND section IS NOT NULL GROUP BY section");
while($r = $sc->fetch_assoc()) $student_counts[$r['section']] = $r['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - Sections</title>
<link rel="stylesheet" href="admin.css">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
.two-col { display: grid; grid-template-columns: 320px 1fr; gap: 20px; align-items: start; }

.form-group { margin-bottom: 14px; }
.form-group label {
    display: block; font-size: 12px; color: var(--muted);
    margin-bottom: 5px; text-transform: uppercase; letter-spacing: .05em;
}
.form-group input,
.form-group select {
    width: 100%; background: var(--bg); border: 1px solid var(--border);
    color: var(--text); padding: 9px 12px; border-radius: 8px;
    font-size: 14px; font-family: 'DM Sans', sans-serif;
    outline: none; box-sizing: border-box; transition: border-color .15s;
}
.form-group input:focus,
.form-group select:focus { border-color: var(--accent); }

.btn-primary {
    width: 100%; background: var(--accent); color: #000; border: none;
    padding: 10px; border-radius: 8px; font-weight: 700; font-size: 14px;
    cursor: pointer; font-family: 'DM Sans', sans-serif;
}
.btn-primary:hover { opacity: .9; }

.sec-table { width: 100%; border-collapse: collapse; }
.sec-table th {
    background: var(--bg); color: var(--muted); font-size: 11px;
    letter-spacing: .07em; text-transform: uppercase;
    padding: 10px 14px; text-align: left; border-bottom: 1px solid var(--border);
}
.sec-table td {
    padding: 12px 14px; border-bottom: 1px solid var(--border);
    font-size: 13px; vertical-align: middle;
}
.sec-table tr:last-child td { border-bottom: none; }
.sec-table tr:hover td { background: var(--bg); }

.act-btn {
    font-size: 12px; padding: 5px 12px; border-radius: 7px;
    border: 1px solid var(--border); background: transparent;
    color: var(--muted); cursor: pointer; text-decoration: none;
    font-family: 'DM Sans', sans-serif; transition: all .15s; display: inline-block;
}
.act-btn:hover        { border-color: #444; color: var(--text); }
.act-btn.danger:hover { border-color: var(--accent3); color: var(--accent3); }

.pill { font-size: 11px; padding: 2px 9px; border-radius: 20px; font-weight: 500; }
.pill-muted  { background: var(--border); color: var(--muted); }
.pill-accent { background: rgba(240,180,41,.12); color: var(--accent); }

.alert { padding: 10px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; }
.alert-green { background: rgba(62,207,142,.1); border: 1px solid rgba(62,207,142,.3); color: var(--accent2); }
.alert-red   { background: rgba(226,92,92,.1);  border: 1px solid rgba(226,92,92,.3);  color: var(--accent3); }

/* Edit modal */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:999; justify-content:center; align-items:center; }
.modal-overlay.open { display:flex; }
.modal { background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:28px; width:340px; }
.modal h3 { font-family:'Syne',sans-serif; font-size:16px; font-weight:700; margin-bottom:16px; }
.modal-btns { display:flex; gap:8px; margin-top:4px; }
.modal-btns button { flex:1; padding:9px; border-radius:8px; font-size:13px; cursor:pointer; font-family:'DM Sans',sans-serif; border:1px solid var(--border); }
.btn-cancel { background:transparent; color:var(--muted); }
.btn-save   { background:var(--accent); color:#000; border-color:var(--accent); font-weight:700; }
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
        <a href="admin_sections.php" class="active"><span class="icon">▣</span> Sections</a>
        <a href="admin_timeslots.php"><span class="icon">◫</span> Time Slots</a>
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
                <h1>Sections</h1>
                <p>Manage grade sections</p>
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

    <?php if($msg): ?>
    <div class="alert alert-green"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <?php if($error): ?>
    <div class="alert alert-red"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="two-col">

        <!-- ADD FORM -->
        <div class="panel">
            <div class="panel-header">
                <h3>Add Section</h3>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <div class="form-group">
                    <label>Grade Level</label>
                    <select name="grade_level">
                        <option value="">Select grade</option>
                        <option value="Grade 11">Grade 11</option>
                        <option value="Grade 12">Grade 12</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Strand</label>
                    <select name="strand">
                        <option value="">Select strand</option>
                        <option value="ICT">ICT</option>
                        <option value="STEM">STEM</option>
                        <option value="ABM">ABM</option>
                        <option value="HUMSS">HUMSS</option>
                        <option value="TVL">TVL</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Assign Teacher</label>
                    <select name="teacher_id">
                        <option value="">No teacher yet</option>
                        <?php foreach($teachers_arr as $t): ?>
                        <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['fullname']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Section Name <span style="color:var(--accent3);">*</span></label>
                    <input name="section_name" placeholder="ICT-11A" required>
                </div>
                <button type="submit" name="add_section" class="btn-primary">Add Section</button>
            </form>
        </div>

        <!-- SECTIONS TABLE -->
        <div class="panel">
            <div class="panel-header">
                <h3>All Sections</h3>
                <span class="badge badge-yellow"><?php echo $sections->num_rows; ?> sections</span>
            </div>

            <?php if($sections->num_rows > 0): ?>
            <table class="sec-table">
                <tr>
                    <th>Section Name</th>
                    <th>Grade</th>
                    <th>Strand</th>
                    <th>Teacher</th>
                    <th>Students</th>
                    <th>Actions</th>
                </tr>
                <?php while($s = $sections->fetch_assoc()):
                    $count = $student_counts[$s['section_name']] ?? 0;
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($s['section_name']); ?></strong></td>
                    <td style="color:var(--muted);"><?php echo htmlspecialchars($s['grade_level'] ?: '—'); ?></td>
                    <td>
                        <?php if($s['strand']): ?>
                        <span class="pill pill-accent"><?php echo htmlspecialchars($s['strand']); ?></span>
                        <?php else: ?>
                        <span style="color:var(--border);">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--muted); font-size:13px;"><?php echo htmlspecialchars($s['teacher_name'] ?? '—'); ?></td>
                    <td><span class="pill pill-muted"><?php echo $count; ?></span></td>
                    <td>
                        <div style="display:flex; gap:6px;">
                            <button class="act-btn" onclick="openEdit(<?php echo $s['id']; ?>,'<?php echo htmlspecialchars($s['grade_level']); ?>','<?php echo htmlspecialchars($s['strand']); ?>','<?php echo htmlspecialchars($s['section_name']); ?>','<?php echo (int)($s['teacher_id'] ?? 0); ?>')">Edit</button>
                            <a href="admin_sections.php?delete=<?php echo $s['id']; ?>" class="act-btn danger" onclick="return confirm('Delete section <?php echo htmlspecialchars($s['section_name']); ?>?')">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
            <?php else: ?>
            <p style="color:var(--muted); font-size:13px; padding:20px 0; text-align:center;">No sections yet. Add one on the left.</p>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <h3>Edit Section</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="edit_id" id="edit_id">
            <div class="form-group">
                <label>Grade Level</label>
                <select name="grade_level" id="edit_grade">
                    <option value="">Select grade</option>
                    <option value="Grade 11">Grade 11</option>
                    <option value="Grade 12">Grade 12</option>
                </select>
            </div>
            <div class="form-group">
                <label>Strand</label>
                <select name="strand" id="edit_strand">
                    <option value="">Select strand</option>
                    <option value="ICT">ICT</option>
                    <option value="STEM">STEM</option>
                    <option value="ABM">ABM</option>
                    <option value="HUMSS">HUMSS</option>
                    <option value="TVL">TVL</option>
                </select>
            </div>
            <div class="form-group">
                <label>Assign Teacher</label>
                <select name="teacher_id" id="edit_teacher">
                    <option value="">No teacher yet</option>
                    <?php foreach($teachers_arr as $t): ?>
                    <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['fullname']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Section Name</label>
                <input name="section_name" id="edit_name" required>
            </div>
            <div class="modal-btns">
                <button type="button" class="btn-cancel" onclick="closeEdit()">Cancel</button>
                <button type="submit" name="edit_section" class="btn-save">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(id, grade, strand, name, teacherId){
    document.getElementById('edit_id').value    = id;
    document.getElementById('edit_name').value  = name;
    setSelect('edit_grade',   grade);
    setSelect('edit_strand',  strand);
    setSelect('edit_teacher', String(teacherId));
    document.getElementById('editModal').classList.add('open');
}
function closeEdit(){
    document.getElementById('editModal').classList.remove('open');
}
function setSelect(id, val){
    const sel = document.getElementById(id);
    for(let o of sel.options) o.selected = (o.value === val);
}
document.getElementById('editModal').addEventListener('click', function(e){
    if(e.target === this) closeEdit();
});
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
