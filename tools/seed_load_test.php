<?php
/**
 * 부하 테스트용 시드 스크립트 (CLI 전용)
 *
 * 회원 10명(lt_user01~10, 비번 lt1234!@#) · 상품 100개(LT0001~LT0100)
 * · 상품당 주문 1,000건 = g5_shop_order/g5_shop_cart 각 100,000행을 넣는다.
 *
 * 상태 비중: 주문 40% · 입금 30% · 준비 20% · 배송 5% · 완료 5%
 * 주문·입금·준비는 ct_stock_use=0 으로 두어 재고 대기수량 SUM 쿼리의
 * 대상이 되게 한다 (lib/shop.lib.php get_it_stock_qty 부하 재현).
 *
 * 핫 아이템(슬로우 쿼리 재현):
 *  - LT0001·LT0002: 대기 주문 각 120만 건 추가 → get_it_stock_qty
 *    단일 쿼리가 1초를 넘겨 슬로우 로그(long_query_time=1)에 잡힌다.
 *  - LT0003: 옵션 20개를 만들고 대기 주문 100만 건을 옵션별 5만 건씩
 *    분산 → 옵션 쿼리 하나는 수십 ms지만 상세 페이지(itemoption.php)가
 *    옵션 수만큼 반복 호출해 페이지 단위로 초 단위가 된다.
 *
 * 재실행하면 기존 시드(lt_user%, LT%, od_id 9로 시작)를 지우고 다시 넣는다.
 *
 * 실행: php tools/seed_load_test.php
 */

if (php_sapi_name() !== 'cli') exit('CLI 전용입니다.');

define('_GNUBOARD_', true);

$root = dirname(__DIR__);
include $root.'/data/dbconfig.php';
include $root.'/lib/pbkdf2.compat.php'; // create_hash()

// CLI php.ini 에 mysqli.default_socket 이 없어 소켓 경로를 직접 지정한다
$socket = file_exists('/run/mysqld/mysqld.sock') ? '/run/mysqld/mysqld.sock' : null;
$db = new mysqli(G5_MYSQL_HOST, G5_MYSQL_USER, G5_MYSQL_PASSWORD, G5_MYSQL_DB, 3306, $socket);
if ($db->connect_errno) exit('DB 접속 실패: '.$db->connect_error."\n");
$db->set_charset('utf8mb4');

const MEMBER_CNT   = 10;
const ITEM_CNT     = 100;
const ORDER_PER_IT = 1000;
const HOT_PLAIN_CNT  = 1200000; // LT0001·LT0002 대기 주문 수
const HOT_OPTION_CNT = 1000000; // LT0003 대기 주문 수 (옵션 20개에 분산)
const OPTION_CNT     = 20;
const OD_ID_BASE   = 9000000000000000; // 실주문 od_id(20YYMMDD…)와 겹치지 않는 대역
const BASE_DAY     = '2026-08-03';     // 이 날짜로부터 과거 90일에 주문을 분산

$t0 = microtime(true);

/* ---- 기존 시드 제거 (재실행 대비) ---- */
$db->query("DELETE FROM g5_shop_cart  WHERE od_id >= ".OD_ID_BASE);
$db->query("DELETE FROM g5_shop_order WHERE od_id >= ".OD_ID_BASE);
$db->query("DELETE FROM g5_shop_item_option WHERE it_id LIKE 'LT%'");
$db->query("DELETE FROM g5_shop_item  WHERE it_id LIKE 'LT%'");
$db->query("DELETE FROM g5_member     WHERE mb_id LIKE 'lt\_user%'");
echo "기존 시드 정리 완료\n";

