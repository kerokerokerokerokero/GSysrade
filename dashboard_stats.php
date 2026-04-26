<?php
/**
 * dashboard_stats.php
 * Called every 30 s by the dashboard JS to refresh stat card numbers.
 * Place this file in the same folder as index.php (your ADMIN root).
 */
header('Content-Type: application/json');
require_once 'db.php';

echo json_encode([
    'students'  => (int)($conn->query("SELECT COUNT(*) AS c FROM student")       ->fetch_assoc()['c'] ?? 0),
    'faculty'   => (int)($conn->query("SELECT COUNT(*) AS c FROM faculty")        ->fetch_assoc()['c'] ?? 0),
    'courses'   => (int)($conn->query("SELECT COUNT(*) AS c FROM course")         ->fetch_assoc()['c'] ?? 0),
    'curricula' => (int)($conn->query("SELECT COUNT(*) AS c FROM crurriculum")    ->fetch_assoc()['c'] ?? 0),
    'subjects'  => (int)($conn->query("SELECT COUNT(*) AS c FROM subject")        ->fetch_assoc()['c'] ?? 0),
    'records'   => (int)($conn->query("SELECT COUNT(*) AS c FROM student_record") ->fetch_assoc()['c'] ?? 0),
    'timestamp' => date('M d, Y h:i A'),
]);
