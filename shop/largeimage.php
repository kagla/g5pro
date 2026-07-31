<?php
include_once('./_common.php');
define('G5_BLADE_PAGE', true); // g5blade 직통 화면 (이미지 팝업)

$it_id = isset($_GET['it_id']) ? get_search_string(trim($_GET['it_id'])) : '';
$no = (isset($_GET['no']) && $_GET['no']) ? (int) $_GET['no'] : 1;

if (G5_IS_MOBILE) {
    include_once(G5_MSHOP_PATH.'/largeimage.php');
    return;
}

$row = get_shop_item($it_id, true);

if(! (isset($row['it_id']) && $row['it_id']))
    alert_close('상품정보가 존재하지 않습니다.');

$imagefile = G5_DATA_PATH.'/item/'.$row['it_img'.$no];
$imagefileurl = run_replace('get_item_image_url', G5_DATA_URL.'/item/'.$row['it_img'.$no], $row, $no);
$size = file_exists($imagefile) ? @getimagesize($imagefile) : array();

$g5['title'] = "{$row['it_name']} ($it_id)";
include_once(G5_PATH.'/head.sub.php');

$skin = G5_SHOP_SKIN_PATH.'/largeimage.skin.php';

// g5blade — 순정 스킨 출력을 잡아 팝업 문서 안에 담는다 (썸네일·창크기 스크립트가 스킨에 있다)
g5_blade_capture_start();
if(is_file($skin))
    include_once($skin);
else
    echo '<p>'.str_replace(G5_PATH.'/', '', $skin).'파일이 존재하지 않습니다.</p>';
g5_blade_capture_end('largeimage');
g5_map_shop_largeimage(g5_blade_captured('largeimage'));

include_once(G5_PATH.'/tail.sub.php');