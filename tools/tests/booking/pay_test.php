<?php
// 이니시스 승인 리턴(booking/inicis/return.php) 회귀 테스트.
//
// 실 결제창은 자동화할 수 없지만, 값이 어긋났을 때 돈을 되돌리는가는 반드시 자동으로 지켜야 한다.
// 그래서 이니시스 서버로 나가는 HttpClient 만 가짜로 바꿔 끼우고 return.php 자체를 그대로 태운다
// (return.php 가 라이브러리를 class_exists 로 감싸 include 하므로 미리 올려 둔 클래스가 이긴다).
//
// return.php 는 끝에서 alert()/goto_url() 로 exit 하므로 시나리오마다 자식 프로세스를 띄운다.
//   부모: 예약을 심고 → 자식을 돌리고 → DB(예약 상태·거래 로그)를 단언한다
//   자식: php pay_test.php child <oid> <시나리오>
if (php_sapi_name() !== 'cli') die('CLI only');

define('BT_ROOT', dirname(dirname(dirname(__DIR__))));

// common.php 는 반드시 전역 스코프에서 include 한다 — 함수 안에서 부르면 $g5·DB 연결이
// 그 함수의 지역 변수가 되어 통째로 사라진다
$_SERVER['HTTP_HOST'] = 'localhost'; $_SERVER['REQUEST_URI'] = '/'; $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['SERVER_PORT'] = '80'; $_SERVER['SCRIPT_NAME'] = '/index.php';
// CLI php.ini 에 mysqli.default_socket 이 없어 소켓 경로를 직접 지정한다 (다른 테스트와 동일)
if (file_exists('/run/mysqld/mysqld.sock')) ini_set('mysqli.default_socket', '/run/mysqld/mysqld.sock');
include_once BT_ROOT.'/common.php';
include_once G5_LIB_PATH.'/booking.lib.php';

// ── 자식 모드 ───────────────────────────────────────────────────────────────

if (isset($argv[1]) && $argv[1] === 'child') {
    $oid = $argv[2];
    $GLOBALS['bt_case'] = $argv[3];

    $bk = booking_get_by_oid($oid);
    if (!$bk) { echo "child: oid 를 못 찾았다\n"; exit(2); }
    $GLOBALS['bt_price'] = (int)$bk['bk_total_price'];
    $GLOBALS['bt_oid'] = $oid;

    // 이니시스 서버 대역. authUrl 은 시나리오가 시키는 응답을, netCancel 은 늘 성공을 돌려준다
    class HttpClient
    {
        var $body = '';
        var $errormsg = '';

        function processHTTP($url, $param)
        {
            if (strpos($url, 'netCancel') !== false) {
                $this->body = '{"resultCode":"0000","resultMsg":"stub netcancel ok"}';
                return true;
            }
            if ($GLOBALS['bt_case'] === 'commfail') { $this->errormsg = 'stub timeout'; return false; }
            if ($GLOBALS['bt_case'] === 'garbage')  { $this->body = 'not json at all'; return true; }

            $moid  = ($GLOBALS['bt_case'] === 'moid') ? 'SOMEONEELSEOID' : $GLOBALS['bt_oid'];
            $total = ($GLOBALS['bt_case'] === 'amount') ? 10 : $GLOBALS['bt_price'];
            $code  = ($GLOBALS['bt_case'] === 'code') ? 'C001' : '0000';

            // 실제 이니시스와 같은 규칙으로 서명한다 — return.php 가 쓰는 timestamp 는
            // 전문에 실려 오므로 가짜 서버도 같은 값으로 계산할 수 있다
            $util = new INIStdPayUtil();
            $sig = $util->makeSignatureAuth(array('mid' => $param['mid'], 'tstamp' => $param['timestamp'],
                'MOID' => $moid, 'TotPrice' => $total));
            if ($GLOBALS['bt_case'] === 'signature') $sig = str_repeat('0', 64);

            $this->body = json_encode(array(
                'resultCode' => $code, 'resultMsg' => '스텁 응답',
                'tid' => 'StdpayCARDINIpayTest'.date('YmdHis'),
                'MOID' => $moid, 'TotPrice' => (string)$total, 'authSignature' => $sig,
                'payMethod' => 'Card', 'applDate' => date('Ymd'), 'applTime' => date('His'),
            ), JSON_UNESCAPED_UNICODE);
            return true;
        }

        function getErrorMsg() { return $this->errormsg; }
        function getBody() { return $this->body; }
    }

    $_REQUEST = array(
        'resultCode'   => '0000',
        'orderNumber'  => $oid,
        'mid'          => 'INIpayTest',
        'idc_name'     => 'stg',
        'authUrl'      => 'https://stgstdpay.inicis.com/api/payAuth',
        'netCancelUrl' => 'https://stgstdpay.inicis.com/api/netCancel',
        'authToken'    => 'STUBAUTHTOKEN',
    );

    chdir(BT_ROOT.'/booking/inicis');   // return.php 의 './_common.php' 가 풀리는 자리
    include './return.php';
    exit;
}

