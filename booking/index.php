<?php
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

$rooms = array();
$result = sql_query(" select * from `{$g5['booking_room_table']}`
    where br_use = 1 order by br_order, br_id ");
while ($row = sql_fetch_array($result)) {
    // 대표 이미지 — bi_main 이 켜진 장을 먼저, 없으면 순서상 첫 장
    $img = sql_fetch(" select bi_file from `{$g5['booking_room_image_table']}`
        where br_id = '".(int)$row['br_id']."' order by bi_main desc, bi_order, bi_id limit 1 ");
    $row['image'] = booking_image_url($img ? $img['bi_file'] : '');
    $rooms[] = $row;
}

// 관리자 바로가기 — 최고관리자에게만 채운다. adm/booking/_common.php 가 'super' 만
// 들여보내므로 같은 기준으로 판정한다(그보다 넓게 보여 주면 눌러도 알림만 뜬다).
// 판정은 여기서 끝내고 뷰에는 URL 만 넘긴다 — 뷰가 $is_admin 같은 전역을 들여다보지 않게.
// rooms 키는 관리자가 아니어도 객실마다 빈 문자열로 채워 둔다. 뷰의 반복문이 isset 없이
// 바로 읽을 수 있어야 하고, 키가 비면 그 자리에서 경고가 뜬다.
// '&' 는 그대로 둔다. 뷰의 {{ }} 가 이스케이프하므로 여기서 &amp; 로 적으면 두 번 먹는다.
$is_super = ($is_admin === 'super');
$admin_links = array('booking' => $is_super ? G5_ADMIN_URL.'/booking/booking_list.php' : '',
    'rooms' => array());
foreach ($rooms as $row) {
    $admin_links['rooms'][$row['br_id']] = $is_super
        ? G5_ADMIN_URL.'/booking/room_form.php?w=u&br_id='.(int)$row['br_id'] : '';
}

$g5['title'] = '객실 안내';
g5_view('booking.index', array('rooms' => $rooms, 'admin_links' => $admin_links));
