<?php
$departments = db_all("SELECT * FROM departments WHERE is_active = 1 ORDER BY sort_order, name");
$categories = db_all("SELECT * FROM job_categories WHERE is_active = 1 ORDER BY sort_order, name");

$errors = array();
$saved = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_csrf();
    $department_id = (int)request_post('department_id', 0);
    $requester_name = trim(request_post('requester_name', ''));
    $requester_phone = trim(request_post('requester_phone', ''));
    $category_id = (int)request_post('category_id', 0);
    $type_id = (int)request_post('type_id', 0);
    $title = trim(request_post('title', ''));
    $description = trim(request_post('description', ''));
    $priority = request_post('priority', 'normal') == 'urgent' ? 'urgent' : 'normal';
    $needed_by = trim(request_post('needed_by', ''));

    if ($department_id <= 0) { $errors[] = 'กรุณาเลือกหน่วยงาน'; }
    if ($requester_name == '') { $errors[] = 'กรุณากรอกชื่อผู้แจ้ง'; }
    if ($category_id <= 0) { $errors[] = 'กรุณาเลือกหมวดงาน'; }
    if ($type_id <= 0) { $errors[] = 'กรุณาเลือกประเภทงาน'; }
    if ($title == '') { $errors[] = 'กรุณากรอกหัวข้อ'; }

    $type_ok = db_one("SELECT id FROM job_types WHERE id = " . $type_id . " AND category_id = " . $category_id . " AND is_active = 1");
    if ($type_id > 0 && !$type_ok) {
        $errors[] = 'ประเภทงานไม่ตรงกับหมวดที่เลือก';
    }

    $needed_sql = 'NULL';
    if ($needed_by != '') {
        $needed_sql = "'" . db_esc($needed_by) . "'";
    }

    if (count($errors) == 0) {
        $ticket = next_ticket_no();
        $now = now_dt();
        $sql = "INSERT INTO jobs (ticket_no, title, description, department_id, requester_name, requester_phone, category_id, type_id, priority, status, needed_by, created_at, updated_at) VALUES ("
            . "'" . db_esc($ticket) . "',"
            . "'" . db_esc($title) . "',"
            . "'" . db_esc($description) . "',"
            . $department_id . ","
            . "'" . db_esc($requester_name) . "',"
            . "'" . db_esc($requester_phone) . "',"
            . $category_id . ","
            . $type_id . ","
            . "'" . db_esc($priority) . "',"
            . "'pending',"
            . $needed_sql . ","
            . "'" . $now . "',"
            . "'" . $now . "')";
        if (db_query($sql)) {
            $id = db_id();
            flash_set('success', 'แจ้งงานสำเร็จ เลขที่ใบงาน ' . $ticket);
            redirect('index.php?p=job_view&id=' . $id);
        } else {
            $errors[] = 'บันทึกไม่สำเร็จ กรุณาลองใหม่';
        }
    }
}

layout_start('แจ้งงาน', 'job_form');
?>
<h1 class="page-title">แจ้งงานซ่อม / ขอใช้บริการ</h1>
<p class="muted">กรอกเฉพาะข้อมูลที่จำเป็น ระบบจะออกเลขที่ใบงานให้ทันที</p>
<?php if (count($errors) > 0) { ?>
  <div class="flash flash-danger"><?php echo h(implode(' · ', $errors)); ?></div>
<?php } ?>
<form class="form-card" method="post" action="index.php?p=job_form">
  <?php echo csrf_field(); ?>
  <div class="form-grid">
    <div>
      <label for="department_id">หน่วยงาน</label>
      <select id="department_id" name="department_id" required>
        <option value="">เลือกหน่วยงาน</option>
        <?php foreach ($departments as $d) { ?>
          <option value="<?php echo (int)$d['id']; ?>" <?php if ((int)request_post('department_id', 0) == (int)$d['id']) echo 'selected'; ?>><?php echo h($d['name']); ?></option>
        <?php } ?>
      </select>
    </div>
    <div>
      <label for="requester_name">ชื่อผู้แจ้ง</label>
      <input type="text" id="requester_name" name="requester_name" required value="<?php echo h(request_post('requester_name', '')); ?>" />
    </div>
    <div>
      <label for="requester_phone">เบอร์ติดต่อ</label>
      <input type="tel" id="requester_phone" name="requester_phone" value="<?php echo h(request_post('requester_phone', '')); ?>" />
    </div>
    <div>
      <label>ความเร่งด่วน</label>
      <select name="priority">
        <option value="normal">ปกติ</option>
        <option value="urgent" <?php if (request_post('priority', '') == 'urgent') echo 'selected'; ?>>ด่วน</option>
      </select>
    </div>
    <div>
      <label for="category_id">หมวดงาน</label>
      <select id="category_id" name="category_id" required>
        <option value="">เลือกหมวดงาน</option>
        <?php foreach ($categories as $c) { ?>
          <option value="<?php echo (int)$c['id']; ?>" <?php if ((int)request_post('category_id', 0) == (int)$c['id']) echo 'selected'; ?>><?php echo h($c['name']); ?></option>
        <?php } ?>
      </select>
    </div>
    <div>
      <label for="type_id">ประเภทงาน</label>
      <select id="type_id" name="type_id" required data-selected="<?php echo h(request_post('type_id', '')); ?>">
        <option value="">เลือกหมวดก่อน</option>
      </select>
    </div>
    <div class="full">
      <label for="title">หัวข้อ</label>
      <input type="text" id="title" name="title" required value="<?php echo h(request_post('title', '')); ?>" />
    </div>
    <div class="full">
      <label for="description">รายละเอียด / รุ่นเครื่อง / อาการ</label>
      <textarea id="description" name="description"><?php echo h(request_post('description', '')); ?></textarea>
    </div>
    <div>
      <label for="needed_by">ต้องการภายในวันที่ (ถ้ามี)</label>
      <input type="date" id="needed_by" name="needed_by" value="<?php echo h(request_post('needed_by', '')); ?>" />
    </div>
  </div>
  <div class="actions">
    <button class="btn" type="submit">ส่งใบงาน</button>
    <a class="btn btn-light" href="index.php">ยกเลิก</a>
  </div>
</form>
<?php
layout_end();
