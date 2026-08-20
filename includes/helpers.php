<?php

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function request_get($key, $default) {
    return isset($_GET[$key]) ? $_GET[$key] : $default;
}

function request_post($key, $default) {
    return isset($_POST[$key]) ? $_POST[$key] : $default;
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function now_dt() {
    return date('Y-m-d H:i:s');
}

function url($page, $params) {
    $q = 'index.php?p=' . urlencode($page);
    if (is_array($params)) {
        foreach ($params as $k => $v) {
            if ($v === '' || $v === null) {
                continue;
            }
            $q .= '&' . urlencode($k) . '=' . urlencode($v);
        }
    }
    return $q;
}

function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = sha1(uniqid(mt_rand(), true));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '" />';
}

function csrf_ok() {
    $sent = request_post('csrf', '');
    return $sent !== '' && $sent === csrf_token();
}

function require_csrf() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && !csrf_ok()) {
        die('คำขอไม่ถูกต้อง กรุณากลับไปลองใหม่');
    }
}

function buddhist_year($ad) {
    return ((int)$ad) + 543;
}

function thai_months() {
    return array(
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
        5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
        9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
    );
}

function thai_month_names() {
    return array(
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    );
}

function thai_date($date) {
    if ($date === null || $date == '' || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') {
        return '-';
    }
    $ts = strtotime($date);
    if (!$ts) {
        return '-';
    }
    $months = thai_months();
    $m = (int)date('n', $ts);
    return date('j', $ts) . ' ' . $months[$m] . ' ' . buddhist_year(date('Y', $ts));
}

function thai_datetime($date) {
    if ($date === null || $date == '' || $date == '0000-00-00 00:00:00') {
        return '-';
    }
    $ts = strtotime($date);
    if (!$ts) {
        return '-';
    }
    return thai_date($date) . ' ' . date('H:i', $ts);
}

function current_fiscal_year_be() {
    $y = (int)date('Y');
    $m = (int)date('n');
    if ($m >= 10) {
        return buddhist_year($y + 1);
    }
    return buddhist_year($y);
}

function fiscal_year_range($be_year) {
    $ad_end = ((int)$be_year) - 543;
    return array(
        'start' => ($ad_end - 1) . '-10-01',
        'end' => $ad_end . '-09-30'
    );
}

function status_label($status) {
    $map = array(
        'pending' => 'รอรับงาน',
        'in_progress' => 'กำลังดำเนินการ',
        'waiting_parts' => 'รออะไหล่',
        'done' => 'เสร็จแล้ว',
        'cancelled' => 'ยกเลิก'
    );
    return isset($map[$status]) ? $map[$status] : $status;
}

function status_class($status) {
    $map = array(
        'pending' => 'st-pending',
        'in_progress' => 'st-progress',
        'waiting_parts' => 'st-parts',
        'done' => 'st-done',
        'cancelled' => 'st-cancel'
    );
    return isset($map[$status]) ? $map[$status] : 'st-pending';
}

function priority_label($priority) {
    return $priority == 'urgent' ? 'ด่วน' : 'ปกติ';
}

function next_ticket_no() {
    $be = buddhist_year(date('Y'));
    $prefix = 'IT-' . $be . '-';
    $row = db_one("SELECT ticket_no FROM jobs WHERE ticket_no LIKE '" . db_esc($prefix) . "%' ORDER BY id DESC LIMIT 1");
    $n = 1;
    if ($row && isset($row['ticket_no'])) {
        $last = substr($row['ticket_no'], strlen($prefix));
        $n = ((int)$last) + 1;
    }
    return $prefix . str_pad((string)$n, 4, '0', STR_PAD_LEFT);
}

function flash_set($type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

function job_select_sql() {
    return "SELECT jobs.*, departments.name AS department_name, "
        . "job_categories.name AS category_name, job_types.name AS type_name, "
        . "users.fullname AS assignee_name "
        . "FROM jobs "
        . "LEFT JOIN departments ON departments.id = jobs.department_id "
        . "LEFT JOIN job_categories ON job_categories.id = jobs.category_id "
        . "LEFT JOIN job_types ON job_types.id = jobs.type_id "
        . "LEFT JOIN users ON users.id = jobs.assignee_id ";
}

function flash_get() {
    if (!isset($_SESSION['flash_message'])) {
        return null;
    }
    $out = array(
        'type' => isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : 'info',
        'message' => $_SESSION['flash_message']
    );
    unset($_SESSION['flash_type']);
    unset($_SESSION['flash_message']);
    return $out;
}

function sort_table_ok($table) {
    $ok = array('departments' => 1, 'job_types' => 1, 'job_categories' => 1, 'sla_items' => 1);
    return isset($ok[$table]);
}

function next_sort_order($table, $where_sql) {
    if (!sort_table_ok($table)) {
        return 1;
    }
    $where = ($where_sql != '') ? $where_sql : '1=1';
    $row = db_one("SELECT MAX(sort_order) AS m FROM `" . $table . "` WHERE " . $where);
    return ($row && $row['m'] !== null && $row['m'] !== '') ? ((int)$row['m'] + 1) : 1;
}

function sort_move($table, $id, $direction, $where_sql) {
    if (!sort_table_ok($table)) {
        return false;
    }
    $id = (int)$id;
    $direction = ($direction == 'up') ? 'up' : 'down';
    $where = ($where_sql != '') ? $where_sql : '1=1';
    $rows = db_all("SELECT id FROM `" . $table . "` WHERE " . $where . " ORDER BY sort_order, id");
    $idx = -1;
    $i = 0;
    while ($i < count($rows)) {
        if ((int)$rows[$i]['id'] == $id) {
            $idx = $i;
            break;
        }
        $i++;
    }
    if ($idx < 0) {
        return false;
    }
    $swap = ($direction == 'up') ? ($idx - 1) : ($idx + 1);
    if ($swap < 0 || $swap >= count($rows)) {
        return false;
    }
    $tmp = $rows[$idx];
    $rows[$idx] = $rows[$swap];
    $rows[$swap] = $tmp;
    $i = 0;
    while ($i < count($rows)) {
        db_query("UPDATE `" . $table . "` SET sort_order = " . ($i + 1) . " WHERE id = " . (int)$rows[$i]['id']);
        $i++;
    }
    return true;
}

function sort_controls($page, $id, $show_up, $show_down, $action = '') {
    if ($action == '') {
        $action = 'move';
    }
    $html = '<span class="sort-btns">';
    $html .= sort_control_btn($page, $id, 'up', 'ขึ้น', $show_up, $action);
    $html .= sort_control_btn($page, $id, 'down', 'ลง', $show_down, $action);
    $html .= '</span>';
    return $html;
}

function sort_control_btn($page, $id, $dir, $label, $enabled, $action = '') {
    if ($action == '') {
        $action = 'move';
    }
    if (!$enabled) {
        return '<button class="btn btn-light btn-sort" type="button" disabled>' . h($label) . '</button>';
    }
    return '<form method="post" action="index.php?p=' . h($page) . '" class="sort-form">'
        . csrf_field()
        . '<input type="hidden" name="action" value="' . h($action) . '" />'
        . '<input type="hidden" name="id" value="' . (int)$id . '" />'
        . '<input type="hidden" name="dir" value="' . h($dir) . '" />'
        . '<button class="btn btn-light btn-sort" type="submit">' . h($label) . '</button>'
        . '</form>';
}
