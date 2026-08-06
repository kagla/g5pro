<?php
if (!defined('_GNUBOARD_')) exit;

// ---------- 배송비 ----------
// 몰 전역 단일 정책(설정 화면): 기본 배송비 + 조건부 무료(기준액 0 이면 없음) + 제주 추가비.
// 조건부 무료를 충족해도 제주 추가비는 남는다 — 실제 택배 원가가 남는 구간이라 몰 관례를 따른다.
function cart_shipping_fee($item_total, $zip = '')
{
    $cc = cart_config();
    $fee = (int)$cc['cc_ship_base'];
    if ((int)$cc['cc_ship_free'] > 0 && (int)$item_total >= (int)$cc['cc_ship_free']) {
        $fee = 0;
    }
    if (cart_zip_is_jeju($zip)) {
        $fee += (int)$cc['cc_ship_jeju'];
    }
    return $fee;
}

// 제주 판정 — 새 우편번호(5자리)는 제주 전역이 63000~63644, 프리픽스 '63' 으로 충분
function cart_zip_is_jeju($zip)
{
    $zip = preg_replace('/[^0-9]/', '', (string)$zip);
    return strlen($zip) === 5 && substr($zip, 0, 2) === '63';
}

// ---------- 주문 ----------

function cart_order_get($od_id)
{
    global $g5;
    $row = sql_fetch(" select * from `{$g5['cart_order_table']}` where od_id = '".(int)$od_id."' ");
    return $row ? $row : null;
}

function cart_order_get_by_no($od_no)
{
    global $g5;
    $od_no = sql_real_escape_string(trim($od_no));
    if ($od_no === '') return null;
    $row = sql_fetch(" select * from `{$g5['cart_order_table']}` where od_no = '$od_no' ");
    return $row ? $row : null;
}

function cart_order_items($od_id)
{
    global $g5;
    $rows = array();
    $result = sql_query(" select * from `{$g5['cart_order_item_table']}`
        where od_id = '".(int)$od_id."' order by oi_id ");
    while ($r = sql_fetch_array($result)) $rows[] = $r;
    return $rows;
}

// 주문번호 — 날짜 + 난수 8자(대문자 hex). UNIQUE 제약과 짝을 이뤄 충돌 시 재생성.
function cart_order_no()
{
    for ($try = 0; $try < 5; $try++) {
        $no = date('ymd', G5_SERVER_TIME).'-'.strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 8));
        if (!cart_order_get_by_no($no)) return $no;
    }
    return date('ymd', G5_SERVER_TIME).'-'.strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 12));
}

function cart_order_status_label($status)
{
    $map = array(
        'unpaid' => '입금대기', 'paid' => '결제완료', 'preparing' => '배송준비',
        'shipping' => '배송중', 'delivered' => '배송완료', 'confirmed' => '구매확정',
        'canceled' => '취소됨',
    );
    return isset($map[$status]) ? $map[$status] : $status;
}

// 체크아웃 대상 — 바구니에서 구매 가능한 행만. 화면(checkout.php)과 제출(checkout_update.php)이
// 같은 함수를 쓰므로 두 화면이 서로 다른 목록을 보는 일이 없다.
function cart_checkout_lines($owner = null)
{
    $lines = array();
    $blocked = array();
    foreach (cart_basket_items($owner) as $r) {
        if ($r['avail'] && !$r['over_stock']) $lines[] = $r;
        else $blocked[] = $r;
    }
    return array('lines' => $lines, 'blocked' => $blocked);
}

