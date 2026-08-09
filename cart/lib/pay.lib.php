<?php
if (!defined('_GNUBOARD_')) exit;

// ---------- 결제 공통 ----------
// 어댑터 규약: 각 PG 는 cart/pay/<pg>.lib.php 에
//   cart_<pg>_ready($order)  — 결제 시도 준비(새 oid 발급·기록) 후 결제창 파라미터 반환
//   cart_<pg>_return()       — 리턴 처리. 성공 시 주문을 paid 로 확정하고 완료 URL 반환, 실패 시 alert
// 를 두고, 이 파일이 수단 목록·확정 트랜잭션·로그를 공통 제공한다.

function cart_pay_methods()
{
    $cc = cart_config();
    $methods = array('bank' => '무통장입금');
    if (trim($cc['cc_inicis_mid']) !== '' && trim($cc['cc_inicis_signkey']) !== '') {
        $methods['inicis'] = '신용카드 (이니시스)';
    }
    if (trim($cc['cc_toss_ckey']) !== '' && trim($cc['cc_toss_skey']) !== '') {
        $methods['toss'] = '신용카드 (토스페이먼츠)';
    }
    return $methods;
}

// 결제 이력 행 — 요청·응답·망취소까지 전부 남긴다(대사의 근거). pm_status:
// req(승인 요청 직전) / approved / failed / netcancel
function cart_payment_log($od_id, $method, $tid, $amount, $status, $data)
{
    global $g5;
    sql_query(" insert into `{$g5['ycart_payment_table']}`
        (od_id, pm_method, pm_tid, pm_amount, pm_status, pm_data, pm_datetime, pm_approved_at)
        values ('".(int)$od_id."', '".sql_real_escape_string($method)."',
                '".sql_real_escape_string($tid)."', '".(int)$amount."',
                '".sql_real_escape_string($status)."',
                '".sql_real_escape_string(is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))."',
                '".G5_TIME_YMDHIS."',
                '".($status === 'approved' ? G5_TIME_YMDHIS : '1970-01-01 00:00:00')."') ", true);
}

// 결제창에 보여줄 주문명 — "첫 상품명 외 N건"
function cart_pay_goodname($od_id)
{
    $items = cart_order_items((int)$od_id);
    if (!count($items)) return '주문상품';
    $name = $items[0]['oi_name'];
    return count($items) > 1 ? $name.' 외 '.(count($items) - 1).'건' : $name;
}

function cart_order_get_by_oid($oid)
{
    global $g5;
    $oid = sql_real_escape_string(trim($oid));
    if ($oid === '') return null;
    $row = sql_fetch(" select * from `{$g5['ycart_order_table']}` where od_oid = '$oid' ");
    return $row ? $row : null;
}

// 결제 시도마다 새 oid 를 발급해 주문에 기록 — 한 번 결제창에 올린 oid 재사용 금지(부킹 교훈)
function cart_pay_new_oid($od)
{
    global $g5;
    $oid = $od['od_no'].'T'.G5_SERVER_TIME.rand(10, 99);
    sql_query(" update `{$g5['ycart_order_table']}`
        set od_oid = '".sql_real_escape_string($oid)."'
        where od_id = '".(int)$od['od_id']."' ", true);
    return $oid;
}

// 승인 확정 — 주문 행을 잠그고 [상태 unpaid/draft·oid 일치·금액 일치]를 잠긴 값으로 재검증한 뒤
// paid 로 전이한다. 빈 문자열이면 성공, 아니면 실패 사유 코드(호출자가 망취소로 되돌린다).
// draft(PG 초안)는 재고를 여기서 처음 차감한다 — 결제 사이 품절이면 'stock' 실패로 승인을
// 되돌린다(주문서 저장을 결제 뒤로 미룬 대가로, 이 좁은 창은 망취소가 책임진다).
//
// approved 이력은 반드시 이 트랜잭션 안(커밋 전)에서 남긴다. 커밋 뒤에 남기면 그 짧은 틈에
// 도착한 같은 tid 의 중복 리턴이 "paid 인데 approved 이력 없음" → duplicate 로 오판되어
// 방금 확정한 진짜 승인을 망취소한다(상품은 나가고 돈은 취소되는 최악의 유출). 잠금 아래
// 단일 기록자가 되면 이후 어떤 같은-tid 관찰자도 반드시 approved 행을 본다.
function cart_order_confirm_paid($od_id, $oid, $method, $tid, $amount)
{
    global $g5;
    $fail = '';
    sql_query(" set autocommit = 0 ", true);
    sql_query(" start transaction ", true);

    $cur = sql_fetch(" select * from `{$g5['ycart_order_table']}`
        where od_id = '".(int)$od_id."' for update ");

    if (!$cur) {
        $fail = 'missing';
    } elseif ($cur['od_status'] === 'paid') {
        // 같은 주문에 승인이 두 번(창 중복·리턴 중복). 같은 tid 면 이미 처리된 성공 —
        // 멱등 성공으로 두고(이력 추가 없음), 다른 tid 면 이번 승인을 되돌리게 한다
        $prev = sql_fetch(" select pm_id from `{$g5['ycart_payment_table']}`
            where od_id = '".(int)$od_id."' and pm_tid = '".sql_real_escape_string($tid)."'
              and pm_status = 'approved' ");
        $fail = $prev ? '' : 'duplicate';
    } elseif ($cur['od_status'] !== 'unpaid' && $cur['od_status'] !== 'draft') {
        $fail = 'status';
    } elseif ($cur['od_oid'] !== $oid) {
        // 결제창이 열린 사이 새 시도가 oid 를 갈아 끼웠다 — 이번 승인은 옛 시도의 것
        $fail = 'oid';
    } elseif ((int)$cur['od_total'] !== (int)$amount) {
        $fail = 'amount2';
    }

    $payable = $fail === '' && ($cur['od_status'] === 'unpaid' || $cur['od_status'] === 'draft');

    // 초안은 지금이 첫 재고 차감 — 결제 사이 품절이면 전부 롤백('stock' → 호출자가 망취소)
    if ($payable && $cur['od_status'] === 'draft') {
        $who = $cur['mb_id'] !== '' ? $cur['mb_id'] : 'guest';
        $items = cart_order_items((int)$od_id);
        foreach ($items as $it) {
            if (!cart_stock_move((int)$it['sk_id'], -(int)$it['oi_qty'], 'order', $cur['od_no'], $who)) {
                $fail = 'stock';
                break;
            }
        }
    }

    // 쿠폰 소진 — 재고와 같은 자리, 같은 무게다. 초안은 여러 개가 동시에 떠 있을 수 있어
    // "아직 안 쓴 장일 때만 잠근다" 는 원자 갱신이 유일한 진짜 방어선이다.
    // 실패하면 그 사이 다른 주문이 먼저 썼다는 뜻이므로 승인을 되돌린다(호출자가 망취소).
    if ($payable && $fail === '' && (int)$cur['od_cm_id'] > 0) {
        if (!cart_coupon_consume((int)$cur['od_cm_id'], (int)$od_id, (int)$cur['od_coupon'])) {
            $fail = 'coupon';
        }
    }

    if ($payable && $fail === '') {
        sql_query(" update `{$g5['ycart_order_table']}`
            set od_status = 'paid', od_pay_method = '".sql_real_escape_string($method)."',
                od_paid_at = '".G5_TIME_YMDHIS."'
            where od_id = '".(int)$od_id."' and od_status in ('unpaid', 'draft') ", true);
        if (get_sql_affected_rows() < 1) {
            $fail = 'update';
            sql_query(" rollback ", true);
        } else {
            cart_payment_log((int)$od_id, $method, $tid, (int)$amount, 'approved', array('oid' => $oid));
            sql_query(" commit ", true);
        }
    } elseif ($fail === '') {
        sql_query(" commit ", true);   // 멱등 성공(이미 paid, 같은 tid) — 바꿀 것 없음
    } else {
        sql_query(" rollback ", true);
    }
    sql_query(" set autocommit = 1 ", true);
    return $fail;
}

// 전자결제 환불 — 승인된 결제(approved 이력)를 PG API 로 되돌린다.
// 성공 시 빈 문자열, 실패 시 사유 문자열. 호출자는 실패하면 취소·반품 자체를 중단해야 한다
// (돈이 안 돌아갔는데 주문만 바뀌는 상태를 만들지 않는다). 결과는 'refund' 이력으로 남는다.
//
// $amount: 이번에 돌려줄 금액. 0 이면 "남은 금액 전부"(주문취소가 쓰는 값).
// 남은 금액은 결제액에서 이미 환불한 누계(od_refund)를 뺀 값이다 — 반품이 여러 번 쌓일 수 있다.
//
// 전체취소와 부분취소를 여기서 가른다. 한 번이라도 부분취소한 거래는 이후 전체취소 요청이
// PG 에서 거부되므로(순정 adm/shop_admin/orderformcartupdate.php 주석에 같은 함정이 적혀 있다),
// 이미 환불 이력이 있으면 남은 금액이라도 부분취소로 보낸다.
function cart_pay_refund($od, $reason, $who = 'admin', $amount = 0)
{
    global $g5;
    $od_id = (int)$od['od_id'];

    $appr = sql_fetch(" select * from `{$g5['ycart_payment_table']}`
        where od_id = '$od_id' and pm_status = 'approved' order by pm_id desc limit 1 ");
    if (!$appr || trim($appr['pm_tid']) === '') return '환불할 승인 이력(TID)이 없습니다.';
    $tid = trim($appr['pm_tid']);

    $refunded = (int)$od['od_refund'];
    $remain = (int)$od['od_total'] - $refunded;
    if ($remain <= 0) return '이미 전액 환불된 주문입니다.';

    $amount = (int)$amount;
    if ($amount <= 0 || $amount > $remain) $amount = $remain;
    $is_part = ($amount < $remain) || ($refunded > 0);

    if ($od['od_pay_method'] === 'inicis') {
        $fail = cart_inicis_refund($od, $tid, $reason,
            $is_part ? array('price' => $amount, 'confirm' => $remain - $amount) : null);
    } elseif ($od['od_pay_method'] === 'toss') {
        $fail = cart_toss_refund($od, $tid, $reason, $is_part ? $amount : 0);
    } else {
        return 'PG 결제 주문이 아닙니다.';
    }

    cart_payment_log($od_id, $od['od_pay_method'], $tid, $amount,
        $fail === '' ? 'refund' : 'failed',
        array('step' => 'admin_refund', 'part' => $is_part ? 1 : 0, 'remain' => $remain - $amount,
              'reason' => $reason, 'by' => $who, 'fail' => $fail));
    return $fail;
}

// 폼 인코딩 POST — 이니시스 INIAPI 용. array(HTTP 코드, 본문배열|null, 원문)
function cart_http_post_form($url, $body)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($body),
        CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded; charset=utf-8'),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_SSL_VERIFYPEER => true,
    ));
    $raw = curl_exec($ch);
    $code = ($raw === false) ? 0 : (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $parsed = ($raw !== false) ? json_decode($raw, true) : null;
    return array($code, is_array($parsed) ? $parsed : null, ($raw === false ? '' : $raw));
}

// JSON POST — 토스 승인·취소용. array(코드, 본문배열|null, 원문)
function cart_http_post_json($url, $body, $headers = array())
{
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => array_merge(array('Content-Type: application/json'), $headers),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_SSL_VERIFYPEER => true,
    ));
    $raw = curl_exec($ch);
    $code = ($raw === false) ? 0 : (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $parsed = ($raw !== false) ? json_decode($raw, true) : null;
    return array($code, is_array($parsed) ? $parsed : null, ($raw === false ? '' : $raw));
}

// 승인 응답에서 문자열 하나 — 배열이 섞여 와도 검증식이 새지 않게(부킹 booking_res 관례)
function cart_pay_res($res, $key)
{
    return (isset($res[$key]) && !is_array($res[$key])) ? (string)$res[$key] : '';
}

// 요청 원문에서 문자열 하나 — common.php 의 addslashes 를 되돌리고, 배열은 버린다
function cart_pay_req($key)
{
    if (!isset($_REQUEST[$key]) || is_array($_REQUEST[$key])) return '';
    return stripslashes((string)$_REQUEST[$key]);
}

include_once(G5_CART_PATH.'/pay/inicis.lib.php');
include_once(G5_CART_PATH.'/pay/toss.lib.php');
