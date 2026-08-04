<?php
// 취소·환불(booking_refund) 회귀 테스트.
//
// 환불은 이니시스에 실 거래(TID)가 있어야만 부를 수 있어 스테이징으로도 자동 검증이 안 된다.
// 그래서 나가는 전문 하나만 가짜로 바꿔 끼우고 booking_refund() 자체는 그대로 태운다 —
// 금액 계산·상태 전이·중복 방어·로그는 모두 실코드가 판단한 결과를 DB 로 확인한다.
// (booking_refund 가 inicis_tid_cancel 을 function_exists 로 감싸 include 하므로
//  여기서 미리 올려 둔 함수가 이긴다. pay_test.php 의 HttpClient 대역과 같은 방식이다)
//
// 확인하는 것:
//   0원 환불은 전문을 보내지 않는다 / 전액·부분의 인자가 규격대로다 / 실패는 상태를 지킨다 /
//   취소 성공 코드는 '00' 이지 승인의 '0000' 이 아니다 / 같은 예약을 두 번 환불하지 않는다 /
//   어느 갈래든 로그가 한 줄 남는다 / 화면이 보여 준 예정액과 실제 환불액의 식이 같다
if (php_sapi_name() !== 'cli') die('CLI only');

define('BT_ROOT', dirname(dirname(dirname(__DIR__))));

// common.php 는 반드시 전역 스코프에서 include 한다 (함수 안이면 $g5·DB 연결이 사라진다)
$_SERVER['HTTP_HOST'] = 'localhost'; $_SERVER['REQUEST_URI'] = '/'; $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['SERVER_PORT'] = '80'; $_SERVER['SCRIPT_NAME'] = '/index.php';
if (file_exists('/run/mysqld/mysqld.sock')) ini_set('mysqli.default_socket', '/run/mysqld/mysqld.sock');
include_once BT_ROOT.'/common.php';
include_once G5_LIB_PATH.'/booking.lib.php';

// ── 이니시스 취소 API 대역 ──────────────────────────────────────────────────
// 부른 인자를 그대로 쌓아 두고, 시나리오가 시킨 응답을 돌려준다
$GLOBALS['rt_calls'] = array();
$GLOBALS['rt_reply'] = '{"resultCode":"00","resultMsg":"정상취소"}';

function inicis_tid_cancel($args, $is_part = false)
{
    $GLOBALS['rt_calls'][] = array('args' => $args, 'is_part' => $is_part);
    return $GLOBALS['rt_reply'];
}

// ── 단언 ────────────────────────────────────────────────────────────────────

$fail = 0;
function ok($cond, $name, $detail = '')
{
    global $fail;
    if ($cond) return;
    echo "FAIL: $name".($detail !== '' ? " — $detail" : '')."\n";
    $fail++;
}

// ── 밑준비 ──────────────────────────────────────────────────────────────────

