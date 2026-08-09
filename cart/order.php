<?php
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

$od_no = (isset($_GET['od_no']) && !is_array($_GET['od_no'])) ? trim($_GET['od_no']) : '';
$is_member = isset($member['mb_id']) && $member['mb_id'] !== '';

// ---- 상세 ----
if ($od_no !== '') {
    $order = cart_order_get_by_no($od_no);

    // 회원 본인, 방금 주문 세션, 비회원 조회 인증 세션 — 셋 중 하나 (처리 화면과 같은 판정)
    // 초안(draft)은 결제 전이라 아직 주문이 아니다 — 어디에도 보이지 않는다
    if (!$order || !cart_order_is_mine($order) || $order['od_status'] === 'draft') {
        alert('주문을 찾을 수 없습니다.', cart_url('guest.php'));
    }

    $cc = cart_config();
    // 내부메모는 관리자만 보는 값이다. 템플릿이 안 쓰는 것과 값이 도달하지 못하는 것은 다르다 —
    // 나중에 누가 이 배열을 통째로 뿌려도 기사 연락처가 따라가지 않게 여기서 뺀다.
    unset($order['od_delivery_admin_memo']);
    $g5['title'] = '주문 상세';
    g5_view('cart.order_view', array(
        'order' => $order,
        'items' => cart_order_items((int)$order['od_id']),
        'status_label' => cart_order_status_label($order['od_status'], $order['od_pay_method']),
        'bank' => trim($cc['cc_bank']),
        'pay_href' => '',
        'list_href' => $is_member ? cart_url('order.php') : cart_url(''),
        'is_member' => $is_member,
        // 배송 정보 — 발송한 뒤에만 보여 준다. 송장 조회 주소는 택배사를 알아본 경우에만 채워진다.
        'track_url' => cart_delivery_track_url($order['od_dc_id'], $order['od_dc_name'], $order['od_invoice']),
        // 구매확정 — 배송완료에서만. 반품이 걸린 주문은 처리가 끝날 때까지 감춘다:
        // 확정은 "다 잘 받았다" 는 매듭이라 반품이 진행 중일 때 누르면 말이 어긋난다.
        'can_confirm' => ($order['od_status'] === 'delivered' && !cart_return_blocks_confirm((int)$order['od_id'])),
        'action_url' => cart_url('order_update.php'),
        'token' => get_token(),
        // 반품 — 고를 수 있는 품목이 있을 때만 신청 칸을 연다. 못 여는 이유는 화면이 말해 준다.
        'return_items' => cart_return_available_items((int)$order['od_id']),
        'return_why_not' => cart_return_why_not($order),
        'returns' => cart_return_rows((int)$order['od_id']),
        'return_days' => cart_return_days(),
        'is_bank' => ($order['od_pay_method'] === 'bank'),
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

$cnt = sql_fetch(" select count(*) as cnt from `{$g5['ycart_order_table']}`
    where mb_id = '$mb_esc' and od_status <> 'draft' ");
$total = (int)$cnt['cnt'];
$total_page = max(1, (int)ceil($total / $rows_per));
if ($page > $total_page) $page = $total_page;
$offset = ($page - 1) * $rows_per;

$orders = array();
$result = sql_query(" select * from `{$g5['ycart_order_table']}`
    where mb_id = '$mb_esc' and od_status <> 'draft' order by od_id desc limit $offset, $rows_per ");
while ($r = sql_fetch_array($result)) {
    // min(oi_name): ONLY_FULL_GROUP_BY 모드에서도 안전한 대표 상품명
    $first = sql_fetch(" select min(oi_name) as oi_name, count(*) as cnt
        from `{$g5['ycart_order_item_table']}`
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
