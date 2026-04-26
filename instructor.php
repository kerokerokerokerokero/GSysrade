<?php   
include 'layouts/header.php';
?>
<body id="page-top">

<!-- Page Wrapper -->
<div id="wrapper">

    <!-- Sidebar -->
    <?php   
include 'layouts/sidebar.php';
?>
    <!-- End of Sidebar -->

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

        <!-- Main Content -->
        <div id="content">

<!-- Topbar -->
<?php include 'layouts/topbar.php'; ?>
<!-- End of Topbar -->

<!-- Begin Page Content -->
<div class="container-fluid">
 <!-- Page Heading -->
 <h1 class="h3 mb-2 text-gray-800">Faculty Members</h1>
           

                    <!-- DataTales Example -->
                  
<?php require_once 'db.php';
if(isset($_GET['id'])){
    $id= $_GET['id'];
    $result = $conn->query("SELECT * FROM faculty WHERE id=$id");
    $rows =$result->fetch_assoc();
  }
  ?>
    <div class="card shadow mb-4">
  <div class="card-header bg-primary text-white">
    <h5 class="mb-0">Instructor Information</h5>
  </div>
  <div class="card-body">
    <!-- Instructor Info -->
    <div class="form-row">
      <div class="form-group col-md-4">
        <label for="instructorFirstName">First Name:</label>
        <input type="text" class="form-control" id="instructorFirstName" name="instructor_first_name" placeholder="<?=$rows['first_name'];?>" readonly>
      </div>
      <div class="form-group col-md-4">
        <label for="instructorMiddleName">Middle Name:</label>
        <input type="text" class="form-control" id="instructorMiddleName" name="instructor_middle_name" placeholder="<?=$rows['middle_name'];?>" readonly>
      </div>
      <div class="form-group col-md-4">
        <label for="instructorLastName">Last Name:</label>
        <input type="text" class="form-control" id="instructorLastName" name="instructor_last_name" placeholder="<?=$rows['last_name'];?>" readonly>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="instructorEmail">Email:</label>
        <input type="email" class="form-control" id="instructorEmail" name="instructor_email" placeholder="<?=$rows['email'];?>" readonly>
      </div>
      <div class="form-group col-md-6">
        <label for="instructorPhone">Phone Number:</label>
        <input type="tel" class="form-control" id="instructorPhone" name="instructor_phone" placeholder="<?=$rows['phone_number'];?>" readonly>
      </div>
    </div>

    <!-- Assigned Subjects Table -->
    <h6 class="text-primary mt-4 mb-3">Assigned Subjects</h6>
    <button type="button" class="btn btn-success mb-3" data-toggle="modal" data-target="#addSubjectModal">
  <i class="fas fa-plus"></i> Assign Subject
</button>
    <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
      <table class="table table-bordered table-hover">
        <thead class="thead-light" style="position: sticky; top: 0; background-color: white; z-index: 1;">
          <tr>
            <th>Subject Code</th>
            <th>Subject Name</th>
            <th>Units</th>
            <th>Schedule</th>
            <th class="d-flex justify-content-center gap-2">Action</th>
          </tr>
        </thead>
        <tbody>
             <?php 
