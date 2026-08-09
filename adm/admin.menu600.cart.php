<?php
if (!defined('_GNUBOARD_')) exit;

// 이 파일은 순정 admin.lib.php 가 모든 관리자 화면에서 자동 스캔으로 읽는다 —
// adm/cart/_common.php 를 안 거치므로 G5_CART_* 상수의 출처(core.lib)를 여기서 직접 문다.
include_once(G5_PATH.'/cart/lib/core.lib.php');

// 5번째 원소 'super' — adm/cart/_common.php 가 최고관리자만 통과시키므로
// 권한 부여 화면에 헛 메뉴로 뜨지 않게 표시한다 (부킹 950 관례)
$menu['menu600'] = array(
    array('600000', '카트', G5_CART_ADMIN_URL.'/', 'cart'),
    array('600050', '대시보드', G5_CART_ADMIN_URL.'/', 'cart_dash', 'super'),
    array('600060', '주문관리', G5_CART_ADMIN_URL.'/order_list.php', 'cart_order', 'super'),
    array('600070', '배송관리', G5_CART_ADMIN_URL.'/delivery_list.php', 'cart_delivery', 'super'),
    array('600075', '반품관리', G5_CART_ADMIN_URL.'/return_list.php', 'cart_return', 'super'),
    array('600077', '쿠폰관리', G5_CART_ADMIN_URL.'/coupon_list.php', 'cart_coupon', 'super'),
    array('600080', '정산관리', G5_CART_ADMIN_URL.'/settle.php', 'cart_settle', 'super'),
    array('600100', '상품관리', G5_CART_ADMIN_URL.'/item_list.php', 'cart_item', 'super'),
    array('600200', '분류관리', G5_CART_ADMIN_URL.'/category.php', 'cart_category', 'super'),
    array('600250', '상품분류연결', G5_CART_ADMIN_URL.'/category_item.php', 'cart_catmap', 'super'),
    array('600300', 'CSV 입출력', G5_CART_ADMIN_URL.'/csv.php', 'cart_csv', 'super'),
    array('600400', '환경설정', G5_CART_ADMIN_URL.'/config_form.php', 'cart_config', 'super'),
    array('600900', '설치/업그레이드', G5_CART_ADMIN_URL.'/install.php', 'cart_install', 'super'),
);
