<?php
$sub_menu = '600450';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

// GPC addslashes 를 원복한 뒤 넘긴다 — 저장 지점(cart_delivery_company_save)이 이스케이프한다.
// 여기서 한 번 더 하면 이중 이스케이프가 된다(config_update 와 같은 방식).
$posted = array();
if (isset($_POST['dc']) && is_array($_POST['dc'])) {
    foreach ($_POST['dc'] as $key => $row) {
        if (!is_array($row)) continue;
        $clean = array();
        foreach (array('name', 'tel', 'url', 'invoice', 'use') as $f) {
            $clean[$f] = (isset($row[$f]) && !is_array($row[$f]))
                ? strip_tags(stripslashes(trim($row[$f]))) : '';
        }
        $posted[(string)$key] = $clean;
    }
}

// 화면 순서는 dc_seq[] 가 말한다 — 드래그로 옮긴 <tr> 의 순서를 제출 직전에 담는다.
// 폼 필드도 문서 순서로 제출되므로 $posted 만으로도 순서를 알 수 있지만, 그건 눈에 안 보이는
// 약속이다. 순서를 다루는 화면이니 계약을 화면과 서버 양쪽에 적어 둔다(item_form 의 im_seq[] 관례).
$rows = array();
if (isset($_POST['dc_seq']) && is_array($_POST['dc_seq'])) {
    foreach ($_POST['dc_seq'] as $key) {
        if (is_array($key)) continue;
        $key = (string)$key;
        if (isset($posted[$key])) { $rows[$key] = $posted[$key]; unset($posted[$key]); }
    }
}
// dc_seq 에 없던 행은 뒤에 붙인다 — JS 가 꺼져도 저장은 되고 순서만 안 바뀐다
foreach ($posted as $key => $row) $rows[$key] = $row;

$del_ids = (isset($_POST['dc_del']) && is_array($_POST['dc_del'])) ? $_POST['dc_del'] : array();

cart_delivery_company_save($rows, $del_ids);

goto_url(G5_CART_ADMIN_URL.'/delivery_company.php');
