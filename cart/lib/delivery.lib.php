<?php
if (!defined('_GNUBOARD_')) exit;

// 택배사 마스터(ycart_delivery_company)와 주문의 배송값. order.lib.php 에서 갈라 나왔다 —
// 조회 주소는 택배사 사정으로 바뀌는 값이라 눈에 잘 띄는 한 곳에 있어야 한다.
//
// 이름 규칙: cart_delivery_company_* 는 업체 마스터를 다루고,
// cart_delivery_track_url · cart_order_set_delivery 는 주문 한 건의 배송값을 다룬다.

// ---------- 배송비 ----------
// 몰 전역 단일 정책: 기본 배송비 + 조건부 무료(기준액 0 이면 없음) + 권역 추가비.
// 조건부 무료를 충족해도 권역 추가비는 남는다 — 실제 택배 원가가 남는 구간이라 몰 관례를 따른다.
//
// 권역(제주·도서산간)은 코드가 아니라 ycart_ship_zone 에 있다. 택배사·계약마다 요금이 다르고
// 섬 우편번호가 흩어져 있어, 한 몰에 맞춘 목록을 코드에 박으면 다음 몰에서 틀린다.

// 배송비의 내역 — 화면과 주문 생성이 같은 함수를 쓴다.
// array(base, free_applied, extra, zone, total)
function cart_shipping_breakdown($item_total, $zip = '')
{
    $cc = cart_config();
    $base = (int)$cc['cc_ship_base'];
    $free = ((int)$cc['cc_ship_free'] > 0 && (int)$item_total >= (int)$cc['cc_ship_free']);
    if ($free) $base = 0;

    $zone = cart_ship_zone_match($zip);
    $extra = $zone ? (int)$zone['sz_fee'] : 0;

    return array(
        'base' => $base,
        'free_applied' => $free,
        'extra' => $extra,
        'zone' => $zone ? $zone['sz_name'] : '',
        'total' => $base + $extra,
    );
}

function cart_shipping_fee($item_total, $zip = '')
{
    $b = cart_shipping_breakdown($item_total, $zip);
    return $b['total'];
}

