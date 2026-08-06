<?php
$sub_menu = '600100';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$w = (isset($_POST['w']) && !is_array($_POST['w'])) ? $_POST['w'] : '';
$it_id = (isset($_POST['it_id']) && !is_array($_POST['it_id'])) ? (int)$_POST['it_id'] : 0;
$list = G5_ADMIN_URL.'/cart/item_list.php';

$post_str = function ($key) {
    return (isset($_POST[$key]) && !is_array($_POST[$key])) ? trim($_POST[$key]) : '';
};

$data = array(
    'it_code' => $post_str('it_code'),
    'it_name' => $post_str('it_name'),
    'it_keyword' => $post_str('it_keyword'),
    'it_content' => (isset($_POST['it_content']) && !is_array($_POST['it_content'])) ? $_POST['it_content'] : '',
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
$sk_codes = isset($_POST['sk_code']) && is_array($_POST['sk_code']) ? $_POST['sk_code'] : array();
$sk_options = isset($_POST['sk_option']) && is_array($_POST['sk_option']) ? $_POST['sk_option'] : array();
$sk_prices = isset($_POST['sk_price']) && is_array($_POST['sk_price']) ? $_POST['sk_price'] : array();
$sk_qtys = isset($_POST['sk_qty']) && is_array($_POST['sk_qty']) ? $_POST['sk_qty'] : array();
$sk_barcodes = isset($_POST['sk_barcode']) && is_array($_POST['sk_barcode']) ? $_POST['sk_barcode'] : array();
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
    $irow = sql_fetch(" select * from `{$g5['cart_item_image_table']}` where im_id = '$imid' ");
    if ($irow && (int)$irow['it_id'] === $it_id) cart_item_image_delete($imid);
}

// 업로드 실패는 즉시 alert 로 끊지 않는다 — 이미 저장된 SKU·성공한 이미지가 날아가지 않게
// 실패 파일만 건너뛰며 오류를 모으고, 전부 처리한 뒤 한 번에 안내한다.
$img_errors = array();
if (isset($_FILES['im_files']) && is_array($_FILES['im_files']['name'])) {
    foreach ($_FILES['im_files']['name'] as $i => $name) {
        if ($_FILES['im_files']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
        $err = cart_item_image_add($it_id, array(
            'name' => $name,
            'tmp_name' => $_FILES['im_files']['tmp_name'][$i],
            'error' => $_FILES['im_files']['error'][$i],
        ), $i);
        if ($err) $img_errors[] = $name.': '.$err;
    }
}
$im_main = (isset($_POST['im_main']) && !is_array($_POST['im_main'])) ? (int)$_POST['im_main'] : 0;
if ($im_main) {
    sql_query(" update `{$g5['cart_item_image_table']}` set im_main = 0 where it_id = '$it_id' ", true);
    sql_query(" update `{$g5['cart_item_image_table']}` set im_main = 1
        where im_id = '$im_main' and it_id = '$it_id' ", true);
}

$back = G5_ADMIN_URL.'/cart/item_form.php?w=u&it_id='.$it_id;
if ($img_errors) alert('이미지 '.count($img_errors).'건 실패: '.implode(' / ', $img_errors), $back);
goto_url($back);
