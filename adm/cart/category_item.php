<?php
$sub_menu = '600250';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '상품분류연결';
include_once(G5_ADMIN_PATH.'/admin.head.php');

$sel_id = (isset($_GET['ca_id']) && !is_array($_GET['ca_id'])) ? (int)$_GET['ca_id'] : 0;
// 본체 GPC addslashes 를 벗긴다 — 안 벗기면 따옴표 든 검색어가 이중 이스케이프된다
$q = (isset($_GET['q']) && !is_array($_GET['q'])) ? stripslashes(trim($_GET['q'])) : '';
$categories = cart_category_list();
$selected = $sel_id ? cart_category_get($sel_id) : null;
if ($sel_id && !$selected) { $sel_id = 0; $q = ''; }

// 직계 하위 수 — 접기/펼치기 토글을 붙일 자리이자, 접었을 때 안에 몇 개가 숨었는지 알려 준다
$child_count = array();
foreach ($categories as $c) {
    $p = (int)$c['ca_parent'];
    if ($p) $child_count[$p] = isset($child_count[$p]) ? $child_count[$p] + 1 : 1;
}

$counts = array();
$result = sql_query(" select ca_id, count(*) as cnt from `{$g5['ycart_item_category_table']}` group by ca_id ");
while ($r = sql_fetch_array($result)) $counts[(int)$r['ca_id']] = (int)$r['cnt'];

// 이 분류에 연결된 상품(직접 연결만 — 하위 분류 소속은 그 분류에서 관리)
// 화면 표는 최신 500개까지만 — 총수는 $counts 가, '연결됨' 판정은 전체 id 목록이 맡는다
$linked = array();
$linked_ids = array();
if ($selected) {
    $result = sql_query(" select i.it_id, i.it_code, i.it_name, i.it_price, i.it_show
        from `{$g5['ycart_item_category_table']}` x
        inner join `{$g5['ycart_item_table']}` i on i.it_id = x.it_id
        where x.ca_id = '$sel_id' order by i.it_id desc limit 500 ");
    while ($r = sql_fetch_array($result)) $linked[] = $r;
    $result = sql_query(" select it_id from `{$g5['ycart_item_category_table']}` where ca_id = '$sel_id' ");
    while ($r = sql_fetch_array($result)) $linked_ids[(int)$r['it_id']] = true;
}

// 상품 검색 — 코드 완전 일치 우선(상품관리와 같은 흐름), 이미 연결된 상품은 표시만
$found = array();
if ($selected && $q !== '') {
    $exact = cart_item_get_by_code($q);
    $search_where = $exact ? " (it_code = '".sql_real_escape_string($q)."') " : cart_item_search_where($q);
    $result = sql_query(" select it_id, it_code, it_name, it_price, it_show
        from `{$g5['ycart_item_table']}` where $search_where order by it_id desc limit 30 ");
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
    'child_count' => $child_count,
    'linked' => $linked,
    'found' => $found,
    'q' => $q,
    'self_url' => G5_CART_ADMIN_URL.'/category_item.php',
    'action_url' => G5_CART_ADMIN_URL.'/category_item_update.php',
    'category_url' => G5_CART_ADMIN_URL.'/category.php',
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
