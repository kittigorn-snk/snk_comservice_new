<?php
require_login();

$edit_id = (int)request_get('edit', 0);
$q = trim(request_get('q', ''));
$status = request_get('status', '');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_csrf();
    $action = request_post('action', '');

    if ($action == 'add') {
        $name = trim(request_post('name', ''));
        if ($name == '' || strlen($name) > 150) {
            flash_set('danger', 'กรุณากรอกชื่อหน่วยงาน');
        } else {
            $dup = db_one("SELECT id FROM departments WHERE name = '" . db_esc($name) . "'");
            if ($dup) {
                flash_set('danger', 'หน่วยงานนี้มีอยู่แล้ว');
            } else {
                db_query("INSERT INTO departments (name, sort_order, is_active) VALUES ('" . db_esc($name) . "', " . next_sort_order('departments', '') . ", 1)");
                flash_set('success', 'เพิ่มหน่วยงานแล้ว');
            }
        }
        redirect('index.php?p=departments');
    }

    if ($action == 'save') {
        $id = (int)request_post('id', 0);
        $name = trim(request_post('name', ''));
        $active = request_post('is_active', '0') == '1' ? 1 : 0;
        $row = db_one("SELECT * FROM departments WHERE id = " . $id);
        if (!$row) {
            flash_set('danger', 'ไม่พบหน่วยงาน');
        } elseif ($name == '' || strlen($name) > 150) {
            flash_set('danger', 'กรุณากรอกชื่อหน่วยงาน');
        } else {
            $dup = db_one("SELECT id FROM departments WHERE name = '" . db_esc($name) . "' AND id <> " . $id);
            if ($dup) {
                flash_set('danger', 'หน่วยงานนี้มีอยู่แล้ว');
            } else {
                db_query("UPDATE departments SET name = '" . db_esc($name) . "', is_active = " . $active . " WHERE id = " . $id);
                flash_set('success', 'บันทึกหน่วยงานแล้ว');
            }
        }
        redirect('index.php?p=departments');
    }

    if ($action == 'toggle') {
        $id = (int)request_post('id', 0);
        $row = db_one("SELECT * FROM departments WHERE id = " . $id);
        if ($row) {
            $next = ((int)$row['is_active'] == 1) ? 0 : 1;
            db_query("UPDATE departments SET is_active = " . $next . " WHERE id = " . $id);
            flash_set('success', $next ? 'เปิดใช้งานหน่วยงานแล้ว' : 'ปิดใช้งานหน่วยงานแล้ว');
        }
        redirect('index.php?p=departments');
    }

    if ($action == 'delete') {
        $id = (int)request_post('id', 0);
        $used = db_one("SELECT COUNT(*) AS c FROM jobs WHERE department_id = " . $id);
        if ($used && (int)$used['c'] > 0) {
            flash_set('danger', 'ลบไม่ได้ เพราะมีใบงานของหน่วยงานนี้อยู่');
        } else {
            db_query("DELETE FROM departments WHERE id = " . $id);
            flash_set('success', 'ลบหน่วยงานแล้ว');
        }
        redirect('index.php?p=departments');
    }

    if ($action == 'move') {
        $id = (int)request_post('id', 0);
        $dir = request_post('dir', '');
        if (sort_move('departments', $id, $dir, '1=1')) {
            flash_set('success', 'จัดลำดับหน่วยงานแล้ว');
        } else {
            flash_set('danger', 'ไม่สามารถย้ายลำดับได้');
        }
        redirect('index.php?p=departments');
    }
}

$where = array('1=1');
if ($q != '') {
    $where[] = "departments.name LIKE '%" . db_esc($q) . "%'";
}
if ($status == 'active') {
    $where[] = "departments.is_active = 1";
} elseif ($status == 'inactive') {
    $where[] = "departments.is_active = 0";
}

$rows = db_all("SELECT departments.*, "
    . "(SELECT COUNT(*) FROM jobs WHERE jobs.department_id = departments.id) AS job_count "
    . "FROM departments "
    . "WHERE " . implode(' AND ', $where) . " "
    . "ORDER BY departments.sort_order, departments.name, departments.id");
$edit_row = $edit_id > 0 ? db_one("SELECT * FROM departments WHERE id = " . $edit_id) : null;
$total = count($rows);

layout_start('หน่วยงาน', 'departments');
?>
<h1 class="page-title">หน่วยงานแจ้งงานซ่อม</h1>
<p class="muted">เพิ่มหน่วยงานที่ใช้ตอนแจ้งซ่อม เช่น ห้องฉุกเฉิน ห้องยา งานผู้ป่วยใน แล้วหน่วยงานจะไปโชว์ในฟอร์มแจ้งงาน ใช้ปุ่ม ขึ้น/ลง เพื่อเรียงลำดับเอง</p>

