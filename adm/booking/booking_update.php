<?php
// 예약 상세 화면(booking_view.php)의 액션 처리 — 취소 승인 · 직권 취소 · 업주 답변.
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

default:
    alert('올바른 방법으로 이용해 주십시오.', $view_url);
}
