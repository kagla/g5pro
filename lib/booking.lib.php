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

// 취소 정책 "남은일수:환불율" 줄 목록을 array(남은일수 => 환불율) 로 편다.
// 남은일수 내림차순(krsort)이라 앞에서부터 처음 걸리는 단계가 곧 적용 단계다.
// 판정(booking_refund_rate)도 화면 고지(booking/room.php)도 이 한 함수만 쓴다 —
// 파싱이 두 곳에 흩어지면 손님이 본 규정과 실제 적용 규정이 갈린다
function booking_cancel_rules($policy_text)
{
    $rules = array();
    foreach (preg_split('/[\r\n]+/', trim((string)$policy_text)) as $line) {
        if (preg_match('/^\s*(\d+)\s*:\s*(\d+)\s*$/', $line, $m)) $rules[(int)$m[1]] = min(100, (int)$m[2]);
    }
    krsort($rules);
    return $rules;
}

// 취소 정책 "남은일수:환불율" 줄 목록에서 환불율(0~100)을 고른다
function booking_refund_rate($policy_text, $days_before)
{
    foreach (booking_cancel_rules($policy_text) as $n => $rate) { if ($days_before >= $n) return $rate; }
    return 0;
}

// 환불액. 화면이 미리 보여 주는 예정액도, 실제로 나가는 돈도 이 한 식으로만 구한다 —
// 계산이 두 곳에 흩어지면 손님이 본 금액과 다른 돈이 나간다.
// 10원 미만은 버린다 (카드 부분취소는 10원 단위로 접수된다)
function booking_refund_amount($total_price, $rate)
{
    $total = (int)$total_price;
    if ($total < 1) return 0;
    $amount = (int)floor($total * (int)$rate / 100 / 10) * 10;
    if ($amount < 0) return 0;
    return min($amount, $total);
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

// 예약 상태의 한글 이름. 고객 화면·관리자 화면·안내 메일이 모두 이 하나만 본다 —
// 같은 목록을 여러 곳에 베껴 두면 상태가 하나 늘 때 어느 화면은 영문 코드를 그대로 내보낸다.
// 모르는 값은 코드 그대로 돌려준다 (숨기는 것보다 보이는 편이 고치기 쉽다)
function booking_status_label($status)
{
    $labels = array('hold' => '결제대기', 'confirmed' => '예약확정',
        'cancel_req' => '취소요청', 'cancelled' => '취소완료', 'expired' => '만료');
    return isset($labels[$status]) ? $labels[$status] : (string)$status;
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

// 예약 열람·조작 권한. 조회(view)·추가 요청(note)·취소 신청이 모두 이 하나만 본다.
//
// 두 갈래뿐이다:
//   1) 비회원 — booking/lookup.php 에서 예약번호+비밀번호를 맞춘 뒤 심은 인가 세션
//   2) 회원   — 로그인한 계정이 예약 행의 mb_id 와 같을 때
// 주소창의 예약번호만으로는 어느 쪽도 열리지 않는다.
//
// mb_id 빈 값끼리 맞아떨어지는 일이 없도록 회원 갈래는 양쪽이 비어 있지 않을 때만 본다 —
// 비로그인 상태의 $member['mb_id'] 는 '' 이고 비회원 예약의 mb_id 도 '' 이다.
function booking_owner_check($bk)
{
    global $member, $is_member;
    if (!$bk || !isset($bk['bk_id'])) return false;
    if (get_session('ss_booking_view_'.(int)$bk['bk_id'])) return true;
    if ($is_member && $bk['mb_id'] !== '' && $member['mb_id'] === $bk['mb_id']) return true;
    return false;
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
            // 취소(환불) API 는 INIAPI Key 로 서명한다. 테스트 상점의 키는 이니시스가 공개한
            // 고정값이다 — 비워 두면 테스트 결제는 되는데 취소만 영영 실패한다
            // (영카트 get_inicis_iniapi_key() 가 INIpayTest 에 쓰는 값과 같다)
            'iniapi_key' => 'ItEQKi3rY7uvDS8l',
            'iniapi_iv'  => 'HYb3yQ4f65QL89==',
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

// 결제대사(adm/booking/recon.php)의 첫 걸음 — 승인된 결제를 예약 행에 붙인다(claim).
// 승인 로그의 거래번호를 bk_tid 에 적고 상태를 confirmed 로 옮긴다. 확정도 환불도 여기를 지난다:
//   1) 환불은 booking_refund() 하나로만 나가야 하는데 그 함수는 confirmed·cancel_req 만 받는다.
//      상태를 옮겨 두면 잠금·중복 방어·거래 로그가 전부 그 함수의 것을 그대로 쓴다
//   2) hold 행에는 tid 가 없다 (확정할 때 적히는 값이다). 로그에서 옮겨 오지 않으면 환불할 수 없다
//
// $check_room 이 참이면 확정용이다 — 자리가 남아 있어야 통과한다.
// 반환은 array('ok' => bool, 'msg' => 안내문).
function booking_recon_claim($bk, $log, $check_room)
{
    global $g5;

    if (!$bk || !isset($bk['bk_id'])) return array('ok' => false, 'msg' => '예약 정보를 찾을 수 없습니다.');
    // 근거는 승인 성공 로그뿐이다. 로그 없이 확정하면 돈을 받지 않은 예약이 확정된다
    if (!$log || !isset($log['bl_oid']) || (string)$log['bl_type'] !== 'auth_res'
        || (string)$log['bl_result_code'] !== '0000')
        return array('ok' => false, 'msg' => '승인 성공 기록을 찾을 수 없습니다.');
    if ($log['bl_oid'] === '' || $log['bl_oid'] !== $bk['bk_oid'])
        return array('ok' => false, 'msg' => '승인 기록과 예약의 주문번호가 서로 다릅니다.');
    if (trim($log['bl_tid']) === '')
        return array('ok' => false, 'msg' => '승인 기록에 거래번호(tid)가 없습니다. 이니시스 관리자에서 직접 처리하십시오.');
    // 승인된 금액과 청구액이 다르면 사람이 봐야 한다 — 전액이 얼마인지 우리가 정할 수 없다
    if ((int)$log['bl_price'] !== (int)$bk['bk_total_price'])
        return array('ok' => false, 'msg' => '승인 금액('.number_format((int)$log['bl_price']).'원)과 예약 청구액('
            .number_format((int)$bk['bk_total_price']).'원)이 다릅니다. 이니시스 관리자에서 직접 확인하십시오.');

    $bk_id = (int)$bk['bk_id'];
    $oid = sql_real_escape_string($log['bl_oid']);
    $now = date('Y-m-d H:i:s', G5_SERVER_TIME);
    // 결제 시각은 승인 로그가 남은 시각이 가장 가깝다
    $pay_time = ($log['bl_datetime'] > '1970-01-02') ? $log['bl_datetime'] : $now;

    sql_query(" set autocommit = 0 ", true);
    sql_query(" start transaction ", true);
    // 잠금 순서는 booking_create_hold()·return.php 와 같게 객실 → 예약이다 (교착 방지)
    $room = sql_fetch(" select * from `{$g5['booking_room_table']}` where br_id = '".(int)$bk['br_id']."' for update ");
    $cur  = sql_fetch(" select * from `{$g5['booking_table']}` where bk_id = '$bk_id' for update ");

    // 되돌린 기록 재확인 — 화면 목록이 건 것과 같은 조건을 잠근 뒤 한 번 더 본다.
    // return.php 는 승인 검증에 실패하면 트랜잭션을 롤백한 뒤 잠금 없이 망취소를 쏘므로,
    // auth_res 는 남았는데 netcancel 은 아직 안 남은 몇 초의 창이 있다. 그 창에서 관리자가
    // "확정"을 누르면 곧 취소될 결제로 예약이 확정된다 — 목록에서 걸러 낸 것으로는 부족하다.
    // for update 로 읽어 판정하는 동안 끼어드는 기록까지 이 트랜잭션 뒤로 세운다
    $undone = sql_fetch(" select bl_id from `{$g5['booking_inicis_log_table']}`
        where bl_oid = '$oid' and bl_type in ('netcancel', 'refund') limit 1 for update ");

    $err = '';
    if (!$cur)                                   $err = '예약 정보를 찾을 수 없습니다.';
    else if ($undone)                            $err = '이미 취소·환불된 결제입니다. 거래 기록을 확인하십시오.';
    else if ($cur['bk_status'] === 'confirmed')  $err = '이미 확정된 예약입니다. 예약 상세에서 처리하십시오.';
    else if ($cur['bk_status'] !== 'hold' && $cur['bk_status'] !== 'expired')
        $err = '결제대기 상태인 예약만 이 화면에서 처리할 수 있습니다. (현재 상태: '.$cur['bk_status'].')';
    else if ($cur['bk_oid'] !== $log['bl_oid'])  $err = '예약의 주문번호가 그 사이 바뀌었습니다.';
    else if ((int)$cur['bk_total_price'] !== (int)$log['bl_price'])
        $err = '예약 청구액이 그 사이 바뀌었습니다.';
    else if ($check_room) {
        // 확정은 자리가 있어야 한다. 아직 살아 있는 점유는 booking_booked_count() 가 이미
        // 제 몫으로 세고 있으므로, 만료된 건에 대해서만 잔여를 본다 (return.php 와 같은 판정)
        if (!$room) $err = '객실 정보가 없습니다.';
        else if (strtotime($cur['bk_hold_expire']) < G5_SERVER_TIME) {
            foreach (booking_nights($cur['bk_checkin'], $cur['bk_checkout']) as $date) {
                if (booking_remain_count($room, $date) < 1) {
                    $err = $date.' 은(는) 다른 예약이 차 있어 확정할 수 없습니다. 환불로 처리하십시오.'; break;
                }
            }
        }
    }

    if (!$err) {
        // 잠근 행이지만 where 에 상태를 한 번 더 건다 — 두 관리자가 같은 줄의 버튼을 동시에
        // 눌러도 한쪽만 한 줄을 바꾼다 (이중 확정·이중 환불이 여기서 끊긴다)
        sql_query(" update `{$g5['booking_table']}` set bk_status = 'confirmed',
            bk_tid = '".sql_real_escape_string(trim($log['bl_tid']))."',
            bk_pay_time = '".sql_real_escape_string($pay_time)."'
            where bk_id = '$bk_id' and bk_status = '".sql_real_escape_string($cur['bk_status'])."' ", true);
        if (get_sql_affected_rows() < 1) $err = '예약 상태가 그 사이 바뀌어 처리하지 못했습니다.';
    }

    if ($err) sql_query(" rollback ", true);
    else      sql_query(" commit ", true);
    sql_query(" set autocommit = 1 ", true);   // 어느 갈래로 가든 원래대로 돌려 놓는다

    if ($err) return array('ok' => false, 'msg' => $err);
    return array('ok' => true, 'msg' => '');
}

// 취소·환불 — 돈이 실제로 나가는 유일한 자리. 관리자의 취소 승인과 직권 취소가 모두 여기로 온다.
// 반환은 array('ok' => bool, 'msg' => 안내문, 'refund_price' => 실제 환불액).
//
// 되돌릴 수 없는 호출이라 순서를 이렇게 잡는다:
//   1) 예약 행을 잠그고(for update) 상태를 다시 읽는다. 이미 cancelled 면 여기서 끝난다 —
//      잠금을 API 응답까지 쥐고 있으므로, 두 관리자가 같은 예약의 승인을 동시에 눌러도
//      뒤엣것은 앞엣것이 끝난 뒤에 상태를 읽고 거부된다 (같은 돈을 두 번 환불하지 않는다)
//   2) 이니시스 취소 API 호출 (0원 이하면 부르지 않는다 — 나갈 돈이 없다)
//   3) 성공했을 때만 상태를 옮기고 커밋한다. 실패하면 되돌려 상태를 그대로 둔다(재시도 가능)
//   4) 어느 갈래로 끝나든 거래 로그를 한 줄 남긴다. 트랜잭션 밖에서 남기므로 되돌려도 기록은 남는다
function booking_refund($bk, $refund_price, $memo)
{
    global $g5;

    if (!$bk || !isset($bk['bk_id'])) return array('ok' => false, 'msg' => '예약 정보를 찾을 수 없습니다.', 'refund_price' => 0);
    $bk_id = (int)$bk['bk_id'];

    // 사유는 이니시스 전문(msg)과 예약 행에 함께 들어간다. 줄바꿈은 전문에서 다루기 어려우므로 편다
    $memo = trim(preg_replace('/\s+/u', ' ', (string)$memo));
    if ($memo === '') $memo = '예약 취소';

    $now = date('Y-m-d H:i:s', G5_SERVER_TIME);
    $refund_price = (int)$refund_price;

    $error = '';        // 비어 있지 않으면 실패다
    $called = false;    // 이니시스에 실제로 전문을 보냈는가
    $is_part = false;
    $response = '';
    $result_code = '';
    $total = (int)$bk['bk_total_price'];

    sql_query(" set autocommit = 0 ", true);
    sql_query(" start transaction ", true);

    $cur = sql_fetch(" select * from `{$g5['booking_table']}` where bk_id = '$bk_id' for update ");

    if (!$cur) {
        $error = '예약 정보를 찾을 수 없습니다.';
    } else if ($cur['bk_status'] === 'cancelled') {
        // 중복 승인. API 를 부르기 전에 끊는다
        $error = '이미 취소·환불이 끝난 예약입니다.';
    } else if ($cur['bk_status'] !== 'confirmed' && $cur['bk_status'] !== 'cancel_req') {
        $error = '취소할 수 있는 상태가 아닙니다. (현재 상태: '.$cur['bk_status'].')';
    }

    if (!$error) {
        // 금액은 잠근 행의 값으로 다시 본다 — 화면에서 읽어 온 총액은 낡았을 수 있다
        $total = (int)$cur['bk_total_price'];
        if ($refund_price < 0) $refund_price = 0;
        if ($refund_price > $total) $refund_price = $total;
    }

    if (!$error && $refund_price > 0) {
        $conf = booking_inicis_conf();
        if (trim($cur['bk_tid']) === '') {
            $error = '결제 거래번호(tid)가 없어 환불할 수 없습니다. 이니시스 관리자에서 직접 취소하십시오.';
        } else if ($conf['mid'] === '' || $conf['iniapi_key'] === '') {
            $error = '상점아이디 또는 INIAPI Key 가 설정되지 않아 환불할 수 없습니다.';
        } else {
            // 영카트 취소 함수를 그대로 쓴다. 다만 그 함수는 값이 없으면 $default(쇼핑몰 설정)를
            // 보므로, 상점아이디·키·주소를 모두 예약 설정으로 덮어 $default 를 아예 타지 않게 한다.
            // audit=false 로 영카트 감사 로그도 끈다 — 예약 거래는 booking_inicis_log 에만 남는다
            //
            // function_exists 로 감싼다 — 회귀 테스트가 이 함수를 대신 올려 두는 경우를
            // include_once 는 막지 못한다 (return.php 의 class_exists 와 같은 방식)
            if (!function_exists('inicis_tid_cancel'))
                include_once(G5_SHOP_PATH.'/inicis/libs/inicis_youngcart_fn.php');

            $is_part = ($refund_price < $total);
            $args = array(
                'paymethod' => 'Card',
                'tid'       => trim($cur['bk_tid']),
                'mid'       => $conf['mid'],
                'key'       => $conf['iniapi_key'],
                'url'       => $conf['refund_url'],
                // 해시에 들어가는 값이다. CLI 처럼 SERVER_ADDR 이 없거나 빈 자리에서도 비지 않게 한다
                // (있는지만 보면 안 된다 — 순정 common.php 가 빈 문자열로 채워 두는 경우가 있다)
                'clientIp'  => (isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] !== '')
                               ? $_SERVER['SERVER_ADDR'] : '127.0.0.1',
                'audit'     => false,
                'msg'       => mb_substr($memo, 0, 50, 'UTF-8'),
            );
            if ($is_part) {
                $args['price'] = $refund_price;              // 이번에 돌려줄 돈
                $args['confirmPrice'] = $total - $refund_price;  // 취소 뒤 남는 승인액
            }

            $called = true;
            $response = (string)inicis_tid_cancel($args, $is_part);

            if (!function_exists('json_decode')) require_once(G5_SHOP_PATH.'/inicis/libs/json_lib.php');
            $res = json_decode($response, true);
            $result_code = (is_array($res) && isset($res['resultCode']) && !is_array($res['resultCode']))
                ? (string)$res['resultCode'] : '';
            $result_msg = (is_array($res) && isset($res['resultMsg']) && !is_array($res['resultMsg']))
                ? clean_xss_tags((string)$res['resultMsg']) : '';

            // 취소 성공은 '00' 이다. 승인(return.php)의 '0000' 과는 다른 코드계다 —
            // 여기서 '0000' 을 성공으로 보면 나가지 않은 돈을 나갔다고 적는다
            if (strcmp('00', $result_code) !== 0) {
                if ($result_code === '') {
                    $result_code = 'parse';
                    $error = '이니시스 취소 응답을 해석할 수 없습니다. 거래 로그를 확인하십시오.';
                } else {
                    $error = '이니시스 취소에 실패했습니다. ('.$result_code
                           . ($result_msg !== '' ? ' '.$result_msg : '').')';
                }
            }
        }
    }

    if (!$error) {
        $set = " bk_status = 'cancelled',
            bk_refund_price = '".(int)$refund_price."',
            bk_refund_time = '$now' ";
        // 직권 취소는 취소 신청을 거치지 않는다 — 취소 시각이 비어 있으면 여기서 찍는다
        if ($cur['bk_cancel_time'] <= '1970-01-02') $set .= " , bk_cancel_time = '$now' ";
        // 손님이 적어 둔 사유가 있으면 남긴다. 비어 있을 때만 처리 사유를 적는다
        if (trim($cur['bk_cancel_memo']) === '')
            $set .= " , bk_cancel_memo = '".sql_real_escape_string(mb_substr($memo, 0, 255, 'UTF-8'))."' ";
        // 잠근 행이지만 where 에 기대 상태를 한 번 더 건다 — 상태가 옮겨 갔다면 한 줄도 바뀌지 않는다
        sql_query(" update `{$g5['booking_table']}` set $set
            where bk_id = '$bk_id' and bk_status = '".sql_real_escape_string($cur['bk_status'])."' ", true);
        if (get_sql_affected_rows() < 1) {
            $result_code = 'update';
            $error = $called
                ? '이니시스 환불은 처리되었으나 예약 상태를 바꾸지 못했습니다. 거래 로그를 확인하고 손으로 맞춰 주십시오.'
                : '예약 상태가 바뀌어 취소를 마치지 못했습니다.';
        }
    }

    if ($error) sql_query(" rollback ", true);
    else        sql_query(" commit ", true);
    sql_query(" set autocommit = 1 ", true);   // 어느 갈래로 가든 원래대로 돌려 놓는다

    // 로그는 트랜잭션 밖이다. 되돌린 시도도, 부르지 않은 이유도 남아야 대사(對査)가 된다
    booking_inicis_log(
        $cur ? $cur['bk_oid'] : $bk['bk_oid'],
        $cur ? $cur['bk_tid'] : (isset($bk['bk_tid']) ? $bk['bk_tid'] : ''),
        'refund', $refund_price,
        $called ? $result_code : ($error ? 'reject' : 'skip'),
        json_encode(array(
            'bk_no'    => $cur ? $cur['bk_no'] : $bk['bk_no'],
            'status'   => $cur ? $cur['bk_status'] : '',
            'total'    => $total,
            'refund'   => $refund_price,
            'part'     => $is_part ? 1 : 0,
            'memo'     => $memo,
            'error'    => $error,
            'response' => $response,
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    if ($error) return array('ok' => false, 'msg' => $error, 'refund_price' => 0);

    booking_send_mail($bk_id, 'cancelled');
    return array('ok' => true, 'refund_price' => $refund_price,
        'msg' => '취소 처리되었습니다. 환불액 '.number_format($refund_price).'원');
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
    $subject = '['.$config['cf_title'].'] '.$titles[$kind].' ('.$bk['bk_no'].')';
    $content = '<p>'.get_text($bk['bk_name']).'님, '.$titles[$kind].'.</p><ul>'
        .'<li>예약번호: '.get_text($bk['bk_no']).'</li>'
        .'<li>객실: '.get_text($room ? $room['br_subject'] : '').'</li>'
        .'<li>기간: '.$bk['bk_checkin'].' ~ '.$bk['bk_checkout'].' ('.count(booking_nights($bk['bk_checkin'], $bk['bk_checkout'])).'박)</li>'
        .'<li>인원: '.(int)$bk['bk_person'].'명</li>'
        .'<li>결제금액: '.number_format((int)$bk['bk_total_price']).'원</li>'
        .'<li>상태: '.booking_status_label($bk['bk_status']).'</li>'
        .'</ul>';
    // 확정 메일에는 조회 화면으로 가는 길을 함께 넣는다. 비회원에게는 이 메일이
    // 사실상 유일한 입구다 — 예약번호와 확인 비밀번호로 상세를 열고 추가 요청·취소를 한다
    if ($kind === 'confirm') {
        $lookup_url = G5_URL.'/booking/lookup.php';
        $content .= '<p>예약 확인·변경·취소: <a href="'.$lookup_url.'">'.$lookup_url.'</a></p>';
    }
    if ($bk['bk_email']) @mailer($config['cf_title'], $config['cf_admin_email'], $bk['bk_email'], $subject, $content, 1);
    if ($bc['bc_admin_email']) @mailer($config['cf_title'], $config['cf_admin_email'], $bc['bc_admin_email'], $subject, $content, 1);
}
