<?php
// 결제 화면 — reserve_update.php 가 hold 를 만든 뒤 보내는 자리.
// 여기서 이니시스 표준결제(INIStdPay) 결제창을 띄운다.
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

// 예약 접근권은 세션의 예약번호 하나로만 준다 — 주소창의 bk_id 로 남의 예약을 열 수 없다
$bk_no = get_session('ss_booking_bk_no');
if (!$bk_no) alert('결제할 예약 정보가 없습니다. 다시 예약해 주세요.', G5_URL.'/booking/');

$bk = booking_get_by_no($bk_no);
if (!$bk) alert('예약 정보를 찾을 수 없습니다. 다시 예약해 주세요.', G5_URL.'/booking/');

// 이미 결제가 끝난 예약을 다시 열면(뒤로가기·새로고침) 완료 화면으로 보낸다
if ($bk['bk_status'] === 'confirmed') goto_url(G5_URL.'/booking/complete.php');
if ($bk['bk_status'] !== 'hold')
    alert('결제할 수 있는 예약이 아닙니다.', G5_URL.'/booking/');

// 남은 점유 시간. 지났으면 재고는 이미 남에게 열려 있다 — 새로 예약해야 한다
$left = strtotime($bk['bk_hold_expire']) - G5_SERVER_TIME;
if ($left <= 0) alert('예약 유효시간이 지났습니다. 다시 예약해 주세요.', G5_URL.'/booking/');

$room = sql_fetch(" select * from `{$g5['booking_room_table']}` where br_id = '".(int)$bk['br_id']."' ");
if (!$room) alert('객실 정보를 찾을 수 없습니다.', G5_URL.'/booking/');

$conf = booking_inicis_conf();
if ($conf['mid'] === '' || $conf['sign_key'] === '')
    alert('결제 설정이 아직 등록되지 않았습니다. 관리자에게 문의해 주세요.', G5_URL.'/booking/');

$addon_items = array();
$result = sql_query(" select bt_subject, bt_price, bt_qty, bt_amount
    from `{$g5['booking_addon_item_table']}` where bk_id = '".(int)$bk['bk_id']."' order by bt_id ");
while ($r = sql_fetch_array($result)) $addon_items[] = $r;

// 주문번호는 결제를 시도할 때마다 새로 발급한다. 한 번 결제창에 올린 oid 를 다시 쓰면
// 이니시스가 중복 주문번호로 되돌려 보내고, 앞선 시도의 승인 결과와도 뒤엉킨다.
// 발급한 값은 예약 행과 세션 양쪽에 둔다 — 서명(makesignature.php)은 세션 값만 믿고,
// 승인 리턴(inicis/return.php)은 예약 행의 값으로 예약을 되찾는다.
$oid = $bk['bk_no'].'T'.time().rand(10, 99);
sql_query(" update `{$g5['booking_table']}` set bk_oid = '".sql_real_escape_string($oid)."'
    where bk_id = '".(int)$bk['bk_id']."' ", true);
set_session('ss_booking_inicis_oid', $oid);

$nights = count(booking_nights($bk['bk_checkin'], $bk['bk_checkout']));

$bc = booking_config();
// 결제창이 인증을 마치면 돌아오는 자리. 창을 닫을 때 부르는 자리는 순정 빈 페이지를 그대로 쓴다
$shop_url = defined('G5_SHOP_URL') ? G5_SHOP_URL : G5_URL.'/shop';

$g5['title'] = '결제';
g5_view('booking.pay', array(
    'bk' => $bk, 'room' => $room, 'nights' => $nights, 'addon_items' => $addon_items,
    'oid' => $oid, 'left' => $left,
    // 뷰에 넘기는 설정은 화면에 필요한 두 값뿐이다. booking_inicis_conf() 를 통째로 넘기면
    // 사인키·INIAPI 키가 HTML 로 새어 나간다
    'conf' => array('mid' => $conf['mid'], 'js_url' => $conf['js_url']),
    'return_url' => G5_URL.'/booking/inicis/return.php',
    'close_url'  => $shop_url.'/inicis/close.php',
    'sign_url'   => G5_URL.'/booking/inicis/makesignature.php',
    'checkin_time' => $bc['bc_checkin_time'], 'checkout_time' => $bc['bc_checkout_time'],
));
