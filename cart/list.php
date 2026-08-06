<?php
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

$ca_id = (isset($_GET['ca_id']) && !is_array($_GET['ca_id'])) ? (int)$_GET['ca_id'] : 0;
$q = (isset($_GET['q']) && !is_array($_GET['q'])) ? trim(strip_tags($_GET['q'])) : '';
$sort = (isset($_GET['sort']) && !is_array($_GET['sort'])) ? $_GET['sort'] : 'new';
$page = (isset($_GET['page']) && !is_array($_GET['page'])) ? max(1, (int)$_GET['page']) : 1;
$rows_per = 24;

$category = $ca_id ? cart_category_get($ca_id) : null;
if ($ca_id && (!$category || !$category['ca_show'])) alert('없는 분류입니다.', cart_url('list.php'));

$where = array(" it_show = 1 ");
if ($category) {
    $ids = cart_category_descendant_ids($ca_id);
    $where[] = " ca_id IN (".implode(',', $ids).") ";
}
if ($q !== '') $where[] = cart_item_search_where($q);
$where_sql = implode(' AND ', $where);

// 정렬 — list_new/list_price 커버링 인덱스와 짝을 맞춘 세 가지만
$orders = array(
    'new' => ' it_id desc ',
    'low' => ' it_price asc, it_id desc ',
    'high' => ' it_price desc, it_id desc ',
);
if (!isset($orders[$sort])) $sort = 'new';

$cnt = sql_fetch(" select count(*) as cnt from `{$g5['cart_item_table']}` where $where_sql ");
$total = (int)$cnt['cnt'];
$total_page = max(1, (int)ceil($total / $rows_per));
if ($page > $total_page) $page = $total_page;
$offset = ($page - 1) * $rows_per;

$items = array();
$result = sql_query(" select it_id, it_name, it_price, it_stock from `{$g5['cart_item_table']}`
    where $where_sql order by {$orders[$sort]} limit $offset, $rows_per ");
while ($r = sql_fetch_array($result)) {
    $imgs = cart_item_images((int)$r['it_id']);
    $r['img'] = count($imgs) ? cart_item_image_url($imgs[0]['im_file']) : '';
    $r['href'] = cart_url('item.php', array('it_id' => $r['it_id']));
    $items[] = $r;
}

// 하위 분류 내비 — 현재 분류의 자식들(최상위면 최상위들)
$children = array();
foreach (cart_category_children($ca_id, true) as $c) {
    $c['href'] = cart_url('list.php', array('ca_id' => $c['ca_id']));
    $children[] = $c;
}

$base_qs = array('ca_id' => $ca_id, 'q' => $q, 'sort' => $sort);
$sorts = array();
foreach (array('new' => '신상품', 'low' => '낮은가격', 'high' => '높은가격') as $k => $name) {
    $sorts[] = array('name' => $name, 'active' => ($sort === $k),
        'href' => cart_url('list.php', array_merge($base_qs, array('sort' => $k))));
}
$pages = array();
for ($p = max(1, $page - 4); $p <= min($total_page, $page + 4); $p++) {
    $pages[] = array('num' => $p, 'current' => ($p === $page),
        'href' => cart_url('list.php', array_merge($base_qs, array('page' => $p))));
}

$g5['title'] = $category ? $category['ca_name'] : ($q !== '' ? '"'.$q.'" 검색' : '전체 상품');
g5_view('cart.list', array(
    'items' => $items,
    'categories' => $children,
    'category' => $category,
    'q' => $q,
    'sorts' => $sorts,
    'page' => $page,
    'total_page' => $total_page,
    'total_count' => $total,
    'pages' => $pages,
    'search_url' => cart_url('list.php'),
));
