<?php
if (!defined('_GNUBOARD_')) exit;

// register_result.php 가 $mb (가입 회원 row) 를 준비한다
g5_view('bbs.register_result', array(
    'mb_id'   => isset($mb['mb_id']) ? get_text($mb['mb_id']) : '',
    'mb_nick' => isset($mb['mb_nick']) ? get_text($mb['mb_nick']) : '',
));
