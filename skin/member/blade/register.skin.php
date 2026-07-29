<?php
if (!defined('_GNUBOARD_')) exit;

g5_view('bbs.register', array(
    'action_url'  => $register_action_url,   // register_form.php
    'stipulation' => get_text($config['cf_stipulation']),  // 이스케이프 완료 → {!! !!}
    'privacy'     => get_text($config['cf_privacy']),
));
