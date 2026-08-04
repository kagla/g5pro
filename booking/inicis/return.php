<?php
// 이니시스 표준결제 승인 리턴 — 결제창이 카드 인증을 마치면 이 주소로 POST 한다.
//
// 인증(authToken)은 아직 돈이 아니다. 여기서 승인 API 를 불러야 실제로 매입이 잡힌다.
// 그래서 순서를 이렇게 잡는다:
//   1) 승인을 부르기 전에 걸러낼 수 있는 것은 모두 먼저 건다 (여기서 멈추면 돈은 움직이지 않았다)
//   2) 승인 요청 → 응답을 확정보다 먼저 로그에 남긴다 (대사의 근거)
//   3) 응답이 우리 예약과 한 치도 어긋나지 않을 때만 예약을 확정한다
//   4) 하나라도 어긋나거나 확정에 실패하면 망취소(netCancel)로 승인을 되돌리고 반드시 로그를 남긴다
include_once('./_common.php');

// PG 비종속 라이브러리 셋. 쇼핑몰을 쓰지 않는 설치에는 G5_SHOP_PATH 가 없다.
// class_exists 로 감싼다 — include_once 는 같은 파일만 막지, 다른 자리에서 같은 이름의
// 클래스를 이미 올려 둔 경우(회귀 테스트가 승인 응답을 대신 만들어 주는 등)를 막지 못한다
$inicis_path = (defined('G5_SHOP_PATH') ? G5_SHOP_PATH : G5_PATH.'/shop').'/inicis/libs';
if (!class_exists('INIStdPayUtil')) include_once($inicis_path.'/INIStdPayUtil.php');
if (!class_exists('properties'))    include_once($inicis_path.'/properties.php');
if (!class_exists('HttpClient'))    include_once($inicis_path.'/HttpClient.php');

@header('Cache-Control: no-store');

$booking_url  = G5_URL.'/booking/';
$pay_url      = G5_URL.'/booking/pay.php';
$complete_url = G5_URL.'/booking/complete.php';

// common.php 가 GPC 전체에 addslashes 를 걸어 둔다. 서명 계산과 승인 전문에는 원문이 들어가야
// 하므로 되돌려서 읽는다 (백슬래시가 없는 값이면 아무 일도 일어나지 않는다).
// 배열로 온 값은 통째로 버린다 — 문자열을 기대하는 자리에 배열이 오면 경고만 남고 검증이 샌다
function booking_req($key)
{
    if (!isset($_REQUEST[$key]) || is_array($_REQUEST[$key])) return '';
    return stripslashes((string)$_REQUEST[$key]);
}

$conf = booking_inicis_conf();
$util = new INIStdPayUtil();
$prop = new properties();

// ── 1. 승인 전 검증 ─────────────────────────────────────────────────────────

// 인증 실패·사용자 취소. 아직 승인 전이라 되돌릴 것이 없다
if (strcmp('0000', booking_req('resultCode')) !== 0) {
    $msg = clean_xss_tags(booking_req('resultMsg'));
    alert($msg !== '' ? $msg : '결제가 취소되었습니다.', $pay_url);
}

// 우리 예약 찾기 — 승인을 부르기 전에 한다. 예약을 못 찾은 채 승인부터 하면
// 되돌릴 대상도 모르는 돈이 잡힌다
$oid = preg_replace('/[^A-Za-z0-9\-_]/', '', booking_req('orderNumber'));
if ($oid === '') alert('주문번호가 없습니다.', $booking_url);

$bk = booking_get_by_oid($oid);
if (!$bk) alert('결제 대상 예약을 찾을 수 없습니다.', $booking_url);

$price = (int)$bk['bk_total_price'];

// 상점아이디 대조 — 우리 상점으로 온 결제가 맞는지
if (booking_req('mid') !== $conf['mid'])
    alert('요청된 상점아이디가 설정과 다릅니다.', $pay_url);

// 승인 URL 은 결제창이 준 값을 그대로 믿지 않는다. idc_name 으로 화이트리스트에서 다시 만들어
// 대조한다 (properties 가 아는 센터가 아니면 여기서 끝낸다 — 정의되지 않은 변수를 만들지 않기 위해
// 화이트리스트를 먼저 본다)
$idc_list = array('fc', 'ks', 'stg');
$idc_name = booking_req('idc_name');
$req_auth_url = booking_req('authUrl');
if (!in_array($idc_name, $idc_list, true) || strcmp($prop->getAuthUrl($idc_name), $req_auth_url) !== 0) {
    booking_inicis_log($oid, '', 'auth_req', $price, 'urlfail',
        json_encode(array('idc_name' => $idc_name, 'authUrl' => $req_auth_url), JSON_UNESCAPED_SLASHES));
    alert('승인 요청 주소가 올바르지 않습니다.', $pay_url);
}
$authUrl = $prop->getAuthUrl($idc_name);

