<?php
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

// 쿠폰함은 회원 전용 — 쿠폰이 회원 계정에 붙는 개념이라 비회원에게는 보여 줄 것이 없다
if (!isset($member['mb_id']) || $member['mb_id'] === '') {
    goto_url(G5_BBS_URL.'/login.php?url='.urlencode(G5_CART_URL.'/coupon.php'), false);
}

// 들어오는 이 순간이 자동 지급(가입 축하·첫 구매)의 발급 시점이다 — 순정 회원가입을
// 건드리지 않으려고 발급을 여기까지 미뤘다(cart_coupon_grant_auto 주석 참고)
cart_coupon_grant_auto($member['mb_id']);

$rows = cart_coupon_mine($member['mb_id']);
$live = 0;
foreach ($rows as $r) if ($r['live']) $live++;

$g5['title'] = '내 쿠폰함';
g5_view('cart.coupon', array(
    'rows' => $rows,
    'live_count' => $live,
    'total' => count($rows),
    'token' => get_token(),
    'action_url' => cart_url('coupon_update.php'),
    'home_href' => cart_url(''),
));
