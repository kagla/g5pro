<?php
// 결제대사 claim(booking_recon_claim) 회귀 테스트.
//
// 이 함수는 "승인은 났는데 확정되지 않은 결제"를 예약 행에 붙이는 자리다. 확정도 환불도
// 여기를 지나므로, 여기서 한 번 더 걸러야 하는 것들을 실제 DB 로 확인한다:
//   승인 로그가 근거인가 / 되돌린 기록(망취소·환불)이 이미 있는가 / 상태·금액·주문번호가 맞는가 /
//   같은 건을 두 번 붙이지 않는가 / 확정은 자리가 있어야 하는가
//
// 되돌린 기록 재확인이 특히 중요하다 — return.php 는 승인 검증에 실패하면 트랜잭션을 롤백한 뒤
// 잠금 없이 망취소를 쏜다. auth_res 는 남았는데 netcancel 은 아직 안 남은 창에서 관리자가
// "확정"을 누르면 곧 취소될 결제로 예약이 확정된다.
if (php_sapi_name() !== 'cli') die('CLI only');

define('BT_ROOT', dirname(dirname(dirname(__DIR__))));
$_SERVER['HTTP_HOST'] = 'localhost'; $_SERVER['REQUEST_URI'] = '/'; $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['SERVER_PORT'] = '80'; $_SERVER['SCRIPT_NAME'] = '/index.php';
if (file_exists('/run/mysqld/mysqld.sock')) ini_set('mysqli.default_socket', '/run/mysqld/mysqld.sock');
include_once BT_ROOT.'/common.php';
include_once G5_LIB_PATH.'/booking.lib.php';

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
    br_subject = '__recontest__', br_content = '', br_base_person = 2, br_max_person = 4,
    br_person_price = 0, br_room_count = 1, br_weekday_price = 50000, br_weekend_price = 50000,
    br_use = 1, br_order = 0, br_datetime = now() ", true);
$test_br_id = sql_insert_id();

$made_bk = array(); $made_log = array();

