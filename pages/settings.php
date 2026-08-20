<?php
require_login();
$tab = request_get('tab', 'departments');
$tabs = array(
    'departments' => 'หน่วยงาน',
    'types' => 'ประเภทงาน',
    'users' => 'ผู้ใช้',
    'sla' => 'ข้อตกลงบริการ'
);
if (!isset($tabs[$tab])) {
    $tab = 'departments';
}
if (!is_admin()) {
    unset($tabs['users']);
}
if ($tab == 'users') {
    require_admin();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_csrf();
    $action = request_post('action', '');
    $now = now_dt();

    if ($tab == 'departments') {
        if ($action == 'add') {
            $name = trim(request_post('name', ''));
            if ($name != '') {
                db_query("INSERT INTO departments (name, sort_order, is_active) VALUES ('" . db_esc($name) . "', 99, 1)");
                flash_set('success', 'เพิ่มหน่วยงานแล้ว');
            }
        } elseif ($action == 'save') {
            $id = (int)request_post('id', 0);
            $name = trim(request_post('name', ''));
            $active = request_post('is_active', '0') == '1' ? 1 : 0;
            db_query("UPDATE departments SET name = '" . db_esc($name) . "', is_active = $active WHERE id = $id");
            flash_set('success', 'บันทึกหน่วยงานแล้ว');
        }
        redirect('index.php?p=settings&tab=departments');
    }

    if ($tab == 'types') {
        if ($action == 'add_category') {
            $name = trim(request_post('name', ''));
            if ($name != '') {
                db_query("INSERT INTO job_categories (name, sort_order, is_active) VALUES ('" . db_esc($name) . "', 99, 1)");
                flash_set('success', 'เพิ่มหมวดงานแล้ว');
            }
        } elseif ($action == 'save_category') {
            $id = (int)request_post('id', 0);
            $name = trim(request_post('name', ''));
            $active = request_post('is_active', '0') == '1' ? 1 : 0;
            db_query("UPDATE job_categories SET name = '" . db_esc($name) . "', is_active = $active WHERE id = $id");
            flash_set('success', 'บันทึกหมวดงานแล้ว');
        } elseif ($action == 'add_type') {
            $cid = (int)request_post('category_id', 0);
            $name = trim(request_post('name', ''));
            if ($cid > 0 && $name != '') {
                db_query("INSERT INTO job_types (category_id, name, sort_order, is_active) VALUES ($cid, '" . db_esc($name) . "', 99, 1)");
                flash_set('success', 'เพิ่มประเภทงานแล้ว');
            }
        } elseif ($action == 'save_type') {
            $id = (int)request_post('id', 0);
            $name = trim(request_post('name', ''));
            $active = request_post('is_active', '0') == '1' ? 1 : 0;
            db_query("UPDATE job_types SET name = '" . db_esc($name) . "', is_active = $active WHERE id = $id");
            flash_set('success', 'บันทึกประเภทงานแล้ว');
        }
        redirect('index.php?p=settings&tab=types');
    }

    if ($tab == 'users') {
        require_admin();
        if ($action == 'add') {
            $username = trim(request_post('username', ''));
            $fullname = trim(request_post('fullname', ''));
            $password = request_post('password', '');
            $role = request_post('role', 'staff') == 'admin' ? 'admin' : 'staff';
            $err = user_create($username, $fullname, $password, $role);
            if ($err != '') {
                flash_set('danger', $err);
            } else {
                flash_set('success', 'เพิ่มผู้ใช้แล้ว');
            }
        } elseif ($action == 'save') {
            $id = (int)request_post('id', 0);
            $fullname = trim(request_post('fullname', ''));
            $role = request_post('role', 'staff') == 'admin' ? 'admin' : 'staff';
            $active = request_post('is_active', '0') == '1' ? 1 : 0;
            $password = request_post('password', '');
            $target = db_one("SELECT * FROM users WHERE id = " . $id);
            if (!$target) {
                flash_set('danger', 'ไม่พบผู้ใช้');
            } else {
                if ($target['role'] == 'admin' && ($role != 'admin' || $active != 1) && admin_count() <= 1) {
                    flash_set('danger', 'ต้องมีผู้ดูแลระบบที่ใช้งานได้อย่างน้อย 1 คน');
                } else {
                    $ok = true;
                    if ($password != '') {
                        $perr = password_validate($password, $target['username']);
                        if ($perr != '') {
                            flash_set('danger', $perr);
                            $ok = false;
                        }
                    }
                    if ($ok) {
                        $sql = "UPDATE users SET fullname = '" . db_esc($fullname) . "', role = '" . $role . "', is_active = $active";
                        if ($password != '') {
                            $sql .= ", password_hash = '" . db_esc(password_make($password)) . "'";
                        }
                        $sql .= " WHERE id = $id";
                        db_query($sql);
                        flash_set('success', 'บันทึกผู้ใช้แล้ว');
                    }
                }
            }
        }
        redirect('index.php?p=settings&tab=users');
    }

    if ($tab == 'sla') {
        if ($action == 'add') {
            $title = trim(request_post('title', ''));
            $duration = trim(request_post('duration_text', ''));
            if ($title != '') {
                db_query("INSERT INTO sla_items (title, duration_text, sort_order, is_active) VALUES ('" . db_esc($title) . "', '" . db_esc($duration) . "', 99, 1)");
                flash_set('success', 'เพิ่มข้อตกลงแล้ว');
            }
        } elseif ($action == 'save') {
            $id = (int)request_post('id', 0);
            $title = trim(request_post('title', ''));
            $duration = trim(request_post('duration_text', ''));
            $active = request_post('is_active', '0') == '1' ? 1 : 0;
            db_query("UPDATE sla_items SET title = '" . db_esc($title) . "', duration_text = '" . db_esc($duration) . "', is_active = $active WHERE id = $id");
            flash_set('success', 'บันทึกข้อตกลงแล้ว');
        }
        redirect('index.php?p=settings&tab=sla');
    }
}

