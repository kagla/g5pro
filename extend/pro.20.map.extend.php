<?php
/**
 * g5pro 화면 매핑 모음 — 기본 서비스(bbs·회원).
 * 변환된 순정 화면이 스킨 include 자리에서 g5_map_*() 를 호출한다.
 * 한 화면 = 한 함수. 순정 전역변수를 뷰용 배열로 정리해 g5_view() 를 호출하는 것이 전부다.
 * (런타임·공통 데이터는 pro.10.extend.php — extend/ 로드 순서 설명도 그 머리말에 있다)
 *
 * 로드 순서 20번. 함수 정의뿐이라 순서 의존은 없고, 번호는 읽는 사람을 위한 것이다.
 */
if (!defined('_GNUBOARD_')) exit;

// bo_skin → 목록 뷰 조회표 (게시판마다 목록 모양을 고른다 · 설계 §5)
// 등록되지 않은 값이면 기본 표 목록으로 폴백한다.
//
// 값은 순정 스킨명을 그대로 쓴다. 설치기가 bo_skin 에 basic/gallery 를 넣고, 순정 스킨으로
// 쓰던 사이트가 이 배포판으로 옮겨와도 목록 모양이 그대로 유지된다. 예전에는 pro_ 접두사를
// 붙이고 순정명을 별칭으로 이어 붙였는데, 같은 것을 두 이름으로 부르는 셈이라 걷어냈다.
function g5_pro_list_views()
{
    return array(
        'basic'   => array('view' => 'bbs.board_list',         'body' => 'partials.list_body_table',   'thumb' => false),
        'simple'  => array('view' => 'bbs.board_list_simple',  'body' => 'partials.list_body_simple',  'thumb' => false),
        'card'    => array('view' => 'bbs.board_list_card',    'body' => 'partials.list_body_card',    'thumb' => true),
        'gallery' => array('view' => 'bbs.board_list_gallery', 'body' => 'partials.list_body_gallery', 'thumb' => true),
    );
}

// 관리자 스킨 선택에 덧붙일 이름.
// 순정은 skin/board/ 디렉터리를 훑어 선택지를 만든다. basic·gallery 는 그 디렉터리가
// 실제로 있으므로 이미 목록에 뜬다. 여기에는 디렉터리가 없는 것만 넣는다 — 안 그러면
// 같은 값이 두 번 보인다. 라벨을 따로 붙이지 않는 이유는, 디렉터리에서 온 항목이
// 이름 그대로 보이기 때문이다. 둘만 한글 설명이 붙으면 목록의 결이 어긋난다.
function g5_pro_list_skins()
{
    return array('simple', 'card');
}

// ── 게시판 목록 (bbs/list.php)
function g5_map_board_list()
{
    global $list, $board, $bo_table, $is_category, $sca, $sfl, $stx, $sst, $sod, $sop, $wr_id;
    global $total_count, $page, $total_page, $write_href, $rss_href, $admin_href, $is_checkbox;
    global $is_good, $is_nogood;

    $views = g5_pro_list_views();
    $skin  = isset($board['bo_skin']) ? $board['bo_skin'] : '';
    $variant = isset($views[$skin]) ? $views[$skin] : $views['basic'];
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
            'is_current'  => (!empty($wr_id) && $wr_id == $row['wr_id']),   // 열람중
            'icon_new'    => !empty($row['icon_new']),
            'icon_file'   => !empty($row['icon_file']),
            'icon_secret' => !empty($row['icon_secret']),
            'icon_hot'    => !empty($row['icon_hot']),      // bo_hot 이상 조회
            'icon_link'   => !empty($row['icon_link']),
            'icon_reply'  => !empty($row['icon_reply']),
            // 답변글 들여쓰기 — wr_reply 한 글자당 한 단계
            'depth'       => strlen((string)$row['wr_reply']),
            'ca_name'     => isset($row['ca_name']) ? $row['ca_name'] : '',
            'ca_href'     => isset($row['ca_name_href']) ? $row['ca_name_href'] : '',
            'good'        => (int)$row['wr_good'],
            'nogood'      => (int)$row['wr_nogood'],
            // bo_use_list_content — 목록에 본문 미리보기 (태그를 걷어낸 발췌)
            'excerpt'     => isset($row['content'])
                             ? cut_str(trim(preg_replace('/\s+/u', ' ', strip_tags(str_replace('<', ' <', $row['content'])))), 160, '…')
                             : '',
            // bo_use_list_file — 목록에 첨부 파일
            'files'       => g5_pro_list_files($row),
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

    $data = array(
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
        'search'      => array('sfl' => $sfl, 'stx' => $stx, 'sca' => $sca),
        'board_url'   => short_url_clean(G5_BBS_URL.'/board.php?bo_table='.$bo_table),
        'is_checkbox' => (bool)$is_checkbox,
        'list_update_action' => G5_BBS_URL.'/board_list_update.php',
        'move_action'        => G5_BBS_URL.'/move.php',
        // 게시판 설정에서 켠 것만 화면에 나타난다
        'is_good'     => (bool)$is_good,
        'is_nogood'   => (bool)$is_nogood,
        'use_search'  => (bool)$board['bo_use_search'],
        'use_content' => (bool)$board['bo_use_list_content'],
        'use_file'    => (bool)$board['bo_use_list_file'],
        'gallery_cols'=> max(1, (int)$board['bo_gallery_cols']),
        'sfl_options' => get_board_sfl_select_options($sfl),   // 순정 옵션 HTML → {!! !!}
        'sort'        => g5_pro_sort_links($bo_table, $sop, $is_good, $is_nogood),
        'sort_now'    => array('sst' => $sst, 'sod' => $sod),
        'content_head'=> g5_pro_captured('content_head'),    // 관리자가 넣은 HTML → {!! !!}
        'content_tail'=> g5_pro_captured('content_tail'),
    );

    // 전체목록보이기(bo_use_list_view) — 읽기 화면이 먼저 목록을 수집한 뒤 자기 아래에 붙인다
    if (!empty($GLOBALS['g5_pro_collect_list'])) {
        $GLOBALS['g5_pro_list_below'] = array('body' => $variant['body'], 'data' => $data);
        return;
    }
    g5_view($variant['view'], $data);
}

