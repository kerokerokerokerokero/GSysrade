<?php
// layouts/topbar.php — Shows current user, role badge, and dropdown menu.
if (session_status() === PHP_SESSION_NONE) session_start();

$adminName = $_SESSION['admin_username'] ?? 'User';
$role      = $_SESSION['role']           ?? '';
$safeName  = preg_replace('/[^A-Za-z0-9_-]/', '_', $adminName);

// Avatar lookup
$avatarPath = 'img/undraw_profile.svg';
$avatarDir  = __DIR__ . '/../uploads/avatars';
foreach (['png','jpg','jpeg','gif'] as $ext) {
    if ($adminName && file_exists($avatarDir.'/'.$safeName.'.'.$ext)) {
        $avatarPath = 'uploads/avatars/'.$safeName.'.'.$ext;
        break;
    }
}

// Role label + badge colour
$roleLabels = [
    'superadmin' => ['label'=>'Superadmin', 'badge'=>'danger',  'icon'=>'shield-alt'],
    'faculty'    => ['label'=>'Faculty',     'badge'=>'primary', 'icon'=>'chalkboard-teacher'],
    'student'    => ['label'=>'Student',     'badge'=>'success', 'icon'=>'user-graduate'],
];
$rl = $roleLabels[$role] ?? ['label'=>ucfirst($role), 'badge'=>'secondary', 'icon'=>'user'];
?>

<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <ul class="navbar-nav ml-auto">
        <div class="topbar-divider d-none d-sm-block"></div>

        <!-- User Dropdown -->
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <div class="mr-2 d-none d-lg-flex flex-column align-items-end">
                    <span class="text-gray-600 small font-weight-bold">
                        <?= htmlspecialchars($adminName) ?>
                    </span>
                    <span class="badge badge-<?= $rl['badge'] ?> mt-1 px-2" style="font-size:.7rem;">
                        <i class="fas fa-<?= $rl['icon'] ?> mr-1"></i><?= $rl['label'] ?>
                    </span>
                </div>
                <img class="img-profile rounded-circle" src="<?= htmlspecialchars($avatarPath) ?>">
            </a>

            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                 aria-labelledby="userDropdown">

                <a class="dropdown-item" href="profile.php">
                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>Profile
                </a>

                <?php if ($role !== 'student'): ?>
                <a class="dropdown-item" href="change_password.php">
                    <i class="fas fa-key fa-sm fa-fw mr-2 text-warning"></i>Change Password
                </a>
                <?php endif; ?>

                <?php if ($role === 'superadmin'): ?>
                <a class="dropdown-item" href="activity_log.php">
                    <i class="fas fa-list fa-sm fa-fw mr-2 text-info"></i>Activity Log
                </a>
                <?php endif; ?>

                <div class="dropdown-divider"></div>
                <a class="dropdown-item text-danger" href="#" data-toggle="modal" data-target="#confirmLogoutModal">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2"></i>Logout
                </a>
            </div>
        </li>
    </ul>
</nav>

<!-- Logout Confirm Modal -->
<div class="modal fade" id="confirmLogoutModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-sign-out-alt mr-2"></i>Confirm Logout
                </h5>
                <button class="close text-white" type="button" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Are you sure you want to log out,
                <strong><?= htmlspecialchars($adminName) ?></strong>?
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                <a class="btn btn-danger" href="logout.php">
                    <i class="fas fa-sign-out-alt mr-1"></i>Logout
                </a>
            </div>
        </div>
    </div>
</div>
