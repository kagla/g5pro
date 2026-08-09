<?php
include_once('./_common.php');

check_token();

$back = cart_url('coupon.php');
if (!isset($member['mb_id']) || $member['mb_id'] === '') {
    alert('로그인 후 이용해 주세요.', G5_BBS_URL.'/login.php');
}

$post = function ($key) {
    return (isset($_POST[$key]) && !is_array($_POST[$key])) ? stripslashes(trim($_POST[$key])) : '';
};

if ($post('mode') !== 'redeem') alert('잘못된 요청입니다.', $back);

// 코드 입력은 "쿠폰함에 담는 방법 중 하나" 일 뿐이다 — 담기고 나면 관리자가 지급한 장과
// 완전히 같은 길로 흐른다(주문서에서 골라 쓴다).
$r = cart_coupon_redeem_code($member['mb_id'], $post('code'));
alert($r['msg'], $back);
