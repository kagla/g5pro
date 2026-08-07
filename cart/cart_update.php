<?php
include_once('./_common.php');

check_token();

$post = function ($key) {
    return (isset($_POST[$key]) && !is_array($_POST[$key])) ? trim($_POST[$key]) : '';
};
$mode = $post('mode');
$back = cart_url('cart.php');

if ($mode === 'add') {
    // 상세 화면이 고른 옵션을 여러 줄로 보낸다: sk_id[] 와 qty[] 가 같은 순서로 짝을 이룬다.
    // 옛 단일 전송(sk_id 하나)도 계속 받는다 — 옵션 없는 상품과 지난 링크를 위해.
    $sk_ids = (isset($_POST['sk_id']) && is_array($_POST['sk_id']))
        ? array_map('intval', $_POST['sk_id']) : array((int)$post('sk_id'));
    $qtys = (isset($_POST['qty']) && is_array($_POST['qty']))
        ? array_map('intval', $_POST['qty']) : array((int)$post('qty'));

    $added = array();
    foreach ($sk_ids as $i => $sk_id) {
        if ($sk_id < 1) continue;
        $qty = max(1, isset($qtys[$i]) ? (int)$qtys[$i] : 1);
        $err = cart_cart_add($sk_id, $qty);
        if ($err !== '') {
            // 한 줄이라도 막히면 그 자리에서 알린다 — 앞서 담긴 줄은 장바구니에 그대로 남는다
            $sku = cart_sku_get($sk_id);
            $err_item = $sku ? cart_item_get((int)$sku['it_id']) : null;
            $item_url = $err_item ? cart_url('item.php', array('code' => $err_item['it_code'])) : cart_url('');
            alert($err, $item_url);
        }
        $added[] = $sk_id;
    }
    if (!count($added)) alert('담을 옵션을 선택해 주세요.', cart_url(''));

    // dest=buy 는 "바로구매" — 방금 담은 줄들만 주문서로 넘긴다(buy 스코프).
    // 장바구니에 있던 다른 상품은 함께 결제되지 않는다. 같은 옵션이 이미 담겨 있었으면
    // 수량이 합쳐진 그 행을 그대로 쓴다(장바구니 행은 옵션당 하나만 유지).
    if ($post('dest') === 'buy') {
        $owner = cart_cart_owner();
        $ct_ids = array();
        $result = sql_query(" select ct_id from `{$g5['ycart_cart_table']}`
            where sk_id in (".implode(',', $added).") and ".cart_cart_where($owner));
        while ($r = sql_fetch_array($result)) $ct_ids[] = (int)$r['ct_id'];
        goto_url(cart_url('checkout.php', $ct_ids ? array('buy' => implode(',', $ct_ids)) : array()));
    }
    goto_url($back);
}

if ($mode === 'set') {
    cart_cart_set_qty((int)$post('ct_id'), (int)$post('qty'));
    goto_url($back);
}

if ($mode === 'del') {
    cart_cart_remove((int)$post('ct_id'));
    goto_url($back);
}

alert('잘못된 요청입니다.', $back);
