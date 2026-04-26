<?php
// ================================================================
//  PASTE THIS BLOCK at the very TOP of student_record.php
//  (replace the existing session_start() + require_once 'db.php' lines)
// ================================================================

require_once 'auth.php';   // enforces login for all roles
require_once 'db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Students can ONLY view their own record
require_owns_student($id);

// Faculty and Students cannot delete — hide the delete action below.
// (The delete buttons in student_record.php should already check can_delete())