$authToken = booking_req('authToken');
if ($authToken === '') alert('인증 토큰이 없습니다.', $pay_url);

// ── 2. 승인 요청 ────────────────────────────────────────────────────────────

$timestamp = $util->getTimestamp();
$authMap = array(
    'mid'       => $conf['mid'],
    'authToken' => $authToken,
    // authToken · timestamp 를 알파벳 순 NVP 로 이어 붙여 SHA-256
    'signature' => $util->makeSignature(array('authToken' => $authToken, 'timestamp' => $timestamp)),
    'timestamp' => $timestamp,
    'charset'   => 'UTF-8',
    'format'    => 'JSON',
);

booking_inicis_log($oid, '', 'auth_req', $price, '',
    json_encode(array('authUrl' => $authUrl, 'mid' => $conf['mid'], 'timestamp' => $timestamp,
        'bk_no' => $bk['bk_no']), JSON_UNESCAPED_SLASHES));

$http = new HttpClient();
$comm_ok = $http->processHTTP($authUrl, $authMap);
$res = $comm_ok ? json_decode($http->body, true) : null;
if (!is_array($res)) $res = array();

// 승인 응답에서 값 하나를 문자열로 꺼낸다. 없거나 배열이면 빈 문자열 —
// 배열이 섞여 들어와도 검증식이 경고만 남기고 통과해 버리는 일이 없게 한다
function booking_res($res, $key)
{
    return (isset($res[$key]) && !is_array($res[$key])) ? (string)$res[$key] : '';
}

$tid = booking_res($res, 'tid');

// 확정보다 먼저 남긴다. 뒤에서 무엇이 터지든 "승인이 실제로 있었는가"를 이 줄로 되짚을 수 있어야
// 대사(對査)가 된다 — 확정 뒤에 남기면 확정에 실패한 승인이 기록 없이 사라진다
$res_code = booking_res($res, 'resultCode');
booking_inicis_log($oid, $tid, 'auth_res', (int)booking_res($res, 'TotPrice'),
    $res_code !== '' ? $res_code : ($comm_ok ? 'parse' : 'http'),
    $comm_ok ? $http->body : ('통신 실패: '.$http->getErrorMsg()));

// ── 3. 승인 결과 검증 ───────────────────────────────────────────────────────
//
// 금액은 세 겹으로 본다: ① makesignature.php 가 서버가 읽은 청구액으로 서명했고,
// ② 그 금액이 authSignature(mid·tstamp·MOID·TotPrice) 안에 묶여 돌아오며,
// ③ 여기서 TotPrice 를 예약 행의 bk_total_price 와 다시 맞춘다.
$fail_reason = '';
$fail_msg = '';
if (!$comm_ok) {
    // 응답을 못 받았다고 승인이 없었다고 단정할 수 없다 (요청은 갔는데 응답만 끊겼을 수 있다).
    // 이니시스 표준 예제와 같이 망취소로 되돌린다 — 확신이 없을 때는 취소 쪽으로 실패한다
    $fail_reason = 'http';
} else if ($res_code === '') {
    $fail_reason = 'parse';
} else if (strcmp('0000', $res_code) !== 0) {
    $fail_reason = 'code';
    $fail_msg = clean_xss_tags(booking_res($res, 'resultMsg'));
} else if (booking_res($res, 'MOID') === '' || booking_res($res, 'TotPrice') === ''
        || booking_res($res, 'authSignature') === '') {
    // 성공이라면서 검증에 필요한 값이 빠졌다 — 믿을 수 없는 응답이다
    $fail_reason = 'parse';
} else {
    $secure = array('mid' => $conf['mid'], 'tstamp' => $timestamp,
        'MOID' => booking_res($res, 'MOID'), 'TotPrice' => booking_res($res, 'TotPrice'));
    if (strcmp($util->makeSignatureAuth($secure), booking_res($res, 'authSignature')) !== 0) $fail_reason = 'signature';
    else if (booking_res($res, 'MOID') !== $oid)             $fail_reason = 'moid';
    else if ((int)booking_res($res, 'TotPrice') !== $price)  $fail_reason = 'amount';
}

// ── 4. 확정 트랜잭션 ────────────────────────────────────────────────────────

