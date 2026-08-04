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

$g5['title'] = '객실 안내';
g5_view('booking.index', array('rooms' => $rooms));
