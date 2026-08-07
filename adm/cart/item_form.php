<?php
$sub_menu = '600100';
include_once('./_common.php');
include_once(G5_EDITOR_LIB);   // 환경설정에서 고른 에디터(없으면 순정 textarea)
auth_check_menu($auth, $sub_menu, 'w');

$w = (isset($_GET['w']) && !is_array($_GET['w'])) ? $_GET['w'] : '';
$it_id = (isset($_GET['it_id']) && !is_array($_GET['it_id'])) ? (int)$_GET['it_id'] : 0;

$item = array('it_id' => 0, 'it_code' => '', 'it_name' => '', 'it_keyword' => '',
    'it_content' => '', 'it_show' => 1);
$skus = array();
$images = array();
if ($w === 'u') {
    $item = cart_item_get($it_id);
    if (!$item) alert('없는 상품입니다.', G5_CART_ADMIN_URL.'/item_list.php');
    $skus = cart_item_skus($it_id);
    $images = cart_item_images($it_id);
}

$g5['title'] = $w === 'u' ? '상품 수정' : '상품 등록';
include_once(G5_ADMIN_PATH.'/admin.head.php');

// 상세 설명 — 환경설정에서 고른 에디터를 그대로 쓴다(순정 글쓰기와 같은 배선).
// 에디터가 '선택없음'이면 editor_html 이 알아서 평범한 textarea 를 준다.
$is_dhtml_editor = (isset($config['cf_editor']) && $config['cf_editor'] !== '');
$editor_html = editor_html('it_content', $item['it_content'], $is_dhtml_editor);
$editor_js = get_editor_js('it_content', $is_dhtml_editor);

// SKU 옵션 JSON 을 뷰에서 다루기 좋게 파싱해 넘긴다
foreach ($skus as $i => $s) {
    $opt = json_decode($s['sk_option'], true);
    $skus[$i]['opt_label'] = (is_array($opt) && count($opt)) ? implode(' / ', array_map(
        function ($k, $v) { return $k.'='.$v; }, array_keys($opt), $opt)) : '단일';
}

$ca_ids_now = $w === 'u' ? cart_item_ca_ids($it_id) : array();

// 저장해 둔 옵션 조합 — 화면 JS 가 그대로 쓰도록 이름·묶음만 추려 넘긴다
$presets = array();
foreach (cart_option_preset_list() as $p) {
    $presets[] = array('id' => (int)$p['op_id'], 'name' => $p['op_name'], 'sets' => $p['sets']);
}

cadm_view('item_form', array(
    'presets' => $presets,
    'preset_url' => G5_CART_ADMIN_URL.'/option_preset.php',
    'w' => $w,
    'item' => $item,
    'skus' => $skus,
    'images' => $images,
    'categories' => cart_category_list(),
    'ca_ids' => $ca_ids_now,
    'editor_html' => $editor_html,
    'editor_js' => $editor_js,
    'image_url_base' => G5_CART_DATA_URL.'/item/',
    'action_url' => G5_CART_ADMIN_URL.'/item_form_update.php',
    'list_url' => G5_CART_ADMIN_URL.'/item_list.php',
    // 분류 쪽에서 상품을 붙이는 화면들. 상품 연결은 이 상품을 들고 간다 —
    // 첫 연결 분류를 열고(그 화면은 분류가 선택돼야 오른쪽 검색이 뜬다) 검색어에 상품코드를
    // 넣어, 도착하자마자 이 상품이 오른쪽에 잡히게 한다.
    'category_url' => G5_CART_ADMIN_URL.'/category.php',
    'category_item_url' => G5_CART_ADMIN_URL.'/category_item.php'
        .(($w === 'u' && $item['it_code'] !== '')
            ? '?'.http_build_query(array_merge(
                $ca_ids_now ? array('ca_id' => $ca_ids_now[0]) : array(),
                array('q' => $item['it_code'])))
            : ''),
    // 사용자 상품보기 바로가기 — 수정 화면에서만(등록 전에는 볼 상품이 없다)
    'view_url' => ($w === 'u' && $item['it_code'] !== '')
        ? cart_url('item.php', array('code' => $item['it_code'])) : '',
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
