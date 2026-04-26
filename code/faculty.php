<?php
// code/faculty.php — Faculty profile CRUD (superadmin or faculty).
// Passwords go to tbl_faculty_auth (Argon2id), NOT to the faculty table.

require_once '../auth.php';
require_role(['superadmin', 'faculty']);

require_once '../db.php';
require_once '../log_activity.php';

// ── CREATE (Superadmin only — adding faculty from the dashboard) ──
if (isset($_POST['create'])) {
    require_role('superadmin');   // only superadmin can add faculty from dashboard

    $first_name   = trim($_POST['first_name']   ?? '');
    $middle_name  = trim($_POST['middle_name']  ?? '');
    $last_name    = trim($_POST['last_name']    ?? '');
    $email        = trim($_POST['email']        ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $address      = trim($_POST['address']      ?? '');
    $user_name    = trim($_POST['user_name']    ?? '');
    $plain_pass   = $_POST['password']          ?? '';

    if (empty($plain_pass)) {
        echo 'Password is required.'; exit();
    }

    // Insert faculty profile (no password column)
    $stmt = $conn->prepare(
        "INSERT INTO faculty (first_name,middle_name,last_name,email,phone_number,address,user_name)
         VALUES (?,?,?,?,?,?,?)"
    );
    $stmt->bind_param('sssssss',
        $first_name,$middle_name,$last_name,$email,$phone_number,$address,$user_name);

    if ($stmt->execute()) {
        $faculty_id = $conn->insert_id;
        $stmt->close();

        // Hash and store in tbl_faculty_auth
        $hash = lti_hash($plain_pass);
        $auth = $conn->prepare(
            "INSERT INTO tbl_faculty_auth (faculty_id, username, password_hash) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash)"
        );
        $auth->bind_param('iss', $faculty_id, $user_name, $hash);
        $auth->execute(); $auth->close();

        log_activity($conn, 'Faculty added', 'faculty', "$first_name $last_name", 'success', 'chalkboard-teacher');
        header('Location: ../faculty.php'); exit();
    } else {
        echo 'Error: '.$stmt->error;
    }
}

// ── UPDATE ────────────────────────────────────────────────────────
if (isset($_POST['update'])) {

    $id           = intval($_POST['id']          ?? 0);
    $first_name   = trim($_POST['first_name']    ?? '');
    $middle_name  = trim($_POST['middle_name']   ?? '');
    $last_name    = trim($_POST['last_name']     ?? '');
    $email        = trim($_POST['email']         ?? '');
    $phone_number = trim($_POST['phone_number']  ?? '');
    $address      = trim($_POST['address']       ?? '');
    $user_name    = trim($_POST['user_name']     ?? '');
    $plain_pass   = trim($_POST['password']      ?? '');

    // Faculty can only edit their own record
    if (is_faculty() && $id !== intval($_SESSION['admin_id'] ?? 0)) {
        $_SESSION['error_msg'] = 'You may only edit your own profile.';
        header('Location: ../faculty.php'); exit();
    }

    $stmt = $conn->prepare(
        "UPDATE faculty SET first_name=?,middle_name=?,last_name=?,
         email=?,phone_number=?,address=?,user_name=? WHERE id=?"
    );
    $stmt->bind_param('sssssssi',
        $first_name,$middle_name,$last_name,$email,$phone_number,$address,$user_name,$id);

    if ($stmt->execute()) {
        $stmt->close();

        // Update password in tbl_faculty_auth only if a new one was provided
        if ($plain_pass !== '') {
            $hash = lti_hash($plain_pass);
            $auth = $conn->prepare(
                "UPDATE tbl_faculty_auth SET password_hash=?, username=? WHERE faculty_id=?"
            );
            $auth->bind_param('ssi', $hash, $user_name, $id);
            $auth->execute(); $auth->close();
        } else {
            // Still sync username if it changed
            $auth = $conn->prepare(
                "UPDATE tbl_faculty_auth SET username=? WHERE faculty_id=?"
            );
            $auth->bind_param('si', $user_name, $id);
            $auth->execute(); $auth->close();
        }

        log_activity($conn, 'Faculty updated', 'faculty', "$first_name $last_name", 'success', 'user-edit');
        header('Location: ../faculty.php'); exit();
    } else {
        echo 'Error: '.$stmt->error;
    }
}
