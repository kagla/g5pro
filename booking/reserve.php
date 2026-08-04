<?php
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

// 배열로 온 값은 통째로 버린다 — 배열에 (int) 를 씌우면 경고가 뜨고 엉뚱한 값이 남는다
$br_id    = (isset($_GET['br_id']) && !is_array($_GET['br_id'])) ? (int)$_GET['br_id'] : 0;
$checkin  = (isset($_GET['checkin']) && !is_array($_GET['checkin'])) ? trim($_GET['checkin']) : '';
$checkout = (isset($_GET['checkout']) && !is_array($_GET['checkout'])) ? trim($_GET['checkout']) : '';

$room = sql_fetch(" select * from `{$g5['booking_room_table']}`
    where br_id = '$br_id' and br_use = 1 ");
if (!$room) alert('등록된 객실이 아닙니다.', G5_URL.'/booking/');

$room_url = G5_URL.'/booking/room.php?br_id='.$br_id;

// 인원은 폼에서 고른다. 여기서는 기준 인원으로 일정만 검증한다 —
// 기준 인원은 br_max_person 이하가 보장되므로 인원 때문에 걸리지 않는다
$person = (int)$room['br_base_person'];
$error = booking_validate_stay($room, $checkin, $checkout, $person);
if ($error) alert($error, $room_url);

// 잔여 재확인. 캘린더를 본 뒤 남이 먼저 잡았을 수 있다.
// 여기를 통과해도 확정은 아니다 — 진짜 판정은 booking_create_hold() 가 행 잠금 안에서 다시 한다
$nights = booking_nights($checkin, $checkout);
foreach ($nights as $date) {
    if (booking_remain_count($room, $date) < 1)
        alert($date.' 은(는) 예약이 마감되었습니다.', $room_url);
}

// 이 객실에 담긴 상품만. 최종 방어는 booking_calc_price() 의 매핑 조건이다
$addons = booking_room_addons($br_id);

// 기준 인원·부가상품 0개일 때의 금액. 인원·수량을 바꾸면 화면 JS 가 같은 식으로 다시 센다.
// 제출 뒤 실제 청구액은 booking_create_hold() 안의 booking_calc_price() 가 다시 계산한 값이다
$price = booking_calc_price($room, $checkin, $checkout, $person, array());

// 설정은 화면에 쓰는 값만 골라 넘긴다. booking_config() 가 돌려주는 행에는
// 이니시스 상점키·API 키가 들어 있어 통째로 뷰에 넘기면 안 된다.
$bc = booking_config();
$conf = array(
    'checkin_time'  => $bc['bc_checkin_time'],
    'checkout_time' => $bc['bc_checkout_time'],
    'hold_minutes'  => (int)$bc['bc_hold_minutes'],
    'refund_terms'  => $bc['bc_refund_terms'],
);

// 회원이면 예약자 정보를 채워 둔다 (고칠 수 있다 — 예약자와 회원이 늘 같지는 않다)
$guest = array(
    'name'  => $is_member ? $member['mb_name'] : '',
    'hp'    => $is_member ? $member['mb_hp'] : '',
    'email' => $is_member ? $member['mb_email'] : '',
);

$g5['title'] = $room['br_subject'].' 예약';
g5_view('booking.reserve', array(
    'room' => $room, 'checkin' => $checkin, 'checkout' => $checkout,
    'nights' => count($nights), 'person' => $person, 'addons' => $addons,
    'price' => $price, 'conf' => $conf, 'guest' => $guest,
    'is_member' => (bool)$is_member, 'token' => get_token(),
));
