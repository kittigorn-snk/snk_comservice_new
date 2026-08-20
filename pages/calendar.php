<?php
$month = (int)request_get('m', date('n'));
$year = (int)request_get('y', date('Y'));
if ($month < 1 || $month > 12) { $month = (int)date('n'); }
if ($year < 2000 || $year > 2100) { $year = (int)date('Y'); }

$prev_m = $month - 1;
$prev_y = $year;
if ($prev_m < 1) { $prev_m = 12; $prev_y--; }
$next_m = $month + 1;
$next_y = $year;
if ($next_m > 12) { $next_m = 1; $next_y++; }

$start = sprintf('%04d-%02d-01', $year, $month);
$days = (int)date('t', strtotime($start));
$end = sprintf('%04d-%02d-%02d', $year, $month, $days);
$jobs = db_all(job_select_sql() . " WHERE DATE(jobs.created_at) BETWEEN '" . db_esc($start) . "' AND '" . db_esc($end) . "' ORDER BY jobs.id");
$by_day = array();
foreach ($jobs as $job) {
    $d = (int)substr($job['created_at'], 8, 2);
    if (!isset($by_day[$d])) { $by_day[$d] = array(); }
    $by_day[$d][] = $job;
}

$first_w = (int)date('w', strtotime($start));
$months = thai_month_names();
$today = date('Y-m-d');

layout_start('ปฏิทินแจ้งงาน', 'calendar');
?>
<h1 class="page-title">ปฏิทินแจ้งงาน</h1>
<div class="actions" style="margin-bottom:14px;">
  <a class="btn btn-light" href="index.php?p=calendar&m=<?php echo $prev_m; ?>&y=<?php echo $prev_y; ?>">เดือนก่อน</a>
  <strong><?php echo h($months[$month] . ' ' . buddhist_year($year)); ?></strong>
  <a class="btn btn-light" href="index.php?p=calendar&m=<?php echo $next_m; ?>&y=<?php echo $next_y; ?>">เดือนถัดไป</a>
</div>
<div class="cal-grid">
  <?php
  $heads = array('อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส');
  foreach ($heads as $hname) {
      echo '<div class="cal-head">' . h($hname) . '</div>';
  }
  $i = 0;
  while ($i < $first_w) {
      echo '<div class="cal-cell"></div>';
      $i++;
  }
  $d = 1;
  while ($d <= $days) {
      $iso = sprintf('%04d-%02d-%02d', $year, $month, $d);
      $cls = ($iso == $today) ? ' cal-cell today' : ' cal-cell';
      echo '<div class="' . $cls . '"><div class="d">' . $d . '</div>';
      if (isset($by_day[$d])) {
          foreach ($by_day[$d] as $job) {
              echo '<a href="index.php?p=job_view&id=' . (int)$job['id'] . '">' . h($job['ticket_no']) . '</a>';
          }
      }
      echo '</div>';
      $d++;
  }
  ?>
</div>
<?php
layout_end();
