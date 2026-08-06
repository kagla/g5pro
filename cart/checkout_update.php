<?php
include_once('./_common.php');

check_token();

// 주문서 페이지가 PG 결제를 같은 화면에서 열 때는 ajax=1 로 온다 — 이동 대신 JSON 으로 답한다
$is_ajax = (isset($_POST['ajax']) && $_POST['ajax'] === '1');
$ajax_out = function ($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
};
$fail = function ($msg) use ($is_ajax, $ajax_out) {
    if ($is_ajax) $ajax_out(array('error' => $msg));
    alert($msg);
};

// common.php 가 GPC 전체에 addslashes 를 걸어 둔다 — 그대로 저장하면 O'Neil 이 O\'Neil 로
// 남는다(이스케이프는 저장 지점의 sql_real_escape_string 이 담당). 원문으로 되돌려 읽는다.
$post = function ($key) {
    return (isset($_POST[$key]) && !is_array($_POST[$key])) ? stripslashes(trim($_POST[$key])) : '';
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
    'expect_ct_ids' => $post('expect_ct_ids'),
    'expect_item_total' => (int)str_replace(',', '', $post('expect_item_total')),
    // 바로구매 스코프 — 주문서가 보여준 행들만 주문한다(장바구니의 다른 상품 제외)
    'only_ct_ids' => array_values(array_filter(array_map('intval', explode(',', $post('buy'))))),
);

if ($input['od_name'] === '') $fail('주문하시는 분 이름을 입력하세요.');
if ($input['od_hp'] === '') $fail('연락처를 입력하세요.');
if ($input['od_email'] === '' || !preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $input['od_email'])) {
    $fail('이메일을 올바르게 입력하세요.');
}
if ($input['od_zip'] === '' || $input['od_addr1'] === '') $fail('배송지 주소를 입력하세요.');
if ($input['od_addr2'] === '') $fail('상세 주소를 입력하세요.');
if ($input['od_depositor'] === '') $input['od_depositor'] = $input['od_name'];

// 결제수단 화이트리스트 — 키가 등록된 PG 만 허용
$methods = cart_pay_methods();
if (!isset($methods[$input['od_pay_method']])) $fail('결제 수단을 선택하세요.');

// 무통장 — 주문을 저장하고 완료(입금 안내)로. 주문서는 여기서 확정된다.
if ($input['od_pay_method'] === 'bank') {
    $r = cart_order_create($input);
    if (!is_array($r)) {
        if ($is_ajax) $ajax_out(array('error' => $r));
        alert($r, cart_url('checkout.php'));
    }
    // 완료·조회 화면 접근 허가 — 방금 주문한 브라우저(세션)만. 회원은 본인 주문이면 항상 허용.
    $_SESSION['ss_cart_last_od_no'] = $r['od_no'];
    if ($is_ajax) $ajax_out(array('ok' => 1, 'redirect' => cart_url('complete.php', array('od_no' => $r['od_no']))));
    goto_url(cart_url('complete.php', array('od_no' => $r['od_no'])));
}

// PG — 결제 전에는 주문을 저장하지 않는다: 초안(draft)만 만들어 결제창 파라미터의 근거로 쓰고,
// 승인 확정이 재고 차감·바구니 정리와 함께 진짜 주문으로 바꾼다. 주문서 화면(ajax) 전용 경로다.
if (!$is_ajax) {
    alert('카드 결제는 주문서 화면에서 진행됩니다. 브라우저의 자바스크립트를 켜 주세요.', cart_url('checkout.php'));
}
$r = cart_order_create($input, null, true);
if (!is_array($r)) $ajax_out(array('error' => $r));
$ajax_out(array('ok' => 1, 'od_no' => $r['od_no']));
