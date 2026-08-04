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
