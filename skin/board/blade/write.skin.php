<?php
if (!defined('_GNUBOARD_')) exit;

$categories = array();
if ($is_category && $board['bo_category_list']) {
    $w_ca_name = isset($write['ca_name']) ? $write['ca_name'] : (isset($sca) ? $sca : '');
    foreach (explode('|', (string)$board['bo_category_list']) as $c) {
        $categories[] = array('name' => $c, 'selected' => ($w_ca_name === $c));
    }
}

g5_view('bbs.board_write', array(
    'board' => array(
        'bo_table'   => $bo_table,
        'bo_subject' => $board['bo_subject'],
    ),
    'w'          => $w,                       // '' 새글, 'u' 수정
    'action_url' => $action_url,
    'subject'    => isset($write['wr_subject']) ? get_text($write['wr_subject'], 0) : '',
    'categories' => $categories,
    'is_member'  => (bool)$is_member,
    'name'       => $name,
    'is_name'    => (bool)$is_name,
    'is_secret'  => (int)$is_secret,          // 1=선택 가능, 2=강제
    'secret_checked' => isset($write['wr_option']) && strpos((string)$write['wr_option'], 'secret') !== false,
    'option_hidden'  => $option_hidden,       // 순정 hidden 필드 HTML → {!! !!}
    'editor_html'    => $editor_html,          // 순정 에디터/textarea HTML → {!! !!}
    'editor_js'      => $editor_js,            // 순정 검증 JS 조각 → {!! !!}
    'is_use_captcha' => (bool)$is_use_captcha,
    'captcha_html'   => $is_use_captcha ? captcha_html() : '',
    'captcha_js'     => $is_use_captcha ? captcha_js() : '',
    'file_count'     => (int)$file_count,
    'list_href'      => short_url_clean(G5_BBS_URL.'/board.php?bo_table='.$bo_table),
    'token'          => function_exists('get_token') ? get_token() : '',
));
