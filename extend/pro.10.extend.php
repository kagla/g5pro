<?php
/**
 * g5pro 런타임 — BladeOne 로드, g5_view()/pro_takeover() 정의
 * 설계: docs/superpowers/specs/2026-07-29-g5pro-design.md (저장소에 없음 — 작성자 로컬 문서)
 *
 * ── extend/ 로드 순서 (common.php:836~853) ──
 * 순정 common.php 는 extend/ 안의 *.php 를 natsort(파일명 자연순)로 정렬해
 * 차례로 include_once 한다. 하위 폴더는 훑지 않는다.
 * 이름 안의 숫자가 pro 계열끼리의 순서를 눈에 보이게 고정한 것이다:
 *
 *   debugbar.extend.php · default.config.php · g5_54version_update.extend.php
 *   pro.10.extend.php           ← 이 파일. 런타임. pro 계열 중 첫째
 *   pro.20.map.extend.php       기본 화면 매핑 (bbs·회원)
 *   pro.30.map.shop.extend.php  쇼핑몰 화면 매핑
 *   (그 뒤로 shop.extend·smarteditor·sms5·social_login·version …)
 *
 * 10번이 pro 계열 중 첫째여야 하는 이유는 이 파일에만 **최상위 실행 코드**가
 * 있기 때문이다 — G5_TEMPLATE 결정과 cf_template 컬럼 자동 생성, BladeOne require.
 * 20·30번은 함수 정의뿐이라 서로 순서 의존이 없다(호출 시점에만 실행된다).
 * 번호는 10씩 띄웠으니 사이에 끼울 것이 생기면 15 처럼 넣으면 된다.
 *
 * 앞서 실행되는 순정 확장 셋은 add_event 등록과 무관한 상수 define 뿐이라
 * 이 파일보다 먼저 돌아도 상관없다. 다만 그 셋 중 하나라도 G5_TEMPLATE 이나
 * g5_pro_*() 를 로드 시점에 쓰게 되면 그때는 이 파일이 앞서야 한다 —
 * 그런 날이 오면 이름을 00.pro.extend.php 처럼 숫자로 시작하게 바꾼다.
 * 자연순에서 숫자는 글자보다 앞서므로 그것이 확실한 첫째 자리다.
 *
 * extend/parts/ 는 로더가 건드리지 않는 자리다. 요청 시작 시점에 실행돼선 안 되고
 * 다른 코드가 필요할 때 직접 include 하는 조각(예: pro.shop_items.php 데이터 수집기)을 둔다.
 */
if (!defined('_GNUBOARD_')) exit;

// 활성 템플릿 — g5_config.cf_template 컬럼 (기본 'standard').
// 컬럼이 없으면 자동 생성하므로 순정 install SQL 수정이 필요 없다.
//
// 고르는 곳은 관리자 환경설정 한 곳뿐이다. 예전에는 config.php 에서 G5_TEMPLATE 을
// 미리 define 하면 DB 를 건너뛰게 해 두었는데, 그러면 관리자에서 템플릿을 바꿔도
// 화면이 그대로인데 이유를 알 길이 없었다. 값이 두 곳에 있고 하나가 조용히 이기는 구조라
// 걷어냈다. 아래 상수는 요청 동안 뷰 경로를 찾을 때 쓰는 것이지 설정 자리가 아니다.
if (!isset($config['cf_template'])) {
    sql_query(" ALTER TABLE `{$g5['config_table']}` ADD COLUMN cf_template varchar(100) NOT NULL DEFAULT 'standard' ", false);
    $config['cf_template'] = 'standard';
}
$g5_pro_tpl = trim($config['cf_template']);
if (!$g5_pro_tpl || !is_dir(G5_PATH.'/template/'.$g5_pro_tpl)) $g5_pro_tpl = 'standard';
define('G5_TEMPLATE', $g5_pro_tpl);
unset($g5_pro_tpl);

require_once G5_PATH.'/lib/bladeone/BladeOne.php';

