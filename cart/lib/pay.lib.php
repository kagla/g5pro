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
    sql_query(" insert into `{$g5['cart_payment_table']}`
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
    $row = sql_fetch(" select * from `{$g5['cart_order_table']}` where od_oid = '$oid' ");
    return $row ? $row : null;
}

// 결제 시도마다 새 oid 를 발급해 주문에 기록 — 한 번 결제창에 올린 oid 재사용 금지(부킹 교훈)
function cart_pay_new_oid($od)
{
    global $g5;
    $oid = $od['od_no'].'T'.G5_SERVER_TIME.rand(10, 99);
    sql_query(" update `{$g5['cart_order_table']}`
        set od_oid = '".sql_real_escape_string($oid)."'
        where od_id = '".(int)$od['od_id']."' ", true);
    return $oid;
}

// 승인 확정 — 주문 행을 잠그고 [상태 unpaid·oid 일치·금액 일치]를 잠긴 값으로 재검증한 뒤
// paid 로 전이한다. 빈 문자열이면 성공, 아니면 실패 사유 코드(호출자가 망취소로 되돌린다).
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

    $cur = sql_fetch(" select * from `{$g5['cart_order_table']}`
        where od_id = '".(int)$od_id."' for update ");

    if (!$cur) {
        $fail = 'missing';
    } elseif ($cur['od_status'] === 'paid') {
        // 같은 주문에 승인이 두 번(창 중복·리턴 중복). 같은 tid 면 이미 처리된 성공 —
        // 멱등 성공으로 두고(이력 추가 없음), 다른 tid 면 이번 승인을 되돌리게 한다
        $prev = sql_fetch(" select pm_id from `{$g5['cart_payment_table']}`
            where od_id = '".(int)$od_id."' and pm_tid = '".sql_real_escape_string($tid)."'
              and pm_status = 'approved' ");
        $fail = $prev ? '' : 'duplicate';
    } elseif ($cur['od_status'] !== 'unpaid') {
        $fail = 'status';
    } elseif ($cur['od_oid'] !== $oid) {
        // 결제창이 열린 사이 새 시도가 oid 를 갈아 끼웠다 — 이번 승인은 옛 시도의 것
        $fail = 'oid';
    } elseif ((int)$cur['od_total'] !== (int)$amount) {
        $fail = 'amount2';
    }

    if ($fail === '' && $cur['od_status'] === 'unpaid') {
        sql_query(" update `{$g5['cart_order_table']}`
            set od_status = 'paid', od_pay_method = '".sql_real_escape_string($method)."',
                od_paid_at = '".G5_TIME_YMDHIS."'
            where od_id = '".(int)$od_id."' and od_status = 'unpaid' ", true);
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

include_once(G5_PATH.'/cart/pay/inicis.lib.php');
include_once(G5_PATH.'/cart/pay/toss.lib.php');
