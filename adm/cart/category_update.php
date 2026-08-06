<?php
$sub_menu = '600200';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$w = (isset($_POST['w']) && !is_array($_POST['w'])) ? $_POST['w'] : '';
$ca_id = (isset($_POST['ca_id']) && !is_array($_POST['ca_id'])) ? (int)$_POST['ca_id'] : 0;
$back = G5_ADMIN_URL.'/cart/category.php';

if ($w === 'd') {
    $err = cart_category_delete($ca_id);
    if ($err) alert($err, $back);
    goto_url($back);
}

$data = array(
    'ca_parent' => (isset($_POST['ca_parent']) && !is_array($_POST['ca_parent'])) ? (int)$_POST['ca_parent'] : 0,
    'ca_name'   => (isset($_POST['ca_name']) && !is_array($_POST['ca_name'])) ? trim($_POST['ca_name']) : '',
    'ca_order'  => (isset($_POST['ca_order']) && !is_array($_POST['ca_order'])) ? (int)$_POST['ca_order'] : 0,
    'ca_show'   => !empty($_POST['ca_show']) ? 1 : 0,
);
if ($data['ca_name'] === '') alert('분류 이름을 입력하세요.', $back);

if ($w === 'u') {
    if (!cart_category_get($ca_id)) alert('없는 분류입니다.', $back);
    cart_category_save($data, $ca_id);
} else {
    if ($data['ca_parent']) {
        $prow = cart_category_get($data['ca_parent']);
        if (!$prow) alert('없는 부모 분류입니다.', $back);
        if ((int)$prow['ca_depth'] >= 3) alert('3단까지만 만들 수 있습니다.', $back);
    }
    if (!cart_category_save($data)) alert('저장에 실패했습니다.', $back);
}
goto_url($back);
