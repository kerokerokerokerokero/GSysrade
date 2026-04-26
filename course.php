<?php session_start(); ?>
<?php include 'layouts/header.php'; ?>
<body id="page-top">
<div id="wrapper">

    <?php include 'layouts/sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

<?php include 'layouts/topbar.php'; ?>

<div class="container-fluid">
 <h1 class="h3 mb-2 text-gray-800">Course Table</h1>

<?php if(isset($_SESSION["success_msg"])): ?>
<div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
  <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($_SESSION["success_msg"]); unset($_SESSION["success_msg"]); ?>
  <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>
<?php if(isset($_SESSION["error_msg"])): ?>
<div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
  <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($_SESSION["error_msg"]); unset($_SESSION["error_msg"]); ?>
  <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<?php
require_once 'db.php';
$per_page = 20;
$page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search   = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset   = ($page - 1) * $per_page;
$like     = '%' . $conn->real_escape_string($search) . '%';

$count_result = $conn->query("SELECT COUNT(*) AS total FROM course WHERE code LIKE '$like' OR name LIKE '$like'");
$total_rows   = $count_result->fetch_assoc()['total'];
$total_pages  = max(1, ceil($total_rows / $per_page));
if ($page > $total_pages) $page = $total_pages;

$result = $conn->query("SELECT * FROM course WHERE code LIKE '$like' OR name LIKE '$like' ORDER BY id DESC LIMIT $per_page OFFSET $offset");
?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
            <h6 class="m-0 font-weight-bold text-primary">Course</h6>
            <div class="d-flex align-items-center flex-wrap" style="gap:8px;">
                <form method="GET" action="" class="mb-0" id="searchForm">
                    <div class="input-group input-group-sm" style="width:260px;">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white border-right-0" style="border-radius:4px 0 0 4px;">
                                <i class="fas fa-search text-primary fa-sm"></i>
                            </span>
                        </div>
                        <input type="text" class="form-control border-left-0" name="search" id="liveSearch"
                               placeholder="Search courses..." value="<?= htmlspecialchars($search); ?>"
                               autocomplete="off" style="border-radius:0 4px 4px 0;">
                        <?php if($search): ?>
                        <div class="input-group-append">
                            <a href="course.php" class="btn btn-outline-secondary btn-sm" id="clearSearch" title="Clear search">
                                <i class="fas fa-times fa-sm"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </form>
                <a href="#" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addCourseModal">
                    <i class="fas fa-plus"></i> Add Course
                </a>
            </div>
        </div>
    </div>

<!-- Add Course Modal -->
<div class="modal fade" id="addCourseModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form action="code/course.php" method="POST">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Course</h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group"><label>Code</label><input type="text" class="form-control" name="code" required></div>
          <div class="form-group"><label>Description</label><input type="text" class="form-control" name="description" required></div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="create" class="btn btn-primary">Save</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Edit Course Modal -->
<div class="modal fade" id="editCurriculumModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form action="code/course.php" method="POST">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Course</h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="edit-id">
          <div class="form-group"><label>Code</label><input type="text" class="form-control" name="edit-code" id="edit-code" required></div>
          <div class="form-group"><label>Description</label><input type="text" class="form-control" name="edit-name" id="edit-name" required></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" name="update" class="btn btn-primary">Save Changes</button>
        </div>
      </div>
    </form>
  </div>
</div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Description</th>
                        
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['code']); ?></td>
                            <td><?= htmlspecialchars($row['name']); ?></td>
                         
                            <td class="text-center">
                                <div class="d-flex justify-content-center" style="gap:4px;">
                                    <a href="course_year.php?id=<?=$row['id'];?>" class="btn btn-sm btn-success">view subject</a>
                                    <a href="#" class="btn btn-sm btn-primary editBtn"
                                       data-toggle="modal" data-target="#editCurriculumModal"
                                       data-id="<?= $row['id']; ?>"
                                       data-name="<?= htmlspecialchars($row['name']); ?>"
                                       data-code="<?= htmlspecialchars($row['code']); ?>">Edit</a>
                                    <a href="delete.php?type=course&id=<?= $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">No Course found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap" style="gap:8px;">
            <small class="text-muted">
                Showing <?= $total_rows > 0 ? $offset + 1 : 0; ?>–<?= min($offset + $per_page, $total_rows); ?> of <?= $total_rows; ?> entries<?= $search ? ' (filtered)' : ''; ?>
            </small>
            <nav>
                <ul class="pagination mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?= $page - 1; ?>&search=<?= urlencode($search); ?>">Previous</a>
                    </li>
                    <?php
                    $start = max(1, $page - 2); $end = min($total_pages, $page + 2);
                    if ($start > 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <li class="page-item <?= $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?= $i; ?>&search=<?= urlencode($search); ?>"><?= $i; ?></a>
                        </li>
                    <?php endfor;
                    if ($end < $total_pages) echo '<li class="page-item disabled"><span class="page-link">…</span></li>'; ?>
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?= $page + 1; ?>&search=<?= urlencode($search); ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>

</div><!-- /.container-fluid -->
        </div>
        <?php include 'layouts/footer.php'; ?>
    </div>
</div>

<a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ready to Leave?</h5>
                <button class="close" type="button" data-dismiss="modal"><span>×</span></button>
            </div>
            <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                <a class="btn btn-primary" href="login.php">Logout</a>
            </div>
        </div>
    </div>
</div>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>
<script src="vendor/chart.js/Chart.min.js"></script>
<script src="js/demo/chart-area-demo.js"></script>
<script src="js/demo/chart-pie-demo.js"></script>

<script>
$(document).ready(function () {
  $('.editBtn').on('click', function () {
    $('#edit-id').val($(this).data('id'));
    $('#edit-name').val($(this).data('name'));
    $('#edit-code').val($(this).data('code'));
  });
});
</script>

<script>
$(document).ready(function () {
  var searchTimer;

  function refreshSearchResults() {
    var query = $('#liveSearch').val().trim();
    var params = new URLSearchParams(window.location.search);
    if (query) {
      params.set('search', query);
    } else {
      params.delete('search');
    }
    params.delete('page');
    var url = window.location.pathname + (params.toString() ? '?' + params.toString() : '');

    history.replaceState(null, '', url);
    $('.card-body').load(url + ' .card-body > *', function () {
      $('#liveSearch').focus();
      var input = $('#liveSearch').get(0);
      if (input && input.setSelectionRange) {
        var len = query.length;
        input.setSelectionRange(len, len);
      }
    });
  }

  $('#liveSearch').on('input', function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(refreshSearchResults, 400);
  });
  // Clear button
  $('#clearSearch').on('click', function(e){
    e.preventDefault();
    $('#liveSearch').val('').focus();
    refreshSearchResults();
  });
});
</script>

</body>
</html>
