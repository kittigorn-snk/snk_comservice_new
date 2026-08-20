<?php
$id = (int)request_get('id', 0);
$job = db_one(job_select_sql() . " WHERE jobs.id = " . $id);
if (!$job) {
    flash_set('danger', 'ไม่พบใบงาน');
    redirect('index.php?p=job_list');
}

$user = current_user();
$staff = db_all("SELECT id, fullname FROM users WHERE is_active = 1 ORDER BY fullname");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_login();
    require_csrf();
    $action = request_post('action', '');
    $now = now_dt();
    $work_note = trim(request_post('work_note', $job['work_note']));
    $estimate_days = trim(request_post('estimate_days', $job['estimate_days']));
    $assignee_id = (int)request_post('assignee_id', 0);
    $assignee_sql = $assignee_id > 0 ? (string)$assignee_id : 'NULL';

    if ($action == 'accept') {
        $uid = (int)$user['id'];
        db_query("UPDATE jobs SET status = 'in_progress', assignee_id = $uid, received_at = '" . db_esc($now) . "', updated_at = '" . db_esc($now) . "' WHERE id = $id AND status = 'pending'");
        flash_set('success', 'รับงานแล้ว');
    } elseif ($action == 'assign') {
        db_query("UPDATE jobs SET assignee_id = $assignee_sql, status = IF(status = 'pending', 'in_progress', status), received_at = IF(received_at IS NULL, '" . db_esc($now) . "', received_at), estimate_days = '" . db_esc($estimate_days) . "', work_note = '" . db_esc($work_note) . "', updated_at = '" . db_esc($now) . "' WHERE id = $id");
        flash_set('success', 'บันทึกการมอบหมายแล้ว');
    } elseif ($action == 'waiting_parts') {
        db_query("UPDATE jobs SET status = 'waiting_parts', work_note = '" . db_esc($work_note) . "', updated_at = '" . db_esc($now) . "' WHERE id = $id AND status IN ('in_progress','waiting_parts')");
        flash_set('success', 'เปลี่ยนเป็นรออะไหล่');
    } elseif ($action == 'resume') {
        db_query("UPDATE jobs SET status = 'in_progress', updated_at = '" . db_esc($now) . "' WHERE id = $id AND status = 'waiting_parts'");
        flash_set('success', 'กลับมาดำเนินการต่อ');
    } elseif ($action == 'done') {
        db_query("UPDATE jobs SET status = 'done', work_note = '" . db_esc($work_note) . "', completed_at = '" . db_esc($now) . "', updated_at = '" . db_esc($now) . "' WHERE id = $id AND status IN ('in_progress','waiting_parts')");
        flash_set('success', 'ปิดงานเรียบร้อย');
    } elseif ($action == 'cancel') {
        db_query("UPDATE jobs SET status = 'cancelled', work_note = '" . db_esc($work_note) . "', updated_at = '" . db_esc($now) . "' WHERE id = $id AND status IN ('pending','in_progress','waiting_parts')");
        flash_set('success', 'ยกเลิกใบงานแล้ว');
    } elseif ($action == 'save_note') {
        db_query("UPDATE jobs SET work_note = '" . db_esc($work_note) . "', estimate_days = '" . db_esc($estimate_days) . "', assignee_id = $assignee_sql, updated_at = '" . db_esc($now) . "' WHERE id = $id");
        flash_set('success', 'บันทึกหมายเหตุแล้ว');
    }
    redirect('index.php?p=job_view&id=' . $id);
}

layout_start('ใบงาน ' . $job['ticket_no'], 'job_list');
?>
<div class="ticket-hero">
  <div class="muted">เลขที่ใบงาน</div>
  <div class="no"><?php echo h($job['ticket_no']); ?></div>
  <div><?php echo badge_status($job['status']); ?> <?php echo badge_priority($job['priority']); ?></div>
</div>

