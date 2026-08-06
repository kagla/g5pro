<?php
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

$hidden_ca_ids = cart_hidden_category_ids();

// 노출 상품 공통 조건 — 목록(list.php)의 전체 경로와 같은 기준
$visible_where = " i.it_show = 1 ";
if ($hidden_ca_ids) {
    $visible_where .= " AND i.ca_id NOT IN (".implode(',', $hidden_ca_ids).") ";
}

// 상품 행 묶음 조회 — 섹션마다 같은 꼴이라 하나로 모은다. 카드의 분류명 줄(오늘의집의
// 브랜드 줄 자리)을 위해 분류 이름을 조인하고, 대표 이미지는 말미에 한 방 조회
function cart_index_fetch($where, $limit)
{
    global $g5;
    $rows = array();
    $result = sql_query(" select i.it_id, i.it_name, i.it_price, i.it_stock, c.ca_name
        from `{$g5['cart_item_table']}` i
        left join `{$g5['cart_category_table']}` c on c.ca_id = i.ca_id
        where $where order by i.it_id desc limit ".(int)$limit);
    while ($r = sql_fetch_array($result)) $rows[] = $r;
    return $rows;
}

// 신상품 — 전체에서 최신 8개
$new_items = cart_index_fetch($visible_where, 8);

// 최상위 분류 + 분류별 상품 행(상품 있는 분류만, 최대 4개 섹션)
$top_cats = array();
$sections = array();
foreach (cart_category_children(0, true) as $c) {
    $ca_id = (int)$c['ca_id'];
    $top_cats[] = array(
        'ca_id' => $ca_id,
        'name' => $c['ca_name'],
        'initial' => mb_substr($c['ca_name'], 0, 1, 'utf-8'),
        'href' => cart_url('list.php', array('ca_id' => $ca_id)),
        'img' => '',
    );
    if (count($sections) >= 4) continue;
    $ids = cart_category_descendant_ids($ca_id, true);
    $rows = cart_index_fetch(" i.it_show = 1 AND i.ca_id IN (".implode(',', $ids).") ", 8);
    if (!count($rows)) continue;
    $sections[] = array(
        'ca_id' => $ca_id,
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

// 분류 아이콘 — 그 분류 섹션의 첫 상품 사진을 쓴다(별도 아이콘 없이 사진 타일)
$cat_img = array();
foreach ($sections as $sec) {
    if (count($sec['items']) && $sec['items'][0]['img'] !== '') {
        $cat_img[$sec['ca_id']] = $sec['items'][0]['img'];
    }
}
foreach ($top_cats as $i => $c) {
    if (isset($cat_img[$c['ca_id']])) $top_cats[$i]['img'] = $cat_img[$c['ca_id']];
}

// 메인 배너 — 데모 배너 파일이 있으면 사진 배너, 없으면 그라데이션 배너로 폴백
$banner_url = is_file(G5_DATA_PATH.'/cart/demo/banner.jpg')
    ? G5_DATA_URL.'/cart/demo/banner.jpg' : '';

$is_member = isset($member['mb_id']) && $member['mb_id'] !== '';

$g5['title'] = '스토어';
g5_view('cart.index', array(
    'top_cats' => $top_cats,
    'new_items' => $new_items,
    'sections' => $sections,
    'banner_url' => $banner_url,
    'search_url' => cart_url('list.php'),
    'all_href' => cart_url('list.php'),
    'basket_href' => cart_url('basket.php'),
    'orders_href' => $is_member ? cart_url('order.php') : cart_url('guest.php'),
));
