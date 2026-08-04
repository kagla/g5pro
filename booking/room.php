<?php
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

// 배열로 온 값은 통째로 버린다 — 배열에 (int) 를 씌우면 경고가 뜨고 엉뚱한 값이 남는다
$br_id = (isset($_GET['br_id']) && !is_array($_GET['br_id'])) ? (int)$_GET['br_id'] : 0;

$room = sql_fetch(" select * from `{$g5['booking_room_table']}`
    where br_id = '$br_id' and br_use = 1 ");
if (!$room) alert('등록된 객실이 아닙니다.', G5_URL.'/booking/');

// 갤러리 — 대표 이미지가 첫 장으로 오게 정렬한다
$images = array();
$result = sql_query(" select bi_file from `{$g5['booking_room_image_table']}`
    where br_id = '$br_id' order by bi_main desc, bi_order, bi_id ");
while ($r = sql_fetch_array($result)) $images[] = booking_image_url($r['bi_file']);

// 이 객실에 담긴 상품만 — 예약 폼·요금 계산과 같은 기준(booking_room_addons)
$addons = booking_room_addons($br_id);

// 설정은 화면에 쓰는 값만 골라 넘긴다. booking_config() 가 돌려주는 행에는
// 이니시스 상점키·API 키가 들어 있어 통째로 뷰에 넘기면 안 된다.
$bc = booking_config();

// 취소 규정 "남은일수:환불율" 을 사람이 읽는 줄로 편다. 판정 자체는 booking_refund_rate() 가 한다 —
// 둘 다 booking_cancel_rules() 한 곳에서 파싱하므로 고지와 실제 환불율이 어긋나지 않는다
$cancel_rules = booking_cancel_rules($bc['bc_cancel_policy']);

$conf = array(
    'checkin_time'  => $bc['bc_checkin_time'],
    'checkout_time' => $bc['bc_checkout_time'],
    'min_nights'    => (int)$bc['bc_min_nights'],
    'max_nights'    => (int)$bc['bc_max_nights'],
    'open_months'   => (int)$bc['bc_open_months'],
    'refund_terms'  => $bc['bc_refund_terms'],
    'cancel_rules'  => $cancel_rules,
);

// 캘린더 스크립트가 쓰는 값. 날짜 판정의 기준은 서버 시각이다 —
// 방문자 PC 시계로 정하면 시차·오차만큼 어긋난 날짜를 고를 수 있다.
$js = array(
    'br_id'         => (int)$room['br_id'],
    'ym'            => date('Y-m', G5_SERVER_TIME),
    // 넘어갈 수 있는 마지막 달 — 그 뒤는 어차피 전부 마감이라 다음 달 버튼을 잠근다
    'limit_ym'      => date('Y-m', strtotime('+'.$conf['open_months'].' month', G5_SERVER_TIME)),
    'today'         => date('Y-m-d', G5_SERVER_TIME),
    'min_nights'    => $conf['min_nights'],
    'max_nights'    => $conf['max_nights'],
    'checkin_time'  => $conf['checkin_time'],
    'checkout_time' => $conf['checkout_time'],
    'ajax_url'      => G5_URL.'/booking/ajax.calendar.php',
    'reserve_url'   => G5_URL.'/booking/reserve.php',
);

// 관리자 수정 바로가기 — 최고관리자에게만 채운다. adm/booking/_common.php 가 'super' 만
// 들여보내므로 같은 기준으로 판정한다(그보다 넓게 보여 주면 눌러도 알림만 뜬다).
// 판정은 여기서 끝내고 뷰에는 URL 만 넘긴다 — 뷰가 $is_admin 같은 전역을 들여다보지 않게.
// '&' 는 그대로 둔다. 뷰의 {{ }} 가 이스케이프하므로 여기서 &amp; 로 적으면 두 번 먹는다.
$is_super = ($is_admin === 'super');
$admin_edit_url = $is_super
    ? G5_ADMIN_URL.'/booking/room_form.php?w=u&br_id='.(int)$room['br_id'] : '';

$g5['title'] = $room['br_subject'];
g5_view('booking.room', array(
    'room' => $room, 'images' => $images, 'addons' => $addons,
    'conf' => $conf, 'js' => $js, 'admin_edit_url' => $admin_edit_url,
));
