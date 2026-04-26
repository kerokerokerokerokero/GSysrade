<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

$curriculum_id = isset($_GET['curriculum_id']) && is_numeric($_GET['curriculum_id']) ? intval($_GET['curriculum_id']) : 0;
$is_curriculum = $curriculum_id > 0;
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
} else {
    // Validate student ID
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        die("Invalid student ID.");
    }

    $id = intval($_GET['id']);
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
} // end else
?>