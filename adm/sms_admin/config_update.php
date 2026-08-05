<?php
$sub_menu = "900100";
include_once("./_common.php");

auth_check_menu($auth, $sub_menu, "w");

check_demo();

check_admin_token();

$g5['title'] = "SMS 기본설정";

// 업체 선택·연동 계정은 기본환경설정 > SMS설정이 맡는다 (config_form_update.php).
// 여기는 SMS 관리 고유 설정인 회신번호만 저장한다 — 같은 설정을 두 화면이 쓰면 관리가 어렵다
$cf_phone = isset($_REQUEST['cf_phone']) ? addslashes(clean_xss_tags(stripslashes($_REQUEST['cf_phone']), 1, 1)) : '';

// 회신번호 체크
if(!check_vaild_callback($cf_phone))
    alert('회신번호가 올바르지 않습니다.');

$res = sql_fetch("select * from ".$g5['sms5_config_table']." limit 1");

if (!$res)
    $sql = "insert into ";
else
    $sql = "update ";

$sql .= $g5['sms5_config_table']." set cf_phone='$cf_phone' ";

sql_query($sql);

goto_url("./config.php");
