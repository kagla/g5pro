<?php
$sub_menu = '600075';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '반품관리';
include_once(G5_ADMIN_PATH.'/admin.head.php');

$q = (isset($_GET['q']) && !is_array($_GET['q'])) ? trim($_GET['q']) : '';
// 기본은 '접수' — 이 화면에 들어오는 이유는 처리할 일이 있어서다. 처리한 건은 골라서 본다.
$status = (isset($_GET['status']) && !is_array($_GET['status'])) ? trim($_GET['status']) : 'requested';
$page = (isset($_GET['page']) && !is_array($_GET['page'])) ? max(1, (int)$_GET['page']) : 1;
$rows_per = 30;

$statuses = array('requested' => '접수', 'approved' => '반품 완료', 'rejected' => '거절');

$where = array(' 1=1 ');
if ($status === 'requested') {
    // 처리 중(approving)은 승인 도중 멈춘 드문 상태 — 사람이 봐야 하므로 '접수' 와 함께 보인다
    $where[] = " r.rt_status in ('requested', 'approving') ";
} elseif (isset($statuses[$status])) {
    $where[] = " r.rt_status = '".sql_real_escape_string($status)."' ";
}
if ($q !== '') {
    $esc = sql_real_escape_string($q);
    $where[] = " (o.od_no like '%$esc%' or o.od_name like '%$esc%' or o.od_hp like '%$esc%' or r.mb_id like '%$esc%') ";
}
$where_sql = implode(' and ', $where);

$cnt = sql_fetch(" select count(*) as cnt from `{$g5['ycart_return_table']}` r
    join `{$g5['ycart_order_table']}` o on o.od_id = r.od_id where $where_sql ");
$total = (int)$cnt['cnt'];
$total_page = max(1, (int)ceil($total / $rows_per));
if ($page > $total_page) $page = $total_page;
$offset = ($page - 1) * $rows_per;

$returns = array();
$result = sql_query(" select r.*, o.od_no, o.od_name, o.od_hp, o.od_pay_method, o.od_status,
        o.od_total, o.od_refund
    from `{$g5['ycart_return_table']}` r
    join `{$g5['ycart_order_table']}` o on o.od_id = r.od_id
    where $where_sql order by r.rt_id desc limit $offset, $rows_per ");
while ($r = sql_fetch_array($result)) {
    $r['item_total'] = cart_return_item_total($r);
    $r['status_label'] = cart_return_status_label($r['rt_status']);
    // 환불 입력 상한 — 결제액에서 이미 환불한 누계를 뺀 값(주문마다 다르므로 행마다 싣는다)
    $r['refundable'] = max(0, (int)$r['od_total'] - (int)$r['od_refund']);
    $r['is_bank'] = ($r['od_pay_method'] === 'bank');
    $r['view_url'] = G5_CART_ADMIN_URL.'/order_view.php?od_id='.(int)$r['od_id'];
    $returns[] = $r;
}

// 반품 품목 → 상품 수정 화면 바로가기. 반품이 잦은 상품은 설명이나 옵션을 손봐야 하는
// 상품이고, 그 판단을 하려면 결국 상품을 열어 봐야 한다. 목록의 품목을 두 방으로 모아 온다
// (줄마다 묻지 않는다 — 한 화면에 30건이고 줄마다 품목이 여럿이다).
$rt_items = cart_return_items_for_admin($returns);
foreach ($returns as $i => $r) {
    $returns[$i]['items'] = $rt_items[(int)$r['rt_id']];
}

// 상태 탭 건수 — 처리할 일이 몇 건인지 숫자로 본다
$counts = array();
$result = sql_query(" select rt_status, count(*) cnt from `{$g5['ycart_return_table']}` group by rt_status ");
while ($r = sql_fetch_array($result)) $counts[$r['rt_status']] = (int)$r['cnt'];
$counts['requested'] = (isset($counts['requested']) ? $counts['requested'] : 0)
    + (isset($counts['approving']) ? $counts['approving'] : 0);

cadm_view('return_list', array(
    'returns' => $returns,
    'statuses' => $statuses,
    'counts' => $counts,
    'status' => $status,
    'q' => $q,
    'total' => $total,
    'page' => $page,
    'total_page' => $total_page,
    'token' => get_token(),
    'self_url' => G5_CART_ADMIN_URL.'/return_list.php',
    'update_url' => G5_CART_ADMIN_URL.'/order_update.php',
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
