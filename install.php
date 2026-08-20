<?php
/**
 * One-time installer for snk_comservice_new
 * Compatible with PHP 5.2 / MySQL 5.0 (AppServ)
 */
error_reporting(E_ALL);
@ini_set('display_errors', '1');
@date_default_timezone_set('Asia/Bangkok');

$cfg = include dirname(__FILE__) . '/config/database.php';
$is_cli = (php_sapi_name() == 'cli');

function out($msg, $is_cli) {
    if ($is_cli) {
        echo $msg . "\n";
    } else {
        echo '<p>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</p>';
    }
}

function fail($msg, $is_cli) {
    out('ERROR: ' . $msg, $is_cli);
    exit(1);
}

function make_password($plain) {
    $salt = substr(sha1(uniqid(mt_rand(), true)), 0, 16);
    return $salt . '$' . hash('sha256', $salt . $plain);
}

if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="th"><head><meta charset="utf-8"><title>ติดตั้งระบบ</title>';
    echo '<style>body{font-family:Tahoma,sans-serif;max-width:720px;margin:40px auto;padding:0 16px;color:#134e4a}a{color:#0f766e}</style></head><body>';
    echo '<h1>ติดตั้งระบบศูนย์คอมพิวเตอร์</h1>';
}

$conn = @mysqli_connect($cfg['host'], $cfg['user'], $cfg['pass']);
if (!$conn) {
    fail('เชื่อมต่อ MySQL ไม่ได้: ' . mysqli_connect_error(), $is_cli);
}

$db_name = $cfg['name'];
$sql = "CREATE DATABASE IF NOT EXISTS `" . mysqli_real_escape_string($conn, $db_name) . "` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";
if (!mysqli_query($conn, $sql)) {
    fail('สร้างฐานข้อมูลไม่ได้: ' . mysqli_error($conn), $is_cli);
}
if (!mysqli_select_db($conn, $db_name)) {
    fail('เลือกฐานข้อมูลไม่ได้: ' . mysqli_error($conn), $is_cli);
}
mysqli_query($conn, "SET NAMES utf8");
out('สร้างฐานข้อมูล ' . $db_name . ' แล้ว', $is_cli);

$force = false;
if ($is_cli && isset($argv) && in_array('--force', $argv)) {
    $force = true;
}
if (!$is_cli && isset($_GET['force']) && $_GET['force'] == '1') {
    $force = true;
}
$exists = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if ($exists && mysqli_num_rows($exists) > 0 && !$force) {
    out('ติดตั้งไว้แล้ว ไม่ได้ลบข้อมูลเดิม', $is_cli);
    out('ถ้าต้องการติดตั้งใหม่ให้เปิด install.php?force=1', $is_cli);
    if (!$is_cli) {
        echo '<p><a href="index.php">เข้าสู่เว็บไซต์</a></p></body></html>';
    }
    exit;
}