// 결제 직전에서 멈춘 예약 한 건 + 그 결제의 승인 성공 로그
function rc_make($oid, $total = 50000, $status = 'hold', $expire = '+1 hour', $checkin = '+30 day')
{
    global $g5, $test_br_id, $made_bk;
    $in  = date('Y-m-d', strtotime($checkin));
    $out = date('Y-m-d', strtotime($checkin.' +1 day'));
    sql_query(" insert into `{$g5['booking_table']}` set
        bk_no = '".booking_new_no()."', br_id = '$test_br_id',
        bk_checkin = '$in', bk_checkout = '$out', bk_person = 2,
        bk_name = '__recontest__', bk_hp = '010-0000-0000', bk_email = '', bk_request = '',
        mb_id = '', bk_password = '', bk_room_price = '$total', bk_person_price = 0,
        bk_addon_price = 0, bk_total_price = '$total', bk_status = '$status',
        bk_hold_expire = '".date('Y-m-d H:i:s', strtotime($expire))."',
        bk_oid = '$oid', bk_tid = '', bk_datetime = now(), bk_ip = '127.0.0.1' ", true);
    $id = sql_insert_id();
    $made_bk[] = $id;
    return booking_get($id);
}

function rc_log($oid, $tid, $type, $price, $code)
{
    global $g5, $made_log;
    sql_query(" insert into `{$g5['booking_inicis_log_table']}` set bl_oid = '$oid', bl_tid = '$tid',
        bl_type = '$type', bl_price = '$price', bl_result_code = '$code', bl_data = '{}',
        bl_datetime = '".date('Y-m-d H:i:s', G5_SERVER_TIME)."' ", true);
    $id = sql_insert_id();
    $made_log[] = $id;
    return sql_fetch(" select * from `{$g5['booking_inicis_log_table']}` where bl_id = '$id' ");
}

function rc_status($bk_id)
{
    global $g5;
    return sql_fetch(" select bk_status, bk_tid, bk_pay_time from `{$g5['booking_table']}` where bk_id = '".(int)$bk_id."' ");
}

// ── 1. 근거가 되는 로그가 아니면 거부한다 ───────────────────────────────────

$bk = rc_make('RCT0001');
$r = booking_recon_claim($bk, null, true);
ok(!$r['ok'], '1-1 로그가 없으면 거부');
$r = booking_recon_claim($bk, rc_log('RCT0001', 'RCTTID1', 'auth_res', 50000, '9999'), true);
ok(!$r['ok'], '1-2 승인 실패 코드의 로그는 거부', $r['msg']);
$r = booking_recon_claim($bk, rc_log('RCT0001', 'RCTTID1', 'auth_req', 50000, '0000'), true);
ok(!$r['ok'], '1-3 승인 요청 로그는 거부', $r['msg']);
$r = booking_recon_claim($bk, rc_log('RCT9999', 'RCTTID1', 'auth_res', 50000, '0000'), true);
ok(!$r['ok'], '1-4 주문번호가 다른 로그는 거부', $r['msg']);
$r = booking_recon_claim($bk, rc_log('RCT0001', '', 'auth_res', 50000, '0000'), true);
ok(!$r['ok'], '1-5 거래번호 없는 로그는 거부', $r['msg']);
$r = booking_recon_claim($bk, rc_log('RCT0001', 'RCTTID1', 'auth_res', 40000, '0000'), true);
ok(!$r['ok'], '1-6 승인 금액이 청구액과 다르면 거부', $r['msg']);
ok(rc_status($bk['bk_id'])['bk_status'] === 'hold', '1-7 거부된 동안 예약은 그대로');

// ── 2. 되돌린 기록이 있으면 거부한다 (망취소 · 환불) ────────────────────────
//
// 목록 쿼리도 같은 조건으로 거르지만, 목록을 그린 뒤 버튼을 누르기까지의 창이 남는다.
// 그 창에서 return.php 의 망취소가 끼어들어도 확정되지 않아야 한다.

$bk = rc_make('RCT0002');
$log = rc_log('RCT0002', 'RCTTID2', 'auth_res', 50000, '0000');
rc_log('RCT0002', 'RCTTID2', 'netcancel', 50000, 'amount');   // 화면을 그린 뒤 들어온 망취소
$r = booking_recon_claim($bk, $log, true);
ok(!$r['ok'], '2-1 망취소 기록이 있으면 확정을 거부', $r['msg']);
ok(strpos($r['msg'], '취소') !== false, '2-2 거부 사유가 취소·환불임을 알린다', $r['msg']);
ok(rc_status($bk['bk_id'])['bk_status'] === 'hold', '2-3 예약은 hold 그대로');
ok(rc_status($bk['bk_id'])['bk_tid'] === '', '2-4 거래번호도 붙지 않았다');

$bk = rc_make('RCT0003');
$log = rc_log('RCT0003', 'RCTTID3', 'auth_res', 50000, '0000');
rc_log('RCT0003', 'RCTTID3', 'refund', 50000, '00');
$r = booking_recon_claim($bk, $log, false);
ok(!$r['ok'], '2-5 환불 기록이 있으면 환불용 claim 도 거부', $r['msg']);
ok(rc_status($bk['bk_id'])['bk_status'] === 'hold', '2-6 예약은 hold 그대로 (이중 환불 없음)');

// ── 3. 정상 claim ───────────────────────────────────────────────────────────

$bk = rc_make('RCT0004');
$log = rc_log('RCT0004', 'RCTTID4', 'auth_res', 50000, '0000');
$r = booking_recon_claim($bk, $log, true);
ok($r['ok'], '3-1 정상 건은 통과', $r['msg']);
$cur = rc_status($bk['bk_id']);
ok($cur['bk_status'] === 'confirmed', '3-2 확정 상태가 된다', $cur['bk_status']);
ok($cur['bk_tid'] === 'RCTTID4', '3-3 거래번호를 로그에서 옮겨 적는다', $cur['bk_tid']);
ok($cur['bk_pay_time'] === $log['bl_datetime'], '3-4 결제 시각은 승인 로그의 시각', $cur['bk_pay_time']);

// 같은 버튼을 두 번 누른 경우
$r = booking_recon_claim(booking_get($bk['bk_id']), $log, true);
ok(!$r['ok'], '3-5 두 번째 claim 은 거부 (이중 확정 없음)', $r['msg']);
// 첫 claim 이 남긴 결과는 그대로여야 한다
ok(rc_status($bk['bk_id'])['bk_tid'] === 'RCTTID4', '3-6 앞선 결과를 덮지 않는다');
// 화면이 들고 있던 옛 예약 행(hold 시절)으로 다시 불러도 거부한다
$r = booking_recon_claim($bk, $log, true);
ok(!$r['ok'], '3-7 낡은 예약 배열로 불러도 잠근 행을 다시 읽어 거부', $r['msg']);

// ── 4. 확정은 자리가 있어야 한다 ────────────────────────────────────────────
//
// 만료된 hold 만 잔여를 본다 (살아 있는 점유는 booking_booked_count 가 이미 제 몫으로 센다).
// 객실 실수는 1 이고 3-1 에서 이미 한 건을 확정해 두었으므로 같은 날짜는 자리가 없다.

$full_in = date('Y-m-d', strtotime('+30 day'));
$bk = rc_make('RCT0005', 50000, 'hold', '-1 hour', '+30 day');   // 만료된 hold, 같은 날짜
$log = rc_log('RCT0005', 'RCTTID5', 'auth_res', 50000, '0000');
$r = booking_recon_claim($bk, $log, true);
ok(!$r['ok'], '4-1 자리가 없으면 확정을 거부', $r['msg']);
ok(strpos($r['msg'], $full_in) !== false, '4-2 어느 날짜가 막혔는지 알린다', $r['msg']);
// 같은 건이라도 환불용 claim 은 통과해야 한다 — 돈을 돌려주는 데는 자리가 필요 없다
$r = booking_recon_claim($bk, $log, false);
ok($r['ok'], '4-3 환불용 claim 은 자리를 보지 않는다', $r['msg']);
ok(rc_status($bk['bk_id'])['bk_status'] === 'confirmed', '4-4 환불용 claim 도 확정으로 붙인다');

// ── 5. 확정·취소된 예약은 이 화면의 것이 아니다 ─────────────────────────────

$bk = rc_make('RCT0006', 50000, 'cancelled', '+1 hour', '+60 day');
$log = rc_log('RCT0006', 'RCTTID6', 'auth_res', 50000, '0000');
$r = booking_recon_claim($bk, $log, false);
ok(!$r['ok'], '5-1 취소된 예약은 거부', $r['msg']);
ok(rc_status($bk['bk_id'])['bk_status'] === 'cancelled', '5-2 상태를 건드리지 않는다');

// ── 뒷정리 ──────────────────────────────────────────────────────────────────

if ($made_bk) sql_query(" delete from `{$g5['booking_table']}` where bk_id in (".implode(',', $made_bk).") ", true);
if ($made_log) sql_query(" delete from `{$g5['booking_inicis_log_table']}` where bl_id in (".implode(',', $made_log).") ", true);
sql_query(" delete from `{$g5['booking_room_table']}` where br_id = '$test_br_id' ", true);

$left = sql_fetch(" select count(*) as c from `{$g5['booking_table']}` where bk_name = '__recontest__' ");
ok((int)$left['c'] === 0, '뒷정리: 테스트 예약이 남지 않았다');
$left = sql_fetch(" select count(*) as c from `{$g5['booking_inicis_log_table']}` where bl_oid like 'RCT%' ");
ok((int)$left['c'] === 0, '뒷정리: 테스트 로그가 남지 않았다');

echo $fail ? "recon_test: $fail FAIL\n" : "recon_test: OK\n";
exit($fail ? 1 : 0);
