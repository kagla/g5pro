<?php
$sub_menu = '600100';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');

$w = (isset($_GET['w']) && !is_array($_GET['w'])) ? $_GET['w'] : '';
$it_id = (isset($_GET['it_id']) && !is_array($_GET['it_id'])) ? (int)$_GET['it_id'] : 0;

$item = array('it_id' => 0, 'it_code' => '', 'ca_id' => 0, 'it_name' => '', 'it_keyword' => '',
    'it_content' => '', 'it_show' => 1);
$skus = array();
$images = array();
if ($w === 'u') {
    $item = cart_item_get($it_id);
    if (!$item) alert('없는 상품입니다.', G5_ADMIN_URL.'/cart/item_list.php');
    $skus = cart_item_skus($it_id);
    $images = cart_item_images($it_id);
}

$g5['title'] = $w === 'u' ? '상품 수정' : '상품 등록';
include_once(G5_ADMIN_PATH.'/admin.head.php');

// SKU 옵션 JSON 을 뷰에서 다루기 좋게 파싱해 넘긴다
foreach ($skus as $i => $s) {
    $opt = json_decode($s['sk_option'], true);
    $skus[$i]['opt_label'] = (is_array($opt) && count($opt)) ? implode(' / ', array_map(
        function ($k, $v) { return $k.'='.$v; }, array_keys($opt), $opt)) : '단일';
}

cadm_view('item_form', array(
    'w' => $w,
    'item' => $item,
    'skus' => $skus,
    'images' => $images,
    'categories' => cart_category_list(),
    'image_url_base' => G5_DATA_URL.'/cart/item/',
    'action_url' => G5_ADMIN_URL.'/cart/item_form_update.php',
    'list_url' => G5_ADMIN_URL.'/cart/item_list.php',
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
