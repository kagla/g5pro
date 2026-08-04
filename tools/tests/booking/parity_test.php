<?php
if (php_sapi_name() !== 'cli') die('CLI only');
$_SERVER['HTTP_HOST'] = 'localhost'; $_SERVER['REQUEST_URI'] = '/'; $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['SERVER_PORT'] = '80'; $_SERVER['SCRIPT_NAME'] = '/index.php';
// CLI php.ini 에 mysqli.default_socket 이 없어 소켓 경로를 직접 지정한다 (다른 booking 테스트와 동일)
if (file_exists('/run/mysqld/mysqld.sock')) ini_set('mysqli.default_socket', '/run/mysqld/mysqld.sock');
include_once __DIR__.'/../../../common.php';
include_once G5_LIB_PATH.'/booking.lib.php';

// 예약 폼(template/standard/booking/reserve.blade.php)이 화면에서 보여 주는 금액과
// 서버가 실제로 청구하는 금액(booking_calc_price)이 같은지 대조한다.
//
// 식을 이 파일에 손으로 옮겨 적으면 뷰만 고쳐도 테스트가 계속 통과해 버린다. 그래서
// 뷰 파일에서 comma()·recalc() 를 통째로 떼어 내 node 로 실제 실행한다.
// 뷰의 함수 이름·구조가 바뀌어 떼어 내지 못하면 그것도 FAIL 이다 — 가드가 조용히 죽지 않게.

$view = G5_PATH.'/template/standard/booking/reserve.blade.php';
$fail = 0;

function chk($cond, $msg)
{
    global $fail;
    if (!$cond) { echo "FAIL: $msg\n"; $fail++; }
}

// node 가 없는 환경(운영 서버 등)에서는 이 대조만 건너뛴다. 스위트 전체를 깨지는 않는다
$node = trim((string)@shell_exec('command -v node 2>/dev/null'));
if ($node === '') { echo "parity_test: SKIP (node 없음)\n"; exit(0); }

// ── 뷰에서 함수 원문을 떼어 낸다 (중괄호 균형으로 끝을 찾는다)
function parity_extract_fn($src, $name)
{
    $pos = strpos($src, 'function '.$name.'(');
    if ($pos === false) return null;
    $open = strpos($src, '{', $pos);
    if ($open === false) return null;
    $depth = 0; $len = strlen($src);
    for ($i = $open; $i < $len; $i++) {
        if ($src[$i] === '{') { $depth++; continue; }
        if ($src[$i] !== '}') continue;
        $depth--;
        if ($depth === 0) return array(
            'full' => substr($src, $pos, $i - $pos + 1),
            'body' => substr($src, $open + 1, $i - $open - 1),
        );
    }
    return null;
}

if (!is_file($view)) { echo "FAIL: 예약 폼 뷰가 없다 ($view)\n"; echo "parity_test: 1 FAIL\n"; exit(1); }
$src = file_get_contents($view);

$comma  = parity_extract_fn($src, 'comma');
$recalc = parity_extract_fn($src, 'recalc');
chk($comma !== null,  '뷰에서 comma() 를 떼어 내지 못했다 — 뷰 구조가 바뀌었으면 이 테스트도 함께 고쳐야 한다');
chk($recalc !== null, '뷰에서 recalc() 를 떼어 내지 못했다 — 뷰 구조가 바뀌었으면 이 테스트도 함께 고쳐야 한다');

// 떼어 낸 코드가 기대한 값들을 실제로 쓰는지 본다. 이름이 바뀌면 스텁이 헛돌아
// 0 원끼리 비교하며 통과할 수 있다
if ($recalc) {
    foreach (array('nights', 'roomPrice', 'basePerson', 'personPrice',
                   '#bk-person', '.bk-addon-qty', 'data-price',
                   '#bk-p-person', '#bk-p-addon', '#bk-p-total') as $needle) {
        chk(strpos($recalc['body'], $needle) !== false, "recalc() 가 '$needle' 를 더 이상 쓰지 않는다");
    }
}
// 뷰가 계산에 필요한 값을 HTML 로 실제로 내보내는지 (JS 만 맞고 값이 안 나가면 소용없다)
foreach (array('data-nights=', 'data-room-price=', 'data-base-person=', 'data-person-price=', 'data-price=') as $attr) {
    chk(strpos($src, $attr) !== false, "뷰가 $attr 속성을 내보내지 않는다");
}
if ($fail) { echo "parity_test: $fail FAIL\n"; exit(1); }

// ── 테스트 데이터. lib_test 와 같은 방식으로 만들고 지운다.
// br_use=0 으로 두어 운영 화면(booking/index.php 는 br_use=1 만 본다)에 뜨지 않게 한다
booking_install();
$test_subject = '__parity_test_room__';
$test_addon   = '__parity_test_addon__';

