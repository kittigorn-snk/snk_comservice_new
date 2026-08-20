<?php
error_reporting(E_ALL);
@ini_set('display_errors', '0');
@date_default_timezone_set('Asia/Bangkok');

if (!headers_sent()) {
    $secure = false;
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] && $_SERVER['HTTPS'] != 'off') {
        $secure = true;
    }
    session_set_cookie_params(0, '/', '', $secure, true);
    header('Content-Type: text/html; charset=utf-8');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
}
@ini_set('session.use_only_cookies', '1');
@ini_set('session.use_trans_sid', '0');
if (!isset($_SESSION)) {
    session_start();
}

require_once dirname(__FILE__) . '/db.php';
require_once dirname(__FILE__) . '/helpers.php';
require_once dirname(__FILE__) . '/auth.php';
require_once dirname(__FILE__) . '/layout.php';
