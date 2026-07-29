<?php
/**
 * g5blade 화면 매핑 모음 — 변환된 순정 화면이 스킨 include 자리에서 g5_map_*() 를 호출한다.
 * 한 화면 = 한 함수. 순정 전역변수를 뷰용 배열로 정리해 g5_view() 를 호출하는 것이 전부다.
 * (런타임·공통 데이터는 blade.extend.php)
 */
if (!defined('_GNUBOARD_')) exit;

// bo_skin → 목록 뷰 조회표 (게시판마다 목록 모양을 고른다 · 설계 §5)
// 등록되지 않은 값이면 기본 표 목록으로 폴백한다.
function g5_blade_list_views()
{
    return array(
        'blade'         => array('view' => 'bbs.board_list',         'thumb' => false),
        'blade_simple'  => array('view' => 'bbs.board_list_simple',  'thumb' => false),
        'blade_card'    => array('view' => 'bbs.board_list_card',    'thumb' => true),
        'blade_gallery' => array('view' => 'bbs.board_list_gallery', 'thumb' => true),
    );
}

// ── 게시판 목록 (bbs/list.php)
function g5_map_board_list()
{
    global $list, $board, $bo_table, $is_category, $sca, $sfl, $stx;
    global $total_count, $page, $total_page, $write_href, $rss_href, $admin_href, $is_checkbox;

    $views = g5_blade_list_views();
    $skin  = isset($board['bo_skin']) ? $board['bo_skin'] : '';
    $variant = isset($views[$skin]) ? $views[$skin] : $views['blade'];
    if ($variant['thumb']) include_once(G5_LIB_PATH.'/thumbnail.lib.php');

    $items = array();
    foreach ($list as $row) {
        $items[] = array(
            'wr_id'       => $row['wr_id'],
            'num'         => isset($row['num']) ? $row['num'] : '',
            'href'        => $row['href'],
            'subject'     => $row['subject'],              // get_text 완료 HTML-safe → {!! !!}
            'name'        => $row['name'],                 // 사이드뷰 HTML → {!! !!}
            'datetime'    => $row['datetime2'],
            'hit'         => $row['wr_hit'],
            'comment_cnt' => (int)$row['wr_comment'],
            'is_notice'   => !empty($row['is_notice']),
            'icon_new'    => !empty($row['icon_new']),
            'icon_file'   => !empty($row['icon_file']),
            'icon_secret' => !empty($row['icon_secret']),
            // 썸네일 변형(카드·갤러리)에서만 조회
            'thumb'       => $variant['thumb']
                ? get_list_thumbnail($bo_table, $row['wr_id'],
                      ($board['bo_gallery_width'] ?: 300), ($board['bo_gallery_height'] ?: 225), false, true)
                : null,   // ['src'=>URL, 'alt'] — src 비면 이미지 없음
        );
    }

    $categories = array();
    if ($is_category && $board['bo_category_list']) {
        foreach (explode('|', (string)$board['bo_category_list']) as $c) {
            $categories[] = array(
                'name'   => $c,
                'href'   => short_url_clean(G5_BBS_URL.'/board.php?bo_table='.$bo_table.'&sca='.urlencode($c)),
                'active' => ($sca === $c),
            );
        }
    }

    g5_view($variant['view'], array(
        'board' => array(
            'bo_table'   => $bo_table,
            'bo_subject' => $board['bo_subject'],
        ),
        'items'       => $items,
        'categories'  => $categories,
        'total_count' => (int)$total_count,
        'page'        => (int)$page,
        'total_page'  => (int)$total_page,
        'page_href'   => short_url_clean(G5_BBS_URL.'/board.php?bo_table='.$bo_table
                         .'&sca='.urlencode($sca).'&sfl='.urlencode($sfl).'&stx='.urlencode($stx).'&page='),
        'write_href'  => $write_href,
        'rss_href'    => $rss_href,
        'admin_href'  => $admin_href,
        'search'      => array('sfl' => $sfl, 'stx' => $stx),
        'board_url'   => short_url_clean(G5_BBS_URL.'/board.php?bo_table='.$bo_table),
        'is_checkbox' => (bool)$is_checkbox,
        'list_update_action' => G5_BBS_URL.'/board_list_update.php',
    ));
}

