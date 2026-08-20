<?php
$errors = array();
$is_setup = (user_count() == 0);
$can_register = $is_setup || is_admin();

if (!$can_register) {
    flash_set('warning', 'การสมัครสมาชิกทำได้เฉพาะผู้ดูแลระบบ กรุณาเข้าสู่ระบบด้วยบัญชี admin');
    redirect('index.php?p=login');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_csrf();
    if (auth_is_locked()) {
        $errors[] = 'พยายามหลายครั้งเกินไป กรุณารอ 15 นาที';
    } else {
        $username = trim(request_post('username', ''));
        $fullname = trim(request_post('fullname', ''));
        $password = request_post('password', '');
        $confirm = request_post('password_confirm', '');
        $role = $is_setup ? 'admin' : request_post('role', 'staff');
        if ($password !== $confirm) {
            $errors[] = 'รหัสผ่านยืนยันไม่ตรงกัน';
        }
        if (count($errors) == 0) {
            $err = user_create($username, $fullname, $password, $role);
            if ($err != '') {
                $errors[] = $err;
            } else {
                if ($is_setup) {
                    flash_set('success', 'สร้างบัญชีผู้ดูแลระบบแล้ว กรุณาเข้าสู่ระบบ');
                    redirect('index.php?p=login');
                }
                flash_set('success', 'เพิ่มสมาชิก ' . $username . ' เรียบร้อย');
                redirect('index.php?p=register');
            }
        }
    }
}

layout_start('สมัครสมาชิก', 'register');
?>
<div class="form-card" style="max-width:560px;margin:0 auto;">
  <h1 class="page-title"><?php echo $is_setup ? 'สร้างบัญชีผู้ดูแลระบบคนแรก' : 'สมัครสมาชิกสำหรับเจ้าหน้าที่'; ?></h1>
  <?php if ($is_setup) { ?>
    <p class="muted">ยังไม่มีผู้ใช้ในระบบ กรุณาสร้างบัญชีผู้ดูแลระบบก่อนเริ่มงาน</p>
  <?php } else { ?>
    <p class="muted">เฉพาะผู้ดูแลระบบสามารถเพิ่มสมาชิกได้ ไม่เปิดให้สมัครด้วยตนเองจากอินเทอร์เน็ตสาธารณะ</p>
  <?php } ?>
  <p class="muted">รหัสผ่านอย่างน้อย 10 ตัว ต้องมีทั้งตัวอักษรและตัวเลข และต้องไม่ใช่รหัสที่เดาง่าย</p>
  <?php if (count($errors) > 0) { ?>
    <div class="flash flash-danger"><?php echo h(implode(' · ', $errors)); ?></div>
  <?php } ?>
  <form method="post" action="index.php?p=register" autocomplete="off">
    <?php echo csrf_field(); ?>
    <p>
      <label for="username">ชื่อผู้ใช้</label>
      <input type="text" id="username" name="username" required minlength="3" maxlength="32" autocomplete="off" value="<?php echo h(request_post('username', '')); ?>" />
    </p>
    <p>
      <label for="fullname">ชื่อ-สกุล</label>
      <input type="text" id="fullname" name="fullname" required maxlength="100" value="<?php echo h(request_post('fullname', '')); ?>" />
    </p>
    <?php if (!$is_setup) { ?>
    <p>
      <label for="role">สิทธิ์</label>
      <select id="role" name="role">
        <option value="staff">เจ้าหน้าที่</option>
        <option value="admin">ผู้ดูแลระบบ</option>
      </select>
    </p>
    <?php } ?>
    <p>
      <label for="password">รหัสผ่าน</label>
      <input type="password" id="password" name="password" required minlength="10" maxlength="128" autocomplete="new-password" />
    </p>
    <p>
      <label for="password_confirm">ยืนยันรหัสผ่าน</label>
      <input type="password" id="password_confirm" name="password_confirm" required minlength="10" maxlength="128" autocomplete="new-password" />
    </p>
    <div class="actions">
      <button class="btn" type="submit"><?php echo $is_setup ? 'สร้างบัญชีผู้ดูแล' : 'เพิ่มสมาชิก'; ?></button>
      <a class="btn btn-light" href="index.php?p=login">เข้าสู่ระบบ</a>
    </div>
  </form>
</div>
<?php
layout_end();
