<?php
// 예약 상세 — 본인 확인을 통과한 사람만 볼 수 있다.
// 회원은 로그인만으로, 비회원은 lookup.php 에서 받은 인가 세션으로 들어온다.
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

$lookup_url = G5_URL.'/booking/lookup.php';

$bk_no = (isset($_GET['bk_no']) && !is_array($_GET['bk_no'])) ? trim($_GET['bk_no']) : '';
$bk = $bk_no ? booking_get_by_no($bk_no) : null;

// 없는 예약과 남의 예약을 같은 자리로 보낸다 — 답이 갈리면 예약번호가 있는지 없는지가 샌다
if (!booking_owner_check($bk)) goto_url($lookup_url);

$room = sql_fetch(" select br_subject from `{$g5['booking_room_table']}` where br_id = '".(int)$bk['br_id']."' ");
if (!$room) $room = array('br_subject' => '');

$addon_items = array();
$result = sql_query(" select bt_subject, bt_price, bt_qty, bt_amount
    from `{$g5['booking_addon_item_table']}` where bk_id = '".(int)$bk['bk_id']."' order by bt_id ");
while ($r = sql_fetch_array($result)) $addon_items[] = $r;

// 메모 타임라인. 손님이 남긴 추가 요청과 업주 답이 시간순으로 섞인다
$notes = array();
$result = sql_query(" select bn_writer, bn_content, bn_datetime
    from `{$g5['booking_note_table']}` where bk_id = '".(int)$bk['bk_id']."' order by bn_id ");
while ($r = sql_fetch_array($result)) {
    $r['writer_text'] = ($r['bn_writer'] === 'admin') ? '업주' : '고객';
    $notes[] = $r;
}

$status_label = array('hold' => '결제대기', 'confirmed' => '예약확정',
    'cancel_req' => '취소요청', 'cancelled' => '취소완료');
$status_text = isset($status_label[$bk['bk_status']]) ? $status_label[$bk['bk_status']] : $bk['bk_status'];

$bc = booking_config();

// 체크인까지 남은 날. 날짜끼리만 뺀다 (시각이 섞이면 같은 날이 0 도 1 도 된다)
$today = date('Y-m-d', G5_SERVER_TIME);
$days_before = (int)floor((strtotime($bk['bk_checkin'].' 00:00:00') - strtotime($today.' 00:00:00')) / 86400);

// 취소 신청은 확정된 예약을 체크인 당일까지. 정책의 "0:..." 줄이 바로 당일 환불율 자리라
// 당일을 빼면 그 규칙이 영영 쓰이지 않는다
$can_cancel = ($bk['bk_status'] === 'confirmed' && $days_before >= 0);
$refund_rate = booking_refund_rate($bc['bc_cancel_policy'], $days_before);
// 미리 보여 주는 예상액이다. 실제 환불액은 취소 처리 시점에 다시 계산한다
$refund_price = (int)floor((int)$bk['bk_total_price'] * $refund_rate / 100);

// 결제 전 기본값(1970-01-01)은 날짜가 아니라 "아직 없음"이다. 화면에 흘리지 않는다
$pay_time = ($bk['bk_pay_time'] > '1970-01-02') ? $bk['bk_pay_time'] : '';

$g5['title'] = '예약 상세';
g5_view('booking.view', array(
    'bk' => $bk, 'room' => $room, 'addon_items' => $addon_items, 'notes' => $notes,
    'pay_time' => $pay_time,
    'nights' => count(booking_nights($bk['bk_checkin'], $bk['bk_checkout'])),
    'status_text' => $status_text,
    'can_cancel' => $can_cancel,
    'days_before' => $days_before,
    'refund_rate' => $refund_rate,
    'refund_price' => $refund_price,
    // 설정 행에는 이니시스 상점키가 들어 있다. 화면에 쓰는 값만 골라 넘긴다
    'conf' => array(
        'checkin_time'  => $bc['bc_checkin_time'],
        'checkout_time' => $bc['bc_checkout_time'],
        'refund_terms'  => $bc['bc_refund_terms'],
    ),
    'token' => get_token(),
));
