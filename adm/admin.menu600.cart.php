<?php
if (!defined('_GNUBOARD_')) exit;

// 5번째 원소 'super' — adm/cart/_common.php 가 최고관리자만 통과시키므로
// 권한 부여 화면에 헛 메뉴로 뜨지 않게 표시한다 (부킹 950 관례)
$menu['menu600'] = array(
    array('600000', '카트', G5_ADMIN_URL.'/cart/', 'cart'),
    array('600050', '대시보드', G5_ADMIN_URL.'/cart/', 'cart_dash', 'super'),
    array('600060', '주문관리', G5_ADMIN_URL.'/cart/order_list.php', 'cart_order', 'super'),
    array('600070', '배송관리', G5_ADMIN_URL.'/cart/delivery_list.php', 'cart_delivery', 'super'),
    array('600080', '정산관리', G5_ADMIN_URL.'/cart/settle.php', 'cart_settle', 'super'),
    array('600100', '상품관리', G5_ADMIN_URL.'/cart/item_list.php', 'cart_item', 'super'),
    array('600200', '분류관리', G5_ADMIN_URL.'/cart/category.php', 'cart_category', 'super'),
    array('600250', '상품분류연결', G5_ADMIN_URL.'/cart/category_item.php', 'cart_catmap', 'super'),
    array('600300', 'CSV 입출력', G5_ADMIN_URL.'/cart/csv.php', 'cart_csv', 'super'),
    array('600400', '환경설정', G5_ADMIN_URL.'/cart/config_form.php', 'cart_config', 'super'),
    array('600900', '설치/업그레이드', G5_ADMIN_URL.'/cart/install.php', 'cart_install', 'super'),
);
