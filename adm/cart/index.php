<?php
$sub_menu = '600050';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '카트 대시보드';
include_once(G5_ADMIN_PATH.'/admin.head.php');

// 매출로 치는 상태 — 판정은 order.lib 한 곳에서만 정한다(정산 화면과 어긋나지 않게)
$paid_in = cart_order_paid_where();
$today0 = date('Y-m-d', G5_SERVER_TIME).' 00:00:00';

$r = sql_fetch(" select count(*) cnt, coalesce(sum(od_total), 0) amt
    from `{$g5['ycart_order_table']}` where $paid_in and od_paid_at >= '$today0' ");
$today_sales = (int)$r['amt'];
$today_paid_cnt = (int)$r['cnt'];

$r = sql_fetch(" select count(*) cnt from `{$g5['ycart_order_table']}`
    where od_status <> 'draft' and od_datetime >= '$today0' ");
$today_orders = (int)$r['cnt'];

// 최근 7일 일별 매출 — od_paid_at 기준(오늘 포함). 그래프 높이는 순정 sidx 방식(px)으로 계산한다.
$days = array();
$max_amt = 0;
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', G5_SERVER_TIME - $i * 86400);
    $r = sql_fetch(" select coalesce(sum(od_total), 0) amt, count(*) cnt
        from `{$g5['ycart_order_table']}`
        where $paid_in and od_paid_at >= '$d 00:00:00' and od_paid_at <= '$d 23:59:59' ");
    $amt = (int)$r['amt'];
    if ($amt > $max_amt) $max_amt = $amt;
    $days[] = array(
        'date' => $d,
        'label' => substr($d, 5, 5).' ('.get_yoil($d).')',
        'amt' => $amt,
        'cnt' => (int)$r['cnt'],
    );
}

// y 축 눈금·막대 px 높이 — 순정 shop_admin index 와 같은 규칙(최대값 천 단위 올림, offset 10)
$max_y = max(1000, (int)(ceil($max_amt / 1000) * 1000));
$y_val = array($max_y);
for ($i = 4; $i >= 1; $i--) $y_val[] = (int)($max_y * (($i * 2) / 10));
$max_height = 230;
foreach ($days as $k => $d) {
    $days[$k]['h'] = $d['amt'] > 0 ? (int)(($max_height * $d['amt']) / $max_y) + 10 : 0;
}

// 상태 분포 — 초안 제외, 건수와 금액을 함께
$status_sum = array(
    'unpaid' => array('cnt' => 0, 'amt' => 0),
    'paid' => array('cnt' => 0, 'amt' => 0),
    'shipping' => array('cnt' => 0, 'amt' => 0),
    'canceled' => array('cnt' => 0, 'amt' => 0),
);
$result = sql_query(" select od_status, count(*) cnt, coalesce(sum(od_total), 0) amt
    from `{$g5['ycart_order_table']}` where od_status <> 'draft' group by od_status ");
while ($row = sql_fetch_array($result)) {
    $s = $row['od_status'];
    if ($s === 'preparing' || $s === 'shipping' || $s === 'delivered') $s = 'shipping';
    if ($s === 'confirmed') $s = 'paid';
    if (!isset($status_sum[$s])) continue;
    $status_sum[$s]['cnt'] += (int)$row['cnt'];
    $status_sum[$s]['amt'] += (int)$row['amt'];
}

// 재고 임박 — 판매 중(sk_use, it_show)인데 5개 이하
$low_limit = 5;
$r = sql_fetch(" select count(*) cnt from `{$g5['ycart_sku_table']}` s
    join `{$g5['ycart_item_table']}` i on i.it_id = s.it_id
    where s.sk_use = 1 and i.it_show = 1 and s.sk_qty <= $low_limit ");
$low_total = (int)$r['cnt'];
$low_rows = array();
$result = sql_query(" select s.sk_id, s.sk_qty, s.sk_option, s.sk_code, i.it_id, i.it_name
    from `{$g5['ycart_sku_table']}` s
    join `{$g5['ycart_item_table']}` i on i.it_id = s.it_id
    where s.sk_use = 1 and i.it_show = 1 and s.sk_qty <= $low_limit
    order by s.sk_qty asc, s.sk_id asc limit 10 ");
while ($row = sql_fetch_array($result)) {
    $opt = json_decode($row['sk_option'], true);
    $row['opt_label'] = (is_array($opt) && count($opt)) ? implode(' / ', array_values($opt)) : '기본';
    $row['edit_url'] = G5_CART_ADMIN_URL.'/item_form.php?w=u&it_id='.(int)$row['it_id'];
    $low_rows[] = $row;
}

// 최근 주문 — 초안 제외 10건
$recent = array();
$result = sql_query(" select * from `{$g5['ycart_order_table']}`
    where od_status <> 'draft' order by od_id desc limit 10 ");
while ($row = sql_fetch_array($result)) {
    $first = sql_fetch(" select min(oi_name) as oi_name, count(*) as cnt
        from `{$g5['ycart_order_item_table']}` where od_id = '".(int)$row['od_id']."' group by od_id ");
    $row['summary'] = $first
        ? ($first['oi_name'].((int)$first['cnt'] > 1 ? ' 외 '.((int)$first['cnt'] - 1).'건' : ''))
        : '';
    $row['status_label'] = cart_order_status_label($row['od_status'], $row['od_pay_method']);
    $recent[] = $row;
}

// 카탈로그·바구니 현황
$r = sql_fetch(" select count(*) cnt from `{$g5['ycart_item_table']}` where it_show = 1 ");
$item_cnt = (int)$r['cnt'];
$r = sql_fetch(" select count(*) cnt from `{$g5['ycart_cart_table']}` ");
$cart_cnt = (int)$r['cnt'];

cadm_view('dashboard', array(
    'today_sales' => $today_sales,
    'today_paid_cnt' => $today_paid_cnt,
    'today_orders' => $today_orders,
    'days' => $days,
    'y_val' => $y_val,
    'status_sum' => $status_sum,
    'low_total' => $low_total,
    'low_rows' => $low_rows,
    'low_limit' => $low_limit,
    'recent' => $recent,
    'item_cnt' => $item_cnt,
    'cart_cnt' => $cart_cnt,
    // 반품 신청은 손님이 기다리는 일이라 대시보드에서 먼저 눈에 띄어야 한다 —
    // 주문 상세를 하나씩 열어 봐야 알 수 있으면 며칠씩 방치된다
    'return_pending' => cart_return_pending_count(),
    'return_url' => G5_CART_ADMIN_URL.'/order_list.php?rt=1',
    'item_list_url' => G5_CART_ADMIN_URL.'/item_list.php',
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
