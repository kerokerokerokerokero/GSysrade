<?php
session_start();
require_once 'db.php';

// ═══════════════════════════════════════════
// SELF-CONTAINED: DELETE CURRICULUM SUBJECT
// ═══════════════════════════════════════════
if (isset($_POST['action']) && $_POST['action'] === 'delete_cs') {
    header('Content-Type: application/json');
    $cs_id = intval(isset($_POST['cs_id']) ? $_POST['cs_id'] : 0);
    if ($cs_id > 0) {
        $del = $conn->query("DELETE FROM curriculum_subject WHERE id = $cs_id");
        echo json_encode(['success' => (bool)$del, 'error' => $del ? '' : $conn->error]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    }
    exit;
}

// ═══════════════════════════════════════════
// SELF-CONTAINED: ADD SUBJECTS TO CURRICULUM
// ═══════════════════════════════════════════
if (isset($_POST['action']) && $_POST['action'] === 'add_subject') {
    $curr_id   = intval($_POST['curriculum_id']);
    $year      = intval($_POST['year']);
    $sem       = $conn->real_escape_string($_POST['sem']);
    $subjects  = isset($_POST['subject']) ? $_POST['subject'] : [];
    $course_id = 0;

    $added = 0;
    $skipped = 0;
    foreach ($subjects as $sid) {
        $sid = intval($sid);
        $check = $conn->query("SELECT id FROM curriculum_subject WHERE curriculum='$curr_id' AND subject='$sid' LIMIT 1");
        if ($check && $check->num_rows === 0) {
            $conn->query("INSERT INTO curriculum_subject (curriculum, course, year, sem, subject) VALUES ($curr_id, $course_id, $year, '$sem', $sid)");
            $added++;
        } else {
            $skipped++;
        }
    }

    if ($added > 0) {
        $_SESSION['success_msg'] = "$added subject(s) added successfully.";
        if ($skipped > 0) {
            $_SESSION['warning_msg'] = "$skipped duplicate subject(s) were skipped.";
        }
    } elseif ($skipped > 0) {
        $_SESSION['warning_msg'] = 'Selected subjects are already in this curriculum. No new subjects were added.';
    } else {
        $_SESSION['warning_msg'] = 'No subjects selected.';
    }

    header("Location: curriculum_view.php?id=$curr_id");
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'import_excel') {
    $curr_id = intval($_POST['curriculum_id']);
    $rows_json = isset($_POST['import_rows']) ? $_POST['import_rows'] : '[]';
    $rows = json_decode($rows_json, true);
    $added = 0;
    $skipped = 0;
    if (is_array($rows)) {
        foreach ($rows as $row) {
            $subjectCode = isset($row['subject_code']) ? trim($row['subject_code']) : '';
            $yearRaw = isset($row['year']) ? trim($row['year']) : '';
            $semRaw = isset($row['sem']) ? trim($row['sem']) : '';
            $year = 0;
            if (preg_match('/^(1|1st|first)/i', $yearRaw)) {
                $year = 1;
            } elseif (preg_match('/^(2|2nd|second)/i', $yearRaw)) {
                $year = 2;
            } elseif (preg_match('/^(3|3rd|third)/i', $yearRaw)) {
                $year = 3;
            } elseif (preg_match('/^(4|4th|fourth)/i', $yearRaw)) {
                $year = 4;
            } else {
                $year = intval($yearRaw);
            }
            $sem = '';
            $semLower = strtolower($semRaw);
            if (preg_match('/^(1|1st|first)/i', $semLower)) {
                $sem = '1';
            } elseif (preg_match('/^(2|2nd|second)/i', $semLower)) {
                $sem = '2';
            } elseif (strpos($semLower, 'summer') !== false) {
                $sem = 'summer';
            } else {
                $sem = $semRaw;
            }
            if ($subjectCode === '' || $year <= 0 || $sem === '') {
                $skipped++;
                continue;
            }
            $subjectCodeEscaped = $conn->real_escape_string($subjectCode);
            $sub_res = $conn->query("SELECT id FROM subject WHERE subject_code = '$subjectCodeEscaped' LIMIT 1");
            if (!$sub_res || $sub_res->num_rows === 0) {
                $skipped++;
                continue;
            }
            $subject = $sub_res->fetch_assoc();
            $sid = intval($subject['id']);
            $course_id = 0;
            $check = $conn->query("SELECT id FROM curriculum_subject WHERE curriculum='$curr_id' AND subject='$sid' LIMIT 1");
            if ($check && $check->num_rows === 0) {
                $conn->query("INSERT INTO curriculum_subject (curriculum, course, year, sem, subject) VALUES ($curr_id, $course_id, $year, '$sem', $sid)");
                $added++;
            }
        }
    }
    $_SESSION['success_msg'] = "$added imported subject(s)." . ($skipped ? " $skipped row(s) skipped due to missing or invalid data." : '');
    header("Location: curriculum_view.php?id=$curr_id");
    exit;
}

// ═══════════════════════════════════════════
// FETCH CURRICULUM INFO
// ═══════════════════════════════════════════════════════════
$curr_id     = isset($_GET['id']) ? intval($_GET['id']) : 0;
$curr_result = $conn->query("SELECT * FROM crurriculum WHERE id = $curr_id");
$curriculum  = $curr_result ? $curr_result->fetch_assoc() : null;

if (!$curriculum) {
    echo "<p class='text-danger p-4'>Curriculum not found.</p>";
    exit;
}

// ═══════════════════════════════════════════
// FETCH ALL COURSES (for Add Subject modal)
// ═══════════════════════════════════════════
$all_courses = [];
$cr = $conn->query("SELECT * FROM course ORDER BY code ASC");
if ($cr) { while ($c = $cr->fetch_assoc()) $all_courses[] = $c; }

// ═══════════════════════════════════════════
// FETCH SUBJECTS FOR THIS CURRICULUM
// Grouped: course → year → semester
// NOTE: No 'grade' column — grades live on student_record_subject, not here
// ═══════════════════════════════════════════
$data   = [];
$cs_sql = "
  SELECT cs.id AS cs_id, cs.course, cs.year, cs.sem,
         c.code AS course_code, c.name AS course_name,
         s.id AS subject_id, s.subject_code, s.des AS subject_name, s.unit
  FROM curriculum_subject cs
  LEFT JOIN course  c ON c.id = cs.course
  JOIN subject s ON s.id = cs.subject
  WHERE cs.curriculum = $curr_id
  ORDER BY COALESCE(c.name, 'General') ASC, cs.year ASC, cs.sem ASC, s.subject_code ASC
";
$cs_result = $conn->query($cs_sql);
if ($cs_result && $cs_result->num_rows > 0) {
    while ($row = $cs_result->fetch_assoc()) {
        $ckey = $row['course'];
        $ykey = $row['year'];
        $skey = $row['sem'];
        if (!isset($data[$ckey])) {
            $data[$ckey] = [
                'course_code' => $row['course_code'],
                'course_name' => $row['course_name'],
                'years'       => [],
            ];
        }
        if (!isset($data[$ckey]['years'][$ykey])) {
            $data[$ckey]['years'][$ykey] = [];
        }
        $data[$ckey]['years'][$ykey][$skey][] = $row;
    }
}
$existing_subject_ids = [];
foreach ($data as $course_item) {
    foreach ($course_item['years'] as $year_block) {
        foreach ($year_block as $subjects) {
            foreach ($subjects as $subject_row) {
                $existing_subject_ids[] = intval($subject_row['subject_id']);
            }
        }
    }
}
$existing_subject_ids = array_values(array_unique($existing_subject_ids));
$sem_label  = ['1' => '1st Semester', '2' => '2nd Semester', 'summer' => 'Summer'];
$year_label = ['1' => '1st Year',     '2' => '2nd Year',     '3' => '3rd Year',    '4' => '4th Year'];

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $filename = preg_replace('/[^A-Za-z0-9_-]/', '_', $curriculum['name']) ?: 'curriculum_export';
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    echo "Curriculum Name\t" . $curriculum['name'] . "\n";
    echo "School Year\t" . $curriculum['date'] . "\n\n";
    echo "Course Code\tCourse Name\tYear\tSemester\tSubject Code\tSubject Name\tUnits\n";
    foreach ($data as $course) {
        foreach ($course['years'] as $yr => $sems) {
            foreach ($sems as $sem => $subjects) {
                foreach ($subjects as $subj) {
                    $courseCode = $course['course_code'] ?: '';
                    $courseName = $course['course_name'] ?: 'Unassigned';
                    $semLabel = isset($sem_label[$sem]) ? $sem_label[$sem] : $sem;
                    echo "$courseCode\t$courseName\t$yr\t$semLabel\t{$subj['subject_code']}\t{$subj['subject_name']}\t{$subj['unit']}\n";
                }
            }
        }
    }
    exit;
}

