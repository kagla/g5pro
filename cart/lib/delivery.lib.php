<?php
if (!defined('_GNUBOARD_')) exit;

// 택배사 마스터(ycart_delivery_company)와 주문의 배송값. order.lib.php 에서 갈라 나왔다 —
// 조회 주소는 택배사 사정으로 바뀌는 값이라 눈에 잘 띄는 한 곳에 있어야 한다.
//
// 이름 규칙: cart_delivery_company_* 는 업체 마스터를 다루고,
// cart_delivery_track_url · cart_order_set_delivery 는 주문 한 건의 배송값을 다룬다.

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
