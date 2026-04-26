<?php
require_once 'auth.php';
require_once 'db.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {

    $current  = $_POST['current_password']  ?? '';
    $new_pass = $_POST['new_password']      ?? '';
    $confirm  = $_POST['confirm_password']  ?? '';
    $role     = current_role();

    // ── Superadmin cannot be switched to someone else
    // ── Students cannot change their own password (it's always their lname)
    if ($role === 'student') {
        $error = 'Students cannot change their login password. Your password is always your Last Name.';
    } elseif (empty($current) || empty($new_pass) || empty($confirm)) {
        $error = 'All fields are required.';
    } elseif (strlen($new_pass) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $new_pass)) {
        $error = 'New password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[0-9]/', $new_pass)) {
        $error = 'New password must contain at least one number.';
    } elseif ($new_pass !== $confirm) {
        $error = 'New passwords do not match.';
    } elseif ($new_pass === $current) {
        $error = 'New password must be different from the current one.';
    } else {

        // ── Fetch stored hash from correct auth table ─────────────
        if ($role === 'superadmin') {
            $stmt = $conn->prepare("SELECT id, password_hash FROM tbl_superadmin LIMIT 1");
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else {
            // faculty
            $uid  = intval($_SESSION['admin_id'] ?? 0);
            $stmt = $conn->prepare(
                "SELECT id, password_hash FROM tbl_faculty_auth WHERE faculty_id=? LIMIT 1"
            );
            $stmt->bind_param('i', $uid);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }

        if (!$row) {
            $error = 'Account record not found.';
        } elseif (!lti_verify($current, $row['password_hash'])) {
            $error = 'Current password is incorrect.';
        } else {
            $new_hash = lti_hash($new_pass);

            if ($role === 'superadmin') {
                $upd = $conn->prepare("UPDATE tbl_superadmin SET password_hash=? WHERE id=?");
                $upd->bind_param('si', $new_hash, $row['id']);
            } else {
                $upd = $conn->prepare("UPDATE tbl_faculty_auth SET password_hash=? WHERE id=?");
                $upd->bind_param('si', $new_hash, $row['id']);
            }

            if ($upd->execute()) {
                $success = 'Password changed successfully.';
            } else {
                $error = 'Failed to update password. Please try again.';
            }
            $upd->close();
        }
    }
}
?>
<?php include 'layouts/header.php'; ?>
<body id="page-top">
<div id="wrapper">
    <?php include 'layouts/sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include 'layouts/topbar.php'; ?>
            <div class="container-fluid">

                <div class="d-flex align-items-center mb-4">
                    <h1 class="h3 text-gray-800 mb-0 mr-3">Change Password</h1>
                    <span class="badge badge-<?= is_superadmin() ? 'danger' : 'primary' ?> px-3 py-2">
                        <i class="fas fa-<?= is_superadmin() ? 'shield-alt' : 'chalkboard-teacher' ?> mr-1"></i>
                        <?= ucfirst(current_role()) ?>
                    </span>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <?php if (current_role() === 'student'): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    Your login password is always your <strong>Last Name</strong>. Only a Superadmin can change your name.
                </div>
                <?php else: ?>
                <div class="card shadow mb-4" style="max-width:520px;">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-key mr-2"></i>Update Your Password
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="change_password.php">

                            <div class="form-group">
                                <label>Current Password</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    </div>
                                    <input type="password" class="form-control" name="current_password"
                                           required autocomplete="current-password">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>New Password</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                                    </div>
                                    <input type="password" class="form-control" name="new_password"
                                           id="newPwd" required minlength="8" autocomplete="new-password">
                                </div>
                                <small class="text-muted">Min 8 characters, 1 uppercase letter, 1 number.</small>
                            </div>

                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-check"></i></span>
                                    </div>
                                    <input type="password" class="form-control" name="confirm_password"
                                           id="confPwd" required autocomplete="new-password">
                                </div>
                                <div id="matchHint" class="small mt-1"></div>
                            </div>

                            <button type="submit" name="change_password" class="btn btn-primary btn-block">
                                <i class="fas fa-save mr-1"></i> Update Password
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
        <?php include 'layouts/footer.php'; ?>
    </div>
</div>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>
<script>
const np = document.getElementById('newPwd');
const cp = document.getElementById('confPwd');
const mh = document.getElementById('matchHint');
if (cp) {
    cp.addEventListener('input', function() {
        if (!this.value) { mh.textContent=''; return; }
        mh.innerHTML = this.value === np.value
            ? '<span class="text-success"><i class="fas fa-check-circle mr-1"></i>Passwords match</span>'
            : '<span class="text-danger"><i class="fas fa-times-circle mr-1"></i>Passwords do not match</span>';
    });
}
</script>
</body>
</html>
