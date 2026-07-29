<?php
if (!defined('_GNUBOARD_')) exit;

include_once(G5_LIB_PATH.'/thumbnail.lib.php'); // get_view_thumbnail()

// 댓글: 순정 view_comment.php 백엔드를 재사용하고,
// blade용 view_comment.skin.php 가 echo 대신 $g5_blade_comments 를 채운다
$g5_blade_comments = array();
include_once(G5_BBS_PATH.'/view_comment.php');

$files = array();
if (isset($view['file']) && is_array($view['file'])) {
    for ($i = 0; $i < (int)$view['file']['count']; $i++) {
        if (empty($view['file'][$i]['source'])) continue;
        $files[] = array(
            'source'   => $view['file'][$i]['source'],
            'href'     => $view['file'][$i]['href'],
            'size'     => $view['file'][$i]['size'],
            'download' => $view['file'][$i]['download'],
            'is_image' => (bool)$view['file'][$i]['view'],
            'view'     => $view['file'][$i]['view'],   // 이미지면 <img> HTML
        );
    }
}

$links = array();
for ($i = 1; $i <= 2; $i++) {
    if (empty($view['link'][$i])) continue;
    $links[] = array(
        'url'  => $view['link'][$i],
        'href' => $view['link_href'][$i],
        'hit'  => $view['link_hit'][$i],
    );
}

g5_view('bbs.board_view', array(
    'board' => array(
        'bo_table'   => $bo_table,
        'bo_subject' => $board['bo_subject'],
    ),
    'post' => array(
        'wr_id'    => $view['wr_id'],
        'subject'  => get_text($view['wr_subject']),   // 이스케이프 완료 → {!! !!}
        'name'     => $view['name'],                   // 사이드뷰 HTML → {!! !!}
        'datetime' => $view['wr_datetime'],
        'hit'      => $view['wr_hit'],
        'ca_name'  => isset($view['ca_name']) ? $view['ca_name'] : '',
        'content'  => get_view_thumbnail($view['content']),  // 순정 가공 HTML → {!! !!}
    ),
    'files'       => $files,
    'links'       => $links,
    'comments'    => $g5_blade_comments,
    'list_href'   => short_url_clean(G5_BBS_URL.'/board.php?bo_table='.$bo_table.$qstr),
    'write_href'  => ($member['mb_level'] >= $board['bo_write_level'])
                     ? short_url_clean(G5_BBS_URL.'/write.php?bo_table='.$bo_table) : '',
    // 순정 view.php 가 만든 링크 (&amp; 엔티티 포함 → 뷰에서 {!! !!})
    'update_href' => $update_href,
    'delete_href' => $delete_href,
    'reply_href'  => $reply_href,
    'prev_href'   => $prev_href,
    'next_href'   => $next_href,
    'comment_action' => $comment_action_url,
    'comment_hidden' => array(
        'w'          => 'c',
        'bo_table'   => $bo_table,
        'wr_id'      => $view['wr_id'],
        'comment_id' => '',
        'sca'        => isset($sca) ? $sca : '',
        'sfl'        => isset($sfl) ? $sfl : '',
        'stx'        => isset($stx) ? $stx : '',
        'spt'        => isset($spt) ? $spt : '',
        'page'       => isset($page) ? $page : '',
        'is_good'    => '',
    ),
    'is_member'      => (bool)(isset($member['mb_id']) ? $member['mb_id'] : ''),
));