// 목록의 첨부 파일 (bo_use_list_file 을 켜야 get_list 가 채운다)
function g5_pro_list_files($row)
{
    $out = array();
    if (empty($row['file']) || !is_array($row['file'])) return $out;
    for ($i = 0; $i < (int)$row['file']['count']; $i++) {
        if (empty($row['file'][$i]['source'])) continue;
        $out[] = array(
            'source' => $row['file'][$i]['source'],
            'href'   => $row['file'][$i]['href'],
            'size'   => $row['file'][$i]['size'],
        );
    }
    return $out;
}

// 정렬 링크 — 순정 subject_sort_link() 가 여는 <a> 태그만 돌려주므로 href 만 뽑아 쓴다
function g5_pro_sort_links($bo_table, $sop, $is_good, $is_nogood)
{
    $qstr2 = 'bo_table='.$bo_table.'&amp;sop='.$sop;
    $cols = array('wr_hit' => '조회', 'wr_datetime' => '날짜');
    if ($is_good)   $cols['wr_good']   = '추천';
    if ($is_nogood) $cols['wr_nogood'] = '비추천';

    $out = array();
    foreach ($cols as $col => $label) {
        $tag = subject_sort_link($col, $qstr2, 1);
        $out[$col] = array(
            'label' => $label,
            'href'  => preg_match('/href="([^"]*)"/', $tag, $m) ? $m[1] : '',
        );
    }
    return $out;
}

