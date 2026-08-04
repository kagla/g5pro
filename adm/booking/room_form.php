<?php
$sub_menu = '950200';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');

$w = isset($_GET['w']) ? preg_replace('/[^a-z]/', '', (string)$_GET['w']) : '';
$br_id = isset($_GET['br_id']) ? (int)$_GET['br_id'] : 0;

// 새 객실의 기본값 — 뷰가 모든 키를 그대로 읽으므로 여기서 채워 넘긴다
$room = array('br_id' => 0, 'br_subject' => '', 'br_content' => '', 'br_base_person' => 2,
    'br_max_person' => 4, 'br_person_price' => 0, 'br_room_count' => 1,
    'br_weekday_price' => 0, 'br_weekend_price' => 0, 'br_use' => 1, 'br_order' => 0);
$images = array();
$booking_cnt = 0;

if ($w == 'u') {
    $row = sql_fetch(" select * from `{$g5['booking_room_table']}` where br_id = '$br_id' ");
    if (!$row) alert('등록된 객실이 아닙니다.', './room_list.php');
    $room = $row;

    $result = sql_query(" select * from `{$g5['booking_room_image_table']}`
        where br_id = '$br_id' order by bi_order, bi_id ");
    while ($r = sql_fetch_array($result)) $images[] = $r;

    // 삭제 버튼이 소프트 삭제로 바뀌는지 미리 알려주기 위한 건수
    $cnt = sql_fetch(" select count(*) as cnt from `{$g5['booking_table']}`
        where br_id = '$br_id' and bk_status in ('confirmed','cancel_req') ");
    $booking_cnt = (int)$cnt['cnt'];
}

$g5['title'] = ($w == 'u') ? '객실 수정' : '객실 추가';
include_once(G5_ADMIN_PATH.'/admin.head.php');

badm_view('room_form', array('w' => $w, 'room' => $room, 'images' => $images,
    'booking_cnt' => $booking_cnt, 'admin_url' => G5_ADMIN_URL));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
