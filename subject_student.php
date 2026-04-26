<?php
session_start();
require_once 'db.php';

// ════════════════════════════════════════════════════════
//  AJAX: UPDATE GRADE ROW
// ════════════════════════════════════════════════════════
if (isset($_POST['action']) && $_POST['action'] === 'update_grade') {
    header('Content-Type: application/json');

    $row_id    = intval($_POST['row_id']    ?? 0);
    $pre_mid   = trim(strtoupper($_POST['pre_mid']   ?? ''));
    $midterm   = trim(strtoupper($_POST['midterm']   ?? ''));
    $pre_final = trim(strtoupper($_POST['pre_final'] ?? ''));
    $final     = trim(strtoupper($_POST['final']     ?? ''));

    if ($row_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid row ID']);
        exit;
    }

    $normalizeGrade = function($v) {
        $v = trim(strtoupper($v));
        if ($v === 'NG' || $v === '') return null;
        return is_numeric($v) ? floatval($v) : null;
    };

    $pm = $normalizeGrade($pre_mid);
    $mt = $normalizeGrade($midterm);
    $pf = $normalizeGrade($pre_final);
    $fn = $normalizeGrade($final);

    $has_ng = in_array('NG', [$pre_mid, $midterm, $pre_final, $final], true);

    if (!$has_ng && $pm !== null && $mt !== null && $pf !== null && $fn !== null) {
        $computed    = ($pm + $mt + $pf + $fn) * 0.25;
        $final_grade = round($computed, 2);
        $remarks     = $final_grade >= 75 ? 'Passed' : 'Failed';
    } else {
        $final_grade = null;
        $remarks     = $has_ng ? 'INC' : null;
    }

    $pm_sql = $pm !== null ? $pm : 'NULL';
    $mt_sql = $mt !== null ? $mt : 'NULL';
    $pf_sql = $pf !== null ? $pf : 'NULL';
    $fn_sql = $fn !== null ? $fn : 'NULL';
    $fg_sql = $final_grade !== null ? $final_grade : 'NULL';
    $rk_sql = $remarks !== null ? "'" . $conn->real_escape_string($remarks) . "'" : 'NULL';

    $ok = $conn->query("
        UPDATE student_record_subject
        SET pre_mid=$pm_sql, midterm=$mt_sql, pre_final=$pf_sql, `final`=$fn_sql,
            final_grade=$fg_sql, remarks=$rk_sql
        WHERE id=$row_id
    ");

    echo json_encode([
        'success'     => (bool)$ok,
        'error'       => $ok ? null : $conn->error,
        'final_grade' => $final_grade,
        'remarks'     => $remarks,
    ]);
    exit;
}

// ════════════════════════════════════════════════════════
//  RESOLVE SUBJECT FROM URL PARAMS
// ════════════════════════════════════════════════════════
$faculty_subject_id = intval($_GET['faculty_subject_id'] ?? 0);
$subject_id         = intval($_GET['subject_id']         ?? 0);
$faculty_id         = intval($_GET['faculty_id']         ?? 0);

if ($faculty_subject_id > 0) {
    $fsq = $conn->query("SELECT * FROM faculty_subject WHERE id = $faculty_subject_id LIMIT 1");
    if ($fsq && $fsq->num_rows > 0) {
        $fs_row     = $fsq->fetch_assoc();
        $subject_id = intval($fs_row['id_subject']);
        if (!$faculty_id) $faculty_id = intval($fs_row['faculty_id']);
    }
}

$subject_info = null;
if ($subject_id > 0) {
    $sq = $conn->query("SELECT * FROM subject WHERE id = $subject_id LIMIT 1");
    if ($sq && $sq->num_rows > 0) $subject_info = $sq->fetch_assoc();
}

$faculty_info = null;
if ($faculty_id > 0) {
    $fq = $conn->query("SELECT * FROM faculty WHERE id = $faculty_id LIMIT 1");
    if ($fq && $fq->num_rows > 0) $faculty_info = $fq->fetch_assoc();
}

// ════════════════════════════════════════════════════════
//  FETCH ALL STUDENTS WITH THIS SUBJECT IN THEIR RECORD
// ════════════════════════════════════════════════════════
$students = [];
if ($subject_id > 0) {
    $sql = "
        SELECT
            srs.id          AS row_id,
            srs.pre_mid,
            srs.midterm,
            srs.pre_final,
            srs.final,
            srs.final_grade,
            srs.remarks,
            sr.school_year,
            sr.year_level,
            sr.semester,
            s.id            AS student_id,
            s.fname, s.mname, s.lname,
            sub.unit,
            sub.subject_code,
            sub.des         AS subject_name,
            c.code          AS course_code,
            c.name          AS course_name
        FROM student_record_subject srs
        JOIN student_record sr  ON sr.id  = srs.record_id
        JOIN student s          ON s.id   = sr.student_id
        JOIN subject sub        ON sub.id = srs.subject_id
        LEFT JOIN course c      ON c.id   = s.course
        WHERE srs.subject_id = $subject_id
        ORDER BY s.lname ASC, s.fname ASC
    ";
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
        while ($r = $res->fetch_assoc()) $students[] = $r;
    }
}