// ── 댓글 매핑 (bbs/view_comment.php 의 스킨 include 자리에서 호출) — echo 없이 배열 반환
function g5_map_view_comment($list)
{
    $comments = array();
    for ($i = 0; $i < count($list); $i++) {
        $comments[] = array(
            'id'        => $list[$i]['wr_id'],
            'name'      => $list[$i]['name'],          // 사이드뷰 HTML → {!! !!}
            'content'   => $list[$i]['content'],       // 순정 가공 HTML → {!! !!}
            'datetime'  => $list[$i]['wr_datetime'],
            'depth'     => strlen((string)$list[$i]['wr_comment_reply']),
            'is_secret' => strpos((string)$list[$i]['wr_option'], 'secret') !== false,
            'del_link'  => isset($list[$i]['del_link']) ? $list[$i]['del_link'] : '',  // &amp; 포함 → {!! !!}
            'is_edit'   => (bool)$list[$i]['is_edit'],
            'is_reply'  => (bool)$list[$i]['is_reply'],   // 깊이 제한(5) 포함 순정 판정
            'raw'       => $list[$i]['content1'],         // 수정 폼 채움용 원문
        );
    }
    return $comments;
}

// ── 게시판 읽기 (bbs/view.php) — $comments 는 호출부가 view_comment.php 로 수집해 전달
function g5_map_board_view($comments)
{
    global $view, $board, $bo_table, $member, $is_admin, $qstr;
    global $update_href, $delete_href, $reply_href, $prev_href, $next_href, $comment_action_url;
    global $sca, $sfl, $stx, $spt, $page;

    include_once(G5_LIB_PATH.'/thumbnail.lib.php'); // get_view_thumbnail()

    $files = array();
    if (isset($view['file']) && is_array($view['file'])) {
        for ($i = 0; $i < (int)$view['file']['count']; $i++) {
            if (empty($view['file'][$i]['source'])) continue;
            $files[] = array(
                'source'   => $view['file'][$i]['source'],
                'href'     => $view['file'][$i]['href'],
                'size'     => $view['file'][$i]['size'],
                'download' => $view['file'][$i]['download'],
                'is_image' => (bool)$view['file'][$i]['view'],
                'view'     => $view['file'][$i]['view'],   // 이미지면 <img> HTML
            );
        }
    }

    $links = array();
    for ($i = 1; $i <= 2; $i++) {
        if (empty($view['link'][$i])) continue;
        $links[] = array(
            'url'  => $view['link'][$i],
            'href' => $view['link_href'][$i],
            'hit'  => $view['link_hit'][$i],
        );
    }

    g5_view('bbs.board_view', array(
        'board' => array(
            'bo_table'   => $bo_table,
            'bo_subject' => $board['bo_subject'],
        ),
        'post' => array(
            'wr_id'    => $view['wr_id'],
            'subject'  => get_text($view['wr_subject']),   // 이스케이프 완료 → {!! !!}
            'name'     => $view['name'],                   // 사이드뷰 HTML → {!! !!}
            'datetime' => $view['wr_datetime'],
            'hit'      => $view['wr_hit'],
            'ca_name'  => isset($view['ca_name']) ? $view['ca_name'] : '',
            'content'  => get_view_thumbnail($view['content']),  // 순정 가공 HTML → {!! !!}
        ),
        'files'       => $files,
        'links'       => $links,
        'comments'    => $comments,
        'list_href'   => short_url_clean(G5_BBS_URL.'/board.php?bo_table='.$bo_table.$qstr),
        'write_href'  => ($member['mb_level'] >= $board['bo_write_level'])
                         ? short_url_clean(G5_BBS_URL.'/write.php?bo_table='.$bo_table) : '',
        // 순정 view.php 가 만든 링크 (&amp; 엔티티 포함 → 뷰에서 {!! !!})
        'update_href' => $update_href,
        'delete_href' => $delete_href,
        'reply_href'  => $reply_href,
        'prev_href'   => $prev_href,
        'next_href'   => $next_href,
        'comment_action' => $comment_action_url,
        'comment_hidden' => array(
            'w'          => 'c',
            'bo_table'   => $bo_table,
            'wr_id'      => $view['wr_id'],
            'comment_id' => '',
            'sca'        => isset($sca) ? $sca : '',
            'sfl'        => isset($sfl) ? $sfl : '',
            'stx'        => isset($stx) ? $stx : '',
            'spt'        => isset($spt) ? $spt : '',
            'page'       => isset($page) ? $page : '',
            'is_good'    => '',
        ),
        'is_member'      => (bool)(isset($member['mb_id']) ? $member['mb_id'] : ''),
    ));
}

