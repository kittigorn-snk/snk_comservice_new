<?php

function current_user() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $id = (int)$_SESSION['user_id'];
    if ($id <= 0) {
        return null;
    }
    return db_one("SELECT * FROM users WHERE id = " . $id . " AND is_active = 1");
}

function is_logged_in() {
    return current_user() ? true : false;
}

function is_admin() {
    $user = current_user();
    return $user && $user['role'] == 'admin';
}

function require_login() {
    if (!is_logged_in()) {
        flash_set('warning', 'กรุณาเข้าสู่ระบบสำหรับเจ้าหน้าที่');
        redirect('index.php?p=login');
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        flash_set('danger', 'เฉพาะผู้ดูแลระบบเท่านั้นที่ดำเนินการนี้ได้');
        redirect('index.php');
    }
}

function user_count() {
    $row = db_one("SELECT COUNT(*) AS c FROM users");
    return $row ? (int)$row['c'] : 0;
}

function admin_count() {
    $row = db_one("SELECT COUNT(*) AS c FROM users WHERE role = 'admin' AND is_active = 1");
    return $row ? (int)$row['c'] : 0;
}

function hash_equals_safe($known, $given) {
    $known = (string)$known;
    $given = (string)$given;
    if (strlen($known) !== strlen($given)) {
        return false;
    }
    $diff = 0;
    $i = 0;
    $len = strlen($known);
    while ($i < $len) {
        $diff |= ord($known[$i]) ^ ord($given[$i]);
        $i++;
    }
    return $diff === 0;
}

function username_validate($username) {
    if (strlen($username) < 3 || strlen($username) > 32) {
        return 'ชื่อผู้ใช้ต้องยาว 3-32 ตัวอักษร';
    }
    if (!preg_match('/^[A-Za-z0-9._-]+$/', $username)) {
        return 'ชื่อผู้ใช้ใช้ได้เฉพาะ a-z, 0-9, จุด, ขีดกลาง และขีดล่าง';
    }
    return '';
}

function password_validate($password, $username) {
    $len = strlen($password);
    if ($len < 10 || $len > 128) {
        return 'รหัสผ่านต้องยาว 10-128 ตัวอักษร';
    }
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        return 'รหัสผ่านต้องมีทั้งตัวอักษรและตัวเลข';
    }
    if (strtolower($password) === strtolower($username)) {
        return 'รหัสผ่านต้องไม่ซ้ำกับชื่อผู้ใช้';
    }
    $weak = array('password', 'password123', 'admin123', '1234567890', 'snk11249', 'qwerty1234');
    $i = 0;
    while ($i < count($weak)) {
        if (strtolower($password) === $weak[$i]) {
            return 'รหัสผ่านนี้เดาง่ายเกินไป กรุณาตั้งรหัสใหม่';
        }
        $i++;
    }
    return '';
}

function password_make($plain) {
    $salt = substr(sha1(uniqid(mt_rand(), true)), 0, 16);
    $hash = $plain;
    $i = 0;
    while ($i < 8000) {
        $hash = hash('sha256', $salt . $hash);
        $i++;
    }
    return 'v2$' . $salt . '$' . $hash;
}

function password_check($plain, $stored) {
    $parts = explode('$', $stored);
    if (count($parts) == 3 && $parts[0] == 'v2') {
        $salt = $parts[1];
        $hash = $plain;
        $i = 0;
        while ($i < 8000) {
            $hash = hash('sha256', $salt . $hash);
            $i++;
        }
        return hash_equals_safe($parts[2], $hash);
    }
    if (count($parts) == 2) {
        return hash_equals_safe($parts[1], hash('sha256', $parts[0] . $plain));
    }
    return false;
}

function auth_client_ip() {
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
}

function auth_lock_file() {
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'snk_login_' . md5(auth_client_ip());
}

function auth_is_locked() {
    $file = auth_lock_file();
    if (!is_file($file)) {
        return false;
    }
    $data = @file_get_contents($file);
    $info = $data ? unserialize($data) : false;
    if (!$info || !isset($info['until'])) {
        return false;
    }
    if (time() < (int)$info['until']) {
        return true;
    }
    @unlink($file);
    return false;
}

function auth_fail_login() {
    $file = auth_lock_file();
    $info = array('fails' => 0, 'until' => 0);
    if (is_file($file)) {
        $old = unserialize(@file_get_contents($file));
        if (is_array($old)) {
            $info = $old;
        }
    }
    $info['fails'] = isset($info['fails']) ? ((int)$info['fails'] + 1) : 1;
    if ($info['fails'] >= 5) {
        $info['until'] = time() + 900;
    }
    @file_put_contents($file, serialize($info));
    @usleep(400000);
}

function auth_clear_lock() {
    $file = auth_lock_file();
    if (is_file($file)) {
        @unlink($file);
    }
}

function auth_login($username, $password) {
    $username = trim($username);
    if ($username == '' || $password == '') {
        return false;
    }
    if (auth_is_locked()) {
        return false;
    }
    $user = db_one("SELECT * FROM users WHERE username = '" . db_esc($username) . "' AND is_active = 1");
    if (!$user || !password_check($password, $user['password_hash'])) {
        auth_fail_login();
        return false;
    }
    auth_clear_lock();
    if (function_exists('session_regenerate_id')) {
        @session_regenerate_id(true);
    }
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['csrf_token'] = sha1(uniqid(mt_rand(), true));
    return true;
}

function auth_logout() {
    unset($_SESSION['user_id']);
    unset($_SESSION['csrf_token']);
    if (function_exists('session_regenerate_id')) {
        @session_regenerate_id(true);
    }
}

function user_exists($username) {
    $row = db_one("SELECT id FROM users WHERE username = '" . db_esc($username) . "'");
    return $row ? true : false;
}

function user_create($username, $fullname, $password, $role) {
    $username = trim($username);
    $fullname = trim($fullname);
    $role = ($role == 'admin') ? 'admin' : 'staff';
    $err = username_validate($username);
    if ($err != '') {
        return $err;
    }
    $err = password_validate($password, $username);
    if ($err != '') {
        return $err;
    }
    if ($fullname == '' || strlen($fullname) > 100) {
        return 'กรุณากรอกชื่อ-สกุล ไม่เกิน 100 ตัวอักษร';
    }
    if (user_exists($username)) {
        return 'ชื่อผู้ใช้นี้ถูกใช้แล้ว';
    }
    $now = now_dt();
    $hash = password_make($password);
    $ok = db_query("INSERT INTO users (username, password_hash, fullname, role, is_active, created_at) VALUES ("
        . "'" . db_esc($username) . "',"
        . "'" . db_esc($hash) . "',"
        . "'" . db_esc($fullname) . "',"
        . "'" . $role . "', 1, '" . $now . "')");
    if (!$ok) {
        return 'ไม่สามารถสร้างบัญชีได้ กรุณาลองใหม่';
    }
    return '';
}
