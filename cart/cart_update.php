<?php
include_once('./_common.php');

check_token();

$post = function ($key) {
    return (isset($_POST[$key]) && !is_array($_POST[$key])) ? trim($_POST[$key]) : '';
};
$mode = $post('mode');
$back = cart_url('cart.php');

// 상세 화면의 "장바구니" 는 담고 나서 옮겨 갈지를 묻는다(confirm) — 그래서 서버가 이동시키면
// 안 되고 결과만 돌려줘야 한다. ajax=1 이 그 요청이다. 나머지 경로(바로구매·장바구니 화면의
// 수량 변경·삭제)는 지금까지처럼 alert·goto_url 로 그대로 흐른다.
$is_ajax = ($post('ajax') === '1');
$out = function ($data) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
};
// 알림 하나를 두 말투로 — 화면 이동이면 순정 alert, ajax 면 JSON. 부르는 쪽이 갈래를 안 봐도 되게.
$fail = function ($msg, $url) use ($is_ajax, $out) {
    if ($is_ajax) $out(array('ok' => false, 'msg' => $msg));
    alert($msg, $url);
};

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
            $fail($err, $item_url);
        }
        $added[] = $sk_id;
    }
    if (!count($added)) $fail('담을 옵션을 선택해 주세요.', cart_url(''));

    // dest=buy 는 "바로구매" — 방금 담은 줄들만 주문서로 넘긴다(buy 스코프).
    // 장바구니에 있던 다른 상품은 함께 결제되지 않는다. 같은 옵션이 이미 담겨 있었으면
    // 수량이 합쳐진 그 행을 그대로 쓴다(장바구니 행은 옵션당 하나만 유지).
    if ($post('dest') === 'buy') {
        $owner = cart_cart_owner();
        $ct_ids = array();
        $result = sql_query(" select ct_id from `{$g5['ycart_cart_table']}`
            where sk_id in (".implode(',', $added).") and ".cart_cart_where($owner));
        while ($r = sql_fetch_array($result)) $ct_ids[] = (int)$r['ct_id'];
        $buy_url = cart_url('checkout.php', $ct_ids ? array('buy' => implode(',', $ct_ids)) : array());
        if ($is_ajax) $out(array('ok' => true, 'href' => $buy_url));
        goto_url($buy_url);
    }
    // 담긴 종류 수 — "장바구니(3)" 처럼 지금 상태를 알려 주고 옮겨 갈지 묻는 데 쓴다
    if ($is_ajax) $out(array('ok' => true, 'href' => $back, 'count' => cart_cart_count()));
    goto_url($back);
}

// 수량 변경 — 장바구니 화면은 ajax=1 로 부른다(누르는 즉시 저장되고 화면만 고쳐 그린다).
// 값이 바뀌면 그 줄이 재고를 넘길 수 있으므로, 바뀐 뒤의 상태를 함께 돌려준다 —
// 화면이 경고와 선택 가능 여부를 다시 칠할 근거다.
if ($mode === 'set') {
    $ct_id = (int)$post('ct_id');
    $set = cart_cart_set_qty($ct_id, (int)$post('qty'));

    if ($is_ajax) {
        $row = null;
        foreach (cart_cart_items() as $r) {
            if ((int)$r['ct_id'] === $ct_id) { $row = $r; break; }
        }
        // 수량을 0 으로 만들면 줄이 사라진다(set_qty 가 삭제로 넘긴다)
        if (!$row) $out(array('ok' => true, 'removed' => true, 'ct_id' => $ct_id));
        $out(array(
            'ok' => true, 'ct_id' => $ct_id,
            // 재고까지만 담겼으면 화면이 그 자리에서 이유를 알린다(확인을 누를 필요 없는 알림)
            'clamped' => !empty($set['clamped']),
            'max' => (int)$row['sk_qty'],
            'qty' => (int)$row['ct_qty'],
            'line_total' => (int)$row['sk_price'] * (int)$row['ct_qty'],
            'avail' => (bool)$row['avail'],
            'over_stock' => (bool)$row['over_stock'],
            'sk_qty' => (int)$row['sk_qty'],
        ));
    }
    goto_url($back);
}

if ($mode === 'del') {
    cart_cart_remove((int)$post('ct_id'));
    goto_url($back);
}

// 선택 삭제 — 체크한 줄만 한 번에 뺀다. 남의 ct_id 를 섞어 보내도 cart_cart_remove 가
// 소유자 조건을 함께 걸어 아무 행에도 닿지 않는다(한 줄씩 지우는 것과 같은 방어).
if ($mode === 'seldel') {
    $ids = (isset($_POST['ct_id']) && is_array($_POST['ct_id'])) ? $_POST['ct_id'] : array();
    $n = 0;
    foreach (array_unique(array_map('intval', $ids)) as $ct_id) {
        if ($ct_id < 1) continue;
        cart_cart_remove($ct_id);
        $n++;
    }
    if (!$n) alert('삭제할 상품을 선택해 주세요.', $back);
    goto_url($back);
}

alert('잘못된 요청입니다.', $back);
