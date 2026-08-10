<?php
if (!defined('_GNUBOARD_')) exit;

// ---------- 반품 ----------
// 고객이 품목을 골라 신청하고, 관리자가 승인하거나 거절한다.
//
// 설계에서 지킨 선 세 가지 —
//  1) **품목 단위, 수량 단위 아님.** 같은 상품 3개 중 1개만 반품은 받지 않는다. 수량을 쪼개면
//     주문품목 행을 나누거나 남은 수량을 따로 관리해야 하고, 그때부터 모든 화면이 "이 행의
//     남은 수량" 을 다시 계산해야 한다. 품목 통째로만 받으면 상태 한 칸만 바뀐다.
//  2) **환불 금액은 시스템이 확정하지 않는다.** 선택 품목 합계를 화면에 기본값으로 제안만 하고
//     최종 금액은 관리자가 정한다. 그래야 나중에 쿠폰·포인트가 들어와도 "5만원 주문에 5천원
//     쿠폰 쓰고 2만원어치 반품하면 얼마" 같은 안분 계산이 이 파일로 소급되지 않는다.
//  3) **배송비는 환불하지 않는다.** 이미 배송이 일어났다. 남은 금액이 무료배송 기준 아래로
//     떨어져도 소급 청구하지 않는다.
//
// 상태는 두 층이다. 주문 상태(od_status)는 배송 생애만 담고, 반품은 품목 상태(oi_status)에
// 단다 — 3개 중 1개를 반품하면 주문은 "배송완료(1건 반품)" 로 정직하게 읽힌다.
// 전 품목이 반품되면 그때만 od_status 를 'returned' 로 접는다.
//
//   oi_status:  normal ──(신청)──> return_req ──(승인)──> returned
//                          ^                  └──(거절)──> normal
//
// 구매확정(confirmed)한 주문은 반품 신청을 받지 않는다 — 확정이 "다 잘 받았다" 는 매듭이라
// 그 뒤의 반품은 말이 어긋나고, 4단계에서 확정에 포인트 적립을 걸면 회수 문제까지 따라온다.
// 그래서 확정 버튼은 반품이 걸린 주문에서 감춘다(cart_return_blocks_confirm).

function cart_return_days()
{
    $cc = cart_config();
    return (int)$cc['cc_return_days'];
}

// 자주 쓰는 반품 사유 — 화면이 고르게 해 주고, 고른 뒤에도 고칠 수 있다(주문취소 모달과 같은 방식).
// 손님이 빈 칸 앞에서 무슨 말을 적어야 할지 고민하지 않게 하는 것이 목적이라, 값을 코드로
// 저장하지 않고 문구 그대로 넣는다 — 나중에 항목을 바꿔도 옛 신청의 사유가 깨지지 않는다.
function cart_return_reasons()
{
    return array(
        '단순 변심 (마음에 들지 않음)',
        '사이즈·색상이 생각과 다름',
        '상품이 파손·불량임',
        '상품 설명과 다름',
        '다른 상품이 배송됨',
        '배송이 너무 늦음',
    );
}

function cart_return_get($rt_id)
{
    global $g5;
    $rt_id = (int)$rt_id;
    if ($rt_id < 1) return null;
    $row = sql_fetch(" select * from `{$g5['ycart_return_table']}` where rt_id = '$rt_id' ");
    return $row ? $row : null;
}

