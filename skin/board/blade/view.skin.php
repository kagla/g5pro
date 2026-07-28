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

$can_edit = isset($member['mb_id']) && $member['mb_id'] && ($is_admin || $member['mb_id'] == $view['mb_id']);

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
    // 순정 view.php 는 수정/삭제 링크를 만들지 않는다(스킨 몫) — 여기서 구성
    'update_href' => $can_edit
                     ? short_url_clean(G5_BBS_URL.'/write.php?bo_table='.$bo_table.'&w=u&wr_id='.$view['wr_id'].$qstr) : '',
    'delete_href' => $can_edit
                     ? short_url_clean(G5_BBS_URL.'/delete.php?bo_table='.$bo_table.'&wr_id='.$view['wr_id'].'&token='.(function_exists('get_token') ? get_token() : '').$qstr) : '',
    'comment_action' => G5_BBS_URL.'/write_comment_update.php',
    'comment_token'  => function_exists('get_token') ? get_token() : '',
    'is_member'      => (bool)(isset($member['mb_id']) ? $member['mb_id'] : ''),
));