// ── 게시물 복사·이동 대상 고르기 (bbs/move.php) — 목록에서 팝업으로 연다
function g5_map_move($sw, $act, $wr_id_list, $list)
{
    global $bo_table, $sfl, $stx, $spt, $sst, $sod, $page;

    $boards = array();
    foreach ($list as $row) {
        $boards[] = array(
            'bo_table'   => $row['bo_table'],
            'bo_subject' => $row['bo_subject'],
            'gr_subject' => $row['gr_subject'],
            'current'    => ($row['bo_table'] === $bo_table),
        );
    }

    g5_view('bbs.move', array(
        'sw'          => $sw,
        'act'         => $act,           // '복사' | '이동'
        'wr_id_list'  => $wr_id_list,
        'boards'      => $boards,
        'action'      => G5_BBS_URL.'/move_update.php',
        'count'       => count(array_filter(explode(',', (string)$wr_id_list), 'strlen')),
        'keep'        => array(          // 순정 move_update.php 가 그대로 되돌려받는 검색 상태
            'bo_table' => $bo_table,
            'sfl' => $sfl, 'stx' => $stx, 'spt' => $spt,
            'sst' => $sst, 'sod' => $sod, 'page' => $page,
        ),
        'referer'     => isset($_SERVER['HTTP_REFERER']) ? get_text(clean_xss_tags($_SERVER['HTTP_REFERER'])) : '',
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
            'datetime'  => g5_pro_dt($list[$i]['wr_datetime']),
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
    global $view, $board, $bo_table, $member, $is_admin, $is_member, $qstr;
    global $update_href, $delete_href, $reply_href, $prev_href, $next_href, $comment_action_url;
    global $scrap_href, $sca, $sfl, $stx, $spt, $page;
    global $good_href, $nogood_href, $copy_href, $move_href, $search_href;
    global $is_ip_view, $is_signature, $signature;
    global $prev_wr_subject, $prev_wr_date, $next_wr_subject, $next_wr_date;

    include_once(G5_LIB_PATH.'/thumbnail.lib.php'); // get_view_thumbnail(), get_file_thumbnail()

    // 본문이 {이미지:n} 으로 직접 부른 첨부는 rich_content 안에 이미 그려져 있다.
    // 그 번호는 아래 이미지 묶음에서 빼야 같은 그림이 두 번 나오지 않는다.
    $inlined = array();
    if (preg_match_all("/{이미지\:([0-9]+)[:]?([^}]*)}/i", $view['content'], $m))
        $inlined = array_flip($m[1]);

    $files = array();
    $images = array();
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
            // 이미지 첨부는 그림으로도 보여준다 (순정 view.skin.php 의 bo_v_img).
            // get_file_thumbnail() 이 게시판 '이미지 폭' 설정까지 맞춰 준다.
            if ($view['file'][$i]['view'] && !isset($inlined[$i]))
                $images[] = get_file_thumbnail($view['file'][$i]);
        }
    }

    // 내가 이미 누른 것 — 버튼을 켜 두어야 다시 눌러 취소할 수 있음이 보인다
    $my_react = '';
    if (!empty($member['mb_id'])) {
        $g = sql_fetch(" select bg_flag from `{$GLOBALS['g5']['board_good_table']}`
                          where bo_table = '".sql_escape_string($bo_table)."'
                            and wr_id = '".(int)$view['wr_id']."'
                            and mb_id = '".sql_escape_string($member['mb_id'])."'
                            and bg_flag in ('good','nogood') ", false);
        $my_react = isset($g['bg_flag']) ? $g['bg_flag'] : '';
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
            'datetime' => g5_pro_dt($view['wr_datetime']),
            'hit'      => $view['wr_hit'],
            'ca_name'  => isset($view['ca_name']) ? $view['ca_name'] : '',
            'ca_href'  => isset($view['ca_name']) && $view['ca_name']
                          ? short_url_clean(G5_BBS_URL.'/board.php?bo_table='.$bo_table.'&sca='.urlencode($view['ca_name'])) : '',
            // rich_content — 본문의 {이미지:n} 치환까지 끝난 순정 결과
            'content'  => get_view_thumbnail($view['rich_content']),  // 순정 가공 HTML → {!! !!}
            'comment_cnt' => (int)$view['wr_comment'],
            'ip'       => $is_ip_view ? $view['wr_ip'] : '',   // bo_use_ip_view
        ),
        'files'       => $files,
        'images'      => $images,
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
        'scrap_href'  => $scrap_href,   // 회원일 때만 값 있음 — win_scrap 팝업으로 연다
        // 이전·다음글 — 제목과 날짜까지 (순정 view.php 가 함께 만들어 둔다)
        'prev' => $prev_href ? array('href' => $prev_href, 'subject' => $prev_wr_subject, 'date' => g5_pro_dt($prev_wr_date, false)) : null,
        'next' => $next_href ? array('href' => $next_href, 'subject' => $next_wr_subject, 'date' => g5_pro_dt($next_wr_date, false)) : null,
        // 추천·비추천 — href 는 회원이고 게시판이 켠 경우에만 값이 있다
        // 비회원이 추천·비추천·스크랩을 누르면 로그인으로 보낸다. 보던 주소를 그대로 담아
        // 검색어·페이지 상태까지 안고 돌아온다. 절대주소 + urlencode 는 순정 qawrite.php 가
        // 쓰는 방식이고, login_check.php 의 check_url_host() 가 같은 호스트만 통과시킨다.
        'login_href' => $is_member ? '' :
            G5_BBS_URL.'/login.php?url='.urlencode(G5_URL.(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/')),
        'good' => array(
            'use'    => (bool)$board['bo_use_good'],
            'href'   => $good_href ? $good_href.'&amp;'.$qstr : '',
            'count'  => (int)$view['wr_good'],
            'mine'   => ($my_react === 'good'),
        ),
        'nogood' => array(
            'use'    => (bool)$board['bo_use_nogood'],
            'href'   => $nogood_href ? $nogood_href.'&amp;'.$qstr : '',
            'count'  => (int)$view['wr_nogood'],
            'mine'   => ($my_react === 'nogood'),
        ),
        'signature'   => $is_signature ? $signature : '',   // bo_use_signature, 순정 가공 HTML
        'copy_href'   => $copy_href,     // 게시판 관리자 이상
        'move_href'   => $move_href,
        'search_href' => $search_href,   // 검색 결과에서 들어왔을 때만 값 있음
        'use_sns'     => (bool)($board['bo_use_sns'] && (!empty($GLOBALS['config']['cf_facebook_appid']) || !empty($GLOBALS['config']['cf_twitter_key']))),
        'share_url'   => G5_BBS_URL.'/board.php?bo_table='.$bo_table.'&wr_id='.$view['wr_id'],
        'content_head'=> g5_pro_captured('content_head'),
        'content_tail'=> g5_pro_captured('content_tail'),
        // 전체목록보이기를 켠 게시판에서만 값이 있다
        'list_below'  => isset($GLOBALS['g5_pro_list_below']) ? $GLOBALS['g5_pro_list_below'] : null,
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
    global $is_file_content;
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

    // 이미 붙어 있는 파일 — 이름만으로는 지울 수도, 설명을 고칠 수도 없다.
    // 순정 write.skin.php 가 주는 것과 같은 값을 넘긴다.
    $files_exist = array();
    if ($w === 'u' && isset($file) && is_array($file)) {
        for ($i = 0; $i < $file_count; $i++) {
            $files_exist[$i] = array(
                'source'  => isset($file[$i]['source']) ? $file[$i]['source'] : '',
                'size'    => isset($file[$i]['size']) ? $file[$i]['size'] : '',
                // 파일이 실제로 있을 때만 삭제 칸을 낸다 (순정과 같은 조건)
                'has'     => !empty($file[$i]['file']),
                'content' => isset($file[$i]['bf_content']) ? $file[$i]['bf_content'] : '',
            );
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
        'is_file_content'=> !empty($is_file_content),   // 게시판 설정 bo_use_file_content
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

// ── 회원 프로필 (bbs/profile.php) — 글쓴이 이름의 사이드뷰에서 새 창으로 연다.
// 이름은 순정 get_sideview() HTML 대신 순수 텍스트를 쓴다 — 팝업 안에서 또 사이드뷰를 열 일이 없고,
// 그 HTML 은 아바타 <img> 를 품고 있어 프로필 사진과 겹친다.
function g5_map_profile($mb, $reg_after, $homepage, $profile_html)
{
    global $member;

    $can_see = ($member['mb_level'] >= $mb['mb_level']);   // 순정과 같은 판정

    g5_view('bbs.profile', array(
        'nick'      => get_text($mb['mb_nick']),
        'photo'     => g5_pro_profile_src($mb['mb_id']),
        'level'     => (int)$mb['mb_level'],
        'point'     => (int)$mb['mb_point'],
        'join_date' => $can_see ? g5_pro_dt($mb['mb_datetime'], false) : '',
        'join_days' => $can_see ? (int)$reg_after : 0,
        'last_login'=> $can_see ? g5_pro_dt($mb['mb_today_login']) : '',
        'homepage'  => $homepage,
        'profile'   => $profile_html,   // 순정 conv_content 완료 → {!! !!}
    ));
}

// ── 메일 쓰기 (bbs/formmail.php) — 사이드뷰의 "메일보내기". formmail_send.php 계약 그대로:
// to(암호화된 주소)·attach·fnick·fmail·subject·type·content·file1·file2 + 캡차.
function g5_map_formmail($name, $email, $type)
{
    global $is_member, $member;

    g5_view('bbs.formmail', array(
        'name'      => $name,        // get_text 완료 → {!! !!}
        'email'     => $email,       // str_encrypt 로 암호화된 값
        'is_member' => (bool)$is_member,
        'mb_nick'   => $is_member ? get_text($member['mb_nick']) : '',
        'mb_email'  => $is_member ? $member['mb_email'] : '',
        'type'      => (int)$type,
        'action'    => G5_BBS_URL.'/formmail_send.php',
        'captcha_html' => captcha_html(),
        'captcha_js'   => chk_captcha_js(),
    ));
}

// ── 비밀번호 확인 (bbs/password.php) — 비밀글 열람·수정·삭제 전에 한 번 묻는 화면.
// $w 에 따라 action 과 문구가 갈리는 것은 순정이 이미 정해 놓은 값을 그대로 받는다.
function g5_map_board_password($action, $w, $comment_id)
{
    global $g5, $bo_table, $wr_id, $sfl, $stx, $page;

    // 제목·문구는 순정 스킨(password.skin.php)이 $w 로 갈라 세우던 것을 그대로 옮겼다
    if ($w === 'u') {
        $g5['title'] = '글 수정';
        $lead = array('작성자만 글을 수정할 수 있습니다.', '글 쓸 때 적은 비밀번호를 넣으면 수정할 수 있습니다.');
    } else if ($w === 'd' || $w === 'x') {
        $g5['title'] = ($w === 'x' ? '댓글 삭제' : '글 삭제');
        $lead = array('작성자만 글을 삭제할 수 있습니다.', '글 쓸 때 적은 비밀번호를 넣으면 삭제할 수 있습니다.');
    } else {
        $lead = array('비밀글로 보호된 글입니다.', '작성자와 관리자만 볼 수 있습니다. 본인이라면 비밀번호를 넣어 주세요.');
    }

    g5_view('bbs.password', array(
        'action'  => $action,
        'lead'    => $lead,
        'hidden'  => array(
            'w'          => $w,
            'bo_table'   => $bo_table,
            'wr_id'      => $wr_id,
            'comment_id' => $comment_id,
            'sfl'        => isset($sfl) ? $sfl : '',
            'stx'        => isset($stx) ? $stx : '',
            'page'       => isset($page) ? $page : '',
        ),
        'list_href' => G5_BBS_URL.'/board.php?bo_table='.$bo_table,
    ));
}

// ── 이미지 크게보기 (bbs/view_image.php) — 끌어서 옮기고 더블클릭으로 닫는 순정 뷰어.
// 이미지·드래그 스크립트는 순정 출력을 그대로 담고 사이트 골격만 팝업 문서로 바꾼다.
function g5_map_view_image($body_html)
{
    g5_view('bbs.view_image', array('body_html' => $body_html));
}

// ── 알림 (bbs/alert.php) — 순정 alert() 이 부르는 흐름의 끝. 스크립트가 alert 를 띄우고
// url 로 이동(없으면 history.back)한다. 그 스크립트는 순정이 만든 것을 그대로 받아 넘긴다.
// 뷰가 그리는 것은 스크립트가 도는 동안 잠깐 보이는 화면 + JS 를 끈 브라우저용 대체 화면이다.
function g5_map_alert($script, $message, $heading, $url, $post_fields)
{
    g5_view_message('bbs.alert', array(
        'script'      => $script,        // 순정이 만든 <script> 알맹이 → {!! !!}
        'message'     => $message,       // 개행을 <br> 로 바꾼 순정 $msg2 (strip_tags 완료) → {!! !!}
        'heading'     => $heading,
        'url'         => $url,
        'post_fields' => $post_fields,   // $post 일 때 되돌아갈 폼에 실을 값
    ));
}

// ── 알림 후 창닫기 (bbs/alert_close.php) — 팝업 안에서 뜬 알림. 확인하면 창이 닫힌다.
function g5_map_alert_close($script, $message, $heading, $note)
{
    global $g5;

    g5_view_message('bbs.alert_close', array(
        // 순정이 $g5['title'] 을 세우지 않는 화면이라, 두면 사이트 이름이 제목처럼 읽힌다
        'title'   => (isset($g5['title']) && $g5['title']) ? $g5['title'] : '알림',
        'script'  => $script,
        'message' => $message,
        'heading' => $heading,
        'note'    => $note,
    ));
}

// ── 확인 (bbs/confirm.php) — 순정 confirm() . 예/아니오에 따라 url1/url2 로 갈린다.
function g5_map_confirm($script, $message, $heading, $url1, $url2, $url3)
{
    global $g5;

    g5_view_message('bbs.confirm', array(
        'title'   => (isset($g5['title']) && $g5['title']) ? $g5['title'] : '확인',
        'script'  => $script,
        'message' => $message,
        'heading' => $heading,
        'url1'    => $url1,
        'url2'    => $url2,
        'url3'    => $url3,
    ));
}

// ── 회원정보 찾기 (bbs/password_lost.php) — 가입 이메일로 아이디·임시비밀번호 안내메일 받기.
// 본인인증으로 찾기(cf_cert_find)를 켠 사이트는 호출부가 직통을 포기하고 순정 스킨을 쓴다.
function g5_map_password_lost()
{
    global $action_url;

    g5_view('bbs.password_lost', array(
        'action_url'   => $action_url,        // password_lost2.php
        'captcha_html' => captcha_html(),     // password_lost2.php 가 항상 chk_captcha()
        'captcha_js'   => chk_captcha_js(),
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
        // 이름이 'me' 면 안 된다 — g5_view() 가 array_merge 로 합치며 뒤가 이기므로
        // 레이아웃(g5_pro_common)의 me 를 통째로 덮어써 헤더 프로필 메뉴가 깨진다.
        // mb_level 이 사라져 최고관리자에게 관리자 링크가 안 나오던 원인이었다.
        'form' => array(
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
            'datetime'  => g5_pro_dt($row['me_send_datetime']),
            'is_read'   => !pro_empty_date(isset($row['me_read_datetime']) ? $row['me_read_datetime'] : null),
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
        'datetime'   => g5_pro_dt($memo['me_send_datetime']),
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
            'datetime' => g5_pro_dt($row['po_datetime']),
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
                'datetime' => g5_pro_dt($row['wr_datetime'], false),
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

// ── 스크랩 목록 (bbs/scrap.php) — win_scrap 600px 창, 링크는 opener(부모 창)에서 연다
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
            'board_href' => $row['opener_href'],
            'datetime'   => g5_pro_dt($row['ms_datetime'], false),
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

// ── 스크랩 팝업 (bbs/scrap_popin.php) — win_scrap 600px 창, 사이트 골격 없는 독립 뷰
function g5_map_scrap_popin()
{
    global $bo_table, $wr_id, $write;

    g5_view('bbs.scrap_popin', array(
        'bo_table' => $bo_table,
        'wr_id'    => $wr_id,
        'subject'  => get_text(cut_str($write['wr_subject'], 255)),  // 이스케이프 완료 → {!! !!}
        'action'   => G5_BBS_URL.'/scrap_popin_update.php',
    ));
}

// ── 게시판 그룹 (bbs/group.php) — $boards 는 호출부가 순정 쿼리(접근레벨 필터)로 수집해 전달
function g5_map_group($boards)
{
    global $group, $gr_id;

    g5_view('bbs.group', array(
        'group' => array(
            'gr_id'      => $gr_id,
            'gr_subject' => get_text($group['gr_subject']),   // 이스케이프 완료 → {!! !!}
        ),
        'boards' => $boards,   // [['bo_table','bo_subject'], ...] — 최신글은 뷰가 partials.latest 로 조회
    ));
}

// ── 내용 페이지 (bbs/content.php) — $html 은 호출부가 conv_content·치환코드 처리를 마친 본문
function g5_map_content($html)
{
    global $co, $co_id, $is_admin;

    g5_view('bbs.content', array(
        'co_id'      => $co_id,
        'subject'    => get_text($co['co_subject']),   // 이스케이프 완료 → {!! !!}
        'content'    => $html,                         // conv_content 완료 HTML → {!! !!}
        'head_img'   => file_exists(G5_DATA_PATH.'/content/'.$co_id.'_h') ? G5_DATA_URL.'/content/'.$co_id.'_h' : '',
        'tail_img'   => file_exists(G5_DATA_PATH.'/content/'.$co_id.'_t') ? G5_DATA_URL.'/content/'.$co_id.'_t' : '',
        'admin_href' => $is_admin ? G5_ADMIN_URL.'/contentform.php?w=u&co_id='.$co_id : '',
    ));
}

// ── 새글 모아보기 (bbs/new.php)
// 순정은 그룹 select 를 HTML 문자열($group_select)로 만들지만, 우리 폼은 우리가 그리므로
// 같은 질의를 다시 돌려 배열로 넘긴다 (게시판 그룹 표라 행이 몇 개뿐이다).
function g5_map_new()
{
    global $g5, $list, $total_count, $page, $total_page, $gr_id, $view, $mb_id, $is_admin, $rows;

    $groups = array();
    $res = sql_query(" select gr_id, gr_subject from {$g5['group_table']} order by gr_id ");
    while ($row = sql_fetch_array($res)) {
        $groups[] = array('id' => $row['gr_id'], 'subject' => $row['gr_subject']);
    }

    $items = array();
    foreach ((array)$list as $row) {
        $items[] = array(
            'gr_id'      => $row['gr_id'],
            'gr_subject' => $row['gr_subject'],
            'bo_table'   => $row['bo_table'],
            'bo_subject' => $row['bo_subject'],
            'wr_id'      => $row['wr_id'],
            'bn_id'      => isset($row['bn_id']) ? $row['bn_id'] : '',
            'subject'    => get_text($row['wr_subject']),
            'href'       => $row['href'],
            'name'       => $row['name'],                  // 사이드뷰 HTML → {!! !!}
            // 순정이 이미 목록용으로 줄여 둔 값 — 오늘이면 시:분, 아니면 월-일.
            // 게시판 목록도 같은 값을 쓰므로 두 화면의 눈금이 맞는다.
            'datetime'   => $row['datetime2'],
            'is_comment' => !empty($row['comment']),
        );
    }

    // 현재 조건을 유지한 채 한 가지만 바꾼 주소 (필터 링크용)
    $qs = function ($over = array()) use ($gr_id, $view, $mb_id) {
        $q = array_merge(array('gr_id' => $gr_id, 'view' => $view, 'mb_id' => $mb_id), $over);
        $q = array_filter($q, function ($v) { return $v !== '' && $v !== null; });
        return './new.php'.($q ? '?'.http_build_query($q) : '');
    };

    g5_view('bbs.new', array(
        'items'      => $items,
        'groups'     => $groups,
        'gr_id'      => $gr_id,
        'view'       => $view,
        'mb_id'      => $mb_id,
        'is_admin'   => (bool)$is_admin,
        'total'      => (int)$total_count,
        'page'       => (int)$page,
        'total_page' => (int)$total_page,
        'page_href'  => $qs(array('page' => '')).(strpos($qs(), '?') === false ? '?page=' : '&page='),
        'views'      => array(
            array('key' => '',  'label' => '전체',   'href' => $qs(array('view' => ''))),
            array('key' => 'w', 'label' => '원글',   'href' => $qs(array('view' => 'w'))),
            array('key' => 'c', 'label' => '댓글',   'href' => $qs(array('view' => 'c'))),
        ),
        'group_href' => $qs(array('gr_id' => '__GR__')),
    ));
}

// ── 자주하시는 질문 (bbs/faq.php)
// 순정은 마스터(분류)마다 목록을 따로 그린다. 분류 전환은 링크, 검색은 분류 안에서만 걸린다.
function g5_map_faq()
{
    global $faq_master_list, $fm, $fm_id, $faq_list, $stx, $himg_src, $timg_src, $is_admin, $admin_href;
    global $total_count, $page, $total_page;

    $cates = array();
    foreach ((array)$faq_master_list as $row) {
        $cates[] = array(
            'id'     => $row['fm_id'],
            'name'   => $row['fm_subject'],
            'href'   => G5_BBS_URL.'/faq.php?fm_id='.$row['fm_id'],
            'active' => ((int)$row['fm_id'] === (int)$fm_id),
        );
    }

    $items = array();
    foreach ((array)$faq_list as $row) {
        $items[] = array(
            'id'      => $row['fa_id'],
            // 검색어가 있으면 순정이 search_font 로 강조 HTML 을 넣는다 → {!! !!}
            'subject' => $row['fa_subject'],
            'content' => $row['fa_content'],
        );
    }

    g5_view('bbs.faq', array(
        'title'      => $fm['fm_subject'],
        'cates'      => $cates,
        'items'      => $items,
        'fm_id'      => $fm_id,
        'stx'        => $stx,
        // 관리자가 FAQ관리에서 올린 머리말·꼬리말 이미지 (없으면 빈 값)
        'head_img'   => isset($himg_src) ? $himg_src : '',
        'tail_img'   => isset($timg_src) ? $timg_src : '',
        // 순정과 같게 conv_content 를 거친다 (줄바꿈·이미지 태그 변환) → {!! !!}
        'head_html'  => isset($fm['fm_head_html']) ? conv_content($fm['fm_head_html'], 1) : '',
        'tail_html'  => isset($fm['fm_tail_html']) ? conv_content($fm['fm_tail_html'], 1) : '',
        'is_admin'   => (bool)$is_admin,
        'admin_href' => isset($admin_href) ? $admin_href : '',
        'total'      => (int)$total_count,
        'page'       => (int)$page,
        'total_page' => (int)$total_page,
        'page_href'  => G5_BBS_URL.'/faq.php?fm_id='.$fm_id.'&amp;stx='.urlencode($stx).'&amp;page=',
        'action'     => G5_BBS_URL.'/faq.php',
    ));
}

// ── 설문조사 결과 (bbs/poll_result.php)
// 순정이 백분율·막대 길이·기타의견·지난설문까지 이미 계산해 둔다. 여기서는 옮기기만 한다.
function g5_map_poll_result()
{
    global $po, $po_id, $list, $list2, $list3, $total_po_cnt, $is_etc, $po_etc, $name;
    global $is_admin, $is_guest, $member;

    $options = array();
    foreach ((array)$list as $row) {
        $options[] = array(
            'num'     => $row['num'],
            'content' => $row['content'],
            'cnt'     => (int)$row['cnt'],
            'rate'    => (float)$row['rate'],   // 백분율
            'bar'     => (int)$row['bar'],      // 최다 항목 대비 막대 길이
        );
    }

    $etc = array();
    foreach ((array)$list2 as $row) {
        $etc[] = array(
            'name'     => $row['name'],      // 사이드뷰 HTML → {!! !!}
            'idea'     => $row['idea'],
            'datetime' => $row['datetime'],
            'del'      => $row['del'],       // 순정이 만든 <a> 여는 태그 → {!! !!}
        );
    }

    $others = array();
    foreach ((array)$list3 as $row) {
        if ((int)$row['po_id'] === (int)$po_id) continue;   // 지금 보고 있는 설문은 뺀다
        $others[] = array(
            'href'    => G5_BBS_URL.'/poll_result.php?po_id='.(int)$row['po_id'],
            'subject' => $row['subject'],
            'date'    => $row['date'],
        );
    }

    // 비회원 이름 칸에는 순정이 아무 안내도 두지 않아 무엇을 넣는 칸인지 알 수 없다.
    // 회원일 때 $name 은 닉네임 글자 + hidden 이라 아래 치환이 걸리지 않는다.
    $etc_name = $is_etc ? str_replace(
        'name="pc_name" size="10"',
        'name="pc_name" size="10" placeholder="작성자"',
        $name
    ) : '';

    g5_view('bbs.poll_result', array(
        'po_id'      => (int)$po_id,
        'subject'    => $po['po_subject'],
        'options'    => $options,
        'total'      => (int)$total_po_cnt,
        'others'     => $others,
        // 기타의견 — po_etc 가 비어 있으면 순정도 그리지 않는다
        'is_etc'     => (bool)$is_etc,
        'etc_label'  => $is_etc ? $po_etc : '',
        'etc_name'   => $etc_name,               // 회원이면 닉네임+hidden, 아니면 입력칸 → {!! !!}
        'etc_items'  => $etc,
        'etc_action' => G5_BBS_URL.'/poll_etc_update.php',
        // 의견 남기기는 권한이 될 때만 (순정 스킨도 같은 조건으로 폼을 감춘다)
        'etc_can'    => (int)$member['mb_level'] >= (int)$po['po_level'],
        // 비회원 캡차 — 순정과 같게 붙인다. 다만 bbs/poll_etc_update.php 는
        // 이 값을 검사하지 않는다 (순정도 클라이언트 JS 로만 막는다)
        'captcha_html' => ($is_etc && $is_guest && function_exists('captcha_html')) ? captcha_html() : '',
        'captcha_js'   => ($is_etc && $is_guest && function_exists('chk_captcha_js')) ? chk_captcha_js() : '',
        'admin_href' => ($is_admin === 'super') ? G5_ADMIN_URL.'/poll_form.php?w=u&po_id='.(int)$po_id : '',
    ));
}

// ── 현재접속자 (bbs/current_connect.php)
// 순정이 이미 회원/비회원을 갈라 name 을 만들어 둔다 (비회원은 IP, 관리자에게만 온전히 보인다).
function g5_map_connect()
{
    global $list, $config;

    $items = array();
    foreach ((array)$list as $row) {
        $items[] = array(
            'num'      => $row['num'],
            'name'     => $row['name'],        // 회원이면 사이드뷰 HTML → {!! !!}
            'is_member'=> !empty($row['mb_id']),
            'location' => $row['lo_location'], // 순정이 넣는 링크 HTML → {!! !!}
            'url'      => $row['lo_url'],
        );
    }

    g5_view('bbs.connect', array(
        'items' => $items,
        'total' => count($items),
    ));
}

// ── 비밀번호 재설정 (bbs/password_reset.php)
// 본인인증으로 아이디/비밀번호를 찾은 뒤 새 비밀번호를 정하는 화면.
// 여기 오기 전에 순정이 세션(ss_cert_mb_id)으로 본인 확인을 끝냈다.
function g5_map_password_reset()
{
    global $action_url;

    g5_view('bbs.password_reset', array(
        'action' => $action_url,
        'mb_id'  => isset($_POST['mb_id']) ? get_text($_POST['mb_id']) : '',
    ));
}

// ── 1:1 문의 목록 (bbs/qalist.php)
// 순정 qa 는 게시판과 별개 테이블·별개 설정(qa_config)을 쓴다. 답변 여부가 핵심 상태다.
function g5_map_qa_list()
{
    global $list, $total_count, $page, $total_page, $qstr, $stx, $sca, $sfl;
    global $write_href, $list_href, $qaconfig, $category_option, $is_admin;

    $items = array();
    foreach ((array)$list as $row) {
        $items[] = array(
            'num'       => $row['num'],
            'category'  => $row['category'],
            'subject'   => $row['subject'],      // 검색어 강조 HTML 가능 → {!! !!}
            'href'      => $row['view_href'],
            'name'      => $row['name'],
            'date'      => $row['date'],
            'has_file'  => !empty($row['icon_file']),
            'answered'  => !empty($row['qa_status']),
        );
    }

    g5_view('bbs.qa_list', array(
        'items'      => $items,
        'total'      => (int)$total_count,
        'page'       => (int)$page,
        'total_page' => (int)$total_page,
        'page_href'  => G5_BBS_URL.'/qalist.php'.$qstr.'&amp;page=',
        'write_href' => $write_href,
        'list_href'  => $list_href,
        'search'     => array('sfl' => $sfl, 'stx' => $stx, 'sca' => $sca),
        'categories' => g5_pro_qa_categories($qaconfig, $sca),
        'head'       => g5_pro_captured('qa_head'),   // 관리자가 넣은 상단 HTML → {!! !!}
        'tail'       => g5_pro_captured('qa_tail'),
        'is_admin'   => (bool)$is_admin,
    ));
}

// 1:1문의 분류 — 설정에 줄바꿈으로 적어 둔 것을 링크로 만든다
function g5_pro_qa_categories($qaconfig, $sca)
{
    $out = array();
    $raw = isset($qaconfig['qa_category']) ? trim($qaconfig['qa_category']) : '';
    if (!$raw) return $out;
    foreach (array_filter(array_map('trim', explode('|', $raw))) as $c) {
        $out[] = array(
            'name'   => $c,
            'href'   => G5_BBS_URL.'/qalist.php?sca='.urlencode($c),
            'active' => ($sca === $c),
        );
    }
    return $out;
}

// ── 1:1 문의 읽기 (bbs/qaview.php)
function g5_map_qa_view()
{
    global $view, $answer, $qstr, $list_href, $write_href, $update_href, $delete_href;
    global $answer_update_href, $answer_delete_href, $prev_href, $next_href, $is_admin;
    global $editor_html, $editor_js, $is_dhtml_editor, $html_value, $html_checked;
    global $token, $qaconfig, $sca, $stx, $page;

    // 답변 등록 폼 — 순정은 이 자리에서만 답변을 받는다. qawrite.php 는 w=a 를 거부하므로
    // (bbs/qawrite.php:6) 이 폼이 없으면 관리자가 답변할 길이 아예 없어진다.
    // 조건은 순정 view.skin.php 와 같다 — 질문글이고, 아직 답변이 안 달렸을 때.
    $show_answer_form = empty($view['qa_type']) && !(!empty($view['qa_status']) && !empty($answer['qa_id']));

    $option_hidden = '';
    $options = array();
    if ($is_dhtml_editor) {
        $option_hidden = '<input type="hidden" name="qa_html" value="1">';
    } else {
        $options[] = array('name' => 'qa_html', 'value' => $html_value, 'label' => 'html', 'checked' => (bool)$html_checked);
    }

    $files = array();
    for ($i = 0; $i < (int)(isset($view['download_count']) ? $view['download_count'] : 0); $i++) {
        if (empty($view['download_href'][$i])) continue;
        $files[] = array(
            'href'   => $view['download_href'][$i],
            'source' => $view['download_source'][$i],
        );
    }

    g5_view('bbs.qa_view', array(
        'item' => array(
            'category' => $view['category'],
            'subject'  => $view['subject'],
            'content'  => $view['content'],       // conv_content 완료 → {!! !!}
            'name'     => $view['name'],
            'datetime' => $view['datetime'],
            'answered' => !empty($view['qa_status']),
            'is_answer'=> !empty($view['qa_type']),
        ),
        'images'  => isset($view['img_file']) ? (array)$view['img_file'] : array(),
        'files'   => $files,
        'answer'  => (isset($answer['qa_id']) && $answer['qa_id'])
                     ? array('content' => conv_content($answer['qa_content'], $answer['qa_html']),
                             'datetime'=> $answer['qa_datetime'])
                     : null,
        'links' => array(
            'list'   => $list_href,
            'write'  => $write_href,
            'update' => isset($update_href) ? $update_href : '',
            'delete' => isset($delete_href) ? $delete_href : '',
            'prev'   => isset($prev_href) ? $prev_href : '',
            'next'   => isset($next_href) ? $next_href : '',
            'answer_update' => $answer_update_href,
            'answer_delete' => $answer_delete_href,
        ),
        'answer_form' => array(
            'show'        => $show_answer_form,
            'action'      => G5_BBS_URL.'/qawrite_update.php',
            'token'       => $token,
            'qa_id'       => $view['qa_id'],
            'editor_html' => $editor_html,      // 순정 에디터/textarea HTML → {!! !!}
            'editor_js'   => $editor_js,        // submit 검사 안에 들어갈 조각
            'option_hidden' => $option_hidden,  // 에디터를 쓸 때의 qa_html 숨은 값 → {!! !!}
            'options'     => $options,          // 에디터를 안 쓸 때의 html 체크박스
            'use_file'    => !empty($qaconfig['qa_use_upload']),
            'params'      => array('sca' => $sca, 'stx' => $stx, 'page' => $page),
        ),
        'head'     => g5_pro_captured('qa_head'),
        'tail'     => g5_pro_captured('qa_tail'),
        'is_admin' => (bool)$is_admin,
    ));
}

// ── 1:1 문의 쓰기 (bbs/qawrite.php) — w 값으로 신규·수정·답변·추가질문이 갈린다
function g5_map_qa_write()
{
    global $w, $write, $qa_id, $action_url, $category_option, $qaconfig, $token, $qstr, $page, $sca, $stx, $sfl;
    global $editor_html, $editor_js, $is_dhtml_editor, $html_value, $html_checked;

    // 순정 qa 스킨과 같은 규칙 — 에디터를 쓰면 qa_html 을 숨은 값으로 못박고,
    // 아니면 체크박스로 고르게 한다. 이 값이 빠지면 본문이 통째로 이스케이프돼 보인다.
    $option_hidden = '';
    $options = array();
    if ($is_dhtml_editor) {
        $option_hidden = '<input type="hidden" name="qa_html" value="1">';
    } else {
        $options[] = array('name' => 'qa_html', 'value' => $html_value, 'label' => 'html', 'checked' => (bool)$html_checked);
    }

    g5_view('bbs.qa_write', array(
        'w'        => $w,
        'title'    => ($w === 'u' ? '문의 수정' : ($w === 'a' ? '답변 등록' : ($w === 'r' ? '추가 질문' : '문의하기'))),
        'action'   => $action_url,
        'token'    => $token,
        'qa_id'    => $qa_id,
        'write'    => array(
            'subject'    => isset($write['qa_subject']) ? $write['qa_subject'] : '',
            'content'    => isset($write['qa_content']) ? $write['qa_content'] : '',
            'category'   => isset($write['qa_category']) ? $write['qa_category'] : '',
            'email'      => isset($write['qa_email']) ? $write['qa_email'] : '',
            'hp'         => isset($write['qa_hp']) ? $write['qa_hp'] : '',
            'email_recv' => !empty($write['qa_email_recv']),
            'sms_recv'   => !empty($write['qa_sms_recv']),
            'html'       => !empty($write['qa_html']),
        ),
        'category_option' => $category_option,   // 순정 option HTML → {!! !!}
        'editor_html'  => $editor_html,          // 순정 에디터/textarea HTML → {!! !!}
        'editor_js'    => $editor_js,            // 완성된 스크립트가 아니라 submit 검사에 들어갈 조각
        'option_hidden'=> $option_hidden,        // 에디터를 쓸 때의 qa_html 숨은 값 → {!! !!}
        'options'      => $options,              // 에디터를 안 쓸 때의 html 체크박스
        'use_file'   => !empty($qaconfig['qa_use_upload']),
        'list_href'  => G5_BBS_URL.'/qalist.php'.$qstr,
        'params'     => array('page' => $page, 'sca' => $sca, 'stx' => $stx, 'sfl' => $sfl),
        'head'       => g5_pro_captured('qa_head'),
        'tail'       => g5_pro_captured('qa_tail'),
    ));
}

// ── 기존 회원 본인인증 (bbs/member_cert_refresh.php)
// 인증 수단마다 여는 창의 주소·종류가 다르다. 순정 스킨은 그 분기를 뷰 안에서 PHP switch 로
// 풀었는데, 여기서는 값으로 정리해 넘긴다 — 뷰가 인증사 이름을 몰라도 되게 한다.
function g5_map_cert_refresh()
{
    global $config, $member, $action_url, $w;

    $ways = array();

    // 간편인증 (이니시스) — 버튼이 여럿일 수 있어 data-type 으로 기관을 가른다
    if (!empty($config['cf_cert_simple'])) {
        $ways[] = array(
            'key'   => 'simple',
            'label' => '간편인증',
            'url'   => G5_INICERT_URL.'/ini_request.php',
            'open'  => 'sa',        // call_sa() 로 연다
            'type'  => '',
        );
    }

    if (!empty($config['cf_cert_hp'])) {
        $hp = array(
            'kcb'    => array(G5_OKNAME_URL.'/hpcert1.php',        'kcb-hp'),
            'kcp'    => array(G5_KCPCERT_URL.'/kcpcert_form.php',  'kcp-hp'),
            'kcp_v2' => array(G5_KCPCERT_V2_URL.'/kcpcert_form.php','kcp_v2-hp'),
            'lg'     => array(G5_LGXPAY_URL.'/AuthOnlyReq.php',    'lg-hp'),
        );
        // 설정값이 위 넷 중 하나가 아니면 버튼을 내지 않는다.
        // 순정은 눌러야 "설정을 해주십시오" 를 띄웠는데, 애초에 못 누르게 하는 편이 낫다.
        if (isset($hp[$config['cf_cert_hp']])) {
            $ways[] = array(
                'key'   => 'hp',
                'label' => '휴대폰 본인확인',
                'url'   => $hp[$config['cf_cert_hp']][0],
                'open'  => 'win',   // certify_win_open() 으로 연다
                'type'  => $hp[$config['cf_cert_hp']][1],
            );
        }
    }

    if (!empty($config['cf_cert_ipin'])) {
        $ways[] = array(
            'key'   => 'ipin',
            'label' => '아이핀 본인확인',
            'url'   => G5_OKNAME_URL.'/ipin1.php',
            'open'  => 'win',
            'type'  => 'kcb-ipin',
        );
    }

    // 순정 스킨이 certify.js 를 여기서 싣는다 — 인증 창 여는 함수(call_sa·certify_win_open)가 그 안에 있다
    if ($ways) add_javascript('<script src="'.G5_JS_URL.'/certify.js?v='.G5_JS_VER.'"></script>', 0);

    g5_view('bbs.member_cert_refresh', array(
        'action'   => $action_url,
        'w'        => $w,
        'url'      => isset($GLOBALS['urlencode']) ? $GLOBALS['urlencode'] : '',
        'member'   => array(
            'mb_id'     => $member['mb_id'],
            'mb_name'   => $member['mb_name'],
            'mb_hp'     => $member['mb_hp'],
            'mb_certify'=> $member['mb_certify'],
            'has_dupinfo' => !empty($member['mb_dupinfo']),
        ),
        'ways'     => $ways,
    ));
}
