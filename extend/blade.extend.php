<?php
/**
 * g5blade 런타임 — BladeOne 로드, g5_view()/blade_takeover() 정의
 * 설계: docs/superpowers/specs/2026-07-29-g5blade-design.md
 */
if (!defined('_GNUBOARD_')) exit;

// 활성 템플릿 선택 — g5_config.cf_template 컬럼 (기본 'one')
// 컬럼이 없으면 자동 생성하므로 순정 install SQL 수정이 필요 없다.
// config.php 에 G5_TEMPLATE 을 define 하면 그것이 최우선 (개발용 강제 오버라이드).
if (!defined('G5_TEMPLATE')) {
    if (!isset($config['cf_template'])) {
        sql_query(" ALTER TABLE `{$g5['config_table']}` ADD COLUMN cf_template varchar(100) NOT NULL DEFAULT 'one' ", false);
        $config['cf_template'] = 'one';
    }
    $g5_blade_tpl = trim($config['cf_template']);
    if (!$g5_blade_tpl || !is_dir(G5_PATH.'/template/'.$g5_blade_tpl)) $g5_blade_tpl = 'one';
    define('G5_TEMPLATE', $g5_blade_tpl);
    unset($g5_blade_tpl);
}

require_once G5_PATH.'/lib/bladeone/BladeOne.php';

function g5_blade()
{
    static $blade = null;
    if ($blade === null) {
        $views = G5_PATH.'/template/'.G5_TEMPLATE;
        $cache = G5_DATA_PATH.'/cache/blade/'.G5_TEMPLATE;
        if (!is_dir($cache)) {
            @mkdir($cache, G5_DIR_PERMISSION, true);
            @chmod($cache, G5_DIR_PERMISSION);
        }
        $mode = (defined('G5_BLADE_DEBUG') && G5_BLADE_DEBUG)
            ? \eftec\bladeone\BladeOne::MODE_DEBUG
            : \eftec\bladeone\BladeOne::MODE_AUTO;
        $blade = new \eftec\bladeone\BladeOne($views, $cache, $mode);
    }
    return $blade;
}

// 현재 요청이 blade 로 렌더되는가 — head/tail 가드가 호출
function blade_takeover()
{
    global $board, $config;

    // 스킨 시스템이 없는 화면 (index.php 등)
    if (defined('G5_BLADE_PAGE') && G5_BLADE_PAGE) return true;

    // 게시판: 해당 보드의 스킨이 blade 또는 blade_* 파생 (blade_gallery 등)
    if (!empty($board['bo_skin']) && strpos($board['bo_skin'], 'blade') === 0) return true;

    // 회원 영역: 스킨이 blade 이고 "변환된" 화면일 때만 (미변환 화면은 순정 head/tail 유지)
    if (!empty($config['cf_member_skin']) && $config['cf_member_skin'] === 'blade') {
        $converted = array('/bbs/login.php');
        $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
        foreach ($converted as $page) {
            if (substr($script, -strlen($page)) === $page) return true;
        }
    }

    return false;
}

// 모든 뷰 공통 데이터 (설계 §7)
function g5_blade_common()
{
    global $config, $member, $g5;

    return array(
        'site' => array(
            'title'    => isset($config['cf_title']) ? $config['cf_title'] : '',
            'add_meta' => isset($config['cf_add_meta']) ? $config['cf_add_meta'] : '',
        ),
        'me' => (isset($member['mb_id']) && $member['mb_id']) ? array(
            'mb_id'    => $member['mb_id'],
            'mb_nick'  => $member['mb_nick'],
            'mb_name'  => $member['mb_name'],
            'mb_level' => $member['mb_level'],
            'mb_point' => $member['mb_point'],
        ) : null,
        'menu'   => g5_blade_menu(),
        'title'  => (isset($g5['title']) && $g5['title']) ? $g5['title'] : (isset($config['cf_title']) ? $config['cf_title'] : ''),
        'popups' => g5_blade_popups(),
        'template' => array(
            'name'   => G5_TEMPLATE,
            'url'    => G5_URL.'/template/'.G5_TEMPLATE,
            'assets' => G5_URL.'/template/'.G5_TEMPLATE.'/assets',
        ),
    );
}

function g5_view($view, $data = array())
{
    g5_blade_connect();
    echo g5_blade()->run($view, array_merge(g5_blade_common(), $data));
}

