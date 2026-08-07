<?php
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

$rows = cart_cart_items();

// 대표 이미지 일괄
$main_images = cart_item_main_images(array_column($rows, 'it_id'));

$items = array();
$total = 0;
$buyable = 0;
$blocked = 0;   // 재고 부족·판매 중지 — 하나라도 있으면 주문 단계로 못 넘어간다
foreach ($rows as $r) {
    $it_id = (int)$r['it_id'];
    $r['img'] = isset($main_images[$it_id]) ? cart_item_image_url($main_images[$it_id]) : '';
    $r['href'] = cart_url('item.php', array('code' => $r['it_code']));
    $r['line_total'] = (int)$r['sk_price'] * (int)$r['ct_qty'];
    if ($r['avail'] && !$r['over_stock']) {
        $total += $r['line_total'];
        $buyable++;
    } else {
        $blocked++;
    }
    $items[] = $r;
}

$cc = cart_config();
$ship_notice = '';
if ((int)$cc['cc_ship_free'] > 0) {
    $remain = (int)$cc['cc_ship_free'] - $total;
    $ship_notice = $remain > 0
        ? number_format($remain).'원 더 담으면 무료배송'
        : '무료배송 조건 충족';
}

$g5['title'] = '장바구니';
g5_view('cart.cart', array(
    'items' => $items,
    'total' => $total,
    'buyable' => $buyable,
    'blocked' => $blocked,
    'ship_base' => (int)$cc['cc_ship_base'],
    'ship_notice' => $ship_notice,
    'token' => get_token(),
    'action_url' => cart_url('cart_update.php'),
    'checkout_href' => cart_url('checkout.php'),
    'list_href' => cart_url(''),
));
