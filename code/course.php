<?php
require_once '../db.php';
require_once '../log_activity.php';

if (isset($_POST['create'])) {
    $code = $conn->real_escape_string($_POST['code'] ?? '');
    $name = $conn->real_escape_string($_POST['description'] ?? '');
    $date = date('Y-m-d H:i:s');

    $sql = "INSERT INTO course (name,code) VALUES ('$name','$code')";
    if ($conn->query($sql) === TRUE) {
        log_activity($conn, 'Course added', 'course', "$code - $name", 'info', 'book');
        header("Location: ../course.php");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

if (isset($_POST['update'])) {
    $name = $conn->real_escape_string($_POST['edit-name'] ?? '');
    $code = $conn->real_escape_string($_POST['edit-code'] ?? '');
    $id   = intval($_POST['id'] ?? 0);
    $date = date('Y-m-d H:i:s');

    $sql = "UPDATE course SET name='$name', date='$date', code='$code' WHERE id='$id'";
    if ($conn->query($sql) === TRUE) {
        log_activity($conn, 'Course updated', 'course', "$code - $name", 'info', 'book-open');
        header("Location: ../course.php");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
