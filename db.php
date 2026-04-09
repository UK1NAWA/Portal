<?php

$servername = "sql100.infinityfree.com";
$dbname     = "if0_41607430_student_portal";
$dbusername = "if0_41607430";
$dbpassword = "H011YPASSW0RD1";


$conn = new mysqli('p:' . $servername, $dbusername, $dbpassword, $dbname);

if ($conn->connect_error) {
    error_log("DB connection failed: " . $conn->connect_error);
    http_response_code(503);
    die("Service temporarily unavailable. Please try again shortly.");
}

$conn->set_charset("utf8mb4");

class DBSessionHandler implements SessionHandlerInterface {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function open($path, $name): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read($id): string {
        $stmt = $this->conn->prepare(
            "SELECT session_data FROM php_sessions WHERE session_id = ? AND last_activity > ?"
        );
        $expiry = time() - (3 * 24 * 60 * 60); // 3-day window matches teacher/admin timeout
        $stmt->bind_param("si", $id, $expiry);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ? $result['session_data'] : '';
    }

    public function write($id, $data): bool {
        $now  = time();
        $stmt = $this->conn->prepare(
            "INSERT INTO php_sessions (session_id, session_data, last_activity)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE session_data = VALUES(session_data),
                                     last_activity = VALUES(last_activity)"
        );
        $stmt->bind_param("ssi", $id, $data, $now);
        return $stmt->execute();
    }

    public function destroy($id): bool {
        $stmt = $this->conn->prepare(
            "DELETE FROM php_sessions WHERE session_id = ?"
        );
        $stmt->bind_param("s", $id);
        return $stmt->execute();
    }

    public function gc($max_lifetime): int|false {
        $expiry = time() - $max_lifetime;
        $stmt   = $this->conn->prepare(
            "DELETE FROM php_sessions WHERE last_activity < ?"
        );
        $stmt->bind_param("i", $expiry);
        $stmt->execute();
        return $stmt->affected_rows;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', 259200); // 3 days — matches teacher/admin timeout
}

// Register the DB session handler before any session_start()
$handler = new DBSessionHandler($conn);
session_set_save_handler($handler, true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): void {
    $submitted = $_POST['csrf_token'] ?? '';
    $expected  = $_SESSION['csrf_token'] ?? '';

    if ($submitted === '' || !hash_equals($expected, $submitted)) {
        http_response_code(403);
        error_log("CSRF check failed — IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        die("Invalid request. Please go back and try again.");
    }
}