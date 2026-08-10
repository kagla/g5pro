<?php
$sub_menu = '600065';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '입금관리';
include_once(G5_ADMIN_PATH.'/admin.head.php');

// 입금을 기다리는 무통장 주문만 — 확인하면 이 화면을 떠난다(배송관리와 같은 규칙).
// 카드·간편결제의 unpaid 는 여기 오지 않는다. 그건 결제창을 닫은 흔적이라 관리자가 할 일이 없다.
$cc = cart_config();
$days = (int)$cc['cc_unpaid_days'];      // 0 이면 자동취소를 안 하므로 기한도 없다

// 기한이 임박했거나 지난 것만 따로 보는 탭 — 오늘 안에 손봐야 할 것을 먼저 찾는 화면이다
$tab = (isset($_GET['tab']) && !is_array($_GET['tab'])) ? trim($_GET['tab']) : '';
$tabs = array('' => '전체', 'due' => '기한 임박');
if (!isset($tabs[$tab])) $tab = '';

$where = " od_status = 'unpaid' and od_pay_method = 'bank' ";
// 기한 임박 = 남은 날이 0 이하(오늘 마감이거나 이미 지남). 자동취소가 꺼져 있으면 임박이 없다
if ($tab === 'due' && $days > 0) {
    $limit_dt = date('Y-m-d H:i:s', G5_SERVER_TIME - ($days - 1) * 86400);
    $where .= " and od_datetime < '".sql_real_escape_string($limit_dt)."' ";
} elseif ($tab === 'due') {
    $where .= " and 0 ";
}

$page = (isset($_GET['page']) && !is_array($_GET['page'])) ? max(1, (int)$_GET['page']) : 1;
$rows_per = 30;
$cnt = sql_fetch(" select count(*) as cnt, sum(od_total) as amt
    from `{$g5['ycart_order_table']}` where $where ");
$total = (int)$cnt['cnt'];
$total_amt = (int)$cnt['amt'];
$total_page = max(1, (int)ceil($total / $rows_per));
if ($page > $total_page) $page = $total_page;
$offset = ($page - 1) * $rows_per;

$orders = array();
$result = sql_query(" select * from `{$g5['ycart_order_table']}`
    where $where order by od_id desc limit $offset, $rows_per ");
while ($r = sql_fetch_array($result)) {
    $first = sql_fetch(" select min(oi_name) as oi_name, count(*) as cnt
        from `{$g5['ycart_order_item_table']}` where od_id = '".(int)$r['od_id']."' group by od_id ");
    $r['summary'] = $first
        ? ($first['oi_name'].((int)$first['cnt'] > 1 ? ' 외 '.((int)$first['cnt'] - 1).'건' : ''))
        : '';
    // 남은 기한 — 주문일로부터 cc_unpaid_days 일. 지나면 방문 편승 청소가 자동 취소한다.
    // null = 자동취소를 안 하는 설정이라 기한 자체가 없다.
    $r['left_days'] = null;
    if ($days > 0) {
        $deadline = strtotime($r['od_datetime']) + $days * 86400;
        $r['left_days'] = (int)floor(($deadline - G5_SERVER_TIME) / 86400);
    }
    $r['view_url'] = G5_CART_ADMIN_URL.'/order_view.php?od_id='.(int)$r['od_id'];
    $orders[] = $r;
}

$tab_counts = array();
$row = sql_fetch(" select count(*) cnt from `{$g5['ycart_order_table']}`
    where od_status = 'unpaid' and od_pay_method = 'bank' ");
$tab_counts[''] = (int)$row['cnt'];
if ($days > 0) {
    $limit_dt = date('Y-m-d H:i:s', G5_SERVER_TIME - ($days - 1) * 86400);
    $row = sql_fetch(" select count(*) cnt from `{$g5['ycart_order_table']}`
        where od_status = 'unpaid' and od_pay_method = 'bank'
          and od_datetime < '".sql_real_escape_string($limit_dt)."' ");
    $tab_counts['due'] = (int)$row['cnt'];
} else {
    $tab_counts['due'] = 0;
}

cadm_view('deposit_list', array(
    'orders' => $orders,
    'tabs' => $tabs,
    'tab' => $tab,
    'tab_counts' => $tab_counts,
    'total' => $total,
    'total_amt' => $total_amt,
    'days' => $days,
    'bank' => trim($cc['cc_bank']),
    'page' => $page,
    'total_page' => $total_page,
    'token' => get_token(),
    'self_url' => G5_CART_ADMIN_URL.'/deposit_list.php',
    'update_url' => G5_CART_ADMIN_URL.'/deposit_update.php',
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
