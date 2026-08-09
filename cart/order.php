<?php
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

$od_no = (isset($_GET['od_no']) && !is_array($_GET['od_no'])) ? trim($_GET['od_no']) : '';
$is_member = isset($member['mb_id']) && $member['mb_id'] !== '';

// common.php 가 $_GET 전체에 addslashes 를 걸어 둔다(G5_ESCAPE_FUNCTION). 이 값들은 화면에
// 되비추고 링크에 다시 실을 것이라 여기서 한 번 벗긴다 — 안 벗기면 페이지를 넘길 때마다
// 백슬래시가 붙어 같은 검색이 다른 결과를 낸다(순정 common.lib.php 의 검색어 처리와 같은 방식).
$get = function ($key) {
    return (isset($_GET[$key]) && !is_array($_GET[$key])) ? trim(stripslashes($_GET[$key])) : '';
};
$q = $get('q');
$status = $get('status');
$period = $get('period');
$page = max(1, (int)$get('page'));

$periods = array('3m' => '최근 3개월', '6m' => '최근 6개월', '1y' => '최근 1년');
$period_days = array('3m' => 92, '6m' => 183, '1y' => 365);
$statuses = cart_order_statuses();
if (!isset($statuses[$status])) $status = '';
if (!isset($period_days[$period])) $period = '';

// 지금 보고 있던 목록의 좌표 — 상세로 갔다가 "목록" 을 눌렀을 때 같은 자리로 돌아온다.
// 검색해서 3페이지까지 넘긴 사람이 주문 하나를 열어 보고 나면 처음부터 다시 찾아야 했다.
$cond = array();
if ($q !== '') $cond['q'] = $q;
if ($status !== '') $cond['status'] = $status;
if ($period !== '') $cond['period'] = $period;
$cond_page = $cond;
if ($page > 1) $cond_page['page'] = $page;

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
        // 보던 목록으로 — 검색 조건과 페이지를 그대로 물고 돌아간다
        'list_href' => $is_member ? cart_url('order.php', $cond_page) : cart_url(''),
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

$rows_per = 20;
$mb_esc = sql_real_escape_string($member['mb_id']);

// 기간은 자유 입력이 아니라 고른 값이다 — 손님이 날짜 두 개를 찍는 것보다 "최근 6개월" 한 번이
// 빠르고, 값이 정해져 있으니 형식 검증도 필요 없다. 기본은 전체 — 기본값으로 기간을 걸면
// 오래전 주문 하나뿐인 사람에게 "주문이 없습니다" 가 뜬다.
// (조건 읽기·검증은 파일 맨 위에서 끝냈다 — 상세 화면도 같은 값을 써야 돌아갈 자리를 안다)
$where = array(" o.mb_id = '$mb_esc' ", " o.od_status <> 'draft' ");

if ($status !== '') {
    $where[] = " o.od_status = '".sql_real_escape_string($status)."' ";
}
if ($period !== '') {
    $from = date('Y-m-d 00:00:00', G5_SERVER_TIME - $period_days[$period] * 86400);
    $where[] = " o.od_datetime >= '$from' ";
}
// 검색어 한 칸으로 주문번호와 상품명을 함께 찾는다 — 손님이 기억하는 것은 그 둘뿐이고,
// "어디서 찾을지" 를 먼저 고르게 하면 고르는 일부터 실수한다.
// 상품명은 주문품목에 있으므로 exists 로 건다(조인하면 품목 수만큼 주문이 중복된다).
if ($q !== '') {
    // LIKE 의 % 와 _ 는 손님이 친 글자 그대로여야 한다. 안 막으면 '_' 한 글자로 전체 주문이
    // "검색 결과" 로 나오고, '50% 세일' 을 찾으면 % 가 아무 글자로 풀려 엉뚱한 주문이 걸린다.
    // 순서가 중요하다 — LIKE 용 이스케이프를 먼저 하고 그 결과를 SQL 문자열로 이스케이프해야
    // 손님이 친 백슬래시까지 글자로 지켜진다(반대로 하면 백슬래시가 LIKE 탈출 문자로 새어 든다).
    $esc = sql_real_escape_string(addcslashes($q, '\\%_'));
    $where[] = " (o.od_no like '%$esc%'
        or exists (select 1 from `{$g5['ycart_order_item_table']}` i
                   where i.od_id = o.od_id and i.oi_name like '%$esc%')) ";
}
$where_sql = implode(' and ', $where);

$cnt = sql_fetch(" select count(*) as cnt from `{$g5['ycart_order_table']}` o where $where_sql ");
$total = (int)$cnt['cnt'];
$total_page = max(1, (int)ceil($total / $rows_per));
if ($page > $total_page) $page = $total_page;
$offset = ($page - 1) * $rows_per;
// 페이지가 범위를 넘어 당겨졌으면 돌아올 좌표도 그 자리로 — 상세에서 "목록" 을 눌렀을 때
// 없는 페이지로 되돌아가지 않게 한다
$cond_page = $cond;
if ($page > 1) $cond_page['page'] = $page;

$orders = array();
$result = sql_query(" select o.* from `{$g5['ycart_order_table']}` o
    where $where_sql order by o.od_id desc limit $offset, $rows_per ");
while ($r = sql_fetch_array($result)) {
    // min(oi_name): ONLY_FULL_GROUP_BY 모드에서도 안전한 대표 상품명
    $first = sql_fetch(" select min(oi_name) as oi_name, count(*) as cnt
        from `{$g5['ycart_order_item_table']}`
        where od_id = '".(int)$r['od_id']."' group by od_id ");
    $r['summary'] = $first
        ? ($first['oi_name'].((int)$first['cnt'] > 1 ? ' 외 '.((int)$first['cnt'] - 1).'건' : ''))
        : '';
    $r['status_label'] = cart_order_status_label($r['od_status'], $r['od_pay_method']);
    // 상세로 갈 때 지금 목록의 좌표를 함께 들고 간다 — 돌아올 자리가 여기라야 한다
    $r['href'] = cart_url('order.php', array_merge($cond_page, array('od_no' => $r['od_no'])));
    $orders[] = $r;
}

// 페이징 링크는 지금 걸린 조건을 그대로 물고 간다 — 2페이지로 넘어가면서 조건이 풀리면
// 손님은 같은 목록을 보고 있다고 믿은 채 다른 목록을 본다($cond 는 파일 맨 위에서 만들었다)
$pages = array();
for ($p = max(1, $page - 4); $p <= min($total_page, $page + 4); $p++) {
    $pages[] = array('num' => $p, 'current' => ($p === $page),
        'href' => cart_url('order.php', array_merge($cond, array('page' => $p))));
}

$g5['title'] = '주문 내역';
g5_view('cart.order_list', array(
    'orders' => $orders,
    'total' => $total,
    'pages' => $pages,
    'total_page' => $total_page,
    'home_href' => cart_url(''),
    'q' => $q,
    'status' => $status,
    'period' => $period,
    'periods' => $periods,
    'statuses' => $statuses,
    // 조건이 하나라도 걸렸나 — 0건일 때 "주문이 없다" 와 "조건에 맞는 게 없다" 는 다른 말이다
    'searched' => ($q !== '' || $status !== '' || $period !== ''),
    'search_url' => cart_url('order.php'),
));
