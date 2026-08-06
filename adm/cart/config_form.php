<?php
$sub_menu = '600400';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '카트 환경설정';
include_once(G5_ADMIN_PATH.'/admin.head.php');

cadm_view('config', array(
    'cc' => cart_config(),
    'action_url' => G5_CART_ADMIN_URL.'/config_update.php',
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
