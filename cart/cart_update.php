<?php
include_once('./_common.php');

check_token();

$post = function ($key) {
    return (isset($_POST[$key]) && !is_array($_POST[$key])) ? trim($_POST[$key]) : '';
};
$mode = $post('mode');
$back = cart_url('cart.php');

if ($mode === 'add') {
    $sk_id = (int)$post('sk_id');
    $qty = max(1, (int)$post('qty'));
    $err = cart_basket_add($sk_id, $qty);
    if ($err !== '') {
        $sku = cart_sku_get($sk_id);
        $item_url = $sku ? cart_url('item.php', array('it_id' => (int)$sku['it_id'])) : cart_url('');
        alert($err, $item_url);
    }
    // dest=buy 는 "바로구매" — 담은 뒤 그 행만 담긴 주문서로(buy=bk_id 스코프).
    // 장바구니의 다른 상품은 함께 결제되지 않는다. 같은 옵션이 이미 담겨 있었으면
    // 수량이 합쳐진 그 행 하나를 주문한다(장바구니 개념은 하나만 유지).
    if ($post('dest') === 'buy') {
        $owner = cart_basket_owner();
        $row = sql_fetch(" select bk_id from `{$g5['cart_basket_table']}`
            where sk_id = '$sk_id' and ".cart_basket_where($owner));
        goto_url(cart_url('checkout.php', $row ? array('buy' => (int)$row['bk_id']) : array()));
    }
    goto_url($back);
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
