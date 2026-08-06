<?php
if (!defined('_GNUBOARD_')) exit;

// ---------- 분류 ----------
// ca_path 는 자기 자신까지 포함한 '/1/5/23/' 꼴 — 서브트리는 ca_path LIKE '/1/5/%' 프리픽스로 잡는다

// 분류 최대 깊이 — 생성·이동·부모 선택지가 전부 이 값 하나를 본다 (2026-08-06 3→5단 확대)
if (!defined('CART_CA_MAX_DEPTH')) define('CART_CA_MAX_DEPTH', 5);

function cart_category_get($ca_id)
{
    global $g5;
    $row = sql_fetch(" select * from `{$g5['cart_category_table']}` where ca_id = '".(int)$ca_id."' ");
    return $row ? $row : null;
}

// 자동 분류코드 — 영문 소문자+숫자 10자리, UNIQUE 충돌 시 재시도
function cart_category_code_generate()
{
    global $g5;
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    while (true) {
        $code = '';
        for ($i = 0; $i < 10; $i++) $code .= $chars[mt_rand(0, 35)];
        $dup = sql_fetch(" select ca_id from `{$g5['cart_category_table']}` where ca_code = '$code' ");
        if (!$dup) return $code;
    }
}

// 수동 입력 코드 검증 — 빈 문자열이면 통과, 아니면 사용자에게 보여줄 사유.
// 형식 검사 뒤 중복 검사(자기 자신 제외). 최후 방어는 UNIQUE 인덱스가 맡는다.
function cart_category_code_error($code, $except_ca_id = 0)
{
    global $g5;
    if (!preg_match('/^[A-Za-z0-9_-]{1,20}$/', $code)) {
        return '분류코드는 영문·숫자·하이픈·언더라인 1~20자입니다.';
    }
    $row = sql_fetch(" select ca_id from `{$g5['cart_category_table']}`
        where ca_code = '".sql_real_escape_string($code)."' and ca_id <> '".(int)$except_ca_id."' ");
    return $row ? '이미 쓰는 분류코드입니다: '.$code : '';
}

function cart_category_get_by_code($code)
{
    global $g5;
    $code = trim($code);
    if ($code === '') return null;
    $row = sql_fetch(" select * from `{$g5['cart_category_table']}`
        where ca_code = '".sql_real_escape_string($code)."' ");
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
        // 최대 깊이 — 부모가 이미 한계 단이면(=자신이 한계+1단이 됨) 신규 생성 거부
        if (!$ca_id && (int)$prow['ca_depth'] >= CART_CA_MAX_DEPTH) return 0;
        $ppath = $prow['ca_path'];
        $depth = (int)$prow['ca_depth'] + 1;
    }

    // 설명·기본 정렬 — 화이트리스트 밖 정렬 값은 빈 값(몰 기본)으로
    $desc = sql_real_escape_string(mb_substr(strip_tags(trim(isset($data['ca_desc']) ? $data['ca_desc'] : '')), 0, 500, 'utf-8'));
    $sort = isset($data['ca_sort']) && in_array($data['ca_sort'], array('new', 'low', 'high'), true)
        ? $data['ca_sort'] : '';

    if ($ca_id) {
        // 부모 변경은 여기서 안 한다 — 드래그 이동(cart_category_move)이 트리 재계산까지 책임진다
        // 분류코드는 넘어온 경우에만 바꾼다(검증은 호출부가 cart_category_code_error 로 마친 뒤)
        $code_set = isset($data['ca_code']) && $data['ca_code'] !== ''
            ? ", ca_code = '".sql_real_escape_string($data['ca_code'])."'" : '';
        sql_query(" update `{$g5['cart_category_table']}`
            set ca_name = '$name', ca_order = '$order', ca_show = '$show',
                ca_desc = '$desc', ca_sort = '$sort' $code_set
            where ca_id = '".(int)$ca_id."' ", true);
        return (int)$ca_id;
    }

    sql_query(" insert into `{$g5['cart_category_table']}`
        (ca_parent, ca_code, ca_name, ca_path, ca_depth, ca_order, ca_show)
        values ('$parent', '".cart_category_code_generate()."', '$name', '$ppath', '$depth', '$order', '$show') ", true);
    $new_id = sql_insert_id();
    sql_query(" update `{$g5['cart_category_table']}`
        set ca_path = '".sql_real_escape_string($ppath.$new_id.'/')."'
        where ca_id = '$new_id' ", true);
    return (int)$new_id;
}

// 분류 이미지 — 스토어홈 원형 칩 등에서 쓴다. data/cart/category/ 아래 한 분류 한 파일.
function cart_category_image_url($file)
{
    return $file !== '' ? G5_DATA_URL.'/cart/category/'.$file : '';
}

function cart_category_image_save($ca_id, $file)
{
    global $g5;
    $ca_id = (int)$ca_id;
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) return '업로드 실패(코드 '.$file['error'].')';
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp'))) return '이미지 파일만 올릴 수 있습니다.';
    if (!@getimagesize($file['tmp_name'])) return '이미지가 아닙니다.';

    $dir = G5_DATA_PATH.'/cart/category';
    if (!is_dir($dir)) { @mkdir($dir, G5_DIR_PERMISSION, true); @chmod($dir, G5_DIR_PERMISSION); }
    $name = $ca_id.'_'.substr(md5(uniqid(mt_rand(), true)), 0, 8).'.'.$ext;
    if (!move_uploaded_file($file['tmp_name'], $dir.'/'.$name)) return '파일 저장 실패';
    @chmod($dir.'/'.$name, G5_FILE_PERMISSION);

    cart_category_image_delete($ca_id); // 기존 파일 정리 후 교체
    sql_query(" update `{$g5['cart_category_table']}`
        set ca_img = '".sql_real_escape_string($name)."' where ca_id = '$ca_id' ", true);
    return '';
}

function cart_category_image_delete($ca_id)
{
    global $g5;
    $row = cart_category_get($ca_id);
    if (!$row || $row['ca_img'] === '') return;
    @unlink(G5_DATA_PATH.'/cart/category/'.$row['ca_img']);
    sql_query(" update `{$g5['cart_category_table']}` set ca_img = '' where ca_id = '".(int)$ca_id."' ", true);
}

// 드래그 이동 — $ca_id 를 $parent 아래로 옮기고 $after_id 형제 바로 뒤에 둔다(0 = 맨 앞).
// 서브트리의 ca_path·ca_depth 를 프리픽스 교체로 함께 재계산하고, 새 부모의 형제 순서를
// 10 단위로 재부여한다. 빈 문자열이면 성공, 아니면 사유.
function cart_category_move($ca_id, $parent, $after_id = 0)
{
    global $g5;
    $ca_id = (int)$ca_id;
    $parent = (int)$parent;
    $after_id = (int)$after_id;

    $row = cart_category_get($ca_id);
    if (!$row) return '없는 분류입니다.';
    if ($parent === $ca_id) return '자기 자신 아래로는 옮길 수 없습니다.';

    $ppath = '/';
    $pdepth = 0;
    if ($parent) {
        $prow = cart_category_get($parent);
        if (!$prow) return '없는 부모 분류입니다.';
        // 자기 자손 밑으로 이동 금지 — 트리가 끊어진다
        if (strpos($prow['ca_path'], $row['ca_path']) === 0) return '하위 분류 아래로는 옮길 수 없습니다.';
        $ppath = $prow['ca_path'];
        $pdepth = (int)$prow['ca_depth'];
    }

    // 서브트리 높이 포함 최대 깊이 제한 — 이동 후 가장 깊은 자손이 한계를 넘으면 거부
    $r = sql_fetch(" select coalesce(max(ca_depth), {$row['ca_depth']}) mx from `{$g5['cart_category_table']}`
        where ca_path like '".sql_real_escape_string($row['ca_path'])."%' ");
    $height = (int)$r['mx'] - (int)$row['ca_depth'];
    if ($pdepth + 1 + $height > CART_CA_MAX_DEPTH) return '최대 '.CART_CA_MAX_DEPTH.'단을 넘게 되어 옮길 수 없습니다.';

    // 부모·경로·깊이 — 서브트리 전체를 프리픽스 교체로
    $old_prefix = $row['ca_path'];
    $new_prefix = $ppath.$ca_id.'/';
    $depth_diff = ($pdepth + 1) - (int)$row['ca_depth'];
    sql_query(" update `{$g5['cart_category_table']}` set ca_parent = '$parent' where ca_id = '$ca_id' ", true);
    sql_query(" update `{$g5['cart_category_table']}`
        set ca_path = concat('".sql_real_escape_string($new_prefix)."', substring(ca_path, ".(strlen($old_prefix) + 1).")),
            ca_depth = ca_depth + ($depth_diff)
        where ca_path like '".sql_real_escape_string($old_prefix)."%' ", true);

    // 새 부모의 형제 순서 재부여 — after_id 뒤에 끼워 넣고 10 단위로
    $siblings = array();
    $result = sql_query(" select ca_id from `{$g5['cart_category_table']}`
        where ca_parent = '$parent' and ca_id <> '$ca_id' order by ca_order, ca_id ");
    while ($s = sql_fetch_array($result)) $siblings[] = (int)$s['ca_id'];
    $ordered = array();
    if (!$after_id) $ordered[] = $ca_id;
    foreach ($siblings as $sid) {
        $ordered[] = $sid;
        if ($sid === $after_id) $ordered[] = $ca_id;
    }
    if (!in_array($ca_id, $ordered, true)) $ordered[] = $ca_id; // after_id 가 형제가 아니면 맨 뒤
    foreach ($ordered as $i => $sid) {
        sql_query(" update `{$g5['cart_category_table']}` set ca_order = '".(($i + 1) * 10)."'
            where ca_id = '$sid' ", true);
    }
    return '';
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
// $only_show=true 면 숨김 자식 아래를 캐스케이드로 전부 제외한다(cart_category_list() 의
// "숨긴 분류 아래는 도달 불가" 의미론과 동일 — 자기 자신 $ca_id 는 호출부가 이미 노출을
// 확인했다는 전제로 항상 포함하고, 그 아래부터 노출 자식만 타고 내려간다)
function cart_category_descendant_ids($ca_id, $only_show = false)
{
    global $g5;
    $row = cart_category_get($ca_id);
    if (!$row) return array();
    $like = sql_real_escape_string($row['ca_path']);
    $result = sql_query(" select ca_id, ca_parent, ca_show from `{$g5['cart_category_table']}`
        where ca_path like '{$like}%' ");
    $rows = array();
    while ($r = sql_fetch_array($result)) $rows[(int)$r['ca_id']] = $r;

    if (!$only_show) return array_keys($rows);

    $by_parent = array();
    foreach ($rows as $id => $r) $by_parent[(int)$r['ca_parent']][] = $id;

    $ca_id = (int)$ca_id;
    $ids = array($ca_id);
    $queue = array($ca_id);
    while ($queue) {
        $pid = array_shift($queue);
        if (empty($by_parent[$pid])) continue;
        foreach ($by_parent[$pid] as $cid) {
            if (!$rows[$cid]['ca_show']) continue;   // 숨긴 자식 — 여기서 가지째 잘라낸다
            $ids[] = $cid;
            $queue[] = $cid;
        }
    }
    return $ids;
}

// 캐스케이드로 숨김 상태인 분류 id 전부(숨긴 노드 자신 + 그 모든 후손) — "숨긴 분류의 서브트리
// 상품은 프론트 어디서도 안 보인다"는 의미론을 목록·검색·상세·자식 분류 직접 URL 네 갈래 전부에
// 같은 기준으로 적용하기 위한 단일 진입점. 전체 분류를 한 번만 읽어 요청 안에서 static 캐시한다.
function cart_hidden_category_ids()
{
    global $g5;
    static $ids = null;
    if ($ids !== null) return $ids;

    $by_parent = array();
    $show = array();
    $result = sql_query(" select ca_id, ca_parent, ca_show from `{$g5['cart_category_table']}` ");
    while ($r = sql_fetch_array($result)) {
        $cid = (int)$r['ca_id'];
        $by_parent[(int)$r['ca_parent']][] = $cid;
        $show[$cid] = (bool)(int)$r['ca_show'];
    }

    $ids = array();
    foreach ($show as $cid => $ok) {
        if (!$ok) cart_hidden_category_collect($by_parent, $cid, $ids);
    }
    $ids = array_values(array_unique($ids));
    return $ids;
}

// cart_hidden_category_ids() 내부 재귀 헬퍼 — $parent_id 자신과 그 모든 후손을 &$ids 에 채운다
function cart_hidden_category_collect($by_parent, $parent_id, &$ids)
{
    $ids[] = $parent_id;
    if (empty($by_parent[$parent_id])) return;
    foreach ($by_parent[$parent_id] as $cid) cart_hidden_category_collect($by_parent, $cid, $ids);
}

// ---------- 상품-분류 연결 (N:M) ----------
// 소속 정보의 유일한 원천. 상품은 분류 0개(무분류 단독 노출)일 수 있다.

function cart_item_ca_ids($it_id)
{
    global $g5;
    $ids = array();
    $result = sql_query(" select ca_id from `{$g5['cart_item_category_table']}`
        where it_id = '".(int)$it_id."' ");
    while ($r = sql_fetch_array($result)) $ids[] = (int)$r['ca_id'];
    return $ids;
}

// 상품이 속한 분류 행 목록(ca_order, ca_id 순) — 첫 행이 상세 빵부스러기의 대표
function cart_item_categories($it_id)
{
    global $g5;
    $rows = array();
    $result = sql_query(" select c.* from `{$g5['cart_item_category_table']}` x
        inner join `{$g5['cart_category_table']}` c on c.ca_id = x.ca_id
        where x.it_id = '".(int)$it_id."'
        order by c.ca_order, c.ca_id ");
    while ($r = sql_fetch_array($result)) $rows[] = $r;
    return $rows;
}

function cart_item_category_add($it_id, $ca_id)
{
    global $g5;
    if (!cart_category_get($ca_id)) return;
    sql_query(" insert ignore into `{$g5['cart_item_category_table']}` (it_id, ca_id)
        values ('".(int)$it_id."', '".(int)$ca_id."') ", true);
}

function cart_item_category_remove($it_id, $ca_id)
{
    global $g5;
    sql_query(" delete from `{$g5['cart_item_category_table']}`
        where it_id = '".(int)$it_id."' and ca_id = '".(int)$ca_id."' ", true);
}

// 소속 전체 교체 — 없는 분류 id 는 걸러낸다. 빈 배열이면 전부 해제(무분류).
function cart_item_category_set($it_id, $ca_ids)
{
    global $g5;
    $it_id = (int)$it_id;
    $ids = array();
    foreach ((array)$ca_ids as $cid) {
        $cid = (int)$cid;
        if ($cid && !isset($ids[$cid]) && cart_category_get($cid)) $ids[$cid] = $cid;
    }
    if ($ids) {
        sql_query(" delete from `{$g5['cart_item_category_table']}`
            where it_id = '$it_id' and ca_id not in (".implode(',', $ids).") ", true);
        foreach ($ids as $cid) {
            sql_query(" insert ignore into `{$g5['cart_item_category_table']}` (it_id, ca_id)
                values ('$it_id', '$cid') ", true);
        }
    } else {
        sql_query(" delete from `{$g5['cart_item_category_table']}` where it_id = '$it_id' ", true);
    }
}

// N:M 숨김 판정(단건) — 연결 분류가 없으면 노출, 하나라도 비숨김이면 노출
function cart_item_is_hidden($it_id)
{
    $ca_ids = cart_item_ca_ids($it_id);
    if (!$ca_ids) return false;
    $hidden = cart_hidden_category_ids();
    foreach ($ca_ids as $cid) {
        if (!in_array($cid, $hidden, true)) return false;
    }
    return true;
}

// N:M 숨김 판정(목록 WHERE 조각) — cart_item_is_hidden() 과 반드시 같은 의미.
// $alias 는 cart_item 테이블 별칭('i' 등), 별칭 없는 쿼리는 빈 문자열.
function cart_item_hidden_where($alias = '')
{
    global $g5;
    $hidden = cart_hidden_category_ids();
    if (!$hidden) return ' 1=1 ';
    $col = ($alias !== '' ? $alias.'.' : '').'it_id';
    $in = implode(',', $hidden);
    return " ( not exists (select 1 from `{$g5['cart_item_category_table']}` hx where hx.it_id = $col)
        or exists (select 1 from `{$g5['cart_item_category_table']}` vx
                   where vx.it_id = $col and vx.ca_id not in ($in)) ) ";
}

// 빈 문자열이면 성공, 아니면 사용자에게 보여줄 거부 사유
function cart_category_delete($ca_id)
{
    global $g5;
    $ca_id = (int)$ca_id;
    $child = sql_fetch(" select count(*) as cnt from `{$g5['cart_category_table']}`
        where ca_parent = '$ca_id' ");
    if ($child['cnt'] > 0) return '하위 분류가 있어 삭제할 수 없습니다. 하위 분류를 먼저 정리하세요.';
    $item = sql_fetch(" select count(*) as cnt from `{$g5['cart_item_category_table']}`
        where ca_id = '$ca_id' ");
    if ($item['cnt'] > 0) return '이 분류에 연결된 상품 '.(int)$item['cnt'].'개가 있어 삭제할 수 없습니다. 연결을 먼저 해제하세요.';
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
    $name = sql_real_escape_string(strip_tags(trim($data['it_name'])));
    $keyword = sql_real_escape_string(strip_tags(trim($data['it_keyword'])));
    $content = sql_real_escape_string($data['it_content']);
    $show = !empty($data['it_show']) ? 1 : 0;
    $shipping = isset($data['it_shipping_id']) ? (int)$data['it_shipping_id'] : 0;
    $now = G5_TIME_YMDHIS;

    if ($it_id) {
        sql_query(" update `{$g5['cart_item_table']}`
            set it_code = '$code', it_name = '$name', it_keyword = '$keyword',
                it_content = '$content', it_show = '$show', it_shipping_id = '$shipping',
                it_update = '$now'
            where it_id = '".(int)$it_id."' ", true);
        return (int)$it_id;
    }

    // it_code 는 UNIQUE — 빈 값 신규가 여럿 겹치지 않게 임시 유일값을 넣고 확정한다
    $tmp = $code !== '' ? $code : uniqid('PT', true);
    sql_query(" insert into `{$g5['cart_item_table']}`
        (it_code, it_name, it_keyword, it_content, it_show, it_shipping_id, it_datetime, it_update)
        values ('".sql_real_escape_string($tmp)."', '$name', '$keyword', '$content',
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
    // LIKE 폴백만 와일드카드를 이스케이프한다 — 검색어에 %·_ 가 그대로 들어가면 전체/단일문자
    // 매치가 돼버리므로 리터럴 문자로 찾게 만든다. 순서 주의: sql_real_escape_string 로 먼저
    // 따옴표·백슬래시를 이스케이프한 뒤에 addcslashes 로 %·_ 앞에 백슬래시를 붙여야
    // (MySQL LIKE 의 기본 ESCAPE 문자가 백슬래시) 이스케이프 문자 자체가 깨지지 않는다.
    $like = addcslashes($esc, '%_');
    return " it_name LIKE '%$like%' ";
}

// ---------- 상품 이미지 ----------

function cart_item_images($it_id)
{
    global $g5;
    $rows = array();
    $result = sql_query(" select * from `{$g5['cart_item_image_table']}`
        where it_id = '".(int)$it_id."' order by im_main desc, im_order, im_id ");
    while ($r = sql_fetch_array($result)) $rows[] = $r;
    return $rows;
}

// 목록 화면의 N+1 방지 — 상품 id 여러 개의 대표 이미지를 한 방에 [it_id => im_file] 로 돌려준다.
// 대표(im_main=1) 우선, 없으면 im_order/im_id 순 첫 행. 이미지가 없는 상품은 키 자체가 없다.
function cart_item_main_images(array $it_ids)
{
    global $g5;
    $map = array();
    if (empty($it_ids)) return $map;
    $ids = implode(',', array_map('intval', $it_ids));
    $result = sql_query(" select it_id, im_file from `{$g5['cart_item_image_table']}`
        where it_id IN ($ids) order by im_main desc, im_order, im_id ");
    while ($r = sql_fetch_array($result)) {
        $iid = (int)$r['it_id'];
        if (!isset($map[$iid])) $map[$iid] = $r['im_file'];   // 정렬상 첫 행 = 대표(또는 최선순위)
    }
    return $map;
}

// $file 은 $_FILES['im_files'] 의 단일 항목(name, tmp_name, error). 성공 시 빈 문자열
function cart_item_image_add($it_id, $file, $order = 0, $main = 0)
{
    global $g5;
    $it_id = (int)$it_id;
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) return '업로드 실패(코드 '.$file['error'].')';
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp'))) return '이미지 파일만 올릴 수 있습니다: '.$file['name'];
    // 내용 검사 — 확장자 위장 차단
    if (!@getimagesize($file['tmp_name'])) return '이미지가 아닙니다: '.$file['name'];

    $dir = cart_item_image_dir($it_id);
    if (!is_dir($dir)) { @mkdir($dir, G5_DIR_PERMISSION, true); @chmod($dir, G5_DIR_PERMISSION); }
    $name = $it_id.'_'.substr(md5(uniqid(mt_rand(), true)), 0, 8).'.'.$ext;
    if (!move_uploaded_file($file['tmp_name'], $dir.'/'.$name)) return '파일 저장 실패';
    @chmod($dir.'/'.$name, G5_FILE_PERMISSION);

    $rel = sprintf('%03d', (int)($it_id / 1000)).'/'.$name;
    sql_query(" insert into `{$g5['cart_item_image_table']}` (it_id, im_file, im_order, im_main)
        values ('$it_id', '".sql_real_escape_string($rel)."', '".(int)$order."', '".(int)$main."') ", true);
    return '';
}

function cart_item_image_delete($im_id)
{
    global $g5;
    $row = sql_fetch(" select * from `{$g5['cart_item_image_table']}` where im_id = '".(int)$im_id."' ");
    if (!$row) return;
    @unlink(G5_DATA_PATH.'/cart/item/'.$row['im_file']);
    sql_query(" delete from `{$g5['cart_item_image_table']}` where im_id = '".(int)$im_id."' ", true);
}
