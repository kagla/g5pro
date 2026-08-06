<?php
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

// 비회원 주문 조회 — 주문번호 + 주문 비밀번호. 성공하면 세션에 허가를 남기고 상세로 보낸다.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_token();
    $od_no = (isset($_POST['od_no']) && !is_array($_POST['od_no'])) ? trim($_POST['od_no']) : '';
    $pw = (isset($_POST['od_pw']) && !is_array($_POST['od_pw'])) ? trim($_POST['od_pw']) : '';

    $order = ($od_no !== '' && $pw !== '') ? cart_order_get_by_no($od_no) : null;
    // 회원 주문은 이 경로로 열 수 없다(비밀번호 해시가 없다) — 로그인 안내
    if ($order && $order['mb_id'] !== '') {
        alert('회원 주문입니다. 로그인 후 주문 내역에서 확인하세요.', cart_url('guest.php'));
    }
    if (!$order || $order['od_guest_pw'] === '' || !validate_password($pw, $order['od_guest_pw'])) {
        alert('주문번호 또는 비밀번호가 맞지 않습니다.', cart_url('guest.php'));
    }

    $_SESSION['ss_cart_guest_od_no'] = $order['od_no'];
    goto_url(cart_url('order.php', array('od_no' => $order['od_no'])));
}

$g5['title'] = '비회원 주문 조회';
g5_view('cart.guest', array(
    'token' => get_token(),
    'action_url' => cart_url('guest.php'),
));
