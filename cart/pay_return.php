<?php
include_once('./_common.php');

// PG 리턴 진입점 — 수단별 어댑터가 검증·승인·확정까지 마치고 완료 URL 을 돌려준다.
// 실패는 어댑터 안에서 alert(exit) 로 끝난다.
$m = (isset($_GET['m']) && !is_array($_GET['m'])) ? $_GET['m'] : '';

if ($m === 'inicis') {
    goto_url(cart_inicis_return());
}
if ($m === 'toss') {
    goto_url(cart_toss_return());
}

alert('잘못된 접근입니다.', cart_url(''));
