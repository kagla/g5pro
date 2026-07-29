<?php
// blade 파생 스킨: 갤러리 — 뷰와 썸네일만 지정하고 blade 원본에 위임
if (!defined('_GNUBOARD_')) exit;
$g5_blade_list_view  = 'bbs.board_list_gallery';
$g5_blade_list_thumb = true;
include(G5_SKIN_PATH.'/board/blade/list.skin.php');
