<?php
// ============================================================
// audit.php — Audit logging helper
// Include this on any page that needs to log actions
// Usage: audit_log($conn, 'ACTION', 'target', 'detail');
// ============================================================

function audit_log($conn, string $action, string $target = '', string $detail = ''): void {
    // Get user info from session if available
    $user_id  = $_SESSION['id']       ?? null;
    $username = $_SESSION['username'] ?? null;
    $role     = $_SESSION['role']     ?? null;

    // Get IP address (handles proxies)
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['HTTP_CLIENT_IP']
        ?? $_SERVER['REMOTE_ADDR']
        ?? null;
    // Only take the first IP if multiple are listed
    if($ip && strpos($ip, ',') !== false){
        $ip = trim(explode(',', $ip)[0]);
    }

    $stmt = $conn->prepare(
        "INSERT INTO audit_log (user_id, username, role, action, target, detail, ip_address)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "issssss",
        $user_id, $username, $role,
        $action, $target, $detail, $ip
    );
    $stmt->execute();
}
