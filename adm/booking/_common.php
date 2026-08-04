<?php
define('G5_IS_ADMIN', true);
// 상대 include 는 실행 스크립트의 작업 디렉터리 기준이라 adm/_common.php 를 거칠 수 없다.
// (adm/_common.php 안의 '../common.php' 가 adm/booking 기준으로 풀려 깨진다 — shop_admin/sms_admin 과 같은 방식으로 직접 탄다)
include_once('../../common.php');
include_once(G5_ADMIN_PATH.'/admin.lib.php');
include_once(G5_LIB_PATH.'/booking.lib.php');

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
if (!booking_installed()) {
    booking_install();
}

// adm/booking/views/ 를 루트로 하는 전용 BladeOne 렌더
function badm_view($view, $data = array())
{
    static $blade = null;
    if ($blade === null) {
        $cache = G5_DATA_PATH.'/cache/pro/badm';
        if (!is_dir($cache)) { @mkdir($cache, G5_DIR_PERMISSION, true); @chmod($cache, G5_DIR_PERMISSION); }
        $blade = new \eftec\bladeone\BladeOne(G5_ADMIN_PATH.'/booking/views', $cache, \eftec\bladeone\BladeOne::MODE_AUTO);
    }
    echo $blade->run($view, $data);
}
