<?php
$sub_menu = '950400';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

// $_POST 는 common.php 에서 이미 escape 되어 있다 — 가공할 때만 stripslashes/addslashes 를 짝지어 쓴다
// 배열로 오지 않은 입력은 통째로 버린다 — 문자열에 [$key] 를 하면 엉뚱한 글자가 값이 된다
$in = array();
foreach (array('ba_id', 'ba_subject', 'ba_price', 'ba_max_qty', 'ba_use', 'ba_order', 'del') as $name) {
    $in[$name] = (isset($_POST[$name]) && is_array($_POST[$name])) ? $_POST[$name] : array();
}

// 1) 먼저 전 행을 읽고 검증한다 — 중간에 멈추면 앞줄만 저장된 채 끝나 버린다
$rows = array();
foreach ($in['ba_id'] as $key => $val) {
    if (is_array($val)) continue;
    $ba_id = (int)$val;

    // 입력칸은 모두 행 번호를 키로 쓴다 — 체크 안 한 체크박스가 빠져도 행이 어긋나지 않는다
    if (isset($in['del'][$key]) && (int)$in['del'][$key]) {
        if ($ba_id) $rows[] = array('act' => 'delete', 'ba_id' => $ba_id);
        continue;
    }

    $ba_subject = (isset($in['ba_subject'][$key]) && !is_array($in['ba_subject'][$key]))
        ? addslashes(trim(strip_tags(clean_xss_tags(stripslashes($in['ba_subject'][$key]))))) : '';

    // 맨 위 신규 행은 비워 둔 채 저장하는 게 정상이다 — 이름이 있을 때만 넣는다
    if ($ba_subject === '') {
        if ($ba_id) alert('부가상품명을 입력하세요. (번호 '.$ba_id.')');
        continue;
    }

    $rows[] = array(
        'act'        => $ba_id ? 'update' : 'insert',
        'ba_id'      => $ba_id,
        'ba_subject' => $ba_subject,
        'ba_price'   => max(0, isset($in['ba_price'][$key]) ? (int)$in['ba_price'][$key] : 0),
        'ba_max_qty' => max(1, isset($in['ba_max_qty'][$key]) ? (int)$in['ba_max_qty'][$key] : 1),
        'ba_use'     => (isset($in['ba_use'][$key]) && (int)$in['ba_use'][$key]) ? 1 : 0,
        'ba_order'   => isset($in['ba_order'][$key]) ? (int)$in['ba_order'][$key] : 0,
    );
}

// 2) 검증을 통과한 뒤에 쓴다
foreach ($rows as $r) {
    if ($r['act'] == 'delete') {
        // 예약에 담긴 부가상품은 booking_addon_item 에 스냅샷으로 남으므로 지워도 지난 예약이 상하지 않는다
        sql_query(" delete from `{$g5['booking_addon_table']}` where ba_id = '{$r['ba_id']}' ", true);
        sql_query(" delete from `{$g5['booking_room_addon_table']}` where ba_id = '{$r['ba_id']}' ", true);
        continue;
    }

    $sql_common = " ba_subject = '{$r['ba_subject']}',
        ba_price = '{$r['ba_price']}',
        ba_max_qty = '{$r['ba_max_qty']}',
        ba_use = '{$r['ba_use']}',
        ba_order = '{$r['ba_order']}' ";

    if ($r['act'] == 'update') {
        sql_query(" update `{$g5['booking_addon_table']}` set $sql_common where ba_id = '{$r['ba_id']}' ", true);
    } else {
        sql_query(" insert into `{$g5['booking_addon_table']}` set $sql_common ", true);
    }
}

// "전 객실에 추가" 버튼 — 위 저장을 마친 뒤 그 상품을 모든 객실에 매핑한다.
// insert ignore 라 이미 붙은 객실의 순서는 그대로다. 필요 없는 객실에서는 객실 수정에서 빼면 된다
$attach_all = (isset($_POST['attach_all']) && !is_array($_POST['attach_all'])) ? (int)$_POST['attach_all'] : 0;
if ($attach_all) {
    $ba = sql_fetch(" select ba_subject, ba_order from `{$g5['booking_addon_table']}` where ba_id = '$attach_all' ");
    if ($ba) {
        sql_query(" insert ignore into `{$g5['booking_room_addon_table']}` (br_id, ba_id, bra_order)
            select br_id, '$attach_all', '".(int)$ba['ba_order']."' from `{$g5['booking_room_table']}` ", true);
        $row = sql_fetch(" select count(*) as cnt from `{$g5['booking_room_addon_table']}` where ba_id = '$attach_all' ");
        alert('저장했습니다. 이 상품은 이제 객실 '.(int)$row['cnt'].'곳에 담겨 있습니다.', './addon_list.php');
    }
}

goto_url('./addon_list.php');
