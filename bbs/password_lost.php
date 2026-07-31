<?php
include_once('./_common.php');
include_once(G5_CAPTCHA_PATH.'/captcha.lib.php');

// g5pro 직통 화면. 단 본인인증으로 찾기(cf_cert_find)를 켠 사이트는 순정 스킨 안의
// 인증창 스크립트(간편·휴대폰·아이핀)가 그 화면의 동작이므로 직통을 포기하고 순정에 맡긴다.
if (!($config['cf_cert_use'] && $config['cf_cert_find']))
    define('G5_PRO_PAGE', true);

if ($is_member) {
    alert("이미 로그인중입니다.", G5_URL);
}

$g5['title'] = '회원정보 찾기';
include_once(G5_PATH.'/_head.php');

$action_url = G5_HTTPS_BBS_URL."/password_lost2.php";
if (pro_takeover())
    g5_map_password_lost(); // g5pro — 스킨 대신 직통 매핑
else
    include_once($member_skin_path.'/password_lost.skin.php');

include_once(G5_PATH.'/_tail.php');