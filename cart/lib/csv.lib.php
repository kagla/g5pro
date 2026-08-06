<?php
if (!defined('_GNUBOARD_')) exit;

function cart_csv_headers()
{
    return array('상품코드', '상품명', '분류ID', '노출', 'SKU코드', '옵션', '판매가', '재고', '바코드', 'SKU사용');
}

// SKU 당 1행 — 엑셀에서 필터·일괄수정하기 좋은 평평한 꼴
function cart_csv_export_rows()
{
    global $g5;
    $rows = array();
    $result = sql_query(" select i.it_code, i.it_name, i.ca_id, i.it_show,
            s.sk_code, s.sk_option, s.sk_price, s.sk_qty, s.sk_barcode, s.sk_use
        from `{$g5['cart_item_table']}` i
        inner join `{$g5['cart_sku_table']}` s on s.it_id = i.it_id
        order by i.it_id, s.sk_id ");
    while ($r = sql_fetch_array($result)) {
        $opt = json_decode($r['sk_option'], true);
        $optstr = '';
        if (is_array($opt) && count($opt)) {
            $parts = array();
            foreach ($opt as $k => $v) $parts[] = $k.'='.$v;
            $optstr = implode('|', $parts);
        }
        $rows[] = array($r['it_code'], $r['it_name'], $r['ca_id'], $r['it_show'],
            $r['sk_code'], $optstr, $r['sk_price'], $r['sk_qty'], $r['sk_barcode'], $r['sk_use']);
    }
    return $rows;
}

function cart_csv_parse($path, &$errors)
{
    $errors = array();
    $fp = fopen($path, 'r');
    if (!$fp) { $errors[] = '파일을 열 수 없습니다.'; return array(); }

    $head = fgetcsv($fp);
    if ($head && isset($head[0])) $head[0] = preg_replace('/^\xEF\xBB\xBF/', '', $head[0]);
    if ($head !== cart_csv_headers()) {
        $errors[] = '헤더가 다릅니다. 내보내기 파일의 첫 행을 그대로 두세요.';
        fclose($fp);
        return array();
    }

    $rows = array();
    $n = 1;
    while (($line = fgetcsv($fp)) !== false) {
        $n++;
        if (count($line) === 1 && trim($line[0]) === '') continue;
        if (count($line) !== count(cart_csv_headers())) {
            $errors[] = $n.'행: 칸 수가 다릅니다('.count($line).'칸)';
            continue;
        }
        $row = array_combine(cart_csv_headers(), array_map('trim', $line));
        if ($row['상품코드'] === '' || $row['SKU코드'] === '') {
            $errors[] = $n.'행: 상품코드·SKU코드는 필수입니다';
            continue;
        }
        if ($row['판매가'] !== '' && !preg_match('/^-?[\d,]+$/', $row['판매가'])) {
            $errors[] = $n.'행: 판매가가 숫자가 아닙니다';
            continue;
        }
        if ($row['재고'] !== '' && !preg_match('/^-?[\d,]+$/', $row['재고'])) {
            $errors[] = $n.'행: 재고가 숫자가 아닙니다';
            continue;
        }
        $row['_line'] = $n;
        $rows[] = $row;
    }
    fclose($fp);
    return $rows;
}

// '색상=빨강|사이즈=L' → JSON. 빈 문자열이면 '{}'
function cart_csv_option_json($str)
{
    $str = trim($str);
    if ($str === '') return '{}';
    $map = array();
    foreach (explode('|', $str) as $pair) {
        $kv = explode('=', $pair, 2);
        if (count($kv) !== 2 || trim($kv[0]) === '') return null;
        $map[trim($kv[0])] = trim($kv[1]);
    }
    return json_encode($map, JSON_UNESCAPED_UNICODE);
}

function cart_csv_summary($rows)
{
    $sum = array('new_items' => 0, 'upd_items' => 0, 'new_skus' => 0, 'upd_skus' => 0,
        'stock_changes' => 0, 'errors' => array());
    $seen_items = array();
    foreach ($rows as $row) {
        if (cart_csv_option_json($row['옵션']) === null) {
            $sum['errors'][] = $row['_line'].'행: 옵션 형식 오류(옵션명=값|옵션명=값)';
            continue;
        }
        if ((int)$row['분류ID'] && !cart_category_get((int)$row['분류ID'])) {
            $sum['errors'][] = $row['_line'].'행: 없는 분류ID '.$row['분류ID'];
            continue;
        }
        if (!isset($seen_items[$row['상품코드']])) {
            $seen_items[$row['상품코드']] = true;
            if (cart_item_get_by_code($row['상품코드'])) $sum['upd_items']++;
            else $sum['new_items']++;
        }
        $sku = cart_sku_get_by_code($row['SKU코드']);
        if ($sku) {
            $sum['upd_skus']++;
            if ($row['재고'] !== '' && (int)str_replace(',', '', $row['재고']) !== (int)$sku['sk_qty']) {
                $sum['stock_changes']++;
            }
        } else {
            $sum['new_skus']++;
            if ($row['재고'] !== '' && (int)str_replace(',', '', $row['재고']) !== 0) $sum['stock_changes']++;
        }
    }
    return $sum;
}

// 500행 청크 반영. summary 의 오류 행은 이미 걸렀다는 전제(화면 흐름이 보장)지만 한 번 더 방어한다
function cart_csv_apply($rows, $who)
{
    $r = array('new_items' => 0, 'upd_items' => 0, 'new_skus' => 0, 'upd_skus' => 0, 'stock_changes' => 0);
    $item_cache = array();   // it_code → it_id (파일 안 중복 조회 방지)

    foreach (array_chunk($rows, 500) as $chunk) {
        sql_query(" START TRANSACTION ", false);
        foreach ($chunk as $row) {
            $optjson = cart_csv_option_json($row['옵션']);
            if ($optjson === null) continue;
            if ((int)$row['분류ID'] && !cart_category_get((int)$row['분류ID'])) continue;

            $code = $row['상품코드'];
            if (!isset($item_cache[$code])) {
                $exist = cart_item_get_by_code($code);
                $data = array(
                    'it_code' => $code,
                    'ca_id' => (int)$row['분류ID'],
                    'it_name' => $row['상품명'],
                    'it_keyword' => $exist ? $exist['it_keyword'] : '',
                    'it_content' => $exist ? $exist['it_content'] : '',
                    'it_show' => (int)$row['노출'] ? 1 : 0,
                    'it_shipping_id' => $exist ? (int)$exist['it_shipping_id'] : 0,
                );
                if ($exist) {
                    cart_item_save($data, (int)$exist['it_id']);
                    $item_cache[$code] = (int)$exist['it_id'];
                    $r['upd_items']++;
                } else {
                    $item_cache[$code] = cart_item_save($data);
                    $r['new_items']++;
                }
            }
            $it_id = $item_cache[$code];

            $sku = cart_sku_get_by_code($row['SKU코드']);
            $sdata = array(
                'it_id' => $it_id,
                'sk_code' => $row['SKU코드'],
                'sk_option' => $optjson,
                'sk_price' => (int)str_replace(',', '', $row['판매가']),
                'sk_barcode' => $row['바코드'],
                'sk_use' => (int)$row['SKU사용'] ? 1 : 0,
            );
            if ($sku && (int)$sku['it_id'] !== $it_id) continue;   // 다른 상품의 SKU 코드 — 건너뜀
            $sk_id = $sku ? cart_sku_save($sdata, (int)$sku['sk_id']) : cart_sku_save($sdata);
            if ($sku) $r['upd_skus']++; else $r['new_skus']++;

            if ($row['재고'] !== '') {
                $target = (int)str_replace(',', '', $row['재고']);
                $before = $sku ? (int)$sku['sk_qty'] : 0;
                if ($target !== $before && cart_stock_set($sk_id, $target, 'csv', 'csv', $who)) {
                    $r['stock_changes']++;
                }
            }
        }
        sql_query(" COMMIT ", false);
    }
    return $r;
}
