<?php
include_once('./_common.php');
define('G5_BLADE_PAGE', true); // g5blade 직통 화면

if( isset($sfl) && ! in_array($sfl, array('b.it_name', 'a.it_id', 'a.is_subject', 'a.is_content', 'a.is_name', 'a.mb_id')) ){
    //다른값이 들어가있다면 초기화
    $sfl = '';
}

if (G5_IS_MOBILE) {
    include_once(G5_MSHOP_PATH.'/itemuselist.php');
    return;
}

$g5['title'] = '사용후기';
include_once('./_head.php');

$sql_common = " from `{$g5['g5_shop_item_use_table']}` a join `{$g5['g5_shop_item_table']}` b on (a.it_id=b.it_id) ";
$sql_search = " where a.is_confirm = '1' ";

if(!$sfl)
    $sfl = 'b.it_name';

if ($stx) {
    $sql_search .= " and ( ";
    switch ($sfl) {
        case "a.it_id" :
            $sql_search .= " ($sfl like '$stx%') ";
            break;
        case "a.is_name" :
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
    $sst  = "a.is_id";
    $sod = "desc";
}
// 정렬 컬럼/방향 화이트리스트
$sst = in_array($sst, array('a.is_id', 'a.is_datetime', 'a.is_score', 'a.it_id', 'b.it_name'), true) ? $sst : 'a.is_id';
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

$sql = " select *
          $sql_common
          $sql_search
          $sql_order
          limit $from_record, $rows ";
$result = sql_query($sql);

// g5blade — 화면은 뷰가 그린다 (순정 검색·정렬·페이징 쿼리는 위 그대로)
$blade_rows = array();
while ($row = sql_fetch_array($result)) $blade_rows[] = $row;
g5_map_shop_itemuselist($blade_rows, $total_count, $page, $total_page, $sfl, $stx);

include_once('./_tail.php');
