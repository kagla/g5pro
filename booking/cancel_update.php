<?php
// 예약 취소 신청 — view.php 의 취소 폼이 보내는 자리.
//
// 여기서는 돈이 한 푼도 움직이지 않는다. 상태를 cancel_req 로 옮기고 그 시점의 환불 예정액을
// 적어 둘 뿐이다. 실제 환불은 관리자가 승인할 때 booking_refund() 가 한다 —
// 손님이 누른 즉시 카드 취소가 나가면, 업주가 정책을 다르게 판단할 여지도 되돌릴 길도 없다.
include_once('./_common.php');

// 실패는 check_token() 이 제 안에서 alert() 로 끝낸다 (note_update.php 와 같다)
check_token();

$lookup_url = G5_URL.'/booking/lookup.php';

$bk_no = (isset($_POST['bk_no']) && !is_array($_POST['bk_no'])) ? trim($_POST['bk_no']) : '';
$bk = $bk_no ? booking_get_by_no($bk_no) : null;

// 조회·추가 요청과 같은 잣대 하나만 본다 — 예약번호를 안다고 남의 예약을 취소할 수는 없다
if (!booking_owner_check($bk)) alert('예약 정보를 확인할 수 없습니다.', $lookup_url);

$view_url = G5_URL.'/booking/view.php?bk_no='.$bk['bk_no'];

// 체크인까지 남은 날. 날짜끼리만 뺀다 (시각이 섞이면 같은 날이 0 도 1 도 된다).
// view.php 가 취소 버튼을 보여 준 조건과 한 글자도 다르면 안 된다 —
// 버튼이 보이는데 눌리지 않거나, 안 보이는데 눌리는 자리가 생긴다
$today = date('Y-m-d', G5_SERVER_TIME);
$days_before = (int)floor((strtotime($bk['bk_checkin'].' 00:00:00') - strtotime($today.' 00:00:00')) / 86400);

if ($bk['bk_status'] === 'cancel_req') alert('이미 취소 신청이 접수된 예약입니다.', $view_url);
if ($bk['bk_status'] !== 'confirmed')  alert('확정된 예약만 취소 신청할 수 있습니다.', $view_url);
// 체크인 당일까지 받는다. 정책의 "0:..." 줄이 바로 당일 환불율 자리라 당일을 빼면 그 규칙이 죽는다
if ($days_before < 0) alert('체크인 날짜가 지난 예약은 취소 신청할 수 없습니다. 전화로 문의해 주세요.', $view_url);

$bc = booking_config();
$rate = booking_refund_rate($bc['bc_cancel_policy'], $days_before);
// 화면이 보여 준 예정액과 같은 식이어야 한다 (booking_refund_amount 한 곳에서만 센다)
$plan = booking_refund_amount($bk['bk_total_price'], $rate);

// 사유는 선택이다. 지금 폼에는 칸이 없지만 보내오면 받는다.
// 여러 줄 글이므로 clean_xss_tags 의 줄바꿈 제거를 끈다 (note_update.php 와 같은 처리).
// bk_cancel_memo 는 varchar(255) 다 — 넘치면 strict 모드에서 update 자체가 실패하므로 먼저 자른다
$memo = (isset($_POST['bk_cancel_memo']) && !is_array($_POST['bk_cancel_memo'])) ? trim($_POST['bk_cancel_memo']) : '';
$memo = mb_substr(clean_xss_tags($memo, 0, 0, 0, 0), 0, 255, 'UTF-8');

// where 에 bk_status='confirmed' 를 함께 건다. 폼을 두 번 보내거나 그 사이 관리자가 손을 댔다면
// 한 줄도 바뀌지 않는다 — 그때 메일까지 보내면 없던 신청이 접수된 셈이 된다
sql_query(" update `{$g5['booking_table']}` set
    bk_status = 'cancel_req',
    bk_cancel_time = '".date('Y-m-d H:i:s', G5_SERVER_TIME)."',
    bk_refund_plan_price = '".(int)$plan."',
    bk_cancel_memo = '".sql_real_escape_string($memo)."'
    where bk_id = '".(int)$bk['bk_id']."' and bk_status = 'confirmed' ", true);

if (get_sql_affected_rows() < 1)
    alert('예약 상태가 바뀌었습니다. 예약 내용을 다시 확인해 주세요.', $view_url);

booking_notify($bk['bk_id'], 'cancel_req');

alert('취소 신청이 접수되었습니다. 환불 예정액은 '.number_format($plan).'원이며, '
    . '업주 확인 뒤 환불됩니다.', $view_url);
