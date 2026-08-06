<?php
if (!defined('_GNUBOARD_')) exit;

function cart_csv_headers()
{
    return array('상품코드', '상품명', '분류ID', '노출', 'SKU코드', '옵션', '판매가', '재고', '바코드', 'SKU사용');
}

// 엑셀 수식/DDE 인젝션 방지 — '=','+','-','@' 로 시작하는 셀만 엑셀이 수식으로 실행하므로
// 그 넷만 가드 대상이다. 어포스트로피로 시작하는 값 자체는 건드리지 않는다(무손실을 우선).
function cart_csv_guard($s)
{
    $s = (string)$s;
    if ($s !== '' && strpos('=+-@', $s[0]) !== false) return "'".$s;
    return $s;
}

// cart_csv_guard() 의 역함수 — 선두 어포스트로피 "다음" 글자가 '=+-@' 중 하나일 때만 그
// 어포스트로피 하나를 벗긴다(=guard 가 실제로 붙였을 흔적일 때만). 그래야 손으로 만든 CSV 의
// "'90s 컬렉션"처럼 정말 어포스트로피로 시작하는 값은 그대로 보존된다. 트레이드오프: 정당한
// 값이 하필 "'=..." 처럼 어포스트로피+수식트리거 글자로 시작하는 극단 케이스만 그 어포스트로피를
// 잃는데, 수식 실행을 막는 쪽이 우선이라 수용한다.
function cart_csv_unguard($s)
{
    $s = (string)$s;
    if (strlen($s) >= 2 && $s[0] === "'" && strpos('=+-@', $s[1]) !== false) return substr($s, 1);
    return $s;
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
        $rows[] = array(cart_csv_guard($r['it_code']), cart_csv_guard($r['it_name']), $r['ca_id'], $r['it_show'],
            cart_csv_guard($r['sk_code']), cart_csv_guard($optstr), $r['sk_price'], $r['sk_qty'],
            cart_csv_guard($r['sk_barcode']), $r['sk_use']);
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
        foreach (array('상품코드', '상품명', 'SKU코드', '옵션', '바코드') as $guarded_key) {
            $row[$guarded_key] = cart_csv_unguard($row[$guarded_key]);
        }
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

// 신규 상품(=DB 에도 없고 이 파일 안 앞선 행으로도 아직 확정되지 않음)인데 분류ID 가 비었거나
// 0 이면 참 — 어느 분류에도 속하지 않는 상품이 생기는 걸 막는다. 기존 상품 수정은 분류ID 를
// 비워도(=변경 없음) 걸리지 않는다. $known_exists 는 "DB 에 있다" 뿐 아니라 "같은 파일의 앞선
// 행이 이미 이 상품코드를 유효한 신규 상품으로 확정했다"도 포함해야 한다 — 그래야 한 파일 안에서
// 행1(분류ID 있음)이 만드는 신규 상품을 행2(분류ID 빈 값)가 "또 신규라서 오류"로 잘못 보지 않는다.
// summary 와 apply 가 반드시 같은 조건식·같은 입력(존재 여부 bool)을 쓰도록 여기 하나로 모은다.
function cart_csv_item_needs_category($known_exists, $ca_id_cell)
{
    return !$known_exists && !(int)$ca_id_cell;
}

// 신규 상품인데 상품명 칸이 비었으면 참 — cart_csv_item_needs_category() 와 같은 판정 패턴.
// 기존 상품 수정은 상품명을 비워도(=변경 없음, 다른 빈 셀과 동일 규칙) 걸리지 않는다.
// summary 와 apply 가 반드시 같은 조건식·같은 입력을 쓰도록 여기 하나로 모은다.
function cart_csv_item_needs_name($known_exists, $name_cell)
{
    return !$known_exists && trim($name_cell) === '';
}

// 행의 SKU코드가 이미 DB 에 있고 그 SKU 의 소유 상품이 이 행의 상품코드와 다르면 그 소유
// 상품코드를 돌려준다(충돌 없음/신규 SKU 면 null). summary 와 apply 가 같은 조건식을 쓰도록
// 여기 하나로 모은다 — 어긋나면 미리보기 수치와 반영 결과가 갈린다.
function cart_csv_sku_conflict($row_item_code, $sku)
{
    if (!$sku) return null;
    $owner = cart_item_get((int)$sku['it_id']);
    $owner_code = $owner ? $owner['it_code'] : '(알수없음)';
    return ($owner_code === $row_item_code) ? null : $owner_code;
}

function cart_csv_summary($rows)
{
    $sum = array('new_items' => 0, 'upd_items' => 0, 'new_skus' => 0, 'upd_skus' => 0,
        'stock_changes' => 0, 'errors' => array());
    $seen_items = array();
    $file_items = array();   // 상품코드 → true, 이 파일 안 앞선 행이 이미 유효한 신규 상품으로 확정한 코드
    foreach ($rows as $row) {
        if (cart_csv_option_json($row['옵션']) === null) {
            $sum['errors'][] = $row['_line'].'행: 옵션 형식 오류(옵션명=값|옵션명=값)';
            continue;
        }
        if ((int)$row['분류ID'] && !cart_category_get((int)$row['분류ID'])) {
            $sum['errors'][] = $row['_line'].'행: 없는 분류ID '.$row['분류ID'];
            continue;
        }
        $code = $row['상품코드'];
        $known_exists = isset($file_items[$code]) || (cart_item_get_by_code($code) !== null);
        if (cart_csv_item_needs_category($known_exists, $row['분류ID'])) {
            $sum['errors'][] = $row['_line'].'행: 신규 상품은 분류ID 필수';
            continue;
        }
        if (cart_csv_item_needs_name($known_exists, $row['상품명'])) {
            $sum['errors'][] = $row['_line'].'행: 신규 상품은 상품명 필수';
            continue;
        }
        $sku = cart_sku_get_by_code($row['SKU코드']);
        $conflict_owner = cart_csv_sku_conflict($code, $sku);
        if ($conflict_owner !== null) {
            $sum['errors'][] = $row['_line'].'행: SKU코드 '.$row['SKU코드'].' 는 다른 상품(코드 '.$conflict_owner.') 소속';
            continue;
        }
        if (!isset($seen_items[$code])) {
            $seen_items[$code] = true;
            if ($known_exists) {
                $sum['upd_items']++;
            } else {
                $sum['new_items']++;
                $file_items[$code] = true;   // 이 행으로 확정 — 같은 파일의 다음 행부터는 "이미 존재"로 본다
            }
        }
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

// 500행 청크 반영. summary 와 완전히 같은 순서·같은 조건(옵션 형식 → 분류ID 존재 → 신규상품
// 분류ID 필수 → SKU 소유권 충돌)으로 건너뛸 행을 먼저 가려낸 뒤에만 실제로 쓴다 — 미리보기
// 수치와 반영 결과가 항상 일치하게 만드는 핵심 구조. $item_cache 는 "it_code → it_id" 저장소인
// 동시에 summary 의 $file_items 와 같은 역할도 한다: 캐시에 코드가 있으면 그 자체로 "이 파일
// 안에서 이미 확정된 상품"이라는 뜻이라 존재 여부 재조회 없이 바로 known_exists=true 로 본다.
function cart_csv_apply($rows, $who)
{
    $r = array('new_items' => 0, 'upd_items' => 0, 'new_skus' => 0, 'upd_skus' => 0, 'stock_changes' => 0);
    $item_cache = array();   // it_code → it_id (파일 안 중복 조회 방지 + summary 의 file_items 와 동격)

    foreach (array_chunk($rows, 500) as $chunk) {
        sql_query(" START TRANSACTION ", false);
        foreach ($chunk as $row) {
            $optjson = cart_csv_option_json($row['옵션']);
            if ($optjson === null) continue;
            if ((int)$row['분류ID'] && !cart_category_get((int)$row['분류ID'])) continue;

            $code = $row['상품코드'];
            if (isset($item_cache[$code])) {
                // 캐시 히트 — 이미 이 파일 안에서 확정된 상품이므로 DB 재조회 없이 존재함으로 본다
                $exist_item = null;
                $known_exists = true;
            } else {
                $exist_item = cart_item_get_by_code($code);
                $known_exists = ($exist_item !== null);
            }
            if (cart_csv_item_needs_category($known_exists, $row['분류ID'])) continue;
            if (cart_csv_item_needs_name($known_exists, $row['상품명'])) continue;

            $sku = cart_sku_get_by_code($row['SKU코드']);
            if (cart_csv_sku_conflict($code, $sku) !== null) continue;

            // ---- 여기까지 통과한 행만 실제로 반영한다 ----
            if (!isset($item_cache[$code])) {
                // 빈 셀 = 변경 없음(수정일 때만). 신규는 위 게이트를 통과했으니 분류ID 는 이미 있다.
                $ca_id = $row['분류ID'] !== '' ? (int)$row['분류ID'] : ($exist_item ? (int)$exist_item['ca_id'] : 0);
                $it_show = $row['노출'] !== '' ? ((int)$row['노출'] ? 1 : 0) : ($exist_item ? (int)$exist_item['it_show'] : 1);
                $data = array(
                    'it_code' => $code,
                    'ca_id' => $ca_id,
                    // 빈 셀 = 변경 없음(수정일 때만). 신규는 위 게이트를 통과했으니 상품명은 이미 있다.
                    'it_name' => $row['상품명'] !== '' ? $row['상품명'] : ($exist_item ? $exist_item['it_name'] : ''),
                    'it_keyword' => $exist_item ? $exist_item['it_keyword'] : '',
                    'it_content' => $exist_item ? $exist_item['it_content'] : '',
                    'it_show' => $it_show,
                    'it_shipping_id' => $exist_item ? (int)$exist_item['it_shipping_id'] : 0,
                );
                if ($exist_item) {
                    cart_item_save($data, (int)$exist_item['it_id']);
                    $item_cache[$code] = (int)$exist_item['it_id'];
                    $r['upd_items']++;
                } else {
                    $item_cache[$code] = cart_item_save($data);
                    $r['new_items']++;
                }
            }
            $it_id = $item_cache[$code];

            // 빈 셀 = 변경 없음(수정일 때만). 신규 SKU 의 기본값: 판매가 0, 바코드 '', 사용함
            $price = $row['판매가'] !== '' ? (int)str_replace(',', '', $row['판매가']) : ($sku ? (int)$sku['sk_price'] : 0);
            $barcode = $row['바코드'] !== '' ? $row['바코드'] : ($sku ? $sku['sk_barcode'] : '');
            $sk_use = $row['SKU사용'] !== '' ? ((int)$row['SKU사용'] ? 1 : 0) : ($sku ? (int)$sku['sk_use'] : 1);
            $sdata = array(
                'it_id' => $it_id,
                'sk_code' => $row['SKU코드'],
                'sk_option' => $optjson,
                'sk_price' => $price,
                'sk_barcode' => $barcode,
                'sk_use' => $sk_use,
            );
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
