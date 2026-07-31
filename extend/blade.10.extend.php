<?php
/**
 * g5blade 런타임 — BladeOne 로드, g5_view()/blade_takeover() 정의
 * 설계: docs/superpowers/specs/2026-07-29-g5blade-design.md
 *
 * ── extend/ 로드 순서 (common.php:836~853) ──
 * 순정 common.php 는 extend/ 안의 *.php 를 natsort(파일명 자연순)로 정렬해
 * 차례로 include_once 한다. 하위 폴더는 훑지 않는다.
 * 파일명 앞의 숫자가 그 순서를 눈에 보이게 고정한 것이다:
 *
 *   blade.10.extend.php           ← 이 파일. 런타임. 반드시 첫째
 *   blade.20.map.extend.php       기본 화면 매핑 (bbs·회원)
 *   blade.30.map.shop.extend.php  쇼핑몰 화면 매핑
 *   (그 뒤로 순정 확장들: debugbar·default.config·shop.extend·social_login …)
 *
 * 10번이 첫째여야 하는 이유는 이 파일에만 **최상위 실행 코드**가 있기 때문이다 —
 * G5_TEMPLATE 결정과 cf_template 컬럼 자동 생성, BladeOne require.
 * 20·30번은 함수 정의뿐이라 서로 순서 의존이 없다(호출 시점에만 실행된다).
 * 번호는 10씩 띄웠으니 사이에 끼울 것이 생기면 15 처럼 넣으면 된다.
 *
 * 주의: 숫자 없는 파일은 자연순에서 숫자 뒤로 가므로(blade.map… 이 blade.10… 뒤),
 * blade 계열에 최상위 실행 코드를 새로 넣을 때는 반드시 번호를 붙인다.
 *
 * extend/parts/ 는 로더가 건드리지 않는 자리다. 요청 시작 시점에 실행돼선 안 되고
 * 다른 코드가 필요할 때 직접 include 하는 조각(예: blade.shop_items.php 데이터 수집기)을 둔다.
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
        'cart'   => g5_blade_cart(),
        'title'  => (isset($g5['title']) && $g5['title']) ? $g5['title'] : (isset($config['cf_title']) ? $config['cf_title'] : ''),
        'popups' => g5_blade_popups(),
        // 순정 add_stylesheet()/add_javascript() 큐 — 레이아웃 <head> 에서 그대로 내보낸다
        'page_assets' => g5_blade_page_assets(),
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

// 게시판 상단·하단 내용(bo_content_head/tail, 포함 파일)을 잡아 뷰로 넘긴다.
// 순정은 스킨 앞뒤로 그대로 흘려보내지만 blade 는 <!DOCTYPE> 보다 먼저 나가면 안 된다.
function g5_blade_capture_start()
{
    ob_start();
}
function g5_blade_capture_end($key)
{
    $GLOBALS['g5_blade_cap_'.$key] = trim(ob_get_clean());
}
function g5_blade_captured($key)
{
    return isset($GLOBALS['g5_blade_cap_'.$key]) ? $GLOBALS['g5_blade_cap_'.$key] : '';
}

// 순정은 add_stylesheet()/add_javascript() 로 모아 둔 것을 tail.sub.php 의 html_end() 가
// <head> 에 끼워 넣는다. blade 화면은 tail.sub 를 타지 않으므로 여기서 직접 꺼내 쓴다.
// (주문서의 카카오 우편번호 postcode.v2.js, 재고체크 shop.order.js 등이 이 큐에 있다)
class g5_blade_assets extends html_process
{
    public static function collect()
    {
        $out = array_merge(self::$css, self::$js);
        usort($out, function ($a, $b) {
            if ($a[0] == $b[0]) return 0;
            return ($a[0] < $b[0]) ? -1 : 1;    // order 가 작을수록 먼저
        });
        $html = '';
        foreach ($out as $row) {
            // 순정 스킨/테마의 스타일시트는 뺀다 — 우리 템플릿이 그 자리를 대신하므로
            // 같이 실으면 배경·레이아웃이 서로 싸운다. (스크립트는 동작이라 모두 싣는다)
            if (stripos($row[1], '<link') !== false
                && preg_match('#/(skin|theme)/#i', $row[1])) continue;
            $html .= $row[1]."\n";
        }
        return $html;
    }
}

function g5_blade_page_assets()
{
    return class_exists('html_process') ? g5_blade_assets::collect() : '';
}

function g5_view($view, $data = array())
{
    // 한 요청에 화면은 하나. 순정이 두 화면을 잇달아 include 하는 경우
    // (board.php 의 전체목록보이기) 문서가 두 번 나가는 것을 막는다.
    static $rendered = false;
    if ($rendered) return;
    $rendered = true;

    g5_blade_buffer_drop();
    g5_blade_connect();
    echo g5_blade()->run($view, array_merge(g5_blade_common(), $data));
}

// 알림·확인 화면(alert·alert_close·confirm) 전용 — "한 요청에 화면 하나" 규칙에서 뺀다.
// 이 화면들은 순정 alert()/confirm() 안에서 include 되고 곧바로 exit 하는 흐름의 끝이라,
// 앞에서 무엇이 그려졌든 알림 스크립트는 반드시 나가야 한다. g5_view() 를 쓰면 앞 화면이
// 이미 렌더된 요청에서 통째로 삼켜져 알림도 이동도 없이 끝난다.
function g5_view_message($view, $data = array())
{
    g5_blade_buffer_drop();
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

// 화면에 쓰는 날짜 형식 — 순정 'YYYY-MM-DD HH:II:SS' 를 'YY-MM-DD HH:II' 로 줄인다.
// 목록의 순정 datetime2('YY-MM-DD')와 같은 눈금이라 화면 전체가 한 형식으로 읽힌다.
// 값이 비었거나(0000-00-00) 형식이 다르면 그대로 돌려준다.
function g5_blade_dt($s, $with_time = true)
{
    $s = (string)$s;
    if (strlen($s) < 10 || $s[0] === '0') return $s;
    return substr($s, 2, 8).($with_time && strlen($s) >= 16 ? ' '.substr($s, 11, 5) : '');
}

// 회원 프로필 이미지 URL — 순정 get_member_profile_img() 는 <img> 태그를 돌려주므로 src 만 뽑는다
function g5_blade_profile_src($mb_id)
{
    if (!function_exists('get_member_profile_img')) return '';
    $html = get_member_profile_img($mb_id);
    return preg_match('/src="([^"]*)"/i', $html, $m) ? $m[1] : '';
}

// 커뮤니티 ↔ 쇼핑몰 전환 — 쇼핑몰이 설치된 경우에만.
// 두 영역을 모두 돌려주고 현재 위치를 active 로 표시한다 (헤더 세그먼트 토글).
// 하나만 보여주면 "갈 곳" 이름이 현재 위치처럼 읽히는 혼동이 있었다.
// 헤더 장바구니 — 쇼핑몰이 설치된 경우에만. 비회원도 세션 장바구니를 쓰므로 로그인과 무관하다.
// 개수는 cart.php 가 목록을 묶는 기준(상품 종류)과 같게 센다 — 옵션 줄 수가 아니다.
function g5_blade_cart()
{
    global $g5;
    if (!defined('G5_USE_SHOP') || !G5_USE_SHOP) return null;

    $cart_id = function_exists('get_session') ? get_session('ss_cart_id') : '';
    $cnt = 0;
    if ($cart_id) {
        $row = sql_fetch(" select count(distinct it_id) as cnt from `{$g5['g5_shop_cart_table']}`
                            where od_id = '".sql_escape_string($cart_id)."' ", false);
        $cnt = (int)(isset($row['cnt']) ? $row['cnt'] : 0);
    }
    return array('count' => $cnt, 'href' => G5_SHOP_URL.'/cart.php');
}

function g5_blade_areas()
{
    if (!defined('G5_USE_SHOP') || !G5_USE_SHOP) return array();

    $script  = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $in_shop = (strpos($script, '/'.G5_SHOP_DIR.'/') !== false);

    return array(
        array('name' => '커뮤니티', 'href' => G5_URL.'/',      'icon' => 'home', 'active' => !$in_shop),
        array('name' => '쇼핑몰',   'href' => G5_SHOP_URL.'/', 'icon' => 'bag',  'active' => $in_shop),
    );
}
