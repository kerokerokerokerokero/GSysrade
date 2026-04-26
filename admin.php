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
 <h1 class="h3 mb-2 text-gray-800">SYSTEM ADMIN</h1>
           

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Student List</h6>
                <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#addCurriculumModal">
                    <i class="fas fa-plus"></i> Add Admin
                </a>
    </div>

<!--modal for add subh=ject-->
<!-- Add subject Modal -->
<div class="modal fade" id="addCurriculumModal" tabindex="-1" role="dialog" aria-labelledby="addCurriculumModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <form action="add_curriculum.php" method="POST">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="addCurriculumModalLabel">Add Student</h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <!-- Student Information -->
          <h6 class="text-primary mb-3">Student Information</h6>
          <div class="form-row">
            <div class="form-group col-md-4">
              <label for="firstName">First Name:</label>
              <input type="text" class="form-control" id="firstName" name="first_name" required>
            </div>
            <div class="form-group col-md-4">
              <label for="middleName">Middle Name:</label>
              <input type="text" class="form-control" id="middleName" name="middle_name">
            </div>
            <div class="form-group col-md-4">
              <label for="lastName">Last Name:</label>
              <input type="text" class="form-control" id="lastName" name="last_name" required>
            </div>
            <div class="form-group col-md-4">
              <label for="dob">Date of Birth:</label>
              <input type="date" class="form-control" id="dob" name="dob" required>
            </div>
          </div>
  
          <!-- Contact Information -->
          <h6 class="text-primary mt-4 mb-3">Contact Information</h6>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="email">Email:</label>
              <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="form-group col-md-6">
              <label for="phone">Phone Number:</label>
              <input type="tel" class="form-control" id="phone" name="phone" required>
            </div>
            <div class="form-group col-md-12">
              <label for="address">Address:</label>
              <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
            </div>
          </div>

          <!-- Account Information -->
          <h6 class="text-primary mt-4 mb-3">Account Information</h6>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="username">Username:</label>
              <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="form-group col-md-6">
              <label for="password">Password:</label>
              <input type="password" class="form-control" id="password" name="password" required>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Add</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>


<!--edit subject-->
<div class="modal fade" id="EditsubjectModal" tabindex="-1" role="dialog" aria-labelledby="editCurriculumModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <form action="add_curriculum.php" method="POST">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="editCurriculumModalLabel">Edit Student</h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <!-- Student Information -->
          <h6 class="text-primary mb-3">Accountt Information</h6>
          <div class="form-row">
            <div class="form-group col-md-4">
              <label for="firstName">First Name:</label>
              <input type="text" class="form-control" id="firstName-edit" name="first_name" required>
            </div>
            <div class="form-group col-md-4">
              <label for="middleName">Middle Name:</label>
              <input type="text" class="form-control" id="middleName-edit" name="middle_name">
            </div>
            <div class="form-group col-md-4">
              <label for="lastName">Last Name:</label>
              <input type="text" class="form-control" id="lastName-edit" name="last_name" required>
            </div>
          </div>

          <!-- Contact Information -->
          <h6 class="text-primary mt-4 mb-3">Contact Information</h6>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="email">Email:</label>
              <input type="email" class="form-control" id="email-edit" name="email" required>
            </div>
            <div class="form-group col-md-6">
              <label for="phone">Phone Number:</label>
              <input type="tel" class="form-control" id="phone-edit" name="phone" required>
            </div>
            <div class="form-group col-md-12">
              <label for="address">Address:</label>
              <textarea class="form-control" id="address" name="address-edit" rows="2" required></textarea>
            </div>
          </div>

          <!-- Account Information -->
          <h6 class="text-primary mt-4 mb-3">Account Settings</h6>
            <div class="form-row">
            <div class="form-group col-md-6">
                <label for="username">Username:</label>
                <input type="text" class="form-control" id="username-edit" name="username" required>
            </div>
            </div>

            <div class="form-row">
            <div class="form-group col-md-12">
                <small class="form-text text-muted mb-2">
                To change your password, fill out the fields below. Leave them blank if no changes are needed.
                </small>
            </div>

            <div class="form-group col-md-4">
                <label for="currentPassword">Current Password:</label>
                <input type="password" class="form-control" id="currentPassword-edit" name="current_password">
            </div>

            <div class="form-group col-md-4" id="newPasswordGroup-edit" style="display: none;">
                <label for="newPassword">New Password:</label>
                <input type="password" class="form-control" id="newPassword" name="new_password">
            </div>

            <div class="form-group col-md-4" id="confirmPasswordGroup-edit" style="display: none;">
                <label for="confirmPassword">Confirm New Password:</label>
                <input type="password" class="form-control" id="confirmPassword" name="confirm_password">
            </div>
            </div>

          
           

        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Save</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>
</div>


    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>User Name</th>
                        <th>Status</th>
                        <th class="d-flex justify-content-center  gap-2">Action</th>
                    </tr>
                </thead>
             
                <tbody>
                    <tr>
                        <td>Zaeed N. Cervantes</td>
                        <td style="color:green;"><b>Active</b></td>
                        <td class="text-center">
                        <div class="d-flex justify-content-center gap-2">
                            <a href="#" class="btn btn-sm btn-primary mr-2" data-toggle="modal" data-target="#EditsubjectModal">Edit</a>
                            <a href="delete.php?id=1" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                        </div>
                    </td>
                    </tr>
                    <!-- More rows here -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation example">
            <ul class="pagination justify-content-end mt-3">
                <li class="page-item"><a class="page-link" href="?page=1">Previous</a></li>
                <li class="page-item"><a class="page-link" href="?page=1">1</a></li>
                <li class="page-item"><a class="page-link" href="?page=2">2</a></li>
                <li class="page-item"><a class="page-link" href="?page=3">3</a></li>
                <li class="page-item"><a class="page-link" href="?page=2">Next</a></li>
            </ul>
        </nav>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
  $('#EditsubjectModal').on('shown.bs.modal', function () {
    const currentPassword = document.querySelector('#currentPassword-edit');
    const newPasswordGroup = document.querySelector('#newPasswordGroup-edit');
    const confirmPasswordGroup = document.querySelector('#confirmPasswordGroup-edit');

    function togglePasswordFields() {
      const hasValue = currentPassword.value.trim() !== '';
      newPasswordGroup.style.display = hasValue ? 'block' : 'none';
      confirmPasswordGroup.style.display = hasValue ? 'block' : 'none';
    }

    currentPassword.addEventListener('input', togglePasswordFields);
    togglePasswordFields();
  });
});
</script>

</body>
</html>
