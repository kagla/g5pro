<?php
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

$od_no = (isset($_GET['od_no']) && !is_array($_GET['od_no'])) ? trim($_GET['od_no']) : '';
$is_member = isset($member['mb_id']) && $member['mb_id'] !== '';

// ---- 상세 ----
if ($od_no !== '') {
    $order = cart_order_get_by_no($od_no);

    // 회원 본인, 방금 주문 세션, 비회원 조회 인증 세션 — 셋 중 하나
    $is_mine = false;
    if ($order) {
        if ($is_member && $member['mb_id'] === $order['mb_id']) $is_mine = true;
        elseif (!empty($_SESSION['ss_cart_last_od_no']) && $_SESSION['ss_cart_last_od_no'] === $order['od_no']) $is_mine = true;
        elseif (!empty($_SESSION['ss_cart_guest_od_no']) && $_SESSION['ss_cart_guest_od_no'] === $order['od_no']) $is_mine = true;
    }
    if (!$order || !$is_mine) alert('주문을 찾을 수 없습니다.', cart_url('guest.php'));

    $cc = cart_config();
    $g5['title'] = '주문 상세';
    g5_view('cart.order_view', array(
        'order' => $order,
        'items' => cart_order_items((int)$order['od_id']),
        'status_label' => cart_order_status_label($order['od_status'], $order['od_pay_method']),
        'bank' => trim($cc['cc_bank']),
        'pay_href' => ($order['od_status'] === 'unpaid' && $order['od_pay_method'] !== 'bank')
            ? cart_url('pay.php', array('od_no' => $order['od_no'])) : '',
        'list_href' => $is_member ? cart_url('order.php') : cart_url(''),
        'is_member' => $is_member,
    ));
    return;
}

// ---- 목록(회원 전용) ----
if (!$is_member) {
    alert('비회원 주문은 주문번호로 조회할 수 있습니다.', cart_url('guest.php'));
}

$page = (isset($_GET['page']) && !is_array($_GET['page'])) ? max(1, (int)$_GET['page']) : 1;
$rows_per = 20;
$mb_esc = sql_real_escape_string($member['mb_id']);

$cnt = sql_fetch(" select count(*) as cnt from `{$g5['cart_order_table']}` where mb_id = '$mb_esc' ");
$total = (int)$cnt['cnt'];
$total_page = max(1, (int)ceil($total / $rows_per));
if ($page > $total_page) $page = $total_page;
$offset = ($page - 1) * $rows_per;

$orders = array();
$result = sql_query(" select * from `{$g5['cart_order_table']}`
    where mb_id = '$mb_esc' order by od_id desc limit $offset, $rows_per ");
while ($r = sql_fetch_array($result)) {
    // min(oi_name): ONLY_FULL_GROUP_BY 모드에서도 안전한 대표 상품명
    $first = sql_fetch(" select min(oi_name) as oi_name, count(*) as cnt
        from `{$g5['cart_order_item_table']}`
        where od_id = '".(int)$r['od_id']."' group by od_id ");
    $r['summary'] = $first
        ? ($first['oi_name'].((int)$first['cnt'] > 1 ? ' 외 '.((int)$first['cnt'] - 1).'건' : ''))
        : '';
    $r['status_label'] = cart_order_status_label($r['od_status'], $r['od_pay_method']);
    $r['href'] = cart_url('order.php', array('od_no' => $r['od_no']));
    $orders[] = $r;
}

$pages = array();
for ($p = max(1, $page - 4); $p <= min($total_page, $page + 4); $p++) {
    $pages[] = array('num' => $p, 'current' => ($p === $page),
        'href' => cart_url('order.php', array('page' => $p)));
}

$g5['title'] = '주문 내역';
g5_view('cart.order_list', array(
    'orders' => $orders,
    'total' => $total,
    'pages' => $pages,
    'total_page' => $total_page,
    'home_href' => cart_url(''),
));
