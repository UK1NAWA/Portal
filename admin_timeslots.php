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

if(isset($_POST['add_slot'])){
    csrf_verify();
    $label = trim($_POST['label']);
    if($label === ''){
        $error = "Time slot label is required.";
    } else {
        $chk = $conn->prepare("SELECT id FROM time_slots WHERE label=?");
        $chk->bind_param("s", $label);
        $chk->execute();
        $chk->store_result();
        if($chk->num_rows > 0){
            $error = "\"$label\" already exists.";
        } else {
            $ins = $conn->prepare("INSERT INTO time_slots (label) VALUES (?)");
            $ins->bind_param("s", $label);
            $ins->execute();
            $msg = "Time slot \"$label\" added.";
        }
    }
}

// Delete time slot
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    // Check if used in schedules
    $used = $conn->prepare("SELECT id FROM section_schedules WHERE time_slot_id=? LIMIT 1");
    $used->bind_param("i", $id);
    $used->execute();
    $used->store_result();
    if($used->num_rows > 0){
        $error = "Cannot delete — this time slot is used in existing schedules.";
    } else {
        $del = $conn->prepare("DELETE FROM time_slots WHERE id=?");
        $del->bind_param("i", $id);
        $del->execute();
        header("Location: admin_timeslots.php?msg=deleted");
       exit();
        exit();
    }
}

if(isset($_GET['msg']) && $_GET['msg'] === 'deleted') $msg = "Time slot deleted.";

// Edit time slot
if(isset($_POST['edit_slot'])){
    csrf_verify();
    $id    = (int)$_POST['edit_id'];
    $label = trim($_POST['label']);
    $upd   = $conn->prepare("UPDATE time_slots SET label=? WHERE id=?");
    $upd->bind_param("si", $label, $id);
    $upd->execute();
    $msg = "Time slot updated.";
}

$slots = $conn->query("SELECT * FROM time_slots ORDER BY id");
$total = $slots->num_rows;
$slots_arr = $slots->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - Time Slots</title>
<link rel="stylesheet" href="admin.css">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
.two-col { display: grid; grid-template-columns: 300px 1fr; gap: 20px; align-items: start; }

.form-group { margin-bottom: 14px; }
.form-group label {
    display: block; font-size: 12px; color: var(--muted);
    margin-bottom: 5px; text-transform: uppercase; letter-spacing: .05em;
}
.form-group input {
    width: 100%; background: var(--bg); border: 1px solid var(--border);
    color: var(--text); padding: 9px 12px; border-radius: 8px;
    font-size: 14px; font-family: 'DM Sans', sans-serif;
    outline: none; box-sizing: border-box; transition: border-color .15s;
}
.form-group input:focus { border-color: var(--accent); }
.form-group small { font-size: 11px; color: var(--muted); margin-top: 4px; display: block; }

.btn-primary {
    width: 100%; background: var(--accent); color: #000; border: none;
    padding: 10px; border-radius: 8px; font-weight: 700; font-size: 14px;
    cursor: pointer; font-family: 'DM Sans', sans-serif;
}
.btn-primary:hover { opacity: .9; }