if (!$fail_reason) {
    sql_query(" set autocommit = 0 ", true);
    sql_query(" start transaction ", true);

    // 잠금 순서는 booking_create_hold() 와 같게 객실 → 예약이다. 순서가 엇갈리면
    // 같은 객실을 두고 두 흐름이 서로를 기다리는 교착이 난다
    $room = sql_fetch(" select * from `{$g5['booking_room_table']}` where br_id = '".(int)$bk['br_id']."' for update ");
    $cur  = sql_fetch(" select * from `{$g5['booking_table']}` where bk_id = '".(int)$bk['bk_id']."' for update ");

    if (!$room || !$cur) {
        $fail_reason = 'missing';
    } else if ($cur['bk_status'] === 'confirmed') {
        // 같은 예약에 승인이 두 번 났다(창을 두 번 띄웠거나 리턴이 두 번 왔다).
        // 앞선 승인만 남기고 이번 건은 되돌린다
        $fail_reason = 'duplicate';
    } else if ($cur['bk_status'] !== 'hold') {
        $fail_reason = 'status';
    } else if ($cur['bk_oid'] !== $oid) {
        // 결제창이 열린 사이에 새 시도가 oid 를 갈아 끼웠다 — 지금 승인은 옛 시도의 것이다
        $fail_reason = 'oid';
    } else if ((int)$cur['bk_total_price'] !== (int)booking_res($res, 'TotPrice')) {
        // 잠그기 전에 읽은 금액이 낡았을 수 있다. 잠근 값으로 한 번 더 본다
        $fail_reason = 'amount2';
    } else if (strtotime($cur['bk_hold_expire']) < G5_SERVER_TIME) {
        // 점유가 풀린 뒤 결제가 끝났다. 방이 아직 남아 있으면 살려 주고(만료된 hold 는
        // booking_booked_count() 가 이미 세지 않는다), 남이 채갔으면 되돌린다
        foreach (booking_nights($cur['bk_checkin'], $cur['bk_checkout']) as $date) {
            if (booking_remain_count($room, $date) < 1) { $fail_reason = 'soldout'; break; }
        }
    }

    if (!$fail_reason) {
        // where 에 bk_status='hold' 를 한 번 더 건다 — 잠금 밖의 다른 경로가 끼어들어도
        // 확정은 hold 였던 행에만 걸린다
        sql_query(" update `{$g5['booking_table']}` set bk_status = 'confirmed',
            bk_tid = '".sql_real_escape_string($tid)."',
            bk_pay_time = '".date('Y-m-d H:i:s', G5_SERVER_TIME)."'
            where bk_id = '".(int)$bk['bk_id']."' and bk_status = 'hold' ", true);
        // 잠근 행이라 여기서 0 이 나올 일은 없지만, 만약 한 줄도 안 바뀌었다면 확정되지 않은
        // 예약을 확정된 셈 치고 메일까지 보내게 된다. 그럴 바에는 돈을 되돌린다
        if (get_sql_affected_rows() < 1) { $fail_reason = 'update'; sql_query(" rollback ", true); }
        else sql_query(" commit ", true);
    } else {
        sql_query(" rollback ", true);
    }
    sql_query(" set autocommit = 1 ", true);   // 어느 갈래로 가든 원래대로 돌려 놓는다
}

// ── 5. 실패 → 망취소 ───────────────────────────────────────────────────────

if ($fail_reason) {
    // 망취소 주소도 화이트리스트에서 다시 만들어 대조한다
    $netCancel = $prop->getNetCancel($idc_name);
    $req_net_url = booking_req('netCancelUrl');
    $sent = 'skip';
    $body = '';
    if (strcmp($netCancel, $req_net_url) === 0) {
        $http2 = new HttpClient();
        if ($http2->processHTTP($netCancel, $authMap)) { $sent = 'sent'; $body = $http2->body; }
        else { $sent = 'commfail'; $body = $http2->getErrorMsg(); }
    }
    // 망취소를 보내지 못한 경우에도 반드시 남긴다 — 사람이 손으로 취소해야 하는 건이
    // 기록 없이 사라지면 안 된다
    booking_inicis_log($oid, $tid, 'netcancel', $price, $fail_reason,
        json_encode(array('reason' => $fail_reason, 'netcancel' => $sent,
            'url' => $netCancel, 'req_url' => $req_net_url, 'body' => $body),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    if ($fail_reason === 'duplicate') {
        // 예약 자체는 이미 확정되어 있다. 완료 화면으로 안내하는 편이 맞다
        set_session('ss_booking_bk_no', $bk['bk_no']);
        set_session('ss_booking_inicis_oid', '');
        alert('이미 결제가 완료된 예약입니다. 이번 결제는 자동 취소되었습니다.', $complete_url);
    }
    if ($fail_reason === 'soldout')
        alert('결제 유효시간이 지나 예약이 마감되었습니다. 결제는 자동 취소되었습니다. 다시 예약해 주세요.', $booking_url);

    // 이니시스가 준 사유가 있으면 그대로 보여 준다 — "(code)" 만으로는 사용자가 할 수 있는 일이 없다
    $tail = ($fail_msg !== '') ? $fail_msg : '('.$fail_reason.')';
    alert('결제가 완료되지 않아 자동 취소되었습니다. '.$tail.' 다시 시도해 주세요.', $booking_url);
}

// ── 6. 확정 ────────────────────────────────────────────────────────────────

// 이 oid 로는 다시 서명을 받을 수 없어야 한다
set_session('ss_booking_inicis_oid', '');
booking_send_mail($bk['bk_id'], 'confirm');
set_session('ss_booking_bk_no', $bk['bk_no']);
goto_url($complete_url);
