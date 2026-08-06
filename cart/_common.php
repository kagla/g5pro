<?php
include_once('../common.php');
include_once(G5_PATH.'/cart/lib/cart.lib.php');
include_once(G5_PATH.'/cart/lib/item.lib.php');
include_once(G5_PATH.'/cart/lib/stock.lib.php');
include_once(G5_PATH.'/cart/lib/order.lib.php');

// 프론트도 첫 접근이 설치를 대신하지 않게 — 미설치면 안내만 하고 끝낸다
if (!cart_installed()) {
    alert('쇼핑몰이 아직 준비되지 않았습니다.', G5_URL);
}
