<?php
$sub_menu = '600250';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$w = (isset($_POST['w']) && !is_array($_POST['w'])) ? $_POST['w'] : '';
$ca_id = (isset($_POST['ca_id']) && !is_array($_POST['ca_id'])) ? (int)$_POST['ca_id'] : 0;
$q = (isset($_POST['q']) && !is_array($_POST['q'])) ? trim($_POST['q']) : '';
$back = G5_ADMIN_URL.'/cart/category_item.php?'.http_build_query(array('ca_id' => $ca_id, 'q' => $q));

if (!cart_category_get($ca_id)) alert('없는 분류입니다.', G5_ADMIN_URL.'/cart/category_item.php');

if ($w === 'add') {
    $it_ids = (isset($_POST['it_ids']) && is_array($_POST['it_ids'])) ? array_map('intval', $_POST['it_ids']) : array();
    if (!$it_ids) alert('추가할 상품을 선택하세요.', $back);
    foreach ($it_ids as $it_id) {
        if (!cart_item_get($it_id)) continue;
        cart_item_category_add($it_id, $ca_id);
    }
    goto_url($back);
}

if ($w === 'del') {
    $it_id = (isset($_POST['it_id']) && !is_array($_POST['it_id'])) ? (int)$_POST['it_id'] : 0;
    cart_item_category_remove($it_id, $ca_id);
    goto_url($back);
}

alert('잘못된 요청입니다.', $back);