// ── 게시판 쓰기 (bbs/write.php) — 순정 write_update.php 계약 유지
function g5_map_board_write()
{
    global $w, $write, $board, $bo_table, $wr_id, $sca, $sfl, $stx, $spt, $sst, $sod, $page;
    global $action_url, $subject, $is_member, $name, $is_name, $is_password;
    global $editor_html, $editor_js, $is_use_captcha, $captcha_js, $file_count, $file;
    global $is_category, $is_notice, $is_html, $is_dhtml_editor, $html_value, $html_checked;
    global $is_secret, $secret_checked, $is_mail, $recv_email_checked, $is_admin, $notice_checked;

    $categories = array();
    if ($is_category && $board['bo_category_list']) {
        $w_ca_name = isset($write['ca_name']) ? $write['ca_name'] : (isset($sca) ? $sca : '');
        foreach (explode('|', (string)$board['bo_category_list']) as $c) {
            $categories[] = array('name' => $c, 'selected' => ($w_ca_name === $c));
        }
    }

    // 순정 write_update.php 계약: 필수 hidden (token 은 js/common.js 가 제출 시 주입)
    $hidden = array(
        'uid'      => get_uniqid(),
        'w'        => $w,
        'bo_table' => $bo_table,
        'wr_id'    => isset($wr_id) ? $wr_id : '',
        'sca'      => isset($sca) ? $sca : '',
        'sfl'      => isset($sfl) ? $sfl : '',
        'stx'      => isset($stx) ? $stx : '',
        'spt'      => isset($spt) ? $spt : '',
        'sst'      => isset($sst) ? $sst : '',
        'sod'      => isset($sod) ? $sod : '',
        'page'     => isset($page) ? $page : '',
    );

    // 옵션: 순정 basic 스킨 로직 이식 (마크업은 뷰에서)
    $option_hidden = '';
    $options = array(); // ['name','value','label','checked']
    if ($is_notice) {
        $options[] = array('name' => 'notice', 'value' => '1', 'label' => '공지', 'checked' => (bool)$notice_checked);
    }
    if ($is_html) {
        if ($is_dhtml_editor) {
            $option_hidden .= '<input type="hidden" value="html1" name="html">';
        } else {
            $options[] = array('name' => 'html', 'value' => $html_value, 'label' => 'html', 'checked' => (bool)$html_checked);
        }
    }
    if ($is_secret) {
        if ($is_admin || $is_secret == 1) {
            $options[] = array('name' => 'secret', 'value' => 'secret', 'label' => '비밀글', 'checked' => (bool)$secret_checked);
        } else {
            $option_hidden .= '<input type="hidden" name="secret" value="secret">';
        }
    }
    if ($is_mail) {
        $options[] = array('name' => 'mail', 'value' => 'mail', 'label' => '답변메일받기', 'checked' => (bool)$recv_email_checked);
    }

    $files_exist = array();
    if ($w === 'u' && isset($file) && is_array($file)) {
        for ($i = 0; $i < $file_count; $i++) {
            $files_exist[$i] = isset($file[$i]['source']) ? $file[$i]['source'] : '';
        }
    }

    g5_view('bbs.board_write', array(
        'board' => array(
            'bo_table'   => $bo_table,
            'bo_subject' => $board['bo_subject'],
        ),
        'w'          => $w,                       // '' 새글, 'u' 수정, 'r' 답변
        'action_url' => $action_url,
        'subject'    => $subject,                 // write.php 가공 완료(get_text) → 뷰 value 에 {!! !!}
        'categories' => $categories,
        'hidden'     => $hidden,
        'options'    => $options,
        'option_hidden' => $option_hidden,        // hidden HTML → {!! !!}
        'is_member'  => (bool)$is_member,
        'name'       => $name,
        'is_name'    => (bool)$is_name,
        'is_password'=> (bool)$is_password,
        'editor_html'    => $editor_html,          // 순정 에디터/textarea HTML → {!! !!}
        'editor_js'      => $editor_js,
        'is_use_captcha' => (bool)$is_use_captcha,
        // write.php 가 이미 준비한 값을 그대로 쓴다 (403·408행)
        'captcha_html'   => $is_use_captcha ? captcha_html() : '',
        'captcha_js'     => $captcha_js,
        'file_count'     => (int)$file_count,
        'files_exist'    => $files_exist,
        'list_href'      => short_url_clean(G5_BBS_URL.'/board.php?bo_table='.$bo_table),
    ));
}

// ── 로그인 (bbs/login.php)
function g5_map_login()
{
    global $login_action_url, $url;

    g5_view('bbs.login', array(
        'login_action_url' => $login_action_url,
        'url'              => $url,
    ));
}

// ── 비밀번호 확인 (bbs/member_confirm.php)
function g5_map_member_confirm()
{
    global $url, $member;

    g5_view('bbs.member_confirm', array(
        'action_url' => $url,                     // 확인 후 이동할 대상 (register_form.php 등)
        'mb_id'      => $member['mb_id'],
    ));
}

