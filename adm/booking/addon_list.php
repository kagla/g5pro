<?php
$sub_menu = '950400';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '부가상품관리';
include_once(G5_ADMIN_PATH.'/admin.head.php');

$addons = array();
$result = sql_query(" select * from `{$g5['booking_addon_table']}` order by ba_order, ba_id ");
while ($row = sql_fetch_array($result)) $addons[] = $row;

badm_view('addon_list', array('addons' => $addons, 'admin_url' => G5_ADMIN_URL));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