$semester_label = [1 => '1st Sem', 2 => '2nd Sem'];
$year_label     = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'];

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
                <div class="d-flex align-items-center mb-3">
                    <?php if ($faculty_id): ?>
                    <a href="instructor.php?id=<?= $faculty_id ?>" class="btn btn-sm btn-outline-secondary mr-3">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <?php endif; ?>
                    <div>
                        <h1 class="h3 mb-0 text-gray-800">Subject Student List</h1>
                        <?php if ($subject_info): ?>
                        <small class="text-muted">
                            <strong><?= htmlspecialchars($subject_info['subject_code']) ?></strong>
                            &mdash; <?= htmlspecialchars($subject_info['des']) ?>
                            &bull; <?= htmlspecialchars($subject_info['unit']) ?> unit(s)
                            <?php if ($faculty_info): ?>
                                &bull; Instructor:
                                <strong><?= htmlspecialchars(
                                    trim($faculty_info['first_name'] . ' ' .
                                        ($faculty_info['middle_name'] ? $faculty_info['middle_name'][0].'. ' : '') .
                                        $faculty_info['last_name'])
                                ) ?></strong>
                            <?php endif; ?>
                        </small>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!$subject_id): ?>
                <div class="alert alert-warning shadow-sm">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    No subject selected. Go back and click <strong>View</strong> on a subject.
                </div>

                <?php else: ?>

                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center"
                         style="background-color:#4e73df;">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-users mr-2"></i>
                            Enrolled Students
                            <?php if ($subject_info): ?>
                                &mdash; <?= htmlspecialchars($subject_info['subject_code']) ?>
                            <?php endif; ?>
                        </h6>
                        <span class="badge badge-light text-primary" style="font-size:0.85rem;">
                            <?= count($students) ?> student(s)
                        </span>
                    </div>

                    <div class="card-body p-0">
                    <?php if (empty($students)): ?>
                        <div class="alert alert-info m-3">
                            <i class="fas fa-info-circle mr-2"></i>
                            No students have this subject in their record yet.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0" style="font-size:0.875rem;">
                                <thead style="background-color:#4e73df; color:#fff;">
                                    <tr>
                                        <th class="text-center align-middle" style="width:40px;">#</th>
                                        <th class="align-middle">Student Name</th>
                                        <th class="text-center align-middle" style="width:80px;">Course</th>
                                        <th class="text-center align-middle" style="width:80px;">Year</th>
                                        <th class="text-center align-middle" style="width:90px;">School Year</th>
                                        <th class="text-center align-middle" style="width:70px;">Sem</th>
                                        <th class="text-center align-middle" style="width:55px;">Units</th>
                                        <th class="text-center align-middle" style="width:90px;">Pre-Mid</th>
                                        <th class="text-center align-middle" style="width:90px;">Midterm</th>
                                        <th class="text-center align-middle" style="width:90px;">Pre-Final</th>
                                        <th class="text-center align-middle" style="width:90px;">Final</th>
                                        <th class="text-center align-middle" style="width:95px;">Final Grade</th>
                                        <th class="text-center align-middle" style="width:85px;">Remark</th>
                                        <th class="text-center align-middle" style="width:65px;">Save</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php $n = 1; foreach ($students as $st):
                                    $fmt = function($v) {
                                        return ($v !== null && $v !== '') ? number_format((float)$v, 2) : '';
                                    };
                                    $fg_val   = $st['final_grade'];
                                    $fg_class = ($fg_val !== null && $fg_val !== '')
                                        ? ($fg_val >= 75 ? 'badge-success' : 'badge-danger') : '';
                                    $rk       = $st['remarks'] ?? '';
                                    $rk_class = match($rk) {
                                        'Passed' => 'badge-success',
                                        'Failed' => 'badge-danger',
                                        'INC'    => 'badge-warning text-dark',
                                        default  => 'badge-secondary',
                                    };
                                    $fullname = htmlspecialchars(
                                        $st['lname'] . ', ' . $st['fname'] .
                                        ($st['mname'] ? ' ' . strtoupper($st['mname'][0]) . '.' : '')
                                    );
                                ?>
                                <tr id="row-<?= $st['row_id'] ?>">
                                    <td class="text-center align-middle"><?= $n++ ?></td>
                                    <td class="align-middle">
                                        <a href="student_record.php?id=<?= $st['student_id'] ?>"
                                           class="font-weight-bold text-primary text-decoration-none" target="_blank">
                                            <?= $fullname ?>
                                        </a>
                                    </td>
                                    <td class="text-center align-middle"><?= htmlspecialchars($st['course_code'] ?? '—') ?></td>
                                    <td class="text-center align-middle"><?= htmlspecialchars($year_label[$st['year_level']] ?? $st['year_level']) ?></td>
                                    <td class="text-center align-middle"><?= htmlspecialchars($st['school_year'] ?? '—') ?></td>
                                    <td class="text-center align-middle"><?= htmlspecialchars($semester_label[$st['semester']] ?? $st['semester']) ?></td>
                                    <td class="text-center align-middle"><?= htmlspecialchars($st['unit'] ?? '—') ?></td>

                                    <!-- Pre-Mid -->
                                    <td class="text-center align-middle">
                                        <input type="text"
                                               class="form-control form-control-sm text-center grade-input"
                                               data-row="<?= $st['row_id'] ?>" data-field="pre_mid"
                                               value="<?= $fmt($st['pre_mid']) ?>"
                                               placeholder="NG/60-100"
                                               style="width:80px; margin:auto;">
                                    </td>
                                    <!-- Midterm -->
                                    <td class="text-center align-middle">
                                        <input type="text"
                                               class="form-control form-control-sm text-center grade-input"
                                               data-row="<?= $st['row_id'] ?>" data-field="midterm"
                                               value="<?= $fmt($st['midterm']) ?>"
                                               placeholder="NG/60-100"
                                               style="width:80px; margin:auto;">
                                    </td>
                                    <!-- Pre-Final -->
                                    <td class="text-center align-middle">
                                        <input type="text"
                                               class="form-control form-control-sm text-center grade-input"
                                               data-row="<?= $st['row_id'] ?>" data-field="pre_final"
                                               value="<?= $fmt($st['pre_final']) ?>"
                                               placeholder="NG/60-100"
                                               style="width:80px; margin:auto;">
                                    </td>
                                    <!-- Final -->
                                    <td class="text-center align-middle">
                                        <input type="text"
                                               class="form-control form-control-sm text-center grade-input"
                                               data-row="<?= $st['row_id'] ?>" data-field="final"
                                               value="<?= $fmt($st['final']) ?>"
                                               placeholder="NG/60-100"
                                               style="width:80px; margin:auto;">
                                    </td>

                                    <!-- Final Grade (auto) -->
                                    <td class="text-center align-middle">
                                        <?php if ($fg_val !== null && $fg_val !== ''): ?>
                                        <span class="badge px-2 py-1 <?= $fg_class ?>"
                                              id="fg-badge-<?= $st['row_id'] ?>">
                                            <?= number_format((float)$fg_val, 2) ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="text-muted" id="fg-badge-<?= $st['row_id'] ?>">—</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Remark (auto) -->
                                    <td class="text-center align-middle">
                                        <span class="badge px-2 py-1 <?= $rk ? $rk_class : '' ?>"
                                              id="rk-badge-<?= $st['row_id'] ?>">
                                            <?= $rk ? htmlspecialchars($rk) : '—' ?>
                                        </span>
                                    </td>

                                    <!-- Save -->
                                    <td class="text-center align-middle">
                                        <button type="button"
                                                class="btn btn-sm btn-success save-grade-btn"
                                                data-row="<?= $st['row_id'] ?>"
                                                title="Save">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                                <tfoot style="background-color:#f0f4ff;">
                                    <tr>
                                        <td colspan="6" class="text-right font-weight-bold pr-3">Total Students:</td>
                                        <td class="text-center font-weight-bold"><?= count($students) ?></td>
                                        <td colspan="7"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                    </div><!-- card-body -->
                </div><!-- card -->

                <?php endif; ?>

            </div><!-- container-fluid -->
        </div><!-- content -->

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

