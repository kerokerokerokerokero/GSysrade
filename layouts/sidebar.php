<?php
// layouts/sidebar.php — Shows navigation based on the current user's role.
if (session_status() === PHP_SESSION_NONE) session_start();

$currentPage = basename($_SERVER['PHP_SELF']);
$role        = $_SESSION['role'] ?? '';

function navActive($page, $current) {
    return ($current === $page) ? 'active' : '';
}
?>
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center"
       href="<?= ($role === 'student') ? 'student_record.php?id='.intval($_SESSION['student_ref_id'] ?? 0) : 'index.php' ?>">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <div class="sidebar-brand-text mx-3">LTI Grading System</div>
    </a>

    <hr class="sidebar-divider my-0">

    <?php if ($role !== 'student'): ?>
    <!-- Dashboard (Superadmin + Faculty only) -->
    <li class="nav-item <?= navActive('index.php', $currentPage) ?>">
        <a class="nav-link" href="index.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>
    <hr class="sidebar-divider">
    <div class="sidebar-heading">Management</div>

    <li class="nav-item <?= navActive('curriculum.php', $currentPage) ?>">
        <a class="nav-link" href="curriculum.php">
            <i class="fas fa-fw fa-sitemap"></i><span>Curriculum</span>
        </a>
    </li>
    <li class="nav-item <?= navActive('course.php', $currentPage) ?>">
        <a class="nav-link" href="course.php">
            <i class="fas fa-fw fa-book"></i><span>Course</span>
        </a>
    </li>
    <li class="nav-item <?= navActive('subject.php', $currentPage) ?>">
        <a class="nav-link" href="subject.php">
            <i class="fas fa-fw fa-clipboard-list"></i><span>Subject</span>
        </a>
    </li>
    <li class="nav-item <?= navActive('faculty.php', $currentPage) ?>">
        <a class="nav-link" href="faculty.php">
            <i class="fas fa-fw fa-chalkboard-teacher"></i><span>Faculty</span>
        </a>
    </li>
    <li class="nav-item <?= navActive('student.php', $currentPage) ?>">
        <a class="nav-link" href="student.php">
            <i class="fas fa-fw fa-user-graduate"></i><span>Student</span>
        </a>
    </li>

    <?php if ($role === 'superadmin'): ?>
    <hr class="sidebar-divider">
    <div class="sidebar-heading">Superadmin</div>
    <li class="nav-item <?= navActive('activity_log.php', $currentPage) ?>">
        <a class="nav-link" href="activity_log.php">
            <i class="fas fa-fw fa-list-alt"></i><span>Activity Log</span>
        </a>
    </li>
    <?php endif; ?>

    <?php else: ?>
    <!-- Student: only their own record -->
    <li class="nav-item active">
        <a class="nav-link" href="student_record.php?id=<?= intval($_SESSION['student_ref_id'] ?? 0) ?>">
            <i class="fas fa-fw fa-file-alt"></i>
            <span>My Record</span>
        </a>
    </li>
    <?php endif; ?>

    <hr class="sidebar-divider d-none d-md-block">
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
