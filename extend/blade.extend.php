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
    echo g5_blade_strip_php(g5_blade()->run($view, array_merge(g5_blade_common(), $data)));
}

// 렌더된 HTML 의 링크를 정리한다 — href·action 속성만 손대므로
// JS 안의 ajax 주소(write_token.php 등)는 그대로 두고 계속 동작한다.
// POST 도 내부 rewrite 라 본문이 보존된다 (리다이렉트가 아니다).
//   1) 게시판 주소를 /board/{게시판}[/{글번호}|/write] 로
//   2) 나머지는 .php 확장자 제거
// 한곳에서 처리하므로 뷰·매핑은 물론 관리자에 등록된 메뉴 링크까지 함께 정리된다.
function g5_blade_strip_php($html)
{
    return preg_replace_callback('/\b(href|action)=(["\'])([^"\']+)\2/i', function ($m) {
        $url = $m[3];
        if (strpos($url, '.php') === false && strpos($url, '/'.G5_BBS_DIR.'/') === false) return $m[0];
        // 외부 사이트는 건드리지 않는다
        if (preg_match('#^[a-z]+://#i', $url) && strpos($url, G5_URL) !== 0) return $m[0];
        // 관리자는 순정 그대로 둔다
        if (strpos($url, '/'.G5_ADMIN_DIR.'/') !== false) return $m[0];

        $new = g5_blade_board_link($url);
        $new = preg_replace('/\.php(?=$|[?#])/', '', $new);
        return $m[1].'='.$m[2].$new.$m[2];
    }, $html);
}

// /bbs/board.php?bo_table=free&wr_id=3  →  /board/free/3
// /bbs/write.php?bo_table=free          →  /board/free/write
function g5_blade_board_link($url)
{
    if (!preg_match('#(/'.preg_quote(G5_BBS_DIR, '#').'/(board|write)(?:\.php)?)\?(.+)$#', $url, $m)) return $url;

    $query = html_entity_decode($m[3], ENT_QUOTES, 'UTF-8');
    parse_str($query, $q);
    if (empty($q['bo_table'])) return $url;

    $bo_table = $q['bo_table'];
    unset($q['bo_table']);

    $path = '/board/'.rawurlencode($bo_table);
    if ($m[2] === 'write') {
        $path .= '/write';
    } else if (!empty($q['wr_id'])) {
        $path .= '/'.rawurlencode($q['wr_id']);
        unset($q['wr_id']);
    }
    unset($q['rewrite']);

    $base = substr($url, 0, strpos($url, $m[1]));
    $rest = $q ? '?'.http_build_query($q, '', '&amp;') : '';
    return $base.$path.$rest;
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

/* ═══════════════════════════════════════════════════════════
   주소 규칙 — 게시판은 /board/{게시판}[/{글번호}|/write]
   루트(/)는 비워 둔다 (순정 짧은주소는 /{게시판} 을 쓰지만 쓰지 않는다)
   ═══════════════════════════════════════════════════════════ */

// 순정 get_pretty_url() 최상단 훅 — 값을 돌려주면 순정 로직을 건너뛴다.
// get_list()·view.php 의 이전/다음글 등 순정이 만드는 링크가 전부 여기를 지난다.
add_replace('get_pretty_url', 'g5_blade_pretty_url', 10, 5);
function g5_blade_pretty_url($url, $folder, $no = '', $query_string = '', $action = '')
{
    if (!in_array($folder, get_board_names())) return $url;   // 게시판만 담당

    $u = G5_URL.'/board/'.$folder;
    if ($no)          $u .= '/'.urlencode($no);
    else if ($action) $u .= '/'.urlencode($action);

    if ($query_string) {
        // 순정과 같은 규칙: 앞이 & 면 ? 로 바꾼다
        $u .= (substr($query_string, 0, 1) === '&')
            ? preg_replace('/&(amp;)?/', '?', $query_string, 1)
            : '?'.$query_string;
    }
    return $u;
}

// 매핑·뷰에서 쓰는 게시판 주소 헬퍼 (short_url_clean 대신)
function g5_board_url($bo_table, $no = '', $query_string = '')
{
    return g5_blade_pretty_url('', $bo_table, $no, $query_string);
}
function g5_board_write_url($bo_table, $query_string = '')
{
    return g5_blade_pretty_url('', $bo_table, '', $query_string, 'write');
}
