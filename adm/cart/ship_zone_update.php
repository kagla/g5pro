<?php
$sub_menu = '600460';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

// GPC addslashes 원복만 하고 넘긴다 — 이스케이프는 저장 지점(cart_ship_zone_save)이 한다
$rows = array();
if (isset($_POST['sz']) && is_array($_POST['sz'])) {
    foreach ($_POST['sz'] as $key => $row) {
        if (!is_array($row)) continue;
        $clean = array();
        foreach (array('name', 'from', 'to', 'fee', 'use') as $f) {
            $clean[$f] = (isset($row[$f]) && !is_array($row[$f]))
                ? strip_tags(stripslashes(trim($row[$f]))) : '';
        }
        $rows[(string)$key] = $clean;
    }
}
$del_ids = (isset($_POST['sz_del']) && is_array($_POST['sz_del'])) ? $_POST['sz_del'] : array();

cart_ship_zone_save($rows, $del_ids);

goto_url(G5_CART_ADMIN_URL.'/ship_zone.php');
