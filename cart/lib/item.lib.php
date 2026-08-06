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

// ---------- 상품 ----------

function cart_item_get($it_id)
{
    global $g5;
    $row = sql_fetch(" select * from `{$g5['cart_item_table']}` where it_id = '".(int)$it_id."' ");
    return $row ? $row : null;
}

function cart_item_get_by_code($code)
{
    global $g5;
    $code = sql_real_escape_string(trim($code));
    if ($code === '') return null;
    $row = sql_fetch(" select * from `{$g5['cart_item_table']}` where it_code = '$code' ");
    return $row ? $row : null;
}

function cart_item_save($data, $it_id = 0)
{
    global $g5;
    $code = sql_real_escape_string(trim($data['it_code']));
    $ca_id = (int)$data['ca_id'];
    $name = sql_real_escape_string(strip_tags(trim($data['it_name'])));
    $keyword = sql_real_escape_string(strip_tags(trim($data['it_keyword'])));
    $content = sql_real_escape_string($data['it_content']);
    $show = !empty($data['it_show']) ? 1 : 0;
    $shipping = isset($data['it_shipping_id']) ? (int)$data['it_shipping_id'] : 0;
    $now = G5_TIME_YMDHIS;

    if ($it_id) {
        sql_query(" update `{$g5['cart_item_table']}`
            set it_code = '$code', ca_id = '$ca_id', it_name = '$name', it_keyword = '$keyword',
                it_content = '$content', it_show = '$show', it_shipping_id = '$shipping',
                it_update = '$now'
            where it_id = '".(int)$it_id."' ", true);
        return (int)$it_id;
    }

    // it_code 는 UNIQUE — 빈 값 신규가 여럿 겹치지 않게 임시 유일값을 넣고 확정한다
    $tmp = $code !== '' ? $code : uniqid('PT', true);
    sql_query(" insert into `{$g5['cart_item_table']}`
        (it_code, ca_id, it_name, it_keyword, it_content, it_show, it_shipping_id, it_datetime, it_update)
        values ('".sql_real_escape_string($tmp)."', '$ca_id', '$name', '$keyword', '$content',
                '$show', '$shipping', '$now', '$now') ", true);
    $new_id = sql_insert_id();
    if ($code === '') {
        sql_query(" update `{$g5['cart_item_table']}` set it_code = 'P{$new_id}'
            where it_id = '$new_id' ", true);
    }
    return (int)$new_id;
}

// ---------- SKU ----------

function cart_sku_get($sk_id)
{
    global $g5;
    $row = sql_fetch(" select * from `{$g5['cart_sku_table']}` where sk_id = '".(int)$sk_id."' ");
    return $row ? $row : null;
}

function cart_sku_get_by_code($code)
{
    global $g5;
    $code = sql_real_escape_string(trim($code));
    if ($code === '') return null;
    $row = sql_fetch(" select * from `{$g5['cart_sku_table']}` where sk_code = '$code' ");
    return $row ? $row : null;
}

function cart_item_skus($it_id, $only_use = false)
{
    global $g5;
    $and = $only_use ? " and sk_use = 1 " : "";
    $rows = array();
    $result = sql_query(" select * from `{$g5['cart_sku_table']}`
        where it_id = '".(int)$it_id."' $and order by sk_id ");
    while ($r = sql_fetch_array($result)) $rows[] = $r;
    return $rows;
}

// 재고(sk_qty)는 여기서 만지지 않는다 — 전 증감이 stock_log 에 남게 stock.lib 만 재고를 쓴다
function cart_sku_save($data, $sk_id = 0)
{
    global $g5;
    $it_id = (int)$data['it_id'];
    $code = sql_real_escape_string(trim($data['sk_code']));
    $option = sql_real_escape_string(trim($data['sk_option']) !== '' ? trim($data['sk_option']) : '{}');
    $price = (int)$data['sk_price'];
    $barcode = sql_real_escape_string(strip_tags(trim($data['sk_barcode'])));
    $use = !empty($data['sk_use']) ? 1 : 0;

    if ($sk_id) {
        sql_query(" update `{$g5['cart_sku_table']}`
            set sk_code = '$code', sk_option = '$option', sk_price = '$price',
                sk_barcode = '$barcode', sk_use = '$use'
            where sk_id = '".(int)$sk_id."' ", true);
        cart_item_cache_refresh($it_id);
        return (int)$sk_id;
    }

    $tmp = $code !== '' ? $code : uniqid('ST', true);
    sql_query(" insert into `{$g5['cart_sku_table']}`
        (it_id, sk_code, sk_option, sk_price, sk_barcode, sk_use)
        values ('$it_id', '".sql_real_escape_string($tmp)."', '$option', '$price', '$barcode', '$use') ", true);
    $new_id = sql_insert_id();
    if ($code === '') {
        // it_code-NN — 상품 안에서 몇 번째 SKU 인지로 붙인다(자릿수 2, 100개 넘으면 그대로 숫자)
        // COUNT 로 NN 을 정하는 방식은 같은 상품에 SKU 가 동시에 생성되면 두 요청이 같은 NN 을
        // 계산할 수 있다(sk_code 는 UNIQUE) — 확정 UPDATE 를 재시도해 충돌을 피한다.
        $item = cart_item_get($it_id);
        $cnt = sql_fetch(" select count(*) as cnt from `{$g5['cart_sku_table']}` where it_id = '$it_id' ");
        $n = (int)$cnt['cnt'];
        $confirmed = false;
        for ($try = 0; $try < 5; $try++) {
            $auto = $item['it_code'].'-'.($n < 100 ? sprintf('%02d', $n) : $n);
            // 성공 판정은 affected_rows 가 아니라 "쿼리가 오류 없이 실행됐는가"로 한다 —
            // 지금 확정하려는 행의 현재 값은 uniqid() placeholder 라 목표 코드와 절대 같을 수
            // 없으므로(같은 값 재설정으로 인한 affected 0 은 원천적으로 없음) 오류 여부만 보면
            // 충분하다. MariaDB 는 UNIQUE 충돌 시 쿼리 자체가 실패(false)로 돌아온다.
            $ok = sql_query(" update `{$g5['cart_sku_table']}`
                set sk_code = '".sql_real_escape_string($auto)."' where sk_id = '$new_id' ", false);
            if ($ok) { $confirmed = true; break; }
            $n++;
        }
        if (!$confirmed) {
            // 5회 모두 충돌 — sk_id(PK) 기반 코드는 절대 겹치지 않으니 최종 폴백으로 확정한다
            $auto = $item['it_code'].'-'.$new_id;
            sql_query(" update `{$g5['cart_sku_table']}`
                set sk_code = '".sql_real_escape_string($auto)."' where sk_id = '$new_id' ", true);
        }
    }
    cart_item_cache_refresh($it_id);
    return (int)$new_id;
}

function cart_sku_delete($sk_id)
{
    global $g5;
    $row = cart_sku_get($sk_id);
    if (!$row) return;
    sql_query(" delete from `{$g5['cart_sku_table']}` where sk_id = '".(int)$sk_id."' ", true);
    cart_item_cache_refresh((int)$row['it_id']);
}

// 목록이 조인 없이 뜨게 하는 캐시 — SKU 가 바뀔 때마다 호출된다
function cart_item_cache_refresh($it_id)
{
    global $g5;
    $it_id = (int)$it_id;
    $sum = sql_fetch(" select min(sk_price) as min_price, sum(sk_qty) as total
        from `{$g5['cart_sku_table']}` where it_id = '$it_id' and sk_use = 1 ");
    $price = (int)$sum['min_price'];
    $stock = (int)$sum['total'];
    sql_query(" update `{$g5['cart_item_table']}`
        set it_price = '$price', it_stock = '$stock' where it_id = '$it_id' ", true);
}

// ---------- 검색 ----------

function cart_item_search_where($q)
{
    $q = trim($q);
    if ($q === '') return '1=1';
    $esc = sql_real_escape_string($q);
    if (cart_ft_available()) {
        return " MATCH(it_name, it_keyword) AGAINST('$esc' IN BOOLEAN MODE) ";
    }
    return " it_name LIKE '%$esc%' ";
}
