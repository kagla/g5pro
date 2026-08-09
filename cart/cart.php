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
    // 고른 것만 주문할 수 있게 되면서 합계가 화면에서 바뀐다 — 무료배송 안내도 따라 바뀌어야
    // 하므로 기준액을 함께 보낸다(첫 그림은 서버가, 이후는 화면이 같은 규칙으로 다시 쓴다)
    'ship_free' => (int)$cc['cc_ship_free'],
    'ship_notice' => $ship_notice,
    'token' => get_token(),
    'action_url' => cart_url('cart_update.php'),
    'checkout_href' => cart_url('checkout.php'),
    'list_href' => cart_url(''),
));
