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
foreach (array('act', 'start_date', 'end_date', 'season', 'peak_percent', 'off_percent',
        'stock_mode', 'partial_count', 'set_price', 'ym') as $name) {
    $in[$name] = (isset($_POST[$name]) && !is_array($_POST[$name])) ? trim((string)$_POST[$name]) : '';
}

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

// ---------------------------------------------------------------- 성수기·비수기 (전체 객실)
// 요금 비율만 전체 적용이 말이 된다 — 방마다 요금이 달라도 각 객실의 기본요금(주중/주말)
// 기준으로 계산되기 때문이다. 판매 객실 수는 타입마다 보유 실수가 달라 여기서 건드리지 않는다
if ($in['act'] === 'season') {
    $season = in_array($in['season'], array('peak', 'off', 'reset'), true) ? $in['season'] : '';
    if ($season === '') alert('성수기·비수기·기본요금 되돌리기 중 하나를 고르세요.', $list_url);

    $pct = 0;
    if ($season !== 'reset') {
        $pct_in = ($season === 'peak') ? $in['peak_percent'] : $in['off_percent'];
        if (!preg_match('/^\d+$/', $pct_in) || (int)$pct_in < 1 || (int)$pct_in > 999) {
            alert('요금 비율은 1~999 사이 숫자로 입력하세요. (예: 성수기 150)', $list_url);
        }
        $pct = (int)$pct_in;
    }

    $result = sql_query(" select * from `{$g5['booking_room_table']}` order by br_order, br_id ");
    while ($tr = sql_fetch_array($result)) {
        $t_br_id = (int)$tr['br_id'];
        for ($t = $start; $t <= $end; $t = strtotime('+1 day', $t)) {
            $date = date('Y-m-d', $t);
            $row = booking_calendar_row($t_br_id, $date);

            if ($season === 'reset') {
                $price = -1;
            } else {
                // 비율의 기준은 객실 기본요금(주중/주말)이다 — 이미 덮어쓴 캘린더 값을 기준으로 하면
                // 같은 비율을 두 번 적용했을 때 겹으로 불어난다. 100원 단위로 반올림
                $w = (int)date('w', $t);
                $base = ($w === 5 || $w === 6) ? (int)$tr['br_weekend_price'] : (int)$tr['br_weekday_price'];
                $price = (int)(round($base * $pct / 100 / 100) * 100);
            }
            // 그 날의 판매 객실 수 지정은 그대로 둔다
            $count = $row ? (int)$row['bd_room_count'] : -1;

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
}

// ---------------------------------------------------------------- 판매 중지·재개 (이 객실)
// 업주는 "이 방, 이 기간, 판매 중지"로 생각한다 — 숫자 계산 없이 동작을 고르게 한다.
// 중지=0, 재개=지정 해제(기본 개수로), 일부만 판매=고른 개수
if ($in['act'] === 'stock') {
    $mode = in_array($in['stock_mode'], array('stop', 'resume', 'partial'), true) ? $in['stock_mode'] : '';
    if ($mode === '') alert('판매 중지·재개·일부만 판매 중 하나를 고르세요.', $list_url);

    $count = 0;
    if ($mode === 'resume') $count = -1;
    if ($mode === 'partial') {
        if (!preg_match('/^\d+$/', $in['partial_count']) || (int)$in['partial_count'] < 1) {
            alert('일부만 판매할 방 개수를 1 이상 숫자로 입력하세요.', $list_url);
        }
        $count = (int)$in['partial_count'];
        if ($count >= (int)$room['br_room_count']) {
            alert('보유 객실 수('.(int)$room['br_room_count'].'개)보다 적어야 일부 판매입니다. 전부 팔려면 판매 재개를 고르세요.', $list_url);
        }
    }

    for ($t = $start; $t <= $end; $t = strtotime('+1 day', $t)) {
        $date = date('Y-m-d', $t);
        $row = booking_calendar_row($br_id, $date);
        // 그 날의 요금 지정은 그대로 둔다
        $price = $row ? (int)$row['bd_price'] : -1;

        if ($price < 0 && $count < 0) {
            // 둘 다 미지정이면 남길 게 없다 — 행을 지워 캘린더를 기본값 상태로 되돌린다
            if ($row) sql_query(" delete from `{$g5['booking_calendar_table']}` where bd_id = '{$row['bd_id']}' ", true);
        } else if ($row) {
            sql_query(" update `{$g5['booking_calendar_table']}`
                set bd_price = '$price', bd_room_count = '$count' where bd_id = '{$row['bd_id']}' ", true);
        } else {
            sql_query(" insert into `{$g5['booking_calendar_table']}`
                set br_id = '$br_id', bd_date = '$date', bd_price = '$price', bd_room_count = '$count' ", true);
        }
    }

    goto_url($list_url);
}

// ---------------------------------------------------------------- 날짜별 요금 지정 (이 객실)
if (!preg_match('/^-?\d+$/', $in['set_price'])) {
    alert('요금을 숫자로 입력하세요. (지정을 해제하려면 -1)', $list_url);
}
$set_price = max(-1, (int)$in['set_price']);

for ($t = $start; $t <= $end; $t = strtotime('+1 day', $t)) {
    $date = date('Y-m-d', $t);
    $row = booking_calendar_row($br_id, $date);
    // 그 날의 판매 객실 수 지정은 그대로 둔다
    $count = $row ? (int)$row['bd_room_count'] : -1;

    if ($set_price < 0 && $count < 0) {
        // 둘 다 미지정이면 남길 게 없다 — 행을 지워 캘린더를 기본값 상태로 되돌린다
        if ($row) sql_query(" delete from `{$g5['booking_calendar_table']}` where bd_id = '{$row['bd_id']}' ", true);
    } else if ($row) {
        sql_query(" update `{$g5['booking_calendar_table']}`
            set bd_price = '$set_price', bd_room_count = '$count' where bd_id = '{$row['bd_id']}' ", true);
    } else {
        sql_query(" insert into `{$g5['booking_calendar_table']}`
            set br_id = '$br_id', bd_date = '$date', bd_price = '$set_price', bd_room_count = '$count' ", true);
    }
}

goto_url($list_url);
