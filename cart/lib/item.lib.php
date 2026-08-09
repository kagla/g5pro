<?php
if (!defined('_GNUBOARD_')) exit;

// ---------- 분류 ----------
// ca_path 는 자기 자신까지 포함한 '/1/5/23/' 꼴 — 서브트리는 ca_path LIKE '/1/5/%' 프리픽스로 잡는다

// 분류 최대 깊이 — 생성·이동·부모 선택지가 전부 이 값 하나를 본다 (2026-08-06 3→5단 확대)
if (!defined('CART_CA_MAX_DEPTH')) define('CART_CA_MAX_DEPTH', 5);

// 분류코드 최대 길이 — 검증·화면·DDL 이 전부 이 값 하나를 본다 (2026-08-07 20→30자 확대:
// 설명적인 코드가 20자에 금방 붙었다. 늘리는 건 무해하고, 줄이면 기존 코드가 잘린다)
if (!defined('CART_CA_CODE_MAX')) define('CART_CA_CODE_MAX', 30);

function cart_category_get($ca_id)
{
    global $g5;
    $row = sql_fetch(" select * from `{$g5['ycart_category_table']}` where ca_id = '".(int)$ca_id."' ");
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
        $dup = sql_fetch(" select ca_id from `{$g5['ycart_category_table']}` where ca_code = '$code' ");
        if (!$dup) return $code;
    }
}

