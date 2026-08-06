<?php
include_once('./_common.php');

check_token();

$post = function ($key) {
    return (isset($_POST[$key]) && !is_array($_POST[$key])) ? trim($_POST[$key]) : '';
};
$mode = $post('mode');
$back = cart_url('basket.php');

if ($mode === 'add') {
    $sk_id = (int)$post('sk_id');
    $qty = max(1, (int)$post('qty'));
    $err = cart_basket_add($sk_id, $qty);
    if ($err !== '') {
        $sku = cart_sku_get($sk_id);
        $item_url = $sku ? cart_url('item.php', array('it_id' => (int)$sku['it_id'])) : cart_url('');
        alert($err, $item_url);
    }
    // dest=buy 는 "바로구매" — 담은 뒤 곧장 주문서로 (장바구니 개념은 하나만 유지)
    goto_url($post('dest') === 'buy' ? cart_url('checkout.php') : $back);
}

if ($mode === 'set') {
    cart_basket_set_qty((int)$post('bk_id'), (int)$post('qty'));
    goto_url($back);
}

if ($mode === 'del') {
    cart_basket_remove((int)$post('bk_id'));
    goto_url($back);
}

alert('잘못된 요청입니다.', $back);
