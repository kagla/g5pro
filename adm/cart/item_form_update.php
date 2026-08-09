<?php
$sub_menu = '600100';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$w = (isset($_POST['w']) && !is_array($_POST['w'])) ? $_POST['w'] : '';
$it_id = (isset($_POST['it_id']) && !is_array($_POST['it_id'])) ? (int)$_POST['it_id'] : 0;
$list = G5_CART_ADMIN_URL.'/item_list.php';

// common.php 가 POST 를 통째로 SQL 이스케이프해 넘긴다 — 여기서 벗겨야 저장 함수의
// sql_real_escape_string 과 겹쳐 역슬래시가 쌓이지 않는다(분류 관리와 같은 처리).
// 에디터 HTML·옵션 JSON 처럼 따옴표가 많은 값에서 특히 크게 어긋난다.
$post_str = function ($key) {
    return (isset($_POST[$key]) && !is_array($_POST[$key])) ? stripslashes(trim($_POST[$key])) : '';
};
$post_arr = function ($key) {
    if (!isset($_POST[$key]) || !is_array($_POST[$key])) return array();
    return array_map(function ($v) { return is_array($v) ? '' : stripslashes($v); }, $_POST[$key]);
};

$data = array(
    'it_code' => $post_str('it_code'),
    'it_name' => $post_str('it_name'),
    'it_keyword' => $post_str('it_keyword'),
    // 에디터 HTML — trim 하지 않는다(앞뒤 공백도 본문의 일부)
    'it_content' => (isset($_POST['it_content']) && !is_array($_POST['it_content'])) ? stripslashes($_POST['it_content']) : '',
    'it_show' => !empty($_POST['it_show']) ? 1 : 0,
    // 배송비는 몰 전역 정책(설정 화면)이라 폼에 필드가 없다 — 수정 시 기존 값을 보존해
    // 저장할 때마다 0 으로 덮이던 문제를 막는다(상품별 정책이 생기면 폼 필드로 승격)
    'it_shipping_id' => 0,
);
if ($w === 'u') {
    $prev = cart_item_get($it_id);
    if ($prev) $data['it_shipping_id'] = (int)$prev['it_shipping_id'];
}
if ($data['it_name'] === '') alert('상품 이름을 입력하세요.');
// 분류는 0개~여러 개 자유 — 없는 분류 id 는 cart_item_category_set 이 걸러낸다
$ca_ids = (isset($_POST['ca_ids']) && is_array($_POST['ca_ids'])) ? array_map('intval', $_POST['ca_ids']) : array();
if ($data['it_code'] !== '') {
    $dup = cart_item_get_by_code($data['it_code']);
    if ($dup && (int)$dup['it_id'] !== $it_id) alert('이미 쓰는 상품코드입니다: '.$data['it_code']);
}
if ($w === 'u' && !cart_item_get($it_id)) alert('없는 상품입니다.', $list);

// ---- SKU 사전 검증(쓰기 전) ----
// 옵션 JSON 파싱·코드 중복을 여기서 전부 확인한다. 검증을 저장 루프 안에 섞어두면
// 뒷 행에서 alert 로 중단될 때 앞 행은 이미 커밋된 채로 남는다(부분 저장) — 그래서
// 상품·SKU 어느 쪽도 아직 쓰지 않은 이 시점에 전부 검사하고, 문제가 있으면 여기서 끝낸다.
$sk_ids = isset($_POST['sk_id']) && is_array($_POST['sk_id']) ? $_POST['sk_id'] : array();
$sk_codes = $post_arr('sk_code');
$sk_options = $post_arr('sk_option');
$sk_prices = $post_arr('sk_price');
$sk_qtys = $post_arr('sk_qty');
$sk_barcodes = $post_arr('sk_barcode');
$sk_uses = isset($_POST['sk_use']) && is_array($_POST['sk_use']) ? $_POST['sk_use'] : array();
$who = isset($member['mb_id']) ? $member['mb_id'] : 'admin';

