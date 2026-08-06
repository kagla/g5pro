<?php
$sub_menu = '600300';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

// 내보내기 — 화면 없이 즉시 다운로드
if (isset($_GET['export']) && $_GET['export'] === '1') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="cart_items_'.date('Ymd', G5_SERVER_TIME).'.csv"');
    echo "\xEF\xBB\xBF";   // 엑셀이 UTF-8 로 읽게 하는 BOM
    $out = fopen('php://output', 'w');
    fputcsv($out, cart_csv_headers());
    foreach (cart_csv_export_rows() as $row) fputcsv($out, $row);
    fclose($out);
    exit;
}

$g5['title'] = 'CSV 입출력';
include_once(G5_ADMIN_PATH.'/admin.head.php');

// 미리보기 단계에서 저장해 둔 업로드가 있으면 요약을 보여 준다
$preview = null;
$pv_key = (isset($_GET['pv']) && !is_array($_GET['pv'])) ? preg_replace('/[^a-f0-9]/', '', $_GET['pv']) : '';
if ($pv_key) {
    $pv_file = G5_CART_DATA_PATH.'/tmp/'.$pv_key.'.csv';
    if (is_file($pv_file)) {
        $errors = array();
        $rows = cart_csv_parse($pv_file, $errors);
        $preview = cart_csv_summary($rows);
        $preview['parse_errors'] = $errors;
        $preview['row_count'] = count($rows);
        $preview['key'] = $pv_key;
    }
}

cadm_view('csv', array(
    'preview' => $preview,
    'export_url' => G5_CART_ADMIN_URL.'/csv.php?export=1',
    'action_url' => G5_CART_ADMIN_URL.'/csv_update.php',
));

include_once(G5_ADMIN_PATH.'/admin.tail.php');
