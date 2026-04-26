<?php
session_start();

// Already logged in?
if (!empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    if ($_SESSION['role'] === 'student') {
        header('Location: student_record.php?id=' . intval($_SESSION['student_ref_id'] ?? 0));
    } else {
        header('Location: index.php');
    }
    exit();
}

require_once 'db.php';
require_once 'log_activity.php';

$error = '';

// ── Rate-limit: max 5 bad attempts per 10 min ──────────────────
if (!isset($_SESSION['login_attempts']))  $_SESSION['login_attempts']  = 0;
if (!isset($_SESSION['login_lockout']))   $_SESSION['login_lockout']   = 0;

if ($_SESSION['login_attempts'] >= 5) {
    $wait = $_SESSION['login_lockout'] - time();
    if ($wait > 0) {
        $error = 'Too many failed attempts. Try again in ' . ceil($wait/60) . ' min.';
    } else {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_lockout']  = 0;
    }
}

// ── LOGIN HANDLER ───────────────────────────────────────────────
if (empty($error) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_sel = $_POST['role'] ?? 'faculty';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';

    // ── SUPERADMIN ───────────────────────────────────────────────
    } elseif ($role_sel === 'superadmin') {
        $stmt = $conn->prepare("SELECT id, username, password_hash FROM tbl_superadmin LIMIT 1");
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row && strtolower($row['username']) === strtolower($username)
                 && lti_verify($password, $row['password_hash'])) {

            // Rehash if algo upgraded
            if (password_needs_rehash($row['password_hash'], LTI_ALGO, LTI_OPTS)) {
                $nh = lti_hash($password);
                $u  = $conn->prepare("UPDATE tbl_superadmin SET password_hash=? WHERE id=?");
                $u->bind_param('si', $nh, $row['id']);
                $u->execute(); $u->close();
            }

            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id']        = $row['id'];
            $_SESSION['admin_username']  = $row['username'];
            $_SESSION['role']            = 'superadmin';
            $_SESSION['login_attempts']  = 0;

            log_activity($conn, 'Login', 'auth', $row['username'].' (superadmin)', 'success', 'shield-alt');
            header('Location: index.php'); exit();
        } else {
            $error = 'Invalid superadmin credentials.';
            $_SESSION['login_attempts']++;
            $_SESSION['login_lockout'] = time() + 600;
            log_activity($conn, 'Login failed', 'auth', "$username (superadmin)", 'danger', 'exclamation-triangle');
        }

    // ── FACULTY ───────────────────────────────────────────────────
    } elseif ($role_sel === 'faculty') {
        $stmt = $conn->prepare(
            "SELECT fa.id, fa.faculty_id, fa.username, fa.password_hash
               FROM tbl_faculty_auth fa
              WHERE fa.username = ? LIMIT 1"
        );
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row && lti_verify($password, $row['password_hash'])) {

            if (password_needs_rehash($row['password_hash'], LTI_ALGO, LTI_OPTS)) {
                $nh = lti_hash($password);
                $u  = $conn->prepare("UPDATE tbl_faculty_auth SET password_hash=? WHERE id=?");
                $u->bind_param('si', $nh, $row['id']); $u->execute(); $u->close();
            }

            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id']        = $row['faculty_id'];  // faculty.id in main table
            $_SESSION['admin_username']  = $row['username'];
            $_SESSION['role']            = 'faculty';
            $_SESSION['login_attempts']  = 0;

            log_activity($conn, 'Login', 'auth', $row['username'].' (faculty)', 'success', 'chalkboard-teacher');
            header('Location: index.php'); exit();
        } else {
            $error = 'Invalid faculty credentials.';
            $_SESSION['login_attempts']++;
            $_SESSION['login_lockout'] = time() + 600;
            log_activity($conn, 'Login failed', 'auth', "$username (faculty)", 'danger', 'exclamation-triangle');
        }

    // ── STUDENT (username=fname, password=lname) ──────────────────
    } elseif ($role_sel === 'student') {
        $stmt = $conn->prepare(
            "SELECT sa.id, sa.student_id, sa.username, sa.password_hash, s.fname, s.lname
               FROM tbl_student_auth sa
               JOIN student s ON s.id = sa.student_id
              WHERE sa.username = ? LIMIT 1"
        );
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row && lti_verify($password, $row['password_hash'])) {

            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id']        = $row['id'];
            $_SESSION['admin_username']  = $row['username'];
            $_SESSION['role']            = 'student';
            $_SESSION['student_ref_id']  = $row['student_id'];
            $_SESSION['login_attempts']  = 0;

            log_activity($conn, 'Login', 'auth', $row['username'].' (student)', 'success', 'user-graduate');
            header('Location: student_record.php?id=' . $row['student_id']); exit();
        } else {
            $error = 'Invalid student credentials. Use your First Name as username and Last Name as password.';
            $_SESSION['login_attempts']++;
            $_SESSION['login_lockout'] = time() + 600;
            log_activity($conn, 'Login failed', 'auth', "$username (student)", 'danger', 'exclamation-triangle');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>LTI Grading System – Login</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:300,400,600,700,800,900" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg,#4e73df 0%,#224abe 100%); min-height:100vh; }
        .login-card { border-radius:1rem; box-shadow:0 1rem 3rem rgba(0,0,0,.18); }
        .login-logo { font-size:2.5rem; font-weight:800; color:#4e73df; }
        .btn-login  { font-size:.9rem; letter-spacing:.05rem; padding:.75rem 1rem; }

        /* Role Panels */
        .role-selector { display:flex; gap:.6rem; margin-bottom:1.4rem; background:#f8fafc;
                         padding:.35rem; border-radius:1rem; border:1px solid rgba(148,163,184,.3); }
        .role-panel { flex:1; min-height:130px; display:flex; flex-direction:column;
                      justify-content:center; align-items:center; cursor:pointer;
                      padding:1.1rem .8rem; background:#fff; color:#1f2937;
                      text-align:center; border-radius:.85rem; border:1px solid rgba(148,163,184,.2);
                      transition:all .25s ease; position:relative; }
        .role-panel:hover { transform:translateY(-2px); background:#eff6ff;
                            border-color:rgba(59,130,246,.4); }
        .role-panel.active { background:#1d4ed8; color:#fff;
                             box-shadow:0 10px 28px rgba(30,64,175,.18);
                             border-color:rgba(37,99,235,.4); }
        .role-panel i  { font-size:1.9rem; margin-bottom:.6rem; }
        .role-panel h5 { margin:0; font-size:1rem; font-weight:800; }
        .role-panel p  { margin:.25rem 0 0; font-size:.8rem; opacity:.85; line-height:1.4; }
        .role-panel.active p { color:rgba(255,255,255,.85); }

        /* Superadmin gold accent */
        .role-panel.superadmin-panel.active { background:linear-gradient(135deg,#b45309,#92400e); }

        .role-transition { transition:transform .25s ease, opacity .25s ease; }
        .role-transition.animate-role { transform:translateX(6px); opacity:.45; }
    </style>
</head>
<body>
<div class="container">
<div class="row justify-content-center align-items-center" style="min-height:100vh;">
<div class="col-xl-10 col-lg-12 col-md-9">
<div class="card login-card o-hidden border-0 my-5">
<div class="card-body p-0">
<div class="row">

    <!-- Left graphic -->
    <div class="col-lg-5 d-none d-lg-flex flex-column align-items-center justify-content-center"
         style="background:linear-gradient(135deg,#4e73df,#224abe);border-radius:1rem 0 0 1rem;padding:3rem;">
        <div class="text-center text-white">
            <i class="fas fa-graduation-cap fa-5x mb-4"></i>
            <h2 class="font-weight-bold">LTI Grading System</h2>
            <p class="mt-2" style="opacity:.75;">Manage students, grades &amp; records.</p>
            <hr style="border-color:rgba(255,255,255,.25);width:60%;">
            <p style="font-size:.8rem;opacity:.65;">
                <i class="fas fa-lock mr-1"></i>
                Secured with Argon2id encryption
            </p>
        </div>
    </div>

    <!-- Right form -->
    <div class="col-lg-7">
    <div class="p-5">
        <div class="text-center mb-4">
            <span class="login-logo">LTI</span>
            <p class="text-muted mt-1">Sign in to your account</p>
        </div>

        <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-1"></i>
            <?= htmlspecialchars($error) ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['signup_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-1"></i>
            <?= htmlspecialchars($_SESSION['signup_success']) ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
        <?php unset($_SESSION['signup_success']); ?>
        <?php endif; ?>

        <form id="loginForm" method="POST" action="login.php" class="role-transition">

            <div class="text-center mb-3">
                <h4 class="font-weight-bold text-dark">Choose Login Type</h4>
                <p class="text-muted mb-0 small">Select your role before signing in.</p>
            </div>

            <!-- 3 Role Panels -->
            <div class="role-selector">
                <!-- Superadmin -->
                <label class="role-panel superadmin-panel <?= (!isset($_POST['role']) || $_POST['role']==='superadmin') ? 'active' : '' ?>" for="roleSuperadmin">
                    <input type="radio" name="role" id="roleSuperadmin" value="superadmin" hidden
                           <?= (!isset($_POST['role']) || $_POST['role']==='superadmin') ? 'checked' : '' ?>>
                    <i class="fas fa-user-shield"></i>
                    <h5>Superadmin</h5>
                    <p>Full system control</p>
                </label>
                <!-- Faculty -->
                <label class="role-panel faculty-panel <?= (isset($_POST['role']) && $_POST['role']==='faculty') ? 'active' : '' ?>" for="roleFaculty">
                    <input type="radio" name="role" id="roleFaculty" value="faculty" hidden
                           <?= (isset($_POST['role']) && $_POST['role']==='faculty') ? 'checked' : '' ?>>
                    <i class="fas fa-chalkboard-teacher"></i>
                    <h5>Faculty</h5>
                    <p>Instructor portal</p>
                </label>
                <!-- Student -->
                <label class="role-panel student-panel <?= (isset($_POST['role']) && $_POST['role']==='student') ? 'active' : '' ?>" for="roleStudent">
                    <input type="radio" name="role" id="roleStudent" value="student" hidden
                           <?= (isset($_POST['role']) && $_POST['role']==='student') ? 'checked' : '' ?>>
                    <i class="fas fa-user-graduate"></i>
                    <h5>Student</h5>
                    <p>View my record</p>
                </label>
            </div>

            <p id="roleHelperText" class="text-muted mb-3 small"></p>

            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                    </div>
                    <input type="text" class="form-control form-control-user" id="username"
                           name="username" placeholder="Username"
                           value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                           required autofocus autocomplete="username">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    </div>
                    <input type="password" class="form-control form-control-user" id="password"
                           name="password" placeholder="Password" required autocomplete="current-password">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" id="togglePwd">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>
            </div>

            <button type="submit" name="login" class="btn btn-primary btn-user btn-block btn-login" id="loginBtn">
                <i class="fas fa-sign-in-alt mr-1"></i> Login
            </button>
        </form>

        <hr>
        <div class="text-center">
            <span class="text-muted small">Faculty? </span>
            <a href="signup.php" class="small font-weight-bold text-primary ml-1">Create Faculty Account</a>
        </div>
    </div>
    </div>

</div><!-- /.row -->
</div>
</div>
</div>
</div>
</div>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>
<script>
// Toggle password visibility
const toggleBtn = document.getElementById('togglePwd');
const pwdField  = document.getElementById('password');
const eyeIcon   = document.getElementById('eyeIcon');
toggleBtn.addEventListener('mousedown',  () => { pwdField.type='text';     eyeIcon.classList.replace('fa-eye','fa-eye-slash'); });
toggleBtn.addEventListener('touchstart', () => { pwdField.type='text';     eyeIcon.classList.replace('fa-eye','fa-eye-slash'); });
toggleBtn.addEventListener('mouseup',    () => { pwdField.type='password'; eyeIcon.classList.replace('fa-eye-slash','fa-eye'); });
toggleBtn.addEventListener('mouseleave', () => { pwdField.type='password'; eyeIcon.classList.replace('fa-eye-slash','fa-eye'); });
toggleBtn.addEventListener('touchend',   () => { pwdField.type='password'; eyeIcon.classList.replace('fa-eye-slash','fa-eye'); });

// Role panel switching
const panels    = document.querySelectorAll('.role-panel');
const loginForm = document.getElementById('loginForm');
const loginBtn  = document.getElementById('loginBtn');
const helperTxt = document.getElementById('roleHelperText');
const userField = document.getElementById('username');

const roleConfig = {
    superadmin: {
        helper: 'Enter your Superadmin username and password.',
        placeholder: 'Superadmin username',
        btn: '<i class="fas fa-shield-alt mr-1"></i> Login as Superadmin'
    },
    faculty: {
        helper: 'Enter your faculty username and password.',
        placeholder: 'Faculty username',
        btn: '<i class="fas fa-sign-in-alt mr-1"></i> Login as Faculty'
    },
    student: {
        helper: 'Username = your First Name · Password = your Last Name',
        placeholder: 'Your First Name',
        btn: '<i class="fas fa-user-graduate mr-1"></i> View My Record'
    }
};

function updateRole() {
    const sel = document.querySelector('input[name="role"]:checked');
    if (!sel) return;
    const r = sel.value;
    panels.forEach(p => p.classList.remove('active'));
    sel.closest('.role-panel').classList.add('active');
    loginForm.classList.add('animate-role');
    setTimeout(() => {
        helperTxt.textContent  = roleConfig[r].helper;
        userField.placeholder  = roleConfig[r].placeholder;
        loginBtn.innerHTML     = roleConfig[r].btn;
        loginForm.classList.remove('animate-role');
    }, 120);
}

panels.forEach(p => p.addEventListener('click', function() {
    const inp = this.querySelector('input[name="role"]');
    if (inp && !inp.checked) { inp.checked = true; updateRole(); }
}));
document.querySelectorAll('input[name="role"]').forEach(i => i.addEventListener('change', updateRole));
updateRole();
</script>
</body>
</html>
