<?php
// 한 달치 요금·잔여를 JSON 으로 돌려준다. booking/room.php 의 캘린더가 호출한다.
// 상수를 먼저 define 하는 이유 — _common.php 가 미설치일 때 HTML 알림 대신 JSON 을 내도록
define('G5_BOOKING_JSON', true);
include_once('./_common.php');

header('Content-Type: application/json; charset=utf-8');
// 잔여는 매 순간 바뀐다. 캐시된 잔여를 보고 고른 날짜는 결제 단계에서 되돌려질 뿐이다
header('Cache-Control: no-store');

$br_id = (isset($_GET['br_id']) && !is_array($_GET['br_id'])) ? (int)$_GET['br_id'] : 0;
$ym = (isset($_GET['ym']) && !is_array($_GET['ym'])) ? (string)$_GET['ym'] : '';
// 형식이 어긋난 달은 조용히 이번 달로 되돌린다 — strtotime 에 넘기기 전에 막는다
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $ym)) $ym = date('Y-m', G5_SERVER_TIME);

$room = sql_fetch(" select * from `{$g5['booking_room_table']}`
    where br_id = '$br_id' and br_use = 1 ");
if (!$room) {
    die(json_encode(array('error' => '등록된 객실이 아닙니다.', 'ym' => $ym, 'days' => array()),
        JSON_UNESCAPED_UNICODE));
}

$bc = booking_config();
$today = date('Y-m-d', G5_SERVER_TIME);
// 당일 마감 시각을 넘기면 오늘 밤은 팔지 않는다 (booking_validate_stay 와 같은 규칙)
$today_closed = (date('H:i', G5_SERVER_TIME) > $bc['bc_sameday_deadline']);
// 오픈 기간. 체크아웃이 한도 안이어야 하므로 마지막으로 팔 수 있는 밤은 한도 하루 전이다
$limit = date('Y-m-d', strtotime('+'.(int)$bc['bc_open_months'].' month', G5_SERVER_TIME));

$last_day = (int)date('t', strtotime($ym.'-01'));
$days = array();
for ($d = 1; $d <= $last_day; $d++) {
    $date = $ym.'-'.sprintf('%02d', $d);
    // 캘린더 행을 한 번만 읽어 요금·실수 함수에 함께 넘긴다 (날짜당 조회를 3회에서 1회로)
    $cal = booking_calendar_row($room['br_id'], $date);
    $remain = booking_sellable_count($room, $date, $cal) - booking_booked_count($room['br_id'], $date);
    if ($remain < 0) $remain = 0;   // 초과예약(관리자가 실수를 줄인 경우)은 0 으로 보인다
    $days[] = array(
        'date'       => $date,
        'price'      => booking_night_price($room, $date, $cal),
        'remain'     => $remain,
        'selectable' => ($remain > 0 && $date >= $today && $date < $limit
                         && !($date === $today && $today_closed)),
    );
}

die(json_encode(array('ym' => $ym, 'days' => $days), JSON_UNESCAPED_UNICODE));
