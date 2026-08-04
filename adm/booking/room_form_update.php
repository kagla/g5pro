<?php
$sub_menu = '950200';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

// $_POST 는 common.php 에서 이미 escape 되어 있다 — 가공할 때만 stripslashes/addslashes 를 짝지어 쓴다
$w     = isset($_POST['w']) ? preg_replace('/[^a-z]/', '', (string)$_POST['w']) : '';
$act   = isset($_POST['act']) ? preg_replace('/[^a-z]/', '', (string)$_POST['act']) : '';
$br_id = isset($_POST['br_id']) ? (int)$_POST['br_id'] : 0;

$image_dir = G5_DATA_PATH.'/booking';
$allow_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');

// ---------------------------------------------------------------- 삭제
if ($act == 'delete') {
    if ($w != 'u' || !$br_id) alert('올바른 방법으로 이용해 주십시오.');
    if (!sql_fetch(" select br_id from `{$g5['booking_room_table']}` where br_id = '$br_id' ")) {
        alert('등록된 객실이 아닙니다.', './room_list.php');
    }

    // 예약이 붙은 객실은 지우지 않는다 — 지난 예약의 객실 정보가 사라지면 대사·환불을 못 한다
    $cnt = sql_fetch(" select count(*) as cnt from `{$g5['booking_table']}`
        where br_id = '$br_id' and bk_status in ('confirmed','cancel_req') ");
    if ((int)$cnt['cnt'] > 0) {
        sql_query(" update `{$g5['booking_room_table']}` set br_use = 0 where br_id = '$br_id' ", true);
        alert('예약이 '.(int)$cnt['cnt'].'건 있어 삭제하지 않고 숨김 처리했습니다.', './room_list.php');
    }

    $result = sql_query(" select bi_file from `{$g5['booking_room_image_table']}` where br_id = '$br_id' ");
    while ($row = sql_fetch_array($result)) {
        $path = $image_dir.'/'.basename($row['bi_file']);
        if ($row['bi_file'] && is_file($path)) @unlink($path);
    }
    sql_query(" delete from `{$g5['booking_room_image_table']}` where br_id = '$br_id' ", true);
    sql_query(" delete from `{$g5['booking_calendar_table']}` where br_id = '$br_id' ", true);
    sql_query(" delete from `{$g5['booking_room_table']}` where br_id = '$br_id' ", true);

    goto_url('./room_list.php');
}

// ---------------------------------------------------------------- 등록/수정
$br_subject = isset($_POST['br_subject']) ? addslashes(trim(strip_tags(clean_xss_tags(stripslashes($_POST['br_subject']))))) : '';
if ($br_subject === '') alert('객실명을 입력하세요.');
$br_content = isset($_POST['br_content']) ? addslashes(clean_xss_tags(stripslashes($_POST['br_content']))) : '';

$br_base_person   = max(1, isset($_POST['br_base_person']) ? (int)$_POST['br_base_person'] : 1);
$br_max_person    = max($br_base_person, isset($_POST['br_max_person']) ? (int)$_POST['br_max_person'] : 1);
$br_person_price  = max(0, isset($_POST['br_person_price']) ? (int)$_POST['br_person_price'] : 0);
$br_room_count    = max(0, isset($_POST['br_room_count']) ? (int)$_POST['br_room_count'] : 0);
$br_weekday_price = max(0, isset($_POST['br_weekday_price']) ? (int)$_POST['br_weekday_price'] : 0);
$br_weekend_price = max(0, isset($_POST['br_weekend_price']) ? (int)$_POST['br_weekend_price'] : 0);
$br_use   = (isset($_POST['br_use']) && (int)$_POST['br_use']) ? 1 : 0;
$br_order = isset($_POST['br_order']) ? (int)$_POST['br_order'] : 0;

$sql_common = " br_subject = '$br_subject',
    br_content = '$br_content',
    br_base_person = '$br_base_person',
    br_max_person = '$br_max_person',
    br_person_price = '$br_person_price',
    br_room_count = '$br_room_count',
    br_weekday_price = '$br_weekday_price',
    br_weekend_price = '$br_weekend_price',
    br_use = '$br_use',
    br_order = '$br_order' ";

if ($w == '') {
    sql_query(" insert into `{$g5['booking_room_table']}` set $sql_common,
        br_datetime = '".G5_TIME_YMDHIS."' ", true);
    $br_id = sql_insert_id();
} else {
    if (!$br_id || !sql_fetch(" select br_id from `{$g5['booking_room_table']}` where br_id = '$br_id' ")) {
        alert('등록된 객실이 아닙니다.', './room_list.php');
    }
    sql_query(" update `{$g5['booking_room_table']}` set $sql_common where br_id = '$br_id' ", true);
}

// ---------------------------------------------------------------- 이미지
$bi_order = (isset($_POST['bi_order']) && is_array($_POST['bi_order'])) ? $_POST['bi_order'] : array();
foreach ($bi_order as $bi_id => $order) {
    $bi_id = (int)$bi_id;
    if (!$bi_id) continue;
    sql_query(" update `{$g5['booking_room_image_table']}` set bi_order = '".(int)$order."'
        where bi_id = '$bi_id' and br_id = '$br_id' ", true);
}

$bi_del = (isset($_POST['bi_del']) && is_array($_POST['bi_del'])) ? $_POST['bi_del'] : array();
foreach ($bi_del as $bi_id => $on) {
    $bi_id = (int)$bi_id;
    if (!$bi_id) continue;
    $row = sql_fetch(" select bi_file from `{$g5['booking_room_image_table']}`
        where bi_id = '$bi_id' and br_id = '$br_id' ");
    if (!$row) continue;
    $path = $image_dir.'/'.basename($row['bi_file']);
    if ($row['bi_file'] && is_file($path)) @unlink($path);
    sql_query(" delete from `{$g5['booking_room_image_table']}` where bi_id = '$bi_id' ", true);
}

// 실패한 파일은 사유와 함께 모아 마지막에 알린다 — 조용히 버리면 관리자가 왜 안 올라갔는지 알 수 없다
$upload_fail = array();

$has_upload = false;
if (isset($_FILES['bi_files']) && isset($_FILES['bi_files']['name']) && is_array($_FILES['bi_files']['name'])) {
    // 파일칸을 비워 둬도 UPLOAD_ERR_NO_FILE 항목이 하나 오므로, 실제로 고른 파일이 있을 때만 디렉터리를 손댄다
    foreach ($_FILES['bi_files']['error'] as $err) { if ($err !== UPLOAD_ERR_NO_FILE) { $has_upload = true; break; } }
}

if ($has_upload) {
    if (!is_dir($image_dir)) {
        @mkdir($image_dir, G5_DIR_PERMISSION, true);
        @chmod($image_dir, G5_DIR_PERMISSION);
    }
    if (!is_dir($image_dir) || !is_writable($image_dir)) {
        alert('업로드 디렉터리를 만들 수 없습니다. '.$image_dir.' 의 권한을 확인하십시오. (객실 정보는 저장되었습니다)', './room_list.php');
    }

    $row = sql_fetch(" select max(bi_order) as mx from `{$g5['booking_room_image_table']}` where br_id = '$br_id' ");
    $next_order = (int)$row['mx'] + 1;

    foreach (array_keys($_FILES['bi_files']['name']) as $i) {
        // 파일명은 사유 안내용으로만 쓴다 — 저장에는 쓰지 않는다
        $name = basename((string)$_FILES['bi_files']['name'][$i]);
        $name = preg_replace('/[^0-9A-Za-z가-힣 ._\-]/u', '', $name);
        $name = ($name === '') ? '(이름 없음)' : mb_substr($name, 0, 40);

        $error = $_FILES['bi_files']['error'][$i];
        if ($error === UPLOAD_ERR_NO_FILE) continue;   // 빈 파일칸 — 실패가 아니다
        if ($error !== UPLOAD_ERR_OK) {
            $upload_fail[] = ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE)
                ? "$name (용량 초과)" : "$name (전송 오류 $error)";
            continue;
        }

        $tmp = $_FILES['bi_files']['tmp_name'][$i];
        if (!is_uploaded_file($tmp)) { $upload_fail[] = "$name (업로드된 파일이 아님)"; continue; }

        // 원본 파일명은 믿지 않는다 — 확장자만 화이트리스트로 받고, 실제 이미지인지 한 번 더 확인한다
        $ext = strtolower((string)pathinfo($_FILES['bi_files']['name'][$i], PATHINFO_EXTENSION));
        if (!in_array($ext, $allow_ext, true)) {
            $upload_fail[] = "$name (허용하지 않는 확장자 — ".implode(', ', $allow_ext).' 만 가능)'; continue;
        }
        if (!@getimagesize($tmp)) { $upload_fail[] = "$name (이미지 파일이 아님)"; continue; }

        $file = md5(uniqid(mt_rand(), true)).'.'.$ext;
        if (!@move_uploaded_file($tmp, $image_dir.'/'.$file)) {
            $upload_fail[] = "$name (저장 실패 — 디렉터리 권한 확인)"; continue;
        }
        @chmod($image_dir.'/'.$file, G5_FILE_PERMISSION);

        sql_query(" insert into `{$g5['booking_room_image_table']}` set br_id = '$br_id',
            bi_file = '$file', bi_order = '".($next_order++)."' ", true);
    }
}

// 대표 이미지는 객실당 하나 — 지정이 없거나 지워졌으면 첫 이미지가 대표가 된다
$bi_main = isset($_POST['bi_main']) ? (int)$_POST['bi_main'] : 0;
sql_query(" update `{$g5['booking_room_image_table']}` set bi_main = 0 where br_id = '$br_id' ", true);
if ($bi_main && sql_fetch(" select bi_id from `{$g5['booking_room_image_table']}`
        where bi_id = '$bi_main' and br_id = '$br_id' ")) {
    sql_query(" update `{$g5['booking_room_image_table']}` set bi_main = 1 where bi_id = '$bi_main' ", true);
} else {
    $row = sql_fetch(" select bi_id from `{$g5['booking_room_image_table']}`
        where br_id = '$br_id' order by bi_order, bi_id limit 1 ");
    if ($row) sql_query(" update `{$g5['booking_room_image_table']}` set bi_main = 1
        where bi_id = '{$row['bi_id']}' ", true);
}

// 성공한 파일은 이미 저장됐다 — 실패분만 사유와 함께 알리고 목록으로 보낸다
if ($upload_fail) {
    alert(count($upload_fail).'건의 이미지를 올리지 못했습니다.\\n\\n'.implode('\\n', $upload_fail), './room_list.php');
}

goto_url('./room_list.php');
