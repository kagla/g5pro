<?php
if (!defined('_GNUBOARD_')) exit;

// 5번째 원소 'super' — adm/cart/_common.php 가 최고관리자만 통과시키므로
// 권한 부여 화면에 헛 메뉴로 뜨지 않게 표시한다 (부킹 950 관례)
$menu['menu600'] = array(
    array('600000', '카트', G5_ADMIN_URL.'/cart/item_list.php', 'cart'),
    array('600100', '상품관리', G5_ADMIN_URL.'/cart/item_list.php', 'cart_item', 'super'),
    array('600200', '분류관리', G5_ADMIN_URL.'/cart/category.php', 'cart_category', 'super'),
    array('600300', 'CSV 입출력', G5_ADMIN_URL.'/cart/csv.php', 'cart_csv', 'super'),
    array('600900', '설치/업그레이드', G5_ADMIN_URL.'/cart/install.php', 'cart_install', 'super'),
);
