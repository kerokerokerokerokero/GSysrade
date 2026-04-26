<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

$curriculum_id = isset($_GET['curriculum_id']) && is_numeric($_GET['curriculum_id']) ? intval($_GET['curriculum_id']) : 0;
$student_id    = isset($_GET['student_id']) && is_numeric($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$id            = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : 0;

$target_student_id = $student_id > 0 ? $student_id : $id;
$is_curriculum = $curriculum_id > 0;
$is_student    = !$is_curriculum && $target_student_id > 0;
$sem_label = ['1' => '1st Semester', '2' => '2nd Semester', 'summer' => 'Summer'];
$year_label = ['1' => '1st Year', '2' => '2nd Year', '3' => '3rd Year', '4' => '4th Year'];
$total_subjects = 0;

if ($is_curriculum) {
    $result = $conn->query("SELECT * FROM crurriculum WHERE id = $curriculum_id");
    if (!$result || $result->num_rows === 0) {
        die("Curriculum not found.");
    }
    $curriculum = $result->fetch_assoc();
    $course_name = 'Curriculum Print';
    $current_doc_id = $curriculum_id;

    $cs_sql = "
      SELECT cs.id AS cs_id, cs.course, cs.year, cs.sem,
             c.code AS course_code, c.name AS course_name,
             s.subject_code, s.des AS subject_name, s.unit
      FROM curriculum_subject cs
      LEFT JOIN course c ON c.id = cs.course
      JOIN subject s ON s.id = cs.subject
      WHERE cs.curriculum = $curriculum_id
      ORDER BY COALESCE(c.name, 'General') ASC, cs.year ASC, cs.sem ASC, s.subject_code ASC
    ";
    $cs_result = $conn->query($cs_sql);
    $grades = [];
    if ($cs_result && $cs_result->num_rows > 0) {
        while ($rec = $cs_result->fetch_assoc()) {
            $ckey = $rec['course'];
            $ykey = $rec['year'];
            $skey = $rec['sem'];
            if (!isset($grades[$ckey])) {
                $grades[$ckey] = [
                    'course_code' => $rec['course_code'],
                    'course_name' => $rec['course_name'],
                    'years'       => [],
                ];
            }
            if (!isset($grades[$ckey]['years'][$ykey])) {
                $grades[$ckey]['years'][$ykey] = [];
            }
            if (!isset($grades[$ckey]['years'][$ykey][$skey])) {
                $grades[$ckey]['years'][$ykey][$skey] = [];
            }
            $grades[$ckey]['years'][$ykey][$skey][] = $rec;
        }
    }
    foreach ($grades as $course) {
        foreach ($course['years'] as $year) {
            foreach ($year as $subjects) {
                $total_subjects += count($subjects);
            }
        }
    }
}

if ($is_student) {
    // Validate student ID
    $id = $target_student_id;
    $current_doc_id = $id;

    // ── Fetch student info ──────────────────────────────────────────────
    $result = $conn->query("SELECT * FROM student WHERE id = $id");
    if ($result->num_rows === 0) {
        die("Student not found.");
    }
    $student = $result->fetch_assoc();

    // ── Fetch course name ───────────────────────────────────────────────
    $course_name = $student['course'] ?? 'N/A';
    $stmt = $conn->prepare("SELECT code, name FROM course WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $student['course']);
        $stmt->execute();
        $cr = $stmt->get_result();
        if ($cr->num_rows > 0) {
            $c = $cr->fetch_assoc();
            $course_name = $c['code'] . ' – ' . $c['name'];
        }
        $stmt->close();
    }

    // ── Fetch all records + subjects + grades ───────────────────────────
    // Matches the same JOIN structure used in student_record.php
    $records_sql = "
  SELECT
    sr.id           AS record_id,
    sr.school_year,
    sr.year_level,
    sr.semester,
    c.name          AS curriculum_name,
    srs.id          AS row_id,
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
  ORDER BY sr.school_year ASC, sr.semester ASC, s.subject_code ASC
";
$records_result = $conn->query($records_sql);

// Group by school_year → semester label → subjects[]
$grades = [];
if ($records_result && $records_result->num_rows > 0) {
    while ($rec = $records_result->fetch_assoc()) {
        $sy  = $rec['school_year'];
        $sem = ($rec['semester'] == 1) ? '1st' : '2nd';
        if (!isset($grades[$sy][$sem])) {
            $grades[$sy][$sem] = [];
        }
        if ($rec['row_id']) {
            $grades[$sy][$sem][] = [
                'subject_code' => $rec['subject_code'],
                'subject_name' => $rec['subject_name'],
                'units'        => $rec['unit'],
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

// ── Compute overall GWA (weighted by units, 60–100 scale) ───────────
$totalUnits  = 0;
$weightedSum = 0;
foreach ($grades as $syData) {
    foreach ($syData as $semData) {
        foreach ($semData as $g) {
            $units = (float)($g['units'] ?? 0);
            $grade = ($g['final_grade'] !== null && $g['final_grade'] !== '') ? (float)$g['final_grade'] : 0;
            if ($units > 0 && $grade > 0) {
                $weightedSum += $units * $grade;
                $totalUnits  += $units;
            }
        }
    }
}
$gwa = ($totalUnits > 0) ? number_format($weightedSum / $totalUnits, 2) : 'N/A';

}
if (!$is_curriculum && !$is_student) {
    die("Invalid print request. Please print from the curriculum or student record page.");
}

// ── Grade → remark helper (60–100 numeric scale) ────────────────────
function gradeRemark($grade) {
    $g = (float)$grade;
    if ($g === 0.0)   return '<span class="rem-badge rem-none">No Grade</span>';
    if ($g >= 90)     return '<span class="rem-badge rem-excellent">Excellent</span>';
    if ($g >= 85)     return '<span class="rem-badge rem-verygood">Very Good</span>';
    if ($g >= 80)     return '<span class="rem-badge rem-good">Good</span>';
    if ($g >= 75)     return '<span class="rem-badge rem-passed">Passed</span>';
    return '<span class="rem-badge rem-failed">Failed</span>';
}

// ── GWA standing label ──────────────────────────────────────────────
function gwaStanding($gwa) {
    if ($gwa === 'N/A') return '';
    $g = (float)$gwa;
    if ($g >= 90) return 'With Highest Honors';
    if ($g >= 85) return 'With High Honors';
    if ($g >= 80) return 'With Honors';
    if ($g >= 75) return 'Satisfactory';
    return 'Needs Improvement';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $is_curriculum ? 'Curriculum Document – ' . htmlspecialchars($curriculum['name']) : 'Student Record – ' . htmlspecialchars($student['fname'] . ' ' . $student['lname']) ?></title>

    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <!-- html2pdf for real PDF download -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style>
        /* ─── Base ──────────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; }
        body {
            background: #eef2f7;
            font-family: 'Inter', 'Nunito', sans-serif;
            color: #2d3748;
        }

        /* ─── Wrapper ───────────────────────────────────────────────── */
        .print-wrapper {
            max-width: 960px;
            margin: 2rem auto 3rem;
            padding: 0 1rem;
        }

        /* ─── Action Bar (screen only) ──────────────────────────────── */
        .action-bar {
            display: flex;
            align-items: center;
            gap: .6rem;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
        }
        .action-bar .btn { font-size: .85rem; font-weight: 600; border-radius: .4rem; }

        /* ─── Document Card ─────────────────────────────────────────── */
        .doc-card {
            background: #fff;
            border-radius: .6rem;
            box-shadow: 0 4px 24px rgba(0,0,0,.10);
            overflow: hidden;
        }

        /* ─── School Header ─────────────────────────────────────────── */
        .school-header {
            background: linear-gradient(135deg, #1a3c8f 0%, #2f5fd4 60%, #4e8bff 100%);
            color: #fff;
            padding: 2rem 2.5rem 1.6rem;
            position: relative;
        }
        .school-header::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ffd700, #ffb300, #ffd700);
        }
        .school-header .school-logo-area {
            display: flex;
            align-items: center;
            gap: 1.2rem;
            margin-bottom: .8rem;
        }
        .school-seal {
            width: 64px; height: 64px;
            border-radius: 50%;
            background: rgba(255,255,255,.15);
            border: 2px solid rgba(255,255,255,.4);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
            flex-shrink: 0;
        }
        .school-name { font-size: 1rem; font-weight: 700; letter-spacing: .03rem; opacity: .95; }
        .school-subtitle { font-size: .78rem; opacity: .7; margin-top: .15rem; }
        .doc-title {
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: .04rem;
            text-transform: uppercase;
            text-align: center;
            border-top: 1px solid rgba(255,255,255,.25);
            border-bottom: 1px solid rgba(255,255,255,.25);
            padding: .6rem 0;
            margin: .6rem 0 .5rem;
        }
        .doc-meta {
            display: flex;
            justify-content: space-between;
            font-size: .78rem;
            opacity: .8;
            flex-wrap: wrap;
            gap: .4rem;
        }

        /* ─── Document Body ─────────────────────────────────────────── */
        .doc-body { padding: 2rem 2.5rem; }

        /* ─── Section Title ─────────────────────────────────────────── */
        .sec-title {
            font-size: .75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08rem;
            color: #1a3c8f;
            border-bottom: 2px solid #1a3c8f;
            padding-bottom: .35rem;
            margin-bottom: 1rem;
        }
        .sec-title i { margin-right: .3rem; }

        /* ─── Info Grid ─────────────────────────────────────────────── */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: .9rem 1.5rem;
            margin-bottom: .5rem;
        }
        .info-item label {
            display: block;
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06rem;
            color: #718096;
            margin-bottom: .2rem;
        }
        .info-item span {
            font-size: .92rem;
            font-weight: 600;
            color: #1a202c;
            display: block;
            border-bottom: 1px dashed #e2e8f0;
            padding-bottom: .25rem;
        }
        .info-item.full { grid-column: 1 / -1; }

        /* ─── GWA Summary Box ───────────────────────────────────────── */
        .gwa-box {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            background: linear-gradient(135deg, #ebf0ff, #f0f7ff);
            border: 1px solid #c3d0f5;
            border-radius: .5rem;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .gwa-number {
            font-size: 2.4rem;
            font-weight: 800;
            color: #1a3c8f;
            line-height: 1;
        }
        .gwa-label { font-size: .72rem; font-weight: 700; color: #718096; text-transform: uppercase; letter-spacing: .06rem; }
        .gwa-standing { font-size: .9rem; font-weight: 600; color: #2d3748; margin-top: .2rem; }
        .gwa-units { margin-left: auto; text-align: right; }
        .gwa-units .num { font-size: 1.4rem; font-weight: 700; color: #2d3748; }
        .gwa-units .lbl { font-size: .7rem; color: #718096; text-transform: uppercase; }

        /* ─── Semester Block ────────────────────────────────────────── */
        .sy-block { margin-bottom: 2rem; }
        .sy-heading {
            display: flex;
            align-items: center;
            gap: .6rem;
            font-size: .92rem;
            font-weight: 700;
            color: #1a3c8f;
            background: #eef2fb;
            padding: .55rem 1rem;
            border-radius: .35rem;
            margin-bottom: 1rem;
            border-left: 4px solid #1a3c8f;
        }
        .sem-heading {
            font-size: .82rem;
            font-weight: 700;
            color: #2f5fd4;
            background: #f7f9ff;
            padding: .35rem .8rem;
            border-radius: .25rem;
            margin-bottom: .5rem;
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        /* ─── Grades Table ──────────────────────────────────────────── */
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .84rem;
            margin-bottom: 1rem;
        }
        .grades-table thead tr {
            background: #1a3c8f;
            color: #fff;
        }
        .grades-table th {
            padding: .55rem .75rem;
            font-size: .73rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05rem;
            text-align: left;
            border: 1px solid #1531a0;
        }
        .grades-table th.tc { text-align: center; }
        .grades-table td {
            padding: .5rem .75rem;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .grades-table td.tc { text-align: center; }
        .grades-table tbody tr:nth-child(even) { background: #f8faff; }
        .grades-table tbody tr:hover { background: #eef2ff; }
        .grades-table tfoot td {
            background: #f0f4ff;
            font-weight: 700;
            border: 1px solid #d0d8f0;
        }
        .grade-val {
            font-weight: 700;
            color: #1a202c;
        }

        /* ─── Remark Badges ─────────────────────────────────────────── */
        .rem-badge {
            display: inline-block;
            padding: .2rem .55rem;
            border-radius: .25rem;
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04rem;
        }
        .rem-excellent { background: #c6f6d5; color: #22543d; }
        .rem-verygood  { background: #bee3f8; color: #1a365d; }
        .rem-good      { background: #b2f5ea; color: #1d4044; }
        .rem-passed    { background: #fefcbf; color: #744210; }
        .rem-failed    { background: #fed7d7; color: #742a2a; }
        .rem-none      { background: #e2e8f0; color: #4a5568; }

        /* ─── Signature Block ───────────────────────────────────────── */
        .sig-row {
            display: flex;
            justify-content: space-between;
            margin-top: 3.5rem;
            gap: 1.5rem;
        }
        .sig-block { flex: 1; text-align: center; }
        .sig-line {
            border-top: 1.5px solid #2d3748;
            padding-top: .35rem;
            margin-top: 2.5rem;
            font-size: .8rem;
            font-weight: 700;
            color: #4a5568;
        }
        .sig-sub {
            font-size: .72rem;
            color: #718096;
            margin-top: .15rem;
        }

        /* ─── Footer Line ───────────────────────────────────────────── */
        .doc-footer-line {
            border-top: 1px solid #e2e8f0;
            margin-top: 2rem;
            padding-top: .75rem;
            display: flex;
            justify-content: space-between;
            font-size: .72rem;
            color: #a0aec0;
        }

        /* ─── PDF Loading Overlay ───────────────────────────────────── */
        #pdf-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.45);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            gap: 1rem;
        }
        #pdf-overlay .spinner-border { width: 2.5rem; height: 2.5rem; }

        /* ─── Print Media ───────────────────────────────────────────── */
        @media print {
            body { background: #fff !important; }
            .action-bar, .no-print { display: none !important; }
            .print-wrapper { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
            .doc-card { box-shadow: none !important; border-radius: 0 !important; }
            .school-header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .grades-table thead tr,
            .gwa-box,
            .sy-heading,
            .rem-badge {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

<!-- PDF loading overlay -->
<div id="pdf-overlay">
    <div class="spinner-border text-light" role="status"></div>
    <span>Generating PDF, please wait…</span>
</div>

<div class="print-wrapper">

    <!-- ── Action Bar ──────────────────────────────────────────────── -->
    <div class="action-bar no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print mr-1"></i> Print
        </button>
        <button onclick="exportPDF()" class="btn btn-danger">
            <i class="fas fa-file-pdf mr-1"></i> Save as PDF
        </button>
        <?php if ($is_curriculum): ?>
        <a href="curriculum_view.php?id=<?= $curriculum_id ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Back to Curriculum
        </a>
        <a href="curriculum.php" class="btn btn-outline-secondary ml-auto">
            <i class="fas fa-book-open mr-1"></i> All Curricula
        </a>
        <?php else: ?>
        <a href="student_record.php?id=<?= $id ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Back to Record
        </a>
        <a href="student.php" class="btn btn-outline-secondary ml-auto">
            <i class="fas fa-users mr-1"></i> All Students
        </a>
        <?php endif; ?>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         DOCUMENT START — this entire div is captured for PDF
         ══════════════════════════════════════════════════════════════ -->
    <div class="doc-card" id="printable-area">

        <!-- ── School / Document Header ─────────────────────────────── -->
        <div class="school-header">
            <div class="school-logo-area">
                <div class="school-seal"><i class="fas fa-graduation-cap"></i></div>
                <div>
                    <div class="school-name">LTI Grading System</div>
                        <div class="school-subtitle"><?php echo $is_curriculum ? 'Curriculum Report' : 'Official Academic Record'; ?></div>
                    </div>
                    <div class="ml-auto text-right no-print" style="opacity:.75; font-size:.78rem;">
                        Generated: <?= date('F j, Y h:i A') ?>
                    </div>
                </div>
                <div class="doc-title"><?= $is_curriculum ? 'Curriculum Document' : 'Student Academic Transcript' ?></div>
                <div class="doc-meta">
                    <?php if ($is_curriculum): ?>
                        <span><i class="fas fa-book-open mr-1"></i> <?= htmlspecialchars($curriculum['name']) ?></span>
                        <span><i class="fas fa-calendar-alt mr-1"></i> <?= htmlspecialchars($curriculum['date']) ?></span>
                        <span><i class="fas fa-list mr-1"></i> Curriculum ID: <?= $curriculum_id ?></span>
                    <?php else: ?>
                        <span><i class="fas fa-user mr-1"></i>
                            <?= htmlspecialchars(trim($student['fname'] . ' ' . ($student['mname'] ? $student['mname'] . ' ' : '') . $student['lname'])) ?>
                        </span>
                        <span><i class="fas fa-id-card mr-1"></i> Student ID: <?= $id ?></span>
                        <span><i class="fas fa-calendar-alt mr-1"></i> <?= date('F j, Y') ?></span>
                    <?php endif; ?>
                </div><!-- end doc-meta -->
        </div><!-- end school-header -->

        <div class="doc-body">
            <?php if ($is_curriculum): ?>
                <div class="sec-title"><i class="fas fa-book-open"></i> Curriculum Details</div>
                <div class="info-grid" style="margin-bottom:1.5rem;">
                    <div class="info-item">
                        <label>Curriculum Name</label>
                        <span><?= htmlspecialchars($curriculum['name']) ?></span>
                    </div>
                    <div class="info-item">
                        <label>School Year</label>
                        <span><?= htmlspecialchars($curriculum['date']) ?></span>
                    </div>
                    <div class="info-item full">
                        <label>Total Subjects</label>
                        <span><?= $total_subjects ?></span>
                    </div>
                </div>

                <div class="sec-title"><i class="fas fa-list-alt"></i> Curriculum Subjects</div>
                <?php if (empty($grades)): ?>
                    <div class="alert alert-info" style="border-radius:.4rem;">
                        <i class="fas fa-info-circle mr-1"></i>
                        No subjects found for this curriculum.
                    </div>
                <?php else: ?>
                    <?php foreach ($grades as $course): ?>
                        <div class="sy-block">
                            <div class="sy-heading" style="background-color:#f0f4ff;">
                                <i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($course['course_code'] ?: 'General') ?>
                                <?= $course['course_name'] ? '— ' . htmlspecialchars($course['course_name']) : '' ?>
                            </div>
                            <?php foreach ($course['years'] as $year => $sems): ?>
                                <div class="mb-3">
                                    <div class="sem-heading">
                                        <i class="fas fa-layer-group"></i>
                                        <?= isset($year_label[$year]) ? $year_label[$year] : 'Year ' . htmlspecialchars($year) ?>
                                    </div>
                                    <?php foreach ($sems as $sem => $subjects): ?>
                                        <div class="mb-2">
                                            <div class="sem-heading" style="font-size:.95rem; margin-bottom:.5rem; background:#e9f2fb;">
                                                <i class="fas fa-calendar-week"></i>
                                                <?= isset($sem_label[$sem]) ? $sem_label[$sem] : htmlspecialchars($sem) ?>
                                            </div>
                                            <table class="grades-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width:40px;">#</th>
                                                        <th style="width:140px;">Subject Code</th>
                                                        <th>Subject Name</th>
                                                        <th class="tc" style="width:80px;">Units</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($subjects as $i => $subj): ?>
                                                    <tr>
                                                        <td class="tc"><?= $i + 1 ?></td>
                                                        <td><?= htmlspecialchars($subj['subject_code'] ?? '—') ?></td>
                                                        <td><?= htmlspecialchars($subj['subject_name'] ?? '—') ?></td>
                                                        <td class="tc"><?= htmlspecialchars($subj['unit'] ?? '—') ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php else: ?>
                <!-- ── Personal Information ─────────────────────────────── -->
                <div class="sec-title"><i class="fas fa-user"></i> Personal Information</div>
                <div class="info-grid" style="margin-bottom:1.5rem;">
                    <div class="info-item">
                        <label>First Name</label>
                        <span><?= htmlspecialchars($student['fname'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-item">
                        <label>Middle Name</label>
                        <span><?= htmlspecialchars($student['mname'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-item">
                        <label>Last Name</label>
                        <span><?= htmlspecialchars($student['lname'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-item">
                        <label>Date of Birth</label>
                        <span><?= !empty($student['dob']) ? date('F j, Y', strtotime($student['dob'])) : 'N/A' ?></span>
                    </div>
                    <div class="info-item">
                        <label>Email Address</label>
                        <span><?= htmlspecialchars($student['email'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-item">
                        <label>Phone Number</label>
                        <span><?= htmlspecialchars($student['phone'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-item full">
                        <label>Home Address</label>
                        <span><?= htmlspecialchars($student['address'] ?? 'N/A') ?></span>
                    </div>
                </div>

                <!-- ── Academic Information ─────────────────────────────── -->
                <div class="sec-title"><i class="fas fa-university"></i> Academic Information</div>
                <div class="info-grid" style="margin-bottom:1.5rem;">
                    <div class="info-item">
                        <label>Course / Program</label>
                        <span><?= htmlspecialchars($course_name) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Year Level</label>
                        <span>Year <?= htmlspecialchars($student['year'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-item">
                        <label>Curriculum</label>
                        <span><?= htmlspecialchars($student['curriculum'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-item">
                        <label>Total Units Earned</label>
                        <span><?= $totalUnits > 0 ? $totalUnits . ' units' : 'N/A' ?></span>
                    </div>
                </div>

                <!-- ── GWA Summary ───────────────────────────────────────── -->
                <?php if ($gwa !== 'N/A'): ?>
                <div class="gwa-box">
                    <div>
                        <div class="gwa-label">General Weighted Average</div>
                        <div class="gwa-number"><?= $gwa ?></div>
                        <div class="gwa-standing"><?= gwaStanding($gwa) ?></div>
                    </div>
                    <div class="gwa-units">
                        <div class="lbl">Total Units Earned</div>
                        <div class="num"><?= $totalUnits ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ── Grades & Subjects ─────────────────────────────────── -->
                <div class="sec-title"><i class="fas fa-list-alt"></i> Grades &amp; Subjects</div>

                <?php if (empty($grades)): ?>
                    <div class="alert alert-info" style="border-radius:.4rem;">
                        <i class="fas fa-info-circle mr-1"></i>
                        No grade records found for this student.
                    </div>
                <?php else: ?>
                <?php foreach ($grades as $sy => $semesters): ?>
                <div class="sy-block">
                    <div class="sy-heading">
                        <i class="fas fa-calendar-alt"></i>
                        School Year <?= htmlspecialchars($sy) ?>
                    </div>

                    <?php foreach ($semesters as $sem => $subjects): ?>
                    <div class="mb-3">
                        <div class="sem-heading">
                            <i class="fas fa-book-open"></i>
                            <?= htmlspecialchars($sem) ?> Semester
                        </div>

                        <table class="grades-table">
                            <thead>
                                <tr>
                                    <th style="width:40px;">#</th>
                                    <th style="width:120px;">Subject Code</th>
                                    <th>Subject Name</th>
                                    <th class="tc" style="width:70px;">Units</th>
                                    <th class="tc" style="width:75px;">Pre-Mid</th>
                                    <th class="tc" style="width:75px;">Midterm</th>
                                    <th class="tc" style="width:75px;">Pre-Final</th>
                                    <th class="tc" style="width:75px;">Final</th>
                                    <th class="tc" style="width:85px;">Final Grade</th>
                                    <th class="tc" style="width:120px;">Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $semUnits = 0;
                                foreach ($subjects as $i => $g):
                                    $semUnits += (float)($g['units'] ?? 0);
                                ?>
                                <tr>
                                    <td class="tc"><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($g['subject_code'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($g['subject_name'] ?? '—') ?></td>
                                    <td class="tc"><?= htmlspecialchars($g['units'] ?? '—') ?></td>
                                    <td class="tc"><?= $g['pre_mid'] !== null && $g['pre_mid'] !== '' ? number_format((float)$g['pre_mid'], 2) : '—' ?></td>
                                    <td class="tc"><?= $g['midterm'] !== null && $g['midterm'] !== '' ? number_format((float)$g['midterm'], 2) : '—' ?></td>
                                    <td class="tc"><?= $g['pre_final'] !== null && $g['pre_final'] !== '' ? number_format((float)$g['pre_final'], 2) : '—' ?></td>
                                    <td class="tc"><?= $g['final'] !== null && $g['final'] !== '' ? number_format((float)$g['final'], 2) : '—' ?></td>
                                    <td class="tc"><?= $g['final_grade'] !== null && $g['final_grade'] !== '' ? number_format((float)$g['final_grade'], 2) : '—' ?></td>
                                    <td class="tc"><?= htmlspecialchars($g['remarks'] ?? '—') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" style="text-align:right; padding-right:1rem;">Semester Total Units:</td>
                                    <td class="tc"><?= $semUnits ?></td>
                                    <td colspan="6"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!$is_curriculum): ?>
            <!-- ── Remark Legend ─────────────────────────────────────── -->
            <div class="remark-legend">
                <span class="legend-label">Grading Scale:</span>
                <span class="rem-badge rem-excellent">Excellent (90–100)</span>
                <span class="rem-badge rem-verygood">Very Good (85–89)</span>
                <span class="rem-badge rem-good">Good (80–84)</span>
                <span class="rem-badge rem-passed">Passed (75–79)</span>
                <span class="rem-badge rem-failed">Failed (&lt;75)</span>
            </div>
            <?php endif; ?>

            <!-- ── Signature Block ───────────────────────────────────── -->
            <div class="sig-row">
                <?php if ($is_curriculum): ?>
                <div class="sig-block">
                    <div class="sig-line">Curriculum Committee Chair</div>
                    <div class="sig-sub">Signature over Printed Name &amp; Date</div>
                </div>
                <div class="sig-block">
                    <div class="sig-line">Academic Director</div>
                    <div class="sig-sub">Signature over Printed Name &amp; Date</div>
                </div>
                <div class="sig-block">
                    <div class="sig-line">School President / Director</div>
                    <div class="sig-sub">Signature over Printed Name &amp; Date</div>
                </div>
                <?php else: ?>
                <div class="sig-block">
                    <div class="sig-line">Student Signature over Printed Name</div>
                    <div class="sig-sub">Date: ____________________</div>
                </div>
                <div class="sig-block">
                    <div class="sig-line">Registrar / Administrator</div>
                    <div class="sig-sub">Date: ____________________</div>
                </div>
                <div class="sig-block">
                    <div class="sig-line">School Director / Principal</div>
                    <div class="sig-sub">Date: ____________________</div>
                </div>
                <?php endif; ?>
            </div>

            <!-- ── Official Certification Note ──────────────────────── -->
            <div class="certification-note">
                <i class="fas fa-stamp mr-1"></i>
                <?php if ($is_curriculum): ?>
                    This curriculum document has been officially reviewed and approved by the Academic Committee of LTI Grading System.
                <?php else: ?>
                    This is a certified true and correct copy of the official academic records of the above-named student from LTI Grading System.
                <?php endif; ?>
            </div>

            <!-- ── Document Footer ───────────────────────────────────── -->
            <div class="doc-footer-line">
                <span><i class="fas fa-shield-alt mr-1"></i> Official Document — LTI Grading System</span>
                <span>Printed: <?= date('F j, Y \a\t h:i A') ?></span>
            </div>

        </div><!-- end doc-body -->
    </div><!-- end doc-card / printable-area -->
</div><!-- end print-wrapper -->

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    // ── Auto-print on ?autoprint=1 ──────────────────────────────────
    <?php if (isset($_GET['autoprint']) && $_GET['autoprint'] == '1'): ?>
    window.addEventListener('load', function () { setTimeout(window.print, 500); });
    <?php endif; ?>

    // ── Real PDF download via html2pdf.js ───────────────────────────
    function exportPDF() {
        const overlay  = document.getElementById('pdf-overlay');
        const element  = document.getElementById('printable-area');
        <?php if ($is_curriculum): ?>
        const filename = 'Curriculum_<?= preg_replace('/[^A-Za-z0-9_-]/', '_', $curriculum['name']) ?>_<?= date('Y-m-d') ?>.pdf';
        <?php else: ?>
        const filename = 'Student_Record_<?= $id ?>_<?= htmlspecialchars(str_replace(' ', '_', $student['lname'] . '_' . $student['fname'])) ?>_' +
                         new Date().toISOString().slice(0, 10) + '.pdf';
        <?php endif; ?>

        // Show loading overlay
        overlay.style.display = 'flex';

        const opt = {
            margin:       [8, 8, 8, 8],          // mm: top, right, bottom, left
            filename:     filename,
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  {
                scale:        2,
                useCORS:      true,
                logging:      false,
                letterRendering: true
            },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
            pagebreak:    { mode: ['avoid-all', 'css', 'legacy'] }
        };

        html2pdf()
            .set(opt)
            .from(element)
            .save()
            .then(function () {
                overlay.style.display = 'none';
            })
            .catch(function (err) {
                overlay.style.display = 'none';
                alert('PDF generation failed: ' + err.message);
            });
    }
</script>
</body>
</html>
