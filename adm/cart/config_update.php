<?php
$sub_menu = '600400';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$post_int = function ($key) {
    return (isset($_POST[$key]) && !is_array($_POST[$key]))
        ? (int)str_replace(',', '', $_POST[$key]) : 0;
};
$ship_base = max(0, $post_int('cc_ship_base'));
$ship_free = max(0, $post_int('cc_ship_free'));
// cc_ship_jeju 는 더 이상 화면에도 계산에도 없다 — 권역 표(ycart_ship_zone)가 그 일을 한다.
// 칼럼은 남겨 둔다(옛 값이 권역 시드의 근거였고, 지우면 되짚을 수 없다).
// tinyint 라 127 을 넘으면 잘린다 — 화면에서 막지 말고 저장 지점에서 가둔다
$unpaid_days = min(127, max(0, $post_int('cc_unpaid_days')));
$return_days = min(127, max(0, $post_int('cc_return_days')));
$post_key = function ($key) {
    // GPC addslashes 원복 후 저장 지점 이스케이프(이중 이스케이프 방지)
    return (isset($_POST[$key]) && !is_array($_POST[$key]))
        ? sql_real_escape_string(strip_tags(stripslashes(trim($_POST[$key])))) : '';
};
$bank = $post_key('cc_bank');
$inicis_mid = $post_key('cc_inicis_mid');
$inicis_signkey = $post_key('cc_inicis_signkey');
$inicis_apikey = $post_key('cc_inicis_apikey');
$toss_ckey = $post_key('cc_toss_ckey');
$toss_skey = $post_key('cc_toss_skey');

cart_config(); // 행이 없으면 만들어 둔다
sql_query(" update `{$g5['ycart_config_table']}`
    set cc_ship_base = '$ship_base', cc_ship_free = '$ship_free',
        cc_bank = '$bank',
        cc_unpaid_days = '$unpaid_days', cc_return_days = '$return_days',
        cc_inicis_mid = '$inicis_mid', cc_inicis_signkey = '$inicis_signkey',
        cc_inicis_apikey = '$inicis_apikey',
        cc_toss_ckey = '$toss_ckey', cc_toss_skey = '$toss_skey'
    where cc_id = 1 ", true);


goto_url(G5_CART_ADMIN_URL.'/config_form.php');