/* ---- 회원 10명 ---- */
$hash = $db->real_escape_string(create_hash('lt1234!@#'));
$rows = array();
for ($m = 1; $m <= MEMBER_CNT; $m++) {
    $id = sprintf('lt_user%02d', $m);
    $rows[] = "('$id', '$hash', '부하테스터$m', '부하테스터$m', '$id@test.local', 2,"
            . " '', '', '', '', '', now(), now(), '".BASE_DAY."', '127.0.0.1')";
}
$db->query("INSERT INTO g5_member
    (mb_id, mb_password, mb_name, mb_nick, mb_email, mb_level,
     mb_signature, mb_memo, mb_lost_certify, mb_profile, mb_agree_log,
     mb_datetime, mb_nick_date, mb_today_login, mb_ip)
    VALUES ".implode(',', $rows)) or exit('회원 INSERT 실패: '.$db->error."\n");
echo "회원 ".MEMBER_CNT."명 등록\n";

/* ---- 상품 100개 ---- */
$rows = array();
for ($i = 1; $i <= ITEM_CNT; $i++) {
    $it_id = sprintf('LT%04d', $i);
    $ca_id = ($i % 2) ? '10' : '20';
    $price = (($i * 137) % 990 + 10) * 100; // 1,000 ~ 99,900원
    $rows[] = "('$it_id', '$ca_id', '부하테스트 상품 $i', $price, 1, 9999999,"
            . " '', '', '', '', '', '', '', '', '', '', 0.0, now(), '127.0.0.1')";
}
$db->query("INSERT INTO g5_shop_item
    (it_id, ca_id, it_name, it_price, it_use, it_stock_qty,
     it_basic, it_explan, it_explan2, it_mobile_explan,
     it_head_html, it_tail_html, it_mobile_head_html, it_mobile_tail_html,
     it_info_value, it_shop_memo, it_use_avg, it_time, it_ip)
    VALUES ".implode(',', $rows)) or exit('상품 INSERT 실패: '.$db->error."\n");
echo "상품 ".ITEM_CNT."개 등록\n";

/* ---- 주문 행 생성 헬퍼 ---- */
$it_price = function($i) { return (($i * 137) % 990 + 10) * 100; };

// (od행, ct행) 문자열 한 쌍을 만든다
$make_rows = function($seq, $it_id, $it_name, $price, $status, $io_id = '', $ct_option = '') {
    $od_id  = OD_ID_BASE + $seq;
    $m      = $seq % MEMBER_CNT + 1;
    $mb_id  = sprintf('lt_user%02d', $m);
    $name   = "부하테스터$m";
    $qty    = $seq % 3 + 1;
    $amount = $price * $qty;
    $send   = ($amount >= 50000) ? 0 : 2500;
    $stock_use = in_array($status, array('주문', '입금', '준비')) ? 0 : 1;

    $time = sprintf("date_sub('".BASE_DAY." 00:00:00', interval %d day) + interval %d second",
                    $seq % 90, $seq % 86400);
    // 주문(미입금)만 미수, 나머지는 결제 완료로 처리
    if ($status === '주문') {
        $receipt = 0; $misu = $amount + $send; $receipt_time = 'NULL';
    } else {
        $receipt = $amount + $send; $misu = 0; $receipt_time = $time;
    }
    $hp = sprintf("010-0000-%04d", $m);

    $od = "($od_id, '$mb_id', '$name', '$mb_id@test.local', '', '$hp',"
        . " '060', '00', '서울시 강남구 테스트로 $m', '{$m}층', "
        . " '$name', '$hp', '서울시 강남구 테스트로 $m', '{$m}층',"
        . " '', 1, $amount, $send, $receipt, $receipt_time, $misu,"
        . " '', '', '$status', '무통장', '0', 0, '', '', $time, '', '127.0.0.1')";
    $ct = "($od_id, '$mb_id', '$it_id', '$it_name', '$status', '',"
        . " $price, $stock_use, '$ct_option', $qty, '$io_id', $time, '127.0.0.1', 1, $time)";
    return array($od, $ct);
};

$flush = function(array $odRows, array $ctRows) use ($db) {
    $db->query("INSERT INTO g5_shop_order
        (od_id, mb_id, od_name, od_email, od_tel, od_hp,
         od_zip1, od_zip2, od_addr1, od_addr2,
         od_b_name, od_b_hp, od_b_addr1, od_b_addr2,
         od_memo, od_cart_count, od_cart_price, od_send_cost,
         od_receipt_price, od_receipt_time, od_misu,
         od_shop_memo, od_mod_history, od_status, od_settle_case,
         od_delivery_company, od_cash, od_cash_no, od_cash_info,
         od_time, od_pwd, od_ip)
        VALUES ".implode(',', $odRows));
    $db->query("INSERT INTO g5_shop_cart
        (od_id, mb_id, it_id, it_name, ct_status, ct_history,
         ct_price, ct_stock_use, ct_option, ct_qty, io_id,
         ct_time, ct_ip, ct_select, ct_select_time)
        VALUES ".implode(',', $ctRows));
};

/* ---- 기본 주문 10만 건 (상품당 1,000건) ---- */
// seq % 20 → 0~7 주문, 8~13 입금, 14~17 준비, 18 배송, 19 완료
$statuses = array_merge(
    array_fill(0, 8, '주문'), array_fill(0, 6, '입금'),
    array_fill(0, 4, '준비'), array('배송'), array('완료')
);

$db->autocommit(false);
$seq = 0;
for ($i = 1; $i <= ITEM_CNT; $i++) {
    $it_id   = sprintf('LT%04d', $i);
    $it_name = "부하테스트 상품 $i";
    $price   = $it_price($i);

    $odRows = $ctRows = array();
    for ($k = 0; $k < ORDER_PER_IT; $k++) {
        $seq++;
        list($odRows[], $ctRows[]) = $make_rows($seq, $it_id, $it_name, $price, $statuses[$seq % 20]);
    }
    $flush($odRows, $ctRows);

    if ($i % 10 === 0) {
        $db->commit();
        echo "  기본 주문 ".number_format($seq)."건...\n";
    }
}
$db->commit();
$base_cnt = $seq;

/* ---- 핫 아이템 1: LT0001·LT0002 에 대기 주문 각 120만 건 ---- */
// 전부 주문·입금·준비 상태(ct_stock_use=0)라 get_it_stock_qty 의
// SUM 스캔 대상이 되어 단일 쿼리가 1초를 넘긴다.
$pending = array('주문', '입금', '준비');
foreach (array(1, 2) as $i) {
    $it_id   = sprintf('LT%04d', $i);
    $it_name = "부하테스트 상품 $i";
    $price   = $it_price($i);

    $odRows = $ctRows = array();
    for ($k = 1; $k <= HOT_PLAIN_CNT; $k++) {
        $seq++;
        list($odRows[], $ctRows[]) = $make_rows($seq, $it_id, $it_name, $price, $pending[$seq % 3]);
        if ($k % 1000 === 0) {
            $flush($odRows, $ctRows);
            $odRows = $ctRows = array();
        }
        if ($k % 200000 === 0) {
            $db->commit();
            echo "  핫($it_id) ".number_format($k)."건...\n";
        }
    }
    $db->commit();
}

/* ---- 핫 아이템 2: LT0003 옵션 20개 + 대기 주문 100만 건 분산 ---- */
// 옵션 쿼리 하나는 수십 ms지만 itemoption.php 가 옵션 수만큼
// get_option_stock_qty 를 반복 호출해 페이지 단위로 초 단위가 된다.
$db->query("UPDATE g5_shop_item SET it_option_subject = '색상' WHERE it_id = 'LT0003'");
$optRows = array();
for ($j = 1; $j <= OPTION_CNT; $j++) {
    $optRows[] = "('".sprintf('색상%02d', $j)."', 0, 'LT0003', 0, 9999999, 0, 1)";
}
$db->query("INSERT INTO g5_shop_item_option
    (io_id, io_type, it_id, io_price, io_stock_qty, io_noti_qty, io_use)
    VALUES ".implode(',', $optRows));
$db->commit();

$price = $it_price(3);
$odRows = $ctRows = array();
for ($k = 1; $k <= HOT_OPTION_CNT; $k++) {
    $seq++;
    $io_id = sprintf('색상%02d', $k % OPTION_CNT + 1);
    list($odRows[], $ctRows[]) = $make_rows($seq, 'LT0003', '부하테스트 상품 3',
        $price, $pending[$seq % 3], $io_id, '색상:'.$io_id);
    if ($k % 1000 === 0) {
        $flush($odRows, $ctRows);
        $odRows = $ctRows = array();
    }
    if ($k % 200000 === 0) {
        $db->commit();
        echo "  핫(LT0003 옵션) ".number_format($k)."건...\n";
    }
}
$db->commit();
$db->autocommit(true);

printf("완료: 회원 %d · 상품 %d · 기본 주문 %s건 · 핫 주문 %s건 (%.1f초)\n",
    MEMBER_CNT, ITEM_CNT, number_format($base_cnt),
    number_format($seq - $base_cnt), microtime(true) - $t0);
