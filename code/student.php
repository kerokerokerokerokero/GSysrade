<?php
// code/student.php — Handles student create & update.
// Auto-syncs tbl_student_auth (username=fname, password=hash(lname)).

require_once '../auth.php';   // role check
require_role(['superadmin', 'faculty']);

require_once '../db.php';
require_once '../log_activity.php';

// ── CREATE ────────────────────────────────────────────────────────
if (isset($_POST['create'])) {

    $fname      = trim($_POST['fname']       ?? '');
    $mname      = trim($_POST['mname']       ?? '');
    $lname      = trim($_POST['lname']       ?? '');
    $dob        = $_POST['dob']              ?? '';
    $courseId   = intval($_POST['course']    ?? 0);
    $year       = $conn->real_escape_string($_POST['year']       ?? '');
    $curriculum = $conn->real_escape_string($_POST['curriculum'] ?? '');
    $email      = $conn->real_escape_string($_POST['email']      ?? '');
    $phone      = $conn->real_escape_string($_POST['phone']      ?? '');
    $address    = $conn->real_escape_string($_POST['address']    ?? '');

    // Auto-generate username from course + lname
    $courseCode = '';
    if ($courseId > 0) {
        $cs = $conn->prepare("SELECT code FROM course WHERE id=? LIMIT 1");
        $cs->bind_param('i', $courseId); $cs->execute();
        $cs->bind_result($courseCode);   $cs->fetch(); $cs->close();
    }
    $courseCode    = $courseCode ?: 'COURSE';
    $cleanLname    = preg_replace('/[^A-Za-z0-9]/', '', ucwords(strtolower($lname)));
    $genUsername   = strtoupper($courseCode).'_'.$cleanLname;
    $username      = trim($_POST['username'] ?? '') !== ''
                     ? $conn->real_escape_string(trim($_POST['username']))
                     : $genUsername;

    // Ensure unique username
    $baseUser = $username; $counter = 1;
    $chk = $conn->prepare("SELECT id FROM student WHERE username=? LIMIT 1");
    while (true) {
        $chk->bind_param('s', $username); $chk->execute(); $chk->store_result();
        if ($chk->num_rows === 0) break;
        $username = $baseUser.$counter++;
    }
    $chk->close();

    // Insert student profile (no password column needed here)
    $sql  = "INSERT INTO student (fname,mname,lname,dob,course,year,curriculum,email,phone,address,username)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssissssss',
        $fname,$mname,$lname,$dob,$courseId,$year,$curriculum,$email,$phone,$address,$username);

    if ($stmt->execute()) {
        $student_id = $conn->insert_id;
        $stmt->close();

        // ── Auto-create tbl_student_auth ──────────────────────────
        // username = fname (lowercase), password = Argon2id(lname)
        $auth_user = strtolower(trim($fname));
        $auth_hash = lti_hash(trim($lname));

        $auth = $conn->prepare(
            "INSERT INTO tbl_student_auth (student_id, username, password_hash) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE username=VALUES(username), password_hash=VALUES(password_hash)"
        );
        $auth->bind_param('iss', $student_id, $auth_user, $auth_hash);
        $auth->execute();
        $auth->close();

        log_activity($conn, 'Student added', 'student', "$fname $lname", 'primary', 'user-graduate');
        header('Location: ../student.php'); exit();
    } else {
        echo 'Error: '.$stmt->error;
    }
}

// ── UPDATE ────────────────────────────────────────────────────────
if (isset($_POST['update'])) {

    $id         = intval($_POST['id'] ?? 0);
    $fname      = trim($_POST['fname']       ?? '');
    $mname      = trim($_POST['mname']       ?? '');
    $lname      = trim($_POST['lname']       ?? '');
    $dob        = $_POST['dob']              ?? '';
    $courseId   = intval($_POST['course']    ?? 0);
    $year       = $conn->real_escape_string($_POST['year']       ?? '');
    $curriculum = $conn->real_escape_string($_POST['curriculum'] ?? '');
    $email      = $conn->real_escape_string($_POST['email']      ?? '');
    $phone      = $conn->real_escape_string($_POST['phone']      ?? '');
    $address    = $conn->real_escape_string($_POST['address']    ?? '');
    $username   = trim($_POST['username']    ?? '');

    if ($username === '') {
        $cs2 = $conn->prepare("SELECT username FROM student WHERE id=? LIMIT 1");
        $cs2->bind_param('i', $id); $cs2->execute();
        $cs2->bind_result($username); $cs2->fetch(); $cs2->close();
    }

    $sql  = "UPDATE student SET fname=?,mname=?,lname=?,dob=?,course=?,year=?,
             curriculum=?,email=?,phone=?,address=?,username=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssissssssi',
        $fname,$mname,$lname,$dob,$courseId,$year,$curriculum,$email,$phone,$address,$username,$id);

    if ($stmt->execute()) {
        $stmt->close();

        // ── Sync tbl_student_auth when name changes ───────────────
        $auth_user = strtolower(trim($fname));
        $auth_hash = lti_hash(trim($lname));

        $auth = $conn->prepare(
            "INSERT INTO tbl_student_auth (student_id, username, password_hash) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE username=VALUES(username), password_hash=VALUES(password_hash)"
        );
        $auth->bind_param('iss', $id, $auth_user, $auth_hash);
        $auth->execute();
        $auth->close();

        log_activity($conn, 'Student updated', 'student', "$fname $lname", 'primary', 'user-edit');
        header('Location: ../student.php'); exit();
    } else {
        echo 'Error: '.$stmt->error;
    }
}