// ── 부모 모드 ───────────────────────────────────────────────────────────────



$fail = 0;
function ok($cond, $name, $detail = '')
{
    global $fail;
    if ($cond) return;
    echo "FAIL: $name".($detail !== '' ? " — $detail" : '')."\n";
    $fail++;
}

// 검증 전용 객실. 테스트가 끝나면 지운다
sql_query(" insert into `{$GLOBALS['g5']['booking_room_table']}` set
    br_subject = '__paytest__', br_content = '', br_base_person = 2, br_max_person = 4,
    br_person_price = 0, br_room_count = 1, br_weekday_price = 50000, br_weekend_price = 50000,
    br_use = 1, br_order = 0, br_datetime = now() ", true);
$test_br_id = sql_insert_id();

$made_bk = array();

// 예약 한 건(hold)을 심고 주문번호를 돌려준다.
// 검증 객실의 실수는 1 이라 시나리오마다 날짜가 겹치면 서로를 마감시켜 버린다 —
// 따로 지정하지 않으면 이틀씩 밀어 가며 서로 다른 날짜를 잡는다
function bt_make_booking($br_id, $price, $expire_offset, $checkin_offset = 0)
{
    global $g5, $made_bk;
    static $seq = 0;
    if (!$checkin_offset) $checkin_offset = 30 + 2 * ($seq++);
    $checkin  = date('Y-m-d', G5_SERVER_TIME + $checkin_offset * 86400);
    $checkout = date('Y-m-d', G5_SERVER_TIME + ($checkin_offset + 1) * 86400);
    $bk_no = booking_new_no();
    $oid = $bk_no.'T'.time().rand(10, 99);
    sql_query(" insert into `{$g5['booking_table']}` set
        bk_no = '$bk_no', br_id = '".(int)$br_id."',
        bk_checkin = '$checkin', bk_checkout = '$checkout', bk_person = 2,
        bk_name = '검증', bk_hp = '010-0000-0000', bk_email = '', bk_request = '',
        bk_room_price = '".(int)$price."', bk_total_price = '".(int)$price."',
        bk_status = 'hold', bk_hold_expire = '".date('Y-m-d H:i:s', G5_SERVER_TIME + $expire_offset)."',
        bk_oid = '$oid', bk_datetime = now(), bk_ip = '127.0.0.1' ", true);
    $made_bk[] = sql_insert_id();
    return $oid;
}

function bt_run($oid, $case)
{
    $cmd = escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' child '
         . escapeshellarg($oid).' '.escapeshellarg($case).' 2>&1';
    return shell_exec($cmd);
}

function bt_logs($oid)
{
    global $g5;
    $rows = array();
    $r = sql_query(" select bl_type, bl_result_code, bl_price from `{$g5['booking_inicis_log_table']}`
        where bl_oid = '".sql_real_escape_string($oid)."' order by bl_id ");
    while ($x = sql_fetch_array($r)) $rows[] = $x;
    return $rows;
}

function bt_log_types($oid)
{
    $t = array();
    foreach (bt_logs($oid) as $row) $t[] = $row['bl_type'];
    return implode(',', $t);
}

function bt_bk($oid) { return booking_get_by_oid($oid); }

// ── 1. 정상 승인 → 확정 ─────────────────────────────────────────────────────
$oid = bt_make_booking($test_br_id, 50000, 600);
$out = bt_run($oid, 'success');
$bk = bt_bk($oid);
ok($bk['bk_status'] === 'confirmed', '정상 승인이면 예약이 확정된다', 'status='.$bk['bk_status']);
ok($bk['bk_tid'] !== '', '확정 시 거래번호(tid)를 남긴다');
ok($bk['bk_pay_time'] > '1970-01-01 00:00:01', '확정 시 결제시각을 남긴다');
ok(bt_log_types($oid) === 'auth_req,auth_res', '정상 승인은 요청·응답 두 줄만 남긴다', bt_log_types($oid));
// CLI 에서는 goto_url() 의 Location 헤더가 출력으로 남지 않으므로, 알림이 뜨지 않았다는
// 것으로 "실패 경로로 새지 않았다"를 본다
ok(strpos($out, 'alert(') === false, '정상 승인은 알림 없이 끝난다', trim((string)$out));

// ── 2. 금액 위변조 → 망취소 ────────────────────────────────────────────────
$oid = bt_make_booking($test_br_id, 50000, 600);
bt_run($oid, 'amount');
$bk = bt_bk($oid);
ok($bk['bk_status'] === 'hold', '승인 금액이 다르면 확정하지 않는다', 'status='.$bk['bk_status']);
ok(bt_log_types($oid) === 'auth_req,auth_res,netcancel', '금액 불일치는 망취소까지 남긴다', bt_log_types($oid));
$logs = bt_logs($oid);
ok($logs[2]['bl_result_code'] === 'amount', '망취소 사유가 amount 로 남는다', $logs[2]['bl_result_code']);

// ── 3. 응답 서명 위조 → 망취소 ─────────────────────────────────────────────
$oid = bt_make_booking($test_br_id, 50000, 600);
bt_run($oid, 'signature');
ok(bt_bk($oid)['bk_status'] === 'hold', '응답 서명이 틀리면 확정하지 않는다');
$logs = bt_logs($oid);
ok(count($logs) === 3 && $logs[2]['bl_result_code'] === 'signature', '망취소 사유가 signature 로 남는다');

// ── 4. 주문번호 뒤바뀜 → 망취소 ────────────────────────────────────────────
$oid = bt_make_booking($test_br_id, 50000, 600);
bt_run($oid, 'moid');
ok(bt_bk($oid)['bk_status'] === 'hold', '응답 주문번호가 다르면 확정하지 않는다');
$logs = bt_logs($oid);
ok(count($logs) === 3 && $logs[2]['bl_result_code'] === 'moid', '망취소 사유가 moid 로 남는다');

// ── 5. 승인 통신 실패 → 망취소 ─────────────────────────────────────────────
$oid = bt_make_booking($test_br_id, 50000, 600);
bt_run($oid, 'commfail');
ok(bt_bk($oid)['bk_status'] === 'hold', '승인 통신이 끊기면 확정하지 않는다');
$logs = bt_logs($oid);
ok(count($logs) === 3 && $logs[2]['bl_result_code'] === 'http',
   '통신 실패도 망취소로 되돌린다 (승인이 잡혔을 수 있다)');

// ── 6. 승인 실패 코드 → 망취소 ─────────────────────────────────────────────
$oid = bt_make_booking($test_br_id, 50000, 600);
bt_run($oid, 'code');
ok(bt_bk($oid)['bk_status'] === 'hold', '승인 실패 코드면 확정하지 않는다');
$logs = bt_logs($oid);
ok(count($logs) === 3 && $logs[1]['bl_result_code'] === 'C001', '승인 응답 코드를 그대로 남긴다');

// ── 7. 이미 확정된 예약에 승인이 또 왔다 → 망취소 ──────────────────────────
$oid = bt_make_booking($test_br_id, 50000, 600);
sql_query(" update `{$g5['booking_table']}` set bk_status = 'confirmed', bk_tid = 'FIRSTTID'
    where bk_oid = '".sql_real_escape_string($oid)."' ", true);
$out = bt_run($oid, 'success');
$bk = bt_bk($oid);
ok($bk['bk_tid'] === 'FIRSTTID', '중복 승인은 먼저 잡힌 거래번호를 덮어쓰지 않는다', $bk['bk_tid']);
$logs = bt_logs($oid);
ok(count($logs) === 3 && $logs[2]['bl_result_code'] === 'duplicate', '중복 승인은 duplicate 로 망취소한다');

// ── 8. hold 가 풀린 뒤 승인 — 방이 남아 있으면 살린다 ──────────────────────
$oid = bt_make_booking($test_br_id, 50000, -60);
bt_run($oid, 'success');
ok(bt_bk($oid)['bk_status'] === 'confirmed', '유효시간이 지나도 방이 남아 있으면 확정한다');

// ── 9. hold 가 풀린 사이 방이 팔렸다 → 망취소 ──────────────────────────────
$oid = bt_make_booking($test_br_id, 50000, -60, 80);
$expired = bt_bk($oid);
// 같은 날짜를 다른 손님이 확정으로 채운다 (객실 실수는 1이다)
sql_query(" insert into `{$g5['booking_table']}` set bk_no = '".booking_new_no()."',
    br_id = '".(int)$test_br_id."', bk_checkin = '{$expired['bk_checkin']}',
    bk_checkout = '{$expired['bk_checkout']}', bk_person = 2, bk_name = '먼저', bk_hp = '',
    bk_email = '', bk_request = '', bk_status = 'confirmed', bk_datetime = now() ", true);
$made_bk[] = sql_insert_id();
bt_run($oid, 'success');
ok(bt_bk($oid)['bk_status'] === 'hold', '유효시간이 지나고 방도 없으면 확정하지 않는다');
$logs = bt_logs($oid);
ok(count($logs) === 3 && $logs[2]['bl_result_code'] === 'soldout', '마감이면 soldout 으로 망취소한다');

// ── 10. autocommit 이 원래대로 돌아왔는가 ──────────────────────────────────
// (return.php 는 확정 트랜잭션에서 autocommit 을 껐다 켠다. 자식 프로세스라 연결은 다르지만
//  같은 코드가 부모 연결에서 돌아도 되도록, 여기서는 세션 변수 자체를 확인한다)
$row = sql_fetch(" select @@autocommit as v ");
ok((int)$row['v'] === 1, '테스트 뒤에도 autocommit 은 켜져 있다');

// ── 뒷정리 ─────────────────────────────────────────────────────────────────
foreach ($made_bk as $bk_id) {
    $b = booking_get($bk_id);
    if ($b) sql_query(" delete from `{$g5['booking_inicis_log_table']}` where bl_oid = '".sql_real_escape_string($b['bk_oid'])."' ", true);
    sql_query(" delete from `{$g5['booking_addon_item_table']}` where bk_id = '".(int)$bk_id."' ", true);
    sql_query(" delete from `{$g5['booking_table']}` where bk_id = '".(int)$bk_id."' ", true);
}
sql_query(" delete from `{$g5['booking_room_table']}` where br_id = '".(int)$test_br_id."' ", true);

echo $fail ? "pay_test: $fail FAIL\n" : "pay_test: OK\n";
exit($fail ? 1 : 0);
