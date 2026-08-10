<?php
/**
 * 고객이 자기 주문에 하는 처리 — 구매확정 · 반품신청 · 부대 정보 수정 · 주문 취소.
 *
 * 관리자 쪽 adm/cart/order_update.php 와 이름은 같지만 다른 화면이다: 여기는 손님 것이고
 * 검증도 다르다(관리자 권한 대신 "내 주문인가" 판정).
 *
 * 손님이 고칠 수 있는 것은 "어디로·누구 이름으로" 뿐이다. "무엇을 사는가"(품목·수량·금액)가
 * 바뀌면 취소하고 다시 주문한다 — 그쪽은 재고·쿠폰·결제를 되감았다 다시 만드는 일이라
 * 값 하나 고치는 것과 무게가 다르다.
 */

include_once('./_common.php');

check_token();

$post = function ($key) {
    return (isset($_POST[$key]) && !is_array($_POST[$key])) ? trim($_POST[$key]) : '';
};
$od_no = $post('od_no');
$mode = $post('mode');

$order = $od_no !== '' ? cart_order_get_by_no($od_no) : null;
// 초안은 결제 전이라 아직 주문이 아니다 — 상세 화면과 같은 문턱
if (!$order || $order['od_status'] === 'draft' || !cart_order_is_mine($order)) {
    alert('주문을 찾을 수 없습니다.', cart_url('guest.php'));
}
// 왔던 화면으로 돌려보낸다 — 주문완료에서 입금자명을 고친 손님을 주문 상세로 떨어뜨리면
// "접수됐습니다" 흐름이 끊기고, 방금 본 화면이 아닌 곳에 서게 된다.
// 관리자 쪽 order_update.php 가 목록 화면들에 쓰는 ret 규칙과 같다.
$back = ($post('ret') === 'complete')
    ? cart_url('complete.php', array('od_no' => $order['od_no']))
    : cart_url('order.php', array('od_no' => $order['od_no']));

// 구매확정 — 배송완료 주문의 매듭. 되돌리는 길은 두지 않았으므로 화면에서 한 번 더 묻는다.
// 상태 판정은 화이트리스트를 가진 전이 함수가 잠금 아래에서 다시 하므로 여기서 되풀이하지 않는다.
if ($mode === 'confirm') {
    if (cart_return_blocks_confirm((int)$order['od_id'])) {
        alert('반품 신청이 처리 중입니다. 처리가 끝난 뒤에 확정할 수 있습니다.', $back);
    }
    $err = cart_order_transition((int)$order['od_id'], 'confirm', 'customer');
    if ($err !== '') alert($err, $back);
    goto_url($back);
}

// 입금자명 — 무통장 입금 전에만. 이름을 아는 사람은 손님뿐이라(실제로 이체 버튼을 누르는 것이
// 손님이다) 손님이 직접 고치는 것이 맞다. 입금이 확인된 뒤에는 닫는다: 그때 바꾸면
// 통장에 찍힌 이름과 주문의 기록이 갈려 대사 근거가 사라진다.
if ($mode === 'depositor') {
    if ($order['od_pay_method'] !== 'bank' || $order['od_status'] !== 'unpaid') {
        alert('지금은 입금자명을 바꿀 수 없습니다.', $back);
    }
    $name = $post('od_depositor');
    if ($name === '') $name = $order['od_name'];   // 주문서와 같은 규칙 — 비우면 주문자 이름
    $err = cart_order_edit_fields((int)$order['od_id'], array('od_depositor' => $name), 'customer');
    if ($err !== '') alert($err, $back);
    alert('입금자명을 바꿨습니다.', $back);
}

// 배송지(받는분·연락처·주소)는 손님이 못 고친다. 이름·연락처만 열어도 "주소는 왜 안 되나" 가
// 따라오는데, 주소는 배송비가 우편번호에 걸려 있어(cart_shipping_fee) 금액이 달라지는 자리다.
// 한 건씩 사정을 듣고 차액을 판단할 수 있는 관리자 화면에서만 고친다.

// 주문 취소 — 배송 준비 전까지. 돈이 어디 있느냐로 갈리고, 그 판정은 라이브러리 한 곳에 있다.
//  · 미결제        돈이 안 움직였다 → 재고·쿠폰만 되돌리면 끝
//  · 결제완료 PG   승인된 돈이라 먼저 환불하고, 실패하면 취소도 하지 않는다(관리자 취소와 같은 규칙)
//  · 결제완료 무통장  자동으로 되돌릴 길이 없어 여기서 막는다 — 화면도 같은 문구로 안내한다
if ($mode === 'cancel') {
    $why_not = cart_order_customer_cancel_why_not($order);
    if ($why_not !== '') alert($why_not, $back);

    $reason = mb_substr($post('cancel_reason'), 0, 255, 'utf-8');
    if (trim($reason) === '') alert('취소 사유를 골라 주세요.', $back);

    // 돈이 먼저다 — 환불이 안 나갔는데 주문만 취소된 상태를 만들지 않는다
    if ($order['od_status'] === 'paid') {
        $err = cart_pay_refund($order, $reason, 'customer');
        if ($err !== '') {
            alert('결제 취소에 실패해 주문을 취소하지 못했습니다. 판매자에게 문의해 주세요.', $back);
        }
    }

    $err = cart_order_transition((int)$order['od_id'], 'cancel', 'customer', $reason);
    if ($err !== '') {
        // 환불은 나갔는데 전이가 실패한 드문 경우 — 결제 이력이 남아 있어 판매자가 대사할 수 있다
        alert($err.' 결제 취소 여부는 판매자에게 확인해 주세요.', $back);
    }
    sql_query(" update `{$g5['ycart_order_table']}`
        set od_cancel_reason = '".sql_real_escape_string(strip_tags($reason))."',
            od_canceled_at = '".G5_TIME_YMDHIS."', od_canceled_by = 'customer'
        where od_id = '".(int)$order['od_id']."' ", true);
    alert('주문이 취소되었습니다.', $back);
}

// 반품 신청 — 고른 품목·사유를 접수만 한다. 돈과 재고는 관리자가 승인할 때 움직인다.
// 무통장 주문은 돌려받을 계좌를 여기서 함께 받는다(카드는 원 결제수단으로 되돌아간다).
if ($mode === 'return') {
    $oi_ids = (isset($_POST['oi_id']) && is_array($_POST['oi_id'])) ? $_POST['oi_id'] : array();
    $bank = ($order['od_pay_method'] === 'bank') ? $post('return_bank') : '';
    if ($order['od_pay_method'] === 'bank' && $bank === '') {
        alert('환불받으실 계좌를 입력해 주세요. (은행 · 계좌번호 · 예금주)', $back);
    }
    $res = cart_return_create($order, $oi_ids, $post('return_reason'), $bank);
    if (!is_array($res)) alert($res, $back);
    alert('반품 신청이 접수되었습니다. 판매자 확인 후 처리됩니다.', $back);
}

alert('잘못된 요청입니다.', $back);
