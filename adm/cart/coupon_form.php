<?php
$sub_menu = '600077';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');

$cp_id = (isset($_GET['cp_id']) && !is_array($_GET['cp_id'])) ? (int)$_GET['cp_id'] : 0;
$cp = $cp_id ? cart_coupon_get($cp_id) : null;
if ($cp_id && !$cp) alert('없는 쿠폰입니다.', G5_CART_ADMIN_URL.'/coupon_list.php');

// 새 쿠폰의 기본값 — 오늘부터 한 달. 빈 칸으로 두면 날짜 형식을 매번 물어보게 된다
if (!$cp) {
    $cp = array(
        'cp_id' => 0, 'cp_name' => '', 'cp_code' => '', 'cp_issue' => 'code',
        'cp_type' => 'rate', 'cp_value' => 10, 'cp_max' => 0, 'cp_min' => 0,
        'cp_target' => '', 'cp_begin' => date('Y-m-d', G5_SERVER_TIME),
        'cp_end' => date('Y-m-d', G5_SERVER_TIME + 30 * 86400),
        'cp_days' => 0, 'cp_use' => 1, 'cp_memo' => '',
    );
}

$stats = $cp_id ? cart_coupon_stats($cp_id) : array('issued' => 0, 'used' => 0, 'amount' => 0);
// 발급 내역 — 많으면 최근 100장까지만. 그보다 많아지면 목록이 아니라 통계를 봐야 한다
$holders = $cp_id ? cart_coupon_holders($cp_id, 100) : array();

$g5['title'] = $cp_id ? '쿠폰 수정' : '쿠폰 등록';
include_once(G5_ADMIN_PATH.'/admin.head.php');

cadm_view('coupon_form', array(
    'cp' => $cp,
    'cp_id' => $cp_id,
    'stats' => $stats,
    'holders' => $holders,
    'order_url' => G5_CART_ADMIN_URL.'/order_view.php',
    'samples' => cart_coupon_samples(),
    'issues' => cart_coupon_issues(),
    'types' => cart_coupon_types(),
    'categories' => cart_category_list(),
    'token' => get_token(),
    'action_url' => G5_CART_ADMIN_URL.'/coupon_update.php',
    'list_url' => G5_CART_ADMIN_URL.'/coupon_list.php',
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