$sku_rows = array();    // $i => array('sid'=>.., 'data'=>.., 'qty'=>..) — 쓰기 루프가 그대로 쓴다
$seen_codes = array();  // 이번 제출 안에서의 sk_code 중복(둘 다 신규라 DB 에는 아직 없는 경우) 검사용
foreach ($sk_ids as $i => $sid) {
    $sid = (int)$sid;
    $opt = isset($sk_options[$i]) ? trim($sk_options[$i]) : '{}';
    if ($opt !== '{}' && !is_array(json_decode($opt, true))) alert('옵션 형식 오류(행 '.($i + 1).')');

    $code = isset($sk_codes[$i]) ? trim($sk_codes[$i]) : '';
    if ($code !== '') {
        if (isset($seen_codes[$code])) {
            alert('중복된 SKU 코드입니다: '.$code.' (행 '.($seen_codes[$code] + 1).', '.($i + 1).')');
        }
        $seen_codes[$code] = $i;
        $dup = cart_sku_get_by_code($code);
        if ($dup && (int)$dup['sk_id'] !== $sid) alert('이미 쓰는 SKU 코드입니다: '.$code);
    }

    $sku_rows[$i] = array(
        'sid' => $sid,
        'data' => array(
            'sk_code' => $code,
            'sk_option' => $opt,
            'sk_price' => isset($sk_prices[$i]) ? (int)str_replace(',', '', $sk_prices[$i]) : 0,
            'sk_barcode' => isset($sk_barcodes[$i]) ? trim($sk_barcodes[$i]) : '',
            'sk_use' => !empty($sk_uses[$i]) ? 1 : 0,
        ),
        'qty' => isset($sk_qtys[$i]) ? $sk_qtys[$i] : '',
    );
}

// ---- 여기서부터 쓰기 시작 — 검증은 위에서 전부 끝났다 ----
if ($w === 'u') {
    cart_item_save($data, $it_id);
} else {
    $it_id = cart_item_save($data);
}
cart_item_category_set($it_id, $ca_ids);

// ---- SKU 저장 ----
foreach ($sku_rows as $srow) {
    $row = $srow['data'];
    $row['it_id'] = $it_id;
    $sid = cart_sku_save($row, $srow['sid']);
    if ($srow['qty'] !== '') {
        cart_stock_set($sid, (int)str_replace(',', '', $srow['qty']), 'manual', 'form', $who);
    }
}

// ---- SKU 삭제 — 자동 단일 SKU 생성 체크보다 반드시 먼저 온다.
// 전 SKU 를 삭제 요청한 제출이면 이 삭제가 끝난 뒤에야 실제로 0개가 되므로,
// 순서가 바뀌면(자동생성 체크가 먼저면) "SKU 1개 이상" 불변식이 깨진 채로 남는다.
$sk_dels = isset($_POST['sk_del']) && is_array($_POST['sk_del']) ? $_POST['sk_del'] : array();
foreach ($sk_dels as $sid) {
    $row = cart_sku_get((int)$sid);
    if ($row && (int)$row['it_id'] === $it_id) cart_sku_delete((int)$sid);
}

// SKU 가 하나도 없으면 단일 SKU 를 자동 생성 — "모든 상품은 SKU 1개 이상" 규칙
if (!count(cart_item_skus($it_id))) {
    cart_sku_save(array('it_id' => $it_id, 'sk_code' => '', 'sk_option' => '{}',
        'sk_price' => 0, 'sk_barcode' => '', 'sk_use' => 1));
}

// ---- 이미지 반영 ----
// im_del 은 im_id 만으로 오므로 다른 상품 소속 이미지를 실수(또는 조작)로 못 지우게
// sk_del 과 같은 방식으로 소유권(it_id 일치)을 확인한 것만 지운다.
$im_dels = isset($_POST['im_del']) && is_array($_POST['im_del']) ? $_POST['im_del'] : array();
foreach ($im_dels as $imid) {
    $imid = (int)$imid;
    $irow = sql_fetch(" select * from `{$g5['ycart_item_image_table']}` where im_id = '$imid' ");
    if ($irow && (int)$irow['it_id'] === $it_id) cart_item_image_delete($imid);
}

