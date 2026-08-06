<?php
define('G5_IS_ADMIN', true);
// 상대 include 는 실행 스크립트 기준이라 adm/_common.php 를 못 거친다 — shop_admin 과 같은 방식
include_once('../../common.php');
include_once(G5_ADMIN_PATH.'/admin.lib.php');
include_once(G5_PATH.'/cart/lib/cart.lib.php');
include_once(G5_PATH.'/cart/lib/item.lib.php');
include_once(G5_PATH.'/cart/lib/stock.lib.php');
include_once(G5_PATH.'/cart/lib/csv.lib.php');
include_once(G5_PATH.'/cart/lib/order.lib.php');
include_once(G5_PATH.'/cart/lib/basket.lib.php');
include_once(G5_PATH.'/cart/lib/pay.lib.php');

if (function_exists('g5_check_data_htaccess')) {
    g5_check_data_htaccess();
}

if (isset($token)) {
    $token = @htmlspecialchars(strip_tags($token), ENT_QUOTES);
}

run_event('admin_common');

if ($is_admin != 'super') {
    alert('최고관리자만 접근할 수 있습니다.');
}

// 플러그인 자체 설치 — 첫 진입 시 테이블이 없으면 자동 생성
if (!cart_installed()) {
    cart_install();
}

// adm/cart/views/ 를 루트로 하는 전용 BladeOne 렌더 — 화면은 순정 관리자 클래스
// (tbl_head01·sidx 등)를 그대로 써서 기존 관리자와 같은 모습을 유지한다.
function cadm_view($view, $data = array())
{
    static $blade = null;
    if ($blade === null) {
        $cache = G5_DATA_PATH.'/cache/pro/cadm';
        if (!is_dir($cache)) { @mkdir($cache, G5_DIR_PERMISSION, true); @chmod($cache, G5_DIR_PERMISSION); }
        $blade = new \eftec\bladeone\BladeOne(G5_ADMIN_PATH.'/cart/views', $cache, \eftec\bladeone\BladeOne::MODE_AUTO);
    }
    echo $blade->run($view, $data);
}
