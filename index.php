<?php
require_once dirname(__FILE__) . '/includes/bootstrap.php';

$p = request_get('p', 'home');
$allowed = array(
    'home', 'job_form', 'job_list', 'job_view', 'job_print', 'job_export',
    'calendar', 'reports', 'login', 'logout', 'settings', 'ajax', 'register'
);

if (!in_array($p, $allowed)) {
    $p = 'home';
}

if ($p != 'install' && !db_ready() && $p != 'ajax') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="th"><head><meta charset="utf-8"><title>ยังไม่ได้ติดตั้ง</title></head><body>';
    echo '<p>ยังไม่ได้ติดตั้งฐานข้อมูล กรุณาเปิด <a href="install.php">install.php</a></p></body></html>';
    exit;
}

if ($p == 'logout') {
    auth_logout();
    flash_set('success', 'ออกจากระบบแล้ว');
    redirect('index.php?p=login');
}

$page_file = dirname(__FILE__) . '/pages/' . $p . '.php';
if (!is_file($page_file)) {
    $p = 'home';
    $page_file = dirname(__FILE__) . '/pages/home.php';
}

require $page_file;