// 수동 입력 코드 검증 — 빈 문자열이면 통과, 아니면 사용자에게 보여줄 사유.
// 형식 검사 뒤 중복 검사(자기 자신 제외). 최후 방어는 UNIQUE 인덱스가 맡는다.
function cart_category_code_error($code, $except_ca_id = 0)
{
    global $g5;
    if (!preg_match('/^[A-Za-z0-9_-]{1,'.CART_CA_CODE_MAX.'}$/', $code)) {
        return '분류코드는 영문·숫자·하이픈·언더라인 1~'.CART_CA_CODE_MAX.'자입니다.';
    }
    $row = sql_fetch(" select ca_id from `{$g5['ycart_category_table']}`
        where ca_code = '".sql_real_escape_string($code)."' and ca_id <> '".(int)$except_ca_id."' ");
    return $row ? '이미 쓰는 분류코드입니다: '.$code : '';
}

function cart_category_get_by_code($code)
{
    global $g5;
    $code = trim($code);
    if ($code === '') return null;
    $row = sql_fetch(" select * from `{$g5['ycart_category_table']}`
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
        sql_query(" update `{$g5['ycart_category_table']}`
            set ca_name = '$name', ca_order = '$order', ca_show = '$show',
                ca_desc = '$desc', ca_sort = '$sort' $code_set
            where ca_id = '".(int)$ca_id."' ", true);
        return (int)$ca_id;
    }

    sql_query(" insert into `{$g5['ycart_category_table']}`
        (ca_parent, ca_code, ca_name, ca_path, ca_depth, ca_order, ca_show)
        values ('$parent', '".cart_category_code_generate()."', '$name', '$ppath', '$depth', '$order', '$show') ", true);
    $new_id = sql_insert_id();
    sql_query(" update `{$g5['ycart_category_table']}`
        set ca_path = '".sql_real_escape_string($ppath.$new_id.'/')."'
        where ca_id = '$new_id' ", true);
    return (int)$new_id;
}

// 업로드 폴더 준비 — 없으면 만들고, 못 만들거나 못 쓰면 사유를 돌려준다(빈 문자열이면 준비 완료).
// 실패를 '파일 저장 실패'로 뭉뚱그리지 않는 이유: 웹서버(www-data)가 data/cart 에 못 쓰는
// 권한 문제가 실제로 있었고, 그때 화면 문구만으로는 원인을 알 수 없었다(2026-08-07).
function cart_image_dir_ready($dir)
{
    if (!is_dir($dir)) {
        @mkdir($dir, G5_DIR_PERMISSION, true);
        @chmod($dir, G5_DIR_PERMISSION);
    }
    if (!is_dir($dir)) return '이미지 폴더를 만들 수 없습니다 — data/cart 에 웹서버 쓰기 권한이 있는지 확인하세요.';
    if (!is_writable($dir)) return '이미지 폴더에 쓸 수 없습니다 — '.$dir.' 권한을 확인하세요.';
    return '';
}

// 분류 이미지 — 스토어홈 원형 칩 등에서 쓴다. data/cart/category/ 아래 한 분류 한 파일.
function cart_category_image_url($file)
{
    return $file !== '' ? G5_CART_DATA_URL.'/category/'.$file : '';
}

function cart_category_image_save($ca_id, $file)
{
    global $g5;
    $ca_id = (int)$ca_id;
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) return '업로드 실패(코드 '.$file['error'].')';
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp'))) return '이미지 파일만 올릴 수 있습니다.';
    if (!@getimagesize($file['tmp_name'])) return '이미지가 아닙니다.';

    $dir = G5_CART_DATA_PATH.'/category';
    $err = cart_image_dir_ready($dir);
    if ($err) return $err;
    $name = $ca_id.'_'.substr(md5(uniqid(mt_rand(), true)), 0, 8).'.'.$ext;
    if (!move_uploaded_file($file['tmp_name'], $dir.'/'.$name)) return '파일 저장 실패';
    @chmod($dir.'/'.$name, G5_FILE_PERMISSION);

    cart_category_image_delete($ca_id); // 기존 파일 정리 후 교체
    sql_query(" update `{$g5['ycart_category_table']}`
        set ca_img = '".sql_real_escape_string($name)."' where ca_id = '$ca_id' ", true);
    return '';
}

function cart_category_image_delete($ca_id)
{
    global $g5;
    $row = cart_category_get($ca_id);
    if (!$row || $row['ca_img'] === '') return;
    @unlink(G5_CART_DATA_PATH.'/category/'.$row['ca_img']);
    sql_query(" update `{$g5['ycart_category_table']}` set ca_img = '' where ca_id = '".(int)$ca_id."' ", true);
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
    $r = sql_fetch(" select coalesce(max(ca_depth), {$row['ca_depth']}) mx from `{$g5['ycart_category_table']}`
        where ca_path like '".sql_real_escape_string($row['ca_path'])."%' ");
    $height = (int)$r['mx'] - (int)$row['ca_depth'];
    if ($pdepth + 1 + $height > CART_CA_MAX_DEPTH) return '최대 '.CART_CA_MAX_DEPTH.'단을 넘게 되어 옮길 수 없습니다.';

    // 부모·경로·깊이 — 서브트리 전체를 프리픽스 교체로
    $old_prefix = $row['ca_path'];
    $new_prefix = $ppath.$ca_id.'/';
    $depth_diff = ($pdepth + 1) - (int)$row['ca_depth'];
    sql_query(" update `{$g5['ycart_category_table']}` set ca_parent = '$parent' where ca_id = '$ca_id' ", true);
    sql_query(" update `{$g5['ycart_category_table']}`
        set ca_path = concat('".sql_real_escape_string($new_prefix)."', substring(ca_path, ".(strlen($old_prefix) + 1).")),
            ca_depth = ca_depth + ($depth_diff)
        where ca_path like '".sql_real_escape_string($old_prefix)."%' ", true);

    // 새 부모의 형제 순서 재부여 — after_id 뒤에 끼워 넣고 10 단위로
    $siblings = array();
    $result = sql_query(" select ca_id from `{$g5['ycart_category_table']}`
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
        sql_query(" update `{$g5['ycart_category_table']}` set ca_order = '".(($i + 1) * 10)."'
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
    $result = sql_query(" select * from `{$g5['ycart_category_table']}` $where
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
    $result = sql_query(" select * from `{$g5['ycart_category_table']}`
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
    $result = sql_query(" select ca_id, ca_parent, ca_show from `{$g5['ycart_category_table']}`
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
    $result = sql_query(" select ca_id, ca_parent, ca_show from `{$g5['ycart_category_table']}` ");
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
    $result = sql_query(" select ca_id from `{$g5['ycart_item_category_table']}`
        where it_id = '".(int)$it_id."' ");
    while ($r = sql_fetch_array($result)) $ids[] = (int)$r['ca_id'];
    return $ids;
}

// 상품이 속한 분류 행 목록(ca_order, ca_id 순) — 첫 행이 상세 빵부스러기의 대표
function cart_item_categories($it_id)
{
    global $g5;
    $rows = array();
    $result = sql_query(" select c.* from `{$g5['ycart_item_category_table']}` x
        inner join `{$g5['ycart_category_table']}` c on c.ca_id = x.ca_id
        where x.it_id = '".(int)$it_id."'
        order by c.ca_order, c.ca_id ");
    while ($r = sql_fetch_array($result)) $rows[] = $r;
    return $rows;
}

function cart_item_category_add($it_id, $ca_id)
{
    global $g5;
    if (!cart_category_get($ca_id)) return;
    sql_query(" insert ignore into `{$g5['ycart_item_category_table']}` (it_id, ca_id)
        values ('".(int)$it_id."', '".(int)$ca_id."') ", true);
}

function cart_item_category_remove($it_id, $ca_id)
{
    global $g5;
    sql_query(" delete from `{$g5['ycart_item_category_table']}`
        where it_id = '".(int)$it_id."' and ca_id = '".(int)$ca_id."' ", true);
}

// 소속 전체 교체 — 없는 분류 id 는 걸러낸다. 빈 배열이면 전부 해제(무분류).
function cart_item_category_set($it_id, $ca_ids)
{
    global $g5;
    $it_id = (int)$it_id;
    $cand = array();
    foreach ((array)$ca_ids as $cid) {
        $cid = (int)$cid;
        if ($cid) $cand[$cid] = $cid;
    }
    // 존재 검증은 IN 한 방 — 개수만큼 SELECT 를 돌리지 않는다
    $ids = array();
    if ($cand) {
        $result = sql_query(" select ca_id from `{$g5['ycart_category_table']}`
            where ca_id in (".implode(',', $cand).") ");
        while ($r = sql_fetch_array($result)) $ids[(int)$r['ca_id']] = (int)$r['ca_id'];
    }
    if ($ids) {
        sql_query(" delete from `{$g5['ycart_item_category_table']}`
            where it_id = '$it_id' and ca_id not in (".implode(',', $ids).") ", true);
        foreach ($ids as $cid) {
            sql_query(" insert ignore into `{$g5['ycart_item_category_table']}` (it_id, ca_id)
                values ('$it_id', '$cid') ", true);
        }
    } else {
        sql_query(" delete from `{$g5['ycart_item_category_table']}` where it_id = '$it_id' ", true);
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
// $alias 는 cart_item 테이블 별칭('i' 등). 별칭 없는 쿼리(빈 문자열)는 테이블명으로
// 한정한다 — 서브쿼리 안의 벌거벗은 it_id 는 MySQL 이 안쪽(연결 테이블) 컬럼으로
// 해석해 조건이 상품 무관 상수가 되기 때문(리뷰에서 실증된 버그).
function cart_item_hidden_where($alias = '')
{
    global $g5;
    $hidden = cart_hidden_category_ids();
    if (!$hidden) return ' 1=1 ';
    $col = ($alias !== '' ? $alias : "`{$g5['ycart_item_table']}`").'.it_id';
    $in = implode(',', $hidden);
    return " ( not exists (select 1 from `{$g5['ycart_item_category_table']}` hx where hx.it_id = $col)
        or exists (select 1 from `{$g5['ycart_item_category_table']}` vx
                   where vx.it_id = $col and vx.ca_id not in ($in)) ) ";
}

// 삭제 — N:M 에서 분류는 상품이 속한 여러 자리 중 하나일 뿐이라, 지워도 상품은 사라지지 않는다
// (다른 분류에 그대로 있거나 무분류가 된다). 그래서 상품 연결은 끊기만 한다.
// 하위 분류는 '연결'이 아니라 독립된 분류라 함께 지우면 손실이므로, 지우지 않고 한 단 위로 올린다
// (2026-08-07 사용자 결정: 하위·연결이 있어도 삭제는 되어야 한다).
// 빈 문자열이면 성공, 아니면 사용자에게 보여줄 사유.
function cart_category_delete($ca_id)
{
    global $g5;
    $ca_id = (int)$ca_id;
    $row = cart_category_get($ca_id);
    if (!$row) return '없는 분류입니다.';

    // 직계 하위를 삭제될 분류의 부모로 승격 — 경로·깊이 재계산은 이동 함수가 맡는다.
    // 넣는 자리를 직전에 옮긴 분류 뒤로 이어 붙여(커서) 원래 형제 순서를 지킨다.
    $children = array();
    $result = sql_query(" select ca_id from `{$g5['ycart_category_table']}`
        where ca_parent = '$ca_id' order by ca_order, ca_id ");
    while ($r = sql_fetch_array($result)) $children[] = (int)$r['ca_id'];
    $after = $ca_id;
    foreach ($children as $cid) {
        $err = cart_category_move($cid, (int)$row['ca_parent'], $after);
        if ($err) return '하위 분류를 옮기지 못해 삭제를 멈췄습니다: '.$err;
        $after = $cid;
    }

    cart_category_image_delete($ca_id); // 행이 지워지면 파일 경로를 아는 곳이 없어진다 — 먼저 정리
    sql_query(" delete from `{$g5['ycart_item_category_table']}` where ca_id = '$ca_id' ", true);
    sql_query(" delete from `{$g5['ycart_category_table']}` where ca_id = '$ca_id' ", true);
    return '';
}

// ---------- 상품 ----------

function cart_item_get($it_id)
{
    global $g5;
    $row = sql_fetch(" select * from `{$g5['ycart_item_table']}` where it_id = '".(int)$it_id."' ");
    return $row ? $row : null;
}

function cart_item_get_by_code($code)
{
    global $g5;
    $code = sql_real_escape_string(trim($code));
    if ($code === '') return null;
    $row = sql_fetch(" select * from `{$g5['ycart_item_table']}` where it_code = '$code' ");
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
        sql_query(" update `{$g5['ycart_item_table']}`
            set it_code = '$code', it_name = '$name', it_keyword = '$keyword',
                it_content = '$content', it_show = '$show', it_shipping_id = '$shipping',
                it_update = '$now'
            where it_id = '".(int)$it_id."' ", true);
        return (int)$it_id;
    }

    // it_code 는 UNIQUE — 빈 값 신규가 여럿 겹치지 않게 임시 유일값을 넣고 확정한다
    $tmp = $code !== '' ? $code : uniqid('PT', true);
    sql_query(" insert into `{$g5['ycart_item_table']}`
        (it_code, it_name, it_keyword, it_content, it_show, it_shipping_id, it_datetime, it_update)
        values ('".sql_real_escape_string($tmp)."', '$name', '$keyword', '$content',
                '$show', '$shipping', '$now', '$now') ", true);
    $new_id = sql_insert_id();
    if ($code === '') {
        sql_query(" update `{$g5['ycart_item_table']}` set it_code = 'P{$new_id}'
            where it_id = '$new_id' ", true);
    }
    return (int)$new_id;
}

// ---------- SKU ----------

function cart_sku_get($sk_id)
{
    global $g5;
    $row = sql_fetch(" select * from `{$g5['ycart_sku_table']}` where sk_id = '".(int)$sk_id."' ");
    return $row ? $row : null;
}

function cart_sku_get_by_code($code)
{
    global $g5;
    $code = sql_real_escape_string(trim($code));
    if ($code === '') return null;
    $row = sql_fetch(" select * from `{$g5['ycart_sku_table']}` where sk_code = '$code' ");
    return $row ? $row : null;
}

function cart_item_skus($it_id, $only_use = false)
{
    global $g5;
    $and = $only_use ? " and sk_use = 1 " : "";
    $rows = array();
    $result = sql_query(" select * from `{$g5['ycart_sku_table']}`
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
        sql_query(" update `{$g5['ycart_sku_table']}`
            set sk_code = '$code', sk_option = '$option', sk_price = '$price',
                sk_barcode = '$barcode', sk_use = '$use'
            where sk_id = '".(int)$sk_id."' ", true);
        cart_item_cache_refresh($it_id);
        return (int)$sk_id;
    }

    $tmp = $code !== '' ? $code : uniqid('ST', true);
    sql_query(" insert into `{$g5['ycart_sku_table']}`
        (it_id, sk_code, sk_option, sk_price, sk_barcode, sk_use)
        values ('$it_id', '".sql_real_escape_string($tmp)."', '$option', '$price', '$barcode', '$use') ", true);
    $new_id = sql_insert_id();
    if ($code === '') {
        // it_code-NN — 상품 안에서 몇 번째 SKU 인지로 붙인다(자릿수 2, 100개 넘으면 그대로 숫자)
        // COUNT 로 NN 을 정하는 방식은 같은 상품에 SKU 가 동시에 생성되면 두 요청이 같은 NN 을
        // 계산할 수 있다(sk_code 는 UNIQUE) — 확정 UPDATE 를 재시도해 충돌을 피한다.
        $item = cart_item_get($it_id);
        $cnt = sql_fetch(" select count(*) as cnt from `{$g5['ycart_sku_table']}` where it_id = '$it_id' ");
        $n = (int)$cnt['cnt'];
        $confirmed = false;
        for ($try = 0; $try < 5; $try++) {
            $auto = $item['it_code'].'-'.($n < 100 ? sprintf('%02d', $n) : $n);
            // 성공 판정은 affected_rows 가 아니라 "쿼리가 오류 없이 실행됐는가"로 한다 —
            // 지금 확정하려는 행의 현재 값은 uniqid() placeholder 라 목표 코드와 절대 같을 수
            // 없으므로(같은 값 재설정으로 인한 affected 0 은 원천적으로 없음) 오류 여부만 보면
            // 충분하다. MariaDB 는 UNIQUE 충돌 시 쿼리 자체가 실패(false)로 돌아온다.
            $ok = sql_query(" update `{$g5['ycart_sku_table']}`
                set sk_code = '".sql_real_escape_string($auto)."' where sk_id = '$new_id' ", false);
            if ($ok) { $confirmed = true; break; }
            $n++;
        }
        if (!$confirmed) {
            // 5회 모두 충돌 — sk_id(PK) 기반 코드는 절대 겹치지 않으니 최종 폴백으로 확정한다
            $auto = $item['it_code'].'-'.$new_id;
            sql_query(" update `{$g5['ycart_sku_table']}`
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
    sql_query(" delete from `{$g5['ycart_sku_table']}` where sk_id = '".(int)$sk_id."' ", true);
    cart_item_cache_refresh((int)$row['it_id']);
}

// 목록이 조인 없이 뜨게 하는 캐시 — SKU 가 바뀔 때마다 호출된다
function cart_item_cache_refresh($it_id)
{
    global $g5;
    $it_id = (int)$it_id;
    $sum = sql_fetch(" select min(sk_price) as min_price, sum(sk_qty) as total
        from `{$g5['ycart_sku_table']}` where it_id = '$it_id' and sk_use = 1 ");
    $price = (int)$sum['min_price'];
    $stock = (int)$sum['total'];
    sql_query(" update `{$g5['ycart_item_table']}`
        set it_price = '$price', it_stock = '$stock' where it_id = '$it_id' ", true);
}

// ---------- 옵션 조합 프리셋 ----------
// 상품 폼에서 만든 옵션명·값 묶음("색상: 빨강,파랑 / 사이즈: S,M,L")을 이름 붙여 저장해 두고
// 다음 상품에서 그대로 불러 쓴다. 조합 생성 입력칸을 채워 줄 뿐이라 SKU 와 연결하지 않는다.

// 저장 형태로 정규화 — [{name, vals[]}, ...]. 빈 이름·빈 값은 버리고, 형식이 아니면 빈 배열
function cart_option_preset_normalize($sets)
{
    $out = array();
    if (!is_array($sets)) return $out;
    foreach ($sets as $set) {
        if (!is_array($set) || !isset($set['name']) || !isset($set['vals'])) continue;
        $name = trim(strip_tags((string)$set['name']));
        if ($name === '' || !is_array($set['vals'])) continue;
        $vals = array();
        foreach ($set['vals'] as $v) {
            $v = trim(strip_tags((string)$v));
            // 같은 값이 두 번 들어가면 조합이 중복 생성된다 — 여기서 한 번 걸러 둔다
            if ($v !== '' && !in_array($v, $vals, true)) $vals[] = $v;
        }
        if ($vals) $out[] = array('name' => $name, 'vals' => $vals);
    }
    return $out;
}

function cart_option_preset_list()
{
    global $g5;
    $rows = array();
    // 자주 쓰는 조합이 위로 오게 op_order 우선(작을수록 위). 같은 번호끼리는 이름순
    $result = sql_query(" select * from `{$g5['ycart_option_preset_table']}`
        order by op_order, op_name ", false);
    if (!$result) return $rows;   // 아직 설치 전(옛 설치본)이면 조용히 빈 목록
    while ($r = sql_fetch_array($result)) {
        $sets = cart_option_preset_normalize(json_decode($r['op_data'], true));
        if (!$sets) continue;
        $r['sets'] = $sets;
        $rows[] = $r;
    }
    return $rows;
}

// 같은 이름이면 덮어쓴다(이름이 곧 식별자 — 관리자가 "의류 기본" 을 갱신하는 흐름).
// $order 는 목록 순서(작을수록 위). null 이면 기존 값을 그대로 두고, 새 조합은 맨 끝에 붙는다 —
// 화면에서 저장한 조합이 자주 쓰는 기본 조합들을 밀어내지 않게.
// 빈 문자열이면 성공, 아니면 사용자에게 보여줄 사유
function cart_option_preset_save($name, $sets, $order = null)
{
    global $g5;
    $name = trim(strip_tags((string)$name));
    if ($name === '') return '조합 이름을 입력하세요.';
    if (mb_strlen($name, 'utf-8') > 50) return '조합 이름은 50자까지입니다.';
    $sets = cart_option_preset_normalize($sets);
    if (!$sets) return '저장할 옵션이 없습니다. 옵션명과 값을 먼저 입력하세요.';

    $esc_name = sql_real_escape_string($name);
    $data = sql_real_escape_string(json_encode($sets, JSON_UNESCAPED_UNICODE));
    $now = G5_TIME_YMDHIS;
    $row = sql_fetch(" select op_id from `{$g5['ycart_option_preset_table']}` where op_name = '$esc_name' ");
    if ($row) {
        $set_order = ($order === null) ? '' : ", op_order = '".(int)$order."'";
        sql_query(" update `{$g5['ycart_option_preset_table']}`
            set op_data = '$data', op_datetime = '$now' $set_order
            where op_id = '".(int)$row['op_id']."' ", true);
        return '';
    }
    if ($order === null) {
        $max = sql_fetch(" select max(op_order) as mx from `{$g5['ycart_option_preset_table']}` ");
        $order = ($max && $max['mx'] !== null) ? (int)$max['mx'] + 10 : 10;
    }
    sql_query(" insert into `{$g5['ycart_option_preset_table']}` (op_name, op_order, op_data, op_datetime)
        values ('$esc_name', '".(int)$order."', '$data', '$now') ", true);
    return '';
}

function cart_option_preset_delete($op_id)
{
    global $g5;
    sql_query(" delete from `{$g5['ycart_option_preset_table']}` where op_id = '".(int)$op_id."' ", true);
}

// ---------- 검색 ----------

// 관리자 목록 검색 — 프론트(cart_item_search_where)와 달리 상품코드도 함께 본다.
// 관리자는 코드로 상품을 찝는 일이 잦은데, 코드는 전체를 외워 치기 어렵고 앞자리(예: DEMO)만
// 기억하는 경우가 많다. 코드가 정확히 맞으면 그 하나로 좁히고(가장 흔한 의도),
// 아니면 이름·키워드 검색에 코드 부분일치를 더한다.
// 관리자 화면들이 같은 결과를 내도록 검색 규칙은 이 함수 하나에 모은다.
function cart_item_admin_search_where($q)
{
    $q = trim($q);
    if ($q === '') return ' 1=1 ';
    if (cart_item_get_by_code($q)) {
        return " (it_code = '".sql_real_escape_string($q)."') ";
    }
    // LIKE 와일드카드(%·_)는 리터럴로 — cart_item_search_where 의 폴백과 같은 규칙
    $like = addcslashes(sql_real_escape_string($q), '%_');
    return " (".cart_item_search_where($q)." or it_code LIKE '%$like%') ";
}

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
    $result = sql_query(" select * from `{$g5['ycart_item_image_table']}`
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
    $result = sql_query(" select it_id, im_file from `{$g5['ycart_item_image_table']}`
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
    $err = cart_image_dir_ready($dir);
    if ($err) return $err;
    $name = $it_id.'_'.substr(md5(uniqid(mt_rand(), true)), 0, 8).'.'.$ext;
    if (!move_uploaded_file($file['tmp_name'], $dir.'/'.$name)) return '파일 저장 실패';
    @chmod($dir.'/'.$name, G5_FILE_PERMISSION);

    $rel = sprintf('%03d', (int)($it_id / 1000)).'/'.$name;
    sql_query(" insert into `{$g5['ycart_item_image_table']}` (it_id, im_file, im_order, im_main)
        values ('$it_id', '".sql_real_escape_string($rel)."', '".(int)$order."', '".(int)$main."') ", true);
    return '';
}

// 상품 삭제 — 한 번이라도 팔린 상품은 지우지 않는다.
// 주문서 자체는 스냅샷(oi_name·oi_price)이라 읽히지만, 취소는 재고를 되돌리는 동작이라
// cart_stock_move 가 사라진 SKU 에서 false 를 내고 cart_order_transition 이 취소를 통째로
// 거부한다 — 즉 삭제하면 그 상품이 든 미완료 주문을 영영 취소·환불할 수 없게 된다.
// 그래서 판매 이력이 있으면 거부하고 '노출 끄기(숨김)'로 안내한다.
// 팔린 적 없는 상품만 딸린 자료까지 정리한다:
// 장바구니 행(SKU 경유) → 이미지(파일 포함) → SKU → 재고 이력 → 분류 연결 → 상품 행.
// 빈 문자열이면 성공, 아니면 사용자에게 보여줄 사유.
function cart_item_delete($it_id)
{
    global $g5;
    $it_id = (int)$it_id;
    if (!cart_item_get($it_id)) return '없는 상품입니다.';

    // draft 는 결제 전 초안이라 재고를 건드린 적이 없다 — 판매 이력으로 치지 않는다
    $sold = sql_fetch(" select count(*) as cnt from `{$g5['ycart_order_item_table']}` oi
        inner join `{$g5['ycart_order_table']}` o on o.od_id = oi.od_id
        where oi.it_id = '$it_id' and o.od_status <> 'draft' ");
    if ((int)$sold['cnt'] > 0) {
        return '주문 '.(int)$sold['cnt'].'건에 팔린 상품이라 삭제할 수 없습니다.'
            .' 노출을 꺼서 숨기세요 — 판매는 멈추고 주문·재고 이력은 보존됩니다.';
    }

    // 장바구니는 sk_id 로만 상품을 가리킨다 — SKU 를 지우기 전에 먼저 비운다(고아 행 방지)
    $sk_ids = array();
    $result = sql_query(" select sk_id from `{$g5['ycart_sku_table']}` where it_id = '$it_id' ");
    while ($r = sql_fetch_array($result)) $sk_ids[] = (int)$r['sk_id'];
    if ($sk_ids) {
        sql_query(" delete from `{$g5['ycart_cart_table']}`
            where sk_id in (".implode(',', $sk_ids).") ", true);
    }

    // 이미지는 파일도 함께 지워야 해서 행 단위로 부른다
    foreach (cart_item_images($it_id) as $img) cart_item_image_delete((int)$img['im_id']);

    sql_query(" delete from `{$g5['ycart_sku_table']}` where it_id = '$it_id' ", true);
    sql_query(" delete from `{$g5['ycart_stock_log_table']}` where it_id = '$it_id' ", true);
    sql_query(" delete from `{$g5['ycart_item_category_table']}` where it_id = '$it_id' ", true);
    sql_query(" delete from `{$g5['ycart_item_table']}` where it_id = '$it_id' ", true);
    return '';
}

function cart_item_image_delete($im_id)
{
    global $g5;
    $row = sql_fetch(" select * from `{$g5['ycart_item_image_table']}` where im_id = '".(int)$im_id."' ");
    if (!$row) return;
    @unlink(G5_CART_DATA_PATH.'/item/'.$row['im_file']);
    cart_item_thumb_purge($row['im_file']);
    sql_query(" delete from `{$g5['ycart_item_image_table']}` where im_id = '".(int)$im_id."' ", true);
}
