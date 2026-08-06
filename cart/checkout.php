<?php
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

// 바로구매 스코프 — buy=ct_id(CSV)면 그 바구니 행만 주문서에 올린다(다른 상품은 함께 결제 안 됨)
$buy = (isset($_GET['buy']) && !is_array($_GET['buy'])) ? trim($_GET['buy']) : '';
$only = array_values(array_filter(array_map('intval', explode(',', $buy))));

// 토스 failUrl 복귀 — 사유만 알리고 주문서를 다시 그린다(장바구니는 그대로, 스코프 유지)
if (isset($_GET['fail']) && !is_array($_GET['fail'])) {
    $fail_msg = (isset($_GET['message']) && !is_array($_GET['message']))
        ? clean_xss_tags($_GET['message']) : '';
    alert($fail_msg !== '' ? $fail_msg : '결제가 완료되지 않았습니다. 다시 시도해 주세요.',
        cart_url('checkout.php', count($only) ? array('buy' => implode(',', $only)) : array()));
}

$picked = cart_checkout_lines(null, count($only) ? $only : null);
$lines = $picked['lines'];
if (!count($lines)) {
    alert('주문할 수 있는 상품이 없습니다.', cart_url('cart.php'));
}

$main_images = cart_item_main_images(array_column($lines, 'it_id'));
$item_total = 0;
foreach ($lines as $i => $l) {
    $it_id = (int)$l['it_id'];
    $lines[$i]['img'] = isset($main_images[$it_id]) ? cart_item_image_url($main_images[$it_id]) : '';
    $lines[$i]['line_total'] = (int)$l['sk_price'] * (int)$l['ct_qty'];
    $item_total += $lines[$i]['line_total'];
}

$cc = cart_config();
$is_member = isset($member['mb_id']) && $member['mb_id'] !== '';

$g5['title'] = '주문서 작성';
g5_view('cart.checkout', array(
    'lines' => $lines,
    'blocked_count' => count($picked['blocked']),
    'item_total' => $item_total,
    'expect_ct_ids' => implode(',', array_map('intval', array_column($lines, 'ct_id'))),
    'buy' => count($only) ? implode(',', $only) : '',
    'is_member' => $is_member,
    'default_name' => $is_member ? $member['mb_name'] : '',
    'default_hp' => $is_member ? $member['mb_hp'] : '',
    'default_email' => $is_member ? $member['mb_email'] : '',
    'addresses' => $is_member ? cart_address_list($member['mb_id']) : array(),
    'ship' => array(
        'base' => (int)$cc['cc_ship_base'],
        'free' => (int)$cc['cc_ship_free'],
        'jeju' => (int)$cc['cc_ship_jeju'],
    ),
    'pay_methods' => cart_pay_methods(),
    'token' => get_token(),
    'action_url' => cart_url('checkout_update.php'),
    'cart_href' => cart_url('cart.php'),
));