<div class="panel">
  <h2><?php echo h($job['title']); ?></h2>
  <p><?php echo nl2br(h($job['description'])); ?></p>
  <div class="table-wrap">
    <table class="table-snk">
      <tr><th>หน่วยงาน</th><td><?php echo h($job['department_name']); ?></td></tr>
      <tr><th>ผู้แจ้ง</th><td><?php echo h($job['requester_name']); ?> · <?php echo h($job['requester_phone'] ? $job['requester_phone'] : '-'); ?></td></tr>
      <tr><th>หมวด / ประเภท</th><td><?php echo h($job['category_name']); ?> / <?php echo h($job['type_name']); ?></td></tr>
      <tr><th>วันที่แจ้ง</th><td><?php echo h(thai_datetime($job['created_at'])); ?></td></tr>
      <tr><th>ต้องการภายใน</th><td><?php echo h(thai_date($job['needed_by'])); ?></td></tr>
      <tr><th>วันที่รับงาน</th><td><?php echo h(thai_datetime($job['received_at'])); ?></td></tr>
      <tr><th>วันที่เสร็จ</th><td><?php echo h(thai_datetime($job['completed_at'])); ?></td></tr>
      <tr><th>ผู้รับงาน</th><td><?php echo h($job['assignee_name'] ? $job['assignee_name'] : '-'); ?></td></tr>
      <tr><th>ประมาณการ</th><td><?php echo h($job['estimate_days'] ? $job['estimate_days'] : '-'); ?></td></tr>
      <tr><th>วิธีปฏิบัติ / หมายเหตุ</th><td><?php echo nl2br(h($job['work_note'])); ?></td></tr>
    </table>
  </div>
  <div class="actions">
    <a class="btn btn-light" href="index.php?p=job_print&id=<?php echo (int)$job['id']; ?>" target="_blank">พิมพ์ใบงาน</a>
    <a class="btn btn-light" href="index.php?p=job_list">กลับรายการ</a>
  </div>
</div>

<?php if ($user) { ?>
<div class="form-card">
  <h2>สำหรับเจ้าหน้าที่</h2>
  <form method="post" action="index.php?p=job_view&id=<?php echo (int)$job['id']; ?>">
    <?php echo csrf_field(); ?>
    <div class="form-grid">
      <div>
        <label>ผู้รับงาน</label>
        <select name="assignee_id">
          <option value="0">ยังไม่กำหนด</option>
          <?php foreach ($staff as $s) { ?>
            <option value="<?php echo (int)$s['id']; ?>" <?php if ((int)$job['assignee_id'] == (int)$s['id']) echo 'selected'; ?>><?php echo h($s['fullname']); ?></option>
          <?php } ?>
        </select>
      </div>
      <div>
        <label>ประมาณการ (วัน)</label>
        <input type="text" name="estimate_days" value="<?php echo h($job['estimate_days']); ?>" />
      </div>
      <div class="full">
        <label>วิธีปฏิบัติ / หมายเหตุช่าง</label>
        <textarea name="work_note"><?php echo h($job['work_note']); ?></textarea>
      </div>
    </div>
    <div class="actions">
      <?php if ($job['status'] == 'pending') { ?>
        <button class="btn" type="submit" name="action" value="accept">รับงาน</button>
        <button class="btn btn-secondary" type="submit" name="action" value="assign">มอบหมาย</button>
      <?php } ?>
      <?php if ($job['status'] == 'in_progress') { ?>
        <button class="btn" type="submit" name="action" value="done">ปิดงาน เสร็จแล้ว</button>
        <button class="btn btn-secondary" type="submit" name="action" value="waiting_parts">รออะไหล่</button>
        <button class="btn btn-light" type="submit" name="action" value="assign">บันทึกการมอบหมาย</button>
      <?php } ?>
      <?php if ($job['status'] == 'waiting_parts') { ?>
        <button class="btn" type="submit" name="action" value="resume">ดำเนินการต่อ</button>
        <button class="btn btn-secondary" type="submit" name="action" value="done">ปิดงาน เสร็จแล้ว</button>
      <?php } ?>
      <?php if ($job['status'] != 'done' && $job['status'] != 'cancelled') { ?>
        <button class="btn btn-light" type="submit" name="action" value="save_note">บันทึกหมายเหตุ</button>
        <button class="btn btn-danger" type="submit" name="action" value="cancel" onclick="return confirm('ยืนยันยกเลิกใบงานนี้?');">ยกเลิกงาน</button>
      <?php } ?>
    </div>
  </form>
</div>
<?php } ?>
<?php
layout_end();
