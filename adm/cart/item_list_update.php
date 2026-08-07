<?php
$sub_menu = '600100';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$who = isset($member['mb_id']) ? $member['mb_id'] : 'admin';

// 돌아갈 목록 — 검색어·분류·페이지를 유지해 작업하던 자리로 되돌린다
$qs = array();
foreach (array('q', 'ca_id', 'page', 'per', 'show') as $k) {
    if (isset($_POST['ret_'.$k]) && !is_array($_POST['ret_'.$k]) && $_POST['ret_'.$k] !== '') {
        $qs[$k] = $_POST['ret_'.$k];
    }
}
$back = G5_CART_ADMIN_URL.'/item_list.php'.($qs ? '?'.http_build_query($qs) : '');

// 행 삭제 — 삭제 버튼이 del_it_id 를 실어 보낸다(저장 제출과 같은 폼, 여기서 갈라진다)
$del_it_id = (isset($_POST['del_it_id']) && !is_array($_POST['del_it_id'])) ? (int)$_POST['del_it_id'] : 0;
if ($del_it_id) {
    $err = cart_item_delete($del_it_id);
    if ($err) alert($err, $back);
    alert('상품을 삭제했습니다.', $back);
}

// 선택 저장 — 체크한 행만. 값은 전부 [행번호] 키로 와서 미체크 체크박스가 빠져도 안 밀린다.
$chk = (isset($_POST['chk']) && is_array($_POST['chk'])) ? $_POST['chk'] : array();
if (!$chk) alert('저장할 상품을 체크하세요.', $back);

$arr = function ($key) {
    return (isset($_POST[$key]) && is_array($_POST[$key])) ? $_POST[$key] : array();
};
$it_ids = $arr('it_id');
$sk_ids = $arr('sk_id');
$prices = $arr('sk_price');
$qtys = $arr('sk_qty');
$shows = $arr('it_show');

$saved = 0;
foreach ($chk as $i) {
    $i = (int)$i;
    $it_id = isset($it_ids[$i]) ? (int)$it_ids[$i] : 0;
    $item = cart_item_get($it_id);
    if (!$item) continue;   // 다른 창에서 지워진 행 — 건너뛴다

    // 노출 — 체크가 빠진 행은 배열에 없으므로 그 자체가 '숨김'이다
    sql_query(" update `{$g5['ycart_item_table']}`
        set it_show = '".(!empty($shows[$i]) ? 1 : 0)."'
        where it_id = '$it_id' ", true);

    // 단일 SKU 상품만 목록에서 가격·재고를 고칠 수 있다(여러 SKU 는 상품 수정 화면에서)
    $sk_id = isset($sk_ids[$i]) ? (int)$sk_ids[$i] : 0;
    if ($sk_id) {
        $sku = cart_sku_get($sk_id);
        if (!$sku || (int)$sku['it_id'] !== $it_id) alert('상품과 SKU 가 맞지 않습니다.', $back);
        if (isset($prices[$i]) && !is_array($prices[$i])) {
            $sku['sk_price'] = (int)str_replace(',', '', $prices[$i]);
            cart_sku_save($sku, $sk_id);
        }
        if (isset($qtys[$i]) && !is_array($qtys[$i]) && $qtys[$i] !== '') {
            cart_stock_set($sk_id, (int)str_replace(',', '', $qtys[$i]), 'manual', 'inline', $who);
        }
    }
    $saved++;
}

alert($saved.'개 상품을 저장했습니다.', $back);
