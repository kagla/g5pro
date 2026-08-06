<?php
$sub_menu = '600100';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$it_id = (isset($_POST['it_id']) && !is_array($_POST['it_id'])) ? (int)$_POST['it_id'] : 0;
$item = cart_item_get($it_id);
if (!$item) alert('없는 상품입니다.');
$who = isset($member['mb_id']) ? $member['mb_id'] : 'admin';

// 노출 토글
if (isset($_POST['it_show']) && !is_array($_POST['it_show'])) {
    sql_query(" update `{$g5['ycart_item_table']}`
        set it_show = '".(!empty($_POST['it_show']) ? 1 : 0)."'
        where it_id = '$it_id' ", true);
}

// 단일 SKU 상품의 가격·재고 인라인 편집
$sk_id = (isset($_POST['sk_id']) && !is_array($_POST['sk_id'])) ? (int)$_POST['sk_id'] : 0;
if ($sk_id) {
    $sku = cart_sku_get($sk_id);
    if (!$sku || (int)$sku['it_id'] !== $it_id) alert('상품과 SKU 가 맞지 않습니다.');
    if (isset($_POST['sk_price']) && !is_array($_POST['sk_price'])) {
        $sku['sk_price'] = (int)str_replace(',', '', $_POST['sk_price']);
        cart_sku_save($sku, $sk_id);
    }
    if (isset($_POST['sk_qty']) && $_POST['sk_qty'] !== '' && !is_array($_POST['sk_qty'])) {
        cart_stock_set($sk_id, (int)str_replace(',', '', $_POST['sk_qty']), 'manual', 'inline', $who);
    }
}

$qs = array();
foreach (array('q', 'ca_id', 'page') as $k) {
    if (isset($_POST['ret_'.$k]) && !is_array($_POST['ret_'.$k]) && $_POST['ret_'.$k] !== '') {
        $qs[$k] = $_POST['ret_'.$k];
    }
}
goto_url(G5_CART_ADMIN_URL.'/item_list.php'.($qs ? '?'.http_build_query($qs) : ''));