function g5_pro()
{
    static $blade = null;
    if ($blade === null) {
        $views = G5_PATH.'/template/'.G5_TEMPLATE;
        $cache = G5_DATA_PATH.'/cache/pro/'.G5_TEMPLATE;
        if (!is_dir($cache)) {
            @mkdir($cache, G5_DIR_PERMISSION, true);
            @chmod($cache, G5_DIR_PERMISSION);
        }
        $mode = (defined('G5_PRO_DEBUG') && G5_PRO_DEBUG)
            ? \eftec\bladeone\BladeOne::MODE_DEBUG
            : \eftec\bladeone\BladeOne::MODE_AUTO;
        $blade = new \eftec\bladeone\BladeOne($views, $cache, $mode);
    }
    return $blade;
}

// 현재 요청이 blade 로 렌더되는가 — head/tail 가드가 호출
// 변환된 순정 화면이 상단에서 define('G5_PRO_PAGE', true) 로 스스로 선언한다 (직통 방식)
function pro_takeover()
{
    return defined('G5_PRO_PAGE') && G5_PRO_PAGE;
}

// 모든 뷰 공통 데이터 (설계 §7)
function g5_pro_common()
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
            'photo'    => g5_pro_profile_src($member['mb_id']),
        ) : null,
        'menu'   => g5_pro_menu(),
        'areas'  => g5_pro_areas(),
        'cart'   => g5_pro_cart(),
        'title'  => (isset($g5['title']) && $g5['title']) ? $g5['title'] : (isset($config['cf_title']) ? $config['cf_title'] : ''),
        'popups' => g5_pro_popups(),
        // 순정 add_stylesheet()/add_javascript() 큐 — 레이아웃 <head> 에서 그대로 내보낸다
        'page_assets' => g5_pro_page_assets(),
        'template' => array(
            'name'   => G5_TEMPLATE,
            'url'    => G5_URL.'/template/'.G5_TEMPLATE,
            'assets' => G5_URL.'/template/'.G5_TEMPLATE.'/assets',
        ),
    );
}

// 쇼핑몰처럼 head 이후에도 순정 스킨이 직접 echo 하는 화면에서, 그 잔여 출력을 버린다.
// shop.head.php 가드가 버퍼를 열고 g5_view() 가 렌더 직전에 버린다.
function g5_pro_buffer_start()
{
    ob_start();
    $GLOBALS['g5_pro_ob'] = true;
}
function g5_pro_buffer_drop()
{
    if (!empty($GLOBALS['g5_pro_ob'])) {
        ob_end_clean();
        $GLOBALS['g5_pro_ob'] = false;
    }
}

// 게시판 상단·하단 내용(bo_content_head/tail, 포함 파일)을 잡아 뷰로 넘긴다.
// 순정은 스킨 앞뒤로 그대로 흘려보내지만 blade 는 <!DOCTYPE> 보다 먼저 나가면 안 된다.
function g5_pro_capture_start()
{
    ob_start();
}
function g5_pro_capture_end($key)
{
    $GLOBALS['g5_pro_cap_'.$key] = trim(ob_get_clean());
}
function g5_pro_captured($key)
{
    return isset($GLOBALS['g5_pro_cap_'.$key]) ? $GLOBALS['g5_pro_cap_'.$key] : '';
}

// 순정은 add_stylesheet()/add_javascript() 로 모아 둔 것을 tail.sub.php 의 html_end() 가
// <head> 에 끼워 넣는다. blade 화면은 tail.sub 를 타지 않으므로 여기서 직접 꺼내 쓴다.
// (주문서의 카카오 우편번호 postcode.v2.js, 재고체크 shop.order.js 등이 이 큐에 있다)
class g5_pro_assets extends html_process
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

function g5_pro_page_assets()
{
    return class_exists('html_process') ? g5_pro_assets::collect() : '';
}

function g5_view($view, $data = array())
{
    // 한 요청에 화면은 하나. 순정이 두 화면을 잇달아 include 하는 경우
    // (board.php 의 전체목록보이기) 문서가 두 번 나가는 것을 막는다.
    static $rendered = false;
    if ($rendered) return;
    $rendered = true;

    g5_pro_buffer_drop();
    g5_pro_connect();
    echo g5_pro()->run($view, array_merge(g5_pro_common(), $data));
}

