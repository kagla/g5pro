<?php
$sub_menu = '600450';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '택배사관리';
include_once(G5_ADMIN_PATH.'/admin.head.php');

// 안 쓰는 택배사도 보여 준다 — 이 화면이 켜고 끄는 자리다
cadm_view('delivery_company', array(
    'rows' => cart_delivery_company_list(false),
    'new_count' => 3,      // 한 번에 여럿 추가하는 일이 흔하다
    'token' => get_token(),
    'action_url' => G5_CART_ADMIN_URL.'/delivery_company_update.php',
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
