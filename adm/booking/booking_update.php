<?php
// 관리자 액션 처리 — 예약 상세(booking_view.php)의 취소 승인 · 직권 취소 · 업주 답변 · 요청 확인,
// 그리고 결제대사(recon.php)의 확정 · 환불.
//
// 돈이 나가는 자리는 booking_refund() 하나뿐이다. 여기서는 "누가 무엇을 시켰는가"만 가리고
// 금액·상태 판정은 모두 그 함수 안에서 예약 행을 잠근 채 다시 한다 —
// 화면에서 읽어 온 값으로 환불액을 정하면 그 사이 바뀐 예약에 옛 금액이 나간다.
$sub_menu = '950100';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$act   = (isset($_POST['act']) && !is_array($_POST['act'])) ? preg_replace('/[^a-z_]/', '', (string)$_POST['act']) : '';
$bk_id = (isset($_POST['bk_id']) && !is_array($_POST['bk_id'])) ? (int)$_POST['bk_id'] : 0;

$list_url = './booking_list.php';

$bk = booking_get($bk_id);
if (!$bk) alert('등록된 예약이 아닙니다.', $list_url);

$view_url = './booking_view.php?bk_id='.$bk_id;

switch ($act) {

// ---------------------------------------------------------------- 취소 승인
// 손님이 신청한 건을 그대로 받아들인다. 금액은 신청 시점에 정책으로 계산해 적어 둔
// bk_refund_plan_price 를 쓴다 — 승인이 늦어졌다고 손님이 본 금액이 줄어들면 안 된다
case 'cancel_approve':
    if ($bk['bk_status'] !== 'cancel_req')
        alert('취소 요청 상태인 예약만 승인할 수 있습니다. (현재 상태: '.$bk['bk_status'].')', $view_url);

    $r = booking_refund($bk, (int)$bk['bk_refund_plan_price'], '취소 승인');
    // 실패해도 예약은 그대로다. 사유를 그대로 보여 주고 다시 시도할 수 있게 둔다
    if (!$r['ok']) alert($r['msg'], $view_url);
    alert($r['msg'], $view_url, false);
    break;

// ---------------------------------------------------------------- 직권 취소
// 정책 밖의 결정(업주 사정, 협의 환불 등). 금액을 관리자가 직접 적는다
case 'force_cancel':
    if ($bk['bk_status'] !== 'confirmed' && $bk['bk_status'] !== 'cancel_req')
        alert('확정 또는 취소요청 상태인 예약만 직권 취소할 수 있습니다. (현재 상태: '.$bk['bk_status'].')', $view_url);

    $raw = (isset($_POST['refund_price']) && !is_array($_POST['refund_price'])) ? trim((string)$_POST['refund_price']) : '';
    $raw = str_replace(array(',', ' '), '', $raw);   // 12,000 처럼 적어도 받는다
    if ($raw === '' || !preg_match('/^\d+$/', $raw))
        alert('환불 금액을 0 이상의 숫자로 입력하세요.', $view_url);

    $refund_price = (int)$raw;
    // 결제한 것보다 더 돌려줄 수는 없다. 여기서 막지 않으면 이니시스가 거절할 뿐이지만,
    // 오타를 전문까지 들고 가는 것보다 화면에서 끊는 편이 낫다
    if ($refund_price > (int)$bk['bk_total_price'])
        alert('환불 금액은 결제 금액 '.number_format((int)$bk['bk_total_price']).'원을 넘을 수 없습니다.', $view_url);

    $r = booking_refund($bk, $refund_price, '관리자 직권 취소');
    if (!$r['ok']) alert($r['msg'], $view_url);
    alert($r['msg'], $view_url, false);
    break;

// ---------------------------------------------------------------- 업주 답변
// 여기서 남긴 글은 booking/view.php 의 "요청 · 답변" 타임라인에 '업주' 로 그대로 보인다.
// 내부 메모가 아니다 — 화면(booking_view.php)에도 "고객에게 보이는 답변"임을 적어 둔다
case 'note_add':
    $content = isset($_POST['bn_content']) ? clean_xss_tags(stripslashes((string)$_POST['bn_content']), 0, 0, 0, 0) : '';
    if (trim($content) === '') alert('답변 내용을 입력하세요.', $view_url);
    // 손님 요청과 같은 상한. 글자로 센다 (bn_content 는 text 지만 화면에 그대로 실린다)
    $content = mb_substr($content, 0, 2000, 'UTF-8');

    sql_query(" insert into `{$g5['booking_note_table']}` set
        bk_id = '$bk_id',
        bn_writer = 'admin',
        bn_content = '".sql_real_escape_string($content)."',
        bn_checked = 1,
        bn_datetime = '".G5_TIME_YMDHIS."' ", true);

    goto_url($view_url);
    break;

// ---------------------------------------------------------------- 요청 확인 처리
// 손님이 남긴 요청을 읽었다고 표시한다. 목록의 "미확인 요청" 배지를 끄는 자리다
case 'note_check':
    $bn_id = (isset($_POST['bn_id']) && !is_array($_POST['bn_id'])) ? (int)$_POST['bn_id'] : 0;
    if (!$bn_id) alert('확인할 요청을 고르세요.', $view_url);
    // bk_id 를 함께 건다 — 예약을 넘나들며 남의 요청을 건드리지 못하게 한다
    sql_query(" update `{$g5['booking_note_table']}` set bn_checked = 1
        where bn_id = '$bn_id' and bk_id = '$bk_id' ", true);

    goto_url($view_url);
    break;

// ---------------------------------------------------------------- 결제대사 조치
// recon.php 의 A 케이스 — 승인 로그는 성공('0000')인데 예약이 확정되지 않은 건이다.
// 돈은 나갔고 자리는 잡히지 않았다. 둘 중 하나로만 끝낼 수 있다:
//   recon_confirm — 자리가 남아 있으면 그때 못한 확정을 지금 한다
//   recon_refund  — 돌려준다
//
// 두 갈래 모두 먼저 "결제를 예약 행에 붙이는" 한 걸음(claim)을 밟는다. 승인 로그의 거래번호를
// bk_tid 에 적고 상태를 confirmed 로 옮기는 일이다. 이유는 둘이다:
//   1) 환불은 booking_refund() 하나로만 나가야 하는데 그 함수는 confirmed·cancel_req 만 받는다.
//      상태를 옮겨 두면 잠금·중복 방어·로그가 전부 그 함수의 것을 그대로 쓴다
//   2) hold 행에는 tid 가 없다 (확정할 때 적히는 값이다). 로그에서 옮겨 오지 않으면 환불할 수 없다
// claim 은 `where bk_status = 잠글 때 읽은 상태` + affected rows 로 한 번만 성공한다 —
// 두 관리자가 같은 줄의 버튼을 동시에 눌러도 뒤엣것은 "이미 확정된 예약"으로 거부된다.
case 'recon_refund':
case 'recon_confirm':
    // 이 두 액션은 결제대사 화면의 것이다 (최고관리자만 오는 자리지만 권한도 그쪽으로 본다)
    auth_check_menu($auth, '950500', 'w');
    $recon_url = './recon.php';

    $bl_id = (isset($_POST['bl_id']) && !is_array($_POST['bl_id'])) ? (int)$_POST['bl_id'] : 0;
    // 근거는 승인 로그뿐이다. 로그 없이 확정하면 돈을 받지 않은 예약이 확정된다
    $log = $bl_id ? sql_fetch(" select * from `{$g5['booking_inicis_log_table']}`
        where bl_id = '$bl_id' and bl_type = 'auth_res' and bl_result_code = '0000' ") : null;
    if (!$log) alert('승인 성공 기록을 찾을 수 없습니다.', $recon_url);
    if ($log['bl_oid'] === '' || $log['bl_oid'] !== $bk['bk_oid'])
        alert('승인 기록과 예약의 주문번호가 서로 다릅니다.', $recon_url);
    if (trim($log['bl_tid']) === '')
        alert('승인 기록에 거래번호(tid)가 없습니다. 이니시스 관리자에서 직접 처리하십시오.', $recon_url);
    // 승인된 금액과 청구액이 다르면 사람이 봐야 한다 — 전액이 얼마인지 우리가 정할 수 없다
    if ((int)$log['bl_price'] !== (int)$bk['bk_total_price'])
        alert('승인 금액('.number_format((int)$log['bl_price']).'원)과 예약 청구액('
            .number_format((int)$bk['bk_total_price']).'원)이 다릅니다. 이니시스 관리자에서 직접 확인하십시오.', $recon_url);

    $now = date('Y-m-d H:i:s', G5_SERVER_TIME);
    // 결제 시각은 승인 로그가 남은 시각이 가장 가깝다
    $pay_time = ($log['bl_datetime'] > '1970-01-02') ? $log['bl_datetime'] : $now;

    sql_query(" set autocommit = 0 ", true);
    sql_query(" start transaction ", true);
    // 잠금 순서는 booking_create_hold()·return.php 와 같게 객실 → 예약이다 (교착 방지)
    $room = sql_fetch(" select * from `{$g5['booking_room_table']}` where br_id = '".(int)$bk['br_id']."' for update ");
    $cur  = sql_fetch(" select * from `{$g5['booking_table']}` where bk_id = '$bk_id' for update ");

    $err = '';
    if (!$cur)                                   $err = '예약 정보를 찾을 수 없습니다.';
    else if ($cur['bk_status'] === 'confirmed')  $err = '이미 확정된 예약입니다. 예약 상세에서 처리하십시오.';
    else if ($cur['bk_status'] !== 'hold' && $cur['bk_status'] !== 'expired')
        $err = '결제대기 상태인 예약만 이 화면에서 처리할 수 있습니다. (현재 상태: '.$cur['bk_status'].')';
    else if ($cur['bk_oid'] !== $log['bl_oid'])  $err = '예약의 주문번호가 그 사이 바뀌었습니다.';
    else if ((int)$cur['bk_total_price'] !== (int)$log['bl_price'])
        $err = '예약 청구액이 그 사이 바뀌었습니다.';
    else if ($act === 'recon_confirm') {
        // 확정은 자리가 있어야 한다. 아직 살아 있는 점유는 booking_booked_count() 가 이미
        // 제 몫으로 세고 있으므로, 만료된 건에 대해서만 잔여를 본다 (return.php 와 같은 판정)
        if (!$room) $err = '객실 정보가 없습니다.';
        else if (strtotime($cur['bk_hold_expire']) < G5_SERVER_TIME) {
            foreach (booking_nights($cur['bk_checkin'], $cur['bk_checkout']) as $date) {
                if (booking_remain_count($room, $date) < 1) {
                    $err = $date.' 은(는) 다른 예약이 차 있어 확정할 수 없습니다. 환불로 처리하십시오.'; break;
                }
            }
        }
    }

    if (!$err) {
        sql_query(" update `{$g5['booking_table']}` set bk_status = 'confirmed',
            bk_tid = '".sql_real_escape_string(trim($log['bl_tid']))."',
            bk_pay_time = '".sql_real_escape_string($pay_time)."'
            where bk_id = '$bk_id' and bk_status = '".sql_real_escape_string($cur['bk_status'])."' ", true);
        if (get_sql_affected_rows() < 1) $err = '예약 상태가 그 사이 바뀌어 처리하지 못했습니다.';
    }

    if ($err) sql_query(" rollback ", true);
    else      sql_query(" commit ", true);
    sql_query(" set autocommit = 1 ", true);   // 어느 갈래로 가든 원래대로 돌려 놓는다

    if ($err) alert($err, $recon_url);

    if ($act === 'recon_confirm') {
        // 손님은 결제를 마치고도 확정 안내를 받지 못한 상태다. 지금 보낸다
        booking_send_mail($bk_id, 'confirm');
        alert('예약을 확정했습니다. ('.$bk['bk_no'].')', $recon_url, false);
    }

    // 환불 — 여기서부터는 예약이 confirmed 다. 돈은 booking_refund() 하나로만 나간다
    // (잠금·상태 재검증·중복 방어·거래 로그가 모두 그 안에 있다)
    $r = booking_refund(booking_get($bk_id), (int)$cur['bk_total_price'], '결제대사 환불');
    if (!$r['ok'])
        alert('결제를 예약에 연결했으나 환불에 실패했습니다. '.$r['msg']
            ."\\n예약이 확정 상태로 남았습니다. 예약 상세에서 직권 취소로 다시 시도하십시오.", $view_url);
    alert($r['msg'], $recon_url, false);
    break;

default:
    alert('올바른 방법으로 이용해 주십시오.', $view_url);
}