// ── 회원가입 약관 (bbs/register.php)
function g5_map_register()
{
    global $register_action_url, $config;

    g5_view('bbs.register', array(
        'action_url'  => $register_action_url,   // register_form.php
        'stipulation' => get_text($config['cf_stipulation']),  // 이스케이프 완료 → {!! !!}
        'privacy'     => get_text($config['cf_privacy']),
    ));
}

// ── 가입/정보수정 폼 (bbs/register_form.php)
function g5_map_register_form()
{
    global $w, $register_action_url, $urlencode, $agree, $agree2, $member;

    g5_view('bbs.register_form', array(
        'w'          => $w,
        'action_url' => $register_action_url,   // register_form_update.php
        'url'        => isset($urlencode) ? $urlencode : '',
        'agree'      => $agree,
        'agree2'     => $agree2,
        'me' => array(
            'mb_id'       => isset($member['mb_id']) ? $member['mb_id'] : '',
            'mb_name'     => isset($member['mb_name']) ? get_text($member['mb_name']) : '',
            'mb_nick'     => isset($member['mb_nick']) ? get_text($member['mb_nick']) : '',
            'mb_email'    => isset($member['mb_email']) ? $member['mb_email'] : '',
            'mb_homepage' => isset($member['mb_homepage']) ? get_text($member['mb_homepage']) : '',
        ),
        'captcha_html' => captcha_html(),
        'captcha_js'   => chk_captcha_js(),
    ));
}

// ── 가입 결과 (bbs/register_result.php)
function g5_map_register_result()
{
    global $mb;

    g5_view('bbs.register_result', array(
        'mb_id'   => isset($mb['mb_id']) ? get_text($mb['mb_id']) : '',
        'mb_nick' => isset($mb['mb_nick']) ? get_text($mb['mb_nick']) : '',
    ));
}

// ── 쪽지 목록 (bbs/memo.php) — kind: recv|send
function g5_map_memo()
{
    global $kind, $kind_title, $list, $total_count, $page, $member, $config;

    $total_page = ceil($total_count / $config['cf_page_rows']);
    $items = array();
    foreach ((array)$list as $row) {
        $items[] = array(
            'me_id'     => $row['me_id'],
            'name'      => get_text($row['mb_nick'] ? $row['mb_nick'] : $row['mb_id']),
            'preview'   => get_text(cut_str($row['me_memo'], 60)),
            'datetime'  => $row['me_send_datetime'],
            'is_read'   => (isset($row['me_read_datetime']) && $row['me_read_datetime'] !== '0000-00-00 00:00:00'),
            'view_href' => $row['view_href'],   // &amp; 포함 → {!! !!}
            'del_href'  => $row['del_href'],    // 세션 토큰 포함 → {!! !!}
        );
    }

    g5_view('bbs.memo', array(
        'kind'        => $kind,
        'kind_title'  => $kind_title,
        'items'       => $items,
        'total_count' => (int)$total_count,
        'page'        => (int)$page,
        'total_page'  => (int)$total_page,
        'page_href'   => G5_BBS_URL.'/memo.php?kind='.$kind.'&page=',
        'recv_href'   => G5_BBS_URL.'/memo.php?kind=recv',
        'send_href'   => G5_BBS_URL.'/memo.php?kind=send',
        'form_href'   => G5_BBS_URL.'/memo_form.php',
    ));
}

// ── 쪽지 보기 (bbs/memo_view.php)
function g5_map_memo_view()
{
    global $kind, $memo, $del_link, $prev_link, $next_link, $unkind;

    // 존재하지 않는 쪽지 (순정도 이 경우 빈 화면) — 목록으로 되돌린다
    if (empty($memo['me_id'])) {
        alert('쪽지가 존재하지 않습니다.', G5_BBS_URL.'/memo.php?kind='.$kind);
    }

    $counterpart = $memo['me_'.$unkind.'_mb_id'];

    g5_view('bbs.memo_view', array(
        'kind'       => $kind,
        'name'       => get_text($counterpart),
        'datetime'   => $memo['me_send_datetime'],
        'content'    => get_text($memo['me_memo'], 1),   // 이스케이프+개행 처리 → {!! !!}
        'reply_href' => ($kind === 'recv')
                        ? G5_BBS_URL.'/memo_form.php?me_recv_mb_id='.urlencode($counterpart).'&me_id='.$memo['me_id'] : '',
        'del_href'   => G5_BBS_URL.'/'.$del_link,        // &amp;·토큰 포함 → {!! !!}
        'prev_href'  => $prev_link ? G5_BBS_URL.'/'.ltrim($prev_link, './') : '',
        'next_href'  => $next_link ? G5_BBS_URL.'/'.ltrim($next_link, './') : '',
        'list_href'  => G5_BBS_URL.'/memo.php?kind='.$kind,
    ));
}

