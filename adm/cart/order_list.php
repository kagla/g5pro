<?php
$sub_menu = '600060';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '주문관리';
include_once(G5_ADMIN_PATH.'/admin.head.php');

$q = (isset($_GET['q']) && !is_array($_GET['q'])) ? trim($_GET['q']) : '';
$status = (isset($_GET['status']) && !is_array($_GET['status'])) ? trim($_GET['status']) : '';
$from = (isset($_GET['from']) && !is_array($_GET['from'])) ? trim($_GET['from']) : '';
$to = (isset($_GET['to']) && !is_array($_GET['to'])) ? trim($_GET['to']) : '';
$page = (isset($_GET['page']) && !is_array($_GET['page'])) ? max(1, (int)$_GET['page']) : 1;
$rows_per = 30;

// 관리자 화면이 다루는 상태 전부 — 목록은 order.lib 한 곳에서 정한다(상태가 늘 때 여기서 빠지지 않게)
$statuses = cart_order_statuses();

$where = array(" od_status <> 'draft' ");
if ($status !== '' && isset($statuses[$status])) {
    $where[] = " od_status = '".sql_real_escape_string($status)."' ";
}
// 반품 대기 — 주문 상태가 아니라 반품 신청 쪽 조건이라 상태 필터와 따로 둔다.
// (부분 반품 주문은 배송완료 상태 그대로라 상태로는 못 걸러진다)
$rt_only = (isset($_GET['rt']) && $_GET['rt'] === '1');
if ($rt_only) {
    $where[] = " od_id in (select od_id from `{$g5['ycart_return_table']}`
        where rt_status in ('requested', 'approving')) ";
}
if ($q !== '') {
    $esc = sql_real_escape_string($q);
    $where[] = " (od_no like '%$esc%' or od_name like '%$esc%' or od_hp like '%$esc%' or mb_id like '%$esc%') ";
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $where[] = " od_datetime >= '$from 00:00:00' ";
else $from = '';
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $where[] = " od_datetime <= '$to 23:59:59' ";
else $to = '';
$where_sql = implode(' and ', $where);

$cnt = sql_fetch(" select count(*) as cnt from `{$g5['ycart_order_table']}` where $where_sql ");
$total = (int)$cnt['cnt'];
$total_page = max(1, (int)ceil($total / $rows_per));
if ($page > $total_page) $page = $total_page;
$offset = ($page - 1) * $rows_per;

$orders = array();
$result = sql_query(" select * from `{$g5['ycart_order_table']}`
    where $where_sql order by od_id desc limit $offset, $rows_per ");
while ($r = sql_fetch_array($result)) {
    $first = sql_fetch(" select min(oi_name) as oi_name, count(*) as cnt
        from `{$g5['ycart_order_item_table']}` where od_id = '".(int)$r['od_id']."' group by od_id ");
    $r['summary'] = $first
        ? ($first['oi_name'].((int)$first['cnt'] > 1 ? ' 외 '.((int)$first['cnt'] - 1).'건' : ''))
        : '';
    $r['status_label'] = cart_order_status_label($r['od_status'], $r['od_pay_method']);
    $r['view_url'] = G5_CART_ADMIN_URL.'/order_view.php?od_id='.(int)$r['od_id'];
    $orders[] = $r;
}

// 상태 탭에 건수를 함께 — 운영자가 처리할 일을 숫자로 본다
$status_counts = array();
$result = sql_query(" select od_status, count(*) cnt from `{$g5['ycart_order_table']}`
    where od_status <> 'draft' group by od_status ");
while ($r = sql_fetch_array($result)) $status_counts[$r['od_status']] = (int)$r['cnt'];

cadm_view('order_list', array(
    'orders' => $orders,
    'statuses' => $statuses,
    'status_counts' => $status_counts,
    'status' => $status,
    'q' => $q,
    'from' => $from,
    'to' => $to,
    'total' => $total,
    'page' => $page,
    'total_page' => $total_page,
    'self_url' => G5_CART_ADMIN_URL.'/order_list.php',
    'rt_only' => $rt_only,
    'rt_pending' => cart_return_pending_count(),
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