layout_start('ตั้งค่า', 'settings');
?>
<h1 class="page-title">ตั้งค่าข้อมูลหลัก</h1>
<div class="settings-tabs">
  <?php foreach ($tabs as $key => $label) { ?>
    <a href="index.php?p=settings&tab=<?php echo h($key); ?>" class="<?php echo $tab == $key ? 'active' : ''; ?>"><?php echo h($label); ?></a>
  <?php } ?>
</div>

<?php if ($tab == 'departments') {
    $rows = db_all("SELECT * FROM departments ORDER BY sort_order, id");
?>
<form class="form-card" method="post" action="index.php?p=settings&tab=departments" style="margin-bottom:16px;">
  <?php echo csrf_field(); ?>
  <input type="hidden" name="action" value="add" />
  <div class="form-grid">
    <div class="full"><label>เพิ่มหน่วยงาน</label><input type="text" name="name" required /></div>
  </div>
  <div class="actions"><button class="btn" type="submit">เพิ่ม</button></div>
</form>
<?php foreach ($rows as $row) { ?>
  <form method="post" action="index.php?p=settings&tab=departments" class="panel">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="save" />
    <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>" />
    <div class="form-grid">
      <div><input type="text" name="name" value="<?php echo h($row['name']); ?>" /></div>
      <div>
        <label><input type="checkbox" name="is_active" value="1" <?php if ($row['is_active']) echo 'checked'; ?> /> ใช้งาน</label>
      </div>
    </div>
    <div class="actions"><button class="btn" type="submit">บันทึก</button></div>
  </form>
<?php } } ?>

<?php if ($tab == 'types') {
    $cats = db_all("SELECT * FROM job_categories ORDER BY sort_order, id");
    $types = db_all("SELECT job_types.*, job_categories.name AS category_name FROM job_types LEFT JOIN job_categories ON job_categories.id = job_types.category_id ORDER BY job_categories.sort_order, job_types.sort_order, job_types.id");
?>
<form class="form-card" method="post" action="index.php?p=settings&tab=types" style="margin-bottom:16px;">
  <?php echo csrf_field(); ?>
  <input type="hidden" name="action" value="add_category" />
  <label>เพิ่มหมวดงาน</label>
  <input type="text" name="name" required />
  <div class="actions"><button class="btn" type="submit">เพิ่มหมวด</button></div>
</form>
<?php foreach ($cats as $cat) { ?>
  <form method="post" action="index.php?p=settings&tab=types" class="panel">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="save_category" />
    <input type="hidden" name="id" value="<?php echo (int)$cat['id']; ?>" />
    <div class="form-grid">
      <div><input type="text" name="name" value="<?php echo h($cat['name']); ?>" /></div>
      <div><label><input type="checkbox" name="is_active" value="1" <?php if ($cat['is_active']) echo 'checked'; ?> /> ใช้งาน</label></div>
    </div>
    <div class="actions"><button class="btn" type="submit">บันทึกหมวด</button></div>
  </form>
<?php } ?>
<form class="form-card" method="post" action="index.php?p=settings&tab=types" style="margin-bottom:16px;">
  <?php echo csrf_field(); ?>
  <input type="hidden" name="action" value="add_type" />
  <div class="form-grid">
    <div>
      <label>หมวด</label>
      <select name="category_id">
        <?php foreach ($cats as $cat) { ?>
          <option value="<?php echo (int)$cat['id']; ?>"><?php echo h($cat['name']); ?></option>
        <?php } ?>
      </select>
    </div>
    <div>
      <label>ประเภทงานใหม่</label>
      <input type="text" name="name" required />
    </div>
  </div>
  <div class="actions"><button class="btn" type="submit">เพิ่มประเภท</button></div>
</form>
<?php foreach ($types as $row) { ?>
  <form method="post" action="index.php?p=settings&tab=types" class="panel">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="save_type" />
    <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>" />
    <div class="muted"><?php echo h($row['category_name']); ?></div>
    <div class="form-grid">
      <div><input type="text" name="name" value="<?php echo h($row['name']); ?>" /></div>
      <div><label><input type="checkbox" name="is_active" value="1" <?php if ($row['is_active']) echo 'checked'; ?> /> ใช้งาน</label></div>
    </div>
    <div class="actions"><button class="btn" type="submit">บันทึก</button></div>
  </form>
<?php } } ?>

