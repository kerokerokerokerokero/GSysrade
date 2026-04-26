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
<?php require_once 'db.php';
if(isset($_GET['id'])){
    $id= $_GET['id'];
    $result = $conn->query("SELECT * FROM course WHERE id=$id");
    $rows =$result->fetch_assoc();
  }
  ?>
<!-- Begin Page Content -->
<div class="container-fluid">
 <!-- Page Heading -->
 <h1 class="h3 mb-2 text-gray-800">Course: <?=$rows['name'];?></h1>
           

                    <!-- DataTales Example -->
                  

    <div class="card shadow mb-4">
  <div class="card-header bg-primary text-white">
    <h5 class="mb-0">Year Folder</h5>
  </div>
  <div class="card-body">
  <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
      <table class="table table-bordered table-hover">
        <thead class="thead-light" style="position: sticky; top: 0; background-color: white; z-index: 1;">
          <tr>
            <th>Folder #</th>
            <th>Year</th>
            <th>Action</th>
           
          </tr>
        </thead>
        <tbody>
           <tr>
            <td>1</td>
            <td>1st Year</td>
            <td><div class="d-flex justify-content-center gap-2">
            <a href="course_subject.php?id=<?=$rows['id'];?>&year=1" class="btn btn-sm btn-success mr-2" >view</a>
                </div>    
        </td>
           </tr>
           <tr>
            <td>2</td>
            <td>2nd Year</td>
            <td><div class="d-flex justify-content-center gap-2">
            <a href="course_subject.php?id=<?=$rows['id'];?>&year=2" class="btn btn-sm btn-success mr-2" >view</a>
                </div>    
        </td>
           </tr>
           <tr>
            <td>3</td>
            <td>3rd Year</td>
            <td><div class="d-flex justify-content-center gap-2">
            <a href="course_subject.php?id=<?=$rows['id'];?>&year=3" class="btn btn-sm btn-success mr-2" >view</a>
                </div>    
        </td>
           </tr>
           <tr>
            <td>4</td>
            <td>4rth Year</td>
            <td><div class="d-flex justify-content-center gap-2">
            <a href="course_subject.php?id=<?=$rows['id'];?>&year=4" class="btn btn-sm btn-success mr-2" >view</a>
                </div>    
        </td>
           </tr>

         
        </tbody>
      </table>
    </div>
   
  </div>
</div>

<!--modal for add subh=ject-->
<!-- Add subject Modal -->
<div class="modal fade" id="addrecord" tabindex="-1" role="dialog" aria-labelledby="addCurriculumModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <form action="code/student.php" method="POST">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="addCurriculumModalLabel">Add Student</h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <!-- Student Information -->
          <h6 class="text-primary mb-3">Student Folder</h6>
          <div class="form-row">
            <div class="form-group col-md-4">
              <label for="firstName">School Year:</label>
              <select class="form-control" id="schoolYear">
              </select>
            </div>
            <script type="text/javascript">
              let currentYear = new Date().getFullYear();
              let startYear = currentYear - 5;
              let select = document.getElementById('schoolYear');

              for(let i = startYear; i <= currentYear + 5; i++){
                let option = document.createElement('option');
                option.value = '${i}-${i+ 1}';
                option.text = 'School Year ${i}-${i+ 1}';
                select.appendChild(option);
              }
            </script>
            <div class="form-group col-md-4">
              <label for="middleName">Year:</label>
               <select class="form-control" >
                <option>1</option>
                <option>2</option>
                <option>3</option>
                <option>4</option>
            </div>

            <div class="form-group col-md-4">
              <label for="dob">Curriculum:</label>
              <select class="form-control">
                <option>  </option>
              </select>
            </div>
            <div class="form-group col-md-4">
              <label for="dob">Semester:</label>
              <select class="form-control">
                <option disabled  >Select</option>
                <option>1</option>
                <option>2</option>
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
                <!-- Example subjects (replace with dynamic PHP loop) -->
                <tr>
                    <td><input type="checkbox" name="subjects[]" value="MATH101"></td>
                    <td>MATH101</td>
                    <td>Calculus I</td>
                    <td>3</td>
                </tr>
                <tr>
                    <td><input type="checkbox" name="subjects[]" value="ENG102"></td>
                    <td>ENG102</td>
                    <td>English Composition</td>
                    <td>3</td>
                </tr>
                <tr>
                    <td><input type="checkbox" name="subjects[]" value="CS103"></td>
                    <td>CS103</td>
                    <td>Intro to Programming</td>
                    <td>4</td>
                </tr>
                <tr>
                    <td><input type="checkbox" name="subjects[]" value="CS103"></td>
                    <td>CS103</td>
                    <td>Intro to Programming</td>
                    <td>4</td>
                </tr>
                <tr>
                    <td><input type="checkbox" name="subjects[]" value="CS103"></td>
                    <td>CS103</td>
                    <td>Intro to Programming</td>
                    <td>4</td>
                </tr>
                <tr>
                    <td><input type="checkbox" name="subjects[]" value="CS103"></td>
                    <td>CS103</td>
                    <td>Intro to Programming</td>
                    <td>4</td>
                </tr>
                <!-- Add more rows as needed -->
                </tbody>
            </table>
            </div>



         

        <div class="modal-footer">
          <button type="submit" name="create" class="btn btn-success">Add</button>
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