$statements = array(
    "DROP TABLE IF EXISTS `jobs`",
    "DROP TABLE IF EXISTS `sla_items`",
    "DROP TABLE IF EXISTS `job_types`",
    "DROP TABLE IF EXISTS `job_categories`",
    "DROP TABLE IF EXISTS `departments`",
    "DROP TABLE IF EXISTS `users`",
    "CREATE TABLE `users` (
      `id` int(10) unsigned NOT NULL auto_increment,
      `username` varchar(50) NOT NULL,
      `password_hash` varchar(128) NOT NULL,
      `fullname` varchar(100) NOT NULL,
      `role` varchar(20) NOT NULL default 'staff',
      `is_active` tinyint(1) NOT NULL default '1',
      `created_at` datetime NOT NULL,
      PRIMARY KEY  (`id`),
      UNIQUE KEY `uk_username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
    "CREATE TABLE `departments` (
      `id` int(10) unsigned NOT NULL auto_increment,
      `name` varchar(150) NOT NULL,
      `sort_order` int(11) NOT NULL default '0',
      `is_active` tinyint(1) NOT NULL default '1',
      PRIMARY KEY  (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
    "CREATE TABLE `job_categories` (
      `id` int(10) unsigned NOT NULL auto_increment,
      `name` varchar(100) NOT NULL,
      `sort_order` int(11) NOT NULL default '0',
      `is_active` tinyint(1) NOT NULL default '1',
      PRIMARY KEY  (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
    "CREATE TABLE `job_types` (
      `id` int(10) unsigned NOT NULL auto_increment,
      `category_id` int(10) unsigned NOT NULL,
      `name` varchar(150) NOT NULL,
      `sort_order` int(11) NOT NULL default '0',
      `is_active` tinyint(1) NOT NULL default '1',
      PRIMARY KEY  (`id`),
      KEY `idx_category` (`category_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
    "CREATE TABLE `sla_items` (
      `id` int(10) unsigned NOT NULL auto_increment,
      `title` varchar(150) NOT NULL,
      `duration_text` varchar(100) NOT NULL,
      `sort_order` int(11) NOT NULL default '0',
      `is_active` tinyint(1) NOT NULL default '1',
      PRIMARY KEY  (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
    "CREATE TABLE `jobs` (
      `id` int(10) unsigned NOT NULL auto_increment,
      `ticket_no` varchar(20) NOT NULL,
      `title` varchar(200) NOT NULL,
      `description` text,
      `department_id` int(10) unsigned NOT NULL,
      `requester_name` varchar(100) NOT NULL,
      `requester_phone` varchar(30) NOT NULL default '',
      `category_id` int(10) unsigned NOT NULL,
      `type_id` int(10) unsigned NOT NULL,
      `priority` varchar(10) NOT NULL default 'normal',
      `status` varchar(20) NOT NULL default 'pending',
      `needed_by` date default NULL,
      `received_at` datetime default NULL,
      `completed_at` datetime default NULL,
      `assignee_id` int(10) unsigned default NULL,
      `estimate_days` varchar(20) default NULL,
      `work_note` text,
      `created_at` datetime NOT NULL,
      `updated_at` datetime NOT NULL,
      PRIMARY KEY  (`id`),
      UNIQUE KEY `uk_ticket` (`ticket_no`),
      KEY `idx_status` (`status`),
      KEY `idx_dept` (`department_id`),
      KEY `idx_created` (`created_at`),
      KEY `idx_assignee` (`assignee_id`),
      KEY `idx_priority` (`priority`),
      KEY `idx_category` (`category_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
);

$i = 0;
while ($i < count($statements)) {
    if (!mysqli_query($conn, $statements[$i])) {
        fail('สร้างตารางไม่ได้: ' . mysqli_error($conn), $is_cli);
    }
    $i++;
}
out('สร้างตารางเรียบร้อย', $is_cli);

$now = date('Y-m-d H:i:s');
$admin_pass = 'admin123';
$hash = make_password($admin_pass);
$sql = "INSERT INTO users (username, password_hash, fullname, role, is_active, created_at) VALUES ("
    . "'admin','" . mysqli_real_escape_string($conn, $hash) . "','ผู้ดูแลระบบ','admin',1,'" . $now . "')";
if (!mysqli_query($conn, $sql)) {
    fail('สร้างผู้ใช้ admin ไม่ได้: ' . mysqli_error($conn), $is_cli);
}

$departments = array(
    'ห้องฉุกเฉิน',
    'งานผู้ป่วยนอก',
    'งานผู้ป่วยใน',
    'ห้องยา',
    'ห้องชันสูตร',
    'ห้องเอกซเรย์',
    'ห้องฟัน',
    'ห้องบัตร',
    'ห้องเก็บเงิน',
    'งานประกันสุขภาพ',
    'งานกายภาพบำบัด',
    'แพทย์แผนไทย',
    'จิตเวช',
    'งานควบคุมโรค',
    'งานส่งเสริมสุขภาพ',
    'กลุ่มการพยาบาล',
    'ห้องผู้อำนวยการ',
    'งานบริหารทั่วไป',
    'งานธุรการ',
    'งานพัสดุ',
    'งานการเงิน',
    'งานบัญชี',
    'งานยุทธศาสตร์',
    'ศูนย์คอมพิวเตอร์',
    'งานซ่อมบำรุง',
    'สสอ.ศรีนคร',
    'รพ.สต.นครเดิฐ',
    'รพ.สต.น้ำขุม',
    'รพ.สต.คลองมะพลับ',
    'รพ.สต.หนองบัว',
    'รพ.สต.หนองแหน'
);
$i = 0;
while ($i < count($departments)) {
    $name = mysqli_real_escape_string($conn, $departments[$i]);
    $ord = $i + 1;
    mysqli_query($conn, "INSERT INTO departments (name, sort_order, is_active) VALUES ('$name', $ord, 1)");
    $i++;
}

$categories = array(
    1 => 'คอมพิวเตอร์และอุปกรณ์',
    2 => 'ปริ้นเตอร์และหมึก',
    3 => 'เครือข่ายและอินเทอร์เน็ต',
    4 => 'ระบบโรงพยาบาล (HOSxP และโปรแกรม)',
    5 => 'ห้องประชุมและโสตทัศนูปกรณ์',
    6 => 'บัญชีผู้ใช้และสิทธิ์',
    7 => 'อื่นๆ'
);
foreach ($categories as $cid => $cname) {
    $name = mysqli_real_escape_string($conn, $cname);
    mysqli_query($conn, "INSERT INTO job_categories (id, name, sort_order, is_active) VALUES ($cid, '$name', $cid, 1)");
}

$types = array(
    1 => array('คอมพิวเตอร์เปิดไม่ติด', 'ทำความสะอาด / เป่าฝุ่น', 'เปลี่ยนอุปกรณ์ (เมาส์ คีย์บอร์ด จอ)', 'ติดตั้งคอมพิวเตอร์ใหม่', 'ย้ายคอมพิวเตอร์', 'ซ่อมฮาร์ดแวร์ / ส่งเคลม', 'อื่นๆ ด้านคอมพิวเตอร์'),
    2 => array('เปลี่ยนหมึก', 'ปริ้นไม่ออก', 'กระดาษติด', 'ติดตั้งปริ้นเตอร์ใหม่', 'ส่งซ่อมปริ้นเตอร์', 'อื่นๆ ด้านปริ้นเตอร์'),
    3 => array('เน็ตใช้ไม่ได้ / ระบบล่ม', 'แก้ไข Wi-Fi', 'ติดตั้ง Wi-Fi', 'เดินสาย LAN', 'อื่นๆ ด้านเครือข่าย'),
    4 => array('จัดทำรายงาน HOSxP', 'ติดตั้ง / อัปเดตโปรแกรม', 'แก้ไขการบันทึกข้อมูล', 'ส่งข้อมูล / สำรองข้อมูล', 'อื่นๆ ด้านระบบ'),
    5 => array('จัดห้องประชุมและเครื่องเสียง', 'ถ่ายภาพ / วีดิโอ', 'ซ่อมเครื่องเสียง / โปรเจคเตอร์', 'อื่นๆ ด้านโสตทัศนูปกรณ์'),
    6 => array('เพิ่ม/แก้ไขผู้ใช้ HOSxP', 'บัญชีอินเทอร์เน็ต / Wi-Fi', 'อื่นๆ ด้านบัญชีผู้ใช้'),
    7 => array('งานทั่วไป')
);
foreach ($types as $cid => $list) {
    $ord = 1;
    foreach ($list as $tname) {
        $name = mysqli_real_escape_string($conn, $tname);
        mysqli_query($conn, "INSERT INTO job_types (category_id, name, sort_order, is_active) VALUES ($cid, '$name', $ord, 1)");
        $ord++;
    }
}

$sla = array(
    array('ซ่อมคอมพิวเตอร์', '1-5 วัน'),
    array('เปลี่ยนตลับหมึก', '1-2 วัน'),
    array('ติดตั้งเครื่องเสียงห้องประชุม', '1-2 วัน'),
    array('ส่งซ่อมร้านเปลี่ยนอะไหล่', '10-15 วัน'),
    array('จัดทำรายงาน HOSxP', '1-5 วัน')
);
$ord = 1;
foreach ($sla as $item) {
    $t = mysqli_real_escape_string($conn, $item[0]);
    $d = mysqli_real_escape_string($conn, $item[1]);
    mysqli_query($conn, "INSERT INTO sla_items (title, duration_text, sort_order, is_active) VALUES ('$t', '$d', $ord, 1)");
    $ord++;
}

out('ใส่ข้อมูลตั้งต้นเรียบร้อย', $is_cli);
out('ผู้ใช้เริ่มต้น: admin / admin123 (ควรเปลี่ยนรหัสผ่านหลังเข้าสู่ระบบ)', $is_cli);

if (!$is_cli) {
    echo '<p><a href="index.php">เข้าสู่ระบบเว็บไซต์</a></p></body></html>';
}
