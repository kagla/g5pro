<?php
$sub_menu = '950600';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');

$g5['title'] = '예약 환경설정';
include_once(G5_ADMIN_PATH.'/admin.head.php');

// 설정은 한 행(bc_id=1)뿐이다. 행이 없으면 booking_config() 가 기본값으로 만들어 돌려준다.
// 순정 전역 $config(기본환경설정)와 이름이 겹치지 않도록 $bc 로 받는다
$bc = booking_config();

badm_view('config_form', array('bc' => $bc, 'admin_url' => G5_ADMIN_URL));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
