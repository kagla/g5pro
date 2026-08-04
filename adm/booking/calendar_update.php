<?php
$sub_menu = '950300';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

// 배열로 온 값은 통째로 버린다 — 배열에 (int)/(string) 를 씌우면 경고가 뜨고 엉뚱한 값이 남는다
$br_id = (isset($_POST['br_id']) && !is_array($_POST['br_id'])) ? (int)$_POST['br_id'] : 0;

// $_POST 는 common.php 에서 이미 escape 되어 있다. 아래 값은 모두 형식을 통과한 숫자·날짜뿐이라
// 따옴표가 낄 자리가 없다 — 검증을 통과한 문자열만 SQL 로 내려간다
$in = array();
foreach (array('start_date', 'end_date', 'set_price', 'set_percent', 'set_count', 'ym') as $name) {
    $in[$name] = (isset($_POST[$name]) && !is_array($_POST[$name])) ? trim((string)$_POST[$name]) : '';
}
$all_rooms = (isset($_POST['all_rooms']) && !is_array($_POST['all_rooms']) && (int)$_POST['all_rooms']) ? 1 : 0;

$room = sql_fetch(" select * from `{$g5['booking_room_table']}` where br_id = '$br_id' ");
if (!$room) alert('등록된 객실이 아닙니다.', './room_list.php');

$ym = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $in['ym']) ? $in['ym'] : date('Y-m', G5_SERVER_TIME);
$list_url = './calendar.php?br_id='.$br_id.'&amp;ym='.$ym;

foreach (array('start_date' => '시작일', 'end_date' => '종료일') as $name => $label) {
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $in[$name], $m) || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
        alert($label.'을(를) 올바르게 입력하세요.', $list_url);
    }
}

$start = strtotime($in['start_date']);
$end = strtotime($in['end_date']);
if ($end < $start) alert('종료일은 시작일과 같거나 그 뒤여야 합니다.', $list_url);
// 종료일을 포함해 최대 366일 — 실수로 몇 년치를 긁어 쓰는 일을 막는다
if (($end - $start) / 86400 > 365) alert('한 번에 366일까지만 적용할 수 있습니다.', $list_url);

foreach (array('set_price', 'set_count') as $name) {
    if ($in[$name] !== '' && !preg_match('/^-?\d+$/', $in[$name])) {
        alert('요금과 판매 실수는 숫자로 입력하세요. (해제하려면 -1)', $list_url);
    }
}
// 비율은 해제(-1) 개념이 없다 — 해제는 요금 칸의 -1 로 한다
if ($in['set_percent'] !== '' && (!preg_match('/^\d+$/', $in['set_percent'])
        || (int)$in['set_percent'] < 1 || (int)$in['set_percent'] > 999)) {
    alert('요금 비율은 1~999 사이 숫자로 입력하세요. (예: 성수기 150)', $list_url);
}
if ($in['set_price'] !== '' && $in['set_percent'] !== '') {
    alert('요금은 액수와 비율 중 하나만 입력하세요.', $list_url);
}
if ($in['set_price'] === '' && $in['set_percent'] === '' && $in['set_count'] === '') {
    alert('요금(액수 또는 비율)과 판매 실수 중 최소 하나는 입력하세요.', $list_url);
}

// 전체 객실 적용 — 성수기·비수기를 객실마다 반복하지 않게 한 번에 돈다
$targets = array($room);
if ($all_rooms) {
    $targets = array();
    $result = sql_query(" select * from `{$g5['booking_room_table']}` order by br_order, br_id ");
    while ($r = sql_fetch_array($result)) $targets[] = $r;
}

$pct = (int)$in['set_percent'];
foreach ($targets as $tr) {
    $t_br_id = (int)$tr['br_id'];

    // 빈칸이면 그 날의 기존 값을 그대로 두고, 음수는 모두 -1(미지정)로 모은다
    for ($t = $start; $t <= $end; $t = strtotime('+1 day', $t)) {
        $date = date('Y-m-d', $t);
        $row = booking_calendar_row($t_br_id, $date);

        if ($in['set_percent'] !== '') {
            // 비율의 기준은 객실 기본요금(주중/주말)이다 — 이미 덮어쓴 캘린더 값을 기준으로 하면
            // 같은 비율을 두 번 적용했을 때 겹으로 불어난다. 100원 단위로 반올림
            $w = (int)date('w', $t);
            $base = ($w === 5 || $w === 6) ? (int)$tr['br_weekend_price'] : (int)$tr['br_weekday_price'];
            $price = (int)(round($base * $pct / 100 / 100) * 100);
        } else {
            $price = ($in['set_price'] === '') ? ($row ? (int)$row['bd_price'] : -1) : max(-1, (int)$in['set_price']);
        }
        $count = ($in['set_count'] === '') ? ($row ? (int)$row['bd_room_count'] : -1) : max(-1, (int)$in['set_count']);

        if ($price < 0 && $count < 0) {
            // 둘 다 미지정이면 남길 게 없다 — 행을 지워 캘린더를 기본값 상태로 되돌린다
            if ($row) sql_query(" delete from `{$g5['booking_calendar_table']}` where bd_id = '{$row['bd_id']}' ", true);
        } else if ($row) {
            sql_query(" update `{$g5['booking_calendar_table']}`
                set bd_price = '$price', bd_room_count = '$count' where bd_id = '{$row['bd_id']}' ", true);
        } else {
            sql_query(" insert into `{$g5['booking_calendar_table']}`
                set br_id = '$t_br_id', bd_date = '$date', bd_price = '$price', bd_room_count = '$count' ", true);
        }
    }
}

goto_url($list_url);
