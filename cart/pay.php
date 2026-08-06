<?php
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

$od_no = (isset($_GET['od_no']) && !is_array($_GET['od_no'])) ? trim($_GET['od_no']) : '';
$order = cart_order_get_by_no($od_no);

// 접근 통제 — 방금 주문한 세션이거나 회원 본인 주문만 (complete.php 와 같은 기준)
$is_mine = false;
if ($order) {
    if (!empty($_SESSION['ss_cart_last_od_no']) && $_SESSION['ss_cart_last_od_no'] === $order['od_no']) {
        $is_mine = true;
    } elseif (!empty($_SESSION['ss_cart_guest_od_no']) && $_SESSION['ss_cart_guest_od_no'] === $order['od_no']) {
        // 비회원이 guest.php 로 인증하고 미결제 주문을 이어서 결제하는 경로
        $is_mine = true;
    } elseif (isset($member['mb_id']) && $member['mb_id'] !== '' && $member['mb_id'] === $order['mb_id']) {
        $is_mine = true;
    }
}
if (!$order || !$is_mine) alert('주문을 찾을 수 없습니다.', cart_url(''));

// 이미 결제된 주문을 다시 열면(뒤로가기·새로고침) 완료로 보낸다
if ($order['od_status'] === 'paid') {
    goto_url(cart_url('complete.php', array('od_no' => $order['od_no'])));
}
if ($order['od_status'] !== 'unpaid') alert('결제할 수 있는 주문이 아닙니다.', cart_url(''));

$methods = cart_pay_methods();
$method = $order['od_pay_method'];
if (!isset($methods[$method]) || $method === 'bank') {
    alert('결제 수단 설정이 올바르지 않습니다.', cart_url(''));
}

// 토스 failUrl 로 되돌아온 경우 — 안내만 하고 다시 시도
if (isset($_GET['fail']) && !is_array($_GET['fail'])) {
    $fail_msg = (isset($_GET['message']) && !is_array($_GET['message']))
        ? clean_xss_tags($_GET['message']) : '';
    if ($fail_msg !== '') alert($fail_msg, cart_url('pay.php', array('od_no' => $order['od_no'])));
}

$pg = ($method === 'inicis') ? cart_inicis_ready($order) : cart_toss_ready($order);
if ($method === 'toss') {
    // JS 문자열 조립 대신 스크립트-안전 JSON 으로 통째로 내린다(HEX 플래그가 </script>·따옴표 봉인)
    $pg['params_json'] = json_encode($pg['params'],
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}

$g5['title'] = '결제';
g5_view('cart.pay', array(
    'order' => $order,
    'items' => cart_order_items((int)$order['od_id']),
    'method' => $method,
    'method_label' => $methods[$method],
    'pg' => $pg,
));