.slots-list { display: flex; flex-direction: column; gap: 8px; }
.slot-row {
    display: flex; align-items: center; gap: 10px;
    background: var(--bg); border: 1px solid var(--border);
    border-radius: 10px; padding: 12px 14px;
    font-size: 14px; transition: border-color .15s;
}
.slot-row:hover { border-color: #333; }
.slot-num {
    font-size: 11px; color: var(--muted); min-width: 24px; text-align: center;
}
.slot-label { flex: 1; font-weight: 500; color: var(--text); }
.slot-used {
    font-size: 11px; background: var(--border); color: var(--muted);
    padding: 2px 9px; border-radius: 20px;
}
.slot-actions { display: flex; gap: 5px; align-items: center; }

.act-btn {
    font-size: 12px; padding: 4px 10px; border-radius: 7px;
    border: 1px solid var(--border); background: transparent;
    color: var(--muted); cursor: pointer; text-decoration: none;
    font-family: 'DM Sans', sans-serif; transition: all .15s; display: inline-block;
}
.act-btn:hover        { border-color: #444; color: var(--text); }
.act-btn.danger:hover { border-color: var(--accent3); color: var(--accent3); }

.alert { padding: 10px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; }
.alert-green { background: rgba(62,207,142,.1); border: 1px solid rgba(62,207,142,.3); color: var(--accent2); }
.alert-red   { background: rgba(226,92,92,.1);  border: 1px solid rgba(226,92,92,.3);  color: var(--accent3); }

/* Edit modal */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:999; justify-content:center; align-items:center; }
.modal-overlay.open { display:flex; }
.modal { background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:28px; width:320px; }
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
        <a href="admin_sections.php"><span class="icon">▣</span> Sections</a>
        <a href="admin_timeslots.php" class="active"><span class="icon">◫</span> Time Slots</a>
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
                <h1>Time Slots</h1>
                <p>Manage class periods shown in the timetable</p>
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
        <div>
            <div class="panel">
                <div class="panel-header"><h3>Add Time Slot</h3></div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <div class="form-group">
                        <label>Label</label>
                        <input name="label" placeholder="e.g. 7:00 - 8:00" required>
                        <small>Use a consistent format like 7:00 - 8:00</small>
                    </div>
                    <button type="submit" name="add_slot" class="btn-primary">Add Time Slot</button>
                </form>
            </div>


        </div>

        <!-- SLOTS LIST -->
        <div class="panel">
            <div class="panel-header">
                <h3>All Time Slots</h3>
                <span class="badge badge-yellow"><?php echo $total; ?> slots</span>
            </div>

            <?php if(!empty($slots_arr)): ?>
            <div class="slots-list">
                <?php foreach($slots_arr as $i => $slot):
                    // Check usage count
                    $u = $conn->prepare("SELECT COUNT(*) as c FROM section_schedules WHERE time_slot_id=?");
                    $u->bind_param("i", $slot['id']);
                    $u->execute();
                    $usage = $u->get_result()->fetch_assoc()['c'];
                ?>
                <div class="slot-row">
                    <span class="slot-num"><?php echo $i+1; ?></span>
                    <span class="slot-label"><?php echo htmlspecialchars($slot['label']); ?></span>
                    <?php if($usage > 0): ?>
                    <span class="slot-used"><?php echo $usage; ?> classes</span>
                    <?php endif; ?>
                    <div class="slot-actions">
                        <button class="act-btn" onclick="openEdit(<?php echo $slot['id']; ?>,'<?php echo htmlspecialchars($slot['label']); ?>')">Edit</button>
                        <?php if($usage === 0): ?>
                        <a href="admin_timeslots.php?delete=<?php echo $slot['id']; ?>" class="act-btn danger" onclick="return confirm('Delete this time slot?')">Delete</a>
                        <?php else: ?>
                        <span class="act-btn" style="cursor:default; opacity:.4;" title="In use — cannot delete">Delete</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p style="color:var(--muted); font-size:13px; padding:20px 0; text-align:center;">No time slots yet. Add one on the left.</p>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <h3>Edit Time Slot</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="edit_id" id="edit_id">
            <div class="form-group">
                <label>Label</label>
                <input name="label" id="edit_label" required>
            </div>
            <div class="modal-btns">
                <button type="button" class="btn-cancel" onclick="closeEdit()">Cancel</button>
                <button type="submit" name="edit_slot" class="btn-save">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(id, label){
    document.getElementById('edit_id').value    = id;
    document.getElementById('edit_label').value = label;
    document.getElementById('editModal').classList.add('open');
}
function closeEdit(){
    document.getElementById('editModal').classList.remove('open');
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