// 알림·확인 화면(alert·alert_close·confirm) 전용 — "한 요청에 화면 하나" 규칙에서 뺀다.
// 이 화면들은 순정 alert()/confirm() 안에서 include 되고 곧바로 exit 하는 흐름의 끝이라,
// 앞에서 무엇이 그려졌든 알림 스크립트는 반드시 나가야 한다. g5_view() 를 쓰면 앞 화면이
// 이미 렌더된 요청에서 통째로 삼켜져 알림도 이동도 없이 끝난다.
function g5_view_message($view, $data = array())
{
    g5_pro_buffer_drop();
    echo g5_pro()->run($view, array_merge(g5_pro_common(), $data));
}

// 현재접속자 기록 — 순정은 tail.sub.php 의 html_end()(html_process::run)가 수행하지만
// blade 화면은 tail.sub 를 타지 않으므로 해당 블록을 이식 (lib/common.lib.php:3300)
function g5_pro_connect()
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
function g5_pro_menu()
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
            $sub_on = g5_pro_menu_is_current($sub['link']);
            $menu[$code]['sub'][$i]['on'] = $sub_on;
            if ($sub_on) $section = true;
        }
        $menu[$code]['on'] = g5_pro_menu_is_current($m['link']);
        $menu[$code]['section'] = $section && !$menu[$code]['on'];
    }

    return array_values($menu);
}

// 메뉴 링크가 지금 보고 있는 화면인가.
// 게시판은 글읽기·글쓰기까지 같은 메뉴로 보고(bo_table 일치), 그 밖에는 경로+주요 파라미터로 판정한다.
function g5_pro_menu_is_current($link)
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
function g5_pro_popups()
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
function g5_pro_stats()
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

// ── 템플릿 자산 주소 — 파일 수정시각을 버전으로 붙인다.
// 붙이지 않으면 CSS·JS 를 고쳐도 브라우저가 옛 파일을 계속 쓴다 (응답에 Cache-Control
// 이 없어 브라우저가 알아서 오래 들고 있다). 순정은 head.sub.php 에서 ?ver=G5_CSS_VER
// 로 같은 일을 하는데 템플릿 자산만 그 처리에서 빠져 있었다.
// 파일을 고칠 때만 값이 바뀌므로, 안 고치면 캐시는 그대로 살아 있다.
function g5_pro_asset($file)
{
    static $ver = array();

    $url = G5_URL.'/template/'.G5_TEMPLATE.'/assets/'.$file;
    if (!isset($ver[$file])) {
        $mtime = @filemtime(G5_PATH.'/template/'.G5_TEMPLATE.'/assets/'.$file);
        // 파일이 없으면 버전을 안 붙인다 — 매 요청 값이 달라져 캐시가 죽는 것을 막는다
        $ver[$file] = $mtime ? (string)$mtime : '';
    }
    return $ver[$file] === '' ? $url : $url.'?ver='.$ver[$file];
}

