<?php
$sub_menu = '600300';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$self = G5_CART_ADMIN_URL.'/csv.php';
$mode = (isset($_POST['mode']) && !is_array($_POST['mode'])) ? $_POST['mode'] : '';
$who = isset($member['mb_id']) ? $member['mb_id'] : 'admin';
$tmp_dir = G5_CART_DATA_PATH.'/tmp';
if (!is_dir($tmp_dir)) { @mkdir($tmp_dir, G5_DIR_PERMISSION, true); @chmod($tmp_dir, G5_DIR_PERMISSION); }

if ($mode === 'upload') {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        alert('파일을 올려 주세요.', $self);
    }
    $key = md5(uniqid(mt_rand(), true));
    if (!move_uploaded_file($_FILES['csv_file']['tmp_name'], $tmp_dir.'/'.$key.'.csv')) {
        alert('업로드 저장 실패', $self);
    }
    goto_url($self.'?pv='.$key);
}

if ($mode === 'apply') {
    $key = (isset($_POST['key']) && !is_array($_POST['key'])) ? preg_replace('/[^a-f0-9]/', '', $_POST['key']) : '';
    $file = $tmp_dir.'/'.$key.'.csv';
    if (!$key || !is_file($file)) alert('미리보기 파일이 없습니다. 다시 올려 주세요.', $self);
    $errors = array();
    $rows = cart_csv_parse($file, $errors);
    $result = cart_csv_apply($rows, $who);
    @unlink($file);
    alert('반영 완료 — 신규 상품 '.$result['new_items'].', 수정 상품 '.$result['upd_items']
        .', 신규 SKU '.$result['new_skus'].', 수정 SKU '.$result['upd_skus']
        .', 재고 변경 '.$result['stock_changes'].'건', $self);
}

alert('잘못된 요청입니다.', $self);
