<?php
if (!defined('_GNUBOARD_')) exit;

// view.skin.php(blade) 가 선언한 $g5_blade_comments 를 채운다 — 출력 금지
if (!isset($g5_blade_comments)) $g5_blade_comments = array();

for ($i = 0; $i < count($list); $i++) {
    $g5_blade_comments[] = array(
        'id'        => $list[$i]['wr_id'],
        'name'      => $list[$i]['name'],          // 사이드뷰 HTML → {!! !!}
        'content'   => $list[$i]['content'],       // 순정 가공 HTML → {!! !!}
        'datetime'  => $list[$i]['wr_datetime'],
        'depth'     => strlen((string)$list[$i]['wr_comment_reply']),
        'is_secret' => strpos((string)$list[$i]['wr_option'], 'secret') !== false,
    );
}