<script>
$(document).ready(function () {

    // Uppercase on blur
    $(document).on('blur', '.grade-input', function () {
        $(this).val($(this).val().trim().toUpperCase());
    });

    // Save button → AJAX
    $(document).on('click', '.save-grade-btn', function () {
        var rowId     = $(this).data('row');
        var btn       = $(this);
        var inputs    = $('[data-row="' + rowId + '"].grade-input');

        var pre_mid   = inputs.filter('[data-field="pre_mid"]').val().trim().toUpperCase();
        var midterm   = inputs.filter('[data-field="midterm"]').val().trim().toUpperCase();
        var pre_final = inputs.filter('[data-field="pre_final"]').val().trim().toUpperCase();
        var final_v   = inputs.filter('[data-field="final"]').val().trim().toUpperCase();

        var pattern = /^(NG|([6-9][0-9]|100)(\.[0-9]{1,2})?)$/;
        var fields  = [pre_mid, midterm, pre_final, final_v];
        for (var i = 0; i < fields.length; i++) {
            if (fields[i] !== '' && !pattern.test(fields[i])) {
                alert('Invalid grade value. Enter NG or a number between 60 and 100.');
                return;
            }
        }

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: 'subject_student.php',
            method: 'POST',
            data: {
                action:    'update_grade',
                row_id:    rowId,
                pre_mid:   pre_mid,
                midterm:   midterm,
                pre_final: pre_final,
                final:     final_v
            },
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    // Final Grade badge
                    var fgBadge = $('#fg-badge-' + rowId);
                    if (res.final_grade !== null) {
                        var fg  = parseFloat(res.final_grade).toFixed(2);
                        var cls = parseFloat(res.final_grade) >= 75 ? 'badge-success' : 'badge-danger';
                        fgBadge.attr('class', 'badge px-2 py-1 ' + cls).text(fg);
                    } else {
                        fgBadge.attr('class', 'text-muted').text('—');
                    }
                    // Remark badge
                    var rkBadge = $('#rk-badge-' + rowId);
                    var rk      = res.remarks || '';
                    var rkMap   = {Passed:'badge-success', Failed:'badge-danger', INC:'badge-warning text-dark'};
                    var rkCls   = rkMap[rk] || 'badge-secondary';
                    rkBadge.attr('class', 'badge px-2 py-1 ' + (rk ? rkCls : '')).text(rk || '—');

                    // Flash row green
                    $('#row-' + rowId).addClass('table-success');
                    setTimeout(function () { $('#row-' + rowId).removeClass('table-success'); }, 1400);
                } else {
                    alert('Save failed: ' + (res.error || 'Unknown error'));
                }
            },
            error: function () { alert('Network error. Please try again.'); },
            complete: function () { btn.prop('disabled', false).html('<i class="fas fa-save"></i>'); }
        });
    });

});
</script>

</body>
</html>