// 화면이 보낸 타일 순서. 'e:12' = 이미 있는 im_id 12, 'n:0' = im_files[] 의 0번 파일.
// 남은 사진과 새로 올릴 파일이 한 줄에 섞여 오므로 순서를 이 배열 하나로 표현한다.
// 배열이 아예 없으면(JS 를 끈 브라우저) 아래 업로드는 예전처럼 파일 순서대로 하고
// 순서·대표 손질은 건너뛴다 — 화면이 순서를 정하지 않았는데 서버가 뒤엎지 않게.
$im_seq = (isset($_POST['im_seq']) && is_array($_POST['im_seq'])) ? $_POST['im_seq'] : array();
$has_seq = count($im_seq) > 0;

// 업로드 실패는 즉시 alert 로 끊지 않는다 — 이미 저장된 SKU·성공한 이미지가 날아가지 않게
// 실패 파일만 건너뛰며 오류를 모으고, 전부 처리한 뒤 한 번에 안내한다.
$img_errors = array();
$uploaded = array();   // 파일 인덱스 => 몇 번째 자리에 놓을지. 순서 배열이 있을 때만 채운다

if ($has_seq) {
    // 자리 번호를 먼저 정해 둔다 — 기존 사진은 UPDATE, 새 파일은 INSERT 로 갈리지만
    // 두 갈래가 같은 번호 체계를 써야 한 줄로 이어진다.
    foreach ($im_seq as $pos => $tok) {
        if (!is_string($tok) || strlen($tok) < 3) continue;
        $kind = $tok[0];
        $num = (int)substr($tok, 2);
        if ($kind === 'e') {
            // 소유권 확인은 삭제와 같은 이유 — im_id 만으로 오므로 남의 상품 사진을 못 옮기게
            sql_query(" update `{$g5['ycart_item_image_table']}` set im_order = '".(int)$pos."'
                where im_id = '$num' and it_id = '$it_id' ", true);
        } elseif ($kind === 'n') {
            $uploaded[$num] = (int)$pos;
        }
    }
}

if (isset($_FILES['im_files']) && is_array($_FILES['im_files']['name'])) {
    foreach ($_FILES['im_files']['name'] as $i => $name) {
        if ($_FILES['im_files']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
        // 순서 배열이 온 제출은 파일마다 자리가 반드시 있다(화면이 둘을 한 순회로 만든다).
        // 자리가 없다는 건 순서 배열 자체가 없는 제출(무JS)이라는 뜻 — 예전처럼 파일 순서를 쓴다.
        $order = isset($uploaded[$i]) ? $uploaded[$i] : $i;
        $err = cart_item_image_add($it_id, array(
            'name' => $name,
            'tmp_name' => $_FILES['im_files']['tmp_name'][$i],
            'error' => $_FILES['im_files']['error'][$i],
        ), $order);
        if ($err) $img_errors[] = $name.': '.$err;
    }
}

// 대표는 맨 앞 사진이다 — 화면에서도 첫 칸이 대표 배지를 달고 있다.
// 방금 올린 파일이 첫 칸일 수 있는데 cart_item_image_add 는 새 im_id 를 돌려주지 않는다.
// 그래서 미리 정하지 않고 전부 반영한 뒤 첫 행을 다시 읽는다 — 첫 장 업로드가 실패해도
// 남은 것 중 맨 앞이 대표가 되어 대표 없는 상품이 생기지 않는다.
if ($has_seq) {
    $first = sql_fetch(" select im_id from `{$g5['ycart_item_image_table']}`
        where it_id = '$it_id' order by im_order, im_id limit 1 ");
    sql_query(" update `{$g5['ycart_item_image_table']}` set im_main = 0 where it_id = '$it_id' ", true);
    if ($first) {
        sql_query(" update `{$g5['ycart_item_image_table']}` set im_main = 1
            where im_id = '".(int)$first['im_id']."' and it_id = '$it_id' ", true);
    }
}

$back = G5_CART_ADMIN_URL.'/item_form.php?w=u&it_id='.$it_id;
if ($img_errors) alert('이미지 '.count($img_errors).'건 실패: '.implode(' / ', $img_errors), $back);
goto_url($back);
