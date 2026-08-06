<?php
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

$od_no = (isset($_GET['od_no']) && !is_array($_GET['od_no'])) ? trim($_GET['od_no']) : '';
$order = cart_order_get_by_no($od_no);

// 접근 통제 — 방금 주문한 세션이거나, 회원 본인 주문이거나. 그 외엔 조회 화면으로.
$is_mine = false;
if ($order) {
    if (!empty($_SESSION['ss_cart_last_od_no']) && $_SESSION['ss_cart_last_od_no'] === $order['od_no']) {
        $is_mine = true;
    } elseif (!empty($_SESSION['ss_cart_guest_od_no']) && $_SESSION['ss_cart_guest_od_no'] === $order['od_no']) {
        $is_mine = true;
    } elseif (isset($member['mb_id']) && $member['mb_id'] !== '' && $member['mb_id'] === $order['mb_id']) {
        $is_mine = true;
    }
}
if (!$order || !$is_mine || $order['od_status'] === 'draft') {
    alert('주문을 찾을 수 없습니다.', cart_url(''));
}

$items = cart_order_items((int)$order['od_id']);
$cc = cart_config();

$g5['title'] = '주문 완료';
g5_view('cart.complete', array(
    'order' => $order,
    'items' => $items,
    'status_label' => cart_order_status_label($order['od_status'], $order['od_pay_method']),
    'bank' => trim($cc['cc_bank']),
    'home_href' => cart_url(''),
));
