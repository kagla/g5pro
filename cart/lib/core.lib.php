<?php
if (!defined('_GNUBOARD_')) exit;

// ---------- 모듈 코어 ----------
// 테이블 정의·설치/업그레이드·설정·URL 등 다른 lib 이 모두 기대는 바닥. 가장 먼저 include 한다.
// (장바구니 로직은 cart.lib.php 에 있다)

// 모듈 디렉토리 상수 — 순정 G5_BBS_DIR/G5_ADMIN_DIR 관례 그대로 DIR 하나에서 PATH·URL 을 판다.
// 디렉토리 이름을 바꾸려면 G5_CART_DIR 만 바꾸면 된다(예: config.php 나 extend 에서 선행 정의).
// 부트스트랩 include 경로(cart/_common.php)만은 상수보다 먼저라 리터럴로 남는다.
if (!defined('G5_CART_DIR'))        define('G5_CART_DIR', 'cart');
if (!defined('G5_CART_PATH'))       define('G5_CART_PATH', G5_PATH.'/'.G5_CART_DIR);
if (!defined('G5_CART_URL'))        define('G5_CART_URL', G5_URL.'/'.G5_CART_DIR);
if (!defined('G5_CART_LIB_PATH'))   define('G5_CART_LIB_PATH', G5_CART_PATH.'/lib');
if (!defined('G5_CART_ADMIN_PATH')) define('G5_CART_ADMIN_PATH', G5_ADMIN_PATH.'/'.G5_CART_DIR);
if (!defined('G5_CART_ADMIN_URL'))  define('G5_CART_ADMIN_URL', G5_ADMIN_URL.'/'.G5_CART_DIR);
// 업로드·임시 파일이 사는 곳 (data/cart/…)
if (!defined('G5_CART_DATA_PATH'))  define('G5_CART_DATA_PATH', G5_DATA_PATH.'/'.G5_CART_DIR);
if (!defined('G5_CART_DATA_URL'))   define('G5_CART_DATA_URL', G5_DATA_URL.'/'.G5_CART_DIR);

