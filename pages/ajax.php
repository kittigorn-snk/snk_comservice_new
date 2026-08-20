<?php
header('Content-Type: application/json; charset=utf-8');
$action = request_get('action', '');

if ($action == 'types') {
    $cid = (int)request_get('category_id', 0);
    $rows = db_all("SELECT id, name FROM job_types WHERE category_id = " . $cid . " AND is_active = 1 ORDER BY sort_order, name");
    echo json_encode($rows);
    exit;
}

if ($action == 'stats') {
    $row = db_one("SELECT COUNT(*) AS c FROM jobs WHERE status IN ('pending','in_progress','waiting_parts')");
    echo json_encode(array('pending' => $row ? (int)$row['c'] : 0));
    exit;
}

echo json_encode(array('error' => 'unknown'));
exit;
