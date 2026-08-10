<?php
$sub_menu = '600065';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$back = G5_CART_ADMIN_URL.'/deposit_list.php';
$tab = (isset($_POST['tab']) && !is_array($_POST['tab'])) ? trim($_POST['tab']) : '';
if ($tab !== '') $back .= '?tab='.urlencode($tab);

$ids = (isset($_POST['od_id']) && is_array($_POST['od_id'])) ? $_POST['od_id'] : array();
if (!count($ids)) alert('처리할 주문을 선택하세요.', $back);

// 한 건씩 같은 문(cart_order_transition)을 지난다 — 일괄이라고 검사를 건너뛰지 않는다.
// 그 사이 손님이 취소했거나 다른 관리자가 먼저 처리한 건은 그 건만 실패하고 나머지는 계속한다.
$done = 0;
$fails = array();
foreach ($ids as $id) {
    $id = (int)$id;
    if ($id < 1) continue;
    $err = cart_order_transition($id, 'deposit', $member['mb_id']);
    if ($err === '') { $done++; continue; }
    $od = cart_order_get($id);
    $fails[] = ($od ? $od['od_no'] : '#'.$id).' — '.$err;
}

if (!count($fails)) alert($done.'건을 입금확인 처리했습니다.', $back);

// 실패가 있으면 무엇이 왜 안 됐는지 그대로 보여 준다 — "일부 실패" 만으로는 손댈 곳을 모른다
$msg = $done.'건을 처리했습니다. 다음 '.count($fails)."건은 처리하지 못했습니다:\\n\\n"
     .implode("\\n", array_slice($fails, 0, 10));
if (count($fails) > 10) $msg .= "\\n… 외 ".(count($fails) - 10).'건';
alert($msg, $back);
