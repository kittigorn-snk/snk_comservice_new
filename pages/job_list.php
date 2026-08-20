<?php
$q = trim(request_get('q', ''));
$status = request_get('status', '');
$department_id = (int)request_get('department_id', 0);
$priority = request_get('priority', '');
$page_no = (int)request_get('page', 1);
if ($page_no < 1) { $page_no = 1; }
$per = 20;
$where = array('1=1');
if ($q != '') {
    $eq = db_esc($q);
    $where[] = "(jobs.ticket_no LIKE '%$eq%' OR jobs.title LIKE '%$eq%' OR jobs.requester_name LIKE '%$eq%')";
}
if ($status != '') {
    $where[] = "jobs.status = '" . db_esc($status) . "'";
}
if ($department_id > 0) {
    $where[] = "jobs.department_id = " . $department_id;
}
if ($priority != '') {
    $where[] = "jobs.priority = '" . db_esc($priority) . "'";
}
$where_sql = implode(' AND ', $where);
$count_row = db_one("SELECT COUNT(*) AS c FROM jobs WHERE " . $where_sql);
$total = $count_row ? (int)$count_row['c'] : 0;
$pages = (int)ceil($total / $per);
if ($pages < 1) { $pages = 1; }
if ($page_no > $pages) { $page_no = $pages; }
$offset = ($page_no - 1) * $per;
$jobs = db_all(job_select_sql() . " WHERE " . $where_sql . " ORDER BY jobs.id DESC LIMIT " . $offset . ", " . $per);
$departments = db_all("SELECT * FROM departments WHERE is_active = 1 ORDER BY sort_order, name");
$statuses = array('pending', 'in_progress', 'waiting_parts', 'done', 'cancelled');

layout_start('ติดตามงาน', 'job_list');
?>
<h1 class="page-title">ติดตามงาน</h1>
<form class="filters" method="get" action="index.php">
  <input type="hidden" name="p" value="job_list" />
  <input type="text" name="q" placeholder="ค้นหาเลขที่ / หัวข้อ / ชื่อผู้แจ้ง" value="<?php echo h($q); ?>" />
  <select name="status">
    <option value="">ทุกสถานะ</option>
    <?php foreach ($statuses as $st) { ?>
      <option value="<?php echo h($st); ?>" <?php if ($status == $st) echo 'selected'; ?>><?php echo h(status_label($st)); ?></option>
    <?php } ?>
  </select>
  <select name="department_id">
    <option value="">ทุกหน่วยงาน</option>
    <?php foreach ($departments as $d) { ?>
      <option value="<?php echo (int)$d['id']; ?>" <?php if ($department_id == (int)$d['id']) echo 'selected'; ?>><?php echo h($d['name']); ?></option>
    <?php } ?>
  </select>
  <select name="priority">
    <option value="">ทุกระดับ</option>
    <option value="normal" <?php if ($priority == 'normal') echo 'selected'; ?>>ปกติ</option>
    <option value="urgent" <?php if ($priority == 'urgent') echo 'selected'; ?>>ด่วน</option>
  </select>
  <button class="btn" type="submit">ค้นหา</button>
</form>
<?php if (is_logged_in()) { ?>
  <p><a class="btn btn-light" href="index.php?p=job_export&q=<?php echo urlencode($q); ?>&status=<?php echo urlencode($status); ?>&department_id=<?php echo (int)$department_id; ?>&priority=<?php echo urlencode($priority); ?>">ส่งออก CSV</a></p>
<?php } ?>

<div class="table-wrap table-desktop">
  <table class="table-snk">
    <thead>
      <tr>
        <th>เลขที่</th>
        <th>หัวข้อ</th>
        <th>หน่วยงาน</th>
        <th>ผู้แจ้ง</th>
        <th>สถานะ</th>
        <th>ผู้รับงาน</th>
        <th>วันที่แจ้ง</th>
      </tr>
    </thead>
    <tbody>
    <?php if (count($jobs) == 0) { ?>
      <tr><td colspan="7" class="muted">ไม่พบงาน</td></tr>
    <?php } ?>
    <?php foreach ($jobs as $job) { ?>
      <tr>
        <td><a href="index.php?p=job_view&id=<?php echo (int)$job['id']; ?>"><?php echo h($job['ticket_no']); ?></a></td>
        <td><?php echo h($job['title']); ?> <?php echo badge_priority($job['priority']); ?></td>
        <td><?php echo h($job['department_name']); ?></td>
        <td><?php echo h($job['requester_name']); ?></td>
        <td><?php echo badge_status($job['status']); ?></td>
        <td><?php echo h($job['assignee_name'] ? $job['assignee_name'] : '-'); ?></td>
        <td><?php echo h(thai_datetime($job['created_at'])); ?></td>
      </tr>
    <?php } ?>
    </tbody>
  </table>
</div>
<div class="job-cards">
  <?php foreach ($jobs as $job) { ?>
    <a class="job-card" href="index.php?p=job_view&id=<?php echo (int)$job['id']; ?>" style="text-decoration:none;color:inherit;">
      <strong><?php echo h($job['ticket_no']); ?></strong>
      <div><?php echo h($job['title']); ?></div>
      <div class="muted"><?php echo h($job['department_name']); ?> · <?php echo h($job['requester_name']); ?></div>
      <div style="margin-top:8px;"><?php echo badge_status($job['status']); ?> <?php echo badge_priority($job['priority']); ?></div>
    </a>
  <?php } ?>
</div>
<?php if ($pages > 1) { ?>
  <div class="pager">
    <?php if ($page_no > 1) { ?><a class="btn btn-light" href="index.php?p=job_list&page=<?php echo $page_no - 1; ?>&q=<?php echo urlencode($q); ?>&status=<?php echo urlencode($status); ?>&department_id=<?php echo (int)$department_id; ?>&priority=<?php echo urlencode($priority); ?>">ก่อนหน้า</a><?php } ?>
    <span class="muted">หน้า <?php echo $page_no; ?> / <?php echo $pages; ?></span>
    <?php if ($page_no < $pages) { ?><a class="btn btn-light" href="index.php?p=job_list&page=<?php echo $page_no + 1; ?>&q=<?php echo urlencode($q); ?>&status=<?php echo urlencode($status); ?>&department_id=<?php echo (int)$department_id; ?>&priority=<?php echo urlencode($priority); ?>">ถัดไป</a><?php } ?>
  </div>
<?php } ?>
<?php
layout_end();
