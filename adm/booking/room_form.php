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
$mapped_ids = array();   // 이 객실에 붙은 ba_id, 노출 순서대로

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

    $result = sql_query(" select ba_id from `{$g5['booking_room_addon_table']}`
        where br_id = '$br_id' order by bra_order, ba_id ");
    while ($r = sql_fetch_array($result)) $mapped_ids[] = (int)$r['ba_id'];
}

// 숨김 상품도 목록에 낸다 — 매핑은 노출 여부와 별개다 (숨김을 풀면 그대로 다시 팔린다)
$addon_rows = array();
$result = sql_query(" select ba_id, ba_subject, ba_price, ba_use
    from `{$g5['booking_addon_table']}` order by ba_order, ba_id ");
while ($r = sql_fetch_array($result)) $addon_rows[(int)$r['ba_id']] = $r;

// 오른쪽(이 객실 상품)은 매핑 순서, 왼쪽(담을 수 있는 상품)은 상품 출력 순서
$addon_sel = array();
foreach ($mapped_ids as $ba_id) {
    if (isset($addon_rows[$ba_id])) { $addon_sel[] = $addon_rows[$ba_id]; unset($addon_rows[$ba_id]); }
}
$addon_pool = array_values($addon_rows);

$g5['title'] = ($w == 'u') ? '객실 수정' : '객실 추가';
include_once(G5_ADMIN_PATH.'/admin.head.php');

badm_view('room_form', array('w' => $w, 'room' => $room, 'images' => $images,
    'booking_cnt' => $booking_cnt, 'addon_pool' => $addon_pool, 'addon_sel' => $addon_sel,
    'admin_url' => G5_ADMIN_URL));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
