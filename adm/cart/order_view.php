<?php
$sub_menu = '600060';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$od_id = (isset($_GET['od_id']) && !is_array($_GET['od_id'])) ? (int)$_GET['od_id'] : 0;
$order = cart_order_get($od_id);
if (!$order || $order['od_status'] === 'draft') {
    alert('주문을 찾을 수 없습니다.', G5_CART_ADMIN_URL.'/order_list.php');
}

$g5['title'] = '주문 상세 '.$order['od_no'];
include_once(G5_ADMIN_PATH.'/admin.head.php');

// 결제 이력 — 망취소가 필요했는데 못 나간 행(sent commfail/skip)은 이중 결제 위험 신호로 강조
$payments = array();
$result = sql_query(" select * from `{$g5['ycart_payment_table']}`
    where od_id = '$od_id' order by pm_id desc ");
while ($r = sql_fetch_array($result)) {
    $data = json_decode($r['pm_data'], true);
    $r['sent'] = (is_array($data) && isset($data['sent'])) ? $data['sent'] : '';
    $r['reason'] = (is_array($data) && isset($data['reason'])) ? $data['reason'] : '';
    $r['alarm'] = ($r['pm_status'] === 'netcancel' && $r['sent'] !== 'sent' && $r['sent'] !== '');
    $r['data_short'] = mb_substr($r['pm_data'], 0, 180, 'utf-8');
    $payments[] = $r;
}

// 주문 상품 → 상품 수정 화면 바로가기.
// 주문서는 스냅샷(oi_name·oi_price)이라 상품 행이 없어도 읽힌다 — 판매 이력이 있으면
// cart_item_delete 가 삭제를 막지만 옛 자료·수동 삭제는 있을 수 있다. 그래서 살아 있는
// 상품만 링크한다(없는 상품으로 보내면 수정 화면이 '없는 상품입니다' 로 튕긴다).
// 존재 확인은 행마다 묻지 않고 한 방에 — 주문 한 건에 상품이 여럿이다.
$items = cart_order_items($od_id);
$alive = array();
$it_ids = array_filter(array_map(function ($r) { return (int)$r['it_id']; }, $items));
if ($it_ids) {
    $res = sql_query(" select it_id from `{$g5['ycart_item_table']}`
        where it_id in (".implode(',', array_unique($it_ids)).") ");
    while ($r = sql_fetch_array($res)) $alive[(int)$r['it_id']] = true;
}
foreach ($items as $i => $r) {
    $iid = (int)$r['it_id'];
    $items[$i]['edit_url'] = isset($alive[$iid])
        ? G5_CART_ADMIN_URL.'/item_form.php?w=u&it_id='.$iid : '';
}

// 이 상태에서 가능한 처리 — 라이브러리 화이트리스트(cart_order_transition)와 같은 규칙만 노출
$actions = array();
$s = $order['od_status'];
if ($s === 'unpaid' && $order['od_pay_method'] === 'bank') $actions['deposit'] = '입금확인 (결제완료로)';
if ($s === 'paid') $actions['preparing'] = '배송준비로';
if ($s === 'paid' || $s === 'preparing') $actions['shipping'] = '배송중으로 (발송)';
if ($s === 'shipping') $actions['delivered'] = '배송완료로';
// 구매확정은 원래 고객이 누르는 것이지만, 안 누르고 넘어가는 주문이 대부분이라
// 관리자도 대신 찍을 수 있게 둔다(전화로 "잘 받았다" 는 확인을 받은 경우 등)
if ($s === 'delivered') $actions['confirm'] = '구매확정으로';

// 되돌리는 처리 — 앞으로 가는 버튼과 섞지 않는다. 잘못 눌렀을 때 화면에서 고칠 길이
// 있어야 오클릭이 사고가 아니라 번거로움으로 끝난다.
// 반품 신청이 있는 주문의 배송완료 되돌리기는 라이브러리가 막는다(여기서도 미리 감춘다).
$undo = array();
if ($s === 'paid' && $order['od_pay_method'] === 'bank') $undo['undeposit'] = '입금확인 되돌리기 (입금대기로)';
if ($s === 'shipping') $undo['unship'] = '발송 되돌리기 (결제완료로)';
if ($s === 'delivered' && !count(cart_return_rows($od_id))) $undo['undeliver'] = '배송완료 되돌리기 (배송중으로)';

// 결과가 무거운 처리에만 확인을 받는다. 발송은 하루에 수십 번 눌러야 하므로 확인을 두면
// 금방 무뎌지고, 무뎌진 확인은 없는 것과 같다. 문구는 "정말?" 이 아니라 결과를 말한다.
$confirm_msg = array(
    'delivered' => "배송완료로 바꾸면\n· 손님 화면에 구매확정 버튼이 열립니다\n"
        ."· 반품 신청 기한이 오늘부터 세기 시작합니다\n\n계속할까요?",
    'confirm' => "구매확정은 되돌릴 수 없습니다.\n확정한 뒤에는 손님이 반품을 신청할 수 없습니다.\n\n계속할까요?",
    'unship' => "발송을 되돌립니다.\n발송 시각 기록이 지워지고 결제완료 상태로 돌아갑니다.\n\n계속할까요?",
    'undeliver' => "배송완료를 되돌립니다.\n배송완료 시각이 지워지고 손님의 구매확정 버튼이 닫힙니다.\n\n계속할까요?",
    'undeposit' => "입금확인을 되돌립니다.\n입금 시각이 지워지고 입금대기로 돌아갑니다.\n"
        ."입금 기한이 지나면 자동 취소 대상이 되니 확인 후 다시 처리하세요.\n\n계속할까요?",
);

// 취소는 별도 흐름 — 모달에서 사유·관리자 비밀번호를 받고, PG 결제는 자동 환불까지 나간다
$can_cancel = in_array($s, array('unpaid', 'paid', 'preparing'), true);
$pg_paid = ($order['od_pay_method'] !== 'bank' && in_array($s, array('paid', 'preparing'), true));

// 반품 — 처리 대기 신청이 있으면 상세 위에 카드로 띄운다. 환불 기본값은 신청 품목 합계지만
// 최종 금액은 관리자가 정한다(왕복 배송비 공제 같은 실무 변수를 사람이 흡수한다).
// 반품 표의 품목도 상품 수정 화면으로 보낸다 — 어떤 상품이 돌아왔는지 이름만 보고 짐작하지
// 않게. 링크와 생존 판정은 위에서 만든 주문 상품 줄을 그대로 쓴다(질의를 늘리지 않는다):
// 반품 품목은 언제나 이 주문의 품목이라 $items 안에 반드시 있다.
// 차례도 $items 를 따라간다 — 주문서에 적힌 순서와 반품 표의 순서가 어긋나지 않는다.
$returns = cart_return_rows($od_id);
foreach ($returns as $i => $rt) {
    $returns[$i]['item_total'] = cart_return_item_total($rt);
    $returns[$i]['status_label'] = cart_return_status_label($rt['rt_status']);

    $in_rt = array_flip(array_filter(array_map('intval', explode(',', $rt['rt_oi_ids']))));
    $rows = array();
    foreach ($items as $r) {
        if (!isset($in_rt[(int)$r['oi_id']])) continue;
        $rows[] = array(
            'name' => $r['oi_name'],
            // 옵션·수량은 링크 밖에 둔다 — 누를 곳은 상품 이름이라고 화면이 말해야 한다
            'suffix' => ($r['oi_option'] !== '' ? ' ('.$r['oi_option'].')' : '').' × '.$r['oi_qty'],
            'edit_url' => $r['edit_url'],
        );
    }
    $returns[$i]['items'] = $rows;
}
$refundable = cart_return_refundable($order);

cadm_view('order_view', array(
    'order' => $order,
    'companies' => cart_delivery_company_list(),
    'default_dc' => cart_delivery_company_default(),
    // 나중에 사용을 끈 택배사로 잡아 둔 주문 — 그 하나를 목록에 끼워 넣어야 select 가 안 빈다
    'extra_dc' => ((int)$order['od_dc_id'] > 0) ? cart_delivery_company_get((int)$order['od_dc_id']) : null,
    'coupon' => cart_coupon_of_order($order),
    'items' => $items,
    'returns' => $returns,
    'refundable' => $refundable,
    'is_bank' => ($order['od_pay_method'] === 'bank'),
    'status_label' => cart_order_status_label($order['od_status'], $order['od_pay_method']),
    'payments' => $payments,
    'actions' => $actions,
    'undo' => $undo,
    'confirm_msg' => $confirm_msg,
    'logs' => cart_order_log_rows($od_id),
    'can_cancel' => $can_cancel,
    // 주문 정보 수정은 발송 전까지 — 송장이 나간 뒤에 주소를 고치면 화면과 실제 물건이 갈린다.
    // 처리 화면(order_update.php)과 같은 문턱이어야 한다(어긋나면 칸은 있는데 저장이 튕긴다).
    'can_edit' => in_array($s, array('unpaid', 'paid', 'preparing'), true),
    'pg_paid' => $pg_paid,
    'token' => get_token(),
    'update_url' => G5_CART_ADMIN_URL.'/order_update.php',
    'list_url' => G5_CART_ADMIN_URL.'/order_list.php',
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