// ── 쪽지 쓰기 (bbs/memo_form.php)
function g5_map_memo_form()
{
    global $me_recv_mb_id, $content, $memo_action_url;

    g5_view('bbs.memo_form', array(
        'action_url' => $memo_action_url,     // memo_form_update.php
        'recv_mb_id' => get_text($me_recv_mb_id),
        'content'    => get_text($content),   // 답장 인용문 (이스케이프 완료, textarea 값 → {!! !!})
        'list_href'  => G5_BBS_URL.'/memo.php?kind=recv',
        'captcha_html' => captcha_html(),     // memo_form_update.php 가 항상 chk_captcha()
        'captcha_js'   => chk_captcha_js(),
    ));
}

// ── 포인트 내역 (bbs/point.php)
function g5_map_point()
{
    global $list, $total_count, $page, $config, $member;

    $rows = $config['cf_page_rows'];
    $total_page = ceil($total_count / $rows);
    $items = array();
    foreach ((array)$list as $row) {
        $items[] = array(
            'content'  => get_text($row['po_content']),
            'point'    => (int)$row['po_point'],
            'datetime' => $row['po_datetime'],
        );
    }

    g5_view('bbs.point', array(
        'items'       => $items,
        'total_count' => (int)$total_count,
        'sum_point'   => (int)$member['mb_point'],
        'page'        => (int)$page,
        'total_page'  => (int)$total_page,
        'page_href'   => G5_BBS_URL.'/point.php?page=',
    ));
}

// ── 검색 결과 (bbs/search.php) — 게시판별 그룹
function g5_map_search()
{
    global $stx, $sfl, $sop, $total_count, $list, $bo_subject, $search_table;
    global $board_count, $page, $total_page, $srows;

    $groups = array();
    foreach ((array)$list as $idx => $rows) {
        if (!$rows) continue;
        $items = array();
        foreach ($rows as $row) {
            $items[] = array(
                'href'     => $row['href'],
                'subject'  => $row['subject'],   // search_font 처리(검색어 강조 HTML) → {!! !!}
                'content'  => $row['content'],   // 동일
                'name'     => $row['name'],      // 사이드뷰 HTML → {!! !!}
                'datetime' => substr($row['wr_datetime'], 0, 10),
                'hit'      => $row['wr_hit'],
                'comment_cnt' => (int)$row['wr_comment'],
            );
        }
        $groups[] = array(
            'bo_table'   => $search_table[$idx],
            'bo_subject' => isset($bo_subject[$idx]) ? $bo_subject[$idx] : $search_table[$idx],
            'href'       => G5_BBS_URL.'/board.php?bo_table='.$search_table[$idx],
            'items'      => $items,
        );
    }

    g5_view('bbs.search', array(
        'stx'         => get_text($stx),
        'sfl'         => $sfl,
        'sop'         => $sop,
        'total_count' => (int)$total_count,
        'board_count' => (int)$board_count,
        'groups'      => $groups,
        'page'        => (int)$page,
        'total_page'  => (int)$total_page,
        'page_href'   => G5_BBS_URL.'/search.php?sfl='.urlencode($sfl).'&stx='.urlencode($stx).'&sop='.urlencode($sop).'&page=',
        'action_url'  => G5_BBS_URL.'/search.php',
    ));
}

// ── 스크랩 목록 (bbs/scrap.php)
function g5_map_scrap()
{
    global $list, $total_count, $page, $total_page;

    $items = array();
    foreach ((array)$list as $row) {
        $items[] = array(
            'num'        => $row['num'],
            'bo_subject' => get_text($row['bo_subject']),
            'subject'    => $row['subject'],      // get_text 완료 → {!! !!}
            'href'       => $row['opener_href_wr_id'],
            'datetime'   => substr($row['ms_datetime'], 0, 10),
            'del_href'   => G5_BBS_URL.'/'.ltrim($row['del_href'], './'),  // &amp; 포함 → {!! !!}
        );
    }

    g5_view('bbs.scrap', array(
        'items'       => $items,
        'total_count' => (int)$total_count,
        'page'        => (int)$page,
        'total_page'  => (int)$total_page,
        'page_href'   => G5_BBS_URL.'/scrap.php?page=',
    ));
}

/* ═══════════════════════════════════════════════════════════
   쇼핑몰
   ═══════════════════════════════════════════════════════════ */

// item_list 에서 상품 배열만 뽑는다 (출력 없음) — extend/blade.shop_items.php 참고
