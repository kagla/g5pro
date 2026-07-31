<?php
include_once('./_common.php');

if(defined('G5_THEME_PATH')) {
    $group_file = G5_THEME_PATH.'/group.php';
    if(is_file($group_file)) {
        require_once($group_file);
        return;
    }
    unset($group_file);
}

if (G5_IS_MOBILE) {
    include_once(G5_MOBILE_PATH.'/group.php');
    return;
}

define('G5_PRO_PAGE', true); // g5pro 직통 화면

if(!$is_admin && $group['gr_device'] == 'mobile')
    alert($group['gr_subject'].' 그룹은 모바일에서만 접근할 수 있습니다.');

$g5['title'] = $group['gr_subject'];
include_once('./_head.php');

// 그룹 소속 게시판 (순정 쿼리 그대로 — 접근레벨·기기·본인인증 조건 유지)
$boards = array();
$sql = " select bo_table, bo_subject
            from {$g5['board_table']}
            where gr_id = '{$gr_id}'
              and bo_list_level <= '{$member['mb_level']}'
              and bo_device <> 'mobile' ";
if(!$is_admin)
    $sql .= " and bo_use_cert = '' ";
$sql .= " order by bo_order ";
$result = sql_query($sql);
while ($row = sql_fetch_array($result)) {
    $boards[] = $row;
}

g5_map_group($boards); // g5pro

include_once('./_tail.php');
