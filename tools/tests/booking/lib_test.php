<?php
if (php_sapi_name() !== 'cli') die('CLI only');
$_SERVER['HTTP_HOST'] = 'localhost'; $_SERVER['REQUEST_URI'] = '/'; $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['SERVER_PORT'] = '80'; $_SERVER['SCRIPT_NAME'] = '/index.php';
// CLI php.ini 에 mysqli.default_socket 이 없어 소켓 경로를 직접 지정한다 (tools/seed_load_test.php 와 동일)
if (file_exists('/run/mysqld/mysqld.sock')) ini_set('mysqli.default_socket', '/run/mysqld/mysqld.sock');
include_once __DIR__.'/../../../common.php';
include_once G5_LIB_PATH.'/booking.lib.php';

booking_install();

$fail = 0;
$test_subject = '__lib_test_room__';
$test_addon = '__lib_test_addon__';

function chk($cond, $msg)
{
    global $fail;
    if (!$cond) { echo "FAIL: $msg\n"; $fail++; }
}

// 테스트 데이터 정리 — 이전 실패 실행이 남긴 행까지 지운다
function lib_test_cleanup()
{
    global $g5, $test_subject, $test_addon;
    // 매핑은 상품 행이 살아 있는 동안 조인으로 지운다 (상품을 먼저 지우면 고아 행이 남는다)
    sql_query(" delete m from `{$g5['booking_room_addon_table']}` m
        inner join `{$g5['booking_addon_table']}` a on a.ba_id = m.ba_id
        where a.ba_subject = '".sql_real_escape_string($test_addon)."' ", false);
    sql_query(" delete from `{$g5['booking_addon_table']}`
        where ba_subject = '".sql_real_escape_string($test_addon)."' ", false);
    $sub = sql_real_escape_string($test_subject);
    $res = sql_query(" select br_id from `{$g5['booking_room_table']}` where br_subject = '$sub' ", false);
    if (!$res) return;
    while ($row = sql_fetch_array($res)) {
        $br_id = (int)$row['br_id'];
        $bk = sql_query(" select bk_id from `{$g5['booking_table']}` where br_id = '$br_id' ", false);
        while ($bk && $b = sql_fetch_array($bk)) {
            $bk_id = (int)$b['bk_id'];
            sql_query(" delete from `{$g5['booking_addon_item_table']}` where bk_id = '$bk_id' ", false);
        }
        sql_query(" delete from `{$g5['booking_table']}` where br_id = '$br_id' ", false);
        sql_query(" delete from `{$g5['booking_calendar_table']}` where br_id = '$br_id' ", false);
        sql_query(" delete from `{$g5['booking_room_addon_table']}` where br_id = '$br_id' ", false);
        sql_query(" delete from `{$g5['booking_room_table']}` where br_id = '$br_id' ", false);
    }
}

lib_test_cleanup();
register_shutdown_function('lib_test_cleanup');

// 테스트 객실: 주중 10만 / 주말 15만, 기준 2명 최대 4명 인원추가 2만, 판매 실수 2
// br_use=0 으로 만들어 운영 화면에 노출되지 않게 한다 (hold 구간에서만 잠시 1로 올린다)
sql_query(" insert into `{$g5['booking_room_table']}` set
    br_subject = '".sql_real_escape_string($test_subject)."', br_content = '',
    br_base_person = 2, br_max_person = 4, br_person_price = 20000, br_room_count = 2,
    br_weekday_price = 100000, br_weekend_price = 150000, br_use = 0, br_order = 0,
    br_datetime = '".G5_TIME_YMDHIS."' ", true);
$br_id = sql_insert_id();
$room = sql_fetch(" select * from `{$g5['booking_room_table']}` where br_id = '$br_id' ");

// 검증·hold 구간은 요일이 아니라 재고 로직을 보므로 상대 날짜를 쓴다 (시간이 지나도 스위트가 안 깨진다).
// 30일 뒤 이후 첫 월요일 기준이라 숙박일이 항상 월·화 밤 = 주중요금 10만으로 고정된다.
$mon   = strtotime('next monday', strtotime('+30 day', G5_SERVER_TIME));
$s_in  = date('Y-m-d', $mon);                        // 월
$s_out = date('Y-m-d', strtotime('+2 day', $mon));   // 수 → 월·화 2박
$a_in  = date('Y-m-d', strtotime('+7 day', $mon));   // 다음 주 월
$a_out = date('Y-m-d', strtotime('+8 day', $mon));   // 화 → 1박

// ---- booking_nights: 체크아웃일은 제외 ----
chk(booking_nights('2026-09-04', '2026-09-06') === array('2026-09-04', '2026-09-05'),
    "booking_nights 결과 불일치: ".var_export(booking_nights('2026-09-04','2026-09-06'), true));

// ---- booking_night_price: 금·토 밤은 주말요금 ----
chk(booking_night_price($room, '2026-09-04') === 150000, '금요일(2026-09-04) 밤이 주말요금 150000 아님');
chk(booking_night_price($room, '2026-09-05') === 150000, '토요일(2026-09-05) 밤이 주말요금 150000 아님');
chk(booking_night_price($room, '2026-09-06') === 100000, '일요일(2026-09-06) 밤이 주중요금 100000 아님');
chk(booking_night_price($room, '2026-09-07') === 100000, '월요일(2026-09-07) 밤이 주중요금 100000 아님');

// 캘린더 개별요금이 있으면 그 값이 우선
sql_query(" insert into `{$g5['booking_calendar_table']}` set br_id = '$br_id',
    bd_date = '2026-09-10', bd_price = 250000 ", true);
chk(booking_night_price($room, '2026-09-10') === 250000, '캘린더 개별요금 250000 이 적용되지 않음');
chk(booking_calendar_row($br_id, '2026-09-10') !== null, 'booking_calendar_row 가 있는 행을 못 찾음');
chk(booking_calendar_row($br_id, '2026-09-13') === null, 'booking_calendar_row 가 없는 날짜에 null 을 반환하지 않음');

// ---- booking_sellable_count: 캘린더 판매 실수가 있으면 그 값, 없으면 객실 기본값 ----
sql_query(" insert into `{$g5['booking_calendar_table']}` set br_id = '$br_id',
    bd_date = '2026-09-11', bd_room_count = 0 ", true);
chk(booking_sellable_count($room, '2026-09-11') === 0, '캘린더 bd_room_count=0 인데 판매 실수가 0 이 아님');
chk(booking_sellable_count($room, '2026-09-12') === 2, '캘린더 행이 없는데 판매 실수가 객실 기본값 2 가 아님');

// ---- booking_refund_rate ----
$policy = "7:100\n3:50\n1:30\n0:0";
chk(booking_refund_rate($policy, 10) === 100, '환불율(10일 전)이 100 이 아님');
chk(booking_refund_rate($policy, 3) === 50, '환불율(3일 전)이 50 이 아님');
chk(booking_refund_rate($policy, 2) === 30, '환불율(2일 전)이 30 이 아님');
chk(booking_refund_rate($policy, 0) === 0, '환불율(당일)이 0 이 아님');
chk(booking_refund_rate('', 10) === 0, '빈 정책 문자열의 환불율이 0 이 아님');

// ---- booking_calc_price: 2박 주중 + 인원추가 1명 ----
$price = booking_calc_price($room, '2026-09-07', '2026-09-09', 3, array());
chk($price['room'] === 200000, "객실료가 200000 이 아님 (".var_export($price['room'], true).")");
chk($price['person'] === 40000, "인원추가 요금이 40000 이 아님 (".var_export($price['person'], true).")");
chk($price['addon'] === 0, '부가상품 금액이 0 이 아님');
chk($price['total'] === 240000, "합계가 240000 이 아님 (".var_export($price['total'], true).")");
chk(is_array($price['addon_items']) && count($price['addon_items']) === 0, '부가상품 목록이 빈 배열이 아님');

// ---- booking_validate_stay ----
chk(booking_validate_stay($room, $s_in, $s_out, 3) === '', '정상 예약인데 검증 오류가 났음');
chk(booking_validate_stay($room, '2020-01-01', '2020-01-02', 2) !== '', '지난 날짜인데 검증을 통과했음');
chk(booking_validate_stay($room, $s_in, $s_out, 9) !== '', '최대 인원 초과인데 검증을 통과했음');
chk(booking_validate_stay($room, str_replace('-', '/', $s_in), $s_out, 2) !== '', '날짜 형식 오류인데 검증을 통과했음');

// ---- booking_create_hold: 판매 실수 2 를 넘으면 마감 ----
sql_query(" update `{$g5['booking_room_table']}` set br_use = 1 where br_id = '$br_id' ", true);
$guest = array('name' => '홍길동', 'hp' => '010-0000-0000', 'email' => 'lib_test@example.com',
    'request' => '', 'mb_id' => '', 'password' => '1234');

$h1 = booking_create_hold($br_id, $s_in, $s_out, 3, array(), $guest);
chk(!empty($h1['ok']), '첫 번째 hold 가 실패함 ('.(isset($h1['error']) ? $h1['error'] : '').')');
$h2 = booking_create_hold($br_id, $s_in, $s_out, 2, array(), $guest);
chk(!empty($h2['ok']), '두 번째 hold 가 실패함 ('.(isset($h2['error']) ? $h2['error'] : '').')');
$h3 = booking_create_hold($br_id, $s_in, $s_out, 2, array(), $guest);
chk(isset($h3['ok']) && $h3['ok'] === false, '판매 실수 2 를 넘은 세 번째 hold 가 성공함');
chk(!empty($h3['error']) && strpos($h3['error'], '마감') !== false,
    "세 번째 hold 오류가 마감 메시지가 아님 (".(isset($h3['error']) ? $h3['error'] : '').")");

if (!empty($h1['ok'])) {
    $bk = booking_get($h1['bk_id']);
    chk($bk && $bk['bk_status'] === 'hold', "첫 hold 의 상태가 'hold' 가 아님");
    chk($bk && (int)$bk['bk_total_price'] === 240000, '첫 hold 의 합계 금액이 240000 이 아님');
    chk($bk && $bk['bk_no'] === $h1['bk_no'], 'INSERT 시점에 bk_no 가 채워지지 않음');
    chk(booking_get_by_no($h1['bk_no']) !== null, 'booking_get_by_no 가 예약을 못 찾음');
    sql_query(" update `{$g5['booking_table']}` set bk_oid = 'LIBTESTOID1' where bk_id = '".(int)$h1['bk_id']."' ", true);
    $by_oid = booking_get_by_oid('LIBTESTOID1');
    chk($by_oid && (int)$by_oid['bk_id'] === (int)$h1['bk_id'], 'booking_get_by_oid 가 예약을 못 찾음');
}
chk(booking_get(0) === null, '없는 예약에 booking_get 이 null 을 반환하지 않음');

chk(booking_booked_count($br_id, $s_in) === 2, '유효 hold 2건이 예약 수로 집계되지 않음');
chk(booking_remain_count($room, $s_in) === 0, '잔여 객실이 0 이 아님');
chk(booking_booked_count($br_id, $s_out) === 0, '체크아웃 당일이 예약 수에 잡힘');

// 만료된 hold 는 재고에서 빠진다
if (!empty($h2['ok'])) {
    sql_query(" update `{$g5['booking_table']}` set bk_hold_expire = '2000-01-01 00:00:00'
        where bk_id = '".(int)$h2['bk_id']."' ", true);
    chk(booking_booked_count($br_id, $s_in) === 1, '만료된 hold 가 예약 수에서 빠지지 않음');
    $h4 = booking_create_hold($br_id, $s_in, $s_out, 2, array(), $guest);
    chk(!empty($h4['ok']), '만료 hold 자리에 다시 hold 하지 못함 ('.(isset($h4['error']) ? $h4['error'] : '').')');
}

// ---- 부가상품: 최대 수량으로 잘리고 hold 가 항목을 저장한다 ----
sql_query(" insert into `{$g5['booking_addon_table']}` set
    ba_subject = '".sql_real_escape_string($test_addon)."', ba_price = 30000,
    ba_max_qty = 2, ba_use = 1, ba_order = 0 ", true);
$ba_id = sql_insert_id();

// 객실에 담기 전에는 요금 계산이 상품을 무시해야 한다 (매핑이 최종 방어선)
$unmapped = booking_calc_price($room, $a_in, $a_out, 2, array($ba_id => 1));
chk($unmapped['addon'] === 0, '객실에 안 담긴 부가상품이 요금에 들어감');

sql_query(" insert into `{$g5['booking_room_addon_table']}` set
    br_id = '$br_id', ba_id = '$ba_id', bra_order = 0 ", true);
$ap = booking_calc_price($room, $a_in, $a_out, 2, array($ba_id => 5));
chk(count($ap['addon_items']) === 1, '부가상품 항목이 1건이 아님');
chk($ap['addon_items'][0]['qty'] === 2, '부가상품 수량이 최대 2 로 잘리지 않음');
chk($ap['addon'] === 60000, "부가상품 금액이 60000 이 아님 (".var_export($ap['addon'], true).")");
chk($ap['total'] === 160000, "부가상품 포함 합계가 160000 이 아님 (".var_export($ap['total'], true).")");
chk($ap['addon_items'][0]['unit'] === 'once', '1회 상품의 unit 이 once 가 아님');

// ---- 1박당 상품: 수량에 박수가 곱해진다 (30000원 × 2개 × 2박) ----
sql_query(" update `{$g5['booking_addon_table']}` set ba_unit = 'night' where ba_id = '$ba_id' ", true);
$np = booking_calc_price($room, $s_in, $s_out, 2, array($ba_id => 2));
chk($np['addon'] === 120000, "1박당 상품 금액이 120000 이 아님 (".var_export($np['addon'], true).")");
chk($np['addon_items'][0]['unit'] === 'night', '1박당 상품의 unit 이 night 가 아님');
sql_query(" update `{$g5['booking_addon_table']}` set ba_unit = 'once' where ba_id = '$ba_id' ", true);

$h5 = booking_create_hold($br_id, $a_in, $a_out, 2, array($ba_id => 5), $guest);
chk(!empty($h5['ok']), '부가상품 hold 가 실패함 ('.(isset($h5['error']) ? $h5['error'] : '').')');
if (!empty($h5['ok'])) {
    $cnt = sql_fetch(" select count(*) as c from `{$g5['booking_addon_item_table']}`
        where bk_id = '".(int)$h5['bk_id']."' ");
    chk((int)$cnt['c'] === 1, 'hold 가 부가상품 항목을 저장하지 않음');
    $it = sql_fetch(" select bt_unit from `{$g5['booking_addon_item_table']}`
        where bk_id = '".(int)$h5['bk_id']."' ");
    chk($it && $it['bt_unit'] === 'once', 'hold 스냅샷에 과금 단위가 저장되지 않음');
}

// ---- booking_new_no ----
$no1 = booking_new_no();
$no2 = booking_new_no();
chk(preg_match('/^[A-Z0-9]{10}$/', $no1) === 1, "예약번호 형식이 대문자 영숫자 10자가 아님 ($no1)");
chk($no1 !== $no2, '연속 호출한 예약번호가 같음');

// ---- booking_send_mail 스모크: mailer.lib.php include 경로를 태운다 ----
// common.php 는 mailer.lib.php 를 로드하지 않으므로, include 가 빠지면 여기서 Error 로 죽는다.
// 수신자(bk_email·bc_admin_email)를 비워 실제 발송은 일어나지 않게 한다.
chk(!function_exists('mailer'), '테스트 시작 시점에 mailer() 가 이미 정의돼 있어 include 단언이 무의미함');
booking_send_mail(0, 'confirm'); // 없는 예약 — include 후 즉시 return
chk(function_exists('mailer'), 'booking_send_mail 이 mailer.lib.php 를 include 하지 않음 (호출 시 fatal 위험)');
$bconfig = booking_config();
if (!empty($h1['ok']) && (string)$bconfig['bc_admin_email'] === '') {
    sql_query(" update `{$g5['booking_table']}` set bk_email = ''
        where bk_id = '".(int)$h1['bk_id']."' ", true);
    booking_send_mail($h1['bk_id'], 'confirm'); // 수신자 없음 → 본문만 만들고 발송 없음
}
$mail_ok = true; // 위에서 fatal 이 났다면 이 줄에 도달하지 못한다
chk($mail_ok, 'booking_send_mail 이 fatal 없이 끝나지 않음');

// ---- booking_config ----
$config = booking_config();
chk(is_array($config) && (int)$config['bc_id'] === 1, 'booking_config 가 설정 행을 반환하지 않음');
chk(function_exists('booking_send_mail'), 'booking_send_mail 이 정의되지 않음');

lib_test_cleanup();
echo $fail ? "lib_test: $fail FAIL\n" : "lib_test: OK\n";
exit($fail ? 1 : 0);
