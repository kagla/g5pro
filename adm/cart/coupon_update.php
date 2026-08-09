<?php
$sub_menu = '600077';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$list_url = G5_CART_ADMIN_URL.'/coupon_list.php';
$post = function ($key) {
    return (isset($_POST[$key]) && !is_array($_POST[$key])) ? stripslashes(trim($_POST[$key])) : '';
};
$mode = $post('mode');

// ---------- 삭제 ----------
if ($mode === 'delete') {
    $err = cart_coupon_delete((int)$post('cp_id'));
    if ($err !== '') alert($err, $list_url);
    goto_url($list_url);
}

// ---------- 일괄 지급 ----------
// 회원 아이디를 줄바꿈·쉼표로 붙여 넣는다. 없는 아이디는 건너뛰고 끝에 알린다 —
// 하나가 틀렸다고 전체를 되돌리면 200명 붙여 넣은 사람이 처음부터 다시 해야 한다.
if ($mode === 'grant') {
    $cp_id = (int)$post('cp_id');
    $raw = str_replace(array("\r\n", "\r", ',', ' ', "\t"), "\n", $post('mb_ids'));
    $ids = array_filter(array_map('trim', explode("\n", $raw)));
    if (!count($ids)) alert('지급할 회원 아이디를 입력해 주세요.', G5_CART_ADMIN_URL.'/coupon_form.php?cp_id='.$cp_id);

    $r = cart_coupon_grant_many($cp_id, $ids);
    if ($r['error'] !== '') alert($r['error'], G5_CART_ADMIN_URL.'/coupon_form.php?cp_id='.$cp_id);
    $msg = $r['issued'].'명에게 지급했습니다.';
    if ($r['skipped']) $msg .= ' 이미 가진 회원 '.$r['skipped'].'명은 건너뛰었습니다.';
    if (count($r['unknown'])) {
        $head = implode(', ', array_slice($r['unknown'], 0, 5));
        if (count($r['unknown']) > 5) $head .= ' 외 '.(count($r['unknown']) - 5).'명';
        $msg .= ' 없는 아이디: '.$head;
    }
    alert($msg, G5_CART_ADMIN_URL.'/coupon_form.php?cp_id='.$cp_id);
}

// ---------- 등록·수정 ----------
$cp_id = (int)$post('cp_id');

$data = array(
    'cp_name' => $post('cp_name'),
    'cp_code' => $post('cp_code'),
    'cp_issue' => $post('cp_issue'),
    'cp_type' => $post('cp_type'),
    'cp_value' => (int)str_replace(',', '', $post('cp_value')),
    'cp_max' => (int)str_replace(',', '', $post('cp_max')),
    'cp_min' => (int)str_replace(',', '', $post('cp_min')),
    'cp_target' => $post('cp_target'),
    'cp_begin' => $post('cp_begin'),
    'cp_end' => $post('cp_end'),
    'cp_days' => (int)$post('cp_days'),
    'cp_use' => $post('cp_use') === '1' ? 1 : 0,
    'cp_memo' => $post('cp_memo'),
);

$back = $cp_id ? (G5_CART_ADMIN_URL.'/coupon_form.php?cp_id='.$cp_id) : (G5_CART_ADMIN_URL.'/coupon_form.php');

if ($data['cp_name'] === '') alert('쿠폰 이름을 입력하세요.', $back);
if (!isset(cart_coupon_issues()[$data['cp_issue']])) alert('발급 방법을 선택하세요.', $back);
if (!isset(cart_coupon_types()[$data['cp_type']])) alert('할인 방식을 선택하세요.', $back);
if ($data['cp_value'] < 1) alert('할인 값을 입력하세요.', $back);
if ($data['cp_type'] === 'rate' && $data['cp_value'] > 100) alert('정률 할인은 100%를 넘을 수 없습니다.', $back);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['cp_begin']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['cp_end'])) {
    alert('발급 기간을 날짜로 입력하세요.', $back);
}
if ($data['cp_begin'] > $data['cp_end']) alert('발급 시작일이 종료일보다 늦습니다.', $back);

// 코드 입력 쿠폰은 코드가 있어야 받을 길이 생긴다. 반대로 자동 지급 쿠폰의 코드는 비워 둔다 —
// 코드가 남아 있으면 "가입 축하 쿠폰" 을 아무나 코드로 받아 갈 수 있다.
if ($data['cp_issue'] === 'code') {
    if ($data['cp_code'] === '') alert('코드 입력 쿠폰은 쿠폰 코드가 필요합니다.', $back);
} else {
    $data['cp_code'] = '';
}
$err = cart_coupon_code_error($data['cp_code'], $cp_id);
if ($err !== '') alert($err, $back);

$new_id = cart_coupon_save($data, $cp_id);
goto_url(G5_CART_ADMIN_URL.'/coupon_form.php?cp_id='.(int)$new_id);