// 이 우편번호가 걸리는 권역 — 겹치면 **좁은 구간 하나**. 절대 더하지 않는다.
//
// 좁은 쪽이 이기는 이유: 택배사 요금표는 "넓은 구간이 기본, 좁은 구간이 예외" 로 쓰인다.
// 로젠 파일에 실제로 이런 줄이 있다 — 완도군 금일읍 전체가 6,000원인데 그 안의 충동리만
// 5,000원. 비싼 쪽을 고르면 충동리에서 1,000원을 더 받게 된다. 반대로 제주도 전체 5,000원
// 안의 추자면 6,000원 같은 경우는 좁은 쪽이 비싸므로 어느 규칙으로도 6,000원이 나온다.
// 즉 "좁은 쪽" 이 요금표의 뜻을 그대로 옮기는 유일한 규칙이다.
//
// 폭이 같은데 요금이 다르면 비싼 쪽 — 설정이 모순일 때 덜 받아 손해 보지 않는 쪽으로 둔다.
// 우편번호는 5자리 숫자 문자열 비교로 판정한다 — 앞자리 0 이 살아 있어야 구간이 맞는다.
function cart_ship_zone_match($zip)
{
    global $g5;
    $zip = preg_replace('/[^0-9]/', '', (string)$zip);
    if (strlen($zip) !== 5) return null;
    $z = sql_real_escape_string($zip);
    $row = sql_fetch(" select * from `{$g5['ycart_ship_zone_table']}`
        where sz_use = 1 and sz_zip_from <> '' and sz_zip_to <> ''
          and '$z' between sz_zip_from and sz_zip_to
        order by (sz_zip_to + 0) - (sz_zip_from + 0) asc, sz_fee desc, sz_order, sz_id limit 1 ");
    return $row ? $row : null;
}

// 상품 상세의 배송 안내에 쓰는 한 줄 — "제주 3,000원 · 도서산간 5,000원".
// 한 이름에 구간이 여러 줄이므로 이름별로 접고, 같은 이름에 요금이 다르면 가장 비싼 것을 적는다
// (손님에게 덜 받을 것처럼 말하지 않는다). 쓰는 권역이 없으면 빈 문자열.
function cart_ship_zone_summary()
{
    $by = array();
    foreach (cart_ship_zone_list() as $z) {
        $name = $z['sz_name'];
        $fee = (int)$z['sz_fee'];
        if ($fee < 1) continue;
        if (!isset($by[$name]) || $fee > $by[$name]) $by[$name] = $fee;
    }
    $out = array();
    foreach ($by as $name => $fee) $out[] = $name.' '.number_format($fee).'원';
    return implode(' · ', $out);
}

function cart_ship_zone_list($only_use = true)
{
    global $g5;
    $where = $only_use ? " where sz_use = 1 " : '';
    $rows = array();
    $result = sql_query(" select * from `{$g5['ycart_ship_zone_table']}`
        $where order by sz_order, sz_id ");
    while ($r = sql_fetch_array($result)) $rows[] = $r;
    return $rows;
}

// 환경설정 화면의 저장 — 택배사 저장과 같은 계약이다(배열 순서가 곧 화면 순서).
// $rows : array(행키 => array('name','from','to','fee','use')). 행키는 sz_id 또는 'new1'
function cart_ship_zone_save($rows, $del_ids)
{
    global $g5;
    $table = $g5['ycart_ship_zone_table'];
    $del = array_map('intval', (array)$del_ids);

    foreach ($del as $id) {
        if ($id > 0) sql_query(" delete from `$table` where sz_id = '$id' ", true);
    }

    $ord = 0;
    foreach ((array)$rows as $key => $row) {
        $name = mb_substr(trim((string)(isset($row['name']) ? $row['name'] : '')), 0, 50, 'utf-8');
        // 이름이 빈 줄은 없는 셈 친다(택배사관리와 같은 규칙 — 지우려면 삭제 체크다)
        if ($name === '') continue;

        $id = (strpos((string)$key, 'new') === 0) ? 0 : (int)$key;
        if ($id > 0 && in_array($id, $del, true)) continue;

        // 우편번호는 5자리로 맞춘다. 짧게 적으면(예: 630) 문자열 비교가 엉뚱하게 걸리므로
        // 앞을 0 으로 채우는 대신 뒤를 채운다: 시작은 0, 끝은 9 — "63" 이 63000~63999 가 된다.
        $from = preg_replace('/[^0-9]/', '', (string)(isset($row['from']) ? $row['from'] : ''));
        $to = preg_replace('/[^0-9]/', '', (string)(isset($row['to']) ? $row['to'] : ''));
        if ($from === '' && $to === '') continue;              // 범위가 없으면 걸릴 일도 없다
        if ($to === '') $to = $from;
        if ($from === '') $from = $to;
        $from = str_pad(substr($from, 0, 5), 5, '0');
        $to = str_pad(substr($to, 0, 5), 5, '9');
        if ($from > $to) { $swap = $from; $from = $to; $to = $swap; }   // 거꾸로 적었으면 바로잡는다

        $fee = max(0, (int)str_replace(',', '', (string)(isset($row['fee']) ? $row['fee'] : 0)));
        $use = (isset($row['use']) && $row['use'] !== '') ? 1 : 0;
        $ord += 1;

        $set = " sz_name = '".sql_real_escape_string($name)."',
                 sz_zip_from = '".sql_real_escape_string($from)."',
                 sz_zip_to = '".sql_real_escape_string($to)."',
                 sz_fee = '$fee', sz_order = '$ord', sz_use = '$use' ";
        if ($id > 0) sql_query(" update `$table` set $set where sz_id = '$id' ", true);
        else sql_query(" insert into `$table` set $set ", true);
    }
}

// ---------- 택배사 ----------

// 택배사 목록. 기본은 쓰는 것만 — 관리 화면만 false 로 안 쓰는 것까지 가져간다.
function cart_delivery_company_list($only_use = true)
{
    global $g5;
    $where = $only_use ? " where dc_use = 1 " : '';
    $rows = array();
    $result = sql_query(" select * from `{$g5['ycart_delivery_company_table']}`
        $where order by dc_order, dc_id ");
    while ($r = sql_fetch_array($result)) $rows[] = $r;
    return $rows;
}

function cart_delivery_company_get($dc_id)
{
    global $g5;
    $dc_id = (int)$dc_id;
    if ($dc_id < 1) return null;
    $row = sql_fetch(" select * from `{$g5['ycart_delivery_company_table']}` where dc_id = '$dc_id' ");
    return $row ? $row : null;
}

// 배송관리에서 아직 택배사가 안 정해진 주문에 미리 골라 둘 하나.
// 대부분의 몰이 한 곳으로 보내므로, 이게 있으면 송장번호만 찍으면 된다.
function cart_delivery_company_default()
{
    global $g5;
    $row = sql_fetch(" select * from `{$g5['ycart_delivery_company_table']}`
        where dc_default = 1 and dc_use = 1 order by dc_order, dc_id limit 1 ");
    return $row ? $row : null;
}

// 송장 조회 주소 — 택배사 행의 주소로 잇는다. 행이 없거나(지워짐) 주소가 비면 이름으로 알아본다.
// 이름 폴백은 자유 입력 시절의 옛 주문 때문에 남긴다(그때는 od_dc_id 가 없다).
// 못 알아보면 빈 문자열 — 엉뚱한 곳으로 가는 링크는 없느니만 못하다(화면은 번호만 보여 준다).
function cart_delivery_track_url($dc_id, $dc_name, $invoice)
{
    $invoice = preg_replace('/[^0-9]/', '', (string)$invoice);
    if ($invoice === '') return '';

    $dc = cart_delivery_company_get($dc_id);
    if ($dc && $dc['dc_url'] !== '') return $dc['dc_url'].$invoice;

    $name = str_replace(' ', '', (string)$dc_name);
    if ($name === '') return '';
    $map = array(
        'CJ' => 'https://trace.cjlogistics.com/next/tracking.html?wblNo=',
        '대한통운' => 'https://trace.cjlogistics.com/next/tracking.html?wblNo=',
        '우체국' => 'https://service.epost.go.kr/trace.RetrieveDomRigiTraceList.comm?sid1=',
        '한진' => 'https://www.hanjin.com/kor/CMS/DeliveryMgr/WaybillResult.do?mCode=MN038&schLang=KR&wblnumText2=',
        '롯데' => 'https://www.lotteglogis.com/home/reservation/tracking/linkView?InvNo=',
        '로젠' => 'https://www.ilogen.com/web/personal/trace/',
    );
    foreach ($map as $key => $url) {
        if (stripos($name, $key) !== false) return $url.$invoice;
    }
    return '';
}

// 주문 한 건의 배송값 저장. 배송관리와 주문상세가 같이 쓴다.
// 택배사 유형(dc_invoice)에 따라 송장번호와 배송안내 중 한쪽만 남긴다 — 택배사를 바꿨을 때
// 앞뒤 안 맞는 값이 한 주문에 같이 남지 않게 한다.
function cart_order_set_delivery($od_id, $dc_id, $invoice, $note, $memo)
{
    global $g5;
    $od_id = (int)$od_id;
    $dc_id = (int)$dc_id;
    $memo_sql = " od_delivery_admin_memo = '"
        .sql_real_escape_string(mb_substr(trim((string)$memo), 0, 255, 'utf-8'))."' ";

    // 택배사를 안 골랐으면 배송 세 칸은 건드리지 않는다. 옛 자유입력 주문은 select 가 빈 채로
    // 뜨는데, 메모만 적고 저장했다고 이미 찍힌 택배사 이름이 날아가면 안 된다.
    // 잘못 고른 택배사는 다른 것을 골라 덮으면 된다.
    if ($dc_id === 0) {
        sql_query(" update `{$g5['ycart_order_table']}` set $memo_sql where od_id = '$od_id' ", true);
        return;
    }

    // 없는 택배사면 아무것도 저장하지 않는다. select 를 쓰는 정상 경로에서는 생기지 않는다.
    $dc = cart_delivery_company_get($dc_id);
    if (!$dc) return;

    $takes = ((int)$dc['dc_invoice'] === 1);
    $invoice = $takes ? mb_substr(trim((string)$invoice), 0, 50, 'utf-8') : '';
    $note = $takes ? '' : mb_substr(trim((string)$note), 0, 255, 'utf-8');

    sql_query(" update `{$g5['ycart_order_table']}`
        set od_dc_id = '".(int)$dc['dc_id']."',
            od_dc_name = '".sql_real_escape_string($dc['dc_name'])."',
            od_invoice = '".sql_real_escape_string($invoice)."',
            od_delivery_note = '".sql_real_escape_string($note)."',
            $memo_sql
        where od_id = '$od_id' ", true);
}

// 관리 화면 저장 — 행 배열을 통째로 받아 한 번에 반영한다.
// $rows    : array(행키 => array('name','tel','url','invoice','use')). 행키는 dc_id 또는 'new1'.
//            **배열 순서가 곧 화면 순서다** — dc_order 를 위에서부터 1,2,3… 으로 매긴다.
//            화면은 늘 목록 전체를 보낸다. 일부만 넘기면 넘긴 것들만 1부터 다시 매겨져
//            안 넘긴 행과 번호가 겹친다(깨지지는 않고 dc_id 로 갈린다).
// $del_ids : 지울 dc_id 배열
function cart_delivery_company_save($rows, $del_ids)
{
    global $g5;
    $table = $g5['ycart_delivery_company_table'];
    $del = array_map('intval', (array)$del_ids);

    foreach ($del as $id) {
        // 지워도 이미 나간 주문은 od_dc_name 스냅샷이 남아 안 깨진다(조회 링크는 이름 폴백).
        if ($id > 0) sql_query(" delete from `$table` where dc_id = '$id' ", true);
    }

    $ord = 0;
    foreach ((array)$rows as $key => $row) {
        $name = mb_substr(trim((string)(isset($row['name']) ? $row['name'] : '')), 0, 50, 'utf-8');
        // 이름이 빈 줄은 없는 셈 친다 — 새 줄이면 안 만들고, 기존 행이면 건드리지 않는다.
        // 이름을 지워 사라지게 하는 길은 두지 않는다. 지우려면 삭제 체크다.
        if ($name === '') continue;

        $id = (strpos((string)$key, 'new') === 0) ? 0 : (int)$key;
        if ($id > 0 && in_array($id, $del, true)) continue;

        $takes = (isset($row['invoice']) && $row['invoice'] !== '') ? 1 : 0;
        $url = trim((string)(isset($row['url']) ? $row['url'] : ''));
        // 관리자가 손으로 적는 값이라 최소한의 방어를 둔다 — 엉뚱한 곳으로 가는 링크를 만들지 않는다
        if (!preg_match('#^https?://#i', $url)) $url = '';
        // 송장을 안 받는 수단이면 조회주소는 쓸 데가 없다
        if (!$takes) $url = '';
        $url = mb_substr($url, 0, 255, 'utf-8');
        $use = (isset($row['use']) && $row['use'] !== '') ? 1 : 0;
        // 연락처는 모양을 강제하지 않는다 — 1588-0000·02-000-0000·내선 안내가 섞여 들어온다.
        // 숫자만 남기면 "1588-1255 (내선 2)" 같은 실제 안내가 뭉개진다.
        $tel = mb_substr(trim((string)(isset($row['tel']) ? $row['tel'] : '')), 0, 30, 'utf-8');
        $ord += 1;                                  // 화면에 놓인 순서가 그대로 정렬값이다

        $set = " dc_name = '".sql_real_escape_string($name)."',
                 dc_tel = '".sql_real_escape_string($tel)."',
                 dc_url = '".sql_real_escape_string($url)."',
                 dc_invoice = '$takes', dc_order = '$ord', dc_use = '$use' ";
        if ($id > 0) {
            sql_query(" update `$table` set $set where dc_id = '$id' ", true);
        } else {
            sql_query(" insert into `$table` set $set ", true);
        }
    }

    // 기본은 고르는 것이 아니라 **사용 켠 것 중 맨 위**다. 라디오를 따로 두면 순서와 기본이
    // 어긋날 수 있고("첫 줄인데 기본이 아닌" 상태), 무엇보다 안 쓰는 택배사가 기본이 되어
    // 배송관리 select 에 뜨지도 않는 것이 미리 골라지는 일이 생긴다.
    sql_query(" update `$table` set dc_default = 0 ", true);
    $row = sql_fetch(" select dc_id from `$table` where dc_use = 1 order by dc_order, dc_id limit 1 ");
    if ($row) sql_query(" update `$table` set dc_default = 1 where dc_id = '".(int)$row['dc_id']."' ", true);
}