// 한 주문의 반품 신청 이력 — 최근 것부터. 화면이 "무엇을 왜 반품했나" 를 한 번에 그린다.
function cart_return_rows($od_id)
{
    global $g5;
    $rows = array();
    $result = sql_query(" select * from `{$g5['ycart_return_table']}`
        where od_id = '".(int)$od_id."' order by rt_id desc ");
    while ($r = sql_fetch_array($result)) {
        $r['item_names'] = cart_return_item_names($r);
        $rows[] = $r;
    }
    return $rows;
}

// 신청서에 적힌 품목 이름들 — rt_oi_ids 는 신청 당시의 기록이라 거절 뒤 재신청해도 남는다
// (품목 쪽 oi_rt_id 는 "지금 이 품목을 잡고 있는 신청" 이라 새 신청이 덮어쓴다).
function cart_return_item_names($rt)
{
    global $g5;
    $ids = array_filter(array_map('intval', explode(',', $rt['rt_oi_ids'])));
    if (!$ids) return array();
    $names = array();
    $result = sql_query(" select oi_name, oi_option, oi_qty from `{$g5['ycart_order_item_table']}`
        where oi_id in (".implode(',', $ids).") order by oi_id ");
    while ($r = sql_fetch_array($result)) {
        $names[] = $r['oi_name'].($r['oi_option'] !== '' ? ' ('.$r['oi_option'].')' : '').' × '.$r['oi_qty'];
    }
    return $names;
}

// 관리자 목록용 반품 품목 — 여러 신청의 품목을 두 방으로 모아 [rt_id => [{name, suffix, edit_url}]] 로.
//
// 반품관리는 한 화면에 30건이 뜬다. 신청마다 cart_return_item_names() 를 부르면 질의가 줄 수만큼
// 늘고, 그러고도 "어떤 상품이 돌아왔나" 를 이름으로 짐작해야 한다. 여기서 상품 수정 화면으로 가는
// 문까지 함께 만든다 — 반품이 잦은 상품은 설명이나 옵션을 손봐야 하는 상품이고, 그 판단을 하려면
// 결국 상품을 열어 봐야 한다.
//
// 링크는 살아 있는 상품에만 건다(주문 상세와 같은 규칙) — 없는 상품으로 보내면 수정 화면이
// '없는 상품입니다' 로 튕긴다. 주문서는 스냅샷이라 상품 행이 사라져도 이름·금액은 그대로 읽힌다.
function cart_return_items_for_admin(array $returns)
{
    global $g5;

    $map = array();
    $want = array();
    foreach ($returns as $rt) {
        $map[(int)$rt['rt_id']] = array();
        foreach (array_filter(array_map('intval', explode(',', $rt['rt_oi_ids']))) as $id) $want[$id] = true;
    }
    if (!$want) return $map;

    $rows = array();
    $it_ids = array();
    $res = sql_query(" select oi_id, it_id, oi_name, oi_option, oi_qty
        from `{$g5['ycart_order_item_table']}` where oi_id in (".implode(',', array_keys($want)).") ");
    while ($r = sql_fetch_array($res)) {
        $rows[(int)$r['oi_id']] = $r;
        if ((int)$r['it_id']) $it_ids[(int)$r['it_id']] = true;
    }

    $alive = array();
    if ($it_ids) {
        $res = sql_query(" select it_id from `{$g5['ycart_item_table']}`
            where it_id in (".implode(',', array_keys($it_ids)).") ");
        while ($r = sql_fetch_array($res)) $alive[(int)$r['it_id']] = true;
    }

    foreach ($returns as $rt) {
        $ids = array_filter(array_map('intval', explode(',', $rt['rt_oi_ids'])));
        sort($ids);   // 주문서에 적힌 차례 그대로(oi_id 순) — 표마다 순서가 달라지지 않게
        foreach ($ids as $id) {
            if (!isset($rows[$id])) continue;   // 품목 행이 없는 옛 자료 — 조용히 건너뛴다
            $r = $rows[$id];
            $iid = (int)$r['it_id'];
            $map[(int)$rt['rt_id']][] = array(
                'name' => $r['oi_name'],
                // 옵션·수량은 링크 밖에 둔다 — 누를 곳은 상품 이름이라고 화면이 말해야 한다
                'suffix' => ($r['oi_option'] !== '' ? ' ('.$r['oi_option'].')' : '').' × '.$r['oi_qty'],
                'edit_url' => isset($alive[$iid]) ? G5_CART_ADMIN_URL.'/item_form.php?w=u&it_id='.$iid : '',
            );
        }
    }
    return $map;
}

// 신청에 걸린 품목 금액 합계 — 관리자 화면이 환불 입력의 기본값으로 제안한다(제안일 뿐이다)
function cart_return_item_total($rt)
{
    global $g5;
    $ids = array_filter(array_map('intval', explode(',', $rt['rt_oi_ids'])));
    if (!$ids) return 0;
    $row = sql_fetch(" select coalesce(sum(oi_total), 0) as amt from `{$g5['ycart_order_item_table']}`
        where oi_id in (".implode(',', $ids).") ");
    return (int)$row['amt'];
}

// 처리를 기다리는 신청이 있는가 — 있으면 구매확정을 막는다(다른 품목의 새 신청은 막지 않는다)
function cart_return_open($od_id)
{
    global $g5;
    $row = sql_fetch(" select rt_id from `{$g5['ycart_return_table']}`
        where od_id = '".(int)$od_id."' and rt_status in ('requested', 'approving') limit 1 ");
    return $row ? (int)$row['rt_id'] : 0;
}

function cart_return_blocks_confirm($od_id)
{
    return cart_return_open($od_id) > 0;
}

// 아직 반품하지 않은 품목 — 신청 화면이 고를 수 있는 목록
function cart_return_available_items($od_id)
{
    $rows = array();
    foreach (cart_order_items((int)$od_id) as $it) {
        if ($it['oi_status'] === 'normal') $rows[] = $it;
    }
    return $rows;
}

// 반품 신청을 받을 수 있는 주문인가 — 사유 문자열을 돌려준다(빈 문자열이면 가능).
// 화면과 처리 화면이 같은 판정을 쓰도록 한 곳에 모은다.
function cart_return_why_not($order)
{
    if (!$order) return '주문을 찾을 수 없습니다.';
    if ($order['od_status'] !== 'delivered') {
        return ($order['od_status'] === 'confirmed')
            ? '구매확정한 주문은 반품 신청을 받지 않습니다. 판매자에게 문의해 주세요.'
            : '배송완료된 주문만 반품 신청할 수 있습니다.';
    }
    // 처리 중인 신청이 있어도 다른 품목은 따로 신청할 수 있다. 신청끼리 겹칠 일이 없기 때문이다 —
    // 이미 신청된 품목은 return_req 가 되어 아래 목록에서 빠지므로 남은 것만 고르게 된다.
    // (처음엔 "한 번에 한 건" 으로 막았는데, 4품목 주문에서 한 건 신청하자 나머지 3품목이
    //  통째로 잠겼다. 오늘 하나 반품하고 내일 또 하나 반품하는 건 흔한 일이다.)
    if (!cart_return_available_items((int)$order['od_id'])) {
        return cart_return_open((int)$order['od_id'])
            ? '신청하신 반품이 처리를 기다리고 있습니다.'
            : '반품할 수 있는 상품이 없습니다.';
    }

    $days = cart_return_days();
    if ($days > 0) {
        $base = $order['od_delivered_at'];
        // 배송완료 시각이 없는 옛 주문(컬럼 추가 전)은 기한을 따지지 않는다 — 기준이 없는데
        // 막으면 멀쩡한 주문이 영영 신청 불가가 된다. 기록이 있는 주문부터 기한이 산다.
        if (substr($base, 0, 4) !== '1970' && strtotime($base) + $days * 86400 < G5_SERVER_TIME) {
            return '반품 신청 기간('.$days.'일)이 지났습니다. 판매자에게 문의해 주세요.';
        }
    }
    return '';
}

// 남은 결제 금액 — 이미 환불한 누계를 뺀 값. 환불 입력의 상한이다.
function cart_return_refundable($order)
{
    return max(0, (int)$order['od_total'] - (int)$order['od_refund']);
}

// 고객 신청 — 고른 품목을 잡아 두고(oi_status='return_req') 신청 행을 만든다.
// 잠금 아래에서 품목 상태를 다시 확인한다: 화면을 열어 둔 사이 관리자가 먼저 처리했을 수 있다.
// 성공 시 array('rt_id' => n), 실패 시 사유 문자열.
function cart_return_create($order, $oi_ids, $reason, $bank = '')
{
    global $g5;
    $od_id = (int)$order['od_id'];
    $oi_ids = array_values(array_unique(array_filter(array_map('intval', (array)$oi_ids))));
    if (!$oi_ids) return '반품할 상품을 선택해 주세요.';
    $reason = trim(strip_tags($reason));
    if ($reason === '') return '반품 사유를 입력해 주세요.';

    $why = cart_return_why_not($order);
    if ($why !== '') return $why;

    $fail = '';
    $rt_id = 0;
    sql_query(" set autocommit = 0 ", true);
    sql_query(" start transaction ", true);

    // 고른 품목이 아직 이 주문의 정상 품목인지 잠근 채로 확인 — 남의 주문 품목 번호를
    // 섞어 보내도 여기서 걸린다(개수가 안 맞으면 중단).
    $in = implode(',', $oi_ids);
    $cnt = sql_fetch(" select count(*) as cnt from `{$g5['ycart_order_item_table']}`
        where od_id = '$od_id' and oi_id in ($in) and oi_status = 'normal' for update ");
    if ((int)$cnt['cnt'] !== count($oi_ids)) {
        $fail = '이미 반품 처리된 상품이 있습니다. 화면을 새로고침해 주세요.';
    }

    if ($fail === '') {
        sql_query(" insert into `{$g5['ycart_return_table']}`
            (od_id, mb_id, rt_status, rt_oi_ids, rt_reason, rt_bank, rt_requested_at)
            values ('$od_id', '".sql_real_escape_string($order['mb_id'])."', 'requested',
                    '".implode(',', $oi_ids)."',
                    '".sql_real_escape_string(mb_substr($reason, 0, 255, 'utf-8'))."',
                    '".sql_real_escape_string(mb_substr(trim(strip_tags($bank)), 0, 100, 'utf-8'))."',
                    '".G5_TIME_YMDHIS."') ", true);
        $rt_id = (int)sql_insert_id();
        sql_query(" update `{$g5['ycart_order_item_table']}`
            set oi_status = 'return_req', oi_rt_id = '$rt_id'
            where od_id = '$od_id' and oi_id in ($in) and oi_status = 'normal' ", true);
        if (get_sql_affected_rows() < 1) $fail = '반품 신청에 실패했습니다. 다시 시도해 주세요.';
    }

    sql_query($fail === '' ? " commit " : " rollback ", true);
    sql_query(" set autocommit = 1 ", true);
    return $fail === '' ? array('rt_id' => $rt_id) : $fail;
}

// 거절 — 잡아 두었던 품목을 정상으로 되돌린다. 돈은 움직이지 않는다.
function cart_return_reject($rt_id, $memo, $who)
{
    global $g5;
    $rt_id = (int)$rt_id;
    $rt = cart_return_get($rt_id);
    if (!$rt) return '반품 신청을 찾을 수 없습니다.';
    if ($rt['rt_status'] !== 'requested') return '이미 처리된 신청입니다.';

    sql_query(" update `{$g5['ycart_return_table']}`
        set rt_status = 'rejected', rt_bank = '',
            rt_memo = '".sql_real_escape_string(mb_substr(trim(strip_tags($memo)), 0, 255, 'utf-8'))."',
            rt_done_at = '".G5_TIME_YMDHIS."', rt_done_by = '".sql_real_escape_string($who)."'
        where rt_id = '$rt_id' and rt_status = 'requested' ", true);
    if (get_sql_affected_rows() < 1) return '이미 처리된 신청입니다.';

    // 품목은 정상으로. oi_rt_id 는 남겨 둔다 — 어느 신청에서 되돌아왔는지가 이력이다.
    sql_query(" update `{$g5['ycart_order_item_table']}`
        set oi_status = 'normal' where oi_rt_id = '$rt_id' and oi_status = 'return_req' ", true);
    return '';
}

// 승인 — [선점 → PG 환불 → 재고 복원 → 품목·주문 반영] 순서로 간다.
//
// 선점(requested → approving)을 먼저 하는 이유: 승인 버튼이 두 번 눌리면 환불이 두 번 나간다.
// 상태를 먼저 채가고 못 채간 쪽은 그 자리에서 멈춘다. 환불이 실패하면 requested 로 되돌린다.
//
// 환불이 먼저이고 실패하면 전부 중단하는 것은 취소 흐름과 같은 "돈 우선" 규율이다 —
// 돈이 안 돌아갔는데 반품만 처리되는 상태를 만들지 않는다.
//
// 재고 복원은 실패해도 중단하지 않는다. 취소와 다른 점인데, 반품은 취소보다 한참 뒤에 일어나
// 그 사이 SKU 가 지워졌을 수 있다. 여기서 멈추면 이미 나간 환불을 되돌릴 수 없다 —
// 복원하지 못한 것은 경고로 돌려주어 사람이 재고를 손으로 맞추게 한다.
//
// 반환: array('error' => 사유, 'warn' => 경고문자열배열)
function cart_return_approve($rt_id, $refund, $restock, $memo, $who)
{
    global $g5;
    $rt_id = (int)$rt_id;
    $warn = array();

    $rt = cart_return_get($rt_id);
    if (!$rt) return array('error' => '반품 신청을 찾을 수 없습니다.', 'warn' => $warn);
    if ($rt['rt_status'] !== 'requested') return array('error' => '이미 처리된 신청입니다.', 'warn' => $warn);

    $order = cart_order_get((int)$rt['od_id']);
    if (!$order) return array('error' => '주문을 찾을 수 없습니다.', 'warn' => $warn);

    $refund = max(0, (int)$refund);
    $refundable = cart_return_refundable($order);
    if ($refund > $refundable) {
        return array('error' => '환불 금액이 남은 결제 금액('.number_format($refundable).'원)보다 큽니다.', 'warn' => $warn);
    }

    // 1) 선점 — 여기서 밀린 쪽은 환불을 시도조차 하지 않는다
    sql_query(" update `{$g5['ycart_return_table']}` set rt_status = 'approving'
        where rt_id = '$rt_id' and rt_status = 'requested' ", true);
    if (get_sql_affected_rows() < 1) return array('error' => '이미 처리된 신청입니다.', 'warn' => $warn);

    $restore = function () use ($g5, $rt_id) {
        sql_query(" update `{$g5['ycart_return_table']}` set rt_status = 'requested'
            where rt_id = '".(int)$rt_id."' and rt_status = 'approving' ", true);
    };

    // 2) 환불 — PG 결제만 자동이다. 무통장은 관리자가 은행에서 직접 보내고 여기엔 기록만 남는다.
    if ($refund > 0 && $order['od_pay_method'] !== 'bank') {
        $reason = '반품 환불'.($memo !== '' ? ' - '.$memo : '');
        $err = cart_pay_refund($order, $reason, $who, $refund);
        if ($err !== '') {
            $restore();
            return array('error' => '전자결제 환불 실패 — 반품은 처리하지 않았습니다. '.$err, 'warn' => $warn);
        }
    }

    // 3) 재고 복원 — 관리자가 체크했을 때만. 물건이 훼손돼 돌아오는 일이 있어 자동이 아니다.
    $oi_ids = array_filter(array_map('intval', explode(',', $rt['rt_oi_ids'])));
    if ($restock && $oi_ids) {
        $result = sql_query(" select oi_id, sk_id, oi_name, oi_qty from `{$g5['ycart_order_item_table']}`
            where oi_id in (".implode(',', $oi_ids).") ");
        while ($it = sql_fetch_array($result)) {
            if (!cart_stock_move((int)$it['sk_id'], (int)$it['oi_qty'], 'return', $order['od_no'], $who)) {
                $warn[] = $it['oi_name'].' — 재고를 되돌리지 못했습니다(옵션이 삭제된 상품). 재고를 직접 확인하세요.';
            }
        }
    }

    // 4) 반영 — 품목을 반품 처리하고, 전 품목이 반품이면 주문도 접는다
    sql_query(" set autocommit = 0 ", true);
    sql_query(" start transaction ", true);

    sql_query(" update `{$g5['ycart_order_item_table']}`
        set oi_status = 'returned' where oi_rt_id = '$rt_id' and oi_status = 'return_req' ", true);

    sql_query(" update `{$g5['ycart_order_table']}`
        set od_refund = od_refund + '$refund' where od_id = '".(int)$order['od_id']."' ", true);

    $left = sql_fetch(" select count(*) as cnt from `{$g5['ycart_order_item_table']}`
        where od_id = '".(int)$order['od_id']."' and oi_status <> 'returned' ");
    if ((int)$left['cnt'] === 0) {
        // 전 품목 반품 — 주문 자체를 반품으로 접는다. 전이 화이트리스트(cart_order_transition)를
        // 타지 않는 유일한 상태 변경인데, 이 전이는 "품목이 다 반품됐는가" 라는 품목 쪽 조건에
        // 딸려 있어 단일 상태 전이 모델에 맞지 않는다. 대신 같은 규율(한 함수·잠금 안)을 지킨다.
        sql_query(" update `{$g5['ycart_order_table']}` set od_status = 'returned'
            where od_id = '".(int)$order['od_id']."' and od_status in ('delivered', 'confirmed') ", true);
        // 전 품목이 돌아왔으면 쓴 쿠폰도 손님에게 돌려준다. 일부만 반품일 때는 돌려주지 않는다 —
        // 남은 품목이 그 할인을 받은 채로 남아 있어서, 되살리면 한 장으로 두 번 깎이는 셈이 된다.
        cart_coupon_release((int)$order['od_id']);
    }

    // 환불 계좌는 여기서 지운다 — 송금이 끝나면 더 쓸 데 없는 개인정보다
    sql_query(" update `{$g5['ycart_return_table']}`
        set rt_status = 'approved', rt_refund = '$refund', rt_restock = '".($restock ? 1 : 0)."',
            rt_bank = '',
            rt_memo = '".sql_real_escape_string(mb_substr(trim(strip_tags($memo)), 0, 255, 'utf-8'))."',
            rt_done_at = '".G5_TIME_YMDHIS."', rt_done_by = '".sql_real_escape_string($who)."'
        where rt_id = '$rt_id' ", true);

    sql_query(" commit ", true);
    sql_query(" set autocommit = 1 ", true);
    return array('error' => '', 'warn' => $warn);
}

// 관리자 목록·대시보드용 — 처리를 기다리는 신청 수
function cart_return_pending_count()
{
    global $g5;
    $row = sql_fetch(" select count(*) as cnt from `{$g5['ycart_return_table']}`
        where rt_status in ('requested', 'approving') ");
    return (int)$row['cnt'];
}

function cart_return_status_label($status)
{
    $map = array('requested' => '접수', 'approving' => '처리 중',
        'approved' => '반품 완료', 'rejected' => '거절');
    return isset($map[$status]) ? $map[$status] : $status;
}

// 품목 상태 라벨 — 주문 상세가 줄마다 표시한다. 정상은 굳이 적지 않는다(빈 문자열).
function cart_return_item_label($oi_status)
{
    $map = array('return_req' => '반품 접수', 'returned' => '반품');
    return isset($map[$oi_status]) ? $map[$oi_status] : '';
}
