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
$rows_per = cart_rows_per();
$cnt = sql_fetch(" select count(*) as cnt from `{$g5['ycart_order_table']}` where $where ");
$total = (int)$cnt['cnt'];
$total_page = max(1, (int)ceil($total / $rows_per));
if ($page > $total_page) $page = $total_page;
$offset = ($page - 1) * $rows_per;

// 최근 주문이 위로 — 방금 들어온 것을 먼저 보내게 된다. 오래된 것부터 처리하려면
// 오름차순이 맞지만, 밀린 주문이 쌓이면 오늘 것이 몇 페이지 뒤로 밀려 안 보인다.
$orders = array();
$result = sql_query(" select * from `{$g5['ycart_order_table']}`
    where $where order by od_id desc limit $offset, $rows_per ");
while ($r = sql_fetch_array($result)) {
    $first = sql_fetch(" select min(oi_name) as oi_name, count(*) as cnt
        from `{$g5['ycart_order_item_table']}` where od_id = '".(int)$r['od_id']."' group by od_id ");
    $r['summary'] = $first
        ? ($first['oi_name'].((int)$first['cnt'] > 1 ? ' 외 '.((int)$first['cnt'] - 1).'건' : ''))
        : '';
    $r['status_label'] = cart_order_status_label($r['od_status'], $r['od_pay_method']);
    // 이 상태에서 배송관리가 눌러줄 다음 단계 하나
    $r['next_action'] = ($r['od_status'] === 'shipping') ? 'delivered' : 'shipping';
    $r['next_label'] = ($r['od_status'] === 'shipping') ? '배송완료' : '발송(배송중)';
    // 배송완료만 한 번 묻는다 — 그 한 번이 손님에게 구매확정 문을 열고 반품 기한을 시작시킨다.
    // 발송은 하루에 수십 번 눌러야 하므로 확인을 두면 금방 무뎌지고, 무뎌진 확인은 없는 것과 같다.
    $r['next_confirm'] = ($r['od_status'] === 'shipping')
        ? "배송완료로 바꾸면\n· 손님 화면에 구매확정 버튼이 열립니다\n"
          ."· 반품 신청 기한이 오늘부터 세기 시작합니다\n\n".$r['od_no']." 주문을 배송완료로 바꿀까요?"
        : '';
    $r['view_url'] = G5_CART_ADMIN_URL.'/order_view.php?od_id='.(int)$r['od_id'];
    // 이 주문의 택배사가 목록에 없는데 행은 살아 있으면(나중에 사용을 끈 택배사) 그 하나를
    // 목록에 끼워 넣어야 select 가 빈 채로 뜨지 않는다. 옛 자유입력 주문(od_dc_id = 0)은
    // 끼울 행이 없으므로 select 를 비워 두고 화면이 이름만 따로 보여 준다.
    $r['extra_dc'] = ((int)$r['od_dc_id'] > 0) ? cart_delivery_company_get((int)$r['od_dc_id']) : null;
    $orders[] = $r;
}

$tab_counts = array();
$result = sql_query(" select od_status, count(*) cnt from `{$g5['ycart_order_table']}`
    where od_status in ('paid', 'preparing', 'shipping') group by od_status ");
while ($r = sql_fetch_array($result)) $tab_counts[$r['od_status']] = (int)$r['cnt'];

cadm_view('delivery_list', array(
    'orders' => $orders,
    'companies' => cart_delivery_company_list(),
    'default_dc' => cart_delivery_company_default(),
    'tabs' => $tabs,
    'tab' => $tab,
    'tab_counts' => $tab_counts,
    'total' => $total,
    'page' => $page,
    'total_page' => $total_page,
    'token' => get_token(),
    'self_url' => G5_CART_ADMIN_URL.'/delivery_list.php',
    'update_url' => G5_CART_ADMIN_URL.'/order_update.php',
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
