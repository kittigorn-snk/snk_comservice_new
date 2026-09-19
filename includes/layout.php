<?php

function layout_start($title, $active) {
    $user = current_user();
    $flash = flash_get();
    $pending = 0;
    if (db_ready()) {
        $row = db_one("SELECT COUNT(*) AS c FROM jobs WHERE status IN ('pending','in_progress','waiting_parts')");
        $pending = $row ? (int)$row['c'] : 0;
    }
    echo '<!DOCTYPE html>' . "\n";
    echo '<html lang="th">' . "\n<head>\n";
    echo '<meta charset="utf-8" />' . "\n";
    echo '<meta name="viewport" content="width=device-width, initial-scale=1" />' . "\n";
    echo '<title>' . h($title) . ' | ศูนย์คอมพิวเตอร์ โรงพยาบาลศรีนคร</title>' . "\n";
    echo '<script>(function(){try{var t=localStorage.getItem("snk-theme");if(t==="dark"||(!t&&window.matchMedia&&window.matchMedia("(prefers-color-scheme: dark)").matches)){document.documentElement.className+=" dark";}}catch(e){}})();</script>' . "\n";
    echo '<link rel="stylesheet" href="assets/css/app.css?v=' . @filemtime(dirname(__FILE__) . '/../assets/css/app.css') . '" />' . "\n";
    echo "</head>\n<body>\n";
    echo '<header class="app-header">' . "\n";
    echo '  <div class="container-app header-inner">' . "\n";
    echo '    <a class="brand" href="index.php">' . "\n";
    echo '      <span class="brand-mark">IT</span>' . "\n";
    echo '      <span class="brand-text"><strong>ศูนย์คอมพิวเตอร์</strong><small>โรงพยาบาลศรีนคร</small></span>' . "\n";
    echo '    </a>' . "\n";
    echo '    <nav class="app-nav" id="appNav">' . "\n";
    echo nav_link('home', $active, 'หน้าแรก', 'index.php');
    echo nav_link('job_form', $active, 'แจ้งงาน', 'index.php?p=job_form');
    echo nav_link('job_list', $active, 'ติดตามงาน', 'index.php?p=job_list');
    echo nav_link('calendar', $active, 'ปฏิทิน', 'index.php?p=calendar');
    if ($user) {
        echo nav_link('reports', $active, 'รายงาน', 'index.php?p=reports');
        $settings_on = ($active == 'settings' || $active == 'job_types' || $active == 'departments');
        $settings_tab = isset($_GET['tab']) ? $_GET['tab'] : '';
        echo '<details class="nav-drop" id="settingsDrop">';
        echo '<summary class="nav-drop-btn' . ($settings_on ? ' active' : '') . '">ตั้งค่าระบบ</summary>';
        echo '<div class="nav-drop-menu">';
        echo '<a href="index.php?p=job_types"' . ($active == 'job_types' ? ' class="active"' : '') . '>ประเภทงาน</a>';
        echo '<a href="index.php?p=departments"' . ($active == 'departments' ? ' class="active"' : '') . '>หน่วยงาน</a>';
        echo '<a href="index.php?p=settings"' . (($active == 'settings' && $settings_tab != 'users') ? ' class="active"' : '') . '>ข้อตกลงบริการ</a>';
        if ($user['role'] == 'admin') {
            $reset_n = password_reset_pending_count();
            $users_label = 'ผู้ใช้';
            if ($reset_n > 0) {
                $users_label .= ' (' . $reset_n . ')';
            }
            echo '<a href="index.php?p=settings&amp;tab=users"' . ($settings_tab == 'users' ? ' class="active"' : '') . '>' . h($users_label) . '</a>';
        }
        echo '</div></details>';
        if ($user['role'] == 'admin') {
            echo nav_link('register', $active, 'สมัครสมาชิก', 'index.php?p=register');
        }
        echo '<span class="nav-user">' . h($user['fullname']) . '</span>';
        echo '<a class="nav-logout" href="index.php?p=logout">ออกจากระบบ</a>';
    } else {
        echo nav_link('login', $active, 'ผู้ดูแลระบบ', 'index.php?p=login');
    }
    echo '    </nav>' . "\n";
    echo '    <div class="header-actions">' . "\n";
    echo '      <button class="theme-toggle" type="button" id="themeToggle" aria-label="สลับโหมดสี"><span class="theme-toggle-light">โหมดมืด</span><span class="theme-toggle-dark">โหมดสว่าง</span></button>' . "\n";
    echo '      <button class="nav-toggle" type="button" id="navToggle" aria-label="เมนู">เมนู</button>' . "\n";
    echo '    </div>' . "\n";
    echo '  </div>' . "\n";
    echo '</header>' . "\n";
    echo '<main class="app-main">' . "\n";
    echo '<div class="container-app">' . "\n";
    if ($flash) {
        echo '<div class="flash flash-' . h($flash['type']) . '">' . h($flash['message']) . '</div>';
    }
    echo '<div class="pending-chip" id="pendingChip">งานค้าง <strong id="pendingCount">' . (int)$pending . '</strong></div>';
}

function nav_link($page, $active, $label, $href) {
    $cls = ($page == $active) ? ' class="active"' : '';
    return '<a' . $cls . ' href="' . h($href) . '">' . h($label) . '</a>';
}

function layout_end($extra_js = '') {
    echo '</div>' . "\n";
    echo '</main>' . "\n";
    echo '<footer class="app-footer"><div class="container-app">ศูนย์คอมพิวเตอร์ โรงพยาบาลศรีนคร · โทร 055-652725 ต่อ 118</div></footer>' . "\n";
    echo '<script src="assets/js/app.js?v=' . @filemtime(dirname(__FILE__) . '/../assets/js/app.js') . '"></script>' . "\n";
    echo $extra_js;
    echo "</body>\n</html>";
}

function badge_status($status) {
    return '<span class="badge-st ' . h(status_class($status)) . '">' . h(status_label($status)) . '</span>';
}

function badge_priority($priority) {
    if ($priority == 'urgent') {
        return '<span class="badge-st st-urgent">ด่วน</span>';
    }
    return '<span class="badge-st st-normal">ปกติ</span>';
}
