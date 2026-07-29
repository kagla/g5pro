<?php
/**
 * g5blade — item_list 데이터 수집기.
 *
 * 순정 item_list::run() 은 상품 배열 $list 를 만든 뒤 "스킨 파일"을 include 해서
 * HTML 을 만들어 돌려준다. 이 파일을 스킨 자리에 지정하면 출력 없이 $list 만 넘겨받는다.
 * 덕분에 상품유형·분류·이벤트 검색 쿼리는 순정 로직을 그대로 재사용한다.
 *
 * 사용:
 *   $il = new item_list(); $il->set_type(1);
 *   $items = g5_shop_items($il);
 */
if (!defined('_GNUBOARD_')) exit;

// run() 이 include 하는 시점에 $list 가 스코프에 있다
$GLOBALS['g5_blade_items'] = isset($list) ? $list : array();