include 'layouts/header.php';
?>
<style>
@media print {
  body, .container-fluid { background: white !important; color: black !important; }
  .sidebar, .topbar, .footer, .btn, .alert, .modal, .scroll-to-top, .page-footer, .dropdown-menu, .pagination, .dataTables_wrapper, .breadcrumb, .navbar, .card .card-header .btn { display: none !important; }
  .print-header { display: block !important; }
  .card { box-shadow: none !important; border: none !important; }
  .card-header { background: transparent !important; color: black !important; border-bottom: 1px solid #000 !important; }
  .table { border-collapse: collapse !important; }
  .table th, .table td { border: 1px solid #000 !important; }
  .table thead { background-color: #f8f9fc !important; color: #000 !important; }
  #page-top { padding: 0 !important; }
}
.print-header { display: none; }
</style>
<body id="page-top">
<div id="wrapper">

  <?php include 'layouts/sidebar.php'; ?>

  <div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
      <?php include 'layouts/topbar.php'; ?>

      <div class="container-fluid">

        <!-- Title / breadcrumb -->
        <div class="d-flex align-items-center mb-3">
          <a href="curriculum.php" class="btn btn-sm btn-outline-secondary mr-3">
            <i class="fas fa-arrow-left mr-1"></i>Back
          </a>
          <div>
            <h1 class="h3 mb-0 text-gray-800">
              <i class="fas fa-book-open mr-2 text-primary"></i>
              <?= htmlspecialchars($curriculum['name']); ?>
            </h1>
            <small class="text-muted">
              <i class="fas fa-calendar-alt mr-1"></i><?= htmlspecialchars($curriculum['date']); ?>
            </small>
          </div>
        </div>
        <div class="print-header" style="display:none;">
          <h2 style="margin-bottom:.5rem;">Curriculum Document</h2>
          <p style="margin-bottom:0; font-size:1rem;"><?= htmlspecialchars($curriculum['name']); ?> • <?= htmlspecialchars($curriculum['date']); ?></p>
          <hr style="margin-top:.75rem; margin-bottom:1.5rem;" />
        </div>

        <!-- Flash messages -->
        <?php if (isset($_SESSION['success_msg'])): ?>
          <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($_SESSION['success_msg']); unset($_SESSION['success_msg']); ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
          </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['warning_msg'])): ?>
          <div class="alert alert-warning alert-dismissible fade show shadow-sm">
            <i class="fas fa-exclamation-triangle mr-2"></i><?= htmlspecialchars($_SESSION['warning_msg']); unset($_SESSION['warning_msg']); ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
          </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_msg'])): ?>
          <div class="alert alert-danger alert-dismissible fade show shadow-sm">
            <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($_SESSION['error_msg']); unset($_SESSION['error_msg']); ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
          </div>
        <?php endif; ?>

        <!-- Add Subject Button -->
        <div class="mb-4 d-flex flex-wrap align-items-center" style="gap:.5rem;">
          <button class="btn btn-success" data-toggle="modal" data-target="#addSubjectModal">
            <i class="fas fa-plus mr-1"></i> Add Subject to Curriculum
          </button>
          <a class="btn btn-outline-primary" href="print.php?curriculum_id=<?= $curr_id; ?>&autoprint=1" target="_blank">
            <i class="fas fa-print mr-1"></i> Print
          </a>
          <button class="btn btn-info" type="button" id="downloadPdfBtn">
            <i class="fas fa-file-pdf mr-1"></i> Export PDF
          </button>
          <a href="curriculum_view.php?id=<?= $curr_id; ?>&export=excel" class="btn btn-success">
            <i class="fas fa-file-excel mr-1"></i> Export Excel
          </a>
          <button class="btn btn-warning" type="button" data-toggle="modal" data-target="#uploadExcelModal">
            <i class="fas fa-file-upload mr-1"></i> Upload Excel
          </button>
        </div>

        <div id="emptyCurriculumAlert" class="alert alert-info <?= empty($data) ? '' : 'd-none'; ?>">
          <i class="fas fa-info-circle mr-1"></i>
          No subjects yet. Click <strong>Add Subject to Curriculum</strong> or upload a file to begin.
        </div>

        <!-- ═══════════════════════════════════════
             SUBJECTS: COURSE → YEAR → SEMESTER
        ═══════════════════════════════════════ -->
        <?php if (!empty($data)): ?>
          <?php foreach ($data as $course_id_key => $course): ?>
            <div class="card shadow mb-4">
              <?php if ($course_id_key): ?>
              <div class="card-header bg-primary text-white py-2">
                <h5 class="mb-0">
                  <i class="fas fa-graduation-cap mr-2"></i>
                  <?= htmlspecialchars($course['course_code']) . ' — ' . htmlspecialchars($course['course_name']); ?>
                </h5>
              </div>
              <?php endif; ?>
              <div class="card-body pb-2">

                <?php foreach ($course['years'] as $yr => $sems): ?>
                  <h6 class="font-weight-bold text-primary mt-3 mb-2">
                    <i class="fas fa-layer-group mr-1"></i>
                    <?= isset($year_label[$yr]) ? $year_label[$yr] : "Year $yr"; ?>
                  </h6>
                  <?php foreach ($sems as $sem => $subjects): ?>
                    <div class="card shadow-sm mb-3" style="border-left:4px solid #36b9cc;">
                      <div class="card-header py-2" style="background-color:#eaf0fb;">
                        <span class="font-weight-bold text-info">
                          <i class="fas fa-calendar-week mr-1"></i>
                          <?= isset($sem_label[$sem]) ? $sem_label[$sem] : htmlspecialchars($sem); ?>
                        </span>
                      </div>
                      <div class="card-body p-0">
                        <div class="table-responsive">
                          <table class="table table-bordered table-hover mb-0" style="font-size:0.9rem;">
                            <thead style="background-color:#4e73df; color:#fff;">
                              <tr>
                                <th>Subject Code</th>
                                <th>Subject Name</th>
                                <th class="text-center" style="width:70px;">Units</th>
                                <th class="text-center" style="width:80px;">Action</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php foreach ($subjects as $subj): ?>
                                <tr id="cs-row-<?= $subj['cs_id']; ?>">
                                  <td><?= htmlspecialchars($subj['subject_code']); ?></td>
                                  <td><?= htmlspecialchars($subj['subject_name']); ?></td>
                                  <td class="text-center"><?= htmlspecialchars($subj['unit']); ?></td>
                                  <td class="text-center">
                                    <button class="btn btn-sm btn-danger deleteCSBtn"
                                            data-cs-id="<?= $subj['cs_id']; ?>"
                                            title="Remove subject from curriculum">
                                      <i class="fas fa-trash-alt"></i>
                                    </button>
                                  </td>
                                </tr>
                              <?php endforeach; ?>
                            </tbody>
                            <tfoot style="background-color:#f0f4ff;">
                              <tr>
                                <td colspan="2" class="text-right font-weight-bold pr-3">Total Units:</td>
                                <td class="text-center font-weight-bold">
                                  <?= array_sum(array_column($subjects, 'unit')); ?>
                                </td>
                                <td></td>
                              </tr>
                            </tfoot>
                          </table>
                        </div>
                      </div>
                    </div><!-- /semester card -->

                  <?php endforeach; ?>
                <?php endforeach; ?>

              </div>
            </div><!-- /course card -->
          <?php endforeach; ?>

        <?php endif; ?>

      </div><!-- /.container-fluid -->
    </div>
    <?php include 'layouts/footer.php'; ?>
  </div>
</div>

<!-- Scroll to Top -->
<a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

<!-- ═══════════════════════════════════════════
     ADD SUBJECT TO CURRICULUM MODAL
═══════════════════════════════════════════ -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <form action="curriculum_view.php?id=<?= $curr_id; ?>" method="POST">
      <input type="hidden" name="action"        value="add_subject">
      <input type="hidden" name="curriculum_id" value="<?= $curr_id; ?>">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">
            <i class="fas fa-plus-circle mr-2"></i>Add Subject to Curriculum
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-row">
              <div class="form-group col-md-4">
              <label>Year Level <span class="text-danger">*</span></label>
              <select class="form-control" name="year" required>
                <option value="" disabled selected>— Select Year —</option>
                <option value="1">1st Year</option>
                <option value="2">2nd Year</option>
                <option value="3">3rd Year</option>
                <option value="4">4th Year</option>
              </select>
            </div>
            <div class="form-group col-md-4">
              <label>Semester <span class="text-danger">*</span></label>
              <select class="form-control" name="sem" required>
                <option value="" disabled selected>— Select Semester —</option>
                <option value="1">1st Semester</option>
                <option value="2">2nd Semester</option>
                <option value="summer">Summer</option>
              </select>
            </div>
          </div>
          <hr class="my-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="text-primary mb-0">
              <i class="fas fa-check-square mr-1"></i>Select Subjects
            </h6>
            <div class="form-group mb-0" style="max-width:320px; width:100%;">
              <input id="subjectModalSearch" type="text" class="form-control form-control-sm" placeholder="Search subjects..." autocomplete="off" spellcheck="false">
            </div>
          </div>
          <div class="table-responsive" style="max-height:280px; overflow-y:auto; border:1px solid #dee2e6; border-radius:4px;">
            <table id="subjectTable" class="table table-bordered table-hover table-sm mb-0">
              <thead style="position:sticky; top:0; background-color:#4e73df; color:#fff; z-index:1;">
                <tr>
                  <th class="text-center" style="width:55px;">Add</th>
                  <th>Subject Code</th>
                  <th>Subject Name</th>
                  <th class="text-center" style="width:70px;">Units</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $sub_res = $conn->query("SELECT * FROM subject ORDER BY subject_code ASC");
                if ($sub_res && $sub_res->num_rows > 0):
                  while ($s = $sub_res->fetch_assoc()):
                ?>
                <tr>
                  <?php $subjectAdded = in_array(intval($s['id']), $existing_subject_ids, true); ?>
                  <td class="text-center">
                    <input type="checkbox" name="subject[]" value="<?= $s['id']; ?>" <?= $subjectAdded ? 'disabled' : ''; ?>>
                  </td>
                  <td>
                    <?= htmlspecialchars($s['subject_code']); ?>
                    <?php if ($subjectAdded): ?>
                      <span class="badge badge-secondary ml-2">Added</span>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($s['des']); ?></td>
                  <td class="text-center"><?= htmlspecialchars($s['unit']); ?></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="4" class="text-center text-muted py-3">No subjects found.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i>Add Selected</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="uploadExcelModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <form id="uploadExcelForm" action="curriculum_view.php?id=<?= $curr_id; ?>" method="POST">
      <input type="hidden" name="action" value="import_excel">
      <input type="hidden" name="curriculum_id" value="<?= $curr_id; ?>">
      <input type="hidden" name="import_rows" id="importRowsInput" value="[]">
      <div class="modal-content">
        <div class="modal-header bg-warning text-white">
          <h5 class="modal-title">
            <i class="fas fa-file-upload mr-2"></i>Upload Excel to Curriculum
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-row align-items-end">
            <div class="form-group col-md-8">
              <label for="uploadExcelFile">Select file</label>
              <input id="uploadExcelFile" type="file" accept=".xlsx,.xls,.csv,.txt" class="form-control-file" required>
            </div>
            <div class="form-group col-md-4">
              <label>&nbsp;</label>
              <button id="parseExcelFileBtn" type="button" class="btn btn-primary btn-block">Load preview</button>
            </div>
          </div>
          <div class="alert alert-secondary small" id="uploadExcelHint">
            Accepted formats: XLSX, XLS, CSV, TXT. The first row may be a header row containing Subject Code, Year, Semester.
          </div>
          <div class="table-responsive" style="max-height:280px; overflow-y:auto; border:1px solid #dee2e6; border-radius:4px;">
            <table class="table table-sm table-bordered mb-0">
              <thead style="background-color:#f8f9fc; position:sticky; top:0; z-index:1;">
                <tr>
                  <th>Subject Code</th>
                  <th>Year</th>
                  <th>Semester</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="uploadPreviewBody">
                <tr><td colspan="4" class="text-center text-muted py-4">Select a file and click Load preview.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-warning" id="importExcelSubmit" disabled>
            <i class="fas fa-file-import mr-1"></i> Import selected subjects
          </button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

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
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
$(document).ready(function () {

  const PAGE_URL = window.location.pathname + '?id=<?= $curr_id; ?>';
  let uploadRows = [];

  function showEmptyStateIfNeeded() {
    if ($('.card.shadow.mb-4').length === 0) {
      $('#emptyCurriculumAlert').removeClass('d-none');
    }
  }

  function updateUploadPreview(rows) {
    uploadRows = rows || [];
    var $body = $('#uploadPreviewBody').empty();
    if (!uploadRows.length) {
      $body.append('<tr><td colspan="4" class="text-center text-muted py-4">No valid rows found. Please check your file or headers.</td></tr>');
      $('#importExcelSubmit').prop('disabled', true);
      $('#importRowsInput').val('[]');
      return;
    }

    uploadRows.forEach(function (row) {
      var status = row.subject_code && row.year && row.sem ? 'Ready' : 'Missing required fields';
      var statusClass = status === 'Ready' ? 'text-success' : 'text-danger';
      $body.append(
        '<tr>' +
          '<td>' + $('<div>').text(row.subject_code).html() + '</td>' +
          '<td>' + $('<div>').text(row.year).html() + '</td>' +
          '<td>' + $('<div>').text(row.sem).html() + '</td>' +
          '<td class="' + statusClass + '">' + status + '</td>' +
        '</tr>'
      );
    });
    $('#importExcelSubmit').prop('disabled', false);
    $('#importRowsInput').val(JSON.stringify(uploadRows));
  }

  function normalizeSemester(value) {
    value = String(value || '').trim().toLowerCase();
    if (/^(1|1st|first)/.test(value)) return '1';
    if (/^(2|2nd|second)/.test(value)) return '2';
    if (/summer/.test(value)) return 'summer';
    return value;
  }

  function normalizeYear(value) {
    value = String(value || '').trim().toLowerCase();
    if (/^(1|1st|first)/.test(value)) return '1';
    if (/^(2|2nd|second)/.test(value)) return '2';
    if (/^(3|3rd|third)/.test(value)) return '3';
    if (/^(4|4th|fourth)/.test(value)) return '4';
    var numeric = parseInt(value, 10);
    return numeric > 0 ? String(numeric) : '';
  }

  function normalizeFileRows(rows) {
    if (!rows || !rows.length) return [];

    var headerRow = rows[0].map(function (cell) {
      return typeof cell === 'string' ? cell.trim().toLowerCase() : '';
    });
    var hasHeader = headerRow.some(function (cell) {
      return /subject.*code|subject code|code|year|semester|sem/.test(cell);
    });

    var subjectIndex = 0;
    var yearIndex = 1;
    var semIndex = 2;

    if (hasHeader) {
      subjectIndex = headerRow.findIndex(function (cell) { return /subject.*code|subject code|code/.test(cell); });
      yearIndex = headerRow.findIndex(function (cell) { return /year/.test(cell); });
      semIndex = headerRow.findIndex(function (cell) { return /sem|semester/.test(cell); });
      rows = rows.slice(1);
    }

    return rows.map(function (row) {
      return {
        subject_code: row[subjectIndex] ? String(row[subjectIndex]).trim() : '',
        year: normalizeYear(row[yearIndex]),
        sem: normalizeSemester(row[semIndex])
      };
    }).filter(function (row) {
      return row.subject_code || row.year || row.sem;
    });
  }

  function parseTextContent(text) {
    var delimiter = text.indexOf('\t') >= 0 ? '\t' : ',';
    var lines = text.split(/\r?\n/).filter(function (line) { return line.trim() !== ''; });
    var rows = lines.map(function (line) {
      return line.split(delimiter).map(function (cell) { return cell.trim(); });
    });
    return normalizeFileRows(rows);
  }

  function parseFile(file) {
    var fileName = file.name.toLowerCase();

    if (fileName.match(/\.(csv|txt|xls)$/i)) {
      var reader = new FileReader();
      reader.onload = function (e) {
        var rows = parseTextContent(e.target.result);
        updateUploadPreview(rows);
      };
      reader.readAsText(file);
      return;
    }

    if (fileName.match(/\.xlsx$/i)) {
      var reader = new FileReader();
      reader.onload = function (e) {
        var data = new Uint8Array(e.target.result);
        var workbook = XLSX.read(data, { type: 'array' });
        var sheet = workbook.Sheets[workbook.SheetNames[0]];
        var rows = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '' });
        updateUploadPreview(normalizeFileRows(rows));
      };
      reader.readAsArrayBuffer(file);
      return;
    }

    alert('Unsupported file type. Please use XLSX, XLS, CSV, or TXT.');
  }

  $('#parseExcelFileBtn').on('click', function () {
    var fileInput = $('#uploadExcelFile')[0];
    if (!fileInput.files.length) {
      return alert('Please select a file to load.');
    }
    parseFile(fileInput.files[0]);
  });

  function resetSubjectModalSearch() {
    $('#subjectModalSearch').val('');
    $('#subjectTable tbody tr').each(function () {
      this.style.display = '';
    });
  }

  function filterSubjectTable() {
    var query = $('#subjectModalSearch').val().toLowerCase().trim();
    var $rows = $('#subjectTable tbody tr');
    requestAnimationFrame(function () {
      $rows.each(function () {
        var code = $(this).find('td:nth-child(2)').text().toLowerCase();
        var name = $(this).find('td:nth-child(3)').text().toLowerCase();
        this.style.display = (!query || code.indexOf(query) !== -1 || name.indexOf(query) !== -1) ? '' : 'none';
      });
    });
  }

  $('#subjectModalSearch')
    .on('input', filterSubjectTable)
    .on('keydown keypress', function (e) {
      if (e.key === 'Enter' || e.which === 13) {
        e.preventDefault();
        e.stopPropagation();
        return false;
      }
    });

  $('#addSubjectModal').on('shown.bs.modal hidden.bs.modal', function () {
    resetSubjectModalSearch();
  });

  $('#downloadPdfBtn').on('click', function () {
    var $printHeader = $('.print-header');
    $printHeader.show();
    html2canvas(document.querySelector('.container-fluid'), { scale: 2, useCORS: true })
      .then(function (canvas) {
        var imgData = canvas.toDataURL('image/png');
        var pdf = new jspdf.jsPDF('p', 'mm', 'a4');
        var imgProps = pdf.getImageProperties(imgData);
        var pdfWidth = pdf.internal.pageSize.getWidth();
        var pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
        pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
        var fileName = '<?= preg_replace("/[^A-Za-z0-9_-]/", "_", $curriculum['name']) ?: 'curriculum'; ?>';
        pdf.save(fileName + '.pdf');
      })
      .catch(function () {
        alert('Unable to create PDF. Please try again.');
      })
      .finally(function () {
        $printHeader.hide();
      });
  });

  $(document).on('click', '.deleteCSBtn', function () {
    var $btn = $(this);
    var csId = $btn.data('cs-id');
    var $row = $('#cs-row-' + csId);
    var $tbody = $row.closest('tbody');
    var $semCard = $row.closest('.card');
    var $outerCourseCard = $semCard.closest('.card.shadow.mb-4');

    if (!confirm('Remove this subject from the curriculum?')) return;

    $btn.prop('disabled', true);

    $.post(PAGE_URL, { action: 'delete_cs', cs_id: csId })
      .done(function (res) {
        if (res.success) {
          $row.fadeOut(300, function () {
            $(this).remove();

            var total = 0;
            $tbody.find('tr').each(function () {
              total += parseInt($(this).find('td:eq(2)').text().trim()) || 0;
            if ($tbody.find('tr').length === 0) {
              $semCard.fadeOut(400, function () {
                $(this).remove();
                if ($outerCourseCard.find('.card.shadow-sm.mb-3').length === 0) {
                  $outerCourseCard.fadeOut(400, function () {
                    $(this).remove();
                    showEmptyStateIfNeeded();
                  });
                } else {
                  showEmptyStateIfNeeded();
                }
              });
            }
          });
        } else {
          alert('Delete failed: ' + (res.error || 'Unknown error'));
          $btn.prop('disabled', false);
        }
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
