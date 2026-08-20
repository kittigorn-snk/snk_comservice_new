<?php

function db_config() {
    static $cfg = null;
    if ($cfg === null) {
        $cfg = include dirname(__FILE__) . '/../config/database.php';
    }
    return $cfg;
}

function db() {
    static $conn = null;
    if ($conn !== null) {
        return $conn;
    }
    $cfg = db_config();
    $conn = @mysqli_connect($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name']);
    if (!$conn) {
        return false;
    }
    mysqli_query($conn, "SET NAMES utf8");
    return $conn;
}

function db_esc($value) {
    $conn = db();
    if (!$conn) {
        return '';
    }
    return mysqli_real_escape_string($conn, (string)$value);
}

function db_query($sql) {
    $conn = db();
    if (!$conn) {
        return false;
    }
    $res = mysqli_query($conn, $sql);
    return $res;
}

function db_all($sql) {
    $rows = array();
    $res = db_query($sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function db_one($sql) {
    $rows = db_all($sql);
    if (count($rows) > 0) {
        return $rows[0];
    }
    return null;
}

function db_id() {
    $conn = db();
    if (!$conn) {
        return 0;
    }
    return mysqli_insert_id($conn);
}

function db_ready() {
    $conn = db();
    if (!$conn) {
        return false;
    }
    $row = db_one("SHOW TABLES LIKE 'users'");
    return $row ? true : false;
}
