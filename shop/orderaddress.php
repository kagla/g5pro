<?php
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면 (배송지 팝업)

if(!$is_member)
    alert_close('회원이시라면 회원로그인 후 이용해 주십시오.');

$ad_id = isset($_REQUEST['ad_id']) ? (int) $_REQUEST['ad_id'] : 0;

if($w == 'd') {
    $sql = " delete from {$g5['g5_shop_order_address_table']} where mb_id = '{$member['mb_id']}' and ad_id = '$ad_id' ";
    sql_query($sql);
    goto_url($_SERVER['SCRIPT_NAME']);
}

$sql_common = " from {$g5['g5_shop_order_address_table']} where mb_id = '{$member['mb_id']}' ";

$sql = " select count(ad_id) as cnt " . $sql_common;
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) { $page = 1; } // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$sql = " select *
            $sql_common
            order by ad_default desc, ad_id desc
            limit $from_record, $rows";

$result = sql_query($sql);

if(!sql_num_rows($result))
    alert_close('배송지 목록 자료가 없습니다.');

$order_action_url = G5_HTTPS_SHOP_URL.'/orderaddressupdate.php';

if (G5_IS_MOBILE) {
    include_once(G5_MSHOP_PATH.'/orderaddress.php');
    return;
}

// 테마에 orderaddress.php 있으면 include
if(defined('G5_THEME_SHOP_PATH')) {
    $theme_orderaddress_file = G5_THEME_SHOP_PATH.'/orderaddress.php';
    if(is_file($theme_orderaddress_file)) {
        include_once($theme_orderaddress_file);
        return;
        unset($theme_orderaddress_file);
    }
}

$g5['title'] = '배송지 목록';
include_once(G5_PATH.'/head.sub.php');

// g5pro — 순정 쿼리 결과를 배열로 모아 뷰에 넘긴다
$pro_rows = array();
while ($row = sql_fetch_array($result)) $pro_rows[] = $row;
g5_map_shop_orderaddress($pro_rows, $order_action_url, $total_count, $page, $total_page);

include_once(G5_PATH.'/tail.sub.php');