<div class="form-card" style="margin-bottom:16px;">
  <h2><?php echo $edit_row ? 'แก้ไขหน่วยงาน' : 'เพิ่มหน่วยงาน'; ?></h2>
  <form method="post" action="index.php?p=departments">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="<?php echo $edit_row ? 'save' : 'add'; ?>" />
    <?php if ($edit_row) { ?>
      <input type="hidden" name="id" value="<?php echo (int)$edit_row['id']; ?>" />
    <?php } ?>
    <div class="form-grid">
      <div class="full">
        <label for="dept_name">ชื่อหน่วยงาน</label>
        <input type="text" id="dept_name" name="name" required maxlength="150" value="<?php echo h($edit_row ? $edit_row['name'] : ''); ?>" />
      </div>
      <?php if ($edit_row) { ?>
      <div>
        <label>สถานะ</label>
        <label><input type="checkbox" name="is_active" value="1" <?php if ($edit_row['is_active']) echo 'checked'; ?> /> ใช้งาน</label>
      </div>
      <?php } ?>
    </div>
    <div class="actions">
      <button class="btn" type="submit"><?php echo $edit_row ? 'บันทึก' : 'เพิ่มหน่วยงาน'; ?></button>
      <?php if ($edit_row) { ?>
        <a class="btn btn-light" href="index.php?p=departments">ยกเลิกแก้ไข</a>
      <?php } ?>
    </div>
  </form>
</div>

<form class="filters" method="get" action="index.php">
  <input type="hidden" name="p" value="departments" />
  <input type="text" name="q" placeholder="ค้นหาชื่อหน่วยงาน" value="<?php echo h($q); ?>" />
  <select name="status">
    <option value="">ทุกสถานะ</option>
    <option value="active" <?php if ($status == 'active') echo 'selected'; ?>>ใช้งาน</option>
    <option value="inactive" <?php if ($status == 'inactive') echo 'selected'; ?>>ปิดใช้งาน</option>
  </select>
  <button class="btn" type="submit">ค้นหา</button>
  <a class="btn btn-light" href="index.php?p=departments">ล้าง</a>
</form>

<div class="panel">
  <h2>รายการหน่วยงาน (<?php echo (int)$total; ?> รายการ)</h2>
  <?php if ($q != '' || $status != '') { ?>
    <p class="muted">ล้างการค้นหาแล้วจึงจะเรียงลำดับได้</p>
  <?php } ?>
  <?php if ($total == 0) { ?>
    <p class="muted">ยังไม่มีหน่วยงานตามเงื่อนไขที่ค้นหา</p>
  <?php } else { ?>
    <div class="table-wrap table-desktop">
      <table class="table-snk">
        <thead>
          <tr>
            <th>ลำดับ</th>
            <th>หน่วยงาน</th>
            <th>สถานะ</th>
            <th>จำนวนใบงาน</th>
            <th>จัดการ</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $di = 0;
        foreach ($rows as $row) {
            $can_sort = ($q == '' && $status == '');
            $show_up = $can_sort && $di > 0;
            $show_down = $can_sort && $di < ($total - 1);
        ?>
          <tr>
            <td class="sort-cell">
              <span class="sort-num"><?php echo $di + 1; ?></span>
              <?php echo sort_controls('departments', $row['id'], $show_up, $show_down); ?>
            </td>
            <td><?php echo h($row['name']); ?></td>
            <td><?php echo $row['is_active'] ? '<span class="badge-st st-done">ใช้งาน</span>' : '<span class="badge-st st-cancel">ปิดใช้งาน</span>'; ?></td>
            <td><?php echo (int)$row['job_count']; ?></td>
            <td>
              <div class="actions" style="margin-top:0;">
                <a class="btn btn-light" href="index.php?p=departments&edit=<?php echo (int)$row['id']; ?>">แก้ไข</a>
                <form method="post" action="index.php?p=departments" style="display:inline;">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="action" value="toggle" />
                  <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>" />
                  <button class="btn btn-secondary" type="submit"><?php echo $row['is_active'] ? 'ปิดใช้งาน' : 'เปิดใช้งาน'; ?></button>
                </form>
                <?php if ((int)$row['job_count'] == 0) { ?>
                <form method="post" action="index.php?p=departments" style="display:inline;" onsubmit="return confirm('ลบหน่วยงานนี้?');">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="action" value="delete" />
                  <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>" />
                  <button class="btn btn-danger" type="submit">ลบ</button>
                </form>
                <?php } ?>
              </div>
            </td>
          </tr>
        <?php
            $di++;
        }
        ?>
        </tbody>
      </table>
    </div>
    <div class="job-cards">
      <?php
      $di = 0;
      foreach ($rows as $row) {
          $can_sort = ($q == '' && $status == '');
          $show_up = $can_sort && $di > 0;
          $show_down = $can_sort && $di < ($total - 1);
      ?>
        <div class="job-card">
          <strong><?php echo h($row['name']); ?></strong>
          <div class="muted">ใบงาน <?php echo (int)$row['job_count']; ?></div>
          <div style="margin-top:8px;"><?php echo $row['is_active'] ? '<span class="badge-st st-done">ใช้งาน</span>' : '<span class="badge-st st-cancel">ปิดใช้งาน</span>'; ?></div>
          <div class="actions">
            <?php echo sort_controls('departments', $row['id'], $show_up, $show_down); ?>
            <a class="btn btn-light" href="index.php?p=departments&edit=<?php echo (int)$row['id']; ?>">แก้ไข</a>
          </div>
        </div>
      <?php
          $di++;
      }
      ?>
    </div>
  <?php } ?>
</div>
<?php
layout_end();
