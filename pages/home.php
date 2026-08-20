<?php
$user = current_user();

$pending = db_one("SELECT COUNT(*) AS c FROM jobs WHERE status = 'pending'");
$progress = db_one("SELECT COUNT(*) AS c FROM jobs WHERE status = 'in_progress'");
$parts = db_one("SELECT COUNT(*) AS c FROM jobs WHERE status = 'waiting_parts'");
$done_today = db_one("SELECT COUNT(*) AS c FROM jobs WHERE status = 'done' AND DATE(completed_at) = '" . date('Y-m-d') . "'");
$urgent = db_one("SELECT COUNT(*) AS c FROM jobs WHERE priority = 'urgent' AND status IN ('pending','in_progress','waiting_parts')");

$open_jobs = db_all(job_select_sql() . " WHERE jobs.status IN ('pending','in_progress','waiting_parts') ORDER BY jobs.priority DESC, jobs.id DESC LIMIT 8");
$sla_items = db_all("SELECT * FROM sla_items WHERE is_active = 1 ORDER BY sort_order, id");

layout_start('หน้าแรก', 'home');
?>
<section class="hero">
  <div>
    <h1>แจ้งซ่อมคอมพิวเตอร์ได้ในไม่กี่ขั้นตอน</h1>
    <p>เลือกหน่วยงาน บอกอาการ แล้วรับเลขที่ใบงานทันที เพื่อติดตามสถานะได้เอง ไม่ต้องโทรถามซ้ำ</p>
    <a class="btn-hero" href="index.php?p=job_form">แจ้งงาน</a>
    <a class="btn-ghost" href="index.php?p=job_list">ติดตามงาน</a>
  </div>
  <div>
    <div class="hero-kpi">
      <div class="lbl">งานที่ยังไม่เสร็จ</div>
      <div class="num"><?php echo (int)$pending['c'] + (int)$progress['c'] + (int)$parts['c']; ?></div>
      <div class="muted">รอรับ <?php echo (int)$pending['c']; ?> · กำลังทำ <?php echo (int)$progress['c']; ?></div>
    </div>
  </div>
</section>

<div class="kpi-grid">
  <div class="kpi"><div class="lbl">รอรับงาน</div><div class="num"><?php echo (int)$pending['c']; ?></div></div>
  <div class="kpi"><div class="lbl">กำลังดำเนินการ</div><div class="num"><?php echo (int)$progress['c']; ?></div></div>
  <div class="kpi"><div class="lbl">เสร็จวันนี้</div><div class="num"><?php echo (int)$done_today['c']; ?></div></div>
  <div class="kpi urgent"><div class="lbl">งานด่วนที่ค้าง</div><div class="num"><?php echo (int)$urgent['c']; ?></div></div>
</div>

<div class="panel">
  <h2>งานที่กำลังดูแล</h2>
  <?php if (count($open_jobs) == 0) { ?>
    <p class="muted">ยังไม่มีงานค้าง</p>
  <?php } else { ?>
    <div class="table-wrap table-desktop">
      <table class="table-snk">
        <thead>
          <tr>
            <th>เลขที่</th>
            <th>หัวข้อ</th>
            <th>หน่วยงาน</th>
            <th>สถานะ</th>
            <th>วันที่แจ้ง</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($open_jobs as $job) { ?>
          <tr>
            <td><a href="index.php?p=job_view&id=<?php echo (int)$job['id']; ?>"><?php echo h($job['ticket_no']); ?></a></td>
            <td><?php echo h($job['title']); ?> <?php echo badge_priority($job['priority']); ?></td>
            <td><?php echo h($job['department_name']); ?></td>
            <td><?php echo badge_status($job['status']); ?></td>
            <td><?php echo h(thai_datetime($job['created_at'])); ?></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
    <div class="job-cards">
      <?php foreach ($open_jobs as $job) { ?>
        <a class="job-card" href="index.php?p=job_view&id=<?php echo (int)$job['id']; ?>" style="text-decoration:none;color:inherit;">
          <strong><?php echo h($job['ticket_no']); ?></strong>
          <div><?php echo h($job['title']); ?></div>
          <div class="muted"><?php echo h($job['department_name']); ?> · <?php echo h(thai_date($job['created_at'])); ?></div>
          <div style="margin-top:8px;"><?php echo badge_status($job['status']); ?> <?php echo badge_priority($job['priority']); ?></div>
        </a>
      <?php } ?>
    </div>
  <?php } ?>
</div>

<div class="panel">
  <h2>ข้อตกลงบริการ</h2>
  <div class="sla-list">
    <?php foreach ($sla_items as $item) { ?>
      <div class="sla-item">
        <span><?php echo h($item['title']); ?></span>
        <strong><?php echo h($item['duration_text']); ?></strong>
      </div>
    <?php } ?>
  </div>
  <p class="muted" style="margin:12px 0 0;">หากไม่มีการลงรับงานภายใน 2 วัน ติดต่อฝ่ายสารสนเทศ 055-652725 ต่อ 118</p>
</div>
<?php
layout_end();
