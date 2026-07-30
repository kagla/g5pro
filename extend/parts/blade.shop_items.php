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
 *
 * extend/ 가 아니라 extend/parts/ 에 두는 이유: 이 파일은 확장 모듈이 아니라
 * item_list 가 부를 때마다 실행돼야 하는 조각이다. extend/ 최상위에 두면
 * common.php 로더가 요청 시작 시점에 한 번 include_once 해 버려(그때는 $list 가 없어
 * 빈 배열이 들어간다) 의미 없는 실행이 끼고, 순정이 스킨을 include_once 로 바꾸는 날엔
 * 두 번 다시 실행되지 않아 상품 목록이 항상 비게 된다.
 * 로더는 하위 폴더를 훑지 않으므로 parts/ 에 두면 그 사고가 원천적으로 없다.
 * (지금 순정은 plain include — lib/shop.lib.php 의 item_list::run)
 */
if (!defined('_GNUBOARD_')) exit;

// run() 이 include 하는 시점에 $list 가 스코프에 있다
$GLOBALS['g5_blade_items'] = isset($list) ? $list : array();
