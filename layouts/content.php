<?php
// ════════════════════════════════════════════════
//  DASHBOARD DATA — all queries run before HTML
// ════════════════════════════════════════════════
require_once __DIR__ . '/../db.php';

// ── Stat card counts ─────────────────────────────
$count_students  = $conn->query("SELECT COUNT(*) AS c FROM student")         ->fetch_assoc()['c'] ?? 0;
$count_faculty   = $conn->query("SELECT COUNT(*) AS c FROM faculty")          ->fetch_assoc()['c'] ?? 0;
$count_courses   = $conn->query("SELECT COUNT(*) AS c FROM course")           ->fetch_assoc()['c'] ?? 0;
$count_curricula = $conn->query("SELECT COUNT(*) AS c FROM crurriculum")      ->fetch_assoc()['c'] ?? 0;
$count_subjects  = $conn->query("SELECT COUNT(*) AS c FROM subject")          ->fetch_assoc()['c'] ?? 0;
$count_records   = $conn->query("SELECT COUNT(*) AS c FROM student_record")   ->fetch_assoc()['c'] ?? 0;

// ── Chart 1 – Students per Course (Bar) ──────────
$students_per_course_labels = [];
$students_per_course_data   = [];
$spc = $conn->query("
    SELECT c.code AS label, COUNT(s.id) AS total
    FROM course c
    LEFT JOIN student s ON s.course = c.id
    GROUP BY c.id
    ORDER BY total DESC
");
if ($spc) {
    while ($r = $spc->fetch_assoc()) {
        $students_per_course_labels[] = $r['label'];
        $students_per_course_data[]   = (int)$r['total'];
    }
}

// ── Chart 2 – Students per Year Level (Doughnut) ─
$students_per_year_labels = [];
$students_per_year_data   = [];
$spy = $conn->query("
    SELECT year AS label, COUNT(*) AS total
    FROM student
    GROUP BY year
    ORDER BY year ASC
");
if ($spy) {
    while ($r = $spy->fetch_assoc()) {
        $students_per_year_labels[] = 'Year ' . htmlspecialchars($r['label']);
        $students_per_year_data[]   = (int)$r['total'];
    }
}

// ── Chart 3 – Records per School Year (Line) ─────
$records_per_sy_labels = [];
$records_per_sy_data   = [];
$rsy = $conn->query("
    SELECT school_year AS label, COUNT(*) AS total
    FROM student_record
    GROUP BY school_year
    ORDER BY school_year ASC
");
if ($rsy) {
    while ($r = $rsy->fetch_assoc()) {
        $records_per_sy_labels[] = $r['label'];
        $records_per_sy_data[]   = (int)$r['total'];
    }
}

// ── Chart 4 – Subjects per Unit Count (Horizontal Bar) ─
$units_labels = [];
$units_data   = [];
$uq = $conn->query("
    SELECT unit AS label, COUNT(*) AS total
    FROM subject
    GROUP BY unit
    ORDER BY unit ASC
");
if ($uq) {
    while ($r = $uq->fetch_assoc()) {
        $units_labels[] = $r['label'] . ' unit' . ($r['label'] != 1 ? 's' : '');
        $units_data[]   = (int)$r['total'];
    }
}

// ── Recent Students (last 5) ──────────────────────
$recent_students = [];
$rs = $conn->query("
    SELECT s.fname, s.lname, s.year, c.code AS course_code
    FROM student s
    LEFT JOIN course c ON c.id = s.course
    ORDER BY s.id DESC
    LIMIT 5
");
if ($rs) {
    while ($r = $rs->fetch_assoc()) $recent_students[] = $r;
}

// ── Recent Faculty (last 5) ───────────────────────
$recent_faculty = [];
$rf = $conn->query("SELECT first_name, last_name, email FROM faculty ORDER BY id DESC LIMIT 5");
if ($rf) {
    while ($r = $rf->fetch_assoc()) $recent_faculty[] = $r;
}
?>

<!-- Topbar -->
<?php include __DIR__ . '/topbar.php'; ?>

<!-- ════════════════════════════════════════════════
     BEGIN PAGE CONTENT
     ════════════════════════════════════════════════ -->
<div class="container-fluid" id="dashboard-content">

  <!-- Page Heading -->
  <div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
      <i class="fas fa-tachometer-alt mr-2 text-primary"></i>Dashboard
    </h1>
    <small class="text-muted">
      <i class="fas fa-sync-alt mr-1"></i>
      Last updated: <span id="last-updated"><?= date('M d, Y h:i A') ?></span>
    </small>
  </div>

  <!-- ══ STAT CARDS ══════════════════════════════ -->
  <div class="row">

    <!-- Students -->
    <div class="col-xl-2 col-md-4 mb-4">
      <div class="card border-left-primary shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Students</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-students"><?= $count_students ?></div>
            </div>
            <div class="col-auto"><i class="fas fa-user-graduate fa-2x text-gray-300"></i></div>
          </div>
        </div>
        <a href="student.php" class="card-footer text-primary text-xs text-center py-1 stretched-link" style="text-decoration:none;">
          View all <i class="fas fa-arrow-right ml-1"></i>
        </a>
      </div>
    </div>

    <!-- Faculty -->
    <div class="col-xl-2 col-md-4 mb-4">
      <div class="card border-left-success shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Faculty</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-faculty"><?= $count_faculty ?></div>
            </div>
            <div class="col-auto"><i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i></div>
          </div>
        </div>
        <a href="faculty.php" class="card-footer text-success text-xs text-center py-1 stretched-link" style="text-decoration:none;">
          View all <i class="fas fa-arrow-right ml-1"></i>
        </a>
      </div>
    </div>

    <!-- Courses -->
    <div class="col-xl-2 col-md-4 mb-4">
      <div class="card border-left-info shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Courses</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-courses"><?= $count_courses ?></div>
            </div>
            <div class="col-auto"><i class="fas fa-book fa-2x text-gray-300"></i></div>
          </div>
        </div>
        <a href="course.php" class="card-footer text-info text-xs text-center py-1 stretched-link" style="text-decoration:none;">
          View all <i class="fas fa-arrow-right ml-1"></i>
        </a>
      </div>
    </div>

    <!-- Curricula -->
    <div class="col-xl-2 col-md-4 mb-4">
      <div class="card border-left-warning shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Curricula</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-curricula"><?= $count_curricula ?></div>
            </div>
            <div class="col-auto"><i class="fas fa-sitemap fa-2x text-gray-300"></i></div>
          </div>
        </div>
        <a href="curriculum.php" class="card-footer text-warning text-xs text-center py-1 stretched-link" style="text-decoration:none;">
          View all <i class="fas fa-arrow-right ml-1"></i>
        </a>
      </div>
    </div>

    <!-- Subjects -->
    <div class="col-xl-2 col-md-4 mb-4">
      <div class="card border-left-danger shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Subjects</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-subjects"><?= $count_subjects ?></div>
            </div>
            <div class="col-auto"><i class="fas fa-clipboard-list fa-2x text-gray-300"></i></div>
          </div>
        </div>
        <a href="subject.php" class="card-footer text-danger text-xs text-center py-1 stretched-link" style="text-decoration:none;">
          View all <i class="fas fa-arrow-right ml-1"></i>
        </a>
      </div>
    </div>

    <!-- Records -->
    <div class="col-xl-2 col-md-4 mb-4">
      <div class="card border-left-secondary shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Records</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-records"><?= $count_records ?></div>
            </div>
            <div class="col-auto"><i class="fas fa-file-alt fa-2x text-gray-300"></i></div>
          </div>
        </div>
        <div class="card-footer text-secondary text-xs text-center py-1">
          Student Records
        </div>
      </div>
    </div>

  </div><!-- /row stat cards -->

  <!-- ══ CHARTS ROW 1 ════════════════════════════ -->
  <div class="row">

    <!-- Bar – Students per Course -->
    <div class="col-xl-8 col-lg-7 mb-4">
      <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
          <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-chart-bar mr-2"></i>Students per Course
          </h6>
          <span class="badge badge-primary badge-pill"><?= $count_students ?> total</span>
        </div>
        <div class="card-body">
          <?php if (empty($students_per_course_data)): ?>
            <p class="text-center text-muted py-4"><i class="fas fa-info-circle mr-1"></i>No data yet.</p>
          <?php else: ?>
            <div class="chart-bar" style="position:relative; height:280px;">
              <canvas id="chartStudentsPerCourse"></canvas>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Doughnut – Students per Year Level -->
    <div class="col-xl-4 col-lg-5 mb-4">
      <div class="card shadow">
        <div class="card-header py-3">
          <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-chart-pie mr-2"></i>Students per Year Level
          </h6>
        </div>
        <div class="card-body d-flex flex-column align-items-center">
          <?php if (empty($students_per_year_data)): ?>
            <p class="text-center text-muted py-4"><i class="fas fa-info-circle mr-1"></i>No data yet.</p>
          <?php else: ?>
            <div style="position:relative; height:240px; width:100%;">
              <canvas id="chartYearLevel"></canvas>
            </div>
            <div class="mt-3 w-100" id="yearLevelLegend" style="font-size:0.78rem;"></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div><!-- /charts row 1 -->

  <!-- ══ CHARTS ROW 2 ════════════════════════════ -->
  <div class="row">

    <!-- Line – Records per School Year -->
    <div class="col-xl-8 col-lg-7 mb-4">
      <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
          <h6 class="m-0 font-weight-bold text-success">
            <i class="fas fa-chart-line mr-2"></i>Student Records per School Year
          </h6>
          <span class="badge badge-success badge-pill"><?= $count_records ?> total</span>
        </div>
        <div class="card-body">
          <?php if (empty($records_per_sy_data)): ?>
            <p class="text-center text-muted py-4"><i class="fas fa-info-circle mr-1"></i>No records yet.</p>
          <?php else: ?>
            <div style="position:relative; height:280px;">
              <canvas id="chartRecordsPerSY"></canvas>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Horizontal Bar – Subjects by Unit Count -->
    <div class="col-xl-4 col-lg-5 mb-4">
      <div class="card shadow">
        <div class="card-header py-3">
          <h6 class="m-0 font-weight-bold text-info">
            <i class="fas fa-layer-group mr-2"></i>Subjects by Unit Count
          </h6>
        </div>
        <div class="card-body">
          <?php if (empty($units_data)): ?>
            <p class="text-center text-muted py-4"><i class="fas fa-info-circle mr-1"></i>No subjects yet.</p>
          <?php else: ?>
            <div style="position:relative; height:240px;">
              <canvas id="chartUnitDist"></canvas>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div><!-- /charts row 2 -->

  <!-- ══ RECENT ACTIVITY TABLES ══════════════════ -->
  <div class="row">

    <!-- Recent Students -->
    <div class="col-lg-6 mb-4">
      <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
          <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-user-clock mr-2"></i>Recently Added Students
          </h6>
          <a href="student.php" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead class="thead-light">
                <tr>
                  <th>Name</th>
                  <th>Course</th>
                  <th>Year</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($recent_students)): ?>
                  <tr><td colspan="3" class="text-center text-muted py-3">No students yet.</td></tr>
                <?php else: ?>
                  <?php foreach ($recent_students as $s): ?>
                    <tr>
                      <td><?= htmlspecialchars($s['fname'] . ' ' . $s['lname']) ?></td>
                      <td><span class="badge badge-primary"><?= htmlspecialchars($s['course_code'] ?? '—') ?></span></td>
                      <td><?= htmlspecialchars($s['year']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Faculty -->
    <div class="col-lg-6 mb-4">
      <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
          <h6 class="m-0 font-weight-bold text-success">
            <i class="fas fa-user-tie mr-2"></i>Recently Added Faculty
          </h6>
          <a href="faculty.php" class="btn btn-sm btn-outline-success">View All</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead class="thead-light">
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($recent_faculty)): ?>
                  <tr><td colspan="2" class="text-center text-muted py-3">No faculty yet.</td></tr>
                <?php else: ?>
                  <?php foreach ($recent_faculty as $f): ?>
                    <tr>
                      <td><?= htmlspecialchars($f['first_name'] . ' ' . $f['last_name']) ?></td>
                      <td class="text-muted" style="font-size:0.82rem;"><?= htmlspecialchars($f['email']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /recent tables -->

</div><!-- /.container-fluid -->

<!-- ════════════════════════════════════════════════
     CHART.JS — render all four charts
     ════════════════════════════════════════════════ -->
<script>
(function () {

  var PALETTE = ['#4e73df','#1cc88a','#36b9cc','#f6c23e','#e74a3b','#858796','#5a5c69','#2e59d9'];

  // ── 1. Students per Course (Bar) ─────────────────
  <?php if (!empty($students_per_course_data)): ?>
  (function () {
    var ctx = document.getElementById('chartStudentsPerCourse').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: <?= json_encode($students_per_course_labels) ?>,
        datasets: [{
          label: 'Students',
          data: <?= json_encode($students_per_course_data) ?>,
          backgroundColor: PALETTE,
          borderColor: PALETTE,
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        legend: { display: false },
        scales: {
          yAxes: [{ ticks: { beginAtZero: true, stepSize: 1, precision: 0 } }],
          xAxes: [{ gridLines: { display: false } }]
        },
        tooltips: {
          callbacks: {
            label: function(ti) { return ' ' + ti.yLabel + ' student(s)'; }
          }
        }
      }
    });
  })();
  <?php endif; ?>

  // ── 2. Students per Year Level (Doughnut) ────────
  <?php if (!empty($students_per_year_data)): ?>
  (function () {
    var ctx = document.getElementById('chartYearLevel').getContext('2d');
    var labels = <?= json_encode($students_per_year_labels) ?>;
    var data   = <?= json_encode($students_per_year_data) ?>;
    var total  = data.reduce(function(a,b){ return a+b; }, 0);
    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{ data: data, backgroundColor: PALETTE, hoverOffset: 6 }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        legend: { display: false },
        cutoutPercentage: 65,
        tooltips: {
          callbacks: {
            label: function(ti, d) {
              var v = d.datasets[0].data[ti.index];
              return ' ' + d.labels[ti.index] + ': ' + v + ' (' + Math.round(v/total*100) + '%)';
            }
          }
        }
      }
    });
    // custom legend
    var legend = document.getElementById('yearLevelLegend');
    labels.forEach(function(lbl, i) {
      legend.innerHTML +=
        '<span class="mr-3"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:' +
        PALETTE[i % PALETTE.length] + ';margin-right:4px;"></span>' + lbl + ': <strong>' + data[i] + '</strong></span>';
    });
  })();
  <?php endif; ?>

  // ── 3. Records per School Year (Line) ────────────
  <?php if (!empty($records_per_sy_data)): ?>
  (function () {
    var ctx = document.getElementById('chartRecordsPerSY').getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?= json_encode($records_per_sy_labels) ?>,
        datasets: [{
          label: 'Records Created',
          data: <?= json_encode($records_per_sy_data) ?>,
          borderColor: '#1cc88a',
          backgroundColor: 'rgba(28,200,138,0.08)',
          pointBackgroundColor: '#1cc88a',
          pointRadius: 5,
          fill: true,
          tension: 0.3
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          yAxes: [{ ticks: { beginAtZero: true, stepSize: 1, precision: 0 } }],
          xAxes: [{ gridLines: { color: 'rgba(0,0,0,0.05)' } }]
        },
        tooltips: {
          callbacks: {
            label: function(ti) { return ' ' + ti.yLabel + ' record(s)'; }
          }
        }
      }
    });
  })();
  <?php endif; ?>

  // ── 4. Subjects by Unit Count (Horizontal Bar) ───
  <?php if (!empty($units_data)): ?>
  (function () {
    var ctx = document.getElementById('chartUnitDist').getContext('2d');
    new Chart(ctx, {
      type: 'horizontalBar',
      data: {
        labels: <?= json_encode($units_labels) ?>,
        datasets: [{
          label: 'No. of Subjects',
          data: <?= json_encode($units_data) ?>,
          backgroundColor: '#36b9cc',
          borderColor: '#2c9faf',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        legend: { display: false },
        scales: {
          xAxes: [{ ticks: { beginAtZero: true, stepSize: 1, precision: 0 } }],
          yAxes: [{ gridLines: { display: false } }]
        },
        tooltips: {
          callbacks: {
            label: function(ti) { return ' ' + ti.xLabel + ' subject(s)'; }
          }
        }
      }
    });
  })();
  <?php endif; ?>

  // ── Auto-refresh stat counts every 30 seconds ────
  function refreshStats() {
    fetch('dashboard_stats.php')
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (d.students  !== undefined) document.getElementById('stat-students').textContent  = d.students;
        if (d.faculty   !== undefined) document.getElementById('stat-faculty').textContent   = d.faculty;
        if (d.courses   !== undefined) document.getElementById('stat-courses').textContent   = d.courses;
        if (d.curricula !== undefined) document.getElementById('stat-curricula').textContent = d.curricula;
        if (d.subjects  !== undefined) document.getElementById('stat-subjects').textContent  = d.subjects;
        if (d.records   !== undefined) document.getElementById('stat-records').textContent   = d.records;
        document.getElementById('last-updated').textContent = d.timestamp;
      })
      .catch(function(){});
  }
  setInterval(refreshStats, 30000);

})();
</script>
