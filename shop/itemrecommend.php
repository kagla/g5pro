<?php
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면 (추천 팝업)

$it_id = isset($_REQUEST['it_id']) ? safe_replace_regex($_REQUEST['it_id'], 'it_id') : '';

if (G5_IS_MOBILE) {
    include_once(G5_MSHOP_PATH.'/itemrecommend.php');
    return;
}

if (!$is_member)
    alert_close('회원만 메일을 발송할 수 있습니다.');

// 스팸을 발송할 수 없도록 세션에 아무값이나 저장하여 hidden 으로 넘겨서 다음 페이지에서 비교함
$token = get_random_token_string(16);
set_session("ss_token", $token);

$sql = " select it_name from {$g5['g5_shop_item_table']} where it_id='$it_id' ";
$it = sql_fetch($sql);
if (!$it['it_name'])
    alert_close("등록된 상품이 아닙니다.");

$g5['title'] =  $it['it_name'].' - 추천하기';
include_once(G5_PATH.'/head.sub.php');

g5_map_shop_itemrecommend($it_id, $it['it_name'], $token); // g5pro — 순정 인라인 폼 대신 직통 매핑

include_once(G5_PATH.'/tail.sub.php');
