<?php
require_once 'auth.php';
require_once 'db.php';

$adminName = $_SESSION['admin_username'] ?? 'User';
$role      = $_SESSION['role'] ?? 'faculty';
$adminId   = $_SESSION['admin_id'] ?? 0;

$success = '';
$error   = '';

// ── Fetch current user data ────────────────────────────────────────────────
$userData = [];
if (strtolower($adminName) === 'admin' || $role === 'admin') {
    // Try faculty table first, then student
    $r = $conn->query("SELECT first_name AS fname, last_name AS lname, email FROM faculty WHERE id = $adminId LIMIT 1");
    if ($r && $r->num_rows) { $userData = $r->fetch_assoc(); }
    else {
        $r = $conn->query("SELECT fname, lname, email FROM student WHERE id = $adminId LIMIT 1");
        if ($r && $r->num_rows) { $userData = $r->fetch_assoc(); }
    }
} elseif ($role === 'faculty') {
    $r = $conn->query("SELECT first_name AS fname, last_name AS lname, email FROM faculty WHERE id = $adminId LIMIT 1");
    if ($r && $r->num_rows) { $userData = $r->fetch_assoc(); }
} else {
    $r = $conn->query("SELECT fname, lname, email FROM student WHERE id = $adminId LIMIT 1");
    if ($r && $r->num_rows) { $userData = $r->fetch_assoc(); }
}

// ── Handle avatar upload ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_avatar'])) {
    if (!empty($_FILES['avatar']['name'])) {
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
        $safeName  = preg_replace('/[^A-Za-z0-9_-]/', '_', $adminName);
        $uploadDir = __DIR__ . '/uploads/avatars';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

        if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $error = "Upload failed. Please try again.";
        } elseif (!array_key_exists($_FILES['avatar']['type'], $allowedTypes)) {
            $error = "Only JPG, PNG and GIF images are allowed.";
        } elseif ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
            $error = "Image must be smaller than 2MB.";
        } else {
            // Remove old avatars for this user
            foreach (['jpg','jpeg','png','gif'] as $e) {
                $old = $uploadDir . '/' . $safeName . '.' . $e;
                if (file_exists($old)) { unlink($old); }
            }
            $ext  = $allowedTypes[$_FILES['avatar']['type']];
            $dest = $uploadDir . '/' . $safeName . '.' . $ext;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
                $success = "Profile photo updated successfully!";
            } else {
                $error = "Could not save the uploaded image.";
            }
        }
    } else {
        $error = "Please select an image to upload.";
    }
}

// ── Resolve avatar path ────────────────────────────────────────────────────
$safeName   = preg_replace('/[^A-Za-z0-9_-]/', '_', $adminName);
$avatarPath = 'img/undraw_profile.svg';
foreach (['png','jpg','jpeg','gif'] as $ext) {
    $candidate = __DIR__ . '/uploads/avatars/' . $safeName . '.' . $ext;
    if (file_exists($candidate)) {
        $avatarPath = 'uploads/avatars/' . $safeName . '.' . $ext . '?v=' . filemtime($candidate);
        break;
    }
}

include 'layouts/header.php';
?>
<body id="page-top">
<div id="wrapper">
    <?php include 'layouts/sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include 'layouts/topbar.php'; ?>

            <div class="container-fluid">
                <!-- Page Heading -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-user-circle mr-2 text-primary"></i>My Profile
                    </h1>
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
                    </a>
                </div>

                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?>
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
                <?php endif; ?>
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Avatar Card -->
                    <div class="col-xl-4 col-lg-5 mb-4">
                        <div class="card shadow text-center py-4">
                            <div class="card-body">
                                <img class="img-profile rounded-circle mb-3"
                                     src="<?= htmlspecialchars($avatarPath) ?>"
                                     id="profilePreview"
                                     style="width:120px;height:120px;object-fit:cover;border:3px solid #4e73df;">
                                <h5 class="font-weight-bold text-gray-800 mb-1">
                                    <?= htmlspecialchars($userData['fname'] ?? '') ?> <?= htmlspecialchars($userData['lname'] ?? '') ?>
                                </h5>
                                <p class="text-muted mb-1">@<?= htmlspecialchars($adminName) ?></p>
                                <span class="badge badge-primary px-3 py-1"><?= htmlspecialchars(ucfirst($role)) ?></span>

                                <hr>
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="custom-file mb-3">
                                        <input type="file" class="custom-file-input" id="avatar" name="avatar"
                                               accept="image/jpeg,image/png,image/gif"
                                               onchange="previewAvatar(this)">
                                        <label class="custom-file-label" for="avatar">Choose photo...</label>
                                    </div>
                                    <button type="submit" name="upload_avatar" class="btn btn-primary btn-block">
                                        <i class="fas fa-upload mr-1"></i>Upload Photo
                                    </button>
                                </form>
                                <small class="text-muted d-block mt-2">JPG, PNG or GIF · max 2 MB</small>
                            </div>
                        </div>
                    </div>

                    <!-- Info Card -->
                    <div class="col-xl-8 col-lg-7 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-id-card mr-2"></i>Account Information
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-sm-4 text-muted font-weight-bold">Full Name</div>
                                    <div class="col-sm-8">
                                        <?= htmlspecialchars(($userData['fname'] ?? '') . ' ' . ($userData['lname'] ?? '')) ?>
                                    </div>
                                </div>
                                <hr>
                                <div class="row mb-3">
                                    <div class="col-sm-4 text-muted font-weight-bold">Username</div>
                                    <div class="col-sm-8">
                                        <code><?= htmlspecialchars($adminName) ?></code>
                                    </div>
                                </div>
                                <hr>
                                <div class="row mb-3">
                                    <div class="col-sm-4 text-muted font-weight-bold">Email</div>
                                    <div class="col-sm-8">
                                        <?= htmlspecialchars($userData['email'] ?? '—') ?>
                                    </div>
                                </div>
                                <hr>
                                <div class="row mb-3">
                                    <div class="col-sm-4 text-muted font-weight-bold">Role</div>
                                    <div class="col-sm-8">
                                        <span class="badge badge-primary"><?= htmlspecialchars(ucfirst($role)) ?></span>
                                    </div>
                                </div>
                                <hr>
                                <div class="mt-3">
                                    <a href="change_password.php" class="btn btn-warning">
                                        <i class="fas fa-key mr-1"></i>Change Password
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include 'layouts/footer.php'; ?>
    </div>
</div>

<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>
<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profilePreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
        // Update label
        var label = input.nextElementSibling;
        if (label) label.textContent = input.files[0].name;
    }
}
</script>
</body>
</html>
