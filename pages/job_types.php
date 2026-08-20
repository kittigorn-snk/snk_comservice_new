<?php
require_login();

$errors = array();
$edit_id = (int)request_get('edit', 0);
$filter_cat = (int)request_get('category_id', 0);
$q = trim(request_get('q', ''));

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_csrf();
    $action = request_post('action', '');

    if ($action == 'add_category') {
        $name = trim(request_post('name', ''));
        if ($name == '' || strlen($name) > 100) {
            flash_set('danger', 'กรุณากรอกชื่อหมวดงาน');
        } else {
            db_query("INSERT INTO job_categories (name, sort_order, is_active) VALUES ('" . db_esc($name) . "', " . next_sort_order('job_categories', '') . ", 1)");
            flash_set('success', 'เพิ่มหมวดงานแล้ว');
        }
        redirect('index.php?p=job_types');
    }

    if ($action == 'add_type') {
        $cid = (int)request_post('category_id', 0);
        $name = trim(request_post('name', ''));
        $cat = $cid > 0 ? db_one("SELECT id FROM job_categories WHERE id = " . $cid) : null;
        if (!$cat) {
            flash_set('danger', 'กรุณาเลือกหมวดงาน');
        } elseif ($name == '' || strlen($name) > 150) {
            flash_set('danger', 'กรุณากรอกชื่อประเภทงาน');
        } else {
            $dup = db_one("SELECT id FROM job_types WHERE category_id = " . $cid . " AND name = '" . db_esc($name) . "'");
            if ($dup) {
                flash_set('danger', 'ประเภทงานนี้มีในหมวดเดียวกันแล้ว');
            } else {
                db_query("INSERT INTO job_types (category_id, name, sort_order, is_active) VALUES (" . $cid . ", '" . db_esc($name) . "', " . next_sort_order('job_types', 'category_id = ' . $cid) . ", 1)");
                flash_set('success', 'เพิ่มประเภทงานแล้ว');
            }
        }
        redirect('index.php?p=job_types');
    }

    if ($action == 'save_type') {
        $id = (int)request_post('id', 0);
        $cid = (int)request_post('category_id', 0);
        $name = trim(request_post('name', ''));
        $active = request_post('is_active', '0') == '1' ? 1 : 0;
        $row = db_one("SELECT * FROM job_types WHERE id = " . $id);
        $cat = $cid > 0 ? db_one("SELECT id FROM job_categories WHERE id = " . $cid) : null;
        if (!$row) {
            flash_set('danger', 'ไม่พบประเภทงาน');
        } elseif (!$cat) {
            flash_set('danger', 'กรุณาเลือกหมวดงาน');
        } elseif ($name == '' || strlen($name) > 150) {
            flash_set('danger', 'กรุณากรอกชื่อประเภทงาน');
        } else {
            db_query("UPDATE job_types SET category_id = " . $cid . ", name = '" . db_esc($name) . "', is_active = " . $active . " WHERE id = " . $id);
            flash_set('success', 'บันทึกประเภทงานแล้ว');
        }
        redirect('index.php?p=job_types');
    }

    if ($action == 'toggle_type') {
        $id = (int)request_post('id', 0);
        $row = db_one("SELECT * FROM job_types WHERE id = " . $id);
        if ($row) {
            $next = ((int)$row['is_active'] == 1) ? 0 : 1;
            db_query("UPDATE job_types SET is_active = " . $next . " WHERE id = " . $id);
            flash_set('success', $next ? 'เปิดใช้งานประเภทงานแล้ว' : 'ปิดใช้งานประเภทงานแล้ว');
        }
        redirect('index.php?p=job_types');
    }

    if ($action == 'delete_type') {
        $id = (int)request_post('id', 0);
        $used = db_one("SELECT COUNT(*) AS c FROM jobs WHERE type_id = " . $id);
        if ($used && (int)$used['c'] > 0) {
            flash_set('danger', 'ลบไม่ได้ เพราะมีใบงานใช้ประเภทนี้อยู่');
        } else {
            db_query("DELETE FROM job_types WHERE id = " . $id);
            flash_set('success', 'ลบประเภทงานแล้ว');
        }
        redirect('index.php?p=job_types');
    }

    if ($action == 'move') {
        $id = (int)request_post('id', 0);
        $dir = request_post('dir', '');
        $row = db_one("SELECT * FROM job_types WHERE id = " . $id);
        if ($row && sort_move('job_types', $id, $dir, 'category_id = ' . (int)$row['category_id'])) {
            flash_set('success', 'จัดลำดับประเภทงานแล้ว');
        } else {
            flash_set('danger', 'ไม่สามารถย้ายลำดับได้');
        }
        redirect('index.php?p=job_types');
    }

    if ($action == 'move_category') {
        $id = (int)request_post('id', 0);
        $dir = request_post('dir', '');
        if (sort_move('job_categories', $id, $dir, '1=1')) {
            flash_set('success', 'จัดลำดับหมวดงานแล้ว');
        } else {
            flash_set('danger', 'ไม่สามารถย้ายลำดับได้');
        }
        redirect('index.php?p=job_types');
    }
}

