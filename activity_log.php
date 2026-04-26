<?php
require_once 'auth.php';
require_once 'db.php';

$adminName = $_SESSION['admin_username'] ?? 'User';

// ── Filters ────────────────────────────────────────────────────────────────
$filter_category = $_GET['category'] ?? '';
$filter_user     = trim($_GET['user'] ?? '');
$filter_date     = $_GET['date'] ?? '';
$page            = max(1, (int)($_GET['page'] ?? 1));
$per_page        = 25;
$offset          = ($page - 1) * $per_page;

$where  = [];
$params = [];
$types  = '';

if ($filter_category !== '') {
    $where[]  = 'category = ?';
    $params[] = $filter_category;
    $types   .= 's';
}
if ($filter_user !== '') {
    $where[]  = 'username LIKE ?';
    $params[] = "%$filter_user%";
    $types   .= 's';
}
if ($filter_date !== '') {
    $where[]  = 'DATE(created_at) = ?';
    $params[] = $filter_date;
    $types   .= 's';
}
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ── Count ──────────────────────────────────────────────────────────────────
$count_stmt = $conn->prepare("SELECT COUNT(*) AS c FROM activity_log $where_sql");
if ($params) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_rows  = (int)$count_stmt->get_result()->fetch_assoc()['c'];
$total_pages = max(1, ceil($total_rows / $per_page));
$count_stmt->close();

// ── Fetch rows ─────────────────────────────────────────────────────────────
$fetch_types  = $types . 'ii';
$fetch_params = array_merge($params, [$per_page, $offset]);
$stmt = $conn->prepare(
    "SELECT id, username, action, category, detail, color, icon, ip_address, created_at
     FROM activity_log $where_sql ORDER BY id DESC LIMIT ? OFFSET ?"
);
$stmt->bind_param($fetch_types, ...$fetch_params);
$stmt->execute();
$result   = $stmt->get_result();
$activity = [];
while ($row = $result->fetch_assoc()) { $activity[] = $row; }
$stmt->close();

// ── Summary counts ─────────────────────────────────────────────────────────
$counts = [
    'students' => (int)($conn->query("SELECT COUNT(*) AS c FROM student")->fetch_assoc()['c']  ?? 0),
    'faculty'  => (int)($conn->query("SELECT COUNT(*) AS c FROM faculty")->fetch_assoc()['c']  ?? 0),
    'courses'  => (int)($conn->query("SELECT COUNT(*) AS c FROM course")->fetch_assoc()['c']   ?? 0),
    'subjects' => (int)($conn->query("SELECT COUNT(*) AS c FROM subject")->fetch_assoc()['c']  ?? 0),
];

$categories = ['student','faculty','course','subject','curriculum','auth','other'];

include 'layouts/header.php';
?>
<body id="page-top">
<div id="wrapper">
    <?php include 'layouts/sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include 'layouts/topbar.php'; ?>
            <div class="container-fluid">

                <!-- Heading -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-list mr-2 text-primary"></i>Activity Log
                    </h1>
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
                    </a>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Students</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($counts['students']) ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Faculty</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($counts['faculty']) ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Courses</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($counts['courses']) ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Subjects</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($counts['subjects']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Table -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-history mr-2"></i>System Activity
                            <span class="badge badge-primary ml-1"><?= number_format($total_rows) ?> entries</span>
                        </h6>
                        <small class="text-muted">Logged in as: <strong><?= htmlspecialchars($adminName) ?></strong></small>
                    </div>

                    <!-- Filters -->
                    <div class="card-body border-bottom py-2">
                        <form method="GET" action="activity_log.php" class="form-inline flex-wrap" style="gap:8px;">
                            <select name="category" class="form-control form-control-sm mr-2 mb-1">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat ?>" <?= $filter_category === $cat ? 'selected' : '' ?>>
                                        <?= ucfirst($cat) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="user" class="form-control form-control-sm mr-2 mb-1"
                                   placeholder="Filter by user…" value="<?= htmlspecialchars($filter_user) ?>"
                                   autocomplete="off" style="width:160px;">
                            <input type="date" name="date" class="form-control form-control-sm mr-2 mb-1"
                                   value="<?= htmlspecialchars($filter_date) ?>">
                            <button type="submit" class="btn btn-sm btn-primary mr-1 mb-1">
                                <i class="fas fa-filter mr-1"></i>Filter
                            </button>
                            <a href="activity_log.php" class="btn btn-sm btn-outline-secondary mb-1">
                                <i class="fas fa-times mr-1"></i>Clear
                            </a>
                        </form>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width:50px;">#</th>
                                        <th style="width:150px;"><i class="fas fa-user mr-1 text-muted"></i>User</th>
                                        <th style="width:200px;">Action</th>
                                        <th>Detail</th>
                                        <th style="width:110px;">Category</th>
                                        <th style="width:120px;">IP Address</th>
                                        <th style="width:155px;"><i class="fas fa-clock mr-1 text-muted"></i>Date &amp; Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($activity)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-5">
                                                <i class="fas fa-info-circle mr-1"></i>No activity recorded yet.
                                                <?php if ($where_sql): ?>
                                                    <br><small><a href="activity_log.php">Clear filters</a> to see all entries.</small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($activity as $i => $act): ?>
                                        <tr>
                                            <td class="text-muted small"><?= $offset + $i + 1 ?></td>
                                            <td>
                                                <i class="fas fa-user-circle mr-1 text-secondary"></i>
                                                <strong><?= htmlspecialchars($act['username']) ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= htmlspecialchars($act['color']) ?> mr-1">
                                                    <i class="fas fa-<?= htmlspecialchars($act['icon']) ?>"></i>
                                                </span>
                                                <?= htmlspecialchars($act['action']) ?>
                                            </td>
                                            <td class="text-gray-800"><?= htmlspecialchars($act['detail']) ?></td>
                                            <td>
                                                <span class="badge badge-light text-<?= htmlspecialchars($act['color']) ?> border border-<?= htmlspecialchars($act['color']) ?>">
                                                    <?= htmlspecialchars(ucfirst($act['category'])) ?>
                                                </span>
                                            </td>
                                            <td class="text-muted small"><?= htmlspecialchars($act['ip_address'] ?? '—') ?></td>
                                            <td class="text-muted small">
                                                <?= date('M d, Y', strtotime($act['created_at'])) ?><br>
                                                <span class="text-secondary"><?= date('h:i:s A', strtotime($act['created_at'])) ?></span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php if ($total_pages > 1): ?>
                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            Showing <?= $offset + 1 ?>–<?= min($offset + $per_page, $total_rows) ?>
                            of <?= number_format($total_rows) ?> entries
                        </small>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page-1 ?>&category=<?= urlencode($filter_category) ?>&user=<?= urlencode($filter_user) ?>&date=<?= urlencode($filter_date) ?>">&laquo;</a>
                            </li>
                            <?php for ($p = max(1,$page-2); $p <= min($total_pages,$page+2); $p++): ?>
                            <li class="page-item <?= $p===$page?'active':'' ?>">
                                <a class="page-link" href="?page=<?= $p ?>&category=<?= urlencode($filter_category) ?>&user=<?= urlencode($filter_user) ?>&date=<?= urlencode($filter_date) ?>"><?= $p ?></a>
                            </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page+1 ?>&category=<?= urlencode($filter_category) ?>&user=<?= urlencode($filter_user) ?>&date=<?= urlencode($filter_date) ?>">&raquo;</a>
                            </li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
        <?php include 'layouts/footer.php'; ?>
    </div>
</div>

<a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>
</body>
</html>
