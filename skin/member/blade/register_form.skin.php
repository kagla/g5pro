<?php
if (!defined('_GNUBOARD_')) exit;

// 가입(w='') / 정보수정(w='u') 공용 — 순정 register_form_update.php 계약 유지
g5_view('bbs.register_form', array(
    'w'          => $w,
    'action_url' => $register_action_url,   // register_form_update.php
    'url'        => isset($urlencode) ? $urlencode : '',
    'agree'      => $agree,
    'agree2'     => $agree2,
    'me' => array(
        'mb_id'       => isset($member['mb_id']) ? $member['mb_id'] : '',
        'mb_name'     => isset($member['mb_name']) ? get_text($member['mb_name']) : '',
        'mb_nick'     => isset($member['mb_nick']) ? get_text($member['mb_nick']) : '',
        'mb_email'    => isset($member['mb_email']) ? $member['mb_email'] : '',
        'mb_homepage' => isset($member['mb_homepage']) ? get_text($member['mb_homepage']) : '',
    ),
    'captcha_html' => captcha_html(),
    'captcha_js'   => chk_captcha_js(),
));
