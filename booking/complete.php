<?php
// 결제 완료 화면 — booking/inicis/return.php 가 예약을 확정한 뒤 보내는 자리
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

$bk_no = get_session('ss_booking_bk_no');
if (!$bk_no) alert('예약 정보가 없습니다.', G5_URL.'/booking/');

$bk = booking_get_by_no($bk_no);
if (!$bk) alert('예약 정보를 찾을 수 없습니다.', G5_URL.'/booking/');

// 아직 결제 전이면 결제 화면이 제자리다 (결제 없이 이 주소를 직접 열어 본 경우)
if ($bk['bk_status'] === 'hold') goto_url(G5_URL.'/booking/pay.php');
if ($bk['bk_status'] !== 'confirmed')
    alert('결제가 완료된 예약이 아닙니다.', G5_URL.'/booking/');

// 결제 흐름은 여기서 끝난다 — 남은 주문번호로 서명을 더 받을 일이 없다
set_session('ss_booking_inicis_oid', '');

$room = sql_fetch(" select * from `{$g5['booking_room_table']}` where br_id = '".(int)$bk['br_id']."' ");
if (!$room) $room = array('br_subject' => '');

$addon_items = array();
$result = sql_query(" select bt_subject, bt_price, bt_unit, bt_qty, bt_amount
    from `{$g5['booking_addon_item_table']}` where bk_id = '".(int)$bk['bk_id']."' order by bt_id ");
while ($r = sql_fetch_array($result)) $addon_items[] = $r;

$bc = booking_config();

$g5['title'] = '예약 완료';
g5_view('booking.complete', array(
    'bk' => $bk, 'room' => $room, 'addon_items' => $addon_items,
    'nights' => count(booking_nights($bk['bk_checkin'], $bk['bk_checkout'])),
    'is_member' => (bool)$is_member,
    'conf' => array(
        'checkin_time'  => $bc['bc_checkin_time'],
        'checkout_time' => $bc['bc_checkout_time'],
        'refund_terms'  => $bc['bc_refund_terms'],
    ),
));