// 주문 생성 — 트랜잭션 안에서 [현재가 재계산 → 재고 원자 차감 → 스냅샷 insert → 바구니 비움].
// 재고가 하나라도 부족하면 전부 롤백하고 오류 문자열을 돌려준다. 성공 시 array(od_id, od_no).
// $input: od_name, od_hp, od_email, od_zip, od_addr1, od_addr2, od_memo,
//         od_pay_method('bank'), od_depositor, guest_pw(비회원 필수), mb_id('' = 비회원)
function cart_order_create($input, $owner = null)
{
    global $g5;

    $picked = cart_checkout_lines($owner);
    $lines = $picked['lines'];
    if (!count($lines)) return '주문할 수 있는 상품이 없습니다.';

    // 화면-제출 대조 — 주문서가 보여준 품목 집합·상품합과 지금이 다르면(그 사이 품절·가격 변경)
    // 조용히 다른 주문을 만들지 않고 되돌려 보낸다. 주문서 폼이 hidden 으로 기대값을 보낸다.
    if (isset($input['expect_bk_ids'])) {
        $expect = array_filter(array_map('intval', explode(',', (string)$input['expect_bk_ids'])));
        $now = array_map('intval', array_column($lines, 'bk_id'));
        sort($expect);
        sort($now);
        if ($expect !== $now) return '장바구니 상태가 바뀌었습니다(품절 등). 주문서를 다시 확인해 주세요.';
    }
    if (isset($input['expect_item_total'])) {
        $expect_total = (int)$input['expect_item_total'];
        $now_total = 0;
        foreach ($lines as $l) $now_total += (int)$l['sk_price'] * (int)$l['bk_qty'];
        if ($expect_total !== $now_total) return '상품 가격이 변경되었습니다. 주문서를 다시 확인해 주세요.';
    }

    $mb_id = isset($input['mb_id']) ? trim($input['mb_id']) : '';
    if ($mb_id === '' && strlen(trim($input['guest_pw'])) < 4) {
        return '비회원 주문 비밀번호를 4자 이상 입력하세요.';
    }

    // 금액 재계산 — 바구니 표시가가 아니라 지금 이 시점의 SKU 가격으로 확정한다
    $item_total = 0;
    foreach ($lines as $l) $item_total += (int)$l['sk_price'] * (int)$l['bk_qty'];
    $ship_fee = cart_shipping_fee($item_total, $input['od_zip']);
    $total = $item_total + $ship_fee;

    $od_no = cart_order_no();
    $who = $mb_id !== '' ? $mb_id : 'guest';

    sql_query(" START TRANSACTION ", true);

    sql_query(" insert into `{$g5['cart_order_table']}`
        (od_no, mb_id, od_name, od_hp, od_email, od_zip, od_addr1, od_addr2, od_memo,
         od_item_total, od_ship_fee, od_coupon, od_point, od_total,
         od_status, od_pay_method, od_depositor, od_guest_pw, od_ip, od_datetime)
        values ('".sql_real_escape_string($od_no)."',
                '".sql_real_escape_string($mb_id)."',
                '".sql_real_escape_string(strip_tags(trim($input['od_name'])))."',
                '".sql_real_escape_string(strip_tags(trim($input['od_hp'])))."',
                '".sql_real_escape_string(strip_tags(trim($input['od_email'])))."',
                '".sql_real_escape_string(strip_tags(trim($input['od_zip'])))."',
                '".sql_real_escape_string(strip_tags(trim($input['od_addr1'])))."',
                '".sql_real_escape_string(strip_tags(trim($input['od_addr2'])))."',
                '".sql_real_escape_string(strip_tags(trim($input['od_memo'])))."',
                '$item_total', '$ship_fee', 0, 0, '$total',
                'unpaid',
                '".sql_real_escape_string($input['od_pay_method'])."',
                '".sql_real_escape_string(strip_tags(trim($input['od_depositor'])))."',
                '".sql_real_escape_string($mb_id === '' ? create_hash(trim($input['guest_pw'])) : '')."',
                '".sql_real_escape_string($_SERVER['REMOTE_ADDR'])."',
                '".G5_TIME_YMDHIS."') ", true);
    $od_id = (int)sql_insert_id();

    foreach ($lines as $l) {
        // 원자 차감 — 실패(그 사이 품절)면 전부 되돌린다
        if (!cart_stock_move((int)$l['sk_id'], -(int)$l['bk_qty'], 'order', $od_no, $who)) {
            sql_query(" ROLLBACK ", true);
            return "'".addslashes($l['it_name'])."' 의 재고가 부족합니다. 장바구니 수량을 확인해 주세요.";
        }
        sql_query(" insert into `{$g5['cart_order_item_table']}`
            (od_id, it_id, sk_id, oi_name, oi_option, oi_price, oi_qty, oi_total, oi_status)
            values ('$od_id', '".(int)$l['it_id']."', '".(int)$l['sk_id']."',
                    '".sql_real_escape_string($l['it_name'])."',
                    '".sql_real_escape_string($l['opt_label'])."',
                    '".(int)$l['sk_price']."', '".(int)$l['bk_qty']."',
                    '".((int)$l['sk_price'] * (int)$l['bk_qty'])."', 'normal') ", true);
    }

    // 주문된 행만 바구니에서 비운다 — 구매 불가로 남겨둔 행은 유지
    foreach ($lines as $l) cart_basket_remove((int)$l['bk_id'], $owner);

    sql_query(" COMMIT ", true);
    return array('od_id' => $od_id, 'od_no' => $od_no);
}
