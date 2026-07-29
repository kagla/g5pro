<?php
// blade 스킨: 마크업 없음 — 전역변수를 배열로 매핑해 g5_view() 호출만 한다
// 파생 스킨(blade_gallery 등)은 $g5_blade_list_view / $g5_blade_list_thumb 지정 후 이 파일을 include
if (!defined('_GNUBOARD_')) exit;

$g5_blade_list_view  = isset($g5_blade_list_view) ? $g5_blade_list_view : 'bbs.board_list';
$g5_blade_list_thumb = !empty($g5_blade_list_thumb);
if ($g5_blade_list_thumb) include_once(G5_LIB_PATH.'/thumbnail.lib.php');

$items = array();
foreach ($list as $row) {
    $items[] = array(
        'wr_id'       => $row['wr_id'],
        'num'         => isset($row['num']) ? $row['num'] : '',
        'href'        => $row['href'],
        'subject'     => $row['subject'],              // get_text 완료 HTML-safe → {!! !!}
        'name'        => $row['name'],                 // 사이드뷰 HTML → {!! !!}
        'datetime'    => $row['datetime2'],
        'hit'         => $row['wr_hit'],
        'comment_cnt' => (int)$row['wr_comment'],
        'is_notice'   => !empty($row['is_notice']),
        'icon_new'    => !empty($row['icon_new']),
        'icon_file'   => !empty($row['icon_file']),
        'icon_secret' => !empty($row['icon_secret']),
        // 갤러리 변형: ['src'=>URL, 'alt'=>...] — src 비면 이미지 없음
        'thumb'       => $g5_blade_list_thumb
            ? get_list_thumbnail($bo_table, $row['wr_id'],
                  ($board['bo_gallery_width'] ?: 300), ($board['bo_gallery_height'] ?: 225), false, true)
            : null,
    );
}

$categories = array();
if ($is_category && $board['bo_category_list']) {
    foreach (explode('|', (string)$board['bo_category_list']) as $c) {
        $categories[] = array(
            'name'   => $c,
            'href'   => short_url_clean(G5_BBS_URL.'/board.php?bo_table='.$bo_table.'&sca='.urlencode($c)),
            'active' => ($sca === $c),
        );
    }
}

g5_view($g5_blade_list_view, array(
    'board' => array(
        'bo_table'   => $bo_table,
        'bo_subject' => $board['bo_subject'],
    ),
    'items'       => $items,
    'categories'  => $categories,
    'total_count' => (int)$total_count,
    'page'        => (int)$page,
    'total_page'  => (int)$total_page,
    'page_href'   => short_url_clean(G5_BBS_URL.'/board.php?bo_table='.$bo_table
                     .'&sca='.urlencode($sca).'&sfl='.urlencode($sfl).'&stx='.urlencode($stx).'&page='),
    'write_href'  => $write_href,
    'rss_href'    => $rss_href,
    'admin_href'  => $admin_href,
    'search'      => array('sfl' => $sfl, 'stx' => $stx),
    'board_url'   => short_url_clean(G5_BBS_URL.'/board.php?bo_table='.$bo_table),
    'is_checkbox' => (bool)$is_checkbox,   // 관리자에게만 true (순정 판정)
    'list_update_action' => G5_BBS_URL.'/board_list_update.php',
));
