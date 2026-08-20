<?php
require_login();

$fy = (int)request_get('fy', current_fiscal_year_be());
$mode = request_get('mode', 'fiscal');
$date_from = request_get('date_from', '');
$date_to = request_get('date_to', '');

if ($mode == 'custom' && $date_from != '' && $date_to != '') {
    $start = $date_from;
    $end = $date_to;
} else {
    $mode = 'fiscal';
    $range = fiscal_year_range($fy);
    $start = $range['start'];
    $end = $range['end'];
}

$where = "DATE(jobs.created_at) BETWEEN '" . db_esc($start) . "' AND '" . db_esc($end) . "'";
$total = db_one("SELECT COUNT(*) AS c FROM jobs WHERE $where");
$by_status = db_all("SELECT status, COUNT(*) AS c FROM jobs WHERE $where GROUP BY status");
$by_category = db_all("SELECT job_categories.name AS label, COUNT(*) AS c FROM jobs LEFT JOIN job_categories ON job_categories.id = jobs.category_id WHERE $where GROUP BY jobs.category_id, job_categories.name ORDER BY c DESC");
$by_dept = db_all("SELECT departments.name AS label, COUNT(*) AS c FROM jobs LEFT JOIN departments ON departments.id = jobs.department_id WHERE $where GROUP BY jobs.department_id, departments.name ORDER BY c DESC");
$by_user = db_all("SELECT IFNULL(users.fullname,'ยังไม่มอบหมาย') AS label, COUNT(*) AS c FROM jobs LEFT JOIN users ON users.id = jobs.assignee_id WHERE $where GROUP BY jobs.assignee_id, users.fullname ORDER BY c DESC");

function chart_payload($rows) {
    $labels = array();
    $data = array();
    foreach ($rows as $r) {
        $labels[] = $r['label'];
        $data[] = (int)$r['c'];
    }
    return array('labels' => $labels, 'data' => $data);
}

$cat_json = json_encode(chart_payload($by_category));
$dept_json = json_encode(chart_payload($by_dept));
$user_json = json_encode(chart_payload($by_user));
$status_rows = array();
foreach ($by_status as $r) {
    $status_rows[] = array('label' => status_label($r['status']), 'c' => $r['c']);
}
$status_json = json_encode(chart_payload($status_rows));

$fy_opts = array();
$cur = current_fiscal_year_be();
$i = 0;
while ($i < 6) {
    $fy_opts[] = $cur - $i;
    $i++;
}

layout_start('รายงาน', 'reports');
?>
<h1 class="page-title">รายงานงานบริการ</h1>
<form class="filters" method="get" action="index.php" style="grid-template-columns: 1fr 1fr 1fr 1fr auto;">
  <input type="hidden" name="p" value="reports" />
  <select name="mode">
    <option value="fiscal" <?php if ($mode == 'fiscal') echo 'selected'; ?>>ปีงบประมาณ</option>
    <option value="custom" <?php if ($mode == 'custom') echo 'selected'; ?>>ช่วงวันที่</option>
  </select>
  <select name="fy">
    <?php foreach ($fy_opts as $opt) { ?>
      <option value="<?php echo (int)$opt; ?>" <?php if ($fy == $opt) echo 'selected'; ?>>ปีงบ <?php echo (int)$opt; ?></option>
    <?php } ?>
  </select>
  <input type="date" name="date_from" value="<?php echo h($date_from); ?>" />
  <input type="date" name="date_to" value="<?php echo h($date_to); ?>" />
  <button class="btn" type="submit">แสดงรายงาน</button>
</form>
<p class="muted">ช่วงข้อมูล <?php echo h(thai_date($start)); ?> ถึง <?php echo h(thai_date($end)); ?> · รวม <?php echo (int)$total['c']; ?> งาน</p>

<div class="kpi-grid">
  <?php foreach ($by_status as $r) { ?>
    <div class="kpi"><div class="lbl"><?php echo h(status_label($r['status'])); ?></div><div class="num"><?php echo (int)$r['c']; ?></div></div>
  <?php } ?>
</div>

<div class="card-grid" style="grid-template-columns:1fr 1fr;">
  <div class="panel"><h2>ตามหมวดงาน</h2><canvas id="chartCat" class="chart-box"></canvas></div>
  <div class="panel"><h2>ตามสถานะ</h2><canvas id="chartStatus" class="chart-box"></canvas></div>
  <div class="panel"><h2>ตามหน่วยงาน</h2><canvas id="chartDept" class="chart-box"></canvas></div>
  <div class="panel"><h2>ตามผู้รับงาน</h2><canvas id="chartUser" class="chart-box"></canvas></div>
</div>

<div class="panel">
  <h2>สรุปตามหน่วยงาน</h2>
  <table class="table-snk">
    <thead><tr><th>หน่วยงาน</th><th>จำนวน</th></tr></thead>
    <tbody>
      <?php foreach ($by_dept as $r) { ?>
        <tr><td><?php echo h($r['label']); ?></td><td><?php echo (int)$r['c']; ?></td></tr>
      <?php } ?>
    </tbody>
  </table>
</div>
<?php
$js = '<script src="assets/js/Chart.min.js"></script><script>
function drawChart(id, type, payload) {
  var ctx = document.getElementById(id);
  if (!ctx) return;
  new Chart(ctx.getContext("2d"), {
    type: type,
    data: {
      labels: payload.labels,
      datasets: [{ data: payload.data, backgroundColor: ["#60a5fa","#2563eb","#93c5fd","#1d4ed8","#c5a572","#38bdf8","#818cf8","#64748b"] }]
    },
    options: { responsive: true, maintainAspectRatio: false, legend: { display: type === "pie" } }
  });
}
drawChart("chartCat", "bar", ' . $cat_json . ');
drawChart("chartStatus", "pie", ' . $status_json . ');
drawChart("chartDept", "bar", ' . $dept_json . ');
drawChart("chartUser", "bar", ' . $user_json . ');
</script>';
layout_end($js);