// ── 접속자 요약 — 첫 화면 카드용.
// bbs/current_connect.php 와 같은 조건(최고관리자 제외)으로 세야 카드 숫자와
// 그 페이지의 목록 수가 어긋나지 않는다.
function g5_connect_summary()
{
    global $g5, $config;
    static $s = null;
    if ($s !== null) return $s;

    $admin = sql_escape_string($config['cf_admin']);
    $row = sql_fetch(
        " select count(*) as total, sum(case when mb_id <> '' then 1 else 0 end) as members
            from `{$g5['login_table']}` where mb_id <> '{$admin}' ", false);

    $total   = (int)(isset($row['total']) ? $row['total'] : 0);
    $members = (int)(isset($row['members']) ? $row['members'] : 0);

    $s = array(
        'total'   => $total,
        'members' => $members,
        'guests'  => max(0, $total - $members),
        'href'    => G5_BBS_URL.'/current_connect.php',
    );
    return $s;
}

// ── 투표 위젯 값 — 순정 poll() 은 스킨 파일을 직접 그려버려 Blade 로 넘길 수 없다.
// 값만 뽑아 첫 화면 카드가 쓴다. lib/poll.lib.php 는 건드리지 않는다.
// 설문이 없으면 null 을 돌려주고, 카드는 아무것도 그리지 않는다 (순정 poll() 도 그냥 return).
function g5_poll_widget()
{
    global $g5, $member, $is_member, $is_admin;

    $po = sql_fetch(" select * from `{$g5['poll_table']}` where po_use = 1 order by po_id desc limit 1 ", false);
    if (empty($po['po_id'])) return null;

    // 항목은 po_poll1~9 를 차례로 읽다가 빈 칸에서 끊는다 (순정 규칙)
    $items = array();
    for ($i = 1; $i <= 9; $i++) {
        if (empty($po['po_poll'.$i])) break;
        $items[] = array('no' => $i, 'text' => $po['po_poll'.$i]);
    }
    if (!$items) return null;

    // 이미 투표했나 — 회원은 mb_ids, 비회원은 po_ips.
    // 두 갈래인 이유는 bbs/poll_update.php 가 그렇게 나눠 적기 때문이다.
    // 한쪽만 보면 로그인한 사람이 이미 투표했는데도 항목이 다시 보인다.
    $marks = $is_member
        ? explode(',', (string)$po['mb_ids'])
        : explode(',', (string)$po['po_ips']);
    $mine = $is_member ? $member['mb_id'] : (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '');

    return array(
        'po_id'       => (int)$po['po_id'],
        'subject'     => $po['po_subject'],
        'items'       => $items,
        'voted'       => ($mine !== '' && in_array($mine, $marks, true)),
        'can_vote'    => (int)$member['mb_level'] >= (int)$po['po_level'],
        'level'       => (int)$po['po_level'],
        'result_href' => G5_BBS_URL.'/poll_result.php?po_id='.(int)$po['po_id'],
        'admin_href'  => ($is_admin === 'super') ? G5_ADMIN_URL.'/poll_form.php?w=u&po_id='.(int)$po['po_id'] : '',
    );
}

// ── 인기검색어 — 순정 popular() 도 스킨을 직접 그린다. 여기서는 값만 뽑는다.
// 기본값(최근 3일·7개)과 날짜 계산은 lib/popular.lib.php 와 같게 맞춘다.
function g5_popular_words($pop_cnt = 7, $date_cnt = 3)
{
    global $g5;

    $from = date('Y-m-d', G5_SERVER_TIME - ((int)$date_cnt * 86400));
    $sql = " select pp_word, count(*) as cnt from `{$g5['popular_table']}`
              where pp_date between '{$from}' and '".G5_TIME_YMD."'
              group by pp_word order by cnt desc, pp_word limit 0, ".(int)$pop_cnt;

    $words = array();
    $result = sql_query($sql, false);
    while ($row = sql_fetch_array($result)) {
        $words[] = array(
            'word' => $row['pp_word'],   // 사용자가 넣은 값 — 뷰에서 {{ }} 로 이스케이프
            'href' => G5_BBS_URL.'/search.php?sfl=wr_subject%7C%7Cwr_content&sop=and&stx='.urlencode($row['pp_word']),
        );
    }
    return $words;
}

// 날짜가 "비었는가" — 세 가지 표현을 모두 같게 본다.
//   NULL           마이그레이션 이후의 정식 표현
//   ''             폼에서 넘어온 빈 값
//   '0000-00-00…'  마이그레이션 이전의 옛 표현
// 셋을 다 받는 이유: 순정 파일을 upstream 병합으로 되돌려 받아도 판정이 흔들리지 않고,
// 마이그레이션이 덜 끝난 다른 DB 에 이 코드를 얹어도 그대로 동작한다.
function pro_empty_date($v)
{
    return $v === null || $v === '' || strncmp((string)$v, '0000-00-00', 10) === 0;
}

// 날짜 컬럼이 NULL 을 받는지, date 인지 datetime 인지 — 한 번 보고 기억한다.
// 마이그레이션 여부를 설정값으로 두지 않고 스키마에 직접 물어본다. 설정은 사람이
// 안 바꾸면 틀리지만 스키마는 언제나 사실이고, 표가 섞여 있어도 표마다 맞게 답한다.
function pro_date_meta($table, $column)
{
    static $cache = array();
    $key = $table.'.'.$column;

    if (!isset($cache[$key])) {
        $row = sql_fetch(" select is_nullable, data_type
                             from information_schema.columns
                            where table_schema = database()
                              and table_name = '".sql_escape_string($table)."'
                              and column_name = '".sql_escape_string($column)."' ", false);
        $cache[$key] = array(
            // 컬럼을 못 찾으면(권한·오타) NULL 을 넣어 죽이지 않고 옛 표현으로 물러선다
            'nullable' => isset($row['is_nullable']) && $row['is_nullable'] === 'YES',
            'zero'     => (isset($row['data_type']) && $row['data_type'] === 'date')
                          ? '0000-00-00' : '0000-00-00 00:00:00',
        );
    }

    return $cache[$key];
}

// 날짜를 SQL 리터럴로 — 순정이 '$var' 로 박아 넣던 자리를 대신한다.
//
// 빈 값일 때 무엇을 넣을지는 컬럼이 정한다.
//   NULL 을 받는 컬럼  → NULL      (마이그레이션된 DB)
//   NOT NULL 인 컬럼   → 제로데이트 (아직 옮기지 않은 DB — NULL 을 넣으면 1048 로 죽는다)
// 덕분에 이 코드는 옛 DB 위에서도 그대로 돌아간다. 표를 옮기면 그 표부터 NULL 로 바뀐다.
//
// 어느 쪽이든 빈 문자열은 절대 넣지 않는다. strict 모드에서 '' 를 date/datetime 에
// 넣으면 nullable 여부와 무관하게 1292 로 죽는다 — 순정에 남아 있던 함정이다.
function pro_sql_date($v, $table = '', $column = '')
{
    if (!pro_empty_date($v)) return "'".sql_escape_string((string)$v)."'";
    if ($table === '') return 'NULL';

    $meta = pro_date_meta($table, $column);
    return $meta['nullable'] ? 'NULL' : "'".$meta['zero']."'";
}

// 화면에 쓰는 날짜 형식 — 순정 'YYYY-MM-DD HH:II:SS' 를 'YY-MM-DD HH:II' 로 줄인다.
// 목록의 순정 datetime2('YY-MM-DD')와 같은 눈금이라 화면 전체가 한 형식으로 읽힌다.
// 값이 비었거나(0000-00-00) 형식이 다르면 그대로 돌려준다.
function g5_pro_dt($s, $with_time = true)
{
    $s = (string)$s;
    if (strlen($s) < 10 || $s[0] === '0') return $s;
    return substr($s, 2, 8).($with_time && strlen($s) >= 16 ? ' '.substr($s, 11, 5) : '');
}

// 회원 프로필 이미지 URL — 순정 get_member_profile_img() 는 <img> 태그를 돌려주므로 src 만 뽑는다
function g5_pro_profile_src($mb_id)
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
function g5_pro_cart()
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

function g5_pro_areas()
{
    if (!defined('G5_USE_SHOP') || !G5_USE_SHOP) return array();

    $script  = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $in_shop = (strpos($script, '/'.G5_SHOP_DIR.'/') !== false);

    return array(
        array('name' => '커뮤니티', 'href' => G5_URL.'/',      'icon' => 'home', 'active' => !$in_shop),
        array('name' => '쇼핑몰',   'href' => G5_SHOP_URL.'/', 'icon' => 'bag',  'active' => $in_shop),
    );
}

// ── 추천·비추천 취소와 갈아타기
// 순정 bbs/good.php 는 한 번 누르면 되돌릴 수 없다 — 이미 눌렀으면 막고 끝난다.
// good.php 첫 줄의 run_event('bbs_good_before') 를 잡아, 이미 누른 기록이 있을 때만
// 우리가 처리하고 끝낸다. 처음 누르는 경우는 손대지 않고 순정에게 넘긴다.
// 순정 파일은 한 줄도 고치지 않는다.
add_event('bbs_good_before', 'g5_pro_good_toggle', 10, 3);

function g5_pro_good_toggle($bo_table, $wr_id, $good)
{
    global $g5, $member, $is_member, $board;

    // 화면이 쓰는 길(js=on)만 맡는다. 그 밖은 순정 그대로 둔다.
    if (!isset($_POST['js']) || $_POST['js'] !== 'on') return;
    if ($good !== 'good' && $good !== 'nogood') return;
    if (!$is_member || empty($bo_table) || empty($wr_id)) return;

    // 순정과 같은 자리 확인 — 그 글을 실제로 열어 본 사람만
    if (!get_session('ss_view_'.$bo_table.'_'.$wr_id)) return;

    $bt  = sql_escape_string($bo_table);
    $wid = (int)$wr_id;
    $mb  = sql_escape_string($member['mb_id']);

    $row = sql_fetch(" select bg_flag from `{$g5['board_good_table']}`
                        where bo_table = '{$bt}' and wr_id = '{$wid}' and mb_id = '{$mb}'
                          and bg_flag in ('good','nogood') ", false);
    $had = isset($row['bg_flag']) ? $row['bg_flag'] : '';
    if ($had === '') return;   // 처음 누르는 경우 — 순정이 맡는다

    // 게시판에서 꺼 둔 기능으로는 갈아탈 수 없다
    if ($good === 'good'   && empty($board['bo_use_good']))   g5_pro_good_json('이 게시판은 추천 기능을 사용하지 않습니다.');
    if ($good === 'nogood' && empty($board['bo_use_nogood'])) g5_pro_good_json('이 게시판은 비추천 기능을 사용하지 않습니다.');

    $write_table = $g5['write_prefix'].$bt;

    if ($had === $good) {
        // 같은 것을 다시 눌렀다 — 취소.
        // 조건을 건 DELETE 로 지운 뒤 그 결과를 보고서야 셈을 줄인다.
        // 동시에 두 번 눌러도 한 번만 줄어든다.
        sql_query(" delete from `{$g5['board_good_table']}`
                     where bo_table = '{$bt}' and wr_id = '{$wid}' and mb_id = '{$mb}'
                       and bg_flag = '{$had}' ");
        if (get_sql_affected_rows() > 0)
            sql_query(" update `{$write_table}` set wr_{$had} = greatest(cast(wr_{$had} as signed) - 1, 0) where wr_id = '{$wid}' ");
        $mine = '';
    } else {
        // 다른 것을 눌렀다 — 갈아타기. 기존 것을 내리고 새 것을 올린다.
        sql_query(" update `{$g5['board_good_table']}`
                       set bg_flag = '{$good}', bg_datetime = '".G5_TIME_YMDHIS."'
                     where bo_table = '{$bt}' and wr_id = '{$wid}' and mb_id = '{$mb}'
                       and bg_flag = '{$had}' ");
        if (get_sql_affected_rows() > 0) {
            sql_query(" update `{$write_table}`
                           set wr_{$had}  = greatest(cast(wr_{$had} as signed) - 1, 0),
                               wr_{$good} = wr_{$good} + 1
                         where wr_id = '{$wid}' ");
        }
        $mine = $good;
    }

    $now = sql_fetch(" select wr_good, wr_nogood from `{$write_table}` where wr_id = '{$wid}' ", false);
    g5_pro_good_json('', array(
        'count'  => (int)(isset($now['wr_'.$good]) ? $now['wr_'.$good] : 0),
        'good'   => (int)(isset($now['wr_good']) ? $now['wr_good'] : 0),
        'nogood' => (int)(isset($now['wr_nogood']) ? $now['wr_nogood'] : 0),
        'mine'   => $mine,
    ));
}

// 순정 good.php 가 쓰는 응답 모양({error, count})을 지키고 필요한 것만 덧붙인다.
// 옛 키를 그대로 두므로 이 규약을 읽는 다른 코드가 있어도 깨지지 않는다.
function g5_pro_good_json($error, $extra = array())
{
    $out = array_merge(array('error' => $error, 'count' => ''), $extra);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}
