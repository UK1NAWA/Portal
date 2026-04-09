<!DOCTYPE html>
<?php
include 'db.php';

// Must be logged in and flagged for forced change
if (!isset($_SESSION['loggedIn'])) {
    header("Location: login.html");
    exit();
}

// Double-check the flag in DB (don't trust session alone)
$chk = $conn->prepare("SELECT force_password_change FROM users WHERE id = ?");
$chk->bind_param("i", $_SESSION['id']);
$chk->execute();
$row = $chk->get_result()->fetch_assoc();

if (!$row || !$row['force_password_change']) {
    // Flag not set — send to correct portal
    $role = $_SESSION['role'];
    if ($role === 'admin')        header("Location: admin.php");
    elseif ($role === 'teacher')  header("Location: teacher_portal.php");
    else                          header("Location: portal.php");
    exit();
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_pw     = $_POST['new_password']     ?? '';
    $confirm_pw = $_POST['confirm_password'] ?? '';

    if (strlen($new_pw) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($new_pw !== $confirm_pw) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE users SET password = ?, force_password_change = 0 WHERE id = ?");
        $upd->bind_param("si", $hashed, $_SESSION['id']);

        if ($upd->execute()) {
            session_regenerate_id(true);
            $role = $_SESSION['role'];
            if ($role === 'admin')        header("Location: admin.php");
            elseif ($role === 'teacher')  header("Location: teacher_portal.php");
            else                          header("Location: portal.php");
            exit();
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Your Password</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .forced-wrap {
            max-width: 400px;
            margin: 60px auto;
            padding: 32px 28px;
            background: var(--surface, #13151c);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 14px;
        }
        body.light-mode .forced-wrap {
            background: #ffffff;
            border-color: #e5e7eb;
        }
        .forced-wrap h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .forced-wrap .subtitle {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 24px;
            line-height: 1.5;
        }
        .forced-wrap label {
            display: block;
            font-size: 12px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 5px;
            margin-top: 14px;
        }
        .forced-wrap input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: inherit;
            font-size: 14px;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.15s;
        }
        body.light-mode .forced-wrap input[type="password"] {
            background: #f9fafb;
            border-color: #d1d5db;
        }
        .forced-wrap input[type="password"]:focus { border-color: #f0b429; }
        .strength-track {
            height: 4px;
            background: rgba(255,255,255,0.08);
            border-radius: 99px;
            overflow: hidden;
            margin-top: 8px;
        }
        body.light-mode .strength-track { background: #e5e7eb; }
        #strengthBar {
            height: 100%;
            width: 0%;
            border-radius: 99px;
            transition: width 0.3s, background 0.3s;
        }
        #strengthLabel { font-size: 11px; color: #6b7280; margin-top: 4px; }
        .err-box {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            color: #f87171;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            margin-bottom: 14px;
        }
        .forced-wrap .save { margin-top: 20px; }
    </style>
</head>
<body>
<div class="forced-wrap">
    <h2>&#128274; Set a New Password</h2>
    <p class="subtitle">Your password was reset by an administrator. Please choose a new password before continuing.</p>

    <?php if ($error): ?>
    <div class="err-box"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" onsubmit="return clientValidate()">
        <label for="new_password">New Password <span style="font-size:11px; color:#6b7280;">(min 8 characters)</span></label>
        <input type="password" name="new_password" id="new_password" required autocomplete="new-password">
        <div class="strength-track"><div id="strengthBar"></div></div>
        <div id="strengthLabel"></div>

        <label for="confirm_password">Confirm New Password</label>
        <input type="password" name="confirm_password" id="confirm_password" required autocomplete="new-password">

        <button type="submit" class="save">Set Password & Continue</button>
    </form>
</div>

<script>
document.getElementById('new_password').addEventListener('input', function() {
    const val = this.value;
    const bar = document.getElementById('strengthBar');
    const lbl = document.getElementById('strengthLabel');
    if (!val) { bar.style.width = '0%'; lbl.textContent = ''; return; }

    let score = 0;
    if (val.length >= 8)           score++;
    if (val.length >= 12)          score++;
    if (/[A-Z]/.test(val))         score++;
    if (/[0-9]/.test(val))         score++;
    if (/[^A-Za-z0-9]/.test(val))  score++;

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

function clientValidate() {
    const pw  = document.getElementById('new_password').value;
    const cfm = document.getElementById('confirm_password').value;
    if (pw.length < 8) {
        alert('Password must be at least 8 characters.'); return false;
    }
    if (pw !== cfm) {
        alert('Passwords do not match.'); return false;
    }
    return true;
}
</script>
</body>
</html>
