<?php
$sub_menu = '600080';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '정산관리';
include_once(G5_ADMIN_PATH.'/admin.head.php');

// 기간 — 기본 최근 30일. 정산은 결제 확정 시각(od_paid_at) 기준으로 계산한다.
$from = (isset($_GET['from']) && !is_array($_GET['from'])) ? trim($_GET['from']) : '';
$to = (isset($_GET['to']) && !is_array($_GET['to'])) ? trim($_GET['to']) : '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-d', G5_SERVER_TIME - 29 * 86400);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = date('Y-m-d', G5_SERVER_TIME);

$paid_in = cart_order_paid_where();   // 대시보드와 같은 판정 — order.lib 한 곳에서 정한다
$range = " od_paid_at >= '$from 00:00:00' and od_paid_at <= '$to 23:59:59' ";

// 일별 매출 — 건수·상품합계·배송비·총액
$daily = array();
$sum = array('cnt' => 0, 'item' => 0, 'ship' => 0, 'total' => 0);
// 환불(반품)은 결제일 기준으로 그 주문 줄에서 뺀다 — 순매출 = 총액 - 환불.
// 반품은 결제와 다른 날 일어나지만, 여기서 보려는 것은 "이날 판 것이 결국 얼마였나" 다.
// 쿠폰 할인도 열로 세운다 — 없으면 상품합계 + 배송비 가 총액과 안 맞아, 어디서 5천원이
// 빠졌는지 화면만 보고는 알 수 없다(대사할 때 가장 먼저 막히는 자리다).
$sum = array('cnt' => 0, 'item' => 0, 'ship' => 0, 'coupon' => 0, 'total' => 0, 'refund' => 0, 'net' => 0);
$result = sql_query(" select date(od_paid_at) d, count(*) cnt,
        sum(od_item_total) item_amt, sum(od_ship_fee) ship_amt, sum(od_coupon) coupon_amt,
        sum(od_total) total_amt, sum(od_refund) refund_amt
    from `{$g5['ycart_order_table']}`
    where $paid_in and $range group by date(od_paid_at) order by d desc ");
while ($r = sql_fetch_array($result)) {
    $r['net_amt'] = (int)$r['total_amt'] - (int)$r['refund_amt'];
    $daily[] = $r;
    $sum['cnt'] += (int)$r['cnt'];
    $sum['item'] += (int)$r['item_amt'];
    $sum['ship'] += (int)$r['ship_amt'];
    $sum['coupon'] += (int)$r['coupon_amt'];
    $sum['total'] += (int)$r['total_amt'];
    $sum['refund'] += (int)$r['refund_amt'];
    $sum['net'] += (int)$r['net_amt'];
}

// 수단별 합계 — 같은 기간·같은 기준
$by_method = array();
$result = sql_query(" select od_pay_method m, count(*) cnt, sum(od_total) amt
    from `{$g5['ycart_order_table']}`
    where $paid_in and $range group by od_pay_method ");
$method_names = array('bank' => '무통장', 'inicis' => '이니시스', 'toss' => '토스페이먼츠');
while ($r = sql_fetch_array($result)) {
    $r['label'] = isset($method_names[$r['m']]) ? $method_names[$r['m']] : $r['m'];
    $by_method[] = $r;
}

// 같은 기간의 취소 — 참고 수치(취소 시각 컬럼이 없어 주문 시각 기준)
$r = sql_fetch(" select count(*) cnt, coalesce(sum(od_total), 0) amt
    from `{$g5['ycart_order_table']}`
    where od_status = 'canceled' and od_datetime >= '$from 00:00:00' and od_datetime <= '$to 23:59:59' ");
$canceled = array('cnt' => (int)$r['cnt'], 'amt' => (int)$r['amt']);

// 망취소 이력 — sent 가 'sent' 가 아닌 행은 돈이 걸려 있을 수 있는 미확인 취소라 강조
$netcancels = array();
$result = sql_query(" select p.*, o.od_no from `{$g5['ycart_payment_table']}` p
    join `{$g5['ycart_order_table']}` o on o.od_id = p.od_id
    where p.pm_status = 'netcancel' order by p.pm_id desc limit 50 ");
while ($r = sql_fetch_array($result)) {
    $data = json_decode($r['pm_data'], true);
    $r['sent'] = (is_array($data) && isset($data['sent'])) ? $data['sent'] : '';
    $r['reason'] = (is_array($data) && isset($data['reason'])) ? $data['reason'] : '';
    $r['alarm'] = ($r['sent'] !== 'sent' && $r['sent'] !== '' && $r['sent'] !== 'skip');
    $r['view_url'] = G5_CART_ADMIN_URL.'/order_view.php?od_id='.(int)$r['od_id'];
    $netcancels[] = $r;
}

cadm_view('settle', array(
    'from' => $from,
    'to' => $to,
    'daily' => $daily,
    'sum' => $sum,
    'by_method' => $by_method,
    'canceled' => $canceled,
    'netcancels' => $netcancels,
    'self_url' => G5_CART_ADMIN_URL.'/settle.php',
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
