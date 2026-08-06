<?php
if (!defined('_GNUBOARD_')) exit;

// ---------- 분류 ----------
// ca_path 는 자기 자신까지 포함한 '/1/5/23/' 꼴 — 서브트리는 ca_path LIKE '/1/5/%' 프리픽스로 잡는다

function cart_category_get($ca_id)
{
    global $g5;
    $row = sql_fetch(" select * from `{$g5['cart_category_table']}` where ca_id = '".(int)$ca_id."' ");
    return $row ? $row : null;
}

function cart_category_save($data, $ca_id = 0)
{
    global $g5;
    $parent = isset($data['ca_parent']) ? (int)$data['ca_parent'] : 0;
    $name = sql_real_escape_string(strip_tags(trim($data['ca_name'])));
    $order = isset($data['ca_order']) ? (int)$data['ca_order'] : 0;
    $show = !empty($data['ca_show']) ? 1 : 0;

    $ppath = '/';
    $depth = 1;
    if ($parent) {
        $prow = cart_category_get($parent);
        if (!$prow) return 0;
        // 최대 3단 — 부모가 이미 3단이면(=자신이 4단이 됨) 신규 생성 거부. 화면 안내문 "최대 3단"과 일치.
        if (!$ca_id && (int)$prow['ca_depth'] >= 3) return 0;
        $ppath = $prow['ca_path'];
        $depth = (int)$prow['ca_depth'] + 1;
    }

    if ($ca_id) {
        // 부모 변경은 v1 미지원(트리 재계산 비용·운영 혼란) — 이름·순서·노출만 수정
        sql_query(" update `{$g5['cart_category_table']}`
            set ca_name = '$name', ca_order = '$order', ca_show = '$show'
            where ca_id = '".(int)$ca_id."' ", true);
        return (int)$ca_id;
    }

    sql_query(" insert into `{$g5['cart_category_table']}`
        (ca_parent, ca_name, ca_path, ca_depth, ca_order, ca_show)
        values ('$parent', '$name', '$ppath', '$depth', '$order', '$show') ", true);
    $new_id = sql_insert_id();
    sql_query(" update `{$g5['cart_category_table']}`
        set ca_path = '".sql_real_escape_string($ppath.$new_id.'/')."'
        where ca_id = '$new_id' ", true);
    return (int)$new_id;
}

// 트리 순서(부모 아래 자식)로 평탄화한 전체 목록
// ca_path 문자열 정렬은 '/1/' < '/10/' < '/2/' 로 깨지므로 SQL 정렬에 기대지 않고
// PHP 에서 ca_parent 로 그룹핑한 뒤 각 형제를 (ca_order, ca_id) 순으로 재귀 평탄화한다.
// $only_show=true 는 캐스케이드다: 숨긴 분류의 하위는 자신이 노출이어도 함께 숨는다
// (숨긴 부모가 조회에서 빠지면 그 자식은 트리에서 도달 불가 — 쇼핑몰 분류의 의도된 의미론).
function cart_category_list($only_show = false)
{
    global $g5;
    $where = $only_show ? " where ca_show = 1 " : "";
    $by_parent = array();
    $result = sql_query(" select * from `{$g5['cart_category_table']}` $where
        order by ca_order, ca_id ");
    while ($r = sql_fetch_array($result)) $by_parent[(int)$r['ca_parent']][] = $r;

    $rows = array();
    cart_category_flatten($by_parent, 0, $rows);
    return $rows;
}

// cart_category_list() 내부 재귀 헬퍼 — 형제는 이미 (ca_order, ca_id) 순으로 들어와 있다
function cart_category_flatten($by_parent, $parent_id, &$rows)
{
    if (empty($by_parent[$parent_id])) return;
    foreach ($by_parent[$parent_id] as $r) {
        $rows[] = $r;
        cart_category_flatten($by_parent, (int)$r['ca_id'], $rows);
    }
}

function cart_category_children($ca_id, $only_show = true)
{
    global $g5;
    $and = $only_show ? " and ca_show = 1 " : "";
    $rows = array();
    $result = sql_query(" select * from `{$g5['cart_category_table']}`
        where ca_parent = '".(int)$ca_id."' $and order by ca_order, ca_id ");
    while ($r = sql_fetch_array($result)) $rows[] = $r;
    return $rows;
}

// 자기 자신 포함 서브트리 id 목록 — 목록 화면의 "하위 분류 상품 포함" 조회에 쓴다
function cart_category_descendant_ids($ca_id)
{
    global $g5;
    $row = cart_category_get($ca_id);
    if (!$row) return array();
    $ids = array();
    $like = sql_real_escape_string($row['ca_path']);
    $result = sql_query(" select ca_id from `{$g5['cart_category_table']}`
        where ca_path like '{$like}%' ");
    while ($r = sql_fetch_array($result)) $ids[] = (int)$r['ca_id'];
    return $ids;
}

// 빈 문자열이면 성공, 아니면 사용자에게 보여줄 거부 사유
function cart_category_delete($ca_id)
{
    global $g5;
    $ca_id = (int)$ca_id;
    $child = sql_fetch(" select count(*) as cnt from `{$g5['cart_category_table']}`
        where ca_parent = '$ca_id' ");
    if ($child['cnt'] > 0) return '하위 분류가 있어 삭제할 수 없습니다. 하위 분류를 먼저 정리하세요.';
    $item = sql_fetch(" select count(*) as cnt from `{$g5['cart_item_table']}`
        where ca_id = '$ca_id' ");
    if ($item['cnt'] > 0) return '이 분류에 상품 '.(int)$item['cnt'].'개가 있어 삭제할 수 없습니다. 상품의 분류를 먼저 옮기세요.';
    sql_query(" delete from `{$g5['cart_category_table']}` where ca_id = '$ca_id' ", true);
    return '';
}