// 테이블명 상수 — dbconfig.php 를 건드리지 않고 모듈이 자체 정의(부킹 관례)
function cart_table_defaults()
{
    global $g5;
    $tables = array(
        'ycart_config_table'     => 'ycart_config',
        'ycart_category_table'   => 'ycart_category',
        'ycart_item_table'       => 'ycart_item',
        'ycart_item_image_table' => 'ycart_item_image',
        'ycart_item_category_table' => 'ycart_item_category',
        'ycart_sku_table'        => 'ycart_sku',
        'ycart_stock_log_table'  => 'ycart_stock_log',
        'ycart_cart_table'       => 'ycart_cart',
        'ycart_order_table'      => 'ycart_order',
        'ycart_order_item_table' => 'ycart_order_item',
        'ycart_payment_table'    => 'ycart_payment',
        'ycart_address_table'    => 'ycart_address',
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
        array('ycart_config_table', 'cc_ship_base',
            " ADD `cc_ship_base` int(11) NOT NULL DEFAULT '3000' AFTER `cc_id` "),
        array('ycart_config_table', 'cc_ship_free',
            " ADD `cc_ship_free` int(11) NOT NULL DEFAULT '50000' AFTER `cc_ship_base` "),
        array('ycart_config_table', 'cc_ship_jeju',
            " ADD `cc_ship_jeju` int(11) NOT NULL DEFAULT '3000' AFTER `cc_ship_free` "),
        // 2026-08-06 무통장 입금 안내(계좌 문구) — 결제 단계에서 표시
        array('ycart_config_table', 'cc_bank',
            " ADD `cc_bank` varchar(255) NOT NULL DEFAULT '' AFTER `cc_ship_jeju` "),
        // 2026-08-06 PG 설정 — 키가 채워진 PG 만 결제수단으로 노출된다. 값은 DB 에만(공개 저장소).
        array('ycart_config_table', 'cc_inicis_mid',
            " ADD `cc_inicis_mid` varchar(20) NOT NULL DEFAULT '' AFTER `cc_bank` "),
        array('ycart_config_table', 'cc_inicis_signkey',
            " ADD `cc_inicis_signkey` varchar(100) NOT NULL DEFAULT '' AFTER `cc_inicis_mid` "),
        array('ycart_config_table', 'cc_toss_ckey',
            " ADD `cc_toss_ckey` varchar(100) NOT NULL DEFAULT '' AFTER `cc_inicis_signkey` "),
        array('ycart_config_table', 'cc_toss_skey',
            " ADD `cc_toss_skey` varchar(100) NOT NULL DEFAULT '' AFTER `cc_toss_ckey` "),
        // 2026-08-06 PG 주문번호(oid) — 결제 시도마다 새로 발급(부킹 교훈: oid 재사용 금지)
        array('ycart_order_table', 'od_oid',
            " ADD `od_oid` varchar(40) NOT NULL DEFAULT '' AFTER `od_no`, ADD KEY `od_oid` (`od_oid`) "),
        // 2026-08-06 PG 초안(draft) 주문이 결제 확정 때 비울 장바구니 행 목록(CSV)
        array('ycart_order_table', 'od_ct_ids',
            " ADD `od_ct_ids` varchar(255) NOT NULL DEFAULT '' AFTER `od_guest_pw` "),
        // 2026-08-06 분류 설정 — 이미지(스토어홈 칩)·설명(목록 소개문)·기본 정렬
        array('ycart_category_table', 'ca_img',
            " ADD `ca_img` varchar(255) NOT NULL DEFAULT '' AFTER `ca_name` "),
        array('ycart_category_table', 'ca_desc',
            " ADD `ca_desc` varchar(500) NOT NULL DEFAULT '' AFTER `ca_img` "),
        array('ycart_category_table', 'ca_sort',
            " ADD `ca_sort` varchar(10) NOT NULL DEFAULT '' AFTER `ca_desc` "),
        // 2026-08-06 관리자 취소 — INIAPI 환불 키, 취소 사유·시각
        array('ycart_config_table', 'cc_inicis_apikey',
            " ADD `cc_inicis_apikey` varchar(100) NOT NULL DEFAULT '' AFTER `cc_inicis_signkey` "),
        array('ycart_order_table', 'od_cancel_reason',
            " ADD `od_cancel_reason` varchar(255) NOT NULL DEFAULT '' AFTER `od_shipped_at` "),
        array('ycart_order_table', 'od_canceled_at',
            " ADD `od_canceled_at` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' AFTER `od_cancel_reason` "),
        array('ycart_order_table', 'od_canceled_by',
            " ADD `od_canceled_by` varchar(20) NOT NULL DEFAULT '' AFTER `od_canceled_at` "),
        // 2026-08-06 주소록 — 배송지에 주문자(이름·연락처)도 함께 저장해 불러오기가 한 번에 채운다
        array('ycart_address_table', 'ad_name',
            " ADD `ad_name` varchar(50) NOT NULL DEFAULT '' AFTER `mb_id` "),
        array('ycart_address_table', 'ad_hp',
            " ADD `ad_hp` varchar(20) NOT NULL DEFAULT '' AFTER `ad_name` "),
        // 2026-08-06 수령인 — 주문자와 다를 수 있다(선물). 화면은 "주문자와 동일" 체크가 기본.
        array('ycart_order_table', 'od_recv_name',
            " ADD `od_recv_name` varchar(50) NOT NULL DEFAULT '' AFTER `od_email` "),
        array('ycart_order_table', 'od_recv_hp',
            " ADD `od_recv_hp` varchar(20) NOT NULL DEFAULT '' AFTER `od_recv_name` "),
        // 2026-08-06 3단계 배송 — 택배사·송장·발송 시각
        array('ycart_order_table', 'od_delivery_company',
            " ADD `od_delivery_company` varchar(50) NOT NULL DEFAULT '' AFTER `od_ct_ids` "),
        array('ycart_order_table', 'od_invoice',
            " ADD `od_invoice` varchar(50) NOT NULL DEFAULT '' AFTER `od_delivery_company` "),
        array('ycart_order_table', 'od_shipped_at',
            " ADD `od_shipped_at` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' AFTER `od_invoice` "),
        // 2026-08-07 분류코드 — 사람이 쓰는 식별자(프론트 URL·CSV). 내부 키·FK 는 ca_id 그대로.
        // UNIQUE 는 여기 안 넣는다 — 기존 행이 전부 빈 값이라 채운 뒤 cart_install() 이 붙인다.
        array('ycart_category_table', 'ca_code',
            " ADD `ca_code` varchar(30) NOT NULL DEFAULT '' AFTER `ca_parent` "),
    );
}

