<?php
include_once('./_common.php');
define('G5_BLADE_PAGE', true); // g5blade 직통 화면 (쿠폰 팝업)

if (G5_IS_MOBILE) {
    include_once(G5_MSHOP_PATH.'/coupon.php');
    return;
}

// 테마에 coupon.php 있으면 include
if(defined('G5_THEME_SHOP_PATH')) {
    $theme_coupon_file = G5_THEME_SHOP_PATH.'/coupon.php';
    if(is_file($theme_coupon_file)) {
        include_once($theme_coupon_file);
        return;
        unset($theme_coupon_file);
    }
}

if ($is_guest)
    alert_close('회원만 조회하실 수 있습니다.');

$g5['title'] = $member['mb_nick'].' 님의 쿠폰 내역';
include_once(G5_PATH.'/head.sub.php');

$sql = " select cp_id, cp_subject, cp_method, cp_target, cp_start, cp_end, cp_type, cp_price
            from {$g5['g5_shop_coupon_table']}
            where mb_id IN ( '{$member['mb_id']}', '전체회원' )
              and cp_start <= '".G5_TIME_YMD."'
              and cp_end >= '".G5_TIME_YMD."'
            order by cp_no ";
$result = sql_query($sql);
// g5blade — 화면은 뷰가 그린다. 사용여부·할인대상 판정은 순정 로직 그대로.
$blade_rows = array();
while ($row = sql_fetch_array($result)) {
    if (is_used_coupon($member['mb_id'], $row['cp_id'])) continue;

    if ($row['cp_method'] == 1) {
        $ca = sql_fetch(" select ca_name from {$g5['g5_shop_category_table']} where ca_id = '{$row['cp_target']}' ");
        $row['cp_target_name'] = $ca['ca_name'].'의 상품할인';
    } else if ($row['cp_method'] == 2) {
        $row['cp_target_name'] = '결제금액 할인';
    } else if ($row['cp_method'] == 3) {
        $row['cp_target_name'] = '배송비 할인';
    } else {
        $it = get_shop_item($row['cp_target'], true);
        $row['cp_target_name'] = $it['it_name'].' 상품할인';
    }
    $blade_rows[] = $row;
}
g5_map_shop_coupon($blade_rows);

include_once(G5_PATH.'/tail.sub.php');
