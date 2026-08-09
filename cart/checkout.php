<?php
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

// 바로구매 스코프 — buy=ct_id(CSV)면 그 바구니 행만 주문서에 올린다(다른 상품은 함께 결제 안 됨)
$buy = (isset($_GET['buy']) && !is_array($_GET['buy'])) ? trim($_GET['buy']) : '';
$only = array_values(array_filter(array_map('intval', explode(',', $buy))));

// 토스 failUrl 복귀 — 사유만 알리고 주문서를 다시 그린다(장바구니는 그대로, 스코프 유지)
if (isset($_GET['fail']) && !is_array($_GET['fail'])) {
    $fail_msg = (isset($_GET['message']) && !is_array($_GET['message']))
        ? clean_xss_tags($_GET['message']) : '';
    alert($fail_msg !== '' ? $fail_msg : '결제가 완료되지 않았습니다. 다시 시도해 주세요.',
        cart_url('checkout.php', count($only) ? array('buy' => implode(',', $only)) : array()));
}

$picked = cart_checkout_lines(null, count($only) ? $only : null);
$lines = $picked['lines'];
if (!count($lines)) {
    alert('주문할 수 있는 상품이 없습니다.', cart_url('cart.php'));
}

// 재고가 모자라거나 판매가 중지된 행이 하나라도 있으면 주문서로 넘기지 않는다.
// 조용히 빼고 진행하면 손님은 빠진 줄 모르고 결제한다 — 장바구니에서 정리하고 오게 한다.
// (화면 JS 도 같은 것을 막지만, 주소로 바로 들어오는 경우가 있어 여기가 진짜 방어선이다)
if (count($picked['blocked'])) {
    $names = array();
    foreach ($picked['blocked'] as $b) {
        $names[] = $b['it_name'].($b['over_stock'] ? '(재고 '.(int)$b['sk_qty'].'개)' : '(판매 중지)');
    }
    $head = implode(', ', array_slice($names, 0, 3));
    if (count($names) > 3) $head .= ' 외 '.(count($names) - 3).'건';
    alert('주문할 수 없는 상품이 있습니다: '.$head.'. 장바구니에서 수량을 줄이거나 삭제한 뒤 다시 주문해 주세요.',
        cart_url('cart.php'));
}

$main_images = cart_item_main_images(array_column($lines, 'it_id'));
$item_total = 0;
foreach ($lines as $i => $l) {
    $it_id = (int)$l['it_id'];
    $lines[$i]['img'] = isset($main_images[$it_id]) ? cart_item_image_url($main_images[$it_id]) : '';
    $lines[$i]['line_total'] = (int)$l['sk_price'] * (int)$l['ct_qty'];
    $item_total += $lines[$i]['line_total'];
}

$cc = cart_config();
$is_member = isset($member['mb_id']) && $member['mb_id'] !== '';

// 쿠폰 — 회원만. 주문서에 들어오는 이 순간이 "받을 자격이 있는데 아직 안 받은" 쿠폰의
// 발급 시점이다(가입 축하·첫 구매). 순정 회원가입 코드를 건드리지 않으려는 방법이고,
// 손님 눈에는 처음 열어 본 주문서에 이미 들어와 있는 것으로 보인다.
$coupons = array();
if ($is_member) {
    cart_coupon_grant_auto($member['mb_id']);
    $coupons = cart_coupon_choices($member['mb_id'], $lines, $item_total);
}

$g5['title'] = '주문서 작성';
g5_view('cart.checkout', array(
    'lines' => $lines,
    'blocked_count' => count($picked['blocked']),
    'item_total' => $item_total,
    'coupons' => $coupons,
    'coupon_href' => cart_url('coupon.php'),
    'expect_ct_ids' => implode(',', array_map('intval', array_column($lines, 'ct_id'))),
    'buy' => count($only) ? implode(',', $only) : '',
    'is_member' => $is_member,
    // 기본값은 "지난 주문에 쓴 것" 이 먼저다 — 회원 정보는 가입 당시 값이라, 주문서에서 고쳐 쓴
    // 이메일·연락처가 다음 주문서에서 되돌아가면 매번 다시 고쳐야 한다(주소록이 있는 이유).
    'default_name' => $is_member ? cart_address_default($member, 'ad_name', 'mb_name') : '',
    'default_hp' => $is_member ? cart_address_default($member, 'ad_hp', 'mb_hp') : '',
    'default_email' => $is_member ? cart_address_default($member, 'ad_email', 'mb_email') : '',
    'addresses' => $is_member ? cart_address_list($member['mb_id']) : array(),
    'ship' => array(
        'base' => (int)$cc['cc_ship_base'],
        'free' => (int)$cc['cc_ship_free'],
        'jeju' => (int)$cc['cc_ship_jeju'],
    ),
    'pay_methods' => cart_pay_methods(),
    'token' => get_token(),
    'action_url' => cart_url('checkout_update.php'),
    'address_url' => cart_url('address_update.php'),
    'cart_href' => cart_url('cart.php'),
));
