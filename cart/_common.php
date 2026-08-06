<?php
include_once('../common.php');
include_once(G5_PATH.'/cart/lib/core.lib.php'); // 상수를 여기서 정의하므로 이 줄만 리터럴
include_once(G5_CART_LIB_PATH.'/item.lib.php');
include_once(G5_CART_LIB_PATH.'/stock.lib.php');
include_once(G5_CART_LIB_PATH.'/order.lib.php');
include_once(G5_CART_LIB_PATH.'/cart.lib.php');
include_once(G5_CART_LIB_PATH.'/pay.lib.php');

// 프론트도 첫 접근이 설치를 대신하지 않게 — 미설치면 안내만 하고 끝낸다
if (!cart_installed()) {
    alert('쇼핑몰이 아직 준비되지 않았습니다.', G5_URL);
}
