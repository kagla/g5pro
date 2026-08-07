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
    $row = sql_fetch(" select * from `{$g5['ycart_order_table']}` where od_id = '".(int)$od_id."' ");
    return $row ? $row : null;
}

function cart_order_get_by_no($od_no)
{
    global $g5;
    $od_no = sql_real_escape_string(trim($od_no));
    if ($od_no === '') return null;
    $row = sql_fetch(" select * from `{$g5['ycart_order_table']}` where od_no = '$od_no' ");
    return $row ? $row : null;
}

function cart_order_items($od_id)
{
    global $g5;
    $rows = array();
    $result = sql_query(" select * from `{$g5['ycart_order_item_table']}`
        where od_id = '".(int)$od_id."' order by oi_id ");
    while ($r = sql_fetch_array($result)) $rows[] = $r;
    return $rows;
}

// 주문번호 — 전부 숫자, yymmdd-hhiiss-nnnn (예: 260806-211459-4831).
// 날짜-시각-난수가 하이픈으로 나뉘어 한눈에 읽히고 전화로 불러주기도 쉽다.
// 같은 초의 충돌은 난수 4자리(1/10000)가 가르고, UNIQUE 제약과 짝을 이뤄 충돌 시 재생성한다.
function cart_order_no()
{
    $no = '';
    for ($try = 0; $try < 6; $try++) {
        $no = date('ymd-His-', G5_SERVER_TIME).sprintf('%04d', mt_rand(0, 9999));
        if (!cart_order_get_by_no($no)) return $no;
    }
    return $no;
}

function cart_order_status_label($status, $pay_method = '')
{
    // unpaid 는 수단에 따라 말이 다르다 — 무통장은 입금을 기다리고, 카드는 결제를 기다린다
    if ($status === 'unpaid') {
        return ($pay_method === '' || $pay_method === 'bank') ? '입금대기' : '결제대기';
    }
    $map = array(
        'paid' => '결제완료', 'preparing' => '배송준비',
        'shipping' => '배송중', 'delivered' => '배송완료', 'confirmed' => '구매확정',
        'canceled' => '취소됨',
    );
    return isset($map[$status]) ? $map[$status] : $status;
}

// 체크아웃 대상 — 바구니에서 구매 가능한 행만. 화면(checkout.php)과 제출(checkout_update.php)이
// 같은 함수를 쓰므로 두 화면이 서로 다른 목록을 보는 일이 없다.
// $only_ct_ids — 바로구매 스코프: 이 ct_id 들만 주문 대상으로 본다(나머지 바구니 행은 없는 셈).
// 주문서 화면과 주문 생성이 같은 스코프를 받아야 expect_ct_ids 대조가 어긋나지 않는다.
function cart_checkout_lines($owner = null, $only_ct_ids = null)
{
    $lines = array();
    $blocked = array();
    foreach (cart_cart_items($owner) as $r) {
        if (is_array($only_ct_ids) && !in_array((int)$r['ct_id'], $only_ct_ids, true)) continue;
        if ($r['avail'] && !$r['over_stock']) $lines[] = $r;
        else $blocked[] = $r;
    }
    return array('lines' => $lines, 'blocked' => $blocked);
}

// 주문 생성 — 트랜잭션 안에서 [현재가 재계산 → 재고 원자 차감 → 스냅샷 insert → 바구니 비움].
// 재고가 하나라도 부족하면 전부 롤백하고 오류 문자열을 돌려준다. 성공 시 array(od_id, od_no).
// $input: od_name, od_hp, od_email, od_zip, od_addr1, od_addr2, od_memo,
//         od_pay_method('bank'), od_depositor, guest_pw(비회원 필수), mb_id('' = 비회원)
//
// $draft=true — PG 결제용 초안. 결제 전에는 주문이 "저장"되지 않는다는 사용자 요구의 구현:
//   재고 미차감·바구니 유지, od_status='draft', 비울 ct_id 목록만 od_ct_ids 에 기록.
//   초안은 모든 조회 화면에서 제외되고, 승인 확정(cart_order_confirm_paid)이 재고 차감과
//   함께 paid 로 전이하며, 그때 cart_order_after_paid() 가 바구니를 비운다.
//   같은 세션이 다시 제출하면 이전 초안을 지우고 새로 만든다(주문서 수정 반영).
function cart_order_create($input, $owner = null, $draft = false)
{
    global $g5;

    // 바로구매 스코프 — 주문서가 보여준 것과 같은 행들만 대상으로 한다
    $only = (isset($input['only_ct_ids']) && is_array($input['only_ct_ids']) && count($input['only_ct_ids']))
        ? $input['only_ct_ids'] : null;
    $picked = cart_checkout_lines($owner, $only);
    $lines = $picked['lines'];
    if (!count($lines)) return '주문할 수 있는 상품이 없습니다.';

    // 화면-제출 대조 — 주문서가 보여준 품목 집합·상품합과 지금이 다르면(그 사이 품절·가격 변경)
    // 조용히 다른 주문을 만들지 않고 되돌려 보낸다. 주문서 폼이 hidden 으로 기대값을 보낸다.
    if (isset($input['expect_ct_ids'])) {
        $expect = array_filter(array_map('intval', explode(',', (string)$input['expect_ct_ids'])));
        $now = array_map('intval', array_column($lines, 'ct_id'));
        sort($expect);
        sort($now);
        if ($expect !== $now) return '장바구니 상태가 바뀌었습니다(품절 등). 주문서를 다시 확인해 주세요.';
    }
    if (isset($input['expect_item_total'])) {
        $expect_total = (int)$input['expect_item_total'];
        $now_total = 0;
        foreach ($lines as $l) $now_total += (int)$l['sk_price'] * (int)$l['ct_qty'];
        if ($expect_total !== $now_total) return '상품 가격이 변경되었습니다. 주문서를 다시 확인해 주세요.';
    }

    $mb_id = isset($input['mb_id']) ? trim($input['mb_id']) : '';
    if ($mb_id === '' && strlen(trim($input['guest_pw'])) < 4) {
        return '비회원 주문 비밀번호를 4자 이상 입력하세요.';
    }

    // 금액 재계산 — 바구니 표시가가 아니라 지금 이 시점의 SKU 가격으로 확정한다
    $item_total = 0;
    foreach ($lines as $l) $item_total += (int)$l['sk_price'] * (int)$l['ct_qty'];
    $ship_fee = cart_shipping_fee($item_total, $input['od_zip']);
    $total = $item_total + $ship_fee;

    $od_no = cart_order_no();
    $who = $mb_id !== '' ? $mb_id : 'guest';

    // 이 세션의 이전 초안은 버린다 — 아직 draft 인 경우에만(결제가 끝났으면 건드리지 않는다)
    if ($draft && !empty($_SESSION['ss_cart_draft_od_id'])) {
        $old_id = (int)$_SESSION['ss_cart_draft_od_id'];
        $old = cart_order_get($old_id);
        if ($old && $old['od_status'] === 'draft') {
            sql_query(" delete from `{$g5['ycart_payment_table']}` where od_id = '$old_id' ", true);
            sql_query(" delete from `{$g5['ycart_order_item_table']}` where od_id = '$old_id' ", true);
            sql_query(" delete from `{$g5['ycart_order_table']}` where od_id = '$old_id' and od_status = 'draft' ", true);
        }
        unset($_SESSION['ss_cart_draft_od_id']);
    }

    // 컬럼 길이 상한 — STRICT 모드 서버에서 초과 입력이 트랜잭션 중 DB 오류로 죽지 않게
    // 저장 전에 자른다(utf8 varchar 는 문자 수 기준이라 mb_substr)
    $cap = function ($v, $len) { return mb_substr(trim($v), 0, $len, 'utf-8'); };
    $input['od_name'] = $cap($input['od_name'], 50);
    $input['od_hp'] = $cap($input['od_hp'], 20);
    // 수령인 — 비우면 주문자와 같다(화면의 "주문자와 동일" 체크가 기본)
    if (!isset($input['od_recv_name']) || trim($input['od_recv_name']) === '') $input['od_recv_name'] = $input['od_name'];
    if (!isset($input['od_recv_hp']) || trim($input['od_recv_hp']) === '') $input['od_recv_hp'] = $input['od_hp'];
    $input['od_recv_name'] = $cap($input['od_recv_name'], 50);
    $input['od_recv_hp'] = $cap($input['od_recv_hp'], 20);
    $input['od_email'] = $cap($input['od_email'], 100);
    $input['od_zip'] = $cap($input['od_zip'], 10);
    $input['od_addr1'] = $cap($input['od_addr1'], 255);
    $input['od_addr2'] = $cap($input['od_addr2'], 255);
    $input['od_memo'] = $cap($input['od_memo'], 255);
    $input['od_depositor'] = $cap($input['od_depositor'], 50);

    sql_query(" START TRANSACTION ", true);

    sql_query(" insert into `{$g5['ycart_order_table']}`
        (od_no, mb_id, od_name, od_hp, od_email, od_recv_name, od_recv_hp,
         od_zip, od_addr1, od_addr2, od_memo,
         od_item_total, od_ship_fee, od_coupon, od_point, od_total,
         od_status, od_pay_method, od_depositor, od_guest_pw, od_ct_ids, od_ip, od_datetime)
        values ('".sql_real_escape_string($od_no)."',
                '".sql_real_escape_string($mb_id)."',
                '".sql_real_escape_string(strip_tags(trim($input['od_name'])))."',
                '".sql_real_escape_string(strip_tags(trim($input['od_hp'])))."',
                '".sql_real_escape_string(strip_tags(trim($input['od_email'])))."',
                '".sql_real_escape_string(strip_tags(trim($input['od_recv_name'])))."',
                '".sql_real_escape_string(strip_tags(trim($input['od_recv_hp'])))."',
                '".sql_real_escape_string(strip_tags(trim($input['od_zip'])))."',
                '".sql_real_escape_string(strip_tags(trim($input['od_addr1'])))."',
                '".sql_real_escape_string(strip_tags(trim($input['od_addr2'])))."',
                '".sql_real_escape_string(strip_tags(trim($input['od_memo'])))."',
                '$item_total', '$ship_fee', 0, 0, '$total',
                '".($draft ? 'draft' : 'unpaid')."',
                '".sql_real_escape_string($input['od_pay_method'])."',
                '".sql_real_escape_string(strip_tags(trim($input['od_depositor'])))."',
                '".sql_real_escape_string($mb_id === '' ? create_hash(trim($input['guest_pw'])) : '')."',
                '".sql_real_escape_string($draft ? implode(',', array_map('intval', array_column($lines, 'ct_id'))) : '')."',
                '".sql_real_escape_string($_SERVER['REMOTE_ADDR'])."',
                '".G5_TIME_YMDHIS."') ", true);
    $od_id = (int)sql_insert_id();

    foreach ($lines as $l) {
        // 원자 차감 — 실패(그 사이 품절)면 전부 되돌린다. 초안은 차감하지 않는다(확정 때 차감).
        if (!$draft && !cart_stock_move((int)$l['sk_id'], -(int)$l['ct_qty'], 'order', $od_no, $who)) {
            sql_query(" ROLLBACK ", true);
            return "'".addslashes($l['it_name'])."' 의 재고가 부족합니다. 장바구니 수량을 확인해 주세요.";
        }
        sql_query(" insert into `{$g5['ycart_order_item_table']}`
            (od_id, it_id, sk_id, oi_name, oi_option, oi_price, oi_qty, oi_total, oi_status)
            values ('$od_id', '".(int)$l['it_id']."', '".(int)$l['sk_id']."',
                    '".sql_real_escape_string($l['it_name'])."',
                    '".sql_real_escape_string($l['opt_label'])."',
                    '".(int)$l['sk_price']."', '".(int)$l['ct_qty']."',
                    '".((int)$l['sk_price'] * (int)$l['ct_qty'])."', 'normal') ", true);
    }

    // 주문된 행만 바구니에서 비운다 — 구매 불가로 남겨둔 행은 유지. 초안은 결제 확정 때 비운다.
    if (!$draft) {
        foreach ($lines as $l) cart_cart_remove((int)$l['ct_id'], $owner);
    }

    sql_query(" COMMIT ", true);
    if ($draft) $_SESSION['ss_cart_draft_od_id'] = $od_id;

    // 배송지 자동 저장(회원) — 다음 주문서가 "저장된 배송지 불러오기"로 쓴다
    cart_address_save($mb_id, $input['od_name'], $input['od_hp'], $input['od_email'],
        $input['od_zip'], $input['od_addr1'], $input['od_addr2']);

    return array('od_id' => $od_id, 'od_no' => $od_no);
}

// ---------- 배송지 저장(주소록) ----------
// 회원 주문 때마다 자동 저장 — 주문자(이름·연락처·이메일)까지 함께 담아 "불러오기"가 주문서를
// 한 번에 채운다. 최근 10개만 남긴다. 비회원은 대상이 아니다.
//
// 같은 곳인지는 이름·연락처·주소로만 판단한다(이메일 제외). 이메일만 바꾼 주문이 같은 배송지의
// 새 줄을 만들면 주소록이 금방 지저분해지므로, 그때는 기존 줄의 이메일을 최신 값으로 갱신한다.
function cart_address_save($mb_id, $name, $hp, $email, $zip, $addr1, $addr2)
{
    global $g5;
    $mb_id = trim($mb_id);
    if ($mb_id === '' || trim($addr1) === '') return;

    $nm_e = sql_real_escape_string(mb_substr(trim($name), 0, 50, 'utf-8'));
    $hp_e = sql_real_escape_string(mb_substr(trim($hp), 0, 20, 'utf-8'));
    $em_e = sql_real_escape_string(mb_substr(trim($email), 0, 100, 'utf-8'));
    $zip_e = sql_real_escape_string(mb_substr(trim($zip), 0, 10, 'utf-8'));
    $a1_e = sql_real_escape_string(mb_substr(trim($addr1), 0, 255, 'utf-8'));
    $a2_e = sql_real_escape_string(mb_substr(trim($addr2), 0, 255, 'utf-8'));
    $mb_e = sql_real_escape_string($mb_id);

    $dup = sql_fetch(" select ad_id from `{$g5['ycart_address_table']}`
        where mb_id = '$mb_e' and ad_name = '$nm_e' and ad_hp = '$hp_e'
          and ad_zip = '$zip_e' and ad_addr1 = '$a1_e' and ad_addr2 = '$a2_e' ");
    if ($dup) {
        sql_query(" update `{$g5['ycart_address_table']}`
            set ad_email = '$em_e', ad_datetime = '".G5_TIME_YMDHIS."'
            where ad_id = '".(int)$dup['ad_id']."' ", true);
        return;
    }
    sql_query(" insert into `{$g5['ycart_address_table']}` (mb_id, ad_name, ad_hp, ad_email, ad_zip, ad_addr1, ad_addr2, ad_datetime)
        values ('$mb_e', '$nm_e', '$hp_e', '$em_e', '$zip_e', '$a1_e', '$a2_e', '".G5_TIME_YMDHIS."') ", true);

    // 오래된 것부터 정리 — 최근 10개 유지
    $result = sql_query(" select ad_id from `{$g5['ycart_address_table']}`
        where mb_id = '$mb_e' order by ad_datetime desc, ad_id desc limit 10, 100 ");
    while ($r = sql_fetch_array($result)) {
        sql_query(" delete from `{$g5['ycart_address_table']}` where ad_id = '".(int)$r['ad_id']."' ", true);
    }
}

// 주소록에서 한 건 지운다 — 반드시 자기 것만. mb_id 를 조건에 함께 넣어
// 남의 ad_id 를 보내도 아무 행에 닿지 않게 한다. 지운 행이 있으면 true.
function cart_address_delete($mb_id, $ad_id)
{
    global $g5;
    $mb_id = trim($mb_id);
    $ad_id = (int)$ad_id;
    if ($mb_id === '' || $ad_id < 1) return false;
    sql_query(" delete from `{$g5['ycart_address_table']}`
        where ad_id = '$ad_id' and mb_id = '".sql_real_escape_string($mb_id)."' ", true);
    return (bool)get_sql_affected_rows();
}

// 주문서 기본값 한 칸 — 가장 최근 주소록 값이 있으면 그것, 없으면 회원 정보.
// 주소록 조회는 한 요청에 한 번만 한다(칸마다 부르지 않게 캐시).
function cart_address_default($member, $ad_key, $mb_key)
{
    static $recent = null;
    if ($recent === null) {
        $list = !empty($member['mb_id']) ? cart_address_list($member['mb_id'], 1) : array();
        $recent = count($list) ? $list[0] : array();
    }
    if (!empty($recent[$ad_key])) return $recent[$ad_key];
    return isset($member[$mb_key]) ? $member[$mb_key] : '';
}

function cart_address_list($mb_id, $limit = 10)
{
    global $g5;
    $mb_id = trim($mb_id);
    if ($mb_id === '') return array();
    $rows = array();
    $result = sql_query(" select * from `{$g5['ycart_address_table']}`
        where mb_id = '".sql_real_escape_string($mb_id)."'
        order by ad_datetime desc, ad_id desc limit ".(int)$limit." ");
    while ($r = sql_fetch_array($result)) $rows[] = $r;
    return $rows;
}

// ---------- 관리자 상태 전이 ----------
// 허용 전이 화이트리스트 — 여기 없는 전이는 어떤 화면에서도 못 한다.
//   deposit  : unpaid(무통장) → paid (입금확인)
//   cancel   : unpaid·paid·preparing → canceled (+재고 복원. PG 환불은 별도 — 화면이 안내)
//   preparing: paid → preparing / shipping: paid·preparing → shipping / delivered: shipping → delivered
// 행을 잠그고 잠긴 상태로 재검증한다 — 결제 리턴(confirm_paid)과 같은 규율.
// 성공 시 빈 문자열, 실패 시 사유 문자열 반환.
function cart_order_transition($od_id, $action, $who = 'admin')
{
    global $g5;
    $od_id = (int)$od_id;

    $rules = array(
        'deposit' => array('from' => array('unpaid'), 'to' => 'paid'),
        'cancel' => array('from' => array('unpaid', 'paid', 'preparing'), 'to' => 'canceled'),
        'preparing' => array('from' => array('paid'), 'to' => 'preparing'),
        'shipping' => array('from' => array('paid', 'preparing'), 'to' => 'shipping'),
        'delivered' => array('from' => array('shipping'), 'to' => 'delivered'),
    );
    if (!isset($rules[$action])) return '허용되지 않는 처리입니다.';
    $rule = $rules[$action];

    $fail = '';
    sql_query(" set autocommit = 0 ", true);
    sql_query(" start transaction ", true);

    $cur = sql_fetch(" select * from `{$g5['ycart_order_table']}` where od_id = '$od_id' for update ");
    if (!$cur) {
        $fail = '주문이 없습니다.';
    } elseif (!in_array($cur['od_status'], $rule['from'], true)) {
        $fail = '현재 상태('.cart_order_status_label($cur['od_status'], $cur['od_pay_method']).')에서는 할 수 없는 처리입니다.';
    } elseif ($action === 'deposit' && $cur['od_pay_method'] !== 'bank') {
        $fail = '무통장 주문만 입금확인 처리할 수 있습니다.';
    }

    // 취소는 재고를 원장에 남기며 되돌린다 — 주문 생성(차감)의 정확한 역연산
    if ($fail === '' && $action === 'cancel') {
        foreach (cart_order_items($od_id) as $it) {
            if (!cart_stock_move((int)$it['sk_id'], (int)$it['oi_qty'], 'cancel', $cur['od_no'], $who)) {
                $fail = '재고 복원에 실패했습니다.';
                break;
            }
        }
    }

    if ($fail === '') {
        $set = " od_status = '".$rule['to']."' ";
        if ($action === 'deposit') $set .= ", od_paid_at = '".G5_TIME_YMDHIS."' ";
        if ($action === 'shipping') $set .= ", od_shipped_at = '".G5_TIME_YMDHIS."' ";
        sql_query(" update `{$g5['ycart_order_table']}` set $set
            where od_id = '$od_id' and od_status = '".sql_real_escape_string($cur['od_status'])."' ", true);
        if (get_sql_affected_rows() < 1) $fail = '상태가 이미 바뀌었습니다. 다시 확인해 주세요.';
    }

    sql_query($fail === '' ? " commit " : " rollback ", true);
    sql_query(" set autocommit = 1 ", true);
    return $fail;
}

function cart_order_set_invoice($od_id, $company, $invoice)
{
    global $g5;
    sql_query(" update `{$g5['ycart_order_table']}`
        set od_delivery_company = '".sql_real_escape_string(mb_substr(trim($company), 0, 50, 'utf-8'))."',
            od_invoice = '".sql_real_escape_string(mb_substr(trim($invoice), 0, 50, 'utf-8'))."'
        where od_id = '".(int)$od_id."' ", true);
}

// 결제 확정 직후 마무리 — 초안이 예약해 둔 장바구니 행을 비우고 세션 표식을 정리한다.
// 리턴(pay_return)은 사용자 브라우저 요청이라 주문 당시 세션이 살아 있다. od_ct_ids 를
// 비워 두 번 불려도 무해하게 한다. 무통장(비초안) 주문은 od_ct_ids 가 비어 있어 그냥 지나간다.
function cart_order_after_paid($od_id)
{
    global $g5;
    $od = cart_order_get((int)$od_id);
    if (!$od) return;
    if ($od['od_ct_ids'] !== '') {
        foreach (array_filter(array_map('intval', explode(',', $od['od_ct_ids']))) as $ct_id) {
            cart_cart_remove($ct_id);
        }
        sql_query(" update `{$g5['ycart_order_table']}` set od_ct_ids = '' where od_id = '".(int)$od_id."' ", true);
    }
    if (!empty($_SESSION['ss_cart_draft_od_id']) && (int)$_SESSION['ss_cart_draft_od_id'] === (int)$od_id) {
        unset($_SESSION['ss_cart_draft_od_id']);
    }
}
