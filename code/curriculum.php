<?php
require_once '../db.php';
require_once '../log_activity.php';

if (isset($_POST['create'])) {
    $curriculum = $conn->real_escape_string(trim($_POST['curriculum_name'] ?? ''));
    $date = trim($_POST['year_created'] ?? '');
    if (!preg_match('/^SY \d{4}-\d{4}$/', $date)) { $date = ''; }
    $date = $conn->real_escape_string($date);

    $sql = "INSERT INTO crurriculum (name,date) VALUES ('$curriculum','$date')";
    if ($conn->query($sql) === TRUE) {
        log_activity($conn, 'Curriculum added', 'curriculum', "$curriculum ($date)", 'info', 'graduation-cap');
        header('Location: ../curriculum.php');
        exit();
    } else {
        echo 'Error: ' . $sql . '<br>' . $conn->error;
    }
}

if (isset($_POST['update'])) {
    $curriculum = $conn->real_escape_string(trim($_POST['edit-name'] ?? ''));
    $date = trim($_POST['edit-date'] ?? '');
    if (!preg_match('/^SY \d{4}-\d{4}$/', $date)) { $date = ''; }
    $date = $conn->real_escape_string($date);
    $id = intval($_POST['id'] ?? 0);

    $sql = "UPDATE crurriculum SET name='$curriculum', date='$date' WHERE id='$id'";
    if ($conn->query($sql) === TRUE) {
        log_activity($conn, 'Curriculum updated', 'curriculum', "$curriculum ($date)", 'info', 'edit');
        header('Location: ../curriculum.php');
        exit();
    } else {
        echo 'Error: ' . $sql . '<br>' . $conn->error;
    }
}

if (isset($_POST['subject_curriculum'])) {
    $curriculum = intval($_POST['curriculum'] ?? 0);
    $course     = intval($_POST['course'] ?? 0);
    $year       = intval($_POST['year'] ?? 0);
    $semester   = $conn->real_escape_string($_POST['sem'] ?? '');
    $subjects   = $_POST['subject'] ?? [];
    $date       = date('Y-m-d');

    if ($curriculum && count($subjects) > 0) {
        foreach ($subjects as $subject_id) {
            $subject_id = intval($subject_id);
            $sql = "INSERT INTO curriculum_subject (curriculum, subject, sem, course, year, date) VALUES ($curriculum, $subject_id, '$semester','$course','$year', '$date')";
            $conn->query($sql);
        }
        $count = count($subjects);
        log_activity($conn, 'Subjects added to curriculum', 'curriculum', "$count subject(s) linked — Year $year Sem $semester", 'info', 'link');
        header("Location: ../course_subject.php?id=$course&year=$year");
        exit();
    } else {
        echo 'No subjects selected or invalid curriculum ID.';
    }
}
