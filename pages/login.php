<?php
if (is_logged_in()) {
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_csrf();
    if (auth_is_locked()) {
        $error = 'เข้าสู่ระบบผิดหลายครั้ง ระบบล็อกชั่วคราว 15 นาที';
    } else {
        $username = request_post('username', '');
        $password = request_post('password', '');
        if (auth_login($username, $password)) {
            $user = current_user();
            if ($user && password_check('admin123', $user['password_hash'])) {
                flash_set('warning', 'บัญชีนี้ยังใช้รหัสผ่านเริ่มต้น กรุณาเปลี่ยนรหัสผ่านทันทีที่ตั้งค่า > ผู้ใช้');
            } else {
                flash_set('success', 'เข้าสู่ระบบแล้ว');
            }
            redirect('index.php');
        }
        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    }
}

layout_start('เข้าสู่ระบบ', 'login');
?>
<div class="form-card" style="max-width:420px;margin:0 auto;">
  <h1 class="page-title">สำหรับเจ้าหน้าที่</h1>
  <p class="muted">ใช้บัญชีฝ่ายสารสนเทศเพื่อรับงาน มอบหมาย และดูรายงาน</p>
  <?php if ($error != '') { ?><div class="flash flash-danger"><?php echo h($error); ?></div><?php } ?>
  <form method="post" action="index.php?p=login">
    <?php echo csrf_field(); ?>
    <p>
      <label for="username">ชื่อผู้ใช้</label>
      <input type="text" id="username" name="username" required maxlength="32" autocomplete="username" />
    </p>
    <p>
      <label for="password">รหัสผ่าน</label>
      <input type="password" id="password" name="password" required maxlength="128" autocomplete="current-password" />
    </p>
    <button class="btn" type="submit">เข้าสู่ระบบ</button>
  </form>
  <p class="login-links"><a href="index.php?p=forgot">ลืมรหัสผ่าน</a> — ให้ผู้ดูแลระบบตั้งรหัสใหม่ให้</p>
  <p class="muted" style="margin-top:16px;">การเพิ่มสมาชิกใหม่ทำได้โดยผู้ดูแลระบบเท่านั้น</p>
</div>
<?php
layout_end();
