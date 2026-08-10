<?php
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

// 주소는 상품코드(?code=코드)가 정식 — 파라미터 이름만 보고 상품코드임이 읽히게.
// ?it_id=숫자 는 옛 링크·북마크를 위한 하위호환으로 계속 받는다.
$code = (isset($_GET['code']) && !is_array($_GET['code'])) ? trim($_GET['code']) : '';
$item = $code !== '' ? cart_item_get_by_code($code) : null;
if (!$item) {
    $it_id = (isset($_GET['it_id']) && !is_array($_GET['it_id'])) ? (int)$_GET['it_id'] : 0;
    $item = cart_item_get($it_id);
}
$it_id = $item ? (int)$item['it_id'] : 0;
// 연결 분류는 한 번만 읽어 숨김 판정과 빵부스러기가 같은 데이터를 쓴다
// (판정 규칙은 cart_item_is_hidden 과 동일: 연결이 있는데 전부 숨김이면 숨김)
$item_cats = $item ? cart_item_categories($it_id) : array();
$visible_cats = array();
foreach ($item_cats as $c) {
    if (!in_array((int)$c['ca_id'], cart_hidden_category_ids(), true)) $visible_cats[] = $c;
}
// 상품 자신이 안 숨었어도 전 연결 분류가 숨김이면 상세도 막는다(목록과 같은 의미론).
// 다만 최고관리자는 막는 대신 화면을 보여 준다 — 등록·수정 직후 "손님에게 어떻게 보이나"를
// 확인하려는데 alert 로 튕기면 확인할 방법이 없다. 대신 왜 안 보이는지 배너로 알린다.
$admin_notice = '';
if (!$item) {
    alert('없는 상품입니다.', cart_url('list.php'));
}
if (!$item['it_show'] || ($item_cats && !$visible_cats)) {
    if ($is_admin !== 'super') alert('없는 상품입니다.', cart_url('list.php'));
    $admin_notice = !$item['it_show']
        ? '노출이 중지된 상품입니다. 관리자에게만 보입니다.'
        : '소속 분류가 모두 숨김이라 손님에게는 보이지 않습니다. 관리자에게만 보입니다.';
}

// 화면에 그릴 크기로 줄여서 내보낸다 — 큰 사진 자리는 480px(고해상도 화면까지 900),
// 아래 썸네일 줄은 64px(같은 이유로 128). 원본은 상세 화면에서 쓰지 않는다.
// 둘 다 잘라서 만든다 — 화면에서 두 자리 모두 정사각으로 잘라 보여 주므로(object-fit:cover)
// 잘라 둔 것이 곧 보이는 그대로다. 자르지 않으면 순정은 흰 여백을 채워 넣어 그 여백까지 보인다.
$images = array();
foreach (cart_item_images($it_id) as $img) {
    $images[] = array(
        'view' => cart_item_thumb_url($img['im_file'], 900, 900, true),
        'thumb' => cart_item_thumb_url($img['im_file'], 128, 128, true),
        // 확대해 보기(라이트박스)용 — 폭 1600, 높이 0(원본 비율 유지, 잘림·여백 없음).
        // 원본을 그대로 걸지 않는 이유: 4MB 짜리가 흔해 넘길 때마다 그만큼 내려받는다.
        // 페이지 로딩에는 영향이 없다 — 화면이 라이트박스를 열 때 비로소 받는다.
        'full' => cart_item_thumb_url($img['im_file'], 1600, 0, false),
    );
}

