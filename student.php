<?php session_start(); ?>
<?php include 'layouts/header.php'; ?>
<body id="page-top">
<div id="wrapper">

    <?php include 'layouts/sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

<?php include 'layouts/topbar.php'; ?>

<div class="container-fluid">
 <h1 class="h3 mb-2 text-gray-800">Students</h1>

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

$count_result = $conn->query("SELECT COUNT(*) AS total FROM student WHERE fname LIKE '$like' OR lname LIKE '$like' OR mname LIKE '$like'");
$total_rows   = $count_result->fetch_assoc()['total'];
$total_pages  = max(1, ceil($total_rows / $per_page));
if ($page > $total_pages) $page = $total_pages;

$result = $conn->query("SELECT s.*, c.code as course_code FROM student s LEFT JOIN course c ON s.course = c.id WHERE s.fname LIKE '$like' OR s.lname LIKE '$like' OR s.mname LIKE '$like' ORDER BY s.id DESC LIMIT $per_page OFFSET $offset");
$year_labels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'];
?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
            <h6 class="m-0 font-weight-bold text-primary">Student List</h6>
            <div class="d-flex align-items-center flex-wrap" style="gap:8px;">
                <form method="GET" action="" class="mb-0" id="searchForm">
                    <div class="input-group input-group-sm" style="width:260px;">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white border-right-0" style="border-radius:4px 0 0 4px;">
                                <i class="fas fa-search text-primary fa-sm"></i>
                            </span>
                        </div>
                        <input type="text" class="form-control border-left-0" name="search" id="liveSearch"
                               placeholder="Search students..." value="<?= htmlspecialchars($search); ?>"
                               autocomplete="off" style="border-radius:0 4px 4px 0;">
                        <?php if($search): ?>
                        <div class="input-group-append">
                            <a href="student.php" class="btn btn-outline-secondary btn-sm" id="clearSearch" title="Clear search">
                                <i class="fas fa-times fa-sm"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </form>
                <a href="#" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addStudentModal">
                    <i class="fas fa-plus"></i> Add Student
                </a>
            </div>
        </div>
    </div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <form action="code/student.php" method="POST">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Add Student</h5>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <h6 class="text-primary mb-3">Student Information</h6>
          <div class="form-row">
            <div class="form-group col-md-4">
              <label>First Name:</label>
              <input type="text" class="form-control name-input" name="fname" required>
            </div>
            <div class="form-group col-md-4">
              <label>Middle Name:</label>
              <input type="text" class="form-control name-input" name="mname">
            </div>
            <div class="form-group col-md-4">
              <label>Last Name:</label>
              <input type="text" class="form-control name-input" name="lname" required>
            </div>
            <div class="form-group col-md-4">
              <label>Date of Birth:</label>
              <input type="date" class="form-control" name="dob" required>
            </div>
          </div>
          <h6 class="text-primary mt-4 mb-3">Student School Information</h6>
          <div class="form-row">
            <div class="form-group col-md-4">
              <label>Course:</label>
              <select class="form-control" name="course">
                <option value="">-- select --</option>
                <?php $r = $conn->query("SELECT * FROM course"); while($c=$r->fetch_assoc()): ?>
                <option value="<?=$c['id'];?>"><?=$c['code'];?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-group col-md-4">
              <label>Year:</label>
              <select class="form-control" name="year" required>
                <option value="">-- select --</option>
                <option value="1">1st Year</option>
                <option value="2">2nd Year</option>
                <option value="3">3rd Year</option>
                <option value="4">4th Year</option>
              </select>
            </div>
        
          </div>
       
          <h6 class="text-primary mt-4 mb-3">Contact Information</h6>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Email:</label>
              <input type="email" class="form-control" name="email" required>
            </div>
            <div class="form-group col-md-6">
              <label>Phone Number:</label>
              <input type="tel" class="form-control" name="phone" required>
            </div>
            <div class="form-group col-md-12">
              <label>Address:</label>
              <textarea class="form-control" name="address" rows="2" required></textarea>
            </div>
          </div>
          <h6 class="text-primary mt-4 mb-3">Account Information</h6>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Username <small class="text-muted">(auto-generated if blank)</small></label>
              <input type="text" class="form-control" name="username" placeholder="Leave blank to auto-generate">
            </div>
            <div class="form-group col-md-6">
              <label>Password:</label>
              <input type="password" class="form-control" name="password" required>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="create" class="btn btn-success">Add</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="EditsubjectModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <form action="code/student.php" method="POST">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Edit Student</h5>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="edit-id">
          <h6 class="text-primary mb-3">Student Information</h6>
          <div class="form-row">
            <div class="form-group col-md-4"><label>First Name:</label><input type="text" class="form-control name-input" name="fname" id="edit-fname" required></div>
            <div class="form-group col-md-4"><label>Middle Name:</label><input type="text" class="form-control name-input" name="mname" id="edit-mname"></div>
            <div class="form-group col-md-4"><label>Last Name:</label><input type="text" class="form-control name-input" name="lname" id="edit-lname" required></div>
            <div class="form-group col-md-4"><label>Date of Birth:</label><input type="date" class="form-control" name="dob" id="edit-dob" required></div>
          </div>
          <h6 class="text-primary mt-4 mb-3">Student School Information</h6>
          <div class="form-row">
            <div class="form-group col-md-4"><label>Course:</label><select class="form-control" name="course" id="edit-course"><option value="">-- select --</option><?php $r = $conn->query("SELECT * FROM course"); while($c=$r->fetch_assoc()): ?><option value="<?=$c['id'];?>"><?=$c['code'];?></option><?php endwhile; ?></select></div>
            <div class="form-group col-md-4"><label>Year:</label><select class="form-control" name="year" id="edit-year" required><option value="">-- select --</option><option value="1">1st Year</option><option value="2">2nd Year</option><option value="3">3rd Year</option><option value="4">4th Year</option></select></div>
          </div>
          <h6 class="text-primary mt-4 mb-3">Contact Information</h6>
          <div class="form-row">
            <div class="form-group col-md-6"><label>Email:</label><input type="email" class="form-control" name="email" id="edit-email" required></div>
            <div class="form-group col-md-6"><label>Phone Number:</label><input type="tel" class="form-control" name="phone" id="edit-phone" required></div>
            <div class="form-group col-md-12"><label>Address:</label><textarea class="form-control" name="address" id="edit-address" rows="2" required></textarea></div>
          </div>
          <h6 class="text-primary mt-4 mb-3">Account Information</h6>
          <div class="form-row">
            <div class="form-group col-md-6"><label>Username</label><input type="text" class="form-control" name="username" id="edit-username"></div>
            <div class="form-group col-md-6"><label>Password:</label><input type="password" class="form-control" name="password" id="edit-password" placeholder="Leave blank to keep existing"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="update" class="btn btn-success">Save Changes</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
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
                        <th>Student Name</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars(trim($row['fname'] . ' ' . $row['mname'] . ' ' . $row['lname'])); ?></td>
                            <td><?= htmlspecialchars($row['course_code'] ?? 'N/A'); ?></td>
                            <td><?= htmlspecialchars($year_labels[intval($row['year'])] ?? $row['year'] ?? 'N/A'); ?></td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center" style="gap:4px;">
                                    <a href="student_record.php?id=<?=$row['id'];?>" class="btn btn-sm btn-success">view</a>
                                    <a href="#" class="btn btn-sm btn-primary editBtn" data-toggle="modal" data-target="#EditsubjectModal"
                                       data-id="<?= $row['id']; ?>"
                                       data-fname="<?= htmlspecialchars($row['fname'], ENT_QUOTES); ?>"
                                       data-mname="<?= htmlspecialchars($row['mname'], ENT_QUOTES); ?>"
                                       data-lname="<?= htmlspecialchars($row['lname'], ENT_QUOTES); ?>"
                                       data-dob="<?= htmlspecialchars($row['dob'], ENT_QUOTES); ?>"
                                       data-course="<?= htmlspecialchars($row['course'], ENT_QUOTES); ?>"
                                       data-year="<?= htmlspecialchars($row['year'], ENT_QUOTES); ?>"
                                       data-email="<?= htmlspecialchars($row['email'], ENT_QUOTES); ?>"
                                       data-phone="<?= htmlspecialchars($row['phone'], ENT_QUOTES); ?>"
                                       data-address="<?= htmlspecialchars($row['address'], ENT_QUOTES); ?>"
                                       data-username="<?= htmlspecialchars($row['username'], ENT_QUOTES); ?>">Edit</a>
                                    <a href="delete.php?type=student&id=<?=$row['id'];?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')"><i class="fas fa-trash-alt"></i> Delete</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">No Student found.</td></tr>
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
    $('#edit-fname').val($(this).data('fname'));
    $('#edit-mname').val($(this).data('mname'));
    $('#edit-lname').val($(this).data('lname'));
    $('#edit-dob').val($(this).data('dob'));
    $('#edit-course').val($(this).data('course'));
    $('#edit-year').val($(this).data('year'));
    $('#edit-email').val($(this).data('email'));
    $('#edit-phone').val($(this).data('phone'));
    $('#edit-address').val($(this).data('address'));
    $('#edit-username').val($(this).data('username'));
    $('#edit-password').val('');
  });

  function normalizeNameInput(value) {
    return value
      .replace(/[^A-Za-z\s'-]/g, '')
      .split(/\s+/)
      .map(function (part) {
        return part ? part.charAt(0).toUpperCase() + part.slice(1).toLowerCase() : '';
      })
      .join(' ')
      .trim();
  }

  $(document).on('input', '.name-input', function () {
    var cleaned = normalizeNameInput($(this).val());
    if ($(this).val() !== cleaned) {
      $(this).val(cleaned);
    }
  });

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
