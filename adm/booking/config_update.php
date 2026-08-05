<?php
$sub_menu = '950600';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

// $_POST 는 common.php 에서 이미 escape 되어 있다 — 가공할 때만 stripslashes/addslashes 를 짝지어 쓴다.
// 아래 검증은 모두 stripslashes 한 원문으로 하고, 저장 직전에만 addslashes 를 다시 건다
function booking_post_raw($name)
{
    return (isset($_POST[$name]) && !is_array($_POST[$name])) ? stripslashes((string)$_POST[$name]) : '';
}

// HH:MM 이면서 실제로 존재하는 시각이어야 한다 ("99:99" 는 형식만 맞고 시각이 아니다)
function booking_valid_time($time)
{
    if (!preg_match('/^(\d{2}):(\d{2})$/', $time, $m)) return false;
    return ((int)$m[1] <= 23 && (int)$m[2] <= 59);
}

$bc_checkin_time     = trim(booking_post_raw('bc_checkin_time'));
$bc_checkout_time    = trim(booking_post_raw('bc_checkout_time'));
$bc_sameday_deadline = trim(booking_post_raw('bc_sameday_deadline'));

if (!booking_valid_time($bc_checkin_time))     alert('체크인 시간을 24시간제 HH:MM 형식으로 입력하세요. (예 15:00)');
if (!booking_valid_time($bc_checkout_time))    alert('체크아웃 시간을 24시간제 HH:MM 형식으로 입력하세요. (예 11:00)');
if (!booking_valid_time($bc_sameday_deadline)) alert('당일 예약 마감 시각을 24시간제 HH:MM 형식으로 입력하세요. (예 18:00)');

$bc_hold_minutes = max(1, (int)booking_post_raw('bc_hold_minutes'));
$bc_open_months  = max(1, (int)booking_post_raw('bc_open_months'));
$bc_min_nights   = max(1, (int)booking_post_raw('bc_min_nights'));
// 최대 박수가 최소보다 작으면 아무 날짜도 예약할 수 없게 된다 — 최소 이상으로 끌어올린다
$bc_max_nights   = max($bc_min_nights, (int)booking_post_raw('bc_max_nights'));

// 취소 수수료 단계 — 한 줄당 "남은일수:환불율". 한 줄이라도 어긋나면 통째로 되돌린다
// (반만 저장되면 어떤 규정이 적용되는지 관리자가 알 수 없다)
$policy_lines = array();
// 배열 치환은 한 번에 훑는다 — CRLF 를 먼저 CR 로 바꾸면 빈 줄이 하나씩 늘어 오류 줄 번호가 어긋난다
// (브라우저 textarea 는 CRLF 로 보내므로 실사용 경로다)
$raw_lines = explode("\n", str_replace(array("\r\n", "\r"), "\n", booking_post_raw('bc_cancel_policy')));
foreach ($raw_lines as $no => $line) {
    $line = trim($line);
    if ($line === '') continue;
    if (!preg_match('/^(\d+):(\d+)$/', $line, $m)) {
        alert(($no + 1).'번째 줄이 "남은일수:환불율" 형식이 아닙니다. (입력값: '.$line.')');
    }
    if ((int)$m[2] > 100) {
        alert(($no + 1).'번째 줄의 환불율은 0~100 사이여야 합니다. (입력값: '.$line.')');
    }
    $policy_lines[] = (int)$m[1].':'.(int)$m[2];
}
// 빈 정책은 저장하지 않는다 — 저장되면 모든 취소가 환불 0% 로 굳고,
// 예약 화면의 취소 규정 고지도 통째로 사라진다 (고지 의무가 걸린 항목이다)
if (!count($policy_lines)) {
    alert('취소 수수료 단계를 최소 한 줄 입력하세요. 예: 7:100');
}
$bc_cancel_policy = addslashes(implode("\n", $policy_lines));

$bc_refund_terms = addslashes(clean_xss_tags(booking_post_raw('bc_refund_terms')));

// 이니시스 키는 영문·숫자와 몇 가지 기호뿐이다 — 붙여 넣을 때 딸려 오는 공백·줄바꿈만 털어 낸다.
// 길이는 컬럼 크기에 맞춰 여기서 자른다 (MySQL 에 맡기면 strict 모드에서 저장 자체가 실패한다)
$bc_inicis_mid        = addslashes(mb_substr(trim(strip_tags(booking_post_raw('bc_inicis_mid'))), 0, 20));
$bc_inicis_sign_key   = addslashes(mb_substr(trim(strip_tags(booking_post_raw('bc_inicis_sign_key'))), 0, 64));
$bc_inicis_iniapi_key = addslashes(mb_substr(trim(strip_tags(booking_post_raw('bc_inicis_iniapi_key'))), 0, 64));
$bc_inicis_iniapi_iv  = addslashes(mb_substr(trim(strip_tags(booking_post_raw('bc_inicis_iniapi_iv'))), 0, 64));
$bc_card_test         = (int)booking_post_raw('bc_card_test') ? 1 : 0;

// 빈 값은 "업주 알림을 끈다"는 뜻이라 허용한다
$bc_admin_email = mb_substr(trim(booking_post_raw('bc_admin_email')), 0, 255);
if ($bc_admin_email !== '' && !filter_var($bc_admin_email, FILTER_VALIDATE_EMAIL)) {
    alert('업주 알림 이메일 형식이 올바르지 않습니다. 알림을 받지 않으려면 비워 두십시오.');
}
$bc_admin_email = addslashes($bc_admin_email);

// 업주 알림 휴대폰 — 숫자만 남긴다. 빈 값은 "업주 문자를 끈다"는 뜻이라 허용한다
$bc_admin_hp = preg_replace('/[^0-9]/', '', booking_post_raw('bc_admin_hp'));
$bc_admin_hp = addslashes(mb_substr($bc_admin_hp, 0, 20));

// 행이 없으면 만들어 두고 갱신한다 (첫 저장이 조용히 사라지지 않게)
booking_config();

sql_query(" update `{$g5['booking_config_table']}` set
    bc_checkin_time = '$bc_checkin_time',
    bc_checkout_time = '$bc_checkout_time',
    bc_hold_minutes = '$bc_hold_minutes',
    bc_open_months = '$bc_open_months',
    bc_sameday_deadline = '$bc_sameday_deadline',
    bc_min_nights = '$bc_min_nights',
    bc_max_nights = '$bc_max_nights',
    bc_cancel_policy = '$bc_cancel_policy',
    bc_refund_terms = '$bc_refund_terms',
    bc_inicis_mid = '$bc_inicis_mid',
    bc_inicis_sign_key = '$bc_inicis_sign_key',
    bc_inicis_iniapi_key = '$bc_inicis_iniapi_key',
    bc_inicis_iniapi_iv = '$bc_inicis_iniapi_iv',
    bc_card_test = '$bc_card_test',
    bc_admin_email = '$bc_admin_email',
    bc_admin_hp = '$bc_admin_hp'
    where bc_id = 1 ", true);

// booking_config() 는 요청 단위 static 캐시라 지금 다시 읽어도 낡은 값이다 — 리다이렉트해 새 요청에서 읽는다
goto_url('./config_form.php');
