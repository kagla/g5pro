<?php
include_once('./_common.php');

check_token();

$post = function ($key) {
    return (isset($_POST[$key]) && !is_array($_POST[$key])) ? trim($_POST[$key]) : '';
};

$is_member = isset($member['mb_id']) && $member['mb_id'] !== '';

$input = array(
    'mb_id' => $is_member ? $member['mb_id'] : '',
    'guest_pw' => $post('guest_pw'),
    'od_name' => $post('od_name'),
    'od_hp' => $post('od_hp'),
    'od_email' => $post('od_email'),
    'od_zip' => $post('od_zip'),
    'od_addr1' => $post('od_addr1'),
    'od_addr2' => $post('od_addr2'),
    'od_memo' => $post('od_memo'),
    'od_pay_method' => $post('pay'),
    'od_depositor' => $post('od_depositor'),
    'expect_bk_ids' => $post('expect_bk_ids'),
    'expect_item_total' => (int)str_replace(',', '', $post('expect_item_total')),
);

if ($input['od_name'] === '') alert('주문하시는 분 이름을 입력하세요.');
if ($input['od_hp'] === '') alert('연락처를 입력하세요.');
if ($input['od_zip'] === '' || $input['od_addr1'] === '') alert('배송지 주소를 입력하세요.');
if ($input['od_depositor'] === '') $input['od_depositor'] = $input['od_name'];

// 결제수단 화이트리스트 — 키가 등록된 PG 만 허용
$methods = cart_pay_methods();
if (!isset($methods[$input['od_pay_method']])) alert('결제 수단을 선택하세요.');

$r = cart_order_create($input);
if (!is_array($r)) alert($r, cart_url('checkout.php'));

// 완료·결제 화면 접근 허가 — 방금 주문한 브라우저(세션)만. 회원은 본인 주문이면 항상 허용.
$_SESSION['ss_cart_last_od_no'] = $r['od_no'];

// 무통장은 바로 완료(입금 안내), PG 는 결제창으로
if ($input['od_pay_method'] === 'bank') {
    goto_url(cart_url('complete.php', array('od_no' => $r['od_no'])));
}
goto_url(cart_url('pay.php', array('od_no' => $r['od_no'])));
