<?php
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

$hidden_ca_ids = cart_hidden_category_ids();

// 노출 상품 공통 조건 — 목록(list.php)의 전체 경로와 같은 기준
$visible_where = " it_show = 1 ";
if ($hidden_ca_ids) {
    $visible_where .= " AND ca_id NOT IN (".implode(',', $hidden_ca_ids).") ";
}

// 상품 행 묶음 조회 — 섹션마다 같은 꼴이라 하나로 모은다. 대표 이미지는 말미에 한 방 조회
function cart_index_fetch($where, $limit)
{
    global $g5;
    $rows = array();
    $result = sql_query(" select it_id, it_name, it_price, it_stock from `{$g5['cart_item_table']}`
        where $where order by it_id desc limit ".(int)$limit);
    while ($r = sql_fetch_array($result)) $rows[] = $r;
    return $rows;
}

// 신상품 — 전체에서 최신 8개
$new_items = cart_index_fetch($visible_where, 8);

// 최상위 분류 칩 + 분류별 상품 행(상품 있는 분류만, 최대 4개 섹션)
$top_cats = array();
$sections = array();
foreach (cart_category_children(0, true) as $c) {
    $ca_id = (int)$c['ca_id'];
    $top_cats[] = array(
        'name' => $c['ca_name'],
        'initial' => mb_substr($c['ca_name'], 0, 1, 'utf-8'),
        'href' => cart_url('list.php', array('ca_id' => $ca_id)),
    );
    if (count($sections) >= 4) continue;
    $ids = cart_category_descendant_ids($ca_id, true);
    $rows = cart_index_fetch(" it_show = 1 AND ca_id IN (".implode(',', $ids).") ", 8);
    if (!count($rows)) continue;
    $sections[] = array(
        'name' => $c['ca_name'],
        'href' => cart_url('list.php', array('ca_id' => $ca_id)),
        'items' => $rows,
    );
}

// 대표 이미지 일괄 — 신상품 + 전 섹션을 한 번에
$all_ids = array_column($new_items, 'it_id');
foreach ($sections as $sec) {
    $all_ids = array_merge($all_ids, array_column($sec['items'], 'it_id'));
}
$main_images = cart_item_main_images(array_values(array_unique($all_ids)));

function cart_index_decorate($rows, $main_images)
{
    $out = array();
    foreach ($rows as $r) {
        $it_id = (int)$r['it_id'];
        $r['img'] = isset($main_images[$it_id]) ? cart_item_image_url($main_images[$it_id]) : '';
        $r['href'] = cart_url('item.php', array('it_id' => $it_id));
        $out[] = $r;
    }
    return $out;
}

$new_items = cart_index_decorate($new_items, $main_images);
foreach ($sections as $i => $sec) {
    $sections[$i]['items'] = cart_index_decorate($sec['items'], $main_images);
}

$g5['title'] = '스토어';
g5_view('cart.index', array(
    'top_cats' => $top_cats,
    'new_items' => $new_items,
    'sections' => $sections,
    'search_url' => cart_url('list.php'),
    'all_href' => cart_url('list.php'),
));
