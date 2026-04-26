<?php
require_once 'auth.php';   // sets session + helpers
include    'db.php';
require_once 'log_activity.php';

// ── SUPERADMIN ONLY ─────────────────────────────────────────────
if (!can_delete()) {
    $_SESSION['error_msg'] = 'Only the Superadmin can delete records.';
    header('Location: index.php');
    exit();
}

$allowed = [
    'student'         => ['table'=>'student',        'redirect'=>'student.php'],
    'curriculum'      => ['table'=>'crurriculum',     'redirect'=>'curriculum.php'],
    'course'          => ['table'=>'course',          'redirect'=>'course.php'],
    'subject'         => ['table'=>'subject',         'redirect'=>'subject.php'],
    'faculty'         => ['table'=>'faculty',         'redirect'=>'faculty.php'],
    'faculty_subject' => ['table'=>'faculty_subject', 'redirect'=>'instructor.php'],
];

$type = $_GET['type'] ?? '';
$id   = $_GET['id']   ?? '';

if (!array_key_exists($type, $allowed)) {
    $_SESSION['error_msg'] = 'Invalid delete type.';
    header('Location: index.php'); exit();
}
if (!is_numeric($id) || intval($id) <= 0) {
    $_SESSION['error_msg'] = 'Invalid ID.';
    header('Location: '.$allowed[$type]['redirect']); exit();
}

$id       = intval($id);
$table    = $allowed[$type]['table'];
$redirect = $allowed[$type]['redirect'];

// Grab name for log
$detail  = "$type #$id";
$nameMap = [
    'student'         => "SELECT CONCAT(fname,' ',lname) FROM student WHERE id=?",
    'faculty'         => "SELECT CONCAT(first_name,' ',last_name) FROM faculty WHERE id=?",
    'course'          => "SELECT CONCAT(code,' - ',name) FROM course WHERE id=?",
    'subject'         => "SELECT CONCAT(subject_code,' - ',des) FROM subject WHERE id=?",
    'curriculum'      => "SELECT name FROM crurriculum WHERE id=?",
    'faculty_subject' => "SELECT CONCAT(day,' ',time) FROM faculty_subject WHERE id=?",
];
if (isset($nameMap[$type])) {
    $ns = $conn->prepare($nameMap[$type]);
    $ns->bind_param('i', $id); $ns->execute();
    $ns->bind_result($detail); $ns->fetch(); $ns->close();
}

if ($type === 'faculty_subject') {
    $iid = intval($_GET['instructor'] ?? 0);
    if ($iid > 0) $redirect .= '?id='.$iid;
}

// ── Also clean up tbl_student_auth or tbl_faculty_auth if needed ─
if ($type === 'student') {
    $del_auth = $conn->prepare("DELETE FROM tbl_student_auth WHERE student_id=?");
    $del_auth->bind_param('i', $id); $del_auth->execute(); $del_auth->close();
}
if ($type === 'faculty') {
    $del_auth = $conn->prepare("DELETE FROM tbl_faculty_auth WHERE faculty_id=?");
    $del_auth->bind_param('i', $id); $del_auth->execute(); $del_auth->close();
}

// ── Main delete ──────────────────────────────────────────────────
$stmt = $conn->prepare("DELETE FROM `$table` WHERE id=?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['success_msg'] = ucfirst($type).' deleted successfully.';
        $meta = [
            'student'         => ['danger','user-times'],
            'faculty'         => ['danger','user-times'],
            'course'          => ['danger','book'],
            'subject'         => ['danger','clipboard'],
            'curriculum'      => ['danger','graduation-cap'],
            'faculty_subject' => ['danger','calendar-times'],
        ];
        [$color, $icon] = $meta[$type] ?? ['danger','trash'];
        log_activity($conn, ucfirst($type).' deleted', $type, $detail ?: "$type #$id", $color, $icon);
    } else {
        $_SESSION['error_msg'] = 'Record not found or already deleted.';
    }
} else {
    $_SESSION['error_msg'] = 'Delete failed: '.$conn->error;
}
$stmt->close();
header('Location: '.$redirect); exit();
