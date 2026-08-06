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
$ship_jeju = max(0, $post_int('cc_ship_jeju'));
$bank = (isset($_POST['cc_bank']) && !is_array($_POST['cc_bank']))
    ? sql_real_escape_string(strip_tags(trim($_POST['cc_bank']))) : '';

cart_config(); // 행이 없으면 만들어 둔다
sql_query(" update `{$g5['cart_config_table']}`
    set cc_ship_base = '$ship_base', cc_ship_free = '$ship_free',
        cc_ship_jeju = '$ship_jeju', cc_bank = '$bank'
    where cc_id = 1 ", true);

goto_url(G5_ADMIN_URL.'/cart/config.php');
