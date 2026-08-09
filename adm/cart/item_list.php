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

// 노출여부 — ''(전체)·'1'(노출만)·'0'(숨김만) 셋만. 숨김('0')도 뜻이 있어 문자열로 다룬다
$show = (isset($_GET['show']) && !is_array($_GET['show'])) ? $_GET['show'] : '';
if (!in_array($show, array('', '1', '0'), true)) $show = '';

$where = array('1=1');
if ($q !== '') $where[] = cart_item_admin_search_where($q);
if ($show !== '') $where[] = " it_show = '$show' ";
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

// 목록 썸네일 — 대표 사진 한 장씩, 한 방에 읽는다(행마다 묻던 것을 없앤다).
// 56x46 칸에 원본을 내려보내면 20행짜리 목록 하나가 수십 MB 가 된다.
// 자르지 않고 맞추는 쪽($crop=false)이라 지금처럼 사진 전체가 보인다 — 순정 thumbnail() 이
// 채우는 흰 여백은 칸 배경이 흰색이라 묻힌다. 크기는 칸의 2배(고해상도 화면 대비).
$main_images = cart_item_main_images(array_map(function ($r) { return (int)$r['it_id']; }, $items));
foreach ($items as $i => $r) {
    $file = isset($main_images[(int)$r['it_id']]) ? $main_images[(int)$r['it_id']] : '';
    $items[$i]['thumb_url'] = $file !== '' ? cart_item_thumb_url($file, 112, 92, false) : '';
}

cadm_view('item_list', array(
    'items' => $items,
    'q' => $q,
    'ca_id' => $ca_id,
    'show' => $show,
    'categories' => cart_category_list(),
    'total' => $total,
    'page' => $page,
    'per' => $per,
    'per_options' => $per_options,
    'total_page' => max(1, (int)ceil($total / $rows_per)),
    'self_url' => G5_CART_ADMIN_URL.'/item_list.php',
    'form_url' => G5_CART_ADMIN_URL.'/item_form.php',
    'update_url' => G5_CART_ADMIN_URL.'/item_list_update.php',
    // 목록에서 사진을 그 자리에서 갈아 끼울 때 쓰는 AJAX 주소
    'image_upload_url' => G5_CART_ADMIN_URL.'/item_image_upload.php',
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
