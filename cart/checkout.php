<?php
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

$picked = cart_checkout_lines();
$lines = $picked['lines'];
if (!count($lines)) {
    alert('주문할 수 있는 상품이 없습니다.', cart_url('basket.php'));
}

$main_images = cart_item_main_images(array_column($lines, 'it_id'));
$item_total = 0;
foreach ($lines as $i => $l) {
    $it_id = (int)$l['it_id'];
    $lines[$i]['img'] = isset($main_images[$it_id]) ? cart_item_image_url($main_images[$it_id]) : '';
    $lines[$i]['line_total'] = (int)$l['sk_price'] * (int)$l['bk_qty'];
    $item_total += $lines[$i]['line_total'];
}

$cc = cart_config();
$is_member = isset($member['mb_id']) && $member['mb_id'] !== '';

$g5['title'] = '주문서 작성';
g5_view('cart.checkout', array(
    'lines' => $lines,
    'blocked_count' => count($picked['blocked']),
    'item_total' => $item_total,
    'expect_bk_ids' => implode(',', array_map('intval', array_column($lines, 'bk_id'))),
    'is_member' => $is_member,
    'default_name' => $is_member ? $member['mb_name'] : '',
    'default_hp' => $is_member ? $member['mb_hp'] : '',
    'default_email' => $is_member ? $member['mb_email'] : '',
    'ship' => array(
        'base' => (int)$cc['cc_ship_base'],
        'free' => (int)$cc['cc_ship_free'],
        'jeju' => (int)$cc['cc_ship_jeju'],
    ),
    'token' => get_token(),
    'action_url' => cart_url('checkout_update.php'),
    'basket_href' => cart_url('basket.php'),
));
