<?php
$sub_menu = '600200';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '분류관리';
include_once(G5_ADMIN_PATH.'/admin.head.php');

$categories = cart_category_list();
// 부모 선택지 — 2단계까지만 부모가 될 수 있게(최대 3단 트리, 운영 단순화)
$parent_options = array();
foreach ($categories as $c) {
    if ((int)$c['ca_depth'] <= 2) $parent_options[] = $c;
}
// 분류별 상품 수 — 목록에 함께 보여 삭제 가능 여부를 미리 알 수 있게
$counts = array();
$result = sql_query(" select ca_id, count(*) as cnt from `{$g5['cart_item_table']}` group by ca_id ");
while ($r = sql_fetch_array($result)) $counts[(int)$r['ca_id']] = (int)$r['cnt'];

cadm_view('category', array(
    'categories' => $categories,
    'parent_options' => $parent_options,
    'counts' => $counts,
    'action_url' => G5_ADMIN_URL.'/cart/category_update.php',
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
