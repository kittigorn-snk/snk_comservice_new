<?php
$id = (int)request_get('id', 0);
$job = db_one(job_select_sql() . " WHERE jobs.id = " . $id);
if (!$job) {
    flash_set('danger', 'ไม่พบใบงาน');
    redirect('index.php?p=job_list');
}
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="utf-8" />
  <title>พิมพ์ <?php echo h($job['ticket_no']); ?></title>
  <style>
    body { font-family: Tahoma, sans-serif; color: #0a1628; padding: 24px; }
    h1 { margin: 0 0 4px; }
    .box { border: 2px solid #1e3a8a; border-radius: 12px; padding: 18px; }
    .box { border-top: 6px solid #0a1628; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th, td { border-bottom: 1px solid #d6dce8; text-align: left; padding: 8px 4px; vertical-align: top; }
    th { width: 180px; color: #4b5568; }
    .ticket { font-size: 28px; font-weight: bold; color: #1e3a8a; }
    @media print { .no-print { display: none; } }
  </style>
</head>
<body>
  <p class="no-print"><button onclick="window.print()">พิมพ์</button> <a href="index.php?p=job_view&id=<?php echo (int)$job['id']; ?>">กลับ</a></p>
  <div class="box">
    <div>ศูนย์คอมพิวเตอร์ โรงพยาบาลศรีนคร</div>
    <h1>ใบแจ้งซ่อม / ขอใช้บริการ</h1>
    <div class="ticket"><?php echo h($job['ticket_no']); ?></div>
    <table>
      <tr><th>หัวข้อ</th><td><?php echo h($job['title']); ?></td></tr>
      <tr><th>รายละเอียด</th><td><?php echo nl2br(h($job['description'])); ?></td></tr>
      <tr><th>หน่วยงาน</th><td><?php echo h($job['department_name']); ?></td></tr>
      <tr><th>ผู้แจ้ง / เบอร์</th><td><?php echo h($job['requester_name']); ?> <?php echo h($job['requester_phone']); ?></td></tr>
      <tr><th>หมวดงาน</th><td><?php echo h($job['category_name']); ?> / <?php echo h($job['type_name']); ?></td></tr>
      <tr><th>ความเร่งด่วน</th><td><?php echo h(priority_label($job['priority'])); ?></td></tr>
      <tr><th>สถานะ</th><td><?php echo h(status_label($job['status'])); ?></td></tr>
      <tr><th>วันที่แจ้ง</th><td><?php echo h(thai_datetime($job['created_at'])); ?></td></tr>
      <tr><th>ผู้รับงาน</th><td><?php echo h($job['assignee_name'] ? $job['assignee_name'] : ''); ?></td></tr>
      <tr><th>วิธีปฏิบัติ</th><td><?php echo nl2br(h($job['work_note'])); ?></td></tr>
    </table>
    <p style="margin-top:40px;">ลงชื่อผู้แจ้ง ................................     ลงชื่อผู้รับงาน ................................</p>
  </div>
</body>
</html>
