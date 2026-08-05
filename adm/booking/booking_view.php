<?php
// 예약 상세 — 한 건에 대해 업주가 할 수 있는 일이 모두 모이는 자리.
// 돈을 움직이는 버튼(취소 승인·직권 취소)은 모두 booking_update.php 로 POST 한다.
$sub_menu = '950100';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$list_url = './booking_list.php';
$bk_id = (isset($_GET['bk_id']) && !is_array($_GET['bk_id'])) ? (int)$_GET['bk_id'] : 0;

$bk = booking_get($bk_id);
if (!$bk) alert('등록된 예약이 아닙니다.', $list_url);

// 확인 비밀번호 해시는 화면에 낼 일이 없다. 뷰에 넘기는 배열에서 아예 뺀다 —
// 쓰지 않더라도 데이터가 화면 쪽으로 건너가면 언젠가 누군가 찍는다
unset($bk['bk_password']);

$room = sql_fetch(" select br_subject from `{$g5['booking_room_table']}` where br_id = '".(int)$bk['br_id']."' ");
$br_subject = $room ? $room['br_subject'] : '';

$addon_items = array();
$result = sql_query(" select bt_subject, bt_price, bt_unit, bt_qty, bt_amount
    from `{$g5['booking_addon_item_table']}` where bk_id = '$bk_id' order by bt_id ");
while ($r = sql_fetch_array($result)) $addon_items[] = $r;

// 메모 타임라인. 손님 요청과 업주 답이 시간순으로 섞인다 (고객 화면과 같은 순서)
$notes = array();
$result = sql_query(" select bn_id, bn_writer, bn_content, bn_checked, bn_datetime
    from `{$g5['booking_note_table']}` where bk_id = '$bk_id' order by bn_id ");
while ($r = sql_fetch_array($result)) {
    $notes[] = array(
        'bn_id' => (int)$r['bn_id'],
        'is_guest' => ($r['bn_writer'] !== 'admin'),
        'writer_text' => ($r['bn_writer'] === 'admin') ? '업주(고객에게 보임)' : '고객',
        'bn_content' => $r['bn_content'],
        'bn_checked' => (int)$r['bn_checked'],
        'bn_datetime' => $r['bn_datetime'],
    );
}

// 환불·망취소 기록. 돈이 실제로 어떻게 움직였는지는 예약 행이 아니라 이 로그가 원본이다
// (bl_data 원문은 내보내지 않는다 — 승인 전문 전체가 들어 있다)
$refund_logs = array();
if ($bk['bk_oid'] !== '') {
    $result = sql_query(" select bl_id, bl_type, bl_price, bl_result_code, bl_datetime, bl_tid
        from `{$g5['booking_inicis_log_table']}`
        where bl_oid = '".sql_real_escape_string($bk['bk_oid'])."' and bl_type in ('refund', 'netcancel')
        order by bl_id ");
    while ($r = sql_fetch_array($result)) {
        $refund_logs[] = array(
            'bl_type' => $r['bl_type'],
            'type_text' => ($r['bl_type'] === 'refund') ? '환불' : '망취소',
            'bl_price' => (int)$r['bl_price'],
            'bl_result_code' => $r['bl_result_code'],
            // 취소 성공 코드는 '00' 이다 (승인의 '0000' 과 코드계가 다르다)
            'ok' => ($r['bl_type'] === 'refund' && $r['bl_result_code'] === '00'),
            'bl_tid' => $r['bl_tid'],
            'bl_datetime' => $r['bl_datetime'],
        );
    }
}

// 결제 전 기본값(1970-01-01)은 날짜가 아니라 "아직 없음"이다. 화면에 흘리지 않는다
function bv_time($v) { return ($v > '1970-01-02') ? $v : ''; }

$g5['title'] = '예약 상세';
include_once(G5_ADMIN_PATH.'/admin.head.php');

badm_view('booking_view', array(
    'bk' => $bk,
    'br_subject' => (string)$br_subject,
    'nights' => count(booking_nights($bk['bk_checkin'], $bk['bk_checkout'])),
    'status_text' => booking_status_label($bk['bk_status']),
    'addon_items' => $addon_items,
    'notes' => $notes,
    'refund_logs' => $refund_logs,
    'pay_time' => bv_time($bk['bk_pay_time']),
    'cancel_time' => bv_time($bk['bk_cancel_time']),
    'refund_time' => bv_time($bk['bk_refund_time']),
    'hold_expire' => bv_time($bk['bk_hold_expire']),
    // 취소 승인은 손님이 신청한 시점에 정책으로 굳힌 금액을 그대로 쓴다(재계산하지 않는다)
    'can_approve' => ($bk['bk_status'] === 'cancel_req'),
    // 직권 취소는 확정·취소요청 두 상태에서만 (booking_update.php 의 가드와 같다)
    'can_force'   => ($bk['bk_status'] === 'confirmed' || $bk['bk_status'] === 'cancel_req'),
    'admin_url' => G5_ADMIN_URL,
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
