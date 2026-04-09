<?php
include 'db.php';
include_once __DIR__ . '/audit.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Please fill in all fields.";
    } else {
        // Brute-force protection: max 5 attempts per username per 15 min
        $window = 15 * 60;
        $max    = 5;
        $key    = 'login_attempts_' . preg_replace('/[^a-zA-Z0-9_]/', '', $username);

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'first' => time()];
        }

        $att = &$_SESSION[$key];

        // Reset window if expired
        if (time() - $att['first'] > $window) {
            $att = ['count' => 0, 'first' => time()];
        }

        if ($att['count'] >= $max) {
            $wait = ceil(($window - (time() - $att['first'])) / 60);
            $error = "Too many failed attempts. Try again in {$wait} minute(s).";
        } else {
            // Check if force_password_change column exists (added by migration)
            $col_check = $conn->query("SHOW COLUMNS FROM users LIKE 'force_password_change'");
            $has_force_col = $col_check && $col_check->num_rows > 0;

            $sql = $has_force_col
                ? "SELECT id, fullname, password, role, section, force_password_change FROM users WHERE username=?"
                : "SELECT id, fullname, password, role, section FROM users WHERE username=?";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                $error = "Database error. Please contact your administrator.";
                error_log("Login prepare() failed: " . $conn->error);
            } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($has_force_col) {
                $stmt->bind_result($id, $fullname, $hashed_password, $role, $section, $force_pw_change);
            } else {
                $force_pw_change = 0;
                $stmt->bind_result($id, $fullname, $hashed_password, $role, $section);
            }

            $found = $stmt->fetch();

            // Always run password_verify to prevent timing attacks
            $dummy = '$2y$10$invalidhashfortimingnnnnnnnnnnnnnnnnnnnnnnnnnnn';
            $valid = $found && password_verify($password, $hashed_password);

            if (!$found) {
                password_verify($password, $dummy); // constant-time dummy
            }

            } // end if (!$stmt)

            if (isset($valid) && $valid) {
                // Session fixation prevention
                session_regenerate_id(true);
                unset($_SESSION[$key]);

                $_SESSION['loggedIn'] = true;
                $_SESSION['username'] = $username;
                $_SESSION['fullname'] = $fullname;
                $_SESSION['id']       = $id;
                $_SESSION['role']     = $role;
                $_SESSION['section']  = $section;

                // Force password change if admin triggered a reset
                if ($force_pw_change) {
                    header("Location: forced_password_change.php");
                    exit();
                }

                // Log successful login
                audit_log($conn, 'LOGIN', $username, 'Role: ' . $role);

                if ($role === 'admin') {
                    header("Location: admin.php");
                } elseif ($role === 'teacher') {
                    header("Location: teacher_portal.php");
                } else {
                    header("Location: portal.php");
                }
                exit();
            } else {
                $att['count']++;
                // Generic message — no user enumeration
                audit_log($conn, 'LOGIN_FAILED', $username, 'Invalid credentials');
                $error = "Invalid username or password.";
            }
        }
    }
}
?>
<?php if (isset($error)): ?>
<script>
  // Pass error back to login page
  sessionStorage.setItem('loginError', <?php echo json_encode($error); ?>);
  window.location.href = 'login.html';
</script>
<?php endif; ?>
