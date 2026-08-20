<?php
require_login();
$q = trim(request_get('q', ''));
$status = request_get('status', '');
$department_id = (int)request_get('department_id', 0);
$priority = request_get('priority', '');
$where = array('1=1');
if ($q != '') {
    $eq = db_esc($q);
    $where[] = "(jobs.ticket_no LIKE '%$eq%' OR jobs.title LIKE '%$eq%' OR jobs.requester_name LIKE '%$eq%')";
}
if ($status != '') {
    $where[] = "jobs.status = '" . db_esc($status) . "'";
}
if ($department_id > 0) {
    $where[] = "jobs.department_id = " . $department_id;
}
if ($priority != '') {
    $where[] = "jobs.priority = '" . db_esc($priority) . "'";
}
$rows = db_all(job_select_sql() . " WHERE " . implode(' AND ', $where) . " ORDER BY jobs.id DESC");

$filename = 'jobs-' . date('Ymd-His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
fputcsv($out, array('เลขที่', 'วันที่แจ้ง', 'หัวข้อ', 'หน่วยงาน', 'ผู้แจ้ง', 'เบอร์', 'หมวด', 'ประเภท', 'ความเร่งด่วน', 'สถานะ', 'ผู้รับงาน', 'วันที่เสร็จ'));
foreach ($rows as $job) {
    fputcsv($out, array(
        $job['ticket_no'],
        $job['created_at'],
        $job['title'],
        $job['department_name'],
        $job['requester_name'],
        $job['requester_phone'],
        $job['category_name'],
        $job['type_name'],
        priority_label($job['priority']),
        status_label($job['status']),
        $job['assignee_name'],
        $job['completed_at']
    ));
}
fclose($out);
exit;
