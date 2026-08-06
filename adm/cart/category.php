<?php
$sub_menu = '600200';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '분류관리';
include_once(G5_ADMIN_PATH.'/admin.head.php');

$sel_id = (isset($_GET['ca_id']) && !is_array($_GET['ca_id'])) ? (int)$_GET['ca_id'] : 0;
$categories = cart_category_list();
$selected = $sel_id ? cart_category_get($sel_id) : null;
if ($sel_id && !$selected) $sel_id = 0;

// 분류별 연결 상품 수 — 트리에 함께 보여 삭제 가능 여부를 미리 알 수 있게
$counts = array();
$result = sql_query(" select ca_id, count(*) as cnt from `{$g5['cart_item_category_table']}` group by ca_id ");
while ($r = sql_fetch_array($result)) $counts[(int)$r['ca_id']] = (int)$r['cnt'];

cadm_view('category', array(
    'categories' => $categories,
    'selected' => $selected,
    'sel_id' => $sel_id,
    'counts' => $counts,
    'self_url' => G5_ADMIN_URL.'/cart/category.php',
    'action_url' => G5_ADMIN_URL.'/cart/category_update.php',
    'link_url' => G5_ADMIN_URL.'/cart/category_item.php',
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
