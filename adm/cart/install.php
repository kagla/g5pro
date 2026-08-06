<?php
$sub_menu = '600900';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '카트 설치/업그레이드';
include_once(G5_ADMIN_PATH.'/admin.head.php');

// _common.php 가 이미 자동 설치했지만, 버튼으로 재실행(컬럼 업그레이드 반영)할 수 있게 한다
$result = null;
if (isset($_GET['run']) && $_GET['run'] === '1') {
    $result = cart_install();
}

$tables = array();
foreach (cart_table_ddl() as $key => $ddl) {
    $tables[] = array(
        'name'   => $g5[$key],
        'exists' => (bool)sql_query(" DESC `{$g5[$key]}` ", false),
    );
}

cadm_view('install', array(
    'tables' => $tables,
    'result' => $result,
    'ft'     => cart_ft_available(),
    'run_url' => G5_ADMIN_URL.'/cart/install.php?run=1',
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
