<?php
$sub_menu = '600077';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '쿠폰관리';
include_once(G5_ADMIN_PATH.'/admin.head.php');

$q = (isset($_GET['q']) && !is_array($_GET['q'])) ? trim($_GET['q']) : '';
$page = (isset($_GET['page']) && !is_array($_GET['page'])) ? max(1, (int)$_GET['page']) : 1;
$rows_per = 30;

$where = array(' 1=1 ');
if ($q !== '') {
    $esc = sql_real_escape_string($q);
    $where[] = " (cp_name like '%$esc%' or cp_code like '%$esc%' or cp_memo like '%$esc%') ";
}
$where_sql = implode(' and ', $where);

$cnt = sql_fetch(" select count(*) as cnt from `{$g5['ycart_coupon_table']}` where $where_sql ");
$total = (int)$cnt['cnt'];
$total_page = max(1, (int)ceil($total / $rows_per));
if ($page > $total_page) $page = $total_page;
$offset = ($page - 1) * $rows_per;

$today = date('Y-m-d', G5_SERVER_TIME);
$issues = cart_coupon_issues();

$coupons = array();
$result = sql_query(" select * from `{$g5['ycart_coupon_table']}` where $where_sql
    order by cp_id desc limit $offset, $rows_per ");
while ($r = sql_fetch_array($result)) {
    $st = cart_coupon_stats((int)$r['cp_id']);
    $r['issued'] = $st['issued'];
    $r['used'] = $st['used'];
    $r['used_amount'] = $st['amount'];
    $r['label'] = cart_coupon_label($r);
    $r['target_label'] = cart_coupon_target_label($r);
    $r['issue_label'] = isset($issues[$r['cp_issue']]) ? $issues[$r['cp_issue']] : $r['cp_issue'];
    // 왜 지금 안 나가는지 — '사용 안 함' 과 '기간 밖' 은 다른 일인데 목록에선 똑같이 조용하다
    $r['live'] = ((int)$r['cp_use'] === 1 && $r['cp_begin'] <= $today && $r['cp_end'] >= $today);
    $r['why_off'] = !(int)$r['cp_use'] ? '사용 안 함'
        : ($r['cp_begin'] > $today ? '시작 전' : ($r['cp_end'] < $today ? '기간 끝' : ''));
    $r['edit_url'] = G5_CART_ADMIN_URL.'/coupon_form.php?cp_id='.(int)$r['cp_id'];
    $coupons[] = $r;
}

cadm_view('coupon_list', array(
    'coupons' => $coupons,
    'q' => $q,
    'total' => $total,
    'page' => $page,
    'total_page' => $total_page,
    'token' => get_token(),
    'self_url' => G5_CART_ADMIN_URL.'/coupon_list.php',
    'form_url' => G5_CART_ADMIN_URL.'/coupon_form.php',
    'update_url' => G5_CART_ADMIN_URL.'/coupon_update.php',
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
