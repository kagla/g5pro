<?php
include_once('./_common.php');
include_once(G5_CAPTCHA_PATH.'/captcha.lib.php');

// g5pro 직통 화면. 본인인증으로 찾기(cf_cert_find)도 뷰가 함께 그린다 —
// 예전에는 인증창 스크립트 때문에 그 경우만 순정 스킨에 넘겼는데, 그러면 같은 화면이
// 사이트 설정에 따라 옛 디자인으로 나왔다(g5_map_password_lost 주석 참고).
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