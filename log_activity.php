<?php
/**
 * log_activity.php
 * Place this file in ADMIN/ (same folder as db.php).
 *
 * Usage — call anywhere after a successful DB action:
 *   require_once 'log_activity.php';   // (or '../log_activity.php' from code/)
 *   log_activity($conn, 'Student added', 'student', 'Juan dela Cruz', 'primary', 'user-graduate');
 *
 * @param mysqli $conn     Active DB connection
 * @param string $action   Short verb phrase  e.g. "Student added"
 * @param string $category student | faculty | course | subject | curriculum | auth | other
 * @param string $detail   Name / description of what changed
 * @param string $color    Bootstrap color: primary | success | info | warning | danger | secondary
 * @param string $icon     FontAwesome icon name (without "fa-")
 */
function log_activity(
    mysqli $conn,
    string $action,
    string $category,
    string $detail,
    string $color  = 'secondary',
    string $icon   = 'circle'
): void {
    // Works whether session key is admin_username (admin) or username (faculty/student)
    $username = $_SESSION['admin_username'] ?? ($_SESSION['username'] ?? 'system');
    $ip       = $_SERVER['REMOTE_ADDR'] ?? null;

    $stmt = $conn->prepare(
        "INSERT INTO activity_log (username, action, category, detail, color, icon, ip_address)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    if ($stmt) {
        $stmt->bind_param('sssssss', $username, $action, $category, $detail, $color, $icon, $ip);
        $stmt->execute();
        $stmt->close();
    }
}
