<?php
/**
 * 비회원 글쓰기 관문 2단계 — 답을 받아 토큰을 내준다.
 *
 * write_check.js.php 가 내려 준 문제를 실제로 풀어야 여기를 통과한다.
 * 답이 맞으면 세션에 토큰을 넣고 그 값을 돌려준다. 글쓰기 폼의 칸을 채우는 것은
 * 그 응답을 받은 자바스크립트다.
 *
 * 짝이 되는 검사는 extend/bot_guard.extend.php 에 있다.
 */

include_once(dirname(dirname(__DIR__)).'/common.php');

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$c = isset($_POST['c']) ? trim($_POST['c']) : '';
$a = isset($_POST['a']) ? trim($_POST['a']) : '';

$token = bg_answer_challenge($c, $a);

if (!$token) {
    header('HTTP/1.1 400 Bad Request');
    exit;
}

echo $token;
