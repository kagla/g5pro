<?php
$sub_menu = '600250';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '상품분류연결';
include_once(G5_ADMIN_PATH.'/admin.head.php');

$sel_id = (isset($_GET['ca_id']) && !is_array($_GET['ca_id'])) ? (int)$_GET['ca_id'] : 0;
$q = (isset($_GET['q']) && !is_array($_GET['q'])) ? trim($_GET['q']) : '';
$categories = cart_category_list();
$selected = $sel_id ? cart_category_get($sel_id) : null;
if ($sel_id && !$selected) { $sel_id = 0; $q = ''; }

$counts = array();
$result = sql_query(" select ca_id, count(*) as cnt from `{$g5['cart_item_category_table']}` group by ca_id ");
while ($r = sql_fetch_array($result)) $counts[(int)$r['ca_id']] = (int)$r['cnt'];

// 이 분류에 연결된 상품(직접 연결만 — 하위 분류 소속은 그 분류에서 관리)
$linked = array();
if ($selected) {
    $result = sql_query(" select i.it_id, i.it_code, i.it_name, i.it_price, i.it_show
        from `{$g5['cart_item_category_table']}` x
        inner join `{$g5['cart_item_table']}` i on i.it_id = x.it_id
        where x.ca_id = '$sel_id' order by i.it_id desc limit 500 ");
    while ($r = sql_fetch_array($result)) $linked[] = $r;
}

// 상품 검색 — 코드 완전 일치 우선(상품관리와 같은 흐름), 이미 연결된 상품은 표시만
$found = array();
if ($selected && $q !== '') {
    $exact = cart_item_get_by_code($q);
    $search_where = $exact ? " (it_code = '".sql_real_escape_string($q)."') " : cart_item_search_where($q);
    $linked_ids = array();
    foreach ($linked as $r) $linked_ids[(int)$r['it_id']] = true;
    $result = sql_query(" select it_id, it_code, it_name, it_price, it_show
        from `{$g5['cart_item_table']}` where $search_where order by it_id desc limit 30 ");
    while ($r = sql_fetch_array($result)) {
        $r['already'] = isset($linked_ids[(int)$r['it_id']]);
        $found[] = $r;
    }
}

cadm_view('category_item', array(
    'categories' => $categories,
    'selected' => $selected,
    'sel_id' => $sel_id,
    'counts' => $counts,
    'linked' => $linked,
    'found' => $found,
    'q' => $q,
    'self_url' => G5_ADMIN_URL.'/cart/category_item.php',
    'action_url' => G5_ADMIN_URL.'/cart/category_item_update.php',
    'category_url' => G5_ADMIN_URL.'/cart/category.php',
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
