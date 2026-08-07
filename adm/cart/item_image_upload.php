<?php
/**
 * 목록에서 상품 이미지 한 장을 바로 올린다 (AJAX 전용).
 *
 * 상품 수정 화면까지 들어가지 않고 목록에서 곧바로 사진을 갈아 끼우려는 용도다.
 * 목록에는 대표 한 장만 보이므로, 올린 사진을 대표로 세워야 그 자리에서
 * 바뀐 것이 눈에 보인다. 기존 사진은 지우지 않고 보조로 남는다 —
 * 여러 장 관리는 상품 수정 화면이 맡는다.
 *
 * 응답은 JSON 하나다. {ok:true, url, im_id} 또는 {ok:false, msg}.
 */

$sub_menu = '600100';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function cart_img_fail($msg)
{
    echo json_encode(array('ok' => false, 'msg' => $msg), JSON_UNESCAPED_UNICODE);
    exit;
}

$it_id = isset($_POST['it_id']) ? (int)$_POST['it_id'] : 0;
if ($it_id < 1) cart_img_fail('상품을 찾을 수 없습니다.');

$item = sql_fetch(" select it_id from `{$g5['ycart_item_table']}` where it_id = '$it_id' ");
if (!$item) cart_img_fail('상품을 찾을 수 없습니다.');

if (!isset($_FILES['im_file']) || $_FILES['im_file']['error'] === UPLOAD_ERR_NO_FILE) {
    cart_img_fail('올릴 파일이 없습니다.');
}

// 새 사진을 맨 앞에 세우기 위해 지금 쓰는 가장 작은 im_order 보다 하나 앞 번호를 준다.
// 대표 지정과 별개로 순서까지 맞춰 두어야 대표를 나중에 바꿔도 앞자리를 지킨다.
$min = sql_fetch(" select min(im_order) as mn from `{$g5['ycart_item_image_table']}` where it_id = '$it_id' ");
$order = ($min && $min['mn'] !== null) ? (int)$min['mn'] - 1 : 0;

$err = cart_item_image_add($it_id, $_FILES['im_file'], $order);
if ($err) cart_img_fail($err);

// 방금 넣은 행 — cart_item_image_add 가 id 를 돌려주지 않아 마지막 것을 다시 읽는다
$row = sql_fetch(" select im_id, im_file from `{$g5['ycart_item_image_table']}`
    where it_id = '$it_id' order by im_id desc limit 1 ");
if (!$row) cart_img_fail('저장은 됐지만 기록을 찾지 못했습니다. 새로고침해 주세요.');

// 대표를 새 사진으로 옮긴다
sql_query(" update `{$g5['ycart_item_image_table']}` set im_main = 0 where it_id = '$it_id' ", true);
sql_query(" update `{$g5['ycart_item_image_table']}` set im_main = 1
    where im_id = '".(int)$row['im_id']."' and it_id = '$it_id' ", true);

echo json_encode(array(
    'ok'    => true,
    'im_id' => (int)$row['im_id'],
    'url'   => G5_DATA_URL.'/cart/item/'.$row['im_file'],
), JSON_UNESCAPED_UNICODE);
