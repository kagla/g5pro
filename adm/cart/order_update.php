<?php
$sub_menu = '600060';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');

// 관리자 폼은 admin.js 가 제출 시 token 을 관리자 토큰(get_admin_token)으로 덮는다 —
// 프론트식 check_token() 을 쓰면 항상 불일치("올바른 방법으로...")가 난다. 다른 카트 관리자
// update 들과 같은 check_admin_token() 을 쓴다.
check_admin_token();

$post = function ($key) {
    return (isset($_POST[$key]) && !is_array($_POST[$key])) ? stripslashes(trim($_POST[$key])) : '';
};
$od_id = (int)$post('od_id');
$mode = $post('mode');
$back = G5_CART_ADMIN_URL.'/order_view.php?od_id='.$od_id;
// 목록 화면에서 온 요청은 그 목록으로 돌려보낸다 — 여러 건을 이어서 처리하는 화면들이다
if ($post('ret') === 'delivery') $back = G5_CART_ADMIN_URL.'/delivery_list.php';
if ($post('ret') === 'return') $back = G5_CART_ADMIN_URL.'/return_list.php';

$order = cart_order_get($od_id);
if (!$order || $order['od_status'] === 'draft') {
    alert('주문을 찾을 수 없습니다.', G5_CART_ADMIN_URL.'/order_list.php');
}

if ($mode === 'invoice') {
    cart_order_set_invoice($od_id, $post('od_delivery_company'), $post('od_invoice'));
    goto_url($back);
}

if ($mode === 'transition') {
    // 취소는 여기로 못 온다 — 사유·관리자 비밀번호를 받는 cancel 모드만 취소할 수 있다
    if ($post('action') === 'cancel') alert('취소는 사유와 비밀번호 확인이 필요합니다.', $back);
    $err = cart_order_transition($od_id, $post('action'), $member['mb_id']);
    if ($err !== '') alert($err, $back);
    goto_url($back);
}

// 주문 취소 — [관리자 비밀번호 재확인 → PG 승인 자동환불 → 상태 전이(재고 복원) → 사유 기록].
// 환불이 실패하면 취소 자체를 중단한다: 돈이 안 돌아갔는데 주문만 취소되는 상태를 만들지 않는다.
if ($mode === 'cancel') {
    $reason = mb_substr($post('cancel_reason'), 0, 255, 'utf-8');
    if (trim($reason) === '') alert('취소 사유를 입력하세요.', $back);
    // 순정 로그인과 같은 검증(login_password_check) — 구형·신형 해시 모두 처리한다
    if (!login_password_check($member, $post('admin_pw'), $member['mb_password'])) {
        alert('관리자 비밀번호가 맞지 않습니다.', $back);
    }

    // 결제된 PG 주문이면 환불부터 — 승인 이력이 있는 경우에만 (무통장·미결제는 해당 없음)
    $paid_states = array('paid', 'preparing');
    if ($order['od_pay_method'] !== 'bank' && in_array($order['od_status'], $paid_states, true)) {
        $err = cart_pay_refund($order, $reason, $member['mb_id']);
        if ($err !== '') alert('전자결제 취소 실패 — 주문은 취소하지 않았습니다. '.$err, $back);
    }

    $err = cart_order_transition($od_id, 'cancel', $member['mb_id']);
    if ($err !== '') {
        // 환불은 나갔는데 전이가 실패한 드문 경우 — 이력이 남아 있으니 화면이 대사 근거가 된다
        alert($err.' (전자결제 취소 여부는 결제 이력을 확인하세요)', $back);
    }
    sql_query(" update `{$g5['ycart_order_table']}`
        set od_cancel_reason = '".sql_real_escape_string(strip_tags($reason))."',
            od_canceled_at = '".G5_TIME_YMDHIS."',
            od_canceled_by = '".sql_real_escape_string($member['mb_id'])."'
        where od_id = '$od_id' ", true);
    goto_url($back);
}

// 반품 승인 — [관리자 비밀번호 재확인 → 선점 → PG 부분환불 → 재고 복원 → 품목·주문 반영].
// 주문취소와 같은 규칙으로 비밀번호를 받는다: 돈이 나가는 자리다.
// 재고를 못 되돌린 품목이 있으면 경고로 알린다(환불은 이미 나갔으므로 중단하지 않는다).
if ($mode === 'return_approve' || $mode === 'return_reject') {
    $rt_id = (int)$post('rt_id');
    if (!login_password_check($member, $post('admin_pw'), $member['mb_password'])) {
        alert('관리자 비밀번호가 맞지 않습니다.', $back);
    }
    $memo = mb_substr($post('rt_memo'), 0, 255, 'utf-8');

    if ($mode === 'return_reject') {
        if (trim($memo) === '') alert('거절 사유를 입력하세요.', $back);
        $err = cart_return_reject($rt_id, $memo, $member['mb_id']);
        if ($err !== '') alert($err, $back);
        goto_url($back);
    }

    $refund = (int)str_replace(',', '', $post('rt_refund'));
    $res = cart_return_approve($rt_id, $refund, ($post('rt_restock') === '1'), $memo, $member['mb_id']);
    if ($res['error'] !== '') alert($res['error'], $back);
    if (count($res['warn'])) alert('반품을 처리했습니다. 다만 '.implode(' / ', $res['warn']), $back);
    goto_url($back);
}

// 배송관리의 발송 버튼 — 행의 송장을 저장한 뒤 단계를 전환한다(버튼 하나로 묶음 처리)
if ($mode === 'ship') {
    cart_order_set_invoice($od_id, $post('od_delivery_company'), $post('od_invoice'));
    $err = cart_order_transition($od_id, $post('action'), $member['mb_id']);
    if ($err !== '') alert($err, $back);
    goto_url($back);
}

alert('잘못된 요청입니다.', $back);
