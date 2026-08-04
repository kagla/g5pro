<?php
// 동시성 테스트 — 실수 1짜리 객실에 같은 날짜로 hold 8건을 "진짜 동시에" 던진다.
//
// 한 프로세스 안에서 순서대로 부르면 잠금이 잡히든 말든 늘 통과하므로 아무것도 증명하지 못한다.
// 그래서 워커 PHP 프로세스 8개를 백그라운드로 띄우고(각자 자기 DB 커넥션), 모두 준비된 뒤에
// 시작 신호 파일을 만들어 동시에 출발시킨다. 결과는 파일로 거둔다.
//
// 지키는 규칙: booking_create_hold() 는 객실 행을 FOR UPDATE 로 잠가 같은 객실의 동시 hold 를
// 직렬화한다. 그러므로 실수 1 인 날짜에 8건이 동시에 들어와도 성공은 정확히 1건이어야 한다.
if (php_sapi_name() !== 'cli') die('CLI only');
$_SERVER['HTTP_HOST'] = 'localhost'; $_SERVER['REQUEST_URI'] = '/'; $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['SERVER_PORT'] = '80'; $_SERVER['SCRIPT_NAME'] = '/index.php';
// CLI php.ini 에 mysqli.default_socket 이 없어 소켓 경로를 직접 지정한다 (tools/seed_load_test.php 와 동일)
if (file_exists('/run/mysqld/mysqld.sock')) ini_set('mysqli.default_socket', '/run/mysqld/mysqld.sock');
include_once __DIR__.'/../../../common.php';
include_once G5_LIB_PATH.'/booking.lib.php';

define('CONC_WORKERS', 8);
$test_subject = '__concurrency_test_room__';

// ---------------------------------------------------------------------------
// 워커 모드 — 부모가 자기 자신을 --worker 로 다시 띄운다.
// 파일을 둘로 나누지 않는 이유: 워커와 단언이 같은 부트스트랩·같은 인자 규약을 쓰기 때문에
// 한 파일에 두면 둘이 어긋날 일이 없다.
// ---------------------------------------------------------------------------
if (isset($argv[1]) && $argv[1] === '--worker') {
    $br_id = (int)$argv[2]; $checkin = $argv[3]; $checkout = $argv[4];
    $dir = $argv[5]; $idx = (int)$argv[6];

    @touch($dir.'/ready.'.$idx);
    // 시작 신호를 기다린다. clearstatcache 를 매번 부르지 않으면 file_exists 결과가 캐시된다.
    $deadline = microtime(true) + 60;
    while (microtime(true) < $deadline) {
        clearstatcache(true, $dir.'/start');
        if (file_exists($dir.'/start')) break;
        usleep(200);
    }

    $guest = array('name' => 'W'.$idx, 'hp' => '010-0000-000'.$idx, 'email' => '',
        'request' => '', 'mb_id' => '', 'password' => '1234');
    $t0 = microtime(true);
    $r = booking_create_hold($br_id, $checkin, $checkout, 2, array(), $guest);
    $t1 = microtime(true);

    $out = array(
        'idx' => $idx,
        'ok' => !empty($r['ok']),
        'bk_id' => isset($r['bk_id']) ? (int)$r['bk_id'] : 0,
        'error' => isset($r['error']) ? $r['error'] : '',
        't0' => $t0, 't1' => $t1,
    );
    // 부모가 반쯤 쓰인 파일을 읽지 않도록 임시 파일에 쓰고 rename 으로 갈아 끼운다.
    // 임시 이름은 out.* 과 겹치지 않아야 한다 — 겹치면 부모의 glob 이 임시 파일까지 세어
    // 아직 안 끝난 라운드를 끝난 것으로 착각한다.
    file_put_contents($dir.'/wip.'.$idx, json_encode($out));
    rename($dir.'/wip.'.$idx, $dir.'/out.'.$idx);
    exit(0);
}

// ---------------------------------------------------------------------------
// 부모(테스트) 모드
// ---------------------------------------------------------------------------
booking_install();

$fail = 0;
function chk($cond, $msg)
{
    global $fail;
    if (!$cond) { echo "FAIL: $msg\n"; $fail++; }
}

// 테스트 데이터 정리 — 이전 실패 실행이 남긴 행까지 지운다
function conc_test_cleanup()
{
    global $g5, $test_subject;
    $sub = sql_real_escape_string($test_subject);
    $res = sql_query(" select br_id from `{$g5['booking_room_table']}` where br_subject = '$sub' ", false);
    if (!$res) return;
    while ($row = sql_fetch_array($res)) {
        $br_id = (int)$row['br_id'];
        $bk = sql_query(" select bk_id from `{$g5['booking_table']}` where br_id = '$br_id' ", false);
        while ($bk && $b = sql_fetch_array($bk)) {
            sql_query(" delete from `{$g5['booking_addon_item_table']}` where bk_id = '".(int)$b['bk_id']."' ", false);
        }
        sql_query(" delete from `{$g5['booking_table']}` where br_id = '$br_id' ", false);
        sql_query(" delete from `{$g5['booking_calendar_table']}` where br_id = '$br_id' ", false);
        sql_query(" delete from `{$g5['booking_room_table']}` where br_id = '$br_id' ", false);
    }
}

