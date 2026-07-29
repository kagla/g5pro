<?php
if (!defined('_GNUBOARD_')) exit;

g5_view('bbs.member_confirm', array(
    'action_url' => $url,                     // 확인 후 이동할 대상 (register_form.php 등)
    'mb_id'      => $member['mb_id'],
));
