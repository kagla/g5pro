<?php
$sub_menu = '600060';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$od_id = (isset($_GET['od_id']) && !is_array($_GET['od_id'])) ? (int)$_GET['od_id'] : 0;
$order = cart_order_get($od_id);
if (!$order || $order['od_status'] === 'draft') {
    alert('주문을 찾을 수 없습니다.', G5_ADMIN_URL.'/cart/order_list.php');
}

$g5['title'] = '주문 상세 '.$order['od_no'];
include_once(G5_ADMIN_PATH.'/admin.head.php');

// 결제 이력 — 망취소가 필요했는데 못 나간 행(sent commfail/skip)은 이중 결제 위험 신호로 강조
$payments = array();
$result = sql_query(" select * from `{$g5['cart_payment_table']}`
    where od_id = '$od_id' order by pm_id desc ");
while ($r = sql_fetch_array($result)) {
    $data = json_decode($r['pm_data'], true);
    $r['sent'] = (is_array($data) && isset($data['sent'])) ? $data['sent'] : '';
    $r['reason'] = (is_array($data) && isset($data['reason'])) ? $data['reason'] : '';
    $r['alarm'] = ($r['pm_status'] === 'netcancel' && $r['sent'] !== 'sent' && $r['sent'] !== '');
    $r['data_short'] = mb_substr($r['pm_data'], 0, 180, 'utf-8');
    $payments[] = $r;
}

// 이 상태에서 가능한 처리 — 라이브러리 화이트리스트(cart_order_transition)와 같은 규칙만 노출
$actions = array();
$s = $order['od_status'];
if ($s === 'unpaid' && $order['od_pay_method'] === 'bank') $actions['deposit'] = '입금확인 (결제완료로)';
if ($s === 'paid') $actions['preparing'] = '배송준비로';
if ($s === 'paid' || $s === 'preparing') $actions['shipping'] = '배송중으로 (발송)';
if ($s === 'shipping') $actions['delivered'] = '배송완료로';

// 취소는 별도 흐름 — 모달에서 사유·관리자 비밀번호를 받고, PG 결제는 자동 환불까지 나간다
$can_cancel = in_array($s, array('unpaid', 'paid', 'preparing'), true);
$pg_paid = ($order['od_pay_method'] !== 'bank' && in_array($s, array('paid', 'preparing'), true));

cadm_view('order_view', array(
    'order' => $order,
    'items' => cart_order_items($od_id),
    'status_label' => cart_order_status_label($order['od_status'], $order['od_pay_method']),
    'payments' => $payments,
    'actions' => $actions,
    'can_cancel' => $can_cancel,
    'pg_paid' => $pg_paid,
    'token' => get_token(),
    'update_url' => G5_ADMIN_URL.'/cart/order_update.php',
    'list_url' => G5_ADMIN_URL.'/cart/order_list.php',
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
