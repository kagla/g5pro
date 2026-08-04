<?php
// 이니시스 위변조 방지 서명 발급 — booking/pay.php 의 결제 버튼이 결제창을 열기 직전에 부른다.
//
// 순정 shop/inicis/makesignature.php 와 다른 점: 클라이언트가 보낸 금액을 쓰지 않는다.
// 서명할 금액은 세션의 주문번호로 예약 행을 찾아 서버가 다시 읽는다. 개발자도구로 폼의
// price 를 고쳐도 서명은 진짜 청구액으로 만들어지고, 결제창에 올린 금액과 어긋나면
// 이니시스가 결제 자체를 막는다.
define('G5_BOOKING_JSON', true);   // 미설치일 때 HTML 알림 대신 JSON 을 내도록 (booking/_common.php)
include_once('./_common.php');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// 주문번호는 세션에서만 받는다 — 요청으로 받으면 남의 예약 금액에 서명해 줄 수 있다
$oid = get_session('ss_booking_inicis_oid');
$bk = $oid ? booking_get_by_oid($oid) : null;
if (!$bk || $bk['bk_status'] !== 'hold' || strtotime($bk['bk_hold_expire']) < G5_SERVER_TIME)
    die(json_encode(array('error' => '결제 대상 예약이 없습니다. 다시 예약해 주세요.'), JSON_UNESCAPED_UNICODE));

$conf = booking_inicis_conf();
if ($conf['mid'] === '' || $conf['sign_key'] === '')
    die(json_encode(array('error' => '결제 설정이 아직 등록되지 않았습니다.'), JSON_UNESCAPED_UNICODE));

// 쇼핑몰을 쓰지 않는 설치에서는 G5_SHOP_PATH 가 없다 (shop.config.php 가 G5_USE_SHOP 에서 끝난다).
// 라이브러리는 PG 비종속이라 파일 자리만 알면 그대로 쓸 수 있다
$inicis_path = (defined('G5_SHOP_PATH') ? G5_SHOP_PATH : G5_PATH.'/shop').'/inicis/libs';
include_once($inicis_path.'/INIStdPayUtil.php');

$util = new INIStdPayUtil();
$timestamp = $util->getTimestamp();

// oid · price · timestamp 를 key=value 로 '&' 이어 붙인 뒤 SHA-256 (키 기준 알파벳 순).
// 여기 쓴 timestamp 를 폼의 timestamp 칸에도 그대로 넣어야 검증이 통과한다
$sign = hash('sha256', 'oid='.$oid.'&price='.(int)$bk['bk_total_price'].'&timestamp='.$timestamp);

die(json_encode(array(
    'error'     => '',
    'mKey'      => hash('sha256', $conf['sign_key']),   // 가맹점 확인용 사인키 해시
    'timestamp' => $timestamp,
    'sign'      => $sign,
), JSON_UNESCAPED_UNICODE));