// 모듈 자체 설치/업그레이드 — 순정 설치·dbupgrade 와 무관하게 멱등 실행
function cart_install()
{
    global $g5;

    // 2026-08-07 테이블 개명 — 빈 새 테이블을 만들기 전에 옛 이름이 남아 있으면 데이터째 넘긴다.
    // 두 세대를 함께 처리한다: cart_* → ycart_*(접두어 통일), cart_basket → ycart_cart(장바구니).
    // 새 이름이 이미 있으면 옛 것은 손대지 않는다(둘 다 있는 상황은 사람이 확인할 몫).
    $legacy_tables = array(
        'cart_config' => 'ycart_config', 'cart_category' => 'ycart_category',
        'cart_item' => 'ycart_item', 'cart_item_image' => 'ycart_item_image',
        'cart_item_category' => 'ycart_item_category', 'cart_sku' => 'ycart_sku',
        'cart_stock_log' => 'ycart_stock_log', 'cart_basket' => 'ycart_cart',
        'cart_cart' => 'ycart_cart', 'cart_order' => 'ycart_order',
        'cart_order_item' => 'ycart_order_item', 'cart_payment' => 'ycart_payment',
        'cart_address' => 'ycart_address',
    );
    foreach ($legacy_tables as $old => $new) {
        $old_table = G5_TABLE_PREFIX.$old;
        $new_table = G5_TABLE_PREFIX.$new;
        if (sql_query(" DESC `$old_table` ", false) && !sql_query(" DESC `$new_table` ", false)) {
            sql_query(" RENAME TABLE `$old_table` TO `$new_table` ", true);
        }
    }

    $created = cart_create_tables();

    // 2026-08-07 컬럼 접두사 개명(bk_* → ct_*) — 테이블 개명과 짝. 컬럼 추가(위 upgrades)보다
    // 먼저 돌아야 od_ct_ids 가 "없는 컬럼"으로 오인돼 중복 생성되지 않는다.
    $ct_renames = array(
        array('ycart_cart_table', 'bk_id', " CHANGE `bk_id` `ct_id` int(11) NOT NULL AUTO_INCREMENT "),
        array('ycart_cart_table', 'bk_sid', " CHANGE `bk_sid` `ct_sid` varchar(64) NOT NULL DEFAULT '' "),
        array('ycart_cart_table', 'bk_qty', " CHANGE `bk_qty` `ct_qty` int(11) NOT NULL DEFAULT '1' "),
        array('ycart_cart_table', 'bk_datetime',
            " CHANGE `bk_datetime` `ct_datetime` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' "),
        array('ycart_order_table', 'od_bk_ids',
            " CHANGE `od_bk_ids` `od_ct_ids` varchar(255) NOT NULL DEFAULT '' "),
    );
    foreach ($ct_renames as $rn) {
        list($table_key, $old_col, $change) = $rn;
        if (!sql_fetch(" SHOW COLUMNS FROM `{$g5[$table_key]}` LIKE '$old_col' ")) continue;
        sql_query(" ALTER TABLE `{$g5[$table_key]}` $change ", true);
    }

    $altered = array();
    foreach (cart_column_upgrades() as $up) {
        list($table_key, $column, $add) = $up;
        if (sql_fetch(" SHOW COLUMNS FROM `{$g5[$table_key]}` LIKE '$column' ")) continue;
        sql_query(" ALTER TABLE `{$g5[$table_key]}` $add ", true);
        $altered[] = $g5[$table_key].'.'.$column;
    }

    // 상품 검색용 FULLTEXT ngram — 서버가 지원 안 하면 조용히 넘어가고
    // cart_ft_available() 이 LIKE 폴백을 고른다 (MySQL 5.7+ InnoDB ngram)
    if (!sql_fetch(" SHOW INDEX FROM `{$g5['ycart_item_table']}` WHERE Key_name = 'ft_search' ")) {
        sql_query(" ALTER TABLE `{$g5['ycart_item_table']}`
            ADD FULLTEXT KEY `ft_search` (`it_name`, `it_keyword`) WITH PARSER ngram ", false);
    }

    // 2026-08-07 상품-분류 다대다 — ① 빈 분류코드 채움 ② 그 뒤에야 UNIQUE 가능
    // ③ 기존 단일 ca_id 소속을 연결 테이블로 이관(멱등). ca_id 컬럼 제거(수축)는 전 경로
    // 전환이 끝난 뒤 별도 커밋에서 한다 — 그때까지 두 곳이 공존해도 읽기는 안 깨진다.
    $result = sql_query(" select ca_id from `{$g5['ycart_category_table']}` where ca_code = '' ", true);
    while ($row = sql_fetch_array($result)) {
        sql_query(" update `{$g5['ycart_category_table']}`
            set ca_code = '".cart_category_code_generate()."'
            where ca_id = '".(int)$row['ca_id']."' ", true);
    }
    if (!sql_fetch(" SHOW INDEX FROM `{$g5['ycart_category_table']}` WHERE Key_name = 'ca_code' ")) {
        sql_query(" ALTER TABLE `{$g5['ycart_category_table']}` ADD UNIQUE KEY `ca_code` (`ca_code`) ", true);
    }
    // 2026-08-07 분류코드 20→30자 — 늘리기만 하므로 기존 값은 그대로다(줄이면 잘린다).
    // 컬럼 추가(cart_column_upgrades)는 "있으면 건너뛰기"라 이미 있는 컬럼의 폭은 여기서 넓힌다.
    $col = sql_fetch(" SHOW COLUMNS FROM `{$g5['ycart_category_table']}` LIKE 'ca_code' ");
    if ($col && preg_match('/varchar\((\d+)\)/i', $col['Type'], $m) && (int)$m[1] < CART_CA_CODE_MAX) {
        sql_query(" ALTER TABLE `{$g5['ycart_category_table']}`
            MODIFY `ca_code` varchar(".CART_CA_CODE_MAX.") NOT NULL DEFAULT '' ", true);
    }
    if (sql_fetch(" SHOW COLUMNS FROM `{$g5['ycart_item_table']}` LIKE 'ca_id' ")) {
        sql_query(" insert ignore into `{$g5['ycart_item_category_table']}` (it_id, ca_id)
            select it_id, ca_id from `{$g5['ycart_item_table']}` where ca_id > 0 ", true);
        // 전 경로 전환 완료 후 수축 — 마지막 backfill 직후라 데이터 손실 없음
        sql_query(" ALTER TABLE `{$g5['ycart_item_table']}`
            DROP KEY `list_new`, DROP KEY `list_price`, DROP COLUMN `ca_id` ", true);
        sql_query(" ALTER TABLE `{$g5['ycart_item_table']}`
            ADD KEY `list_new` (`it_show`, `it_id`),
            ADD KEY `list_price` (`it_show`, `it_price`) ", true);
    }
    return array('created' => $created, 'altered' => $altered);
}

function cart_installed()
{
    global $g5;
    return (bool)sql_query(" DESC `{$g5['ycart_config_table']}` ", false);
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
    'ycart_config_table' => " CREATE TABLE IF NOT EXISTS `{$g5['ycart_config_table']}` (
        `cc_id` tinyint(4) NOT NULL DEFAULT '1',
        `cc_ship_base` int(11) NOT NULL DEFAULT '3000',
        `cc_ship_free` int(11) NOT NULL DEFAULT '50000',
        `cc_ship_jeju` int(11) NOT NULL DEFAULT '3000',
        `cc_bank` varchar(255) NOT NULL DEFAULT '',
        `cc_inicis_mid` varchar(20) NOT NULL DEFAULT '',
        `cc_inicis_signkey` varchar(100) NOT NULL DEFAULT '',
        `cc_inicis_apikey` varchar(100) NOT NULL DEFAULT '',
        `cc_toss_ckey` varchar(100) NOT NULL DEFAULT '',
        `cc_toss_skey` varchar(100) NOT NULL DEFAULT '',
        PRIMARY KEY (`cc_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    'ycart_category_table' => " CREATE TABLE IF NOT EXISTS `{$g5['ycart_category_table']}` (
        `ca_id` int(11) NOT NULL AUTO_INCREMENT,
        `ca_parent` int(11) NOT NULL DEFAULT '0',
        `ca_code` varchar(30) NOT NULL DEFAULT '',
        `ca_name` varchar(100) NOT NULL DEFAULT '',
        `ca_img` varchar(255) NOT NULL DEFAULT '',
        `ca_desc` varchar(500) NOT NULL DEFAULT '',
        `ca_sort` varchar(10) NOT NULL DEFAULT '',
        `ca_path` varchar(100) NOT NULL DEFAULT '/',
        `ca_depth` tinyint(4) NOT NULL DEFAULT '1',
        `ca_order` int(11) NOT NULL DEFAULT '0',
        `ca_show` tinyint(4) NOT NULL DEFAULT '1',
        PRIMARY KEY (`ca_id`), UNIQUE KEY `ca_code` (`ca_code`),
        KEY `ca_parent` (`ca_parent`), KEY `ca_path` (`ca_path`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    'ycart_item_table' => " CREATE TABLE IF NOT EXISTS `{$g5['ycart_item_table']}` (
        `it_id` int(11) NOT NULL AUTO_INCREMENT,
        `it_code` varchar(50) NOT NULL DEFAULT '',
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
        KEY `list_new` (`it_show`, `it_id`),
        KEY `list_price` (`it_show`, `it_price`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    'ycart_item_image_table' => " CREATE TABLE IF NOT EXISTS `{$g5['ycart_item_image_table']}` (
        `im_id` int(11) NOT NULL AUTO_INCREMENT,
        `it_id` int(11) NOT NULL DEFAULT '0',
        `im_file` varchar(255) NOT NULL DEFAULT '',
        `im_order` int(11) NOT NULL DEFAULT '0',
        `im_main` tinyint(4) NOT NULL DEFAULT '0',
        PRIMARY KEY (`im_id`), KEY `it_id` (`it_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    'ycart_item_category_table' => " CREATE TABLE IF NOT EXISTS `{$g5['ycart_item_category_table']}` (
        `it_id` int(11) NOT NULL,
        `ca_id` int(11) NOT NULL,
        PRIMARY KEY (`it_id`, `ca_id`),
        KEY `ca_id` (`ca_id`, `it_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    'ycart_sku_table' => " CREATE TABLE IF NOT EXISTS `{$g5['ycart_sku_table']}` (
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
    'ycart_stock_log_table' => " CREATE TABLE IF NOT EXISTS `{$g5['ycart_stock_log_table']}` (
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
    'ycart_cart_table' => " CREATE TABLE IF NOT EXISTS `{$g5['ycart_cart_table']}` (
        `ct_id` int(11) NOT NULL AUTO_INCREMENT,
        `mb_id` varchar(20) NOT NULL DEFAULT '',
        `ct_sid` varchar(64) NOT NULL DEFAULT '',
        `sk_id` int(11) NOT NULL DEFAULT '0',
        `ct_qty` int(11) NOT NULL DEFAULT '1',
        `ct_datetime` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        PRIMARY KEY (`ct_id`),
        UNIQUE KEY `owner_sku` (`mb_id`, `ct_sid`, `sk_id`),
        KEY `owner` (`mb_id`, `ct_sid`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    'ycart_order_table' => " CREATE TABLE IF NOT EXISTS `{$g5['ycart_order_table']}` (
        `od_id` int(11) NOT NULL AUTO_INCREMENT,
        `od_no` varchar(30) NOT NULL DEFAULT '',
        `od_oid` varchar(40) NOT NULL DEFAULT '',
        `mb_id` varchar(20) NOT NULL DEFAULT '',
        `od_name` varchar(50) NOT NULL DEFAULT '',
        `od_hp` varchar(20) NOT NULL DEFAULT '',
        `od_email` varchar(100) NOT NULL DEFAULT '',
        `od_recv_name` varchar(50) NOT NULL DEFAULT '',
        `od_recv_hp` varchar(20) NOT NULL DEFAULT '',
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
        `od_ct_ids` varchar(255) NOT NULL DEFAULT '',
        `od_delivery_company` varchar(50) NOT NULL DEFAULT '',
        `od_invoice` varchar(50) NOT NULL DEFAULT '',
        `od_shipped_at` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        `od_cancel_reason` varchar(255) NOT NULL DEFAULT '',
        `od_canceled_at` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        `od_canceled_by` varchar(20) NOT NULL DEFAULT '',
        `od_ip` varchar(50) NOT NULL DEFAULT '',
        `od_datetime` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        `od_paid_at` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        PRIMARY KEY (`od_id`),
        UNIQUE KEY `od_no` (`od_no`),
        KEY `od_oid` (`od_oid`),
        KEY `mb_id` (`mb_id`, `od_id`),
        KEY `status` (`od_status`, `od_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    'ycart_order_item_table' => " CREATE TABLE IF NOT EXISTS `{$g5['ycart_order_item_table']}` (
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
    'ycart_address_table' => " CREATE TABLE IF NOT EXISTS `{$g5['ycart_address_table']}` (
        `ad_id` int(11) NOT NULL AUTO_INCREMENT,
        `mb_id` varchar(20) NOT NULL DEFAULT '',
        `ad_name` varchar(50) NOT NULL DEFAULT '',
        `ad_hp` varchar(20) NOT NULL DEFAULT '',
        `ad_zip` varchar(10) NOT NULL DEFAULT '',
        `ad_addr1` varchar(255) NOT NULL DEFAULT '',
        `ad_addr2` varchar(255) NOT NULL DEFAULT '',
        `ad_datetime` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        PRIMARY KEY (`ad_id`), KEY `mb_recent` (`mb_id`, `ad_datetime`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    'ycart_payment_table' => " CREATE TABLE IF NOT EXISTS `{$g5['ycart_payment_table']}` (
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
    $row = sql_fetch(" select * from `{$g5['ycart_config_table']}` where cc_id = 1 ");
    if (!$row) {
        sql_query(" insert into `{$g5['ycart_config_table']}` (cc_id) values (1) ", true);
        $row = sql_fetch(" select * from `{$g5['ycart_config_table']}` where cc_id = 1 ");
    }
    return $row;
}

function cart_url($path = '', $qs = array())
{
    $url = G5_CART_URL.'/'.$path;
    if ($qs) $url .= '?'.http_build_query($qs);
    return $url;
}

// FULLTEXT ngram 인덱스가 실제로 만들어졌는지 — 검색이 MATCH/LIKE 를 고르는 기준
function cart_ft_available()
{
    global $g5;
    static $ok = null;
    if ($ok === null) {
        $ok = (bool)sql_fetch(" SHOW INDEX FROM `{$g5['ycart_item_table']}`
            WHERE Key_name = 'ft_search' ");
    }
    return $ok;
}

// 상품 이미지 저장 경로 — 한 디렉터리에 수만 파일이 쌓이지 않게 1000개 단위 분산
function cart_item_image_dir($it_id)
{
    return G5_CART_DATA_PATH.'/item/'.sprintf('%03d', (int)($it_id / 1000));
}

function cart_item_image_url($file)
{
    return G5_CART_DATA_URL.'/item/'.$file;   // im_file 은 '003/1234_abc.jpg' 형태로 저장
}
