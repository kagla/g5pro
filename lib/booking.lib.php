<?php
if (!defined('_GNUBOARD_')) exit;

// 테이블명 상수 폴백 — dbconfig.php 에 상수가 없는 기존 설치 보호
function booking_table_defaults()
{
    global $g5;
    $tables = array(
        'booking_table'            => 'booking',
        'booking_room_table'       => 'booking_room',
        'booking_room_image_table' => 'booking_room_image',
        'booking_calendar_table'   => 'booking_calendar',
        'booking_addon_table'      => 'booking_addon',
        'booking_addon_item_table' => 'booking_addon_item',
        'booking_note_table'       => 'booking_note',
        'booking_config_table'     => 'booking_config',
        'booking_inicis_log_table' => 'booking_inicis_log',
    );
    foreach ($tables as $key => $name) {
        if (!isset($g5[$key])) $g5[$key] = G5_TABLE_PREFIX.$name;
    }
}
booking_table_defaults();

// 모듈 자체 설치/업그레이드 — 순정 설치·dbupgrade 와 무관하게 멱등 실행
function booking_install()
{
    $created = booking_create_tables();
    // 향후 스키마 변경은 여기 누적한다: SHOW COLUMNS 판정 후 ALTER TABLE
    return array('created' => $created);
}

function booking_installed()
{
    global $g5;
    return (bool)sql_query(" DESC `{$g5['booking_config_table']}` ", false);
}

// 테이블 DDL 실행. 생성한 테이블이 있으면 true
function booking_create_tables()
{
    global $g5;
    $created = false;
    foreach (booking_table_ddl() as $key => $ddl) {
        if (sql_query(" DESC `{$g5[$key]}` ", false)) continue;
        sql_query($ddl, true);
        $created = true;
    }
    return $created;
}