require_once 'db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = $conn->query("SELECT * FROM faculty_subject WHERE faculty_id = $id");
}
?>
<?php if ($result && $result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <?php
        // Get subject details for each faculty_subject row
        $subject_id = $row['id_subject'];
        $sub = $conn->query("SELECT * FROM subject WHERE id = $subject_id");
        $subject = $sub && $sub->num_rows > 0 ? $sub->fetch_assoc() : null;
        $scheduleTime = DateTime::createFromFormat('H:i', $row['time']);
        $formattedTime = $scheduleTime ? $scheduleTime->format('g:i A') : $row['time'];
        ?>
        <tr>
            <td><?= htmlspecialchars($subject['subject_code'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($subject['des'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($subject['unit'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($row['day'] . ' ' . $formattedTime) ?></td>
            <td class="d-flex justify-content-center gap-2">
                <a href="subject_student.php?faculty_subject_id=<?= $row['id'] ?>&subject_id=<?= $row['id_subject'] ?>&faculty_id=<?= $id ?>" class="btn btn-sm btn-success mr-2">View</a>
                <a href="delete.php?type=faculty_subject&id=<?= $row['id']; ?>&instructor=<?= $id; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this assignment?')">Delete</a>
            </td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="5">No subjects assigned.</td></tr>
<?php endif; ?>

         
        </tbody>
      </table>
    </div>
  </div>
</div>

<!--modal for add subh=ject-->
<!-- Add subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" role="dialog" aria-labelledby="addCurriculumModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form action="code/subject.php" method="POST">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="addCurriculumModalLabel">Assign Subject</h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        
        <div class="modal-body">
          <h6 class="text-primary mb-3">Assign Subject</h6>
          <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
            <input type="hidden" name="id" value="<?= intval($rows['id']); ?>">
            <div class="form-group mb-3">
              <label for="subjectSearch">Search subjects:</label>
              <input type="text" class="form-control" id="subjectSearch" placeholder="Search subject code or name...">
            </div>
      <table id="subjectTable" class="table table-bordered table-hover">
        <thead class="thead-light" style="position: sticky; top: 0; background-color: white; z-index: 1;">
          <tr>
            <th>Subject Code</th>
            <th>Subject Name</th>
            <th>Units</th>
            <th class="d-flex justify-content-center gap-2">Action</th>
          </tr>
        </thead>
        <tbody>
              <?php 
require_once 'db.php';

// ✅ Corrected table name from 'crurriculum' to 'curriculum'
$sql = "SELECT * FROM subject";
$result = $conn->query($sql);
?>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?php echo htmlspecialchars($row['subject_code']); ?></td>
            <td><?php echo htmlspecialchars($row['des']); ?></td>
            <td><?php echo htmlspecialchars($row['unit']); ?></td>
            <td class="text-center">
              <input type="radio" name="subject" value="<?= $row['id'] ?>" required>
            </td>
          </tr>
          
           <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="3" class="text-center">No curriculum found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
        
          <h6 class="text-primary mt-4 mb-3">Subject Schedule</h6>
          <div class="form-group">
            <label for="day">Day:</label>
            <select class="form-control" id="day" name="day" required>
              <option value="" disabled selected>— Select Day —</option>
              <option value="Monday">Monday</option>
              <option value="Tuesday">Tuesday</option>
              <option value="Wednesday">Wednesday</option>
              <option value="Thursday">Thursday</option>
              <option value="Friday">Friday</option>
              <option value="Saturday">Saturday</option>
              <option value="Sunday">Sunday</option>
            </select>
          </div>
          <div class="form-group">
            <label for="time">Time:</label>
            <input type="time" class="form-control" id="time" name="time" required>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="submit" name="faculty_subject"  class="btn btn-success">Save</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

</div>         
                <!-- /.container-fluid -->

            </div>
        <!-- End of Main Content -->

        <!-- Footer -->
        <?php include 'layouts/footer.php';?>
        <!-- End of Footer -->

    </div>
    <!-- End of Content Wrapper -->

</div>
<!-- End of Page Wrapper -->

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Logout Modal-->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                <a class="btn btn-primary" href="login.php">Logout</a>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap core JavaScript-->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>

<!-- Custom scripts for all pages-->
<script src="js/sb-admin-2.min.js"></script>
<script>
$(document).ready(function() {
  $('#subjectSearch').on('input', function () {
    var query = $(this).val().toLowerCase();
    $('#subjectTable tbody tr').each(function () {
      var code = $(this).find('td').eq(0).text().toLowerCase();
      var name = $(this).find('td').eq(1).text().toLowerCase();
      var units = $(this).find('td').eq(2).text().toLowerCase();
      var visible = code.indexOf(query) !== -1 || name.indexOf(query) !== -1 || units.indexOf(query) !== -1;
      $(this).toggle(visible);
    });
  });
});
</script>

<!-- Page level plugins -->
<script src="vendor/chart.js/Chart.min.js"></script>

<!-- Page level custom scripts -->
<script src="js/demo/chart-area-demo.js"></script>
<script src="js/demo/chart-pie-demo.js"></script>

</body>
</html>