// 워커가 남긴 신호·결과 파일까지 지운다 — 다음 실행이 옛 start 파일을 보고 먼저 출발하면 안 된다
function conc_tmp_cleanup()
{
    global $conc_tmpdir;
    if (!$conc_tmpdir || !is_dir($conc_tmpdir)) return;
    foreach (glob($conc_tmpdir.'/*') as $sub) {
        if (is_dir($sub)) { foreach (glob($sub.'/*') as $f) @unlink($f); @rmdir($sub); }
        else @unlink($sub);
    }
    @rmdir($conc_tmpdir);
}

$conc_tmpdir = sys_get_temp_dir().'/booking_conc_'.getmypid();
if (!is_dir($conc_tmpdir)) mkdir($conc_tmpdir, 0700, true);

conc_test_cleanup();
register_shutdown_function('conc_test_cleanup');
register_shutdown_function('conc_tmp_cleanup');

// 실수 1짜리 테스트 객실. br_use=0 으로 만들어 운영 화면에 노출되지 않게 하고,
// hold 를 받는 동안에만 1 로 올린다 (lib_test.php 와 같은 방식).
sql_query(" insert into `{$g5['booking_room_table']}` set
    br_subject = '".sql_real_escape_string($test_subject)."', br_content = '',
    br_base_person = 2, br_max_person = 4, br_person_price = 20000, br_room_count = 1,
    br_weekday_price = 100000, br_weekend_price = 150000, br_use = 0, br_order = 0,
    br_datetime = '".G5_TIME_YMDHIS."' ", true);
$br_id = sql_insert_id();

// 요일이 아니라 재고 로직을 보므로 상대 날짜를 쓴다 (시간이 지나도 스위트가 안 깨진다)
$mon = strtotime('next monday', strtotime('+60 day', G5_SERVER_TIME));
$checkin  = date('Y-m-d', $mon);
$checkout = date('Y-m-d', strtotime('+1 day', $mon));

sql_query(" update `{$g5['booking_room_table']}` set br_use = 1 where br_id = '$br_id' ", true);

// 워커 8개를 띄우고 동시에 출발시킨 뒤 결과를 거둔다
function conc_round($round, $br_id, $checkin, $checkout)
{
    global $conc_tmpdir;
    $dir = $conc_tmpdir.'/r'.$round;
    if (!is_dir($dir)) mkdir($dir, 0700, true);

    for ($i = 0; $i < CONC_WORKERS; $i++) {
        $cmd = escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' --worker '
            .escapeshellarg((string)$br_id).' '.escapeshellarg($checkin).' '.escapeshellarg($checkout).' '
            .escapeshellarg($dir).' '.escapeshellarg((string)$i).' > /dev/null 2>&1 &';
        exec($cmd);
    }

    // 8개가 모두 부트스트랩을 마치고 신호를 기다리는 상태가 될 때까지 기다린다.
    // 이 단계를 건너뛰고 바로 신호를 주면 늦게 뜬 워커가 앞선 워커의 커밋 뒤에 출발해 동시성이 아니게 된다.
    $deadline = microtime(true) + 60;
    while (microtime(true) < $deadline && count(glob($dir.'/ready.*')) < CONC_WORKERS) usleep(2000);
    if (count(glob($dir.'/ready.*')) < CONC_WORKERS) return array('error' => '워커가 제 시간에 준비되지 않음');

    touch($dir.'/start');

    $deadline = microtime(true) + 120;
    while (microtime(true) < $deadline && count(glob($dir.'/out.*')) < CONC_WORKERS) usleep(2000);
    $files = glob($dir.'/out.*');
    if (count($files) < CONC_WORKERS) return array('error' => '워커 결과가 '.count($files).'개만 도착함');

    $results = array();
    foreach ($files as $f) {
        $r = json_decode(file_get_contents($f), true);
        if (!is_array($r)) return array('error' => '워커 결과를 읽지 못함: '.$f);
        $results[] = $r;
    }
    return array('results' => $results);
}

// ---- 1라운드: 실수 1 에 동시 hold 8건 ----
$r1 = conc_round(1, $br_id, $checkin, $checkout);
chk(empty($r1['error']), '1라운드 실행 실패: '.(isset($r1['error']) ? $r1['error'] : ''));

$success1 = 0; $closed1 = 0; $other1 = array();
$t0s = array(); $t1s = array(); $win_bk_id = 0;
if (empty($r1['error'])) {
    foreach ($r1['results'] as $r) {
        $t0s[] = $r['t0']; $t1s[] = $r['t1'];
        if ($r['ok']) { $success1++; $win_bk_id = (int)$r['bk_id']; }
        else if (strpos($r['error'], '마감') !== false) $closed1++;
        else $other1[] = $r['error'];
    }
    chk($success1 === 1, "동시 hold 8건 중 성공이 1건이 아님 (success=$success1)");
    chk($closed1 === CONC_WORKERS - 1,
        '나머지 '.(CONC_WORKERS - 1)."건이 모두 마감 오류가 아님 (마감=$closed1, 그 외=".implode(' / ', $other1).')');

    // 정말 동시에 출발했는지 — 출발 시각이 흩어져 있으면 위 단언은 아무것도 증명하지 못한다
    $spread = max($t0s) - min($t0s);
    chk($spread < 1.0, sprintf('워커 출발 시각이 %.3f초나 벌어졌다 — 동시 실행이 아님', $spread));
    // 첫 완료 시각보다 먼저 출발한 워커 수 = 실제로 임계 구역에서 겹친 워커 수
    $overlap = 0;
    foreach ($t0s as $t0) { if ($t0 < min($t1s)) $overlap++; }
    chk($overlap >= 2, "임계 구역에서 겹친 워커가 $overlap 개뿐 — 사실상 직렬 실행됨");
    printf("  round1: success=%d closed=%d 출발편차=%.4fs 겹침=%d/%d\n",
        $success1, $closed1, $spread, $overlap, CONC_WORKERS);

    // DB 에도 유효 hold 가 딱 1건이어야 한다 (워커 반환값만 믿지 않는다)
    $cnt = sql_fetch(" select count(*) as c from `{$g5['booking_table']}`
        where br_id = '$br_id' and bk_status = 'hold' ");
    chk((int)$cnt['c'] === 1, '1라운드 뒤 DB 의 hold 행이 1건이 아님 ('.(int)$cnt['c'].')');
    $room = sql_fetch(" select * from `{$g5['booking_room_table']}` where br_id = '$br_id' ");
    chk(booking_booked_count($br_id, $checkin) === 1, '해당 날짜의 예약 수가 1이 아님');
    chk(booking_remain_count($room, $checkin) === 0, '해당 날짜의 잔여 객실이 0이 아님');
}

// ---- 2라운드: 성공한 hold 를 만료시키고 다시 동시 8건 ----
// 만료된 hold 는 재고에서 빠지므로 자리가 하나 다시 열린다. 그 한 자리도 동시에 하나만 나가야 한다.
$success2 = 0; $closed2 = 0; $other2 = array();
if ($win_bk_id) {
    sql_query(" update `{$g5['booking_table']}` set bk_hold_expire = '2000-01-01 00:00:00'
        where bk_id = '$win_bk_id' ", true);
    chk(booking_booked_count($br_id, $checkin) === 0, '만료 처리 뒤에도 예약 수가 0이 아님');

    $r2 = conc_round(2, $br_id, $checkin, $checkout);
    chk(empty($r2['error']), '2라운드 실행 실패: '.(isset($r2['error']) ? $r2['error'] : ''));
    if (empty($r2['error'])) {
        $t0s2 = array();
        foreach ($r2['results'] as $r) {
            $t0s2[] = $r['t0'];
            if ($r['ok']) $success2++;
            else if (strpos($r['error'], '마감') !== false) $closed2++;
            else $other2[] = $r['error'];
        }
        chk($success2 === 1, "만료 자리 재시도에서 성공이 1건이 아님 (success=$success2)");
        chk($closed2 === CONC_WORKERS - 1,
            "만료 자리 재시도의 나머지가 모두 마감 오류가 아님 (마감=$closed2, 그 외=".implode(' / ', $other2).')');
        $spread2 = max($t0s2) - min($t0s2);
        chk($spread2 < 1.0, sprintf('2라운드 워커 출발 시각이 %.3f초나 벌어졌다 — 동시 실행이 아님', $spread2));
        printf("  round2: success=%d closed=%d 출발편차=%.4fs\n", $success2, $closed2, $spread2);

        $cnt = sql_fetch(" select count(*) as c from `{$g5['booking_table']}`
            where br_id = '$br_id' and bk_status = 'hold' and bk_hold_expire > '".G5_TIME_YMDHIS."' ");
        chk((int)$cnt['c'] === 1, '2라운드 뒤 유효 hold 행이 1건이 아님 ('.(int)$cnt['c'].')');
    }
}

// ---- 없는(또는 미사용) 객실에 동시 요청 — 전부 실패해야 한다 ----
sql_query(" update `{$g5['booking_room_table']}` set br_use = 0 where br_id = '$br_id' ", true);
$r3 = conc_round(3, $br_id, $checkin, $checkout);
chk(empty($r3['error']), '3라운드 실행 실패: '.(isset($r3['error']) ? $r3['error'] : ''));
if (empty($r3['error'])) {
    $success3 = 0;
    foreach ($r3['results'] as $r) { if ($r['ok']) $success3++; }
    chk($success3 === 0, "미사용(br_use=0) 객실인데 hold 가 $success3 건 성공함");
}

conc_test_cleanup();
conc_tmp_cleanup();
echo $fail ? "concurrency_test: $fail FAIL\n" : "concurrency_test: OK (success=$success1/".CONC_WORKERS.")\n";
exit($fail ? 1 : 0);
