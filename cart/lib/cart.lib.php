<?php
if (!defined('_GNUBOARD_')) exit;

// 테이블명 상수 — dbconfig.php 를 건드리지 않고 모듈이 자체 정의(부킹 관례)
function cart_table_defaults()
{
    global $g5;
    $tables = array(
        'cart_config_table'     => 'cart_config',
        'cart_category_table'   => 'cart_category',
        'cart_item_table'       => 'cart_item',
        'cart_item_image_table' => 'cart_item_image',
        'cart_sku_table'        => 'cart_sku',
        'cart_stock_log_table'  => 'cart_stock_log',
        'cart_basket_table'     => 'cart_basket',
        'cart_order_table'      => 'cart_order',
        'cart_order_item_table' => 'cart_order_item',
        'cart_payment_table'    => 'cart_payment',
    );
    foreach ($tables as $key => $name) {
        if (!isset($g5[$key])) $g5[$key] = G5_TABLE_PREFIX.$name;
    }
}
cart_table_defaults();

// 버전을 거치며 늘어난 컬럼 — 새 컬럼은 CREATE DDL 과 여기 두 곳에 같이 적는다
// (새 설치는 CREATE 가, 기존 설치는 여기가 맡는다 — 부킹 booking_column_upgrades 관례)
function cart_column_upgrades()
{
    return array(
        // 2026-08-06 2단계 배송비 정책 — 몰 전역 단일 정책(운영 단순화, 상품별 정책은 요구 생기면)
        array('cart_config_table', 'cc_ship_base',
            " ADD `cc_ship_base` int(11) NOT NULL DEFAULT '3000' AFTER `cc_id` "),
        array('cart_config_table', 'cc_ship_free',
            " ADD `cc_ship_free` int(11) NOT NULL DEFAULT '50000' AFTER `cc_ship_base` "),
        array('cart_config_table', 'cc_ship_jeju',
            " ADD `cc_ship_jeju` int(11) NOT NULL DEFAULT '3000' AFTER `cc_ship_free` "),
        // 2026-08-06 무통장 입금 안내(계좌 문구) — 결제 단계에서 표시
        array('cart_config_table', 'cc_bank',
            " ADD `cc_bank` varchar(255) NOT NULL DEFAULT '' AFTER `cc_ship_jeju` "),
    );
}