// SKU 목록 + 옵션 축(색상·사이즈 …) 을 함께 만든다.
// 화면은 조합을 한 번에 늘어놓지 않고 축마다 선택칸을 두어, 앞의 선택에 맞는 값만 남긴다.
// 축 이름과 순서는 옵션 JSON 의 키 순서를 그대로 따른다(관리자가 넣은 순서 = 색상, 사이즈).
$skus = array();
$opt_names = array();      // 축 이름 (예: 색상, 사이즈)
$opt_values = array();     // 축별 값 목록 — 첫 축의 선택칸을 채울 때 쓴다
foreach (cart_item_skus($it_id, true) as $s) {
    $opt = json_decode($s['sk_option'], true);
    if (!is_array($opt)) $opt = array();
    $label = count($opt) ? implode(' / ', array_values($opt)) : '기본';
    foreach ($opt as $k => $v) {
        if (!in_array($k, $opt_names, true)) { $opt_names[] = $k; $opt_values[$k] = array(); }
        if (!in_array($v, $opt_values[$k], true)) $opt_values[$k][] = $v;
    }
    $skus[] = array(
        'sk_id' => (int)$s['sk_id'],
        'opt' => $opt,
        'opt_label' => $label,
        'sk_price' => (int)$s['sk_price'],
        'sk_qty' => (int)$s['sk_qty'],
        'soldout' => ((int)$s['sk_qty'] === 0),
    );
}

// 빵부스러기 대표 분류 — 연결 분류(ca_order 순) 중 캐스케이드-노출인 첫 번째
$category = $visible_cats ? $visible_cats[0] : null;

// 추천 — 같은 분류의 다른 상품을 먼저 보여 주고, 모자라면 최신 상품으로 채운다.
// 숨김 의미론은 목록과 같은 기준(cart_item_hidden_where)을 그대로 쓴다.
$reco = array();
$reco_ids = array();
$ca_ids = array();
foreach ($visible_cats as $c) $ca_ids[] = (int)$c['ca_id'];