function parity_test_cleanup()
{
    global $g5, $test_subject, $test_addon;
    sql_query(" delete from `{$g5['booking_addon_table']}`
        where ba_subject = '".sql_real_escape_string($test_addon)."' ", false);
    sql_query(" delete from `{$g5['booking_room_table']}`
        where br_subject = '".sql_real_escape_string($test_subject)."' ", false);
}
parity_test_cleanup();   // 이전 실패 실행이 남긴 행까지 지운다
register_shutdown_function('parity_test_cleanup');

// 주중 10만 / 주말 15만, 기준 2명 최대 4명 인원추가 2만
sql_query(" insert into `{$g5['booking_room_table']}` set
    br_subject = '".sql_real_escape_string($test_subject)."', br_content = '',
    br_base_person = 2, br_max_person = 4, br_person_price = 20000, br_room_count = 2,
    br_weekday_price = 100000, br_weekend_price = 150000, br_use = 0, br_order = 0,
    br_datetime = '".G5_TIME_YMDHIS."' ", true);
$br_id = sql_insert_id();
$room = sql_fetch(" select * from `{$g5['booking_room_table']}` where br_id = '$br_id' ");

sql_query(" insert into `{$g5['booking_addon_table']}` set
    ba_subject = '".sql_real_escape_string($test_addon)."',
    ba_price = 30000, ba_max_qty = 4, ba_use = 1, ba_order = 0 ", true);
$ba_id = sql_insert_id();
$ba_price = 30000;

// 요일이 아니라 요금 구간을 보므로 상대 날짜를 쓴다 (시간이 지나도 스위트가 안 깨진다)
$mon = strtotime('next monday', strtotime('+30 day', G5_SERVER_TIME));
$fri = strtotime('+4 day', $mon);
$spans = array(
    array(date('Y-m-d', $mon), date('Y-m-d', strtotime('+2 day', $mon))),  // 월→수: 주중 2박
    array(date('Y-m-d', $fri), date('Y-m-d', strtotime('+2 day', $fri))),  // 금→일: 주말 2박
    array(date('Y-m-d', $mon), date('Y-m-d', strtotime('+1 day', $mon))),  // 월→화: 주중 1박
);

// 화면이 JS 에 넘기는 값 = 폼의 data-* 속성. reserve.php 가 계산하는 방식 그대로 만든다
$cases = array(); $expect = array(); $labels = array();
foreach ($spans as $span) {
    list($checkin, $checkout) = $span;
    $nights = count(booking_nights($checkin, $checkout));
    $base = booking_calc_price($room, $checkin, $checkout, (int)$room['br_base_person'], array());
    for ($person = 1; $person <= (int)$room['br_max_person']; $person++) {
        foreach (array(0, 1, (int)4) as $qty) {
            $p = booking_calc_price($room, $checkin, $checkout, $person, array($ba_id => $qty));
            $cases[] = array(
                'nights' => $nights, 'room_price' => (int)$base['room'],
                'base_person' => (int)$room['br_base_person'],
                'person_price' => (int)$room['br_person_price'],
                'person' => $person, 'addons' => array(array($ba_price, $qty)),
            );
            $expect[] = array(
                'person' => number_format($p['person']).'원',
                'addon'  => number_format($p['addon']).'원',
                'total'  => number_format($p['total']),
            );
            $labels[] = "$checkin~$checkout {$person}명 부가{$qty}개";
        }
    }
}

// ── node 러너. 뷰에서 떼어 낸 코드를 그대로 붙이고, recalc() 가 만지는 DOM 만 흉내낸다
$runner = <<<'JS'
const fs = require('fs');
const payload = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));
const out = [];
for (const c of payload.cases) {
    const nights = c.nights, roomPrice = c.room_price;
    const basePerson = c.base_person, personPrice = c.person_price;
    const texts = {};
    // jQuery 스텁 — recalc() 가 쓰는 것만. 셀렉터 문자열이거나, .each 안의 this 다
    const $ = (t) => {
        if (t && t.__addon) return { attr: () => String(t.__addon[0]), val: () => String(t.__addon[1]) };
        if (t === '#bk-person') return { val: () => String(c.person) };
        if (t === '.bk-addon-qty') return { each: (fn) => c.addons.forEach((a) => fn.call({ __addon: a })) };
        return { text: (v) => { texts[t] = v; } };
    };
    const fn = new Function('$', 'nights', 'roomPrice', 'basePerson', 'personPrice',
        payload.comma + '\n' + payload.recalc);
    fn($, nights, roomPrice, basePerson, personPrice);
    out.push({ person: texts['#bk-p-person'], addon: texts['#bk-p-addon'], total: texts['#bk-p-total'] });
}
process.stdout.write(JSON.stringify(out));
JS;

$tmp = sys_get_temp_dir().'/g5pro_parity_'.getmypid();
@mkdir($tmp, 0777, true);
if (!is_dir($tmp) || !is_writable($tmp)) { echo "FAIL: 임시 디렉터리를 못 만든다 ($tmp)\n"; echo "parity_test: 1 FAIL\n"; exit(1); }
$js_file   = $tmp.'/runner.js';
$json_file = $tmp.'/cases.json';
file_put_contents($js_file, $runner);
file_put_contents($json_file, json_encode(array(
    'cases' => $cases, 'comma' => $comma['full'], 'recalc' => $recalc['body'],
)));

$raw = shell_exec(escapeshellarg($node).' '.escapeshellarg($js_file).' '.escapeshellarg($json_file).' 2>&1');
@unlink($js_file); @unlink($json_file); @rmdir($tmp);

$got = json_decode((string)$raw, true);
if (!is_array($got) || count($got) !== count($expect)) {
    echo "FAIL: node 실행 결과를 못 받았다 — ".trim((string)$raw)."\n";
    echo "parity_test: 1 FAIL\n"; exit(1);
}

foreach ($expect as $i => $e) {
    if ($got[$i] === $e) continue;
    echo "FAIL: {$labels[$i]} — 서버=".json_encode($e, JSON_UNESCAPED_UNICODE)
        ." 화면=".json_encode($got[$i], JSON_UNESCAPED_UNICODE)."\n";
    $fail++;
}

parity_test_cleanup();
echo $fail ? "parity_test: $fail FAIL\n" : "parity_test: OK (".count($expect)." 케이스)\n";
exit($fail ? 1 : 0);
