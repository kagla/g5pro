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
// 변환된 순정 화면이 상단에서 define('G5_BLADE_PAGE', true) 로 스스로 선언한다 (직통 방식)
function blade_takeover()
{
    return defined('G5_BLADE_PAGE') && G5_BLADE_PAGE;
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
            'mb_point' => (int)$member['mb_point'],
            'memo_cnt' => (int)(isset($member['mb_memo_cnt']) ? $member['mb_memo_cnt'] : 0),  // 안 읽은 쪽지
            'photo'    => g5_blade_profile_src($member['mb_id']),
        ) : null,
        'menu'   => g5_blade_menu(),
        'areas'  => g5_blade_areas(),
        'title'  => (isset($g5['title']) && $g5['title']) ? $g5['title'] : (isset($config['cf_title']) ? $config['cf_title'] : ''),
        'popups' => g5_blade_popups(),
        'template' => array(
            'name'   => G5_TEMPLATE,
            'url'    => G5_URL.'/template/'.G5_TEMPLATE,
            'assets' => G5_URL.'/template/'.G5_TEMPLATE.'/assets',
        ),
    );
}

// 쇼핑몰처럼 head 이후에도 순정 스킨이 직접 echo 하는 화면에서, 그 잔여 출력을 버린다.
// shop.head.php 가드가 버퍼를 열고 g5_view() 가 렌더 직전에 버린다.
function g5_blade_buffer_start()
{
    ob_start();
    $GLOBALS['g5_blade_ob'] = true;
}
function g5_blade_buffer_drop()
{
    if (!empty($GLOBALS['g5_blade_ob'])) {
        ob_end_clean();
        $GLOBALS['g5_blade_ob'] = false;
    }
}

function g5_view($view, $data = array())
{
    g5_blade_buffer_drop();
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
    if (!isset($g5['lo_location'])) $g5['lo_location'] = '';
    if (!isset($g5['lo_url']))      $g5['lo_url'] = '';

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

    // 현재 위치 표시 — 두 단계로 구분한다.
    //   on      : 이 메뉴가 정확히 지금 화면 (진하게)
    //   section : 하위 중 하나가 지금 화면 (은은하게 — 문맥 표시)
    foreach ($menu as $code => $m) {
        $section = false;
        foreach ($m['sub'] as $i => $sub) {
            $sub_on = g5_blade_menu_is_current($sub['link']);
            $menu[$code]['sub'][$i]['on'] = $sub_on;
            if ($sub_on) $section = true;
        }
        $menu[$code]['on'] = g5_blade_menu_is_current($m['link']);
        $menu[$code]['section'] = $section && !$menu[$code]['on'];
    }

    return array_values($menu);
}

// 메뉴 링크가 지금 보고 있는 화면인가.
// 게시판은 글읽기·글쓰기까지 같은 메뉴로 보고(bo_table 일치), 그 밖에는 경로+주요 파라미터로 판정한다.
function g5_blade_menu_is_current($link)
{
    if (!$link) return false;

    $parts = parse_url(html_entity_decode($link, ENT_QUOTES, 'UTF-8'));
    if (!isset($parts['path'])) return false;

    // 다른 도메인 링크는 대상 아님
    if (isset($parts['host'])) {
        $here = parse_url(G5_URL);
        if (isset($here['host']) && strcasecmp($parts['host'], $here['host']) !== 0) return false;
    }

    $q = array();
    if (isset($parts['query'])) parse_str($parts['query'], $q);

    // 게시판: bo_table 만 같으면 목록·읽기·쓰기 모두 해당 메뉴로 본다
    if (!empty($q['bo_table'])) {
        return isset($_REQUEST['bo_table']) && $_REQUEST['bo_table'] === $q['bo_table'];
    }
    // 내용·그룹 등 식별자 기반
    foreach (array('co_id', 'gr_id', 'ca_id', 'it_id') as $key) {
        if (!empty($q[$key])) {
            return isset($_REQUEST[$key]) && $_REQUEST[$key] === $q[$key];
        }
    }

    // 그 밖에는 경로 일치 (쿼리 없는 링크)
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    return rtrim($parts['path'], '/') === rtrim($script, '/');
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

// 메인 히어로용 사이트 통계 (가벼운 집계 3건)
function g5_blade_stats()
{
    global $g5;
    static $s = null;
    if ($s !== null) return $s;

    $mb = sql_fetch(" select count(*) as cnt from `{$g5['member_table']}` where mb_level > 1 ", false);
    $lo = sql_fetch(" select count(*) as cnt from `{$g5['login_table']}` ", false);
    $wr = sql_fetch(" select sum(bo_count_write) as cnt from `{$g5['board_table']}` ", false);

    $s = array(
        'members' => (int)(isset($mb['cnt']) ? $mb['cnt'] : 0),
        'online'  => (int)(isset($lo['cnt']) ? $lo['cnt'] : 0),
        'posts'   => (int)(isset($wr['cnt']) ? $wr['cnt'] : 0),
    );
    return $s;
}

// 회원 프로필 이미지 URL — 순정 get_member_profile_img() 는 <img> 태그를 돌려주므로 src 만 뽑는다
function g5_blade_profile_src($mb_id)
{
    if (!function_exists('get_member_profile_img')) return '';
    $html = get_member_profile_img($mb_id);
    return preg_match('/src="([^"]*)"/i', $html, $m) ? $m[1] : '';
}

// 커뮤니티 ↔ 쇼핑몰 이동 링크 — 쇼핑몰이 설치된 경우에만.
// 현재 위치는 빼고 "갈 곳" 하나만 돌려준다 (헤더 폭을 아끼고, 할 일이 분명해진다).
function g5_blade_areas()
{
    if (!defined('G5_USE_SHOP') || !G5_USE_SHOP) return array();

    $script  = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $in_shop = (strpos($script, '/'.G5_SHOP_DIR.'/') !== false);

    return $in_shop
        ? array(array('name' => '커뮤니티', 'href' => G5_URL.'/',      'icon' => 'home'))
        : array(array('name' => '쇼핑몰',   'href' => G5_SHOP_URL.'/', 'icon' => 'bag'));
}
