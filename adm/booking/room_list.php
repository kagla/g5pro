<?php
$sub_menu = '950200';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '객실관리';
include_once(G5_ADMIN_PATH.'/admin.head.php');

$rooms = array();
$result = sql_query(" select r.*, (select count(*) from `{$g5['booking_table']}` b
        where b.br_id = r.br_id and b.bk_status in ('confirmed','cancel_req')) as booking_cnt
    from `{$g5['booking_room_table']}` r order by r.br_order, r.br_id ");
while ($row = sql_fetch_array($result)) $rooms[] = $row;

badm_view('room_list', array('rooms' => $rooms, 'admin_url' => G5_ADMIN_URL, 'g5_url' => G5_URL));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
