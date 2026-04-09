<?php
include 'db.php';
include_once __DIR__ . '/cache.php';
include 'session_config.php';

if(!isset($_SESSION['loggedIn'])){
    header("Location: login.html");
    exit();
}

$student_id = $_SESSION['id'];

// Profile — personal data, never cached
$pq = $conn->prepare("SELECT fullname, email, contact_number, student_id_no, year_level, strand, username, role, section FROM users WHERE id=?");
$pq->bind_param("i", $student_id);
$pq->execute();
$profile = $pq->get_result()->fetch_assoc();
$student_section = $profile['section'] ?? null;
$_SESSION['section'] = $student_section;
?>
<!DOCTYPE html>
<?php

$today_schedules   = [];
$all_schedules     = [];
$schedule_is_draft = false;

if($student_section){
    $today = date('D');

    // Cache today's schedule per section+day — 5 min TTL
    $cache_key_today = 'sched_today_' . md5($student_section . $today);
    $today_schedules = Cache::get($cache_key_today);
    if($today_schedules === null){
        $stmt = $conn->prepare("
            SELECT ts.label AS time, ss.subject, ss.instructor, ss.room, ss.day
            FROM section_schedules ss
            JOIN time_slots ts ON ts.id = ss.time_slot_id
            WHERE ss.section = ? AND ss.day = ? AND ss.status = 'published'
            ORDER BY ts.id
        ");
        $stmt->bind_param("ss", $student_section, $today);
        $stmt->execute();
        $today_schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        Cache::set($cache_key_today, $today_schedules, 300);
    }

    // Cache full week schedule per section — 5 min TTL
    $cache_key_week = 'sched_week_' . md5($student_section);
    $all_schedules  = Cache::get($cache_key_week);
    if($all_schedules === null){
        $stmt2 = $conn->prepare("
            SELECT ts.label AS time, ss.subject, ss.instructor, ss.room, ss.day
            FROM section_schedules ss
            JOIN time_slots ts ON ts.id = ss.time_slot_id
            WHERE ss.section = ? AND ss.status = 'published'
            ORDER BY FIELD(ss.day,'Mon','Tue','Wed','Thu','Fri','Sat'), ts.id
        ");
        $stmt2->bind_param("s", $student_section);
        $stmt2->execute();
        $all_schedules = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
        Cache::set($cache_key_week, $all_schedules, 300);
    }

    if(empty($all_schedules)){
        $chk_draft = $conn->prepare("SELECT id FROM section_schedules WHERE section=? LIMIT 1");
        $chk_draft->bind_param("s", $student_section);
        $chk_draft->execute();
        $chk_draft->store_result();
        if($chk_draft->num_rows > 0) $schedule_is_draft = true;
    }
}





// Grades — cache per student, 5 min TTL
$grades_by_quarter = [];
$cache_key_grades  = 'grades_' . $student_id;
$grade_rows        = Cache::get($cache_key_grades);
if($grade_rows === null){
    $grade_q = $conn->prepare("
        SELECT quarter, subject, grade, remarks
        FROM student_grades
        WHERE student_id = ?
        ORDER BY FIELD(quarter,'Q1','Q2','Q3','Q4'), subject
    ");
    $grade_q->bind_param("i", $student_id);
    $grade_q->execute();
    $grade_rows = $grade_q->get_result()->fetch_all(MYSQLI_ASSOC);
    Cache::set($cache_key_grades, $grade_rows, 300);
}
foreach($grade_rows as $gr){
    $grades_by_quarter[$gr['quarter']][] = $gr;
}



?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Portal</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="dashboard">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <h2 class="logo">Student Portal</h2>
        <nav>
            <a href="#" class="active" onclick="showSection('dashboardSection', this, event)">Dashboard</a>
            <a href="#" onclick="showSection('gradesSection', this, event)">Grades</a>
            <a href="#" onclick="showSection('scheduleSection', this, event)">Schedule</a>
            <a href="logout.php">Logout</a>
        </nav>
    </aside>

    <!-- CONTENT -->
    <div class="content-area">

<div class="top-header">
<label class="theme-switch">
    <input type="checkbox" id="themeToggle">
    <span class="slider"></span>
</label>

<button class="hamburger" onclick="toggleMenu()">☰</button>

    
    <div class="top-right">
        <a href="#" id="personalInfoLink" class="header-link" style="color:white;">Personal Information</a>
        <span class="user-name" style="margin-left:30px; margin-right:10px; font-size:20px;"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
    </div>
</div>


        <main class="main-content">

        
            <div class="dashboard-grid" id="dashboardSection">

 <!-------LEFT COLUMN---------->
<div class="left-column">
    <div class="top-widgets">
    <!------------------GRADES SNAPSHOT---------->
    <div class="course-widget dark-card">
        <h2>Grades</h2>
        <?php
        // Find the most recent quarter that has data
        $snap_quarter = null;
        foreach(['Q4','Q3','Q2','Q1'] as $q){
            if(!empty($grades_by_quarter[$q])){ $snap_quarter = $q; break; }
        }
        ?>
        <?php if(!$snap_quarter): ?>
            <div class="grades-snap-empty">No grades posted yet.</div>
        <?php else:
            $snap_rows = $grades_by_quarter[$snap_quarter];
            $valid = array_filter(array_column($snap_rows,'grade'), fn($g) => $g !== null);
            $avg   = count($valid) ? round(array_sum($valid)/count($valid),1) : null;
        ?>
        <div class="grades-snap-header">
            <span class="grades-snap-quarter"><?php echo $snap_quarter; ?></span>
            <?php if($avg !== null): ?>
            <span class="grades-snap-avg <?php echo $avg >= 75 ? 'pass' : 'fail'; ?>">
                avg <?php echo $avg; ?>
            </span>
            <?php endif; ?>
        </div>
        <div class="grades-snap-list">
            <?php foreach(array_slice($snap_rows, 0, 5) as $r):
                $g = $r['grade'];
                $pass = $g !== null && $g >= 75;
                $cls  = $g === null ? 'pending' : ($pass ? 'pass' : 'fail');
            ?>
            <div class="grades-snap-row">
                <span class="grades-snap-subj"><?php echo htmlspecialchars($r['subject']); ?></span>
                <span class="grades-snap-val <?php echo $cls; ?>">
                    <?php echo $g !== null ? $g : '—'; ?>
                </span>
            </div>
            <?php endforeach; ?>
            <?php if(count($snap_rows) > 5): ?>
            <div class="grades-snap-more">+<?php echo count($snap_rows)-5; ?> more subjects</div>
            <?php endif; ?>
        </div>
        <button class="save" style="margin-top:12px;" onclick="showSection('gradesSection',this,event)">View All Grades</button>
        <?php endif; ?>
    </div>
</div>
<!----------------------TODAY'S SCHEDULE------------------>
<div class="mini-schedule-box dark-card">
    <h2>Today's Schedule</h2>

    <?php if(empty($today_schedules)): ?>
        <?php if(!$student_section): ?>
            <div class="no-class-msg">You have not been assigned to a section yet.</div>
        <?php elseif($schedule_is_draft): ?>
            <div class="no-class-msg">Your schedule is being prepared. Check back soon.</div>
        <?php else: ?>
            <div class="no-class-msg">No classes today.</div>
        <?php endif; ?>
    <?php else: ?>
        <?php foreach($today_schedules as $row): ?>
        <div class="today-item">
            <div class="today-time"><?php echo htmlspecialchars($row['time']); ?></div>
            <div class="today-details">
                <span class="today-subject"><?php echo htmlspecialchars($row['subject']); ?></span>
                <span class="today-meta"><?php echo htmlspecialchars($row['instructor']); ?> &mdash; Room <?php echo htmlspecialchars($row['room']); ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <button class="save" onclick="showSection('scheduleSection', this, event)">
        View Full Schedule
    </button>
</div>


</div>

<!--------------------RIGHT COLUMN---------------->
<div class="right-column">
</div>
</div>

            <!--------------PERSONAL INFO FULL PAGE---------------->
            <div id="personalInfoSection" class="personal-info-section" style="display:none;">
                <div class="info-box">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
                        <h2 style="margin:0;">Personal Information</h2>
                        <button class="save" id="backToDashboard" style="margin:0;">&#8592; Back</button>
                    </div>

                    <div id="profileSuccessMsg" style="display:none; background:rgba(62,207,142,0.12); border:1px solid rgba(62,207,142,0.3); color:var(--accent2); border-radius:8px; padding:10px 14px; font-size:13px; margin-bottom:14px;"></div>
                    <div id="profileErrorMsg"   style="display:none; background:rgba(239,68,68,0.1);  border:1px solid rgba(239,68,68,0.3);  color:#f87171;             border-radius:8px; padding:10px 14px; font-size:13px; margin-bottom:14px;"></div>

                    <div class="grid">
                        <!-- Editable by all roles -->
                        <div class="box">
                            <label>Full Name</label>
                            <input type="text" id="pi_fullname" value="<?php echo htmlspecialchars($profile['fullname'] ?? ''); ?>">
                        </div>
                        <div class="box">
                            <label>Email</label>
                            <input type="email" id="pi_email" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>">
                        </div>
                        <div class="box">
                            <label>Contact Number</label>
                            <input type="text" id="pi_contact" value="<?php echo htmlspecialchars($profile['contact_number'] ?? ''); ?>" placeholder="">
                        </div>

                        <?php $is_privileged = in_array($_SESSION['role'], ['teacher','admin']); ?>

                        <!-- Editable only by teacher/admin -->
                        <div class="box">
                            <label>Username</label>
                            <input type="text" id="pi_username"
                                value="<?php echo htmlspecialchars($profile['username'] ?? ''); ?>"
                                <?php echo $is_privileged ? '' : 'readonly'; ?>>
                        </div>
                        <div class="box">
                            <label>Student ID</label>
                            <input type="text" id="pi_student_id"
                                value="<?php echo htmlspecialchars($profile['student_id_no'] ?? 'Not yet assigned'); ?>"
                                readonly
                                style="<?php echo empty($profile['student_id_no']) ? 'color:var(--muted);font-style:italic;' : ''; ?>">
                        </div>
                        
                        <div class="box">
                            <label>Section</label>
                            <input type="text" id="pi_section"
                                value="<?php echo htmlspecialchars($profile['section'] ?? ''); ?>"
                                <?php echo $is_privileged ? '' : 'readonly'; ?>
                                placeholder="<?php echo $is_privileged ? : '—'; ?>">
                        </div>
                    </div>

                    <div style="margin-top:20px; display:flex; gap:10px; flex-wrap:wrap;">
                        <button class="save" id="saveProfileBtn" onclick="saveProfile()">Save Changes</button>
                    </div>

                    <!-- ── Change Password ── -->
                    <div class="pw-change-section">
                        <div class="pw-change-toggle" onclick="togglePwForm()">
                            <span>&#128274; Change Password</span>
                            <span id="pwToggleIcon">&#9660;</span>
                        </div>
                        <div id="pwChangeForm" style="display:none;">
                            <div id="pwSuccessMsg" style="display:none; background:rgba(62,207,142,0.12); border:1px solid rgba(62,207,142,0.3); color:var(--accent2); border-radius:8px; padding:10px 14px; font-size:13px; margin-bottom:12px;"></div>
                            <div id="pwErrorMsg"   style="display:none; background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); color:#f87171; border-radius:8px; padding:10px 14px; font-size:13px; margin-bottom:12px;"></div>
                            <div class="grid" style="margin-bottom:0;">
                                <div class="box">
                                    <label>Current Password</label>
                                    <input type="password" id="pw_current" autocomplete="current-password" placeholder="Enter current password">
                                </div>
                                <div class="box">
                                    <label>New Password <span style="font-size:11px; color:#6b7280;">(min 8 characters)</span></label>
                                    <input type="password" id="pw_new" autocomplete="new-password" placeholder="Enter new password">
                                </div>
                                <div class="box">
                                    <label>Confirm New Password</label>
                                    <input type="password" id="pw_confirm" autocomplete="new-password" placeholder="Repeat new password">
                                </div>
                            </div>
                            <!-- Password strength bar -->
                            <div style="margin-top:10px;">
                                <div style="height:4px; background:rgba(255,255,255,0.08); border-radius:99px; overflow:hidden;">
                                    <div id="pwStrengthBar" style="height:100%; width:0%; border-radius:99px; transition:width 0.3s, background 0.3s;"></div>
                                </div>
                                <div id="pwStrengthLabel" style="font-size:11px; color:#6b7280; margin-top:4px;"></div>
                            </div>
                            <button class="save" style="margin-top:14px;" onclick="submitPasswordChange()">Update Password</button>
                        </div>
                    </div>
                </div>
            </div>

            <!---------------- SECTIONS --------------------->

            <!-- GRADES -->
            <div id="gradesSection" style="display:none;">
                <h2 style="margin:0 0 16px;">Grades</h2>

                <?php if(empty($grades_by_quarter)): ?>
                <p style="color:#6b7280; font-size:14px;">No grades available yet. Check back after your teacher submits grades.</p>

                <?php else: ?>

                <?php
                $quarters = ['Q1','Q2','Q3','Q4'];
                foreach($quarters as $qtr):
                    if(!isset($grades_by_quarter[$qtr])) continue;
                    $rows = $grades_by_quarter[$qtr];
                    $valid_grades = array_filter(array_column($rows, 'grade'), fn($g) => $g !== null);
                    $avg = count($valid_grades) > 0 ? round(array_sum($valid_grades) / count($valid_grades), 2) : null;
                ?>
                <div class="dark-card" style="margin-bottom:14px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                        <strong style="font-size:15px;"><?php echo $qtr; ?></strong>
                        <?php if($avg !== null): ?>
                        <span style="font-size:13px; color:<?php echo $avg >= 75 ? 'var(--accent2)' : 'var(--accent3)'; ?>;">
                            Average: <?php echo $avg; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="font-size:11px; color:#6b7280; text-transform:uppercase; letter-spacing:0.06em;">
                                <th style="text-align:left; padding:6px 8px; border-bottom:1px solid #1e2229;">Subject</th>
                                <th style="text-align:center; padding:6px 8px; border-bottom:1px solid #1e2229;">Grade</th>
                                <th style="text-align:center; padding:6px 8px; border-bottom:1px solid #1e2229;">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($rows as $r): ?>
                        <tr style="font-size:13px;">
                            <td style="padding:9px 8px; border-bottom:1px solid #1e2229;"><?php echo htmlspecialchars($r['subject']); ?></td>
                            <td style="padding:9px 8px; border-bottom:1px solid #1e2229; text-align:center; font-weight:600;
                                color:<?php echo ($r['grade'] !== null && $r['grade'] >= 75) ? 'var(--accent)' : 'var(--accent3)'; ?>;">
                                <?php echo $r['grade'] !== null ? $r['grade'] : '—'; ?>
                            </td>
                            <td style="padding:9px 8px; border-bottom:1px solid #1e2229; text-align:center; font-size:12px; color:#6b7280;">
                                <?php echo htmlspecialchars($r['remarks'] ?? '—'); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- SCHEDULE -->
            <div class="portal" id="scheduleSection" style="display:none;">

                <div class="sched-header">
                    <h2>Weekly Schedule</h2>
                    <?php if($_SESSION['section']): ?>
                    <span class="sched-section-badge"><?php echo htmlspecialchars($_SESSION['section']); ?></span>
                    <?php endif; ?>
                </div>

                <?php if(empty($all_schedules)): ?>
                <?php if(!$student_section): ?>
                <div class="sched-empty">You have not been assigned to a section yet. Please contact your teacher or admin.</div>
                <?php elseif($schedule_is_draft): ?>
                <div class="sched-empty">Your schedule for <strong><?php echo htmlspecialchars($student_section); ?></strong> is still being prepared. It will appear here once published.</div>
                <?php else: ?>
                <div class="sched-empty">No published schedule for your section yet.</div>
                <?php endif; ?>
                <?php else:
                    $day_order = ['Mon','Tue','Wed','Thu','Fri','Sat'];

                    // Build lookup: [time_label][day] = row
                    // Also collect all unique time slots in order
                    $slot_map  = [];
                    $all_times = [];
                    foreach($all_schedules as $row){
                        $slot_map[$row['time']][$row['day']] = $row;
                        if(!in_array($row['time'], $all_times)) $all_times[] = $row['time'];
                    }

                    // Detect which days actually have data
                    $active_days = [];
                    foreach($day_order as $d){
                        foreach($all_schedules as $row){
                            if($row['day'] === $d){ $active_days[] = $d; break; }
                        }
                    }

                    // Separate morning (before 12:00) and afternoon (12:00+)
                    // Based on the start hour of the time label e.g. "7:30 - 8:30"
                    $morning_slots   = [];
                    $afternoon_slots = [];
                    foreach($all_times as $t){
                        $start_hour = (int)explode(':', $t)[0];
                        if($start_hour < 12) $morning_slots[] = $t;
                        else                  $afternoon_slots[] = $t;
                    }
                ?>
                <div style="overflow-x:auto;">
                <table class="sched-grid-table">
                    <thead>
                        <tr>
                            <th class="sched-time-col"></th>
                            <?php foreach($active_days as $d): ?>
                            <th class="sched-day-col"><?php echo $d; ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>

                    <?php if(!empty($morning_slots)): ?>
                    <!-- MORNING DIVIDER -->
                    <tr>
                        <td colspan="<?php echo count($active_days) + 1; ?>" class="sched-period-divider">
                            ☀ Morning
                        </td>
                    </tr>
                    <?php foreach($morning_slots as $time): ?>
                    <tr>
                        <td class="sched-time-cell"><?php echo htmlspecialchars($time); ?></td>
                        <?php foreach($active_days as $d): ?>
                        <td class="sched-cell">
                            <?php if(isset($slot_map[$time][$d])): $r = $slot_map[$time][$d]; ?>
                            <div class="sched-cell-subj"><?php echo htmlspecialchars($r['subject']); ?></div>
                            <div class="sched-cell-room"><?php echo htmlspecialchars($r['room']); ?></div>
                            <div class="sched-cell-meta"><?php echo htmlspecialchars($r['instructor']); ?></div>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; endif; ?>

                    <?php if(!empty($afternoon_slots)): ?>
                    <!-- AFTERNOON DIVIDER -->
                    <tr>
                        <td colspan="<?php echo count($active_days) + 1; ?>" class="sched-period-divider afternoon">
                            ◑ Afternoon
                        </td>
                    </tr>
                    <?php foreach($afternoon_slots as $time): ?>
                    <tr>
                        <td class="sched-time-cell"><?php echo htmlspecialchars($time); ?></td>
                        <?php foreach($active_days as $d): ?>
                        <td class="sched-cell">
                            <?php if(isset($slot_map[$time][$d])): $r = $slot_map[$time][$d]; ?>
                            <div class="sched-cell-subj"><?php echo htmlspecialchars($r['subject']); ?></div>
                            <div class="sched-cell-room"><?php echo htmlspecialchars($r['room']); ?></div>
                            <div class="sched-cell-meta"><?php echo htmlspecialchars($r['instructor']); ?></div>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; endif; ?>

                    </tbody>
                </table>
                </div>
                <?php endif; ?>

            </div><!-- end scheduleSection -->

        </main>
    </div>
</div>

<script>
const toggle = document.getElementById('themeToggle');

// Load saved theme
let savedTheme = localStorage.getItem('theme') || 'dark';

if(savedTheme === 'light'){
    document.body.classList.add('light-mode');
    toggle.checked = true;
}

// Toggle event
toggle.addEventListener('change', () => {

    if(toggle.checked){
        document.body.classList.add('light-mode');
        localStorage.setItem('theme','light');
    } else {
        document.body.classList.remove('light-mode');
        localStorage.setItem('theme','dark');
    }

});


function toggleMenu(){
    document.querySelector(".sidebar").classList.toggle("open");
}


function showSection(sectionId, element, event){
    event.preventDefault();

    const sections = [
        'dashboardSection',
        'gradesSection',
        'scheduleSection',
    ];

    
    sections.forEach(id => {
        const el = document.getElementById(id);
        if(el) el.style.display = 'none';
    });

    
    const active = document.getElementById(sectionId);
    if(active){
        active.style.display = sectionId === 'dashboardSection' ? 'grid' : 'block';
    }

    
    document.querySelectorAll('.sidebar nav a')
        .forEach(link => link.classList.remove('active'));

    // If element is a nav link highlight it, otherwise find the matching nav link
    if(element.closest('.sidebar')){
        element.classList.add('active');
    } else {
        document.querySelectorAll('.sidebar nav a').forEach(link => {
            if(link.getAttribute('onclick') && link.getAttribute('onclick').includes(sectionId)){
                link.classList.add('active');
            }
        });
    }
}



const personalInfoLink = document.getElementById('personalInfoLink');
const personalInfoSection = document.getElementById('personalInfoSection');
const dashboardSection = document.getElementById('dashboardSection');
const backToDashboard = document.getElementById('backToDashboard');

personalInfoLink.addEventListener('click', function(e){
    e.preventDefault();

document.querySelectorAll(
  '#dashboardSection,#gradesSection,#scheduleSection'
).forEach(el => el.style.display='none');


    personalInfoSection.style.display='block';
});


backToDashboard.addEventListener('click', function(){
    personalInfoSection.style.display = 'none';
    dashboardSection.style.display = 'grid';
});

// ── Password strength meter ───────────────────────────────────────────────
document.addEventListener('input', function(e) {
    if (e.target.id !== 'pw_new') return;
    const val = e.target.value;
    const bar = document.getElementById('pwStrengthBar');
    const lbl = document.getElementById('pwStrengthLabel');
    if (!val) { bar.style.width = '0%'; lbl.textContent = ''; return; }

    let score = 0;
    if (val.length >= 8)  score++;
    if (val.length >= 12) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        { w: '20%', bg: '#f87171', text: 'Very weak' },
        { w: '40%', bg: '#fb923c', text: 'Weak' },
        { w: '60%', bg: '#facc15', text: 'Fair' },
        { w: '80%', bg: '#4ade80', text: 'Strong' },
        { w: '100%',bg: '#3ecf8e', text: 'Very strong' },
    ];
    const l = levels[Math.min(score - 1, 4)];
    bar.style.width      = l.w;
    bar.style.background = l.bg;
    lbl.textContent      = l.text;
    lbl.style.color      = l.bg;
});

function togglePwForm() {
    const form = document.getElementById('pwChangeForm');
    const icon = document.getElementById('pwToggleIcon');
    const open = form.style.display === 'none';
    form.style.display = open ? 'block' : 'none';
    icon.textContent   = open ? '▲' : '▼';
    if (!open) {
        // Clear fields when closing
        ['pw_current','pw_new','pw_confirm'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('pwStrengthBar').style.width = '0%';
        document.getElementById('pwStrengthLabel').textContent = '';
        document.getElementById('pwSuccessMsg').style.display = 'none';
        document.getElementById('pwErrorMsg').style.display   = 'none';
    }
}

function submitPasswordChange() {
    const btn    = document.querySelector('#pwChangeForm .save');
    const sucEl  = document.getElementById('pwSuccessMsg');
    const errEl  = document.getElementById('pwErrorMsg');
    sucEl.style.display = 'none';
    errEl.style.display = 'none';

    const current = document.getElementById('pw_current').value;
    const newPw   = document.getElementById('pw_new').value;
    const confirm = document.getElementById('pw_confirm').value;

    // Client-side pre-checks
    if (!current || !newPw || !confirm) {
        errEl.textContent = '✗ Please fill in all password fields.';
        errEl.style.display = 'block'; return;
    }
    if (newPw.length < 8) {
        errEl.textContent = '✗ New password must be at least 8 characters.';
        errEl.style.display = 'block'; return;
    }
    if (newPw !== confirm) {
        errEl.textContent = '✗ New passwords do not match.';
        errEl.style.display = 'block'; return;
    }
    if (newPw === current) {
        errEl.textContent = '✗ New password must be different from your current password.';
        errEl.style.display = 'block'; return;
    }

    btn.disabled    = true;
    btn.textContent = 'Updating…';

    fetch('change_password.php', {
        method: 'POST',
        body: new URLSearchParams({
            current_password: current,
            new_password:     newPw,
            confirm_password: confirm
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            sucEl.textContent = '✓ ' + data.message;
            sucEl.style.display = 'block';
            ['pw_current','pw_new','pw_confirm'].forEach(id => document.getElementById(id).value = '');
            document.getElementById('pwStrengthBar').style.width = '0%';
            document.getElementById('pwStrengthLabel').textContent = '';
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
        btn.textContent = 'Update Password';
    });
}

// ── Save Profile via AJAX ──────────────────────────────────
function saveProfile() {
    const btn     = document.getElementById('saveProfileBtn');
    const success = document.getElementById('profileSuccessMsg');
    const error   = document.getElementById('profileErrorMsg');

    success.style.display = 'none';
    error.style.display   = 'none';

    const body = new URLSearchParams({
        fullname:   document.getElementById('pi_fullname').value.trim(),
        email:      document.getElementById('pi_email').value.trim(),
        contact:    document.getElementById('pi_contact').value.trim(),
        username:   document.getElementById('pi_username')?.value.trim()  ?? '',
        student_id: document.getElementById('pi_student_id')?.value.trim() ?? '',
        year_level: document.getElementById('pi_year_level')?.value.trim() ?? '',
        strand:     document.getElementById('pi_strand')?.value.trim()     ?? '',
        section:    document.getElementById('pi_section')?.value.trim()    ?? ''
    });

    btn.disabled    = true;
    btn.textContent = 'Saving…';

    fetch('update_profile.php', { method: 'POST', body })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                success.textContent  = '✓ ' + data.message;
                success.style.display = 'block';
                // Update header username display if fullname changed
                const nameEl = document.querySelector('.user-name');
                if (nameEl) nameEl.textContent = document.getElementById('pi_fullname').value.trim();
            } else {
                error.textContent  = '✗ ' + data.message;
                error.style.display = 'block';
            }
        })
        .catch(() => {
            error.textContent  = '✗ Network error. Please try again.';
            error.style.display = 'block';
        })
        .finally(() => {
            btn.disabled    = false;
            btn.textContent = 'Save Changes';
        });
}


document.addEventListener('click', (e)=>{
  const sidebar = document.querySelector('.sidebar');
  if(window.innerWidth <=768 &&
     !sidebar.contains(e.target) &&
     !e.target.classList.contains('hamburger')){
        sidebar.classList.remove('open');
  }
});

document.querySelectorAll('.sidebar nav a').forEach(link => {
    link.addEventListener('click', function() {
        const sidebar = document.querySelector('.sidebar');
        if(window.innerWidth <= 768 && sidebar.classList.contains('open')){
            sidebar.classList.remove('open');
        }
    });
});


</script>

</body>
</html>