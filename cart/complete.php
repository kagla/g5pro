<?php
/**
 * 주문 완료 — 결제·주문 생성이 끝나는 네 곳(무통장 checkout_update, PG pay.php,
 * 이니시스·토스 리턴)이 모두 여기로 보낸다.
 *
 * 주문 상세(cart/order.php)와 주소를 나눠 둔 이유는 말투다: 여기는 "접수됐습니다, 지금
 * 이것만 하시면 됩니다" 를 말하고 쇼핑으로 돌려보낸다. 나중에 전환 스크립트·리뷰 유도 같은
 * "완료 순간에만 하는 일" 이 붙는 자리이기도 하다.
 *
 * 그리는 코드는 주문 상세와 한 벌이다(각 템플릿의 partials/order_* 세 장). 한때 두 화면이 같은 칸을
 * 따로 그렸는데, 한쪽만 손보는 일이 반복되면서 같은 주문이 화면마다 다른 말을 했다.
 */

include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

$od_no = (isset($_GET['od_no']) && !is_array($_GET['od_no'])) ? trim($_GET['od_no']) : '';
$order = cart_order_get_by_no($od_no);

// 접근 통제 — 주문 상세와 같은 판정을 쓴다(회원 본인·방금 주문 세션·비회원 조회 세션).
// 예전엔 이 세 갈래를 여기 손으로 적어 두었는데, 한쪽만 고치면 화면에 따라 보이고 안 보이는
// 주문이 생긴다. 초안(draft)은 결제 전이라 아직 주문이 아니다.
if (!$order || !cart_order_is_mine($order) || $order['od_status'] === 'draft') {
    alert('주문을 찾을 수 없습니다.', cart_url(''));
}

$cc = cart_config();
// 내부메모는 관리자만 보는 값이다. 템플릿이 안 쓰는 것과 값이 도달하지 못하는 것은 다르다 —
// 나중에 누가 이 배열을 통째로 뿌려도 기사 연락처가 따라가지 않게 여기서 뺀다(주문 상세와 같은 규칙).
unset($order['od_delivery_admin_memo']);

$g5['title'] = '주문 완료';
g5_view('cart.complete', array(
    'order' => $order,
    'items' => cart_order_items_for_view((int)$order['od_id']),
    'status_label' => cart_order_status_label($order['od_status'], $order['od_pay_method']),
    'bank' => trim($cc['cc_bank']),
    'home_href' => cart_url(''),
    // 입금자명은 여기서 고치는 것이 가장 자연스럽다 — 주문 직후가 "아, 회사 이름으로
    // 보낼게요" 가 나오는 순간이다. 문턱은 주문 상세와 같다(무통장 · 입금 전).
    'can_edit_depositor' => ($order['od_pay_method'] === 'bank' && $order['od_status'] === 'unpaid'),
    // 배송지는 손님이 못 고친다 — 어디에 물어야 하는지만 알린다(발송 전까지)
    'can_edit_receiver' => in_array($order['od_status'], array('unpaid', 'paid', 'preparing'), true),
    'action_url' => cart_url('order_update.php'),
    'token' => get_token(),
));
