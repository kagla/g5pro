<?php
$sub_menu = '600450';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

// GPC addslashes 를 원복한 뒤 넘긴다 — 저장 지점(cart_delivery_company_save)이 이스케이프한다.
// 여기서 한 번 더 하면 이중 이스케이프가 된다(config_update 와 같은 방식).
$rows = array();
if (isset($_POST['dc']) && is_array($_POST['dc'])) {
    foreach ($_POST['dc'] as $key => $row) {
        if (!is_array($row)) continue;
        $clean = array();
        foreach (array('name', 'url', 'invoice', 'order', 'use') as $f) {
            $clean[$f] = (isset($row[$f]) && !is_array($row[$f]))
                ? strip_tags(stripslashes(trim($row[$f]))) : '';
        }
        $rows[(string)$key] = $clean;
    }
}
$default_key = (isset($_POST['dc_default']) && !is_array($_POST['dc_default']))
    ? stripslashes(trim($_POST['dc_default'])) : '';
$del_ids = (isset($_POST['dc_del']) && is_array($_POST['dc_del'])) ? $_POST['dc_del'] : array();

cart_delivery_company_save($rows, $default_key, $del_ids);

goto_url(G5_CART_ADMIN_URL.'/delivery_company.php');
