<?php
session_start();

if (!empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php'); exit();
}

require_once 'db.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {

    $fname    = trim($_POST['fname']    ?? '');
    $mname    = trim($_POST['mname']    ?? '');
    $lname    = trim($_POST['lname']    ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $address  = trim($_POST['address']  ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password']         ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // ── Validation ───────────────────────────────────────────────
    if (empty($fname) || empty($lname) || empty($username) || empty($password)) {
        $error = 'First name, last name, username, and password are required.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Password must contain at least one number.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {

        // ── Check duplicate username ──────────────────────────────
        $chk = $conn->prepare("SELECT id FROM tbl_faculty_auth WHERE username=? LIMIT 1");
        $chk->bind_param('s', $username);
        $chk->execute();
        $chk->store_result();
        $dup = $chk->num_rows > 0;
        $chk->close();

        if ($dup) {
            $error = 'Username is already taken. Please choose another.';
        } else {

            // ── Avatar upload ─────────────────────────────────────
            $avatarFile = null;
            if (!empty($_FILES['avatar']['name'])) {
                $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif'];
                $safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', $username);
                if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                    $error = 'Failed to upload image.';
                } elseif (!array_key_exists($_FILES['avatar']['type'], $allowed)) {
                    $error = 'Only JPG, PNG, GIF images are allowed.';
                } elseif ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
                    $error = 'Image must be smaller than 2 MB.';
                } else {
                    $dir = __DIR__ . '/uploads/avatars';
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    $ext        = $allowed[$_FILES['avatar']['type']];
                    $avatarFile = "uploads/avatars/{$safeName}.{$ext}";
                    if (!move_uploaded_file($_FILES['avatar']['tmp_name'], __DIR__.'/'.$avatarFile)) {
                        $error = 'Unable to save uploaded image.';
                    }
                }
            }

            if (empty($error)) {
                // ── 1. Insert into faculty (profile data, NO password) ──
                $stmt = $conn->prepare(
                    "INSERT INTO faculty (first_name,middle_name,last_name,email,phone_number,address,user_name)
                     VALUES (?,?,?,?,?,?,?)"
                );
                $stmt->bind_param('sssssss', $fname, $mname, $lname, $email, $phone, $address, $username);

                if ($stmt->execute()) {
                    $faculty_id = $conn->insert_id;
                    $stmt->close();

                    // ── 2. Insert into tbl_faculty_auth (hashed password) ──
                    $hash = lti_hash($password);
                    $auth = $conn->prepare(
                        "INSERT INTO tbl_faculty_auth (faculty_id, username, password_hash) VALUES (?,?,?)"
                    );
                    $auth->bind_param('iss', $faculty_id, $username, $hash);

                    if ($auth->execute()) {
                        $auth->close();
                        $_SESSION['signup_success'] = "Faculty account created! Please log in.";
                        header('Location: login.php'); exit();
                    } else {
                        // Rollback faculty row if auth insert fails
                        $conn->query("DELETE FROM faculty WHERE id=$faculty_id");
                        $error = 'Registration failed (auth). Please try again.';
                        $auth->close();
                    }
                } else {
                    $error = 'Registration failed. Please try again.';
                    $stmt->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>LTI – Faculty Sign Up</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:300,400,600,700,800" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        body { background:linear-gradient(135deg,#4e73df,#224abe); min-height:100vh; }
        .signup-card { border-radius:1rem; box-shadow:0 1rem 3rem rgba(0,0,0,.18); }
        .strength-bar { height:6px; border-radius:3px; transition:width .3s, background .3s; }
        .pwd-rule { font-size:.78rem; }
        .pwd-rule.ok   { color:#16a34a; }
        .pwd-rule.fail { color:#dc2626; }
    </style>
</head>
<body>
<div class="container">
<div class="row justify-content-center align-items-center" style="min-height:100vh;">
<div class="col-xl-9 col-lg-11 col-md-12">
<div class="card signup-card border-0 my-5">
<div class="card-body p-0">
<div class="row">

    <!-- Left panel -->
    <div class="col-lg-4 d-none d-lg-flex flex-column align-items-center justify-content-center"
         style="background:linear-gradient(135deg,#4e73df,#224abe);border-radius:1rem 0 0 1rem;padding:2.5rem;">
        <div class="text-center text-white">
            <i class="fas fa-chalkboard-teacher fa-5x mb-4"></i>
            <h3 class="font-weight-bold">Faculty Portal</h3>
            <p style="opacity:.75;font-size:.9rem;">Create your instructor account to manage student records.</p>
            <hr style="border-color:rgba(255,255,255,.25);width:70%;">
            <p style="font-size:.75rem;opacity:.6;"><i class="fas fa-lock mr-1"></i>Password secured with Argon2id</p>
        </div>
    </div>

    <!-- Right form -->
    <div class="col-lg-8">
    <div class="p-5">
        <div class="text-center mb-4">
            <h4 class="font-weight-bold">Create Faculty Account</h4>
            <p class="text-muted small">Fill in your details below. Your password is stored encrypted.</p>
        </div>

        <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
        <?php endif; ?>

        <form method="POST" action="signup.php" enctype="multipart/form-data">

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>First Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="fname"
                           value="<?= htmlspecialchars($_POST['fname'] ?? '') ?>" required>
                </div>
                <div class="form-group col-md-4">
                    <label>Middle Name</label>
                    <input type="text" class="form-control" name="mname"
                           value="<?= htmlspecialchars($_POST['mname'] ?? '') ?>">
                </div>
                <div class="form-group col-md-4">
                    <label>Last Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="lname"
                           value="<?= htmlspecialchars($_POST['lname'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Email Address</label>
                    <input type="email" class="form-control" name="email"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group col-md-6">
                    <label>Phone Number</label>
                    <input type="text" class="form-control" name="phone"
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Address</label>
                <input type="text" class="form-control" name="address"
                       value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Username <span class="text-danger">*</span></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                    </div>
                    <input type="text" class="form-control" name="username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required
                           autocomplete="username">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="password" id="pwd"
                               required minlength="8" autocomplete="new-password">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" id="togglePwd">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <!-- Strength bar -->
                    <div class="mt-1 mb-1" style="background:#e5e7eb;border-radius:3px;">
                        <div class="strength-bar" id="strengthBar" style="width:0%;background:#ef4444;"></div>
                    </div>
                    <div id="pwdRules">
                        <div class="pwd-rule fail" id="r-len"><i class="fas fa-times-circle mr-1"></i>At least 8 characters</div>
                        <div class="pwd-rule fail" id="r-upper"><i class="fas fa-times-circle mr-1"></i>At least one uppercase letter</div>
                        <div class="pwd-rule fail" id="r-num"><i class="fas fa-times-circle mr-1"></i>At least one number</div>
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <label>Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" name="confirm_password" id="cpwd"
                           required autocomplete="new-password">
                    <div id="matchMsg" class="small mt-1"></div>
                </div>
            </div>

            <div class="form-group">
                <label>Profile Photo <span class="text-muted small">(optional, max 2 MB)</span></label>
                <input type="file" class="form-control-file" name="avatar" accept="image/*">
            </div>

            <button type="submit" name="signup" class="btn btn-primary btn-block btn-user mt-2">
                <i class="fas fa-user-plus mr-1"></i> Create Faculty Account
            </button>
        </form>

        <hr>
        <div class="text-center">
            <a href="login.php" class="small text-primary">
                <i class="fas fa-arrow-left mr-1"></i>Back to Login
            </a>
        </div>
    </div>
    </div>

</div>
</div>
</div>
</div>
</div>
</div>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle password visibility
document.getElementById('togglePwd').addEventListener('click', function() {
    const f = document.getElementById('pwd');
    const i = this.querySelector('i');
    f.type = (f.type==='password') ? 'text' : 'password';
    i.classList.toggle('fa-eye'); i.classList.toggle('fa-eye-slash');
});

// Password strength checker
const pwd   = document.getElementById('pwd');
const cpwd  = document.getElementById('cpwd');
const bar   = document.getElementById('strengthBar');
const rLen  = document.getElementById('r-len');
const rUp   = document.getElementById('r-upper');
const rNum  = document.getElementById('r-num');
const match = document.getElementById('matchMsg');

function checkRule(el, ok) {
    el.className = 'pwd-rule ' + (ok ? 'ok' : 'fail');
    el.querySelector('i').className = 'fas ' + (ok ? 'fa-check-circle' : 'fa-times-circle') + ' mr-1';
}
pwd.addEventListener('input', function() {
    const v = this.value;
    const len   = v.length >= 8;
    const upper = /[A-Z]/.test(v);
    const num   = /[0-9]/.test(v);
    checkRule(rLen,  len);
    checkRule(rUp,   upper);
    checkRule(rNum,  num);
    const score = [len, upper, num].filter(Boolean).length;
    const colors = ['#ef4444','#f97316','#22c55e'];
    bar.style.width     = (score / 3 * 100) + '%';
    bar.style.background = colors[score - 1] || '#ef4444';
});
cpwd.addEventListener('input', function() {
    if (this.value === '') { match.textContent = ''; return; }
    if (this.value === pwd.value) {
        match.innerHTML = '<span class="text-success"><i class="fas fa-check-circle mr-1"></i>Passwords match</span>';
    } else {
        match.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle mr-1"></i>Passwords do not match</span>';
    }
});
</script>
</body>
</html>
