<?php
$sub_menu = '600070';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '배송관리';
include_once(G5_ADMIN_PATH.'/admin.head.php');

// 배송 흐름에 있는 주문만 — 결제완료(발송 대기) → 배송준비 → 배송중. 완료되면 이 화면을 떠난다.
$tab = (isset($_GET['tab']) && !is_array($_GET['tab'])) ? trim($_GET['tab']) : '';
$tabs = array('' => '전체', 'paid' => '발송 대기', 'preparing' => '배송준비', 'shipping' => '배송중');
if (!isset($tabs[$tab])) $tab = '';

$where = ($tab !== '') ? " od_status = '".sql_real_escape_string($tab)."' "
    : " od_status in ('paid', 'preparing', 'shipping') ";

$page = (isset($_GET['page']) && !is_array($_GET['page'])) ? max(1, (int)$_GET['page']) : 1;
$rows_per = 30;
$cnt = sql_fetch(" select count(*) as cnt from `{$g5['cart_order_table']}` where $where ");
$total = (int)$cnt['cnt'];
$total_page = max(1, (int)ceil($total / $rows_per));
if ($page > $total_page) $page = $total_page;
$offset = ($page - 1) * $rows_per;

$orders = array();
$result = sql_query(" select * from `{$g5['cart_order_table']}`
    where $where order by od_id asc limit $offset, $rows_per ");
while ($r = sql_fetch_array($result)) {
    $first = sql_fetch(" select min(oi_name) as oi_name, count(*) as cnt
        from `{$g5['cart_order_item_table']}` where od_id = '".(int)$r['od_id']."' group by od_id ");
    $r['summary'] = $first
        ? ($first['oi_name'].((int)$first['cnt'] > 1 ? ' 외 '.((int)$first['cnt'] - 1).'건' : ''))
        : '';
    $r['status_label'] = cart_order_status_label($r['od_status'], $r['od_pay_method']);
    // 이 상태에서 배송관리가 눌러줄 다음 단계 하나
    $r['next_action'] = ($r['od_status'] === 'shipping') ? 'delivered' : 'shipping';
    $r['next_label'] = ($r['od_status'] === 'shipping') ? '배송완료' : '발송(배송중)';
    $r['view_url'] = G5_ADMIN_URL.'/cart/order_view.php?od_id='.(int)$r['od_id'];
    $orders[] = $r;
}

$tab_counts = array();
$result = sql_query(" select od_status, count(*) cnt from `{$g5['cart_order_table']}`
    where od_status in ('paid', 'preparing', 'shipping') group by od_status ");
while ($r = sql_fetch_array($result)) $tab_counts[$r['od_status']] = (int)$r['cnt'];

cadm_view('delivery_list', array(
    'orders' => $orders,
    'tabs' => $tabs,
    'tab' => $tab,
    'tab_counts' => $tab_counts,
    'total' => $total,
    'page' => $page,
    'total_page' => $total_page,
    'token' => get_token(),
    'self_url' => G5_ADMIN_URL.'/cart/delivery_list.php',
    'update_url' => G5_ADMIN_URL.'/cart/order_update.php',
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