$cats = db_all("SELECT * FROM job_categories ORDER BY sort_order, name, id");
$where = array('1=1');
if ($filter_cat > 0) {
    $where[] = "job_types.category_id = " . $filter_cat;
}
if ($q != '') {
    $where[] = "(job_types.name LIKE '%" . db_esc($q) . "%' OR job_categories.name LIKE '%" . db_esc($q) . "%')";
}
$types = db_all("SELECT job_types.*, job_categories.name AS category_name, "
    . "(SELECT COUNT(*) FROM jobs WHERE jobs.type_id = job_types.id) AS job_count "
    . "FROM job_types "
    . "LEFT JOIN job_categories ON job_categories.id = job_types.category_id "
    . "WHERE " . implode(' AND ', $where) . " "
    . "ORDER BY job_categories.sort_order, job_categories.name, job_types.sort_order, job_types.name, job_types.id");
$edit_row = $edit_id > 0 ? db_one("SELECT * FROM job_types WHERE id = " . $edit_id) : null;
$total = count($types);

layout_start('ประเภทงาน', 'job_types');
?>
<h1 class="page-title">ประเภทงาน</h1>
<p class="muted">เพิ่มประเภทงานที่ใช้ตอนแจ้งซ่อม เช่น เปลี่ยนหมึก คอมเปิดไม่ติด แล้วดูรายการทั้งหมดได้ที่นี่ ใช้ปุ่ม ขึ้น/ลง เพื่อเรียงลำดับในฟอร์มแจ้งงาน</p>

<div class="form-card" style="margin-bottom:16px;">
  <h2><?php echo $edit_row ? 'แก้ไขประเภทงาน' : 'เพิ่มประเภทงาน'; ?></h2>
  <form method="post" action="index.php?p=job_types">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="<?php echo $edit_row ? 'save_type' : 'add_type'; ?>" />
    <?php if ($edit_row) { ?>
      <input type="hidden" name="id" value="<?php echo (int)$edit_row['id']; ?>" />
    <?php } ?>
    <div class="form-grid">
      <div>
        <label for="category_id">หมวดงาน</label>
        <select id="category_id" name="category_id" required>
          <option value="">เลือกหมวดงาน</option>
          <?php foreach ($cats as $c) { ?>
            <option value="<?php echo (int)$c['id']; ?>" <?php
              $sel = $edit_row ? (int)$edit_row['category_id'] : (int)request_post('category_id', 0);
              if ($sel == (int)$c['id']) echo 'selected';
            ?>><?php echo h($c['name']); ?><?php if (!$c['is_active']) echo ' (ปิดใช้งาน)'; ?></option>
          <?php } ?>
        </select>
      </div>
      <div>
        <label for="type_name">ชื่อประเภทงาน</label>
        <input type="text" id="type_name" name="name" required maxlength="150" value="<?php echo h($edit_row ? $edit_row['name'] : request_post('name', '')); ?>" />
      </div>
      <?php if ($edit_row) { ?>
      <div>
        <label>สถานะ</label>
        <label><input type="checkbox" name="is_active" value="1" <?php if ($edit_row['is_active']) echo 'checked'; ?> /> ใช้งาน</label>
      </div>
      <?php } ?>
    </div>
    <div class="actions">
      <button class="btn" type="submit"><?php echo $edit_row ? 'บันทึก' : 'เพิ่มประเภทงาน'; ?></button>
      <?php if ($edit_row) { ?>
        <a class="btn btn-light" href="index.php?p=job_types">ยกเลิกแก้ไข</a>
      <?php } ?>
    </div>
  </form>
</div>

<div class="form-card" style="margin-bottom:16px;">
  <h2>หมวดงาน</h2>
  <form method="post" action="index.php?p=job_types">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="add_category" />
    <div class="form-grid">
      <div class="full">
        <label for="category_name">ชื่อหมวด เช่น ปริ้นเตอร์และหมึก</label>
        <input type="text" id="category_name" name="name" required maxlength="100" />
      </div>
    </div>
    <div class="actions">
      <button class="btn btn-secondary" type="submit">เพิ่มหมวด</button>
    </div>
  </form>
  <?php if (count($cats) > 0) { ?>
  <div class="table-wrap" style="margin-top:12px;">
    <table class="table-snk">
      <thead>
        <tr>
          <th>ลำดับ</th>
          <th>หมวดงาน</th>
        </tr>
      </thead>
      <tbody>
      <?php
      $ci = 0;
      $cat_total = count($cats);
      foreach ($cats as $c) {
      ?>
        <tr>
          <td class="sort-cell">
            <span class="sort-num"><?php echo $ci + 1; ?></span>
            <?php echo sort_controls('job_types', $c['id'], $ci > 0, $ci < ($cat_total - 1), 'move_category'); ?>
          </td>
          <td><?php echo h($c['name']); ?><?php if (!$c['is_active']) echo ' <span class="muted">(ปิดใช้งาน)</span>'; ?></td>
        </tr>
      <?php
          $ci++;
      }
      ?>
      </tbody>
    </table>
  </div>
  <?php } ?>
</div>

