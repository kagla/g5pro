<?php
$sub_menu = '600060';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$od_id = (isset($_GET['od_id']) && !is_array($_GET['od_id'])) ? (int)$_GET['od_id'] : 0;
$order = cart_order_get($od_id);
if (!$order || $order['od_status'] === 'draft') {
    alert('주문을 찾을 수 없습니다.', G5_CART_ADMIN_URL.'/order_list.php');
}

$g5['title'] = '주문 상세 '.$order['od_no'];
include_once(G5_ADMIN_PATH.'/admin.head.php');

// 결제 이력 — 망취소가 필요했는데 못 나간 행(sent commfail/skip)은 이중 결제 위험 신호로 강조
$payments = array();
$result = sql_query(" select * from `{$g5['ycart_payment_table']}`
    where od_id = '$od_id' order by pm_id desc ");
while ($r = sql_fetch_array($result)) {
    $data = json_decode($r['pm_data'], true);
    $r['sent'] = (is_array($data) && isset($data['sent'])) ? $data['sent'] : '';
    $r['reason'] = (is_array($data) && isset($data['reason'])) ? $data['reason'] : '';
    $r['alarm'] = ($r['pm_status'] === 'netcancel' && $r['sent'] !== 'sent' && $r['sent'] !== '');
    $r['data_short'] = mb_substr($r['pm_data'], 0, 180, 'utf-8');
    $payments[] = $r;
}

// 주문 상품 → 상품 수정 화면 바로가기.
// 주문서는 스냅샷(oi_name·oi_price)이라 상품 행이 없어도 읽힌다 — 판매 이력이 있으면
// cart_item_delete 가 삭제를 막지만 옛 자료·수동 삭제는 있을 수 있다. 그래서 살아 있는
// 상품만 링크한다(없는 상품으로 보내면 수정 화면이 '없는 상품입니다' 로 튕긴다).
// 존재 확인은 행마다 묻지 않고 한 방에 — 주문 한 건에 상품이 여럿이다.
$items = cart_order_items($od_id);
$alive = array();
$it_ids = array_filter(array_map(function ($r) { return (int)$r['it_id']; }, $items));
if ($it_ids) {
    $res = sql_query(" select it_id from `{$g5['ycart_item_table']}`
        where it_id in (".implode(',', array_unique($it_ids)).") ");
    while ($r = sql_fetch_array($res)) $alive[(int)$r['it_id']] = true;
}
foreach ($items as $i => $r) {
    $iid = (int)$r['it_id'];
    $items[$i]['edit_url'] = isset($alive[$iid])
        ? G5_CART_ADMIN_URL.'/item_form.php?w=u&it_id='.$iid : '';
}

// 이 상태에서 가능한 처리 — 라이브러리 화이트리스트(cart_order_transition)와 같은 규칙만 노출
$actions = array();
$s = $order['od_status'];
if ($s === 'unpaid' && $order['od_pay_method'] === 'bank') $actions['deposit'] = '입금확인 (결제완료로)';
if ($s === 'paid') $actions['preparing'] = '배송준비로';
if ($s === 'paid' || $s === 'preparing') $actions['shipping'] = '배송중으로 (발송)';
if ($s === 'shipping') $actions['delivered'] = '배송완료로';
// 구매확정은 원래 고객이 누르는 것이지만, 안 누르고 넘어가는 주문이 대부분이라
// 관리자도 대신 찍을 수 있게 둔다(전화로 "잘 받았다" 는 확인을 받은 경우 등)
if ($s === 'delivered') $actions['confirm'] = '구매확정으로';

// 취소는 별도 흐름 — 모달에서 사유·관리자 비밀번호를 받고, PG 결제는 자동 환불까지 나간다
$can_cancel = in_array($s, array('unpaid', 'paid', 'preparing'), true);
$pg_paid = ($order['od_pay_method'] !== 'bank' && in_array($s, array('paid', 'preparing'), true));

// 반품 — 처리 대기 신청이 있으면 상세 위에 카드로 띄운다. 환불 기본값은 신청 품목 합계지만
// 최종 금액은 관리자가 정한다(왕복 배송비 공제 같은 실무 변수를 사람이 흡수한다).
$returns = cart_return_rows($od_id);
foreach ($returns as $i => $rt) {
    $returns[$i]['item_total'] = cart_return_item_total($rt);
    $returns[$i]['status_label'] = cart_return_status_label($rt['rt_status']);
}
$refundable = cart_return_refundable($order);

cadm_view('order_view', array(
    'order' => $order,
    'items' => $items,
    'returns' => $returns,
    'refundable' => $refundable,
    'is_bank' => ($order['od_pay_method'] === 'bank'),
    'status_label' => cart_order_status_label($order['od_status'], $order['od_pay_method']),
    'payments' => $payments,
    'actions' => $actions,
    'can_cancel' => $can_cancel,
    'pg_paid' => $pg_paid,
    'token' => get_token(),
    'update_url' => G5_CART_ADMIN_URL.'/order_update.php',
    'list_url' => G5_CART_ADMIN_URL.'/order_list.php',
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