function booking_table_ddl()
{
    global $g5;
    return array(
    'booking_room_table' => " CREATE TABLE IF NOT EXISTS `{$g5['booking_room_table']}` (
        `br_id` int(11) NOT NULL AUTO_INCREMENT,
        `br_subject` varchar(255) NOT NULL DEFAULT '',
        `br_content` text NOT NULL,
        `br_base_person` int(11) NOT NULL DEFAULT '2',
        `br_max_person` int(11) NOT NULL DEFAULT '4',
        `br_person_price` int(11) NOT NULL DEFAULT '0',
        `br_room_count` int(11) NOT NULL DEFAULT '1',
        `br_weekday_price` int(11) NOT NULL DEFAULT '0',
        `br_weekend_price` int(11) NOT NULL DEFAULT '0',
        `br_use` tinyint(4) NOT NULL DEFAULT '1',
        `br_order` int(11) NOT NULL DEFAULT '0',
        `br_datetime` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        PRIMARY KEY (`br_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    'booking_room_image_table' => " CREATE TABLE IF NOT EXISTS `{$g5['booking_room_image_table']}` (
        `bi_id` int(11) NOT NULL AUTO_INCREMENT,
        `br_id` int(11) NOT NULL DEFAULT '0',
        `bi_file` varchar(255) NOT NULL DEFAULT '',
        `bi_order` int(11) NOT NULL DEFAULT '0',
        `bi_main` tinyint(4) NOT NULL DEFAULT '0',
        PRIMARY KEY (`bi_id`), KEY `br_id` (`br_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    'booking_calendar_table' => " CREATE TABLE IF NOT EXISTS `{$g5['booking_calendar_table']}` (
        `bd_id` int(11) NOT NULL AUTO_INCREMENT,
        `br_id` int(11) NOT NULL DEFAULT '0',
        `bd_date` date NOT NULL DEFAULT '1970-01-01',
        `bd_price` int(11) NOT NULL DEFAULT '-1',
        `bd_room_count` int(11) NOT NULL DEFAULT '-1',
        PRIMARY KEY (`bd_id`), UNIQUE KEY `br_date` (`br_id`,`bd_date`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    'booking_addon_table' => " CREATE TABLE IF NOT EXISTS `{$g5['booking_addon_table']}` (
        `ba_id` int(11) NOT NULL AUTO_INCREMENT,
        `ba_subject` varchar(255) NOT NULL DEFAULT '',
        `ba_price` int(11) NOT NULL DEFAULT '0',
        `ba_max_qty` int(11) NOT NULL DEFAULT '10',
        `ba_use` tinyint(4) NOT NULL DEFAULT '1',
        `ba_order` int(11) NOT NULL DEFAULT '0',
        PRIMARY KEY (`ba_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    'booking_addon_item_table' => " CREATE TABLE IF NOT EXISTS `{$g5['booking_addon_item_table']}` (
        `bt_id` int(11) NOT NULL AUTO_INCREMENT,
        `bk_id` int(11) NOT NULL DEFAULT '0',
        `bt_subject` varchar(255) NOT NULL DEFAULT '',
        `bt_price` int(11) NOT NULL DEFAULT '0',
        `bt_qty` int(11) NOT NULL DEFAULT '0',
        `bt_amount` int(11) NOT NULL DEFAULT '0',
        PRIMARY KEY (`bt_id`), KEY `bk_id` (`bk_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    'booking_table' => " CREATE TABLE IF NOT EXISTS `{$g5['booking_table']}` (
        `bk_id` int(11) NOT NULL AUTO_INCREMENT,
        `bk_no` varchar(20) NOT NULL DEFAULT '',
        `br_id` int(11) NOT NULL DEFAULT '0',
        `bk_checkin` date NOT NULL DEFAULT '1970-01-01',
        `bk_checkout` date NOT NULL DEFAULT '1970-01-01',
        `bk_person` int(11) NOT NULL DEFAULT '1',
        `bk_name` varchar(100) NOT NULL DEFAULT '',
        `bk_hp` varchar(20) NOT NULL DEFAULT '',
        `bk_email` varchar(255) NOT NULL DEFAULT '',
        `bk_request` text NOT NULL,
        `mb_id` varchar(20) NOT NULL DEFAULT '',
        `bk_password` varchar(255) NOT NULL DEFAULT '',
        `bk_room_price` int(11) NOT NULL DEFAULT '0',
        `bk_person_price` int(11) NOT NULL DEFAULT '0',
        `bk_addon_price` int(11) NOT NULL DEFAULT '0',
        `bk_total_price` int(11) NOT NULL DEFAULT '0',
        `bk_status` varchar(12) NOT NULL DEFAULT 'hold',
        `bk_hold_expire` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        `bk_oid` varchar(64) NOT NULL DEFAULT '',
        `bk_tid` varchar(64) NOT NULL DEFAULT '',
        `bk_pay_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        `bk_cancel_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        `bk_cancel_memo` varchar(255) NOT NULL DEFAULT '',
        `bk_refund_plan_price` int(11) NOT NULL DEFAULT '0',
        `bk_refund_price` int(11) NOT NULL DEFAULT '0',
        `bk_refund_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        `bk_datetime` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        `bk_ip` varchar(45) NOT NULL DEFAULT '',
        PRIMARY KEY (`bk_id`), UNIQUE KEY `bk_no` (`bk_no`),
        KEY `idx_avail` (`br_id`,`bk_status`,`bk_checkin`,`bk_checkout`),
        KEY `bk_oid` (`bk_oid`), KEY `mb_id` (`mb_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    'booking_note_table' => " CREATE TABLE IF NOT EXISTS `{$g5['booking_note_table']}` (
        `bn_id` int(11) NOT NULL AUTO_INCREMENT,
        `bk_id` int(11) NOT NULL DEFAULT '0',
        `bn_writer` varchar(10) NOT NULL DEFAULT 'guest',
        `bn_content` text NOT NULL,
        `bn_checked` tinyint(4) NOT NULL DEFAULT '0',
        `bn_datetime` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        PRIMARY KEY (`bn_id`), KEY `bk_id` (`bk_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    'booking_config_table' => " CREATE TABLE IF NOT EXISTS `{$g5['booking_config_table']}` (
        `bc_id` int(11) NOT NULL DEFAULT '1',
        `bc_checkin_time` varchar(5) NOT NULL DEFAULT '15:00',
        `bc_checkout_time` varchar(5) NOT NULL DEFAULT '11:00',
        `bc_hold_minutes` int(11) NOT NULL DEFAULT '20',
        `bc_open_months` int(11) NOT NULL DEFAULT '6',
        `bc_sameday_deadline` varchar(5) NOT NULL DEFAULT '18:00',
        `bc_min_nights` int(11) NOT NULL DEFAULT '1',
        `bc_max_nights` int(11) NOT NULL DEFAULT '7',
        `bc_cancel_policy` text NOT NULL,
        `bc_refund_terms` text NOT NULL,
        `bc_inicis_mid` varchar(20) NOT NULL DEFAULT '',
        `bc_inicis_sign_key` varchar(64) NOT NULL DEFAULT '',
        `bc_inicis_iniapi_key` varchar(64) NOT NULL DEFAULT '',
        `bc_inicis_iniapi_iv` varchar(64) NOT NULL DEFAULT '',
        `bc_card_test` tinyint(4) NOT NULL DEFAULT '1',
        `bc_admin_email` varchar(255) NOT NULL DEFAULT '',
        PRIMARY KEY (`bc_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    'booking_inicis_log_table' => " CREATE TABLE IF NOT EXISTS `{$g5['booking_inicis_log_table']}` (
        `bl_id` int(11) NOT NULL AUTO_INCREMENT,
        `bl_oid` varchar(64) NOT NULL DEFAULT '',
        `bl_tid` varchar(64) NOT NULL DEFAULT '',
        `bl_type` varchar(20) NOT NULL DEFAULT '',
        `bl_price` int(11) NOT NULL DEFAULT '0',
        `bl_result_code` varchar(10) NOT NULL DEFAULT '',
        `bl_data` mediumtext NOT NULL,
        `bl_datetime` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        PRIMARY KEY (`bl_id`), KEY `bl_oid` (`bl_oid`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    );
}

// 예약 설정 — 없으면 기본 행을 만들고 반환한다 (요청 단위 static 캐시)
function booking_config()
{
    global $g5;
    static $config = null;
    if ($config !== null) return $config;
    $config = sql_fetch(" select * from `{$g5['booking_config_table']}` where bc_id = 1 ");
    if (!$config) {
        sql_query(" insert into `{$g5['booking_config_table']}` set bc_id = 1,
            bc_cancel_policy = '7:100\n3:50\n1:30\n0:0', bc_refund_terms = '' ", true);
        $config = sql_fetch(" select * from `{$g5['booking_config_table']}` where bc_id = 1 ");
    }
    return $config;
}

// 숙박일(밤) 목록. 체크아웃 당일은 재고를 쓰지 않으므로 제외한다
function booking_nights($checkin, $checkout)
{
    $list = array();
    $t = strtotime($checkin); $end = strtotime($checkout);
    while ($t !== false && $t < $end) { $list[] = date('Y-m-d', $t); $t = strtotime('+1 day', $t); }
    return $list;
}

function booking_calendar_row($br_id, $date)
{
    global $g5;
    $br_id = (int)$br_id; $date = sql_real_escape_string($date);
    $row = sql_fetch(" select * from `{$g5['booking_calendar_table']}` where br_id = '$br_id' and bd_date = '$date' ");
    return $row ? $row : null;
}

// 하룻밤 요금. 캘린더 개별요금이 있으면 우선, 없으면 금·토 밤은 주말요금
function booking_night_price($room, $date, $cal_row = false)
{
    if ($cal_row === false) $cal_row = booking_calendar_row($room['br_id'], $date);
    if ($cal_row && (int)$cal_row['bd_price'] >= 0) return (int)$cal_row['bd_price'];
    $w = (int)date('w', strtotime($date));
    return ($w === 5 || $w === 6) ? (int)$room['br_weekend_price'] : (int)$room['br_weekday_price'];
}

// 그 날짜에 팔 수 있는 객실 실수. 캘린더 값이 있으면 우선
function booking_sellable_count($room, $date, $cal_row = false)
{
    if ($cal_row === false) $cal_row = booking_calendar_row($room['br_id'], $date);
    if ($cal_row && (int)$cal_row['bd_room_count'] >= 0) return (int)$cal_row['bd_room_count'];
    return (int)$room['br_room_count'];
}

// 그 날짜의 유효 예약 수 = 확정·취소요청 + 아직 만료되지 않은 hold
function booking_booked_count($br_id, $date)
{
    global $g5;
    $br_id = (int)$br_id; $date = sql_real_escape_string($date);
    $now = date('Y-m-d H:i:s', G5_SERVER_TIME);
    $row = sql_fetch(" select count(*) as cnt from `{$g5['booking_table']}`
        where br_id = '$br_id' and bk_checkin <= '$date' and bk_checkout > '$date'
          and ( bk_status in ('confirmed', 'cancel_req')
                or (bk_status = 'hold' and bk_hold_expire > '$now') ) ");
    return (int)$row['cnt'];
}

function booking_remain_count($room, $date)
{
    return booking_sellable_count($room, $date) - booking_booked_count($room['br_id'], $date);
}

// 취소 정책 "남은일수:환불율" 줄 목록에서 환불율(0~100)을 고른다
function booking_refund_rate($policy_text, $days_before)
{
    $rules = array();
    foreach (preg_split('/[\r\n]+/', trim((string)$policy_text)) as $line) {
        if (preg_match('/^\s*(\d+)\s*:\s*(\d+)\s*$/', $line, $m)) $rules[(int)$m[1]] = min(100, (int)$m[2]);
    }
    krsort($rules);
    foreach ($rules as $n => $rate) { if ($days_before >= $n) return $rate; }
    return 0;
}

// 유일한 예약번호(대문자 영숫자 10자)
function booking_new_no()
{
    global $g5;
    for ($i = 0; $i < 10; $i++) {
        $no = strtoupper(substr(str_replace(array('.', '/'), '', base64_encode(md5(uniqid(mt_rand(), true), true))), 0, 10));
        $no = preg_replace('/[^A-Z0-9]/', strval(mt_rand(0, 9)), $no);
        if (strlen($no) < 10) continue;
        if (!sql_fetch(" select bk_id from `{$g5['booking_table']}` where bk_no = '$no' ")) return $no;
    }
    return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
}

// 객실료 + 인원추가 + 부가상품. $addons 는 array(ba_id => qty)
function booking_calc_price($room, $checkin, $checkout, $person, $addons)
{
    global $g5;
    $nights = booking_nights($checkin, $checkout);
    $room_price = 0;
    foreach ($nights as $date) $room_price += booking_night_price($room, $date);
    $extra = max(0, (int)$person - (int)$room['br_base_person']);
    $person_price = $extra * count($nights) * (int)$room['br_person_price'];
    $addon_price = 0; $addon_items = array();
    if (is_array($addons)) {
        foreach ($addons as $ba_id => $qty) {
            $ba_id = (int)$ba_id; $qty = (int)$qty;
            if ($qty < 1) continue;
            $ba = sql_fetch(" select * from `{$g5['booking_addon_table']}` where ba_id = '$ba_id' and ba_use = 1 ");
            if (!$ba) continue;
            $qty = min($qty, (int)$ba['ba_max_qty']);
            $amount = (int)$ba['ba_price'] * $qty;
            $addon_price += $amount;
            $addon_items[] = array('ba_id' => $ba_id, 'subject' => $ba['ba_subject'],
                'price' => (int)$ba['ba_price'], 'qty' => $qty, 'amount' => $amount);
        }
    }
    return array('room' => $room_price, 'person' => $person_price, 'addon' => $addon_price,
        'total' => $room_price + $person_price + $addon_price, 'addon_items' => $addon_items);
}

// 숙박 조건 검증. 통과하면 빈 문자열, 아니면 오류 메시지
function booking_validate_stay($room, $checkin, $checkout, $person)
{
    $config = booking_config();
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkin) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkout))
        return '날짜 형식이 올바르지 않습니다.';
    $today = date('Y-m-d', G5_SERVER_TIME);
    if ($checkin < $today) return '지난 날짜는 예약할 수 없습니다.';
    if ($checkin == $today && date('H:i', G5_SERVER_TIME) > $config['bc_sameday_deadline'])
        return '당일 예약은 '.$config['bc_sameday_deadline'].' 까지만 가능합니다.';
    $limit = date('Y-m-d', strtotime('+'.(int)$config['bc_open_months'].' month', G5_SERVER_TIME));
    if ($checkout > $limit) return '예약은 '.(int)$config['bc_open_months'].'개월 이내 날짜만 가능합니다.';
    $nights = count(booking_nights($checkin, $checkout));
    if ($nights < (int)$config['bc_min_nights']) return '최소 '.(int)$config['bc_min_nights'].'박부터 예약 가능합니다.';
    if ($nights > (int)$config['bc_max_nights']) return '최대 '.(int)$config['bc_max_nights'].'박까지 예약 가능합니다.';
    if ((int)$person < 1 || (int)$person > (int)$room['br_max_person'])
        return '인원은 1~'.(int)$room['br_max_person'].'명까지 가능합니다.';
    return '';
}

// 결제 전 임시 점유(hold) 생성. 성공하면 array('ok'=>true,'bk_id','bk_no')
function booking_create_hold($br_id, $checkin, $checkout, $person, $addons, $guest)
{
    global $g5;
    $config = booking_config();
    $br_id = (int)$br_id;
    sql_query(" set autocommit = 0 ", true);
    sql_query(" start transaction ", true);
    // 객실 행 잠금 = 객실별 뮤텍스. 같은 객실의 동시 hold 시도를 직렬화한다
    $room = sql_fetch(" select * from `{$g5['booking_room_table']}` where br_id = '$br_id' and br_use = 1 for update ");
    if (!$room) { sql_query(" rollback ", false); sql_query(" set autocommit = 1 ", false); return array('ok' => false, 'error' => '객실 정보가 없습니다.'); }
    $error = booking_validate_stay($room, $checkin, $checkout, $person);
    if (!$error) {
        foreach (booking_nights($checkin, $checkout) as $date) {
            if (booking_remain_count($room, $date) < 1) { $error = $date.' 은(는) 예약이 마감되었습니다.'; break; }
        }
    }
    if ($error) { sql_query(" rollback ", false); sql_query(" set autocommit = 1 ", false); return array('ok' => false, 'error' => $error); }

    $price = booking_calc_price($room, $checkin, $checkout, $person, $addons);
    $bk_no = booking_new_no();
    $expire = date('Y-m-d H:i:s', G5_SERVER_TIME + (int)$config['bc_hold_minutes'] * 60);
    $now = date('Y-m-d H:i:s', G5_SERVER_TIME);
    sql_query(" insert into `{$g5['booking_table']}` set
        bk_no = '$bk_no', br_id = '$br_id',
        bk_checkin = '".sql_real_escape_string($checkin)."', bk_checkout = '".sql_real_escape_string($checkout)."',
        bk_person = '".(int)$person."',
        bk_name = '".sql_real_escape_string($guest['name'])."',
        bk_hp = '".sql_real_escape_string($guest['hp'])."',
        bk_email = '".sql_real_escape_string($guest['email'])."',
        bk_request = '".sql_real_escape_string($guest['request'])."',
        mb_id = '".sql_real_escape_string($guest['mb_id'])."',
        bk_password = '".sql_real_escape_string($guest['password'])."',
        bk_room_price = '{$price['room']}', bk_person_price = '{$price['person']}',
        bk_addon_price = '{$price['addon']}', bk_total_price = '{$price['total']}',
        bk_status = 'hold', bk_hold_expire = '$expire',
        bk_datetime = '$now', bk_ip = '".sql_real_escape_string($_SERVER['REMOTE_ADDR'])."' ", true);
    $bk_id = sql_insert_id();
    foreach ($price['addon_items'] as $item) {
        sql_query(" insert into `{$g5['booking_addon_item_table']}` set bk_id = '$bk_id',
            bt_subject = '".sql_real_escape_string($item['subject'])."',
            bt_price = '{$item['price']}', bt_qty = '{$item['qty']}', bt_amount = '{$item['amount']}' ", true);
    }
    sql_query(" commit ", true);
    sql_query(" set autocommit = 1 ", true);
    return array('ok' => true, 'bk_id' => $bk_id, 'bk_no' => $bk_no);
}

function booking_get($bk_id)
{
    global $g5;
    $bk_id = (int)$bk_id;
    $row = sql_fetch(" select * from `{$g5['booking_table']}` where bk_id = '$bk_id' ");
    return $row ? $row : null;
}

function booking_get_by_no($bk_no)
{
    global $g5;
    $bk_no = sql_real_escape_string($bk_no);
    $row = sql_fetch(" select * from `{$g5['booking_table']}` where bk_no = '$bk_no' ");
    return $row ? $row : null;
}

function booking_get_by_oid($oid)
{
    global $g5;
    $oid = sql_real_escape_string($oid);
    $row = sql_fetch(" select * from `{$g5['booking_table']}` where bk_oid = '$oid' ");
    return $row ? $row : null;
}

// 이니시스 상점 설정. 결제 화면·승인·환불이 모두 여기 한 곳만 본다.
//
// shop/settle_inicis.inc.php 를 include 하지 않는다 — 그 파일은 $default(쇼핑몰 설정)를
// 제자리에서 덮어쓴다(실 결제일 때 de_inicis_mid 에 'SIR' 을 붙이는 등). 예약이 그것을 타면
// 같은 요청 안의 쇼핑몰 코드가 오염된 설정을 보게 된다. 예약은 제 설정으로만 간다.
//
// 상점아이디는 관리자가 넣은 값을 그대로 쓴다(접두사를 붙이지 않는다) — 예약 환경설정의
// 상점아이디 칸은 이니시스가 발급한 MID 전체를 적는 자리다.
function booking_inicis_conf()
{
    $bc = booking_config();
    // 테스트 결제는 이니시스가 공개한 테스트 상점으로 고정한다. 관리자가 실 키를 넣기 전에도
    // 결제 흐름 전체를 그대로 밟아 볼 수 있어야 한다
    if ((int)$bc['bc_card_test']) {
        return array(
            'mid'        => 'INIpayTest',
            'sign_key'   => 'SU5JTElURV9UUklQTEVERVNfS0VZU1RS',
            'iniapi_key' => '',
            'iniapi_iv'  => '',
            'js_url'     => 'https://stgstdpay.inicis.com/stdjs/INIStdPay.js',
            'refund_url' => 'https://stginiapi.inicis.com/api/v1/refund',
            'test'       => 1,
        );
    }
    return array(
        'mid'        => trim($bc['bc_inicis_mid']),
        'sign_key'   => trim($bc['bc_inicis_sign_key']),
        'iniapi_key' => trim($bc['bc_inicis_iniapi_key']),
        'iniapi_iv'  => trim($bc['bc_inicis_iniapi_iv']),
        'js_url'     => 'https://stdpay.inicis.com/stdjs/INIStdPay.js',
        'refund_url' => 'https://iniapi.inicis.com/api/v1/refund',
        'test'       => 0,
    );
}

// PG 거래 기록. 승인 요청·응답·망취소·환불이 모두 여기에 한 줄씩 남는다.
// $type ∈ auth_req | auth_res | netcancel | refund
//
// 대사(對査)의 근거라서 실패해도 흐름을 멈추지 않고 남기는 쪽으로만 움직인다.
// bl_result_code 는 varchar(10) 이므로 넘치는 값은 자른다 — 여기서 자르지 않으면
// strict 모드에서 insert 자체가 실패해 기록이 통째로 사라진다. 원문은 $data 에 담는다.
function booking_inicis_log($oid, $tid, $type, $price, $result_code, $data)
{
    global $g5;
    sql_query(" insert into `{$g5['booking_inicis_log_table']}` set
        bl_oid = '".sql_real_escape_string(substr((string)$oid, 0, 64))."',
        bl_tid = '".sql_real_escape_string(substr((string)$tid, 0, 64))."',
        bl_type = '".sql_real_escape_string(substr((string)$type, 0, 20))."',
        bl_price = '".(int)$price."',
        bl_result_code = '".sql_real_escape_string(substr((string)$result_code, 0, 10))."',
        bl_data = '".sql_real_escape_string((string)$data)."',
        bl_datetime = '".date('Y-m-d H:i:s', G5_SERVER_TIME)."' ", true);
}

// 예약 안내 메일. 발송 실패는 무시한다 (예약 처리 흐름을 막지 않는다)
function booking_send_mail($bk_id, $kind)
{
    global $g5, $config;
    // common.php 는 mailer.lib.php 를 로드하지 않는다 (순정 호출부가 각자 include 한다)
    include_once(G5_LIB_PATH.'/mailer.lib.php');
    $titles = array('confirm' => '예약이 확정되었습니다', 'cancel_req' => '예약 취소 요청이 접수되었습니다',
        'cancelled' => '예약이 취소되었습니다');
    if (!isset($titles[$kind])) return;
    $bk = booking_get($bk_id);
    if (!$bk) return;
    $bc = booking_config();
    $br_id = (int)$bk['br_id'];
    $room = sql_fetch(" select br_subject from `{$g5['booking_room_table']}` where br_id = '$br_id' ");
    $status = array('hold' => '결제대기', 'confirmed' => '예약확정', 'cancel_req' => '취소요청',
        'cancelled' => '취소완료', 'expired' => '만료');
    $subject = '['.$config['cf_title'].'] '.$titles[$kind].' ('.$bk['bk_no'].')';
    $content = '<p>'.get_text($bk['bk_name']).'님, '.$titles[$kind].'.</p><ul>'
        .'<li>예약번호: '.get_text($bk['bk_no']).'</li>'
        .'<li>객실: '.get_text($room ? $room['br_subject'] : '').'</li>'
        .'<li>기간: '.$bk['bk_checkin'].' ~ '.$bk['bk_checkout'].' ('.count(booking_nights($bk['bk_checkin'], $bk['bk_checkout'])).'박)</li>'
        .'<li>인원: '.(int)$bk['bk_person'].'명</li>'
        .'<li>결제금액: '.number_format((int)$bk['bk_total_price']).'원</li>'
        .'<li>상태: '.(isset($status[$bk['bk_status']]) ? $status[$bk['bk_status']] : $bk['bk_status']).'</li>'
        .'</ul>';
    if ($bk['bk_email']) @mailer($config['cf_title'], $config['cf_admin_email'], $bk['bk_email'], $subject, $content, 1);
    if ($bc['bc_admin_email']) @mailer($config['cf_title'], $config['cf_admin_email'], $bc['bc_admin_email'], $subject, $content, 1);
}
