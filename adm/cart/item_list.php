<?php
$sub_menu = '600100';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '상품관리';
include_once(G5_ADMIN_PATH.'/admin.head.php');

$q = (isset($_GET['q']) && !is_array($_GET['q'])) ? trim($_GET['q']) : '';
$ca_id = (isset($_GET['ca_id']) && !is_array($_GET['ca_id'])) ? (int)$_GET['ca_id'] : 0;
$page = (isset($_GET['page']) && !is_array($_GET['page'])) ? max(1, (int)$_GET['page']) : 1;
// 한 페이지 개수 — 첫 값이 기본이고, 화이트리스트 밖 값은 기본으로 떨어뜨린다
// (주소 조작으로 수만 행을 한 번에 뽑지 못하게)
$per_options = array(20, 50, 100);
$per = (isset($_GET['per']) && !is_array($_GET['per'])) ? (int)$_GET['per'] : $per_options[0];
if (!in_array($per, $per_options, true)) $per = $per_options[0];
$rows_per = $per;

$where = array('1=1');
if ($q !== '') $where[] = cart_item_admin_search_where($q);
if ($ca_id) {
    $ids = cart_category_descendant_ids($ca_id);
    if ($ids) $where[] = " it_id IN (select it_id from `{$g5['ycart_item_category_table']}`
        where ca_id IN (".implode(',', $ids).")) ";
}
$where_sql = implode(' AND ', $where);

$cnt = sql_fetch(" select count(*) as cnt from `{$g5['ycart_item_table']}` where $where_sql ");
$total = (int)$cnt['cnt'];
$offset = ($page - 1) * $rows_per;

$items = array();
$result = sql_query(" select * from `{$g5['ycart_item_table']}`
    where $where_sql order by it_id desc limit $offset, $rows_per ");
while ($r = sql_fetch_array($result)) {
    $r['skus'] = cart_item_skus((int)$r['it_id']);
    $r['single'] = (count($r['skus']) === 1);
    $items[] = $r;
}

cadm_view('item_list', array(
    'items' => $items,
    'q' => $q,
    'ca_id' => $ca_id,
    'categories' => cart_category_list(),
    'total' => $total,
    'page' => $page,
    'per' => $per,
    'per_options' => $per_options,
    'total_page' => max(1, (int)ceil($total / $rows_per)),
    'self_url' => G5_CART_ADMIN_URL.'/item_list.php',
    'form_url' => G5_CART_ADMIN_URL.'/item_form.php',
    'update_url' => G5_CART_ADMIN_URL.'/item_list_update.php',
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
