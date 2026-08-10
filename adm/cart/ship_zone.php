<?php
$sub_menu = '600460';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '추가배송비';
include_once(G5_ADMIN_PATH.'/admin.head.php');

$cc = cart_config();

// 안 쓰는 권역도 보여 준다 — 이 화면이 켜고 끄는 자리다
cadm_view('ship_zone', array(
    'zones' => cart_ship_zone_list(false),
    'ship_base' => (int)$cc['cc_ship_base'],
    'ship_free' => (int)$cc['cc_ship_free'],
    'config_url' => G5_CART_ADMIN_URL.'/config_form.php',
    'token' => get_token(),
    'action_url' => G5_CART_ADMIN_URL.'/ship_zone_update.php',
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
