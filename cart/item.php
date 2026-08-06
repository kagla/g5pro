<?php
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

$it_id = (isset($_GET['it_id']) && !is_array($_GET['it_id'])) ? (int)$_GET['it_id'] : 0;
$item = cart_item_get($it_id);
// 상품 자신이 안 숨었어도 소속 분류가 캐스케이드-숨김이면 상세도 막는다(목록과 같은 의미론)
if (!$item || !$item['it_show'] || in_array((int)$item['ca_id'], cart_hidden_category_ids(), true)) {
    alert('없는 상품입니다.', cart_url('list.php'));
}

$images = array();
foreach (cart_item_images($it_id) as $img) $images[] = cart_item_image_url($img['im_file']);

$skus = array();
foreach (cart_item_skus($it_id, true) as $s) {
    $opt = json_decode($s['sk_option'], true);
    $label = (is_array($opt) && count($opt)) ? implode(' / ', array_values($opt)) : '기본';
    $skus[] = array(
        'sk_id' => (int)$s['sk_id'],
        'opt_label' => $label,
        'sk_price' => (int)$s['sk_price'],
        'sk_qty' => (int)$s['sk_qty'],
        'soldout' => ((int)$s['sk_qty'] === 0),
    );
}

$category = cart_category_get((int)$item['ca_id']);

// 관리자 바로가기 — super 판정은 여기서 끝내고 뷰에는 URL 만 (부킹 room.php 관례)
$admin_edit_url = ($is_admin === 'super')
    ? G5_ADMIN_URL.'/cart/item_form.php?w=u&it_id='.$it_id : '';

$g5['title'] = $item['it_name'];
g5_view('cart.item', array(
    'item' => $item,
    'images' => $images,
    'skus' => $skus,
    'single' => (count($skus) <= 1),
    'category' => $category,
    'list_href' => $category ? cart_url('list.php', array('ca_id' => $category['ca_id'])) : cart_url('list.php'),
    'admin_edit_url' => $admin_edit_url,
));
