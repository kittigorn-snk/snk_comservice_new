<?php
if (is_logged_in()) {
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_csrf();
    $username = request_post('username', '');
    $phone = request_post('contact_phone', '');
    $note = request_post('note', '');
    $err = password_reset_submit($username, $phone, $note);
    if ($err != '') {
        $error = $err;
    } else {
        flash_set('success', 'ส่งคำขอแล้ว ผู้ดูแลระบบจะตั้งรหัสผ่านใหม่แล้วติดต่อกลับตามเบอร์ที่ให้ไว้');
        redirect('index.php?p=login');
    }
}

layout_start('ลืมรหัสผ่าน', 'login');
?>
<div class="form-card" style="max-width:420px;margin:0 auto;">
  <h1 class="page-title">ลืมรหัสผ่าน</h1>
  <p class="muted">ระบบไม่รีเซ็ตรหัสผ่านเอง กรอกชื่อผู้ใช้และเบอร์ติดต่อ ผู้ดูแลระบบจะตั้งรหัสใหม่ให้แล้วโทรกลับ</p>
  <?php if ($error != '') { ?><div class="flash flash-danger"><?php echo h($error); ?></div><?php } ?>
  <form method="post" action="index.php?p=forgot">
    <?php echo csrf_field(); ?>
    <p>
      <label for="username">ชื่อผู้ใช้</label>
      <input type="text" id="username" name="username" required maxlength="50" autocomplete="username" value="<?php echo h(request_post('username', '')); ?>" />
    </p>
    <p>
      <label for="contact_phone">เบอร์โทรติดต่อกลับ</label>
      <input type="text" id="contact_phone" name="contact_phone" required maxlength="30" value="<?php echo h(request_post('contact_phone', '')); ?>" />
    </p>
    <p>
      <label for="note">หมายเหตุ (ถ้ามี)</label>
      <input type="text" id="note" name="note" maxlength="255" value="<?php echo h(request_post('note', '')); ?>" />
    </p>
    <div class="actions">
      <button class="btn" type="submit">ส่งคำขอให้ผู้ดูแลระบบ</button>
      <a class="btn btn-light" href="index.php?p=login">กลับเข้าสู่ระบบ</a>
    </div>
  </form>
  <p class="muted" style="margin-top:16px;">หรือติดต่อฝ่ายสารสนเทศ 055-652725 ต่อ 118</p>
</div>
<?php
layout_end();