<form class="filters" method="get" action="index.php">
  <input type="hidden" name="p" value="job_types" />
  <input type="text" name="q" placeholder="ค้นหาชื่อประเภท / หมวด" value="<?php echo h($q); ?>" />
  <select name="category_id">
    <option value="">ทุกหมวด</option>
    <?php foreach ($cats as $c) { ?>
      <option value="<?php echo (int)$c['id']; ?>" <?php if ($filter_cat == (int)$c['id']) echo 'selected'; ?>><?php echo h($c['name']); ?></option>
    <?php } ?>
  </select>
  <button class="btn" type="submit">ค้นหา</button>
  <a class="btn btn-light" href="index.php?p=job_types">ล้าง</a>
</form>

<div class="panel">
  <h2>รายการประเภทงาน (<?php echo (int)$total; ?> รายการ)</h2>
  <?php if ($total == 0) { ?>
    <p class="muted">ยังไม่มีประเภทงานตามเงื่อนไขที่ค้นหา</p>
  <?php } else { ?>
    <div class="table-wrap table-desktop">
      <table class="table-snk">
        <thead>
          <tr>
            <th>ลำดับ</th>
            <th>หมวดงาน</th>
            <th>ประเภทงาน</th>
            <th>สถานะ</th>
            <th>จำนวนใบงาน</th>
            <th>จัดการ</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $ti = 0;
        $type_count = count($types);
        foreach ($types as $row) {
            $show_up = false;
            $show_down = false;
            if ($q == '') {
                $cid = (int)$row['category_id'];
                $pj = $ti - 1;
                while ($pj >= 0) {
                    if ((int)$types[$pj]['category_id'] == $cid) {
                        $show_up = true;
                        break;
                    }
                    $pj--;
                }
                $nj = $ti + 1;
                while ($nj < $type_count) {
                    if ((int)$types[$nj]['category_id'] == $cid) {
                        $show_down = true;
                        break;
                    }
                    $nj++;
                }
            }
        ?>
          <tr>
            <td class="sort-cell">
              <span class="sort-num"><?php echo $ti + 1; ?></span>
              <?php echo sort_controls('job_types', $row['id'], $show_up, $show_down); ?>
            </td>
            <td><?php echo h($row['category_name'] ? $row['category_name'] : '-'); ?></td>
            <td><?php echo h($row['name']); ?></td>
            <td><?php echo $row['is_active'] ? '<span class="badge-st st-done">ใช้งาน</span>' : '<span class="badge-st st-cancel">ปิดใช้งาน</span>'; ?></td>
            <td><?php echo (int)$row['job_count']; ?></td>
            <td>
              <div class="actions" style="margin-top:0;">
                <a class="btn btn-light" href="index.php?p=job_types&edit=<?php echo (int)$row['id']; ?>">แก้ไข</a>
                <form method="post" action="index.php?p=job_types" style="display:inline;">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="action" value="toggle_type" />
                  <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>" />
                  <button class="btn btn-secondary" type="submit"><?php echo $row['is_active'] ? 'ปิดใช้งาน' : 'เปิดใช้งาน'; ?></button>
                </form>
                <?php if ((int)$row['job_count'] == 0) { ?>
                <form method="post" action="index.php?p=job_types" style="display:inline;" onsubmit="return confirm('ลบประเภทงานนี้?');">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="action" value="delete_type" />
                  <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>" />
                  <button class="btn btn-danger" type="submit">ลบ</button>
                </form>
                <?php } ?>
              </div>
            </td>
          </tr>
        <?php
            $ti++;
        }
        ?>
        </tbody>
      </table>
    </div>
    <div class="job-cards">
      <?php
      $ti = 0;
      $type_count = count($types);
      foreach ($types as $row) {
          $show_up = false;
          $show_down = false;
          if ($q == '') {
              $cid = (int)$row['category_id'];
              $pj = $ti - 1;
              while ($pj >= 0) {
                  if ((int)$types[$pj]['category_id'] == $cid) {
                      $show_up = true;
                      break;
                  }
                  $pj--;
              }
              $nj = $ti + 1;
              while ($nj < $type_count) {
                  if ((int)$types[$nj]['category_id'] == $cid) {
                      $show_down = true;
                      break;
                  }
                  $nj++;
              }
          }
      ?>
        <div class="job-card">
          <strong><?php echo h($row['name']); ?></strong>
          <div class="muted"><?php echo h($row['category_name'] ? $row['category_name'] : '-'); ?> · ใบงาน <?php echo (int)$row['job_count']; ?></div>
          <div style="margin-top:8px;"><?php echo $row['is_active'] ? '<span class="badge-st st-done">ใช้งาน</span>' : '<span class="badge-st st-cancel">ปิดใช้งาน</span>'; ?></div>
          <div class="actions">
            <?php echo sort_controls('job_types', $row['id'], $show_up, $show_down); ?>
            <a class="btn btn-light" href="index.php?p=job_types&edit=<?php echo (int)$row['id']; ?>">แก้ไข</a>
          </div>
        </div>
      <?php
          $ti++;
      }
      ?>
    </div>
  <?php } ?>
</div>
<?php
layout_end();
