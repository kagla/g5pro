<?php
$sub_menu = '600100';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$w = (isset($_POST['w']) && !is_array($_POST['w'])) ? $_POST['w'] : '';
$it_id = (isset($_POST['it_id']) && !is_array($_POST['it_id'])) ? (int)$_POST['it_id'] : 0;
$list = G5_ADMIN_URL.'/cart/item_list.php';

$post_str = function ($key) {
    return (isset($_POST[$key]) && !is_array($_POST[$key])) ? trim($_POST[$key]) : '';
};

$data = array(
    'it_code' => $post_str('it_code'),
    'ca_id' => (int)$post_str('ca_id'),
    'it_name' => $post_str('it_name'),
    'it_keyword' => $post_str('it_keyword'),
    'it_content' => (isset($_POST['it_content']) && !is_array($_POST['it_content'])) ? $_POST['it_content'] : '',
    'it_show' => !empty($_POST['it_show']) ? 1 : 0,
    'it_shipping_id' => 0,
);
if ($data['it_name'] === '') alert('상품 이름을 입력하세요.');
if (!$data['ca_id'] || !cart_category_get($data['ca_id'])) alert('분류를 선택하세요.');
if ($data['it_code'] !== '') {
    $dup = cart_item_get_by_code($data['it_code']);
    if ($dup && (int)$dup['it_id'] !== $it_id) alert('이미 쓰는 상품코드입니다: '.$data['it_code']);
}

if ($w === 'u') {
    if (!cart_item_get($it_id)) alert('없는 상품입니다.', $list);
    cart_item_save($data, $it_id);
} else {
    $it_id = cart_item_save($data);
}

// ---- SKU 반영 (배열 계약은 item_form.blade.php 와 맞춘다) ----
$sk_ids = isset($_POST['sk_id']) && is_array($_POST['sk_id']) ? $_POST['sk_id'] : array();
$sk_codes = isset($_POST['sk_code']) && is_array($_POST['sk_code']) ? $_POST['sk_code'] : array();
$sk_options = isset($_POST['sk_option']) && is_array($_POST['sk_option']) ? $_POST['sk_option'] : array();
$sk_prices = isset($_POST['sk_price']) && is_array($_POST['sk_price']) ? $_POST['sk_price'] : array();
$sk_qtys = isset($_POST['sk_qty']) && is_array($_POST['sk_qty']) ? $_POST['sk_qty'] : array();
$sk_barcodes = isset($_POST['sk_barcode']) && is_array($_POST['sk_barcode']) ? $_POST['sk_barcode'] : array();
$sk_uses = isset($_POST['sk_use']) && is_array($_POST['sk_use']) ? $_POST['sk_use'] : array();
$who = isset($member['mb_id']) ? $member['mb_id'] : 'admin';

foreach ($sk_ids as $i => $sid) {
    $sid = (int)$sid;
    $opt = isset($sk_options[$i]) ? trim($sk_options[$i]) : '{}';
    if ($opt !== '{}' && json_decode($opt, true) === null) alert('옵션 형식 오류(행 '.($i + 1).')');
    $row = array(
        'it_id' => $it_id,
        'sk_code' => isset($sk_codes[$i]) ? trim($sk_codes[$i]) : '',
        'sk_option' => $opt,
        'sk_price' => isset($sk_prices[$i]) ? (int)str_replace(',', '', $sk_prices[$i]) : 0,
        'sk_barcode' => isset($sk_barcodes[$i]) ? trim($sk_barcodes[$i]) : '',
        'sk_use' => !empty($sk_uses[$i]) ? 1 : 0,
    );
    if ($row['sk_code'] !== '') {
        $dup = cart_sku_get_by_code($row['sk_code']);
        if ($dup && (int)$dup['sk_id'] !== $sid) alert('이미 쓰는 SKU 코드입니다: '.$row['sk_code']);
    }
    $sid = cart_sku_save($row, $sid);
    if (isset($sk_qtys[$i]) && $sk_qtys[$i] !== '') {
        cart_stock_set($sid, (int)str_replace(',', '', $sk_qtys[$i]), 'manual', 'form', $who);
    }
}

// SKU 가 하나도 없으면 단일 SKU 를 자동 생성 — "모든 상품은 SKU 1개 이상" 규칙
if (!count(cart_item_skus($it_id))) {
    cart_sku_save(array('it_id' => $it_id, 'sk_code' => '', 'sk_option' => '{}',
        'sk_price' => 0, 'sk_barcode' => '', 'sk_use' => 1));
}

$sk_dels = isset($_POST['sk_del']) && is_array($_POST['sk_del']) ? $_POST['sk_del'] : array();
foreach ($sk_dels as $sid) {
    $row = cart_sku_get((int)$sid);
    if ($row && (int)$row['it_id'] === $it_id) cart_sku_delete((int)$sid);
}

// ---- 이미지 반영 ----
$im_dels = isset($_POST['im_del']) && is_array($_POST['im_del']) ? $_POST['im_del'] : array();
foreach ($im_dels as $imid) cart_item_image_delete((int)$imid);

if (isset($_FILES['im_files']) && is_array($_FILES['im_files']['name'])) {
    foreach ($_FILES['im_files']['name'] as $i => $name) {
        if ($_FILES['im_files']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
        $err = cart_item_image_add($it_id, array(
            'name' => $name,
            'tmp_name' => $_FILES['im_files']['tmp_name'][$i],
            'error' => $_FILES['im_files']['error'][$i],
        ), $i);
        if ($err) alert($err);
    }
}
$im_main = (isset($_POST['im_main']) && !is_array($_POST['im_main'])) ? (int)$_POST['im_main'] : 0;
if ($im_main) {
    sql_query(" update `{$g5['cart_item_image_table']}` set im_main = 0 where it_id = '$it_id' ", true);
    sql_query(" update `{$g5['cart_item_image_table']}` set im_main = 1
        where im_id = '$im_main' and it_id = '$it_id' ", true);
}

goto_url(G5_ADMIN_URL.'/cart/item_form.php?w=u&it_id='.$it_id);
