<?php
require_once '../db.php';
require_once '../log_activity.php';

if (isset($_POST['create'])) {
    $subject = $conn->real_escape_string($_POST['subject'] ?? '');
    $des     = $conn->real_escape_string($_POST['description'] ?? '');
    $units   = $conn->real_escape_string($_POST['units'] ?? '');

    $sql = "INSERT INTO subject (subject_code,des,unit) VALUES ('$subject','$des','$units')";
    if ($conn->query($sql) === TRUE) {
        log_activity($conn, 'Subject added', 'subject', "$subject - $des", 'warning', 'clipboard-list');
        header("Location: ../subject.php");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

if (isset($_POST['update'])) {
    $subject = $conn->real_escape_string($_POST['edit-subject'] ?? '');
    $des     = $conn->real_escape_string($_POST['edit-des'] ?? '');
    $unit    = $conn->real_escape_string($_POST['edit-unit'] ?? '');
    $id      = intval($_POST['id'] ?? 0);

    $sql = "UPDATE subject SET subject_code='$subject', des='$des', unit='$unit' WHERE id='$id'";
    if ($conn->query($sql) === TRUE) {
        log_activity($conn, 'Subject updated', 'subject', "$subject - $des", 'warning', 'clipboard-check');
        header("Location: ../subject.php");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

if (isset($_POST['faculty_subject'])) {
    $faculty_id = intval($_POST['id'] ?? 0);
    $subject_id = intval($_POST['subject'] ?? 0);
    $day  = $conn->real_escape_string($_POST['day']  ?? '');
    $time = $conn->real_escape_string($_POST['time'] ?? '');

    $allowedDays = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
    if (!in_array($day, $allowedDays, true)) {
        echo "<script>alert('Invalid day selected.'); window.history.back();</script>";
        exit();
    }

    if ($faculty_id && $subject_id > 0) {
        $conflict = $conn->query("SELECT * FROM faculty_subject WHERE day='$day' AND time='$time' AND id_subject=$subject_id AND faculty_id != $faculty_id");
        if ($conflict && $conflict->num_rows > 0) {
            echo "<script>alert('Schedule conflict: subject already assigned to another instructor at this day and time.'); window.history.back();</script>";
            exit();
        }
        $instructorConflict = $conn->query("SELECT * FROM faculty_subject WHERE faculty_id=$faculty_id AND day='$day' AND time='$time'");
        if ($instructorConflict && $instructorConflict->num_rows > 0) {
            echo "<script>alert('Schedule conflict: this instructor already has a subject assigned at this day and time.'); window.history.back();</script>";
            exit();
        }

        // Get subject name for the log
        $subjectName = '';
        $sRow = $conn->query("SELECT subject_code, des FROM subject WHERE id=$subject_id LIMIT 1");
        if ($sRow && $r = $sRow->fetch_assoc()) {
            $subjectName = $r['subject_code'] . ' - ' . $r['des'];
        }

        $sql = "INSERT INTO faculty_subject (faculty_id, id_subject, day, time) VALUES ($faculty_id, $subject_id, '$day', '$time')";
        $conn->query($sql);
        log_activity($conn, 'Subject assigned to instructor', 'subject', "$subjectName ($day $time)", 'success', 'chalkboard-teacher');

        header("Location: ../instructor.php?id=$faculty_id");
        exit();
    } else {
        echo "<script>alert('No subject selected or invalid instructor.'); window.history.back();</script>";
    }
}
