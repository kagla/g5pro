<?php
// 결제대사 — PG 거래 기록과 예약 상태가 어긋난 건을 찾는다.
//
// 어긋남은 두 방향이다:
//   A. 승인은 성공했는데 예약이 확정되지 않았다 (돈만 나간 후보 — 손님이 손해를 본다)
//   B. 확정된 예약인데 거래번호가 없다 (역방향 — 환불할 길이 없는 예약이다)
// A 는 이 화면에서 바로 조치할 수 있고, B 는 사람이 이니시스 관리자와 맞춰 봐야 한다.
$sub_menu = '950500';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

// ── A. 승인 성공 로그가 있는데 예약이 확정·취소요청·취소완료가 아닌 건
//
// 망취소(netcancel)·환불(refund) 기록이 남은 건은 뺀다 — 이미 되돌리려 손을 댄 건이다.
// (되돌리기가 실패한 건까지 여기서 빠지므로, 그 갈래는 예약 상세의 거래 기록으로 확인한다)
$unmatched = array();
$result = sql_query(" select l.bl_id, l.bl_oid, l.bl_tid, l.bl_price, l.bl_datetime,
        b.bk_id, b.bk_no, b.bk_status, b.bk_name, b.bk_hp, b.bk_total_price,
        b.bk_checkin, b.bk_checkout, r.br_subject
    from `{$g5['booking_inicis_log_table']}` l
    left join `{$g5['booking_table']}` b on b.bk_oid = l.bl_oid
    left join `{$g5['booking_room_table']}` r on r.br_id = b.br_id
    where l.bl_type = 'auth_res' and l.bl_result_code = '0000'
      and (b.bk_id is null or b.bk_status not in ('confirmed', 'cancel_req', 'cancelled'))
      and not exists (select 1 from `{$g5['booking_inicis_log_table']}` n
                       where n.bl_oid = l.bl_oid and n.bl_type in ('netcancel', 'refund'))
    order by l.bl_id desc limit 200 ");
while ($r = sql_fetch_array($result)) {
    $bk_id = (int)$r['bk_id'];
    // 조치할 수 있는 건은 예약 행이 있고 거래번호가 남아 있고 금액이 맞는 건뿐이다.
    // 나머지는 이니시스 관리자에서 사람이 처리해야 한다 (버튼 대신 이유를 적어 준다)
    $blocked = '';
    if (!$bk_id)                                            $blocked = '이 주문번호의 예약이 없습니다.';
    else if (trim($r['bl_tid']) === '')                     $blocked = '승인 기록에 거래번호가 없습니다.';
    else if ((int)$r['bl_price'] !== (int)$r['bk_total_price']) $blocked = '승인 금액과 예약 청구액이 다릅니다.';
    $unmatched[] = array(
        'bl_id' => (int)$r['bl_id'], 'bl_oid' => $r['bl_oid'], 'bl_tid' => $r['bl_tid'],
        'bl_price' => (int)$r['bl_price'], 'bl_datetime' => $r['bl_datetime'],
        'bk_id' => $bk_id, 'bk_no' => (string)$r['bk_no'],
        'bk_status' => (string)$r['bk_status'],
        'status_text' => $bk_id ? booking_status_label($r['bk_status']) : '예약 없음',
        'bk_name' => (string)$r['bk_name'], 'bk_hp' => (string)$r['bk_hp'],
        'bk_total_price' => (int)$r['bk_total_price'],
        'stay' => $bk_id ? ($r['bk_checkin'].' ~ '.$r['bk_checkout']) : '',
        'br_subject' => (string)$r['br_subject'],
        'blocked' => $blocked,
    );
}

// ── B. 확정된 예약인데 거래번호(tid)가 비어 있는 건
$notid = array();
$result = sql_query(" select b.bk_id, b.bk_no, b.bk_status, b.bk_name, b.bk_hp, b.bk_oid,
        b.bk_total_price, b.bk_checkin, b.bk_checkout, b.bk_pay_time, r.br_subject
    from `{$g5['booking_table']}` b
    left join `{$g5['booking_room_table']}` r on r.br_id = b.br_id
    where b.bk_status = 'confirmed' and trim(b.bk_tid) = ''
    order by b.bk_id desc limit 200 ");
while ($r = sql_fetch_array($result)) {
    $notid[] = array(
        'bk_id' => (int)$r['bk_id'], 'bk_no' => $r['bk_no'], 'bk_oid' => (string)$r['bk_oid'],
        'bk_name' => $r['bk_name'], 'bk_hp' => $r['bk_hp'],
        'bk_total_price' => (int)$r['bk_total_price'],
        'stay' => $r['bk_checkin'].' ~ '.$r['bk_checkout'],
        'br_subject' => (string)$r['br_subject'],
        'pay_time' => ($r['bk_pay_time'] > '1970-01-02') ? $r['bk_pay_time'] : '',
    );
}

$g5['title'] = '결제점검';
include_once(G5_ADMIN_PATH.'/admin.head.php');

badm_view('recon', array(
    'unmatched' => $unmatched, 'notid' => $notid,
    'admin_url' => G5_ADMIN_URL,
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
