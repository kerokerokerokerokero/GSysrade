<?php
session_start();
require_once 'db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ═══════════════════════════════════════════
// HANDLE SUBJECT ROW DELETE (AJAX)
// ═══════════════════════════════════════════
if (isset($_POST['action']) && $_POST['action'] === 'delete_subject') {
    header('Content-Type: application/json');
    $row_id = intval($_POST['row_id'] ?? 0);
    if ($row_id > 0) {
        $record_id = null;
        $res = $conn->query("SELECT record_id FROM student_record_subject WHERE id = $row_id LIMIT 1");
        if ($res && $res->num_rows > 0) {
            $record_id = intval($res->fetch_assoc()['record_id']);
        }
        $del = $conn->query("DELETE FROM student_record_subject WHERE id = $row_id");
        if ($del) {
            $record_deleted = false;
            if ($record_id) {
                $remaining = $conn->query("SELECT 1 FROM student_record_subject WHERE record_id = $record_id LIMIT 1");
                if (!$remaining || $remaining->num_rows === 0) {
                    $conn->query("DELETE FROM student_record WHERE id = $record_id");
                    $record_deleted = true;
                }
            }
            echo json_encode(['success' => true, 'record_deleted' => $record_deleted]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid row ID']);
    }
    exit;
}

// ═══════════════════════════════════════════
// HANDLE FORM SAVE — subjects only, no grades
// Grades are entered via subject_student.php
// ═══════════════════════════════════════════
if (isset($_POST['create_record'])) {
    $student_id    = intval($_POST['student_id']);
    $school_year   = $conn->real_escape_string($_POST['school_year']);
    $year_level    = intval($_POST['year_level']);
    $semester      = intval($_POST['semester']);
    $curriculum_id = !empty($_POST['curriculum_id']) ? intval($_POST['curriculum_id']) : 'NULL';

    $ins = $conn->query("
        INSERT INTO student_record (student_id, school_year, year_level, semester, curriculum_id)
        VALUES ($student_id, '$school_year', $year_level, $semester, $curriculum_id)
    ");

    if ($ins) {
        $record_id   = $conn->insert_id;
        $subject_ids = isset($_POST['subject_ids']) ? $_POST['subject_ids'] : [];

        foreach ($subject_ids as $sid) {
            $sid = intval($sid);
            if ($sid > 0) {
                // Insert with NULL grades — grades are entered by the instructor in subject_student.php
                $conn->query("
                    INSERT INTO student_record_subject
                        (record_id, subject_id, pre_mid, midterm, pre_final, final, final_grade, remarks)
                    VALUES
                        ($record_id, $sid, NULL, NULL, NULL, NULL, NULL, NULL)
                ");
            }
        }
        $_SESSION['success_msg'] = 'Record created. Grades will be entered by the instructor.';
    } else {
        $_SESSION['error_msg'] = 'Failed to save record: ' . $conn->error;
    }

    header("Location: student_record.php?id=$student_id");
    exit;
}

// ═══════════════════════════════════════════
// FETCH STUDENT INFO
// ═══════════════════════════════════════════
$result = $conn->query("
    SELECT s.*, c.code AS course_code, c.name AS course_name, cr.name AS curriculum_name
    FROM student s
    LEFT JOIN course c      ON c.id  = s.course
    LEFT JOIN crurriculum cr ON cr.id = s.curriculum
    WHERE s.id = $id
");
$rows       = $result ? $result->fetch_assoc() : null;
$year_labels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'];

// ═══════════════════════════════════════════
// FETCH SAVED RECORDS + SUBJECTS + GRADES
// ═══════════════════════════════════════════
$records_sql = "
  SELECT
    sr.id           AS record_id,
    sr.school_year,
    sr.year_level,
    sr.semester,
    sr.curriculum_id,
    c.name          AS curriculum_name,
    srs.id          AS row_id,
    srs.subject_id,
    srs.pre_mid,
    srs.midterm,
    srs.pre_final,
    srs.final,
    srs.final_grade,
    srs.remarks,
    s.subject_code,
    s.des           AS subject_name,
    s.unit
  FROM student_record sr
  LEFT JOIN crurriculum c              ON c.id   = sr.curriculum_id
  LEFT JOIN student_record_subject srs ON srs.record_id = sr.id
  LEFT JOIN subject s                  ON s.id   = srs.subject_id
  WHERE sr.student_id = $id
  ORDER BY sr.school_year ASC, sr.year_level ASC, sr.semester ASC, s.subject_code ASC
";
$records_result = $conn->query($records_sql);

$grouped = [];
if ($records_result && $records_result->num_rows > 0) {
    while ($rec = $records_result->fetch_assoc()) {
        $key = $rec['school_year'] . '||' . $rec['year_level'] . '||' . $rec['semester'];
        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'group_key'       => $key,
                'school_year'     => $rec['school_year'],
                'year_level'      => $rec['year_level'],
                'semester'        => $rec['semester'],
                'curriculum_name' => $rec['curriculum_name'],
                'subjects'        => [],
            ];
        }
        if ($rec['row_id']) {
            $existing = array_column($grouped[$key]['subjects'], 'row_id');
            if (!in_array($rec['row_id'], $existing)) {
                $grouped[$key]['subjects'][] = [
                    'row_id'       => $rec['row_id'],
                    'subject_code' => $rec['subject_code'],
                    'subject_name' => $rec['subject_name'],
                    'unit'         => $rec['unit'],
                    'pre_mid'      => $rec['pre_mid'],
                    'midterm'      => $rec['midterm'],
                    'pre_final'    => $rec['pre_final'],
                    'final'        => $rec['final'],
                    'final_grade'  => $rec['final_grade'],
                    'remarks'      => $rec['remarks'],
                ];
            }
        }
    }
    foreach ($grouped as &$grp) {
        usort($grp['subjects'], fn($a, $b) => strcmp($a['subject_code'], $b['subject_code']));
    }
    unset($grp);
}

include 'layouts/header.php';
?>
<body id="page-top">
<div id="wrapper">

    <?php include 'layouts/sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <?php include 'layouts/topbar.php'; ?>

            <div class="container-fluid">
              <h1 class="h3 mb-2 text-gray-800">
                Student File
                <?php if (!empty($rows['fname'])): ?>
                  &mdash; <span class="text-primary"><?= htmlspecialchars($rows['fname']); ?></span>
                <?php endif; ?>
              </h1>

              <!-- Flash messages -->
              <?php if (isset($_SESSION['success_msg'])): ?>
              <div class="alert alert-success alert-dismissible fade show shadow-sm">
                <i class="fas fa-check-circle mr-2"></i>
                <?= htmlspecialchars($_SESSION['success_msg']); unset($_SESSION['success_msg']); ?>
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
              </div>
              <?php endif; ?>
              <?php if (isset($_SESSION['error_msg'])): ?>
              <div class="alert alert-danger alert-dismissible fade show shadow-sm">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?= htmlspecialchars($_SESSION['error_msg']); unset($_SESSION['error_msg']); ?>
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
              </div>
              <?php endif; ?>

<!-- ══════════════════════════════════════════
     STUDENT INFORMATION CARD
     ══════════════════════════════════════════ -->
<div class="card shadow mb-4">
  <div class="card-header bg-primary text-white">
    <h5 class="mb-0">Student Information</h5>
  </div>
  <div class="card-body">
    <div class="form-row">
      <div class="form-group col-md-4">
        <label>First Name:</label>
        <input type="text" class="form-control" placeholder="<?= htmlspecialchars($rows['fname'] ?? ''); ?>" readonly>
      </div>
      <div class="form-group col-md-4">
        <label>Middle Name:</label>
        <input type="text" class="form-control" placeholder="<?= htmlspecialchars($rows['mname'] ?? ''); ?>" readonly>
      </div>
      <div class="form-group col-md-4">
        <label>Last Name:</label>
        <input type="text" class="form-control" placeholder="<?= htmlspecialchars($rows['lname'] ?? ''); ?>" readonly>
      </div>
      <div class="form-group col-md-4">
        <label>Date of Birth:</label>
        <input type="text" class="form-control" placeholder="<?= htmlspecialchars($rows['dob'] ?? ''); ?>" readonly>
      </div>
      <div class="form-group col-md-4">
        <label>Course:</label>
        <input type="text" class="form-control" placeholder="<?= htmlspecialchars($rows['course_code'] ?? $rows['course'] ?? 'N/A'); ?>" readonly>
      </div>
      <div class="form-group col-md-4">
        <label>Year:</label>
        <input type="text" class="form-control" placeholder="<?= htmlspecialchars($year_labels[intval($rows['year'])] ?? $rows['year'] ?? 'N/A'); ?>" readonly>
      </div>
      <div class="form-group col-md-6">
        <label>Email:</label>
        <input type="email" class="form-control" placeholder="<?= htmlspecialchars($rows['email'] ?? ''); ?>" readonly>
      </div>
      <div class="form-group col-md-6">
        <label>Phone Number:</label>
        <input type="tel" class="form-control" placeholder="<?= htmlspecialchars($rows['phone'] ?? ''); ?>" readonly>
      </div>
    </div>

    <!-- ══════════════════════════════════════
         STUDENT RECORD SECTION
         ══════════════════════════════════════ -->
    <div class="card-header bg-primary text-white mt-3">
      <h5 class="mb-0">
        <i class="fas fa-folder-open mr-2"></i>Student Record
        <?php if (!empty($rows['fname'])): ?>
          &mdash; <?= htmlspecialchars($rows['fname']); ?>
        <?php endif; ?>
      </h5>
    </div>

    <div class="mt-3 mb-3 d-flex align-items-center">
      <a href="print.php?student_id=<?= $id ?>&autoprint=1" class="btn btn-info mr-2" target="_blank">
        <i class="fas fa-print mr-1"></i> Print Record
      </a>
      <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addrecord">
        <i class="fas fa-folder-plus mr-1"></i> Create Record
      </button>
    </div>

    <!-- ── Saved records ── -->
    <?php if (empty($grouped)): ?>
      <div class="alert alert-info">
        <i class="fas fa-info-circle mr-1"></i>
        No records found. Click <strong>Create Record</strong> to add one.
      </div>
    <?php else: ?>
      <?php foreach ($grouped as $record): ?>

        <div class="card border-0 shadow-sm mb-4 record-group-card"
             data-group-key="<?= htmlspecialchars($record['group_key']); ?>">
          <!-- Record header -->
          <div class="card-header d-flex justify-content-between align-items-center py-2"
               style="background-color:#eaf0fb; border-left:4px solid #4e73df;">
            <div>
              <i class="fas fa-folder-open mr-2 text-primary"></i>
              <span class="badge badge-primary mr-1">SY <?= htmlspecialchars($record['school_year']); ?></span>
              <span class="badge badge-secondary mr-1">Year <?= htmlspecialchars($record['year_level']); ?></span>
              <span class="badge badge-info mr-1">
                <?= $record['semester'] == 1 ? '1st Semester' : '2nd Semester'; ?>
              </span>
              <?php if ($record['curriculum_name']): ?>
                <span class="badge badge-light border"><?= htmlspecialchars($record['curriculum_name']); ?></span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Subjects + Grades table -->
          <div class="card-body p-0">
            <?php if (empty($record['subjects'])): ?>
              <p class="text-muted text-center py-3 mb-0">No subjects in this record.</p>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" style="font-size:0.9rem;">
                  <thead style="background-color:#4e73df; color:#fff;">
                    <tr>
                      <th class="text-center" style="width:40px;">#</th>
                      <th style="width:120px;">Subject Code</th>
                      <th>Subject Name</th>
                      <th class="text-center" style="width:60px;">Units</th>
                      <th class="text-center" style="width:90px;">Pre-Mid</th>
                      <th class="text-center" style="width:90px;">Midterm</th>
                      <th class="text-center" style="width:90px;">Pre-Final</th>
                      <th class="text-center" style="width:90px;">Final</th>
                      <th class="text-center" style="width:90px;">Final Grade</th>
                      <th class="text-center" style="width:110px;">Remarks</th>
                      <th class="text-center" style="width:80px;">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $n = 1; foreach ($record['subjects'] as $subj): ?>
                    <tr>
                      <td class="text-center"><?= $n++; ?></td>
                      <td><?= htmlspecialchars($subj['subject_code']); ?></td>
                      <td><?= htmlspecialchars($subj['subject_name']); ?></td>
                      <td class="text-center"><?= htmlspecialchars($subj['unit']); ?></td>
                      <td class="text-center"><?= $subj['pre_mid']   !== null && $subj['pre_mid']   !== '' ? number_format((float)$subj['pre_mid'],   2) : '—'; ?></td>
                      <td class="text-center"><?= $subj['midterm']   !== null && $subj['midterm']   !== '' ? number_format((float)$subj['midterm'],   2) : '—'; ?></td>
                      <td class="text-center"><?= $subj['pre_final'] !== null && $subj['pre_final'] !== '' ? number_format((float)$subj['pre_final'], 2) : '—'; ?></td>
                      <td class="text-center"><?= $subj['final']     !== null && $subj['final']     !== '' ? number_format((float)$subj['final'],     2) : '—'; ?></td>
                      <td class="text-center">
                        <?php if ($subj['final_grade'] !== null && $subj['final_grade'] !== ''): ?>
                          <span class="badge px-2 py-1 <?= $subj['final_grade'] >= 75 ? 'badge-success' : 'badge-danger'; ?>">
                            <?= number_format((float)$subj['final_grade'], 2); ?>
                          </span>
                        <?php else: ?>
                          <span class="text-muted">—</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-center">
                        <?php
                          $rk = $subj['remarks'] ?? '';
                          $rk_cls = match($rk) {
                              'Passed' => 'badge badge-success px-2 py-1',
                              'Failed' => 'badge badge-danger px-2 py-1',
                              'INC'    => 'badge badge-warning text-dark px-2 py-1',
                              default  => '',
                          };
                        ?>
                        <?php if ($rk_cls): ?>
                          <span class="<?= $rk_cls ?>"><?= htmlspecialchars($rk); ?></span>
                        <?php else: ?>
                          <span class="text-muted"><?= $rk ?: '—'; ?></span>
                        <?php endif; ?>
                      </td>
                      <td class="text-center">
                        <button type="button"
                          class="btn btn-sm btn-danger deleteSubjectRow"
                          data-row-id="<?= $subj['row_id']; ?>"
                          data-student-id="<?= $id; ?>"
                          data-group-key="<?= htmlspecialchars($record['group_key']); ?>"
                          title="Delete this subject">
                          <i class="fas fa-trash-alt"></i>
                        </button>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                  <tfoot style="background-color:#f0f4ff;">
                    <tr>
                      <td colspan="3" class="text-right font-weight-bold pr-3">Total Units:</td>
                      <td class="text-center font-weight-bold">
                        <?= array_sum(array_column($record['subjects'], 'unit')); ?>
                      </td>
                      <td colspan="7"></td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>

      <?php endforeach; ?>
    <?php endif; ?>

  </div><!-- end card-body -->
</div><!-- end student info card -->


<!-- ══════════════════════════════════════════
     CREATE RECORD MODAL
     ══════════════════════════════════════════ -->
<div class="modal fade" id="addrecord" tabindex="-1" role="dialog" aria-labelledby="addRecordModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form action="student_record.php?id=<?= $id; ?>" method="POST">
      <input type="hidden" name="student_id" value="<?= $id; ?>">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="addRecordModalLabel">
            <i class="fas fa-folder-plus mr-2"></i>Create Student Record
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">

          <!-- Folder Details -->
          <h6 class="text-primary mb-3">Folder Details</h6>
          <div class="form-row">
            <div class="form-group col-md-3">
              <label>School Year: <span class="text-danger">*</span></label>
              <select class="form-control" id="schoolYear" name="school_year" required>
                <option value="" disabled selected>Select</option>
              </select>
              <script>
                (function(){
                  const sel = document.getElementById('schoolYear');
                  for(let i = 2025; i <= 2035; i++){
                    const o = document.createElement('option');
                    o.value = `${i}-${i+1}`;
                    o.text  = `SY ${i}-${i+1}`;
                    sel.appendChild(o);
                  }
                })();
              </script>
            </div>
            <div class="form-group col-md-3">
              <label>Year Level: <span class="text-danger">*</span></label>
              <select class="form-control" name="year_level" required>
                <option value="" disabled selected>Select</option>
                <option value="1">Year 1</option>
                <option value="2">Year 2</option>
                <option value="3">Year 3</option>
                <option value="4">Year 4</option>
              </select>
            </div>
            <div class="form-group col-md-3">
              <label>Curriculum: <span class="text-danger">*</span></label>
              <select class="form-control" name="curriculum_id" required>
                <option value="" disabled selected>Select</option>
                <?php
                $res_cur = $conn->query("SELECT * FROM crurriculum");
                if ($res_cur && $res_cur->num_rows > 0):
                  while ($cur = $res_cur->fetch_assoc()): ?>
                  <option value="<?= $cur['id']; ?>"><?= htmlspecialchars($cur['name']); ?></option>
                <?php endwhile; else: ?>
                  <option value="">No curriculum</option>
                <?php endif; ?>
              </select>
            </div>
            <div class="form-group col-md-3">
              <label>Semester: <span class="text-danger">*</span></label>
              <select class="form-control" name="semester" required>
                <option value="" disabled selected>Select</option>
                <option value="1">1st Semester</option>
                <option value="2">2nd Semester</option>
              </select>
            </div>
          </div>

          <hr class="my-3">

          <!-- Subject Selection -->
          <h6 class="text-primary mb-1">Subject Selection</h6>
          <p class="text-muted small mb-2">
            <i class="fas fa-check-square mr-1"></i>
            Check subjects to enroll. Grades will be entered by the instructor.
          </p>
          <div class="table-responsive" style="max-height:220px; overflow-y:auto; border:1px solid #dee2e6; border-radius:4px;">
            <table class="table table-bordered table-hover table-sm mb-0">
              <thead class="thead-light" style="position:sticky; top:0; z-index:1;">
                <tr>
                  <th class="text-center" style="width:55px;">Add</th>
                  <th>Subject Code</th>
                  <th>Subject Name</th>
                  <th class="text-center" style="width:70px;">Units</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $sql_s = "SELECT * FROM subject ORDER BY subject_code ASC";
                $res_s = $conn->query($sql_s);
                if ($res_s && $res_s->num_rows > 0):
                  while ($subj = $res_s->fetch_assoc()):
                    $sid   = (int)$subj['id'];
                    $scode = htmlspecialchars($subj['subject_code']);
                    $sname = htmlspecialchars($subj['des']);
                    $sunit = htmlspecialchars($subj['unit']);
                ?>
                <tr>
                  <td class="text-center">
                    <input type="checkbox" class="subjectCheck"
                      data-id="<?= $sid ?>"
                      data-code="<?= $scode ?>"
                      data-name="<?= $sname ?>"
                      data-unit="<?= $sunit ?>">
                  </td>
                  <td><?= $scode ?></td>
                  <td><?= $sname ?></td>
                  <td class="text-center"><?= $sunit ?></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="4" class="text-center text-muted py-3">No subjects found.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Added Subjects (no grade inputs — grades come from instructor via subject_student.php) -->
          <h6 class="text-primary mt-4 mb-1">Selected Subjects</h6>
          <p class="text-muted small mb-2">
            <i class="fas fa-info-circle mr-1"></i>
            Grades are entered by the assigned instructor. They will appear here once saved.
          </p>
          <div class="table-responsive" style="max-height:200px; overflow-y:auto; border:1px solid #dee2e6; border-radius:4px;">
            <table class="table table-bordered table-sm mb-0">
              <thead style="position:sticky; top:0; background-color:#4e73df; color:#fff; z-index:1;">
                <tr>
                  <th class="text-center" style="width:40px;">#</th>
                  <th>Subject Code</th>
                  <th>Subject Name</th>
                  <th class="text-center" style="width:70px;">Units</th>
                  <th class="text-center" style="width:70px;">Remove</th>
                </tr>
              </thead>
              <tbody id="addedSubjectsBody">
                <tr id="noSubjectsRow">
                  <td colspan="5" class="text-center text-muted py-3">
                    <i class="fas fa-arrow-up mr-1"></i>
                    No subjects selected yet — check subjects in the table above.
                  </td>
                </tr>
              </tbody>
              <tfoot id="addedSubjectsFoot" style="display:none; background-color:#f0f4ff;">
                <tr>
                  <td colspan="3" class="text-right font-weight-bold">Total Units:</td>
                  <td class="text-center font-weight-bold" id="totalUnits">0</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>

        </div><!-- end modal-body -->

        <div class="modal-footer">
          <button type="submit" name="create_record" class="btn btn-success">
            <i class="fas fa-save mr-1"></i> Save Record
          </button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fas fa-times mr-1"></i> Cancel
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

            </div><!-- /.container-fluid -->
        </div><!-- End Main Content -->

        <?php include 'layouts/footer.php'; ?>
    </div>
</div>

<a class="scroll-to-top rounded" href="#page-top">
  <i class="fas fa-angle-up"></i>
</a>

<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Ready to Leave?</h5>
        <button class="close" type="button" data-dismiss="modal"><span>&times;</span></button>
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

  // ── Checkbox: add/remove subject from selected list ──
  $(document).on('change', '.subjectCheck', function () {
    const checked = $(this).is(':checked');
    const id      = $(this).data('id');
    const code    = $(this).data('code');
    const name    = $(this).data('name');
    const unit    = parseInt($(this).data('unit')) || 0;

    if (checked) {
      $('#noSubjectsRow').hide();
      $('#addedSubjectsFoot').show();

      const row = `
        <tr id="added-${id}" data-unit="${unit}">
          <td class="text-center row-num"></td>
          <td>
            ${code}
            <input type="hidden" name="subject_ids[]" value="${id}">
          </td>
          <td>${name}</td>
          <td class="text-center">${unit}</td>
          <td class="text-center">
            <button type="button" class="btn btn-sm btn-danger removeSubject" data-id="${id}">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>`;

      $('#addedSubjectsBody').append(row);
      reNumber();
      updateTotal();

    } else {
      $(`#added-${id}`).remove();
      reNumber();
      updateTotal();
      checkEmpty();
    }
  });

  // ── Remove button inside selected list ──
  $(document).on('click', '.removeSubject', function () {
    const id = $(this).data('id');
    $(`.subjectCheck[data-id="${id}"]`).prop('checked', false);
    $(`#added-${id}`).remove();
    reNumber();
    updateTotal();
    checkEmpty();
  });

  function reNumber() {
    $('#addedSubjectsBody tr[id^="added-"] .row-num').each(function (i) {
      $(this).text(i + 1);
    });
  }

  function updateTotal() {
    let total = 0;
    $('#addedSubjectsBody tr[id^="added-"]').each(function () {
      total += parseInt($(this).data('unit')) || 0;
    });
    $('#totalUnits').text(total);
  }

  function checkEmpty() {
    if ($('#addedSubjectsBody tr[id^="added-"]').length === 0) {
      $('#noSubjectsRow').show();
      $('#addedSubjectsFoot').hide();
    }
  }

  // ── Reset modal on close ──
  $('#addrecord').on('hidden.bs.modal', function () {
    $('.subjectCheck').prop('checked', false);
    $('#addedSubjectsBody').empty().append(`
      <tr id="noSubjectsRow">
        <td colspan="5" class="text-center text-muted py-3">
          <i class="fas fa-arrow-up mr-1"></i>
          No subjects selected yet — check subjects in the table above.
        </td>
      </tr>`);
    $('#addedSubjectsFoot').hide();
    $('#totalUnits').text('0');
  });

  // ── AJAX: Delete a single subject row from a saved record ──
  $(document).on('click', '.deleteSubjectRow', function () {
    const $btn      = $(this);
    const rowId     = $btn.data('row-id');
    const studentId = $btn.data('student-id');
    const groupKey  = $btn.data('group-key');
    const $tr       = $btn.closest('tr');
    const $tbody    = $tr.closest('tbody');
    const $tfoot    = $tr.closest('table').find('tfoot');
    const $card     = $('[data-group-key="' + groupKey + '"]');

    if (!confirm('Remove this subject from the record?')) return;
    $btn.prop('disabled', true);

    $.post(
      window.location.pathname + '?id=' + studentId,
      { action: 'delete_subject', row_id: rowId }
    )
    .done(function (res) {
      if (!res.success) {
        alert('Delete failed: ' + (res.error || 'Unknown error'));
        $btn.prop('disabled', false);
        return;
      }

      $tr.fadeOut(300, function () {
        $(this).remove();
        const remaining = $tbody.find('tr').length;

        if (remaining === 0) {
          $card.fadeOut(400, function () { $(this).remove(); });
          if ($('.record-group-card').length === 0) {
            $('<div class="alert alert-info mt-3"><i class="fas fa-info-circle mr-1"></i>No records found. Click <strong>Create Record</strong> to add one.</div>')
              .insertAfter('.mt-3.mb-3.d-flex');
          }
        } else {
          $tbody.find('tr').each(function (i) {
            $(this).find('td:first-child').text(i + 1);
          });
          let total = 0;
          $tbody.find('tr').each(function () {
            total += parseInt($(this).find('td:eq(3)').text().trim()) || 0;
          });
          $tfoot.find('td.text-center.font-weight-bold').text(total);
        }
      });
    })
    .fail(function () {
      alert('Network error — please try again.');
      $btn.prop('disabled', false);
    });
  });

});
</script>

</body>
</html>