$reco_fetch = function ($where, $limit) use ($g5, $it_id, &$reco_ids) {
    if ($limit < 1) return array();
    $skip = array_merge(array($it_id), $reco_ids);
    $rows = array();
    $result = sql_query(" select it_id, it_name, it_price, it_stock
        from `{$g5['ycart_item_table']}`
        where it_show = 1 and it_id not in (".implode(',', $skip).")
          and ".cart_item_hidden_where('').$where."
        order by it_id desc limit ".(int)$limit);
    while ($r = sql_fetch_array($result)) { $rows[] = $r; $reco_ids[] = (int)$r['it_id']; }
    return $rows;
};
if ($ca_ids) {
    $reco = $reco_fetch(" and it_id in (select it_id from `{$g5['ycart_item_category_table']}`
        where ca_id in (".implode(',', $ca_ids).")) ", 8);
}
if (count($reco) < 8) {
    $reco = array_merge($reco, $reco_fetch('', 8 - count($reco)));
}
$reco_images = cart_item_main_images(array_column($reco, 'it_id'));
foreach ($reco as $i => $r) {
    $rid = (int)$r['it_id'];
    // 추천 카드도 줄여서 — 이 카드들 때문에 상세 화면이 원본 여러 장을 같이 받고 있었다
    $reco[$i]['img'] = isset($reco_images[$rid]) ? cart_item_thumb_url($reco_images[$rid], 400, 400) : '';
    $reco[$i]['href'] = cart_url('item.php', array('it_id' => $rid));
}

// 판매자 정보 — 회사 정보는 환경설정 값이 비어 있을 수 있으니 있는 것만 넘긴다
$cc = cart_config();
$seller = array(
    'company' => isset($config['cf_company_name']) ? $config['cf_company_name'] : '',
    'owner' => isset($config['cf_company_owner']) ? $config['cf_company_owner'] : '',
    'saupja_no' => isset($config['cf_company_saupja_no']) ? $config['cf_company_saupja_no'] : '',
    'tongsin_no' => isset($config['cf_company_tongsin_no']) ? $config['cf_company_tongsin_no'] : '',
    'tel' => isset($config['cf_company_tel']) ? $config['cf_company_tel'] : '',
    'addr' => isset($config['cf_company_addr']) ? $config['cf_company_addr'] : '',
    'email' => isset($config['cf_admin_email']) ? $config['cf_admin_email'] : '',
    'ship_base' => (int)$cc['cc_ship_base'],
    'ship_free' => (int)$cc['cc_ship_free'],
    // 권역 추가비 안내 — 요금표에서 뽑는다(화면이 규칙을 다시 적으면 표를 고쳐도 안 따라온다)
    'ship_zones' => cart_ship_zone_summary(),
    'bank' => $cc['cc_bank'],
);

// 관리자 바로가기 — super 판정은 여기서 끝내고 뷰에는 URL 만 (부킹 room.php 관례)
$admin_edit_url = ($is_admin === 'super')
    ? G5_CART_ADMIN_URL.'/item_form.php?w=u&it_id='.$it_id : '';

// 축 순서대로 값을 늘어놓은 배열 — 화면이 앞 축부터 좁혀 갈 때 쓴다(["화이트","L"] 꼴)
foreach ($skus as $i => $s) {
    $path = array();
    foreach ($opt_names as $n) $path[] = isset($s['opt'][$n]) ? $s['opt'][$n] : '';
    $skus[$i]['opt_path'] = $path;
}

// 단계 선택의 대상은 '축 값이 모두 찬' SKU 뿐이다. 옵션 없이 남아 있는 기본 SKU
// (단일 SKU 시절 자동 생성분)가 섞이면 선택칸에 빈 항목이 생긴다.
// 값 목록도 같은 대상에서 다시 뽑아 화면과 자료가 어긋나지 않게 한다.
$opt_skus = array();
if (count($opt_names)) {
    foreach ($skus as $s) {
        if (!in_array('', $s['opt_path'], true)) $opt_skus[] = $s;
    }
    $opt_values = array();
    foreach ($opt_names as $ai => $n) {
        $opt_values[$n] = array();
        foreach ($opt_skus as $s) {
            $v = $s['opt_path'][$ai];
            if (!in_array($v, $opt_values[$n], true)) $opt_values[$n][] = $v;
        }
    }
}

// 구매 폼 — 품절 아닌 SKU 가 하나라도 있어야 담기 가능
$buyable_skus = array();
foreach ($skus as $s) {
    if (!$s['soldout']) $buyable_skus[] = $s;
}

// 찜 — 회원만 담을 수 있다. 비회원에게도 하트는 보여 주되(있는 기능을 숨기면 못 찾는다)
// 누르면 로그인으로 안내한다. 돌아올 자리는 지금 보고 있는 상품 상세다.
$wish_mb_id = cart_wish_mb_id();
$item_url = cart_url('item.php', array('code' => $item['it_code']));

$g5['title'] = $item['it_name'];
g5_view('cart.item', array(
    'item' => $item,
    'images' => $images,
    'skus' => $skus,
    'buyable_skus' => $buyable_skus,
    'opt_names' => $opt_names,
    'opt_values' => $opt_values,
    'opt_skus' => $opt_skus,
    'single' => (count($skus) <= 1),
    'category' => $category,
    'list_href' => $category ? cart_url('list.php', array('ca' => $category['ca_code'])) : cart_url('list.php'),
    'admin_edit_url' => $admin_edit_url,
    'admin_notice' => $admin_notice,
    'reco' => $reco,
    'seller' => $seller,
    // 후기·문의는 4단계 기능 — 지금은 개수만(0) 넘겨 탭 자리를 잡아 둔다
    'review_cnt' => (int)$item['it_review_cnt'],
    'qa_cnt' => 0,
    'token' => get_token(),
    'cart_action' => cart_url('cart_update.php'),
    'cart_href' => cart_url('cart.php'),
    'wish_on' => cart_wish_has($it_id, $wish_mb_id),
    'wish_count' => cart_wish_count($it_id),
    'wish_action' => cart_url('wish_update.php'),
    'wish_href' => cart_url('wish.php'),
    'wish_login_url' => G5_BBS_URL.'/login.php?url='.urlencode($item_url),
));
