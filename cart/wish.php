<?php
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

// 찜은 회원 것이다 — 비회원에게는 목록이 있을 수 없으므로 로그인으로 보낸다.
// 돌아올 자리는 이 화면(로그인하면 바로 자기 찜 목록이 열린다).
$wish_url = cart_url('wish.php');
$mb_id = cart_wish_mb_id();
if ($mb_id === '') {
    goto_url(G5_BBS_URL.'/login.php?url='.urlencode($wish_url));
}

$page = (isset($_GET['page']) && !is_array($_GET['page'])) ? max(1, (int)$_GET['page']) : 1;
$rows_per = 24;

$total = cart_wish_total($mb_id);
$total_page = max(1, (int)ceil($total / $rows_per));
if ($page > $total_page) $page = $total_page;

$items = cart_wish_rows($mb_id, ($page - 1) * $rows_per, $rows_per);

$pages = array();
for ($p = max(1, $page - 4); $p <= min($total_page, $page + 4); $p++) {
    $pages[] = array('num' => $p, 'current' => ($p === $page),
        'href' => cart_url('wish.php', array('page' => $p)));
}

$g5['title'] = '찜한 상품';
g5_view('cart.wish', array(
    'items' => $items,
    'total' => $total,
    'pages' => $pages,
    'total_page' => $total_page,
    'home_href' => cart_url(''),
    'token' => get_token(),
    'wish_action' => cart_url('wish_update.php'),
));
