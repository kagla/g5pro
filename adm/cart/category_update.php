<?php
$sub_menu = '600200';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$post = function ($key) {
    return (isset($_POST[$key]) && !is_array($_POST[$key])) ? stripslashes(trim($_POST[$key])) : '';
};
$w = $post('w');
$ca_id = (int)$post('ca_id');
$back = G5_ADMIN_URL.'/cart/category.php';

// 드래그 이동 — 화면 JS 가 ajax 로 부른다. 결과는 JSON, 성공 시 화면이 새로고침한다.
if ($w === 'move') {
    header('Content-Type: application/json; charset=utf-8');
    $err = cart_category_move($ca_id, (int)$post('parent'), (int)$post('after'));
    echo json_encode($err === '' ? array('ok' => 1) : array('error' => $err), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($w === 'd') {
    $err = cart_category_delete($ca_id);
    if ($err) alert($err, $back);
    goto_url($back);
}

// 이미지 삭제만 따로(행의 삭제 버튼)
if ($w === 'imgdel') {
    cart_category_image_delete($ca_id);
    goto_url($back);
}

$data = array(
    'ca_parent' => (int)$post('ca_parent'),
    'ca_name' => $post('ca_name'),
    'ca_order' => (int)$post('ca_order'),
    'ca_show' => !empty($_POST['ca_show']) ? 1 : 0,
    'ca_desc' => $post('ca_desc'),
    'ca_sort' => $post('ca_sort'),
);
if ($data['ca_name'] === '') alert('분류 이름을 입력하세요.', $back);

if ($w === 'u') {
    $cur = cart_category_get($ca_id);
    if (!$cur) alert('없는 분류입니다.', $back);
    // 수정은 부모·순서를 건드리지 않는다(이동은 드래그가 담당)
    $data['ca_order'] = (int)$cur['ca_order'];
    cart_category_save($data, $ca_id);
} else {
    if ($data['ca_parent']) {
        $prow = cart_category_get($data['ca_parent']);
        if (!$prow) alert('없는 부모 분류입니다.', $back);
        if ((int)$prow['ca_depth'] >= CART_CA_MAX_DEPTH) alert(CART_CA_MAX_DEPTH.'단까지만 만들 수 있습니다.', $back);
    }
    $ca_id = cart_category_save($data);
    if (!$ca_id) alert('저장에 실패했습니다.', $back);
}

// 이미지 업로드 — 본 저장 성공 후 처리(실패해도 저장은 유지, 사유만 안내)
if (isset($_FILES['ca_img_file']) && $_FILES['ca_img_file']['error'] !== UPLOAD_ERR_NO_FILE) {
    $err = cart_category_image_save($ca_id, $_FILES['ca_img_file']);
    if ($err) alert('분류는 저장했지만 이미지 업로드에 실패했습니다: '.$err, $back);
}
goto_url($back);
