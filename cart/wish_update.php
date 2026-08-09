<?php
/**
 * 찜 담기·빼기 — 상세 화면의 하트와 찜 목록의 빼기 버튼이 쓴다.
 *
 * 응답은 JSON 하나다. {ok:true, on:bool, count:int} 또는 {ok:false, msg, login}.
 * login:true 는 "로그인하면 되는 일" 이라는 뜻 — 화면이 오류 대신 로그인으로 안내한다.
 * 성공 시 바뀐 상태를 함께 돌려주어 화면이 다시 읽지 않고 하트만 고쳐 그린다.
 */

include_once('./_common.php');

check_token();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$out = function ($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
};

$mb_id = cart_wish_mb_id();
if ($mb_id === '') $out(array('ok' => false, 'login' => true, 'msg' => '로그인이 필요합니다.'));

$it_id = (isset($_POST['it_id']) && !is_array($_POST['it_id'])) ? (int)$_POST['it_id'] : 0;
if ($it_id < 1) $out(array('ok' => false, 'msg' => '상품을 찾을 수 없습니다.'));

$mode = (isset($_POST['mode']) && !is_array($_POST['mode'])) ? $_POST['mode'] : 'toggle';

// del 은 찜 목록에서 빼는 자리 — 이미 빠진 상태를 다시 눌러도 담기지 않게 토글과 나눈다
if ($mode === 'del') {
    cart_wish_remove($it_id, $mb_id);
    $out(array('ok' => true, 'on' => false, 'count' => cart_wish_count($it_id)));
}

$res = cart_wish_toggle($it_id, $mb_id);
if ($res['error'] !== '') $out(array('ok' => false, 'msg' => $res['error']));

$out(array('ok' => true, 'on' => $res['on'], 'count' => $res['count']));
