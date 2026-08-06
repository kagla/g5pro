<?php
include_once('./_common.php');

// PG 결제창 파라미터 엔드포인트(ajax 전용) — 주문서 화면이 초안(draft)으로 결제창을 열 때
// 시도마다 새 oid 를 발급받아 간다. 결제 전에는 주문이 저장되지 않으므로(초안뿐) 예전의
// "결제 페이지"는 없다: 비 ajax 접근은 상태에 맞는 화면으로 돌려보낸다.

$od_no = (isset($_GET['od_no']) && !is_array($_GET['od_no'])) ? trim($_GET['od_no']) : '';
$is_ajax = (isset($_GET['ajax']) && $_GET['ajax'] === '1');
$order = cart_order_get_by_no($od_no);

$ajax_out = function ($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
};

// 접근 통제 — 이 세션의 초안이거나, 방금 주문 세션이거나, 회원 본인 주문
$is_mine = false;
if ($order) {
    if (!empty($_SESSION['ss_cart_draft_od_id']) && (int)$_SESSION['ss_cart_draft_od_id'] === (int)$order['od_id']) {
        $is_mine = true;
    } elseif (!empty($_SESSION['ss_cart_last_od_no']) && $_SESSION['ss_cart_last_od_no'] === $order['od_no']) {
        $is_mine = true;
    } elseif (isset($member['mb_id']) && $member['mb_id'] !== '' && $member['mb_id'] === $order['mb_id']) {
        $is_mine = true;
    }
}
if (!$order || !$is_mine) {
    if ($is_ajax) $ajax_out(array('error' => '주문을 찾을 수 없습니다.'));
    alert('주문을 찾을 수 없습니다.', cart_url(''));
}

// 이미 결제된 주문(뒤로가기·새로고침) — 완료로
if ($order['od_status'] === 'paid') {
    if ($is_ajax) $ajax_out(array('redirect' => cart_url('complete.php', array('od_no' => $order['od_no']))));
    goto_url(cart_url('complete.php', array('od_no' => $order['od_no'])));
}

$methods = cart_pay_methods();
$method = $order['od_pay_method'];
if ($order['od_status'] !== 'draft' || !isset($methods[$method]) || $method === 'bank') {
    if ($is_ajax) $ajax_out(array('error' => '결제할 수 있는 주문이 아닙니다.'));
    alert('결제할 수 있는 주문이 아닙니다.', cart_url(''));
}

// 비 ajax 접근 — 결제는 주문서에서 이어진다(장바구니가 그대로 남아 있다)
if (!$is_ajax) {
    goto_url(cart_url('checkout.php'));
}

$pg = ($method === 'inicis') ? cart_inicis_ready($order) : cart_toss_ready($order);
$ajax_out(array('ok' => 1, 'method' => $method, 'pg' => $pg));
