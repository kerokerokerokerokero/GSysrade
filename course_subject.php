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
 <?php require_once 'db.php';
if(isset($_GET['id'])){
    $id= $_GET['id'];
    $year= $_GET['year'];
    $result = $conn->query("SELECT * FROM course WHERE id=$id");
    $rows =$result->fetch_assoc();
  }
  ?>
 <h1 class="h3 mb-2 text-gray-800">Course Name:<?=$rows['name'];?></h1>
 <button type="button" class="btn btn-success mb-3" data-toggle="modal" data-target="#addrecord">
  <i class="fas fa-plus"></i> Add Subject
</button>
           

                    <!-- DataTales Example -->
                  

    <div class="card shadow mb-4">
  <div class="card-header bg-primary text-white">
  <h5 class="mb-0">1st Semester Subjects</h5>
  </div>
  <div class="card-body">
  <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
      <table class="table table-bordered table-hover">
        <thead class="thead-light" style="position: sticky; top: 0; background-color: white; z-index: 1;">
          <tr>
            <th>Code</th>
            <th>Description</th>
            <th>Units</th>
           
          </tr>
        </thead>
        <tbody>
        <?php require_once 'db.php';
                $sem = 1;
                $sql = "SELECT * FROM curriculum_subject WHERE course='$id' AND sem='$sem' AND year='$year' ";
                $result = $conn->query($sql);
                ?>
                <?php if ($result && $result->num_rows > 0): ?>
                  <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
        // Get subject details for each faculty_subject row
        $subject_id = $row['subject'];
        $sub = $conn->query("SELECT * FROM subject WHERE id = $subject_id");
        $subject = $sub && $sub->num_rows > 0 ? $sub->fetch_assoc() : null;
        ?>
           <tr>
            <td><?= htmlspecialchars($subject['subject_code'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($subject['des'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($subject['unit'] ?? 'N/A') ?></td>
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

<div class="card shadow mb-4">
  <div class="card-header bg-primary text-white">
    <h5 class="mb-0">2nd Semester Subjects</h5>
  </div>
  <div class="card-body">
  <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
      <table class="table table-bordered table-hover">
        <thead class="thead-light" style="position: sticky; top: 0; background-color: white; z-index: 1;">
          <tr>
            <th>Code</th>
            <th>Description</th>
            <th>Units</th>
           
          </tr>
        </thead>
        <tbody>
        <?php require_once 'db.php';
                $sem = 2;
                $sql = "SELECT * FROM curriculum_subject WHERE course='$id' AND sem='$sem' AND year='$year' ";
                $result = $conn->query($sql);
                ?>
                <?php if ($result && $result->num_rows > 0): ?>
                  <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
        // Get subject details for each faculty_subject row
        $subject_id = $row['subject'];
        $sub = $conn->query("SELECT * FROM subject WHERE id = $subject_id");
        $subject = $sub && $sub->num_rows > 0 ? $sub->fetch_assoc() : null;
        ?>
           <tr>
            <td><?= htmlspecialchars($subject['subject_code'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($subject['des'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($subject['unit'] ?? 'N/A') ?></td>
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

<div class="card shadow mb-5">
  <div class="card-header bg-primary text-white">
    <h5 class="mb-0">Summerss Subjects</h5>
  </div>
  <div class="card-body">
  <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
      <table class="table table-bordered table-hover">
        <thead class="thead-light" style="position: sticky; top: 0; background-color: white; z-index: 1;">
          <tr>
            <th>Code</th>
            <th>Description</th>
            <th>Units</th>
           
          </tr>
        </thead>
        <tbody>
        <?php require_once 'db.php';
                $sem = 'summer';
                $sql = "SELECT * FROM curriculum_subject WHERE course='$id' AND sem='$sem' AND year='$year' ";
                $result = $conn->query($sql);
                ?>
                <?php if ($result && $result->num_rows > 0): ?>
                  <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
        // Get subject details for each faculty_subject row
        $subject_id = $row['subject'];
        $sub = $conn->query("SELECT * FROM subject WHERE id = $subject_id");
        $subject = $sub && $sub->num_rows > 0 ? $sub->fetch_assoc() : null;
        ?>
           <tr>
            <td><?= htmlspecialchars($subject['subject_code'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($subject['des'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($subject['unit'] ?? 'N/A') ?></td>
           </tr>

        <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="5">No subjects assigned.</td></tr>
<?php endif; ?>

         
        </tbody>
      </table>
    </div>
  </div>

  

  

<!--modal for add subh=ject-->
<!-- Add subject Modal -->
<div class="modal fade" id="addrecord" tabindex="-1" role="dialog" aria-labelledby="addCurriculumModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <form action="code/curriculum.php" method="POST">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="addCurriculumModalLabel">Add Subject</h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <!-- Student Information -->
          <h6 class="text-primary mb-3">Subject Information</h6>
          <div class="form-row">
            <div class="form-group col-md-4">
              <label for="firstName">Curriculum:</label>
              <select class="form-control" name="curriculum" id="">
                <?php require_once 'db.php';
                $sql = "SELECT * FROM crurriculum";
                $result = $conn->query($sql);
                ?>
                 <?php if ($result->num_rows > 0): ?>
                  <?php while ($row = $result->fetch_assoc()): ?>
                    <option value="<?=$row['id'];?>"><?=$row['name'];?></option>
                    <?php endwhile;?>
                      <?php endif;?>
                
              </select>
              <input type="text" value="<?=$rows['id'];?>" name="course" hidden>
             
            </div>
            <div class="form-group col-md-4">
              <label for="middleName">Year:</label>
              <input type="text" class="form-control" name="year" value="<?=$year;?>" placeholder="<?=$year;?>" readonly>
              <input type="text" name="course" value="<?=$id;?>" hidden>
            </div>

            
            <div class="form-group col-md-4">
              <label for="dob">Semester:</label>
              <select class="form-control"  name="sem">
                <option disabled  >Select</option>
                <option>1</option>
                <option>2</option>
                <option value="">summer</option>
              </select>
            </div>
          </div>

         
                    <!-- Subject Selection Table -->
            <h6 class="text-primary mt-4 mb-3">Subject Selection</h6>
            <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
            <table class="table table-bordered table-hover">
                <thead class="thead-light" style="position: sticky; top: 0; background-color: white; z-index: 1;">
                <tr>
                    <th>Select</th>
                    <th>Subject Code</th>
                    <th>Subject Name</th>
                    <th>Units</th>
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
          <!-- Example static rows (replace with dynamic content as needed) -->
          <tr>
            <td><?php echo htmlspecialchars($row['subject_code']); ?></td>
            <td><?php echo htmlspecialchars($row['des']); ?></td>
            <td><?php echo htmlspecialchars($row['unit']); ?></td>
            <td> <input type="checkbox" name="subject[]" value="<?= $row['id'] ?>"></td>
          </tr>
          
           <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="3" class="text-center">No curriculum found.</td></tr>
        <?php endif; ?>
                <!-- Add more rows as needed -->
                </tbody>
            </table>
            </div>



         

        <div class="modal-footer">
          <button type="submit" name="subject_curriculum" class="btn btn-success">Add</button>
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

<!-- Page level plugins -->
<script src="vendor/chart.js/Chart.min.js"></script>

<!-- Page level custom scripts -->
<script src="js/demo/chart-area-demo.js"></script>
<script src="js/demo/chart-pie-demo.js"></script>

</body>
</html>
