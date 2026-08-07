<?php
/**
 * 저장된 배송지(주소록) 삭제 — 주문서 화면의 AJAX 요청만 받는다.
 *
 * 주소록은 회원 자신의 것만 다룬다. 삭제는 라이브러리가 mb_id 를 조건에 함께 넣어
 * 남의 ad_id 를 보내도 아무 행에 닿지 않는다(여기서도 로그인 여부를 먼저 막는다).
 *
 * 응답은 JSON 하나다. {ok:true, addresses:[...]} 또는 {ok:false, msg}.
 * 성공 시 남은 목록을 함께 돌려주어 화면이 다시 읽지 않고 select 를 새로 그린다.
 */

include_once('./_common.php');

check_token();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$out = function ($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
};

if (empty($member['mb_id'])) $out(array('ok' => false, 'msg' => '로그인이 필요합니다.'));

$ad_id = (isset($_POST['ad_id']) && !is_array($_POST['ad_id'])) ? (int)$_POST['ad_id'] : 0;
if ($ad_id < 1) $out(array('ok' => false, 'msg' => '지울 배송지를 고르세요.'));

if (!cart_address_delete($member['mb_id'], $ad_id)) {
    $out(array('ok' => false, 'msg' => '이미 지워졌거나 내 배송지가 아닙니다.'));
}

// 화면이 곧바로 다시 그릴 수 있게 남은 목록을 화면용 형태로 추려 준다
$list = array();
foreach (cart_address_list($member['mb_id']) as $a) {
    $list[] = array(
        'id'    => (int)$a['ad_id'],
        'name'  => $a['ad_name'],
        'hp'    => $a['ad_hp'],
        'email' => isset($a['ad_email']) ? $a['ad_email'] : '',
        'zip'   => $a['ad_zip'],
        'addr1' => $a['ad_addr1'],
        'addr2' => $a['ad_addr2'],
    );
}
$out(array('ok' => true, 'addresses' => $list));