<?php if ($tab == 'users') {
    $rows = db_all("SELECT id, username, fullname, role, is_active, created_at FROM users ORDER BY id");
?>
<p class="muted">รหัสผ่านใหม่อย่างน้อย 10 ตัว ต้องมีทั้งตัวอักษรและตัวเลข</p>
<p><a class="btn" href="index.php?p=register">ไปหน้าสมัครสมาชิก</a></p>
<form class="form-card" method="post" action="index.php?p=settings&tab=users" style="margin-bottom:16px;">
  <?php echo csrf_field(); ?>
  <input type="hidden" name="action" value="add" />
  <div class="form-grid">
    <div><label>ชื่อผู้ใช้</label><input type="text" name="username" required /></div>
    <div><label>ชื่อ-สกุล</label><input type="text" name="fullname" required /></div>
    <div><label>รหัสผ่าน</label><input type="password" name="password" required /></div>
    <div>
      <label>สิทธิ์</label>
      <select name="role">
        <option value="staff">เจ้าหน้าที่</option>
        <option value="admin">ผู้ดูแล</option>
      </select>
    </div>
  </div>
  <div class="actions"><button class="btn" type="submit">เพิ่มผู้ใช้</button></div>
</form>
<?php foreach ($rows as $row) { ?>
  <form method="post" action="index.php?p=settings&tab=users" class="panel">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="save" />
    <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>" />
    <strong><?php echo h($row['username']); ?></strong>
    <div class="form-grid">
      <div><label>ชื่อ-สกุล</label><input type="text" name="fullname" value="<?php echo h($row['fullname']); ?>" /></div>
      <div>
        <label>สิทธิ์</label>
        <select name="role">
          <option value="staff" <?php if ($row['role'] == 'staff') echo 'selected'; ?>>เจ้าหน้าที่</option>
          <option value="admin" <?php if ($row['role'] == 'admin') echo 'selected'; ?>>ผู้ดูแล</option>
        </select>
      </div>
      <div><label>รหัสผ่านใหม่ (ว่างไว้ถ้าไม่เปลี่ยน)</label><input type="password" name="password" /></div>
      <div><label><input type="checkbox" name="is_active" value="1" <?php if ($row['is_active']) echo 'checked'; ?> /> ใช้งาน</label></div>
    </div>
    <div class="actions"><button class="btn" type="submit">บันทึก</button></div>
  </form>
<?php } } ?>

<?php if ($tab == 'sla') {
    $rows = db_all("SELECT * FROM sla_items ORDER BY sort_order, id");
?>
<form class="form-card" method="post" action="index.php?p=settings&tab=sla" style="margin-bottom:16px;">
  <?php echo csrf_field(); ?>
  <input type="hidden" name="action" value="add" />
  <div class="form-grid">
    <div><label>รายการบริการ</label><input type="text" name="title" required /></div>
    <div><label>ระยะเวลา</label><input type="text" name="duration_text" required /></div>
  </div>
  <div class="actions"><button class="btn" type="submit">เพิ่ม</button></div>
</form>
<?php foreach ($rows as $row) { ?>
  <form method="post" action="index.php?p=settings&tab=sla" class="panel">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="save" />
    <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>" />
    <div class="form-grid">
      <div><input type="text" name="title" value="<?php echo h($row['title']); ?>" /></div>
      <div><input type="text" name="duration_text" value="<?php echo h($row['duration_text']); ?>" /></div>
      <div><label><input type="checkbox" name="is_active" value="1" <?php if ($row['is_active']) echo 'checked'; ?> /> ใช้งาน</label></div>
    </div>
    <div class="actions"><button class="btn" type="submit">บันทึก</button></div>
  </form>
<?php } } ?>
<?php
layout_end();
