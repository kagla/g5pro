<?php
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

if (isset($_SESSION['ss_mb_reg']))
    $mb = get_member($_SESSION['ss_mb_reg']);

// 회원정보가 없다면 초기 페이지로 이동
if (!(isset($mb['mb_id']) && $mb['mb_id']))
    goto_url(G5_URL);

$g5['title'] = '회원가입 완료';
include_once('./_head.php');
g5_map_register_result(); // g5pro
include_once('./_tail.php');