// 모듈 자체 설치/업그레이드 — 순정 설치·dbupgrade 와 무관하게 멱등 실행
function cart_install()
{
    global $g5;
    $created = cart_create_tables();

    $altered = array();
    foreach (cart_column_upgrades() as $up) {
        list($table_key, $column, $add) = $up;
        if (sql_fetch(" SHOW COLUMNS FROM `{$g5[$table_key]}` LIKE '$column' ")) continue;
        sql_query(" ALTER TABLE `{$g5[$table_key]}` $add ", true);
        $altered[] = $g5[$table_key].'.'.$column;
    }

    // 상품 검색용 FULLTEXT ngram — 서버가 지원 안 하면 조용히 넘어가고
    // cart_ft_available() 이 LIKE 폴백을 고른다 (MySQL 5.7+ InnoDB ngram)
    if (!sql_fetch(" SHOW INDEX FROM `{$g5['cart_item_table']}` WHERE Key_name = 'ft_search' ")) {
        sql_query(" ALTER TABLE `{$g5['cart_item_table']}`
            ADD FULLTEXT KEY `ft_search` (`it_name`, `it_keyword`) WITH PARSER ngram ", false);
    }
    return array('created' => $created, 'altered' => $altered);
}

function cart_installed()
{
    global $g5;
    return (bool)sql_query(" DESC `{$g5['cart_config_table']}` ", false);
}

function cart_create_tables()
{
    global $g5;
    $created = false;
    foreach (cart_table_ddl() as $key => $ddl) {
        if (sql_query(" DESC `{$g5[$key]}` ", false)) continue;
        sql_query($ddl, true);
        $created = true;
    }
    return $created;
}

function cart_table_ddl()
{
    global $g5;
    return array(
    'cart_config_table' => " CREATE TABLE IF NOT EXISTS `{$g5['cart_config_table']}` (
        `cc_id` tinyint(4) NOT NULL DEFAULT '1',
        `cc_ship_base` int(11) NOT NULL DEFAULT '3000',
        `cc_ship_free` int(11) NOT NULL DEFAULT '50000',
        `cc_ship_jeju` int(11) NOT NULL DEFAULT '3000',
        `cc_bank` varchar(255) NOT NULL DEFAULT '',
        PRIMARY KEY (`cc_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    'cart_category_table' => " CREATE TABLE IF NOT EXISTS `{$g5['cart_category_table']}` (
        `ca_id` int(11) NOT NULL AUTO_INCREMENT,
        `ca_parent` int(11) NOT NULL DEFAULT '0',
        `ca_name` varchar(100) NOT NULL DEFAULT '',
        `ca_path` varchar(100) NOT NULL DEFAULT '/',
        `ca_depth` tinyint(4) NOT NULL DEFAULT '1',
        `ca_order` int(11) NOT NULL DEFAULT '0',
        `ca_show` tinyint(4) NOT NULL DEFAULT '1',
        PRIMARY KEY (`ca_id`), KEY `ca_parent` (`ca_parent`), KEY `ca_path` (`ca_path`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    'cart_item_table' => " CREATE TABLE IF NOT EXISTS `{$g5['cart_item_table']}` (
        `it_id` int(11) NOT NULL AUTO_INCREMENT,
        `it_code` varchar(50) NOT NULL DEFAULT '',
        `ca_id` int(11) NOT NULL DEFAULT '0',
        `it_name` varchar(255) NOT NULL DEFAULT '',
        `it_keyword` varchar(255) NOT NULL DEFAULT '',
        `it_content` mediumtext NOT NULL,
        `it_show` tinyint(4) NOT NULL DEFAULT '1',
        `it_price` int(11) NOT NULL DEFAULT '0',
        `it_stock` int(11) NOT NULL DEFAULT '0',
        `it_review_cnt` int(11) NOT NULL DEFAULT '0',
        `it_shipping_id` int(11) NOT NULL DEFAULT '0',
        `it_datetime` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        `it_update` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        PRIMARY KEY (`it_id`),
        UNIQUE KEY `it_code` (`it_code`),
        KEY `list_new` (`ca_id`, `it_show`, `it_id`),
        KEY `list_price` (`ca_id`, `it_show`, `it_price`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    'cart_item_image_table' => " CREATE TABLE IF NOT EXISTS `{$g5['cart_item_image_table']}` (
        `im_id` int(11) NOT NULL AUTO_INCREMENT,
        `it_id` int(11) NOT NULL DEFAULT '0',
        `im_file` varchar(255) NOT NULL DEFAULT '',
        `im_order` int(11) NOT NULL DEFAULT '0',
        `im_main` tinyint(4) NOT NULL DEFAULT '0',
        PRIMARY KEY (`im_id`), KEY `it_id` (`it_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    'cart_sku_table' => " CREATE TABLE IF NOT EXISTS `{$g5['cart_sku_table']}` (
        `sk_id` int(11) NOT NULL AUTO_INCREMENT,
        `it_id` int(11) NOT NULL DEFAULT '0',
        `sk_code` varchar(50) NOT NULL DEFAULT '',
        `sk_option` varchar(255) NOT NULL DEFAULT '{}',
        `sk_price` int(11) NOT NULL DEFAULT '0',
        `sk_qty` int(11) NOT NULL DEFAULT '0',
        `sk_barcode` varchar(50) NOT NULL DEFAULT '',
        `sk_use` tinyint(4) NOT NULL DEFAULT '1',
        PRIMARY KEY (`sk_id`),
        UNIQUE KEY `sk_code` (`sk_code`),
        KEY `it_id` (`it_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    'cart_stock_log_table' => " CREATE TABLE IF NOT EXISTS `{$g5['cart_stock_log_table']}` (
        `sl_id` int(11) NOT NULL AUTO_INCREMENT,
        `sk_id` int(11) NOT NULL DEFAULT '0',
        `it_id` int(11) NOT NULL DEFAULT '0',
        `sl_diff` int(11) NOT NULL DEFAULT '0',
        `sl_after` int(11) NOT NULL DEFAULT '0',
        `sl_reason` varchar(20) NOT NULL DEFAULT '',
        `sl_ref` varchar(50) NOT NULL DEFAULT '',
        `sl_who` varchar(50) NOT NULL DEFAULT '',
        `sl_datetime` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        PRIMARY KEY (`sl_id`), KEY `sk_id` (`sk_id`, `sl_id`), KEY `it_id` (`it_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    'cart_basket_table' => " CREATE TABLE IF NOT EXISTS `{$g5['cart_basket_table']}` (
        `bk_id` int(11) NOT NULL AUTO_INCREMENT,
        `mb_id` varchar(20) NOT NULL DEFAULT '',
        `bk_sid` varchar(64) NOT NULL DEFAULT '',
        `sk_id` int(11) NOT NULL DEFAULT '0',
        `bk_qty` int(11) NOT NULL DEFAULT '1',
        `bk_datetime` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        PRIMARY KEY (`bk_id`),
        UNIQUE KEY `owner_sku` (`mb_id`, `bk_sid`, `sk_id`),
        KEY `owner` (`mb_id`, `bk_sid`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    'cart_order_table' => " CREATE TABLE IF NOT EXISTS `{$g5['cart_order_table']}` (
        `od_id` int(11) NOT NULL AUTO_INCREMENT,
        `od_no` varchar(30) NOT NULL DEFAULT '',
        `mb_id` varchar(20) NOT NULL DEFAULT '',
        `od_name` varchar(50) NOT NULL DEFAULT '',
        `od_hp` varchar(20) NOT NULL DEFAULT '',
        `od_email` varchar(100) NOT NULL DEFAULT '',
        `od_zip` varchar(10) NOT NULL DEFAULT '',
        `od_addr1` varchar(255) NOT NULL DEFAULT '',
        `od_addr2` varchar(255) NOT NULL DEFAULT '',
        `od_memo` varchar(255) NOT NULL DEFAULT '',
        `od_item_total` int(11) NOT NULL DEFAULT '0',
        `od_ship_fee` int(11) NOT NULL DEFAULT '0',
        `od_coupon` int(11) NOT NULL DEFAULT '0',
        `od_point` int(11) NOT NULL DEFAULT '0',
        `od_total` int(11) NOT NULL DEFAULT '0',
        `od_status` varchar(20) NOT NULL DEFAULT 'unpaid',
        `od_pay_method` varchar(20) NOT NULL DEFAULT 'bank',
        `od_depositor` varchar(50) NOT NULL DEFAULT '',
        `od_guest_pw` varchar(255) NOT NULL DEFAULT '',
        `od_ip` varchar(50) NOT NULL DEFAULT '',
        `od_datetime` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        `od_paid_at` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        PRIMARY KEY (`od_id`),
        UNIQUE KEY `od_no` (`od_no`),
        KEY `mb_id` (`mb_id`, `od_id`),
        KEY `status` (`od_status`, `od_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    'cart_order_item_table' => " CREATE TABLE IF NOT EXISTS `{$g5['cart_order_item_table']}` (
        `oi_id` int(11) NOT NULL AUTO_INCREMENT,
        `od_id` int(11) NOT NULL DEFAULT '0',
        `it_id` int(11) NOT NULL DEFAULT '0',
        `sk_id` int(11) NOT NULL DEFAULT '0',
        `oi_name` varchar(255) NOT NULL DEFAULT '',
        `oi_option` varchar(255) NOT NULL DEFAULT '',
        `oi_price` int(11) NOT NULL DEFAULT '0',
        `oi_qty` int(11) NOT NULL DEFAULT '0',
        `oi_total` int(11) NOT NULL DEFAULT '0',
        `oi_status` varchar(20) NOT NULL DEFAULT 'normal',
        PRIMARY KEY (`oi_id`), KEY `od_id` (`od_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    'cart_payment_table' => " CREATE TABLE IF NOT EXISTS `{$g5['cart_payment_table']}` (
        `pm_id` int(11) NOT NULL AUTO_INCREMENT,
        `od_id` int(11) NOT NULL DEFAULT '0',
        `pm_method` varchar(20) NOT NULL DEFAULT '',
        `pm_tid` varchar(100) NOT NULL DEFAULT '',
        `pm_amount` int(11) NOT NULL DEFAULT '0',
        `pm_status` varchar(20) NOT NULL DEFAULT 'ready',
        `pm_data` text NOT NULL,
        `pm_datetime` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        `pm_approved_at` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        PRIMARY KEY (`pm_id`), KEY `od_id` (`od_id`), KEY `pm_tid` (`pm_tid`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    );
}

// 설정 단일 행 — 없으면 기본 행을 만든다
function cart_config()
{
    global $g5;
    static $row = null;
    if ($row !== null) return $row;
    $row = sql_fetch(" select * from `{$g5['cart_config_table']}` where cc_id = 1 ");
    if (!$row) {
        sql_query(" insert into `{$g5['cart_config_table']}` (cc_id) values (1) ", true);
        $row = sql_fetch(" select * from `{$g5['cart_config_table']}` where cc_id = 1 ");
    }
    return $row;
}

function cart_url($path = '', $qs = array())
{
    $url = G5_URL.'/cart/'.$path;
    if ($qs) $url .= '?'.http_build_query($qs);
    return $url;
}

// FULLTEXT ngram 인덱스가 실제로 만들어졌는지 — 검색이 MATCH/LIKE 를 고르는 기준
function cart_ft_available()
{
    global $g5;
    static $ok = null;
    if ($ok === null) {
        $ok = (bool)sql_fetch(" SHOW INDEX FROM `{$g5['cart_item_table']}`
            WHERE Key_name = 'ft_search' ");
    }
    return $ok;
}

// 상품 이미지 저장 경로 — 한 디렉터리에 수만 파일이 쌓이지 않게 1000개 단위 분산
function cart_item_image_dir($it_id)
{
    return G5_DATA_PATH.'/cart/item/'.sprintf('%03d', (int)($it_id / 1000));
}

function cart_item_image_url($file)
{
    return G5_DATA_URL.'/cart/item/'.$file;   // im_file 은 '003/1234_abc.jpg' 형태로 저장
}
