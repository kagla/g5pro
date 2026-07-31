<?php
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

if( isset($sfl) && ! in_array($sfl, array('b.it_name', 'a.it_id', 'a.iq_subject', 'a.iq_question', 'a.iq_name', 'a.mb_id')) ){
    //다른값이 들어가있다면 초기화
    $sfl = '';
}

if (G5_IS_MOBILE) {
    include_once(G5_MSHOP_PATH.'/itemqalist.php');
    return;
}

$g5['title'] = '상품문의';
include_once('./_head.php');

$sql_common = " from `{$g5['g5_shop_item_qa_table']}` a join `{$g5['g5_shop_item_table']}` b on (a.it_id=b.it_id) ";
$sql_search = " where (1) ";

if(!$sfl)
    $sfl = 'b.it_name';

if ($stx) {
    $sql_search .= " and ( ";
    switch ($sfl) {
        case "a.it_id" :
            $sql_search .= " ($sfl like '$stx%') ";
            break;
        case "a.iq_name" :
        case "a.mb_id" :
            $sql_search .= " ($sfl = '$stx') ";
            break;
        default :
            $sql_search .= " ($sfl like '%$stx%') ";
            break;
    }
    $sql_search .= " ) ";
}

if (!$sst) {
    $sst  = "a.iq_id";
    $sod = "desc";
}
// 정렬 컬럼/방향 화이트리스트
$sst = in_array($sst, array('a.iq_id', 'a.iq_datetime', 'a.it_id', 'b.it_name'), true) ? $sst : 'a.iq_id';
$sod = preg_match("/^(asc|desc)$/i", $sod) ? $sod : 'desc';
$sql_order = " order by $sst $sod ";

$sql = " select count(*) as cnt
         $sql_common
         $sql_search
         $sql_order ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) { $page = 1; } // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$sql = " select a.*, b.it_name
          $sql_common
          $sql_search
          $sql_order
          limit $from_record, $rows ";
$result = sql_query($sql);

// g5pro — 화면은 뷰가 그린다. 비밀글은 본인·관리자만 제목을 볼 수 있다 (순정 스킨과 같은 판정)
$pro_rows = array();
while ($row = sql_fetch_array($result)) {
    $row['pro_can_read'] = (!$row['iq_secret'] || $is_admin
                              || (isset($member['mb_id']) && $member['mb_id'] && $member['mb_id'] === $row['mb_id']));
    $pro_rows[] = $row;
}
g5_map_shop_itemqalist($pro_rows, $total_count, $page, $total_page, $sfl, $stx);

include_once('./_tail.php');