// 현재접속자 기록 — 순정은 tail.sub.php 의 html_end()(html_process::run)가 수행하지만
// blade 화면은 tail.sub 를 타지 않으므로 해당 블록을 이식 (lib/common.lib.php:3300)
function g5_blade_connect()
{
    global $config, $g5, $member;
    static $done = false;
    if ($done) return;
    $done = true;

    $tmp_row = sql_fetch(" select count(*) as cnt from {$g5['login_table']} where lo_ip = '{$_SERVER['REMOTE_ADDR']}' ");
    $mb_id = isset($member['mb_id']) ? $member['mb_id'] : '';

    if (!empty($tmp_row['cnt'])) {
        sql_query(" update {$g5['login_table']} set mb_id = '{$mb_id}', lo_datetime = '".G5_TIME_YMDHIS."', lo_location = '{$g5['lo_location']}', lo_url = '{$g5['lo_url']}' where lo_ip = '{$_SERVER['REMOTE_ADDR']}' ", false);
    } else {
        sql_query(" insert into {$g5['login_table']} ( lo_ip, mb_id, lo_datetime, lo_location, lo_url ) values ( '{$_SERVER['REMOTE_ADDR']}', '{$mb_id}', '".G5_TIME_YMDHIS."', '{$g5['lo_location']}', '{$g5['lo_url']}' ) ", false);
        sql_query(" delete from {$g5['login_table']} where lo_datetime < '".date("Y-m-d H:i:s", G5_SERVER_TIME - (60 * $config['cf_login_minutes']))."' ", false);
    }
}

// GNB 메뉴 트리 (me_code 2자리=1단, 4자리=2단)
function g5_blade_menu()
{
    global $g5;
    $menu = array();
    $result = sql_query(" select me_code, me_name, me_link, me_target
                            from `{$g5['menu_table']}`
                           where me_use = '1'
                           order by me_order, me_id ", false);
    while ($result && ($row = sql_fetch_array($result))) {
        $len = strlen($row['me_code']);
        if ($len == 2) {
            $menu[$row['me_code']] = array(
                'name' => $row['me_name'], 'link' => $row['me_link'],
                'target' => $row['me_target'], 'sub' => array(),
            );
        } else if ($len == 4) {
            $parent = substr($row['me_code'], 0, 2);
            if (isset($menu[$parent])) {
                $menu[$parent]['sub'][] = array(
                    'name' => $row['me_name'], 'link' => $row['me_link'], 'target' => $row['me_target'],
                );
            }
        }
    }
    return array_values($menu);
}

// 레이어팝업 (head 스킵으로 누락되는 newwin.inc.php 이식)
function g5_blade_popups()
{
    global $g5;
    if (defined('G5_IS_ADMIN')) return array();

    $now = G5_TIME_YMDHIS;
    $popups = array();
    $result = sql_query(" select * from `{$g5['new_win_table']}`
                           where nw_begin_time <= '{$now}' and nw_end_time >= '{$now}'
                             and nw_device in ('both', 'pc')
                             and nw_division in ('both', 'community') ", false);
    while ($result && ($row = sql_fetch_array($result))) {
        if (isset($_COOKIE['hd_pops_'.$row['nw_id']])) continue;
        $popups[] = array(
            'id'            => $row['nw_id'],
            'left'          => $row['nw_left'],
            'top'           => $row['nw_top'],
            'width'         => $row['nw_width'],
            'height'        => $row['nw_height'],
            'subject'       => $row['nw_subject'],
            'content'       => $row['nw_content'],
            'content_html'  => $row['nw_content_html'],
            'disable_hours' => $row['nw_disable_hours'],
        );
    }
    return $popups;
}

// 최신글 데이터 (뷰 partial 용 — 순정 latest() 는 스킨 include 라 blade 에서 못 씀)
function g5_latest_rows($bo_table, $rows = 6, $subject_len = 40)
{
    global $g5;
    $board = sql_fetch(" select * from `{$g5['board_table']}` where bo_table = '".sql_escape_string($bo_table)."' ");
    if (!$board) return array('board' => null, 'items' => array());

    $write_table = $g5['write_prefix'].$board['bo_table'];
    $items = array();
    $result = sql_query(" select * from `{$write_table}` where wr_is_comment = 0 order by wr_num limit 0, ".(int)$rows, false);
    while ($result && ($row = sql_fetch_array($result))) {
        $items[] = get_list($row, $board, '', $subject_len);
    }
    return array(
        'board' => array('bo_table' => $board['bo_table'], 'bo_subject' => $board['bo_subject']),
        'items' => $items,
    );
}
