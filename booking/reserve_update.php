<?php
include_once('./_common.php');

// 순정 get_token()/check_token() 짝. 실패하면 check_token() 이 제 안에서 alert() 로 끝내므로
// 반환값을 볼 필요가 없다 (바깥에 alert 을 덧대면 도달하지 않는 죽은 줄이 된다)
check_token();

// 배열로 온 값은 통째로 버린다 (reserve.php 와 같은 방어)
function booking_post($key)
{
    return (isset($_POST[$key]) && !is_array($_POST[$key])) ? trim((string)$_POST[$key]) : '';
}

$br_id    = (int)booking_post('br_id');
$checkin  = booking_post('checkin');
$checkout = booking_post('checkout');
$person   = (int)booking_post('person');

$room_url = G5_URL.'/booking/room.php?br_id='.$br_id;

// 동의는 화면에서도 required 지만 서버에서 다시 본다 — 폼을 거치지 않는 요청이 있다
if (booking_post('agree') === '')
    alert('취소·환불 규정에 동의하셔야 예약할 수 있습니다.');

$name = clean_xss_tags(booking_post('bk_name'));
if ($name === '') alert('예약자 이름을 입력해 주세요.');

// 숫자와 하이픈만 남긴다
$hp = preg_replace('/[^0-9\-]/', '', booking_post('bk_hp'));
if ($hp === '') alert('연락처를 입력해 주세요.');

$password = booking_post('bk_password');
if (!$is_member && strlen($password) < 4)
    alert('예약 확인용 비밀번호를 4자 이상 입력해 주세요.');

$guest = array(
    'name'  => $name,
    'hp'    => $hp,
    'email' => get_email_address(booking_post('bk_email')),
    // 요청사항은 여러 줄이다. clean_xss_tags 의 기본값은 줄바꿈까지 지우므로 마지막 인자를 끈다
    'request' => clean_xss_tags(booking_post('bk_request'), 0, 0, 0, 0),
    'mb_id' => $is_member ? $member['mb_id'] : '',
    'password' => $is_member ? '' : get_encrypt_string($password),
);

// array(ba_id => qty). 값이 또 배열인 장난은 버린다. 수량 상한은 booking_calc_price() 가 건다
$addons = array();
if (isset($_POST['addon']) && is_array($_POST['addon'])) {
    foreach ($_POST['addon'] as $ba_id => $qty) {
        if (is_array($qty)) continue;
        $addons[(int)$ba_id] = (int)$qty;
    }
}

// 일정·인원·잔여 검증과 요금 계산은 모두 이 안에서 다시 한다 (행 잠금 안)
$result = booking_create_hold($br_id, $checkin, $checkout, $person, $addons, $guest);
if (!$result['ok']) alert($result['error'], $room_url);

// pay/complete 화면의 본인 확인 수단
set_session('ss_booking_bk_no', $result['bk_no']);
goto_url(G5_URL.'/booking/pay.php');
