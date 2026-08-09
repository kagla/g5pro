<?php
/**
 * 고객이 자기 주문에 하는 처리 — 지금은 구매확정 하나다.
 *
 * 관리자 쪽 adm/cart/order_update.php 와 이름은 같지만 다른 화면이다: 여기는 손님 것이고
 * 검증도 다르다(관리자 권한 대신 "내 주문인가" 판정).
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
$back = cart_url('order.php', array('od_no' => $order['od_no']));

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
