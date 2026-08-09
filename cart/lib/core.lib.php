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
        'ycart_option_preset_table' => 'ycart_option_preset',
        'ycart_stock_log_table'  => 'ycart_stock_log',
        'ycart_cart_table'       => 'ycart_cart',
        'ycart_order_table'      => 'ycart_order',
        'ycart_order_item_table' => 'ycart_order_item',
        'ycart_payment_table'    => 'ycart_payment',
        'ycart_address_table'    => 'ycart_address',
        'ycart_wish_table'       => 'ycart_wish',
        'ycart_return_table'     => 'ycart_return',
        'ycart_coupon_table'     => 'ycart_coupon',
        'ycart_coupon_mb_table'  => 'ycart_coupon_mb',
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
        // 2026-08-07 주소록 이메일 — 주문서에서 이메일만 바꾸면 다음 주문서가 회원가입 주소로
        // 되돌아갔다. 이름·연락처와 같은 자리에 담아 불러오기가 세 값을 함께 채운다.
        array('ycart_address_table', 'ad_email',
            " ADD `ad_email` varchar(100) NOT NULL DEFAULT '' AFTER `ad_hp` "),
        // 2026-08-07 옵션 조합 정렬 — 이름순으로 두면 자주 쓰는 조합이 목록 아래로 밀린다.
        // 작을수록 위. 화면에서 새로 저장한 조합은 맨 끝 번호를 받는다.
        array('ycart_option_preset_table', 'op_order',
            " ADD `op_order` int(11) NOT NULL DEFAULT '0' AFTER `op_name` "),
        // 2026-08-09 구매확정 — 배송완료 뒤 고객이 "잘 받았다" 고 매듭짓는 시각.
        // 반품 가능 기간과 포인트 적립 시점의 기준이 되므로 상태와 별도로 시각을 남긴다.
        array('ycart_order_table', 'od_confirmed_at',
            " ADD `od_confirmed_at` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' AFTER `od_shipped_at` "),
        // 2026-08-09 무통장 입금 기한 — 지나면 자동 취소하고 재고를 풀어 준다.
        // 무통장은 주문 즉시 재고를 차감하므로(cart_order_create), 기한이 없으면
        // 입금 안 된 주문이 재고를 무기한 잠근다. 0 이면 자동 취소하지 않는다.
        array('ycart_config_table', 'cc_unpaid_days',
            " ADD `cc_unpaid_days` tinyint(4) NOT NULL DEFAULT '3' AFTER `cc_bank` "),
        // 2026-08-09 반품 — 환불 누계(부분 반품이 여러 번 쌓일 수 있어 합계로 둔다)와
        // 품목이 속한 반품 신청. 배송완료 후 신청 가능 기간은 설정값.
        array('ycart_order_table', 'od_refund',
            " ADD `od_refund` int(11) NOT NULL DEFAULT '0' AFTER `od_total` "),
        array('ycart_order_item_table', 'oi_rt_id',
            " ADD `oi_rt_id` int(11) NOT NULL DEFAULT '0' AFTER `oi_status` "),
        array('ycart_config_table', 'cc_return_days',
            " ADD `cc_return_days` tinyint(4) NOT NULL DEFAULT '7' AFTER `cc_unpaid_days` "),
        // 반품 기한의 기준 시각 — "받은 날부터 며칠" 을 세려면 배송완료 시각이 있어야 한다.
        // 발송 시각(od_shipped_at)으로 대신하면 배송이 오래 걸린 손님이 기한을 손해 본다.
        array('ycart_order_table', 'od_delivered_at',
            " ADD `od_delivered_at` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' AFTER `od_shipped_at` "),
        // 2026-08-09 쿠폰 — 깎인 금액은 od_coupon 에 이미 자리가 있고, "어느 장을 썼는지" 만 없었다.
        // 취소·전체반품 때 그 장을 되살리려면 장을 가리켜야 한다(0 = 안 썼음).
        array('ycart_order_table', 'od_cm_id',
            " ADD `od_cm_id` int(11) NOT NULL DEFAULT '0' AFTER `od_coupon` "),
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
        `cc_unpaid_days` tinyint(4) NOT NULL DEFAULT '3',
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
    // 옵션 조합 프리셋 — 상품 폼의 옵션명·값 묶음을 이름 붙여 두고 다음 상품에서 불러 쓴다.
    // op_data 는 [{"name":"색상","vals":["빨강","파랑"]}, ...] JSON. 상품과 무관한 몰 공용 자산이라
    // 상품·SKU 와 연결하지 않는다(프리셋을 지워도 이미 만든 SKU 는 그대로).
    'ycart_option_preset_table' => " CREATE TABLE IF NOT EXISTS `{$g5['ycart_option_preset_table']}` (
        `op_id` int(11) NOT NULL AUTO_INCREMENT,
        `op_name` varchar(100) NOT NULL DEFAULT '',
        `op_order` int(11) NOT NULL DEFAULT '0',
        `op_data` text NOT NULL,
        `op_datetime` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        PRIMARY KEY (`op_id`),
        UNIQUE KEY `op_name` (`op_name`)
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
        `od_refund` int(11) NOT NULL DEFAULT '0',
        `od_status` varchar(20) NOT NULL DEFAULT 'unpaid',
        `od_pay_method` varchar(20) NOT NULL DEFAULT 'bank',
        `od_depositor` varchar(50) NOT NULL DEFAULT '',
        `od_guest_pw` varchar(255) NOT NULL DEFAULT '',
        `od_ct_ids` varchar(255) NOT NULL DEFAULT '',
        `od_delivery_company` varchar(50) NOT NULL DEFAULT '',
        `od_invoice` varchar(50) NOT NULL DEFAULT '',
        `od_shipped_at` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        `od_delivered_at` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        `od_confirmed_at` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
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
        `oi_rt_id` int(11) NOT NULL DEFAULT '0',
        PRIMARY KEY (`oi_id`), KEY `od_id` (`od_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    'ycart_address_table' => " CREATE TABLE IF NOT EXISTS `{$g5['ycart_address_table']}` (
        `ad_id` int(11) NOT NULL AUTO_INCREMENT,
        `mb_id` varchar(20) NOT NULL DEFAULT '',
        `ad_name` varchar(50) NOT NULL DEFAULT '',
        `ad_hp` varchar(20) NOT NULL DEFAULT '',
        `ad_email` varchar(100) NOT NULL DEFAULT '',
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
    // 찜(관심 상품) — 회원 하나가 상품 하나를 한 번만 찜한다(UNIQUE mb_item 이 곧 토글의 근거).
    // 소유자는 mb_id 뿐이다 — 장바구니와 달리 비회원 세션을 받지 않는다(wish.lib.php 머리말 참고).
    'ycart_wish_table' => " CREATE TABLE IF NOT EXISTS `{$g5['ycart_wish_table']}` (
        `wi_id` int(11) NOT NULL AUTO_INCREMENT,
        `mb_id` varchar(20) NOT NULL DEFAULT '',
        `it_id` int(11) NOT NULL DEFAULT '0',
        `wi_datetime` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        PRIMARY KEY (`wi_id`),
        UNIQUE KEY `mb_item` (`mb_id`, `it_id`),
        KEY `mb_recent` (`mb_id`, `wi_id`),
        KEY `it_id` (`it_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    // 반품 신청 — 신청 한 번이 한 행이다. 한 주문에 신청이 여러 번 있을 수 있어(오늘 한 품목,
    // 다음 주에 또 한 품목) 주문 컬럼에 담지 않는다. 어느 품목이 이 신청에 속하는지는
    // 주문품목의 oi_rt_id 가 가리킨다 — 품목은 한 번만 반품되므로 컬럼 하나로 충분하다.
    // rt_bank(환불 계좌)는 무통장 건에만 받고 환불을 마치면 비운다 — 쓸 일이 끝난 개인정보다.
    'ycart_return_table' => " CREATE TABLE IF NOT EXISTS `{$g5['ycart_return_table']}` (
        `rt_id` int(11) NOT NULL AUTO_INCREMENT,
        `od_id` int(11) NOT NULL DEFAULT '0',
        `mb_id` varchar(20) NOT NULL DEFAULT '',
        `rt_status` varchar(10) NOT NULL DEFAULT 'requested',
        `rt_oi_ids` varchar(255) NOT NULL DEFAULT '',
        `rt_reason` varchar(255) NOT NULL DEFAULT '',
        `rt_bank` varchar(100) NOT NULL DEFAULT '',
        `rt_refund` int(11) NOT NULL DEFAULT '0',
        `rt_restock` tinyint(4) NOT NULL DEFAULT '1',
        `rt_memo` varchar(255) NOT NULL DEFAULT '',
        `rt_requested_at` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        `rt_done_at` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        `rt_done_by` varchar(20) NOT NULL DEFAULT '',
        PRIMARY KEY (`rt_id`),
        KEY `od_id` (`od_id`, `rt_id`),
        KEY `live` (`rt_status`, `rt_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    // 쿠폰 종류(정의). 발급 경로가 코드 입력·관리자 지급·가입·첫구매로 갈리지만
    // 어느 길로 왔든 결과는 하나다 — 회원 쿠폰함에 한 장이 들어온다(ycart_coupon_mb).
    // cp_target 은 '' 전체 / 'ca:분류코드' / 'it:상품id' 세 꼴만 쓴다.
    'ycart_coupon_table' => " CREATE TABLE IF NOT EXISTS `{$g5['ycart_coupon_table']}` (
        `cp_id` int(11) NOT NULL AUTO_INCREMENT,
        `cp_name` varchar(100) NOT NULL DEFAULT '',
        `cp_code` varchar(30) NOT NULL DEFAULT '',
        `cp_issue` varchar(10) NOT NULL DEFAULT 'code',
        `cp_type` varchar(10) NOT NULL DEFAULT 'rate',
        `cp_value` int(11) NOT NULL DEFAULT '0',
        `cp_max` int(11) NOT NULL DEFAULT '0',
        `cp_min` int(11) NOT NULL DEFAULT '0',
        `cp_target` varchar(40) NOT NULL DEFAULT '',
        `cp_begin` date NOT NULL DEFAULT '1970-01-01',
        `cp_end` date NOT NULL DEFAULT '1970-01-01',
        `cp_days` smallint(6) NOT NULL DEFAULT '0',
        `cp_use` tinyint(4) NOT NULL DEFAULT '1',
        `cp_memo` varchar(255) NOT NULL DEFAULT '',
        `cp_datetime` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        PRIMARY KEY (`cp_id`),
        KEY `code_live` (`cp_code`, `cp_use`),
        KEY `issue_live` (`cp_issue`, `cp_use`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
    // 회원이 손에 쥔 한 장. 보유와 사용을 한 행이 겸한다(cm_od_id = 0 이면 아직 안 씀).
    // UNIQUE (cp_id, mb_id) 가 "한 쿠폰은 회원당 한 장" 을 DB 에서 지킨다 —
    // 가입·첫구매 쿠폰을 화면에 들어올 때 지연 발급하는 방식이 이 제약 위에서만 안전하다.
    'ycart_coupon_mb_table' => " CREATE TABLE IF NOT EXISTS `{$g5['ycart_coupon_mb_table']}` (
        `cm_id` int(11) NOT NULL AUTO_INCREMENT,
        `cp_id` int(11) NOT NULL DEFAULT '0',
        `mb_id` varchar(20) NOT NULL DEFAULT '',
        `cm_end` date NOT NULL DEFAULT '1970-01-01',
        `cm_od_id` int(11) NOT NULL DEFAULT '0',
        `cm_amount` int(11) NOT NULL DEFAULT '0',
        `cm_issued_at` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        `cm_used_at` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        PRIMARY KEY (`cm_id`),
        UNIQUE KEY `one_per_mb` (`cp_id`, `mb_id`),
        KEY `mine` (`mb_id`, `cm_od_id`),
        KEY `od_id` (`cm_od_id`)
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

// 하루 한 번만 도는 뒷정리 — 그누보드에는 스케줄러가 없으므로 들어온 요청이 대신 태운다.
// 마지막으로 돈 날짜를 파일에 적어 두고 날짜가 바뀌었을 때만 실제 일을 한다(그 외에는 파일 읽기 한 번).
// 프론트·관리자 양쪽에서 부르지만 도장이 하나라 하루에 한 번만 돈다 — 손님이 안 와도
// 관리자가 들어오면 돌고, 관리자가 안 와도 손님이 오면 돈다.
//
// 도장을 일보다 먼저 찍는다: 일이 실패하면 매 요청이 재시도하며 사이트를 느리게 만든다.
// 하루 놓치는 편이 온종일 느린 것보다 낫고, 다음 날 어차피 다시 돈다.
function cart_daily_sweep()
{
    static $done = false;
    if ($done) return;
    $done = true;

    if (!is_dir(G5_CART_DATA_PATH)) {
        @mkdir(G5_CART_DATA_PATH, G5_DIR_PERMISSION, true);
        @chmod(G5_CART_DATA_PATH, G5_DIR_PERMISSION);
    }
    $file = G5_CART_DATA_PATH.'/sweep.dat';
    $today = date('Ymd', G5_SERVER_TIME);
    if (is_file($file) && trim((string)@file_get_contents($file)) === $today) return;
    if (@file_put_contents($file, $today, LOCK_EX) === false) return;   // 못 적으면 아예 돌지 않는다

    cart_order_expire_unpaid();
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

// 줄여 놓은 이미지 주소. 화면에 64px 로 그릴 자리에 3MB 원본을 내려보내면 상세 화면 하나가
// 수십 MB 가 된다 — 순정 thumbnail() 로 한 번 만들어 두고(원본보다 최신이면 다시 안 만든다)
// 원본 옆 thumb/ 에 캐시한다. 만들지 못하면(GD 없음·권한 없음·애니메이션 GIF) 원본으로 돌아간다.
//
// $crop=false 는 "비율 유지" 가 아니다 — 순정은 상자를 흰색으로 채우고 그 안에 앉힌다
// (레터박스). 화면이 object-fit:cover 로 다시 채우는 자리라면 흰 여백까지 잘려 들어오므로
// 그런 자리엔 $crop=true 를 쓴다.
//
// 진짜 비율 유지가 필요하면 한 변을 0 으로 준다(예: 폭 1600·높이 0). 순정이 나머지 한 변을
// 원본 비율로 계산하므로 잘리지도, 여백이 끼지도 않는다 — 확대해 보기(라이트박스)가 이 방식.
function cart_item_thumb_url($file, $w, $h, $crop = true)
{
    $file = str_replace('\\', '/', (string)$file);
    if ($file === '' || strpos($file, '..') !== false) return '';
    if (!function_exists('thumbnail')) @include_once(G5_LIB_PATH.'/thumbnail.lib.php');
    if (!function_exists('thumbnail')) return cart_item_image_url($file);

    $sub = dirname($file);                       // '003' — 파일명만 저장된 옛 행이면 '.'
    $sub = ($sub === '.' || $sub === '') ? '' : '/'.$sub;
    $name = basename($file);
    $dir = G5_CART_DATA_PATH.'/item'.$sub;
    // 캐시 파일 이름은 크기만 담고 자르기 여부는 담지 않는다(순정 thumbnail() 규칙).
    // 같은 크기를 자른 것과 맞춘 것이 한 폴더에 있으면 서로를 덮어쓴 채 재사용되므로
    // 모드마다 폴더를 나눈다.
    $sect = $crop ? '/thumb' : '/thumb-fit';
    $thumb_dir = $dir.$sect;

    // 이미 만들어 둔 게 있으면 그걸 쓴다. 순정 thumbnail() 은 폴더에 쓸 수 없으면 캐시가
    // 있어도 빈 값을 주므로(다른 사용자가 만든 폴더일 때) 여기서 먼저 확인한다.
    // 원본을 새로 올려 원본이 더 최신이면 건너뛰고 아래에서 다시 만든다.
    $base = preg_replace('/\.[^.]+$/', '', $name);
    $src_time = @filemtime($dir.'/'.$name);
    foreach ((array)glob($thumb_dir.'/thumb-'.$base.'_'.(int)$w.'x'.(int)$h.'.*') as $hit) {
        if (@filemtime($hit) >= $src_time) return G5_CART_DATA_URL.'/item'.$sub.$sect.'/'.basename($hit);
    }

    // 줄어들지 않을 일이면 만들지 않는다. 늘리면 흐려지는 데다 다시 인코딩하느라 파일이
    // 오히려 커진다(800x800 짜리 116KB 원본이 900x900 으로 175KB 가 됐다).
    // 자를 때는 짧은 변이, 맞출 때는 긴 변이 크기를 정하므로 배율 식이 서로 반대다.
    $size = @getimagesize($dir.'/'.$name);
    if ($size && (int)$size[0] > 0 && (int)$size[1] > 0) {
        // 한 변을 0 으로 주면 순정은 나머지 한 변을 원본 비율로 계산한다(잘림도 여백도 없음).
        // 그때 배율은 값이 있는 변만 본다 — 0 을 그대로 식에 넣으면 배율이 늘 0 이 되어
        // "줄어들지 않으면 만들지 않는다" 는 아래 판정이 통째로 무력해진다.
        if (!(int)$h)        $scale = $w / $size[0];
        elseif (!(int)$w)    $scale = $h / $size[1];
        elseif ($crop)       $scale = max($w / $size[0], $h / $size[1]);
        else                 $scale = min($w / $size[0], $h / $size[1]);
        if ($scale >= 1) return cart_item_image_url($file);
    }

    $made = thumbnail($name, $dir, $thumb_dir, (int)$w, (int)$h, false, $crop);
    if (!$made) return cart_item_image_url($file);
    return G5_CART_DATA_URL.'/item'.$sub.$sect.'/'.$made;
}

// 원본을 지울 때 그 원본으로 만든 썸네일도 같이 지운다 — 안 지우면 크기를 바꿀 때마다
// 쓰이지 않는 파일이 쌓이고, 같은 이름이 다시 올라오면 옛 썸네일이 나온다
function cart_item_thumb_purge($file)
{
    $file = str_replace('\\', '/', (string)$file);
    if ($file === '' || strpos($file, '..') !== false) return;
    $sub = dirname($file);
    $sub = ($sub === '.' || $sub === '') ? '' : '/'.$sub;
    $base = preg_replace('/\.[^.]+$/', '', basename($file));
    foreach (array('/thumb', '/thumb-fit') as $sect) {
        foreach ((array)glob(G5_CART_DATA_PATH.'/item'.$sub.$sect.'/thumb-'.$base.'_*') as $f) @unlink($f);
    }
}
