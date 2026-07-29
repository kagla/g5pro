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
        'del_link'  => isset($list[$i]['del_link']) ? $list[$i]['del_link'] : '',  // &amp; 포함 → {!! !!}, 비회원 글은 password.php 경유
        'is_edit'   => (bool)$list[$i]['is_edit'],
        'is_reply'  => (bool)$list[$i]['is_reply'],   // 깊이 제한(5) 포함 순정 판정
        'raw'       => $list[$i]['content1'],         // 수정 폼 채움용 원문
    );
}
