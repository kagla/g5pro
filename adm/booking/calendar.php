<?php
$sub_menu = '950300';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

// 배열로 온 값은 통째로 버린다 — 배열에 (int)/(string) 를 씌우면 경고가 뜨고 엉뚱한 값이 남는다
$br_id = (isset($_GET['br_id']) && !is_array($_GET['br_id'])) ? (int)$_GET['br_id'] : 0;
$ym = (isset($_GET['ym']) && !is_array($_GET['ym'])) ? (string)$_GET['ym'] : '';
// 형식이 어긋난 달은 조용히 이번 달로 되돌린다 — strtotime 에 넘기기 전에 막는다
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $ym)) $ym = date('Y-m', G5_SERVER_TIME);

$rooms = array();
$result = sql_query(" select * from `{$g5['booking_room_table']}` order by br_order, br_id ");
while ($row = sql_fetch_array($result)) $rooms[] = $row;

// br_id 를 안 주면 첫 객실을 연다. 준 번호가 목록에 없으면 잘못된 링크다
$room = null;
foreach ($rooms as $r) {
    if ((int)$r['br_id'] === $br_id) { $room = $r; break; }
}
if (!$room) {
    if ($br_id) alert('등록된 객실이 아닙니다.', './room_list.php');
    if ($rooms) $room = $rooms[0];
}

$first = $ym.'-01';
$last_day = (int)date('t', strtotime($first));
$days = array();

if ($room) {
    for ($d = 1; $d <= $last_day; $d++) {
        $date = $ym.'-'.sprintf('%02d', $d);
        // 캘린더 행을 한 번만 읽어 요금·실수 함수에 함께 넘긴다 (날짜당 조회를 3회에서 1회로)
        $cal = booking_calendar_row($room['br_id'], $date);
        $sellable = booking_sellable_count($room, $date, $cal);
        $booked = booking_booked_count($room['br_id'], $date);
        $days[] = array(
            'date' => $date, 'day' => $d, 'w' => (int)date('w', strtotime($date)),
            'price' => booking_night_price($room, $date, $cal),
            'price_override' => ($cal && (int)$cal['bd_price'] >= 0),
            'sellable' => $sellable, 'count_override' => ($cal && (int)$cal['bd_room_count'] >= 0),
            'booked' => $booked, 'remain' => $sellable - $booked,
            'oversold' => ($booked > $sellable),
        );
    }
}

// 1일이 무슨 요일에서 시작하는지 — 그만큼 앞칸을 비우고, 마지막 줄도 7칸으로 채운다
$lead_blank = (int)date('w', strtotime($first));
$tail_blank = (7 - ($lead_blank + $last_day) % 7) % 7;

$g5['title'] = '요금·재고 캘린더';
include_once(G5_ADMIN_PATH.'/admin.head.php');

badm_view('calendar', array(
    'rooms' => $rooms, 'room' => $room, 'ym' => $ym, 'days' => $days,
    'lead_blank' => $lead_blank, 'tail_blank' => $tail_blank,
    'first_date' => $first, 'last_date' => $ym.'-'.sprintf('%02d', $last_day),
    'prev_ym' => date('Y-m', strtotime($first.' -1 month')),
    'next_ym' => date('Y-m', strtotime($first.' +1 month')),
    'admin_url' => G5_ADMIN_URL,
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