sql_query(" insert into `{$g5['booking_room_table']}` set
    br_subject = '__refundtest__', br_content = '', br_base_person = 2, br_max_person = 4,
    br_person_price = 0, br_room_count = 9, br_weekday_price = 50000, br_weekend_price = 50000,
    br_use = 1, br_order = 0, br_datetime = now() ", true);
$test_br_id = sql_insert_id();

$made_bk = array();

// 결제가 끝난 예약 한 건을 심는다. $tid 를 '' 로 주면 거래번호 없는 예약이 된다
function rt_make($status, $total, $plan = 0, $tid = 'StdpayCARDINIpayTest0001', $memo = '')
{
    global $g5, $test_br_id, $made_bk;
    static $seq = 0;
    $checkin  = date('Y-m-d', G5_SERVER_TIME + (30 + $seq) * 86400);
    $checkout = date('Y-m-d', G5_SERVER_TIME + (31 + $seq) * 86400);
    $seq++;
    $bk_no = booking_new_no();
    sql_query(" insert into `{$g5['booking_table']}` set
        bk_no = '$bk_no', br_id = '".(int)$test_br_id."',
        bk_checkin = '$checkin', bk_checkout = '$checkout', bk_person = 2,
        bk_name = '검증', bk_hp = '010-0000-0000', bk_email = '', bk_request = '',
        bk_room_price = '".(int)$total."', bk_total_price = '".(int)$total."',
        bk_status = '".sql_real_escape_string($status)."',
        bk_oid = '".$bk_no."R".time()."', bk_tid = '".sql_real_escape_string($tid)."',
        bk_refund_plan_price = '".(int)$plan."',
        bk_cancel_memo = '".sql_real_escape_string($memo)."',
        bk_pay_time = now(), bk_datetime = now(), bk_ip = '127.0.0.1' ", true);
    $bk_id = sql_insert_id();
    $made_bk[] = $bk_id;
    return booking_get($bk_id);
}

function rt_logs($bk)
{
    global $g5;
    $rows = array();
    $r = sql_query(" select bl_type, bl_price, bl_result_code, bl_data
        from `{$g5['booking_inicis_log_table']}` where bl_oid = '".sql_real_escape_string($bk['bk_oid'])."' order by bl_id ");
    while ($x = sql_fetch_array($r)) $rows[] = $x;
    return $rows;
}

// 시나리오 하나를 돌린다. 호출 기록을 비우고 응답을 정해 준 뒤 booking_refund 를 부른다
function rt_run($bk, $price, $memo, $reply = '{"resultCode":"00","resultMsg":"정상취소"}')
{
    $GLOBALS['rt_calls'] = array();
    $GLOBALS['rt_reply'] = $reply;
    return booking_refund($bk, $price, $memo);
}

// ── 1. 환불액 계산 — 화면과 실제가 같은 식인가 ─────────────────────────────
// view.php 도 cancel_update.php 도 이 함수만 부른다. 10원 미만은 버린다
ok(booking_refund_amount(50000, 100) === 50000, '전액 환불율이면 결제액 그대로');
ok(booking_refund_amount(50000, 0) === 0, '환불율 0 이면 0원');
ok(booking_refund_amount(33333, 30) === 9990, '10원 미만은 버린다', (string)booking_refund_amount(33333, 30));
ok(booking_refund_amount(50000, 200) === 50000, '환불율이 100 을 넘어도 결제액을 넘지 않는다');
ok(booking_refund_amount(0, 100) === 0, '결제액이 0이면 0원');

// ── 2. 0원 환불 — 전문을 보내지 않는다 ─────────────────────────────────────
$bk = rt_make('cancel_req', 50000, 0);
$r = rt_run($bk, 0, '취소 승인');
$now = booking_get($bk['bk_id']);
ok($r['ok'] === true, '0원 환불도 취소는 성립한다', $r['msg']);
ok(count($GLOBALS['rt_calls']) === 0, '0원이면 이니시스를 부르지 않는다');
ok($now['bk_status'] === 'cancelled', '0원 환불도 상태는 cancelled', $now['bk_status']);
ok((int)$now['bk_refund_price'] === 0, '환불액 0원이 기록된다');
ok($now['bk_refund_time'] > '1970-01-02', '환불 시각이 남는다');
$logs = rt_logs($now);
ok(count($logs) === 1 && $logs[0]['bl_type'] === 'refund' && $logs[0]['bl_result_code'] === 'skip',
   '전문을 안 보낸 취소도 로그를 남긴다(skip)', json_encode($logs));

// ── 3. 전액 환불 — 인자가 규격대로인가 ─────────────────────────────────────
$bk = rt_make('cancel_req', 50000, 50000);
$r = rt_run($bk, 50000, '취소 승인');
$now = booking_get($bk['bk_id']);
ok($r['ok'] === true, '전액 환불이 성공한다', $r['msg']);
ok(count($GLOBALS['rt_calls']) === 1, '전액 환불은 전문을 한 번만 보낸다');
$call = $GLOBALS['rt_calls'][0];
ok($call['is_part'] === false, '총액과 같으면 전액취소(is_part=false)');
ok(!isset($call['args']['price']) && !isset($call['args']['confirmPrice']), '전액취소에는 price/confirmPrice 를 싣지 않는다');
$conf = booking_inicis_conf();
ok($call['args']['mid'] === $conf['mid'], '상점아이디를 예약 설정으로 덮어 보낸다', $call['args']['mid']);
ok($call['args']['key'] === $conf['iniapi_key'] && $call['args']['key'] !== '', 'INIAPI Key 를 예약 설정으로 덮어 보낸다');
ok($call['args']['url'] === $conf['refund_url'], '취소 주소를 예약 설정으로 덮어 보낸다', $call['args']['url']);
ok(isset($call['args']['audit']) && $call['args']['audit'] === false, '영카트 감사 로그는 끈다');
ok($call['args']['paymethod'] === 'Card' && $call['args']['tid'] === $bk['bk_tid'], '결제수단·거래번호를 그대로 싣는다');
ok($call['args']['msg'] !== '' && $call['args']['clientIp'] !== '', '취소 사유·요청 IP 가 비어 있지 않다');
ok($now['bk_status'] === 'cancelled' && (int)$now['bk_refund_price'] === 50000, '전액 환불이 예약에 기록된다');
$logs = rt_logs($now);
ok(count($logs) === 1 && $logs[0]['bl_result_code'] === '00' && (int)$logs[0]['bl_price'] === 50000,
   '성공 로그에 응답 코드와 환불액이 남는다', json_encode($logs));

// ── 4. 부분 환불 ───────────────────────────────────────────────────────────
$bk = rt_make('cancel_req', 50000, 25000);
$r = rt_run($bk, 25000, '취소 승인');
$now = booking_get($bk['bk_id']);
$call = $GLOBALS['rt_calls'][0];
ok($r['ok'] === true, '부분 환불이 성공한다', $r['msg']);
ok($call['is_part'] === true, '총액보다 적으면 부분취소(is_part=true)');
ok((int)$call['args']['price'] === 25000, '부분취소 price 는 돌려줄 돈', json_encode($call['args']));
ok((int)$call['args']['confirmPrice'] === 25000, '부분취소 confirmPrice 는 남길 승인액');
ok((int)$now['bk_refund_price'] === 25000 && $now['bk_status'] === 'cancelled', '부분 환불액이 기록된다');

// ── 5. 총액보다 많은 환불 요청 → 전액취소로 깎인다 ─────────────────────────
$bk = rt_make('confirmed', 50000);
$r = rt_run($bk, 99999, '관리자 직권 취소');
$call = $GLOBALS['rt_calls'][0];
ok($call['is_part'] === false, '총액을 넘는 요청은 전액취소로 보낸다');
ok((int)booking_get($bk['bk_id'])['bk_refund_price'] === 50000, '환불액은 결제액을 넘지 않는다');
ok($r['refund_price'] === 50000, '반환값의 환불액도 결제액까지만');

// ── 6. 취소 실패 코드 → 상태를 지킨다 ──────────────────────────────────────
$bk = rt_make('cancel_req', 50000, 50000);
$r = rt_run($bk, 50000, '취소 승인', '{"resultCode":"01","resultMsg":"취소 불가 거래"}');
$now = booking_get($bk['bk_id']);
ok($r['ok'] === false, '취소 실패는 실패로 돌려준다');
ok(strpos($r['msg'], '01') !== false, '실패 사유에 응답 코드가 들어간다', $r['msg']);
ok($now['bk_status'] === 'cancel_req', '취소가 실패하면 상태를 그대로 둔다(재시도 가능)', $now['bk_status']);
ok((int)$now['bk_refund_price'] === 0, '실패한 환불액은 기록하지 않는다');
$logs = rt_logs($now);
ok(count($logs) === 1 && $logs[0]['bl_result_code'] === '01', '실패도 로그를 남긴다', json_encode($logs));

// 같은 건을 다시 시도할 수 있어야 한다 (환불은 재시도가 유일한 구제책이다)
$r = rt_run(booking_get($bk['bk_id']), 50000, '취소 승인');
ok($r['ok'] === true, '실패한 뒤 다시 시도하면 환불된다', $r['msg']);
ok(booking_get($bk['bk_id'])['bk_status'] === 'cancelled', '재시도 성공이면 cancelled 로 간다');

// ── 7. 승인 코드 '0000' 은 취소 성공이 아니다 ──────────────────────────────
// 승인(return.php)은 '0000', 취소는 '00' 이다. 여기서 헷갈리면 나가지 않은 돈을 나갔다고 적는다
$bk = rt_make('cancel_req', 50000, 50000);
$r = rt_run($bk, 50000, '취소 승인', '{"resultCode":"0000","resultMsg":"승인 코드"}');
ok($r['ok'] === false, "'0000' 은 취소 성공으로 보지 않는다", $r['msg']);
ok(booking_get($bk['bk_id'])['bk_status'] === 'cancel_req', "'0000' 응답에는 상태를 옮기지 않는다");

// ── 8. 통신 실패 · 깨진 응답 ───────────────────────────────────────────────
$bk = rt_make('cancel_req', 50000, 50000);
$r = rt_run($bk, 50000, '취소 승인', '{"resultCode":"COMMUNICATION_FAILED","resultMsg":"timeout"}');
ok($r['ok'] === false && booking_get($bk['bk_id'])['bk_status'] === 'cancel_req', '통신 실패는 상태를 지킨다');
$logs = rt_logs($bk);
// bl_result_code 는 varchar(10) 이라 긴 코드는 잘려 들어간다 (booking_inicis_log 가 자른다)
ok(count($logs) === 1 && $logs[0]['bl_result_code'] === 'COMMUNICAT', '통신 실패 코드도 잘라서 남긴다', json_encode($logs));

$bk = rt_make('cancel_req', 50000, 50000);
$r = rt_run($bk, 50000, '취소 승인', 'not json at all');
ok($r['ok'] === false && booking_get($bk['bk_id'])['bk_status'] === 'cancel_req', '해석 못 할 응답은 상태를 지킨다');
$logs = rt_logs($bk);
ok(count($logs) === 1 && $logs[0]['bl_result_code'] === 'parse', '해석 실패는 parse 로 남는다', json_encode($logs));

// ── 9. 중복 승인 — 같은 돈을 두 번 환불하지 않는다 ─────────────────────────
$bk = rt_make('cancel_req', 50000, 50000);
rt_run($bk, 50000, '취소 승인');
$after_first = booking_get($bk['bk_id']);
// 화면이 들고 있던 옛 예약 배열(cancel_req 시절)로 한 번 더 부른다 — 승인 버튼 두 번 누르기
$r = rt_run($bk, 50000, '취소 승인');
$now = booking_get($bk['bk_id']);
ok($r['ok'] === false, '이미 취소된 예약은 다시 환불하지 않는다', $r['msg']);
ok(count($GLOBALS['rt_calls']) === 0, '중복 승인은 이니시스를 부르지도 않는다');
ok($now['bk_refund_time'] === $after_first['bk_refund_time'], '중복 승인이 환불 기록을 덮어쓰지 않는다');
$logs = rt_logs($now);
ok(count($logs) === 2 && $logs[1]['bl_result_code'] === 'reject', '거부된 시도도 로그에 남는다', json_encode($logs));

// ── 10. 취소할 수 없는 상태 ────────────────────────────────────────────────
$bk = rt_make('hold', 50000);
$r = rt_run($bk, 50000, '관리자 직권 취소');
ok($r['ok'] === false && count($GLOBALS['rt_calls']) === 0, '결제 전(hold) 예약은 환불하지 않는다', $r['msg']);
ok(booking_get($bk['bk_id'])['bk_status'] === 'hold', 'hold 상태는 그대로 남는다');

// ── 11. 거래번호가 없는 예약 ───────────────────────────────────────────────
$bk = rt_make('confirmed', 50000, 0, '');
$r = rt_run($bk, 50000, '관리자 직권 취소');
ok($r['ok'] === false && count($GLOBALS['rt_calls']) === 0, '거래번호가 없으면 전문을 보내지 않는다', $r['msg']);
ok(booking_get($bk['bk_id'])['bk_status'] === 'confirmed', '거래번호가 없어도 상태를 함부로 옮기지 않는다');

// 다만 0원 취소는 전문이 필요 없으므로 거래번호가 없어도 성립한다
$bk = rt_make('confirmed', 50000, 0, '');
$r = rt_run($bk, 0, '관리자 직권 취소');
ok($r['ok'] === true && booking_get($bk['bk_id'])['bk_status'] === 'cancelled',
   '0원 취소는 거래번호가 없어도 성립한다', $r['msg']);

// ── 12. 취소 사유 ──────────────────────────────────────────────────────────
$bk = rt_make('cancel_req', 50000, 50000, 'StdpayCARDINIpayTest0001', '일정이 바뀌었습니다');
rt_run($bk, 50000, '취소 승인');
ok(booking_get($bk['bk_id'])['bk_cancel_memo'] === '일정이 바뀌었습니다',
   '손님이 적어 둔 취소 사유는 덮어쓰지 않는다', booking_get($bk['bk_id'])['bk_cancel_memo']);

$bk = rt_make('confirmed', 50000);
rt_run($bk, 0, '관리자 직권 취소');
ok(booking_get($bk['bk_id'])['bk_cancel_memo'] === '관리자 직권 취소',
   '사유가 비어 있으면 처리 사유를 적는다', booking_get($bk['bk_id'])['bk_cancel_memo']);
ok(booking_get($bk['bk_id'])['bk_cancel_time'] > '1970-01-02', '직권 취소도 취소 시각을 찍는다');

// ── 13. 트랜잭션 뒷정리 ────────────────────────────────────────────────────
$row = sql_fetch(" select @@autocommit as v ");
ok((int)$row['v'] === 1, '실패 경로를 지난 뒤에도 autocommit 은 켜져 있다');

// ── 뒷정리 ─────────────────────────────────────────────────────────────────
foreach ($made_bk as $bk_id) {
    $b = booking_get($bk_id);
    if ($b) sql_query(" delete from `{$g5['booking_inicis_log_table']}` where bl_oid = '".sql_real_escape_string($b['bk_oid'])."' ", true);
    sql_query(" delete from `{$g5['booking_note_table']}` where bk_id = '".(int)$bk_id."' ", true);
    sql_query(" delete from `{$g5['booking_addon_item_table']}` where bk_id = '".(int)$bk_id."' ", true);
    sql_query(" delete from `{$g5['booking_table']}` where bk_id = '".(int)$bk_id."' ", true);
}
sql_query(" delete from `{$g5['booking_room_table']}` where br_id = '".(int)$test_br_id."' ", true);

echo $fail ? "refund_test: $fail FAIL\n" : "refund_test: OK\n";
exit($fail ? 1 : 0);
