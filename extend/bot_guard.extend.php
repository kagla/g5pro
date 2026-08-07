<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 비회원 글쓰기 봇 차단 — 브라우저인지 묻는다
 *
 * 260807, 비회원 게시판에 한 시간 간격으로 홍보글을 올리는 봇이 붙었다.
 * kcaptcha 를 12번 시도해 12번 통과했다. 캡차 이미지를 조여도(색 랜덤,
 * 길이 5~6 랜덤, 폰트 6벌) 걸리는 시간이 5~6초 그대로였다.
 * 이미지를 읽어 내는 상대라 "이미지를 읽을 수 있나" 를 묻는 캡차로는 못 거른다.
 *
 * 그런데 로그를 보면 이 봇은 100건이 넘는 요청 동안 css·js·이미지를
 * 단 하나도 받아 가지 않았다. 화면을 파싱하지 않고 필요한 주소만 직접 찍는다.
 * 그래서 질문을 바꾼다 — 브라우저로 왔는가.
 *
 * 토큰은 글쓰기 화면의 HTML 어디에도 없다. 두 걸음을 밟아야 나온다.
 *   1. extend/parts/write_check.js.php 를 받아 간다 — 여기엔 문제만 있고 토큰은 없다
 *   2. 문제를 풀어 extend/parts/write_verify.php 로 보낸다 — 맞아야 토큰이 나온다
 * 파일을 받아 본문을 훑는 것만으로는 얻을 것이 없다. 실행해야 한다.
 * 답에는 화면에 실제로 그려진 칸 수가 섞이므로 화면을 그리지 않으면 계산도 못 한다.
 * 토큰이 없거나 틀리면 글을 저장하지 않는다.
 *
 * 다만 이것은 벽이 아니라 문턱이다. 자바스크립트가 계산할 수 있는 것은 상대도
 * 옮겨 적을 수 있고, 진짜 브라우저를 띄우기 시작하면 이 계열은 전부 무너진다.
 * 끝까지 남는 방어는 행동과 내용 쪽이다 — 같은 IP 의 비회원 글 빈도, 외부링크.
 *
 * 자바스크립트를 요구하는 것이 새 부담은 아니다. 원래도 js/common.js 가
 * write_token.php 를 불러 token 칸을 만들어 넣지 않으면 check_write_token() 이
 * 거부한다. 봇이 write_token.php 를 찌르는 것도 그래서다 — 그 한 군데만 흉내 냈다.
 *
 * 검사 칸은 자동등록방지 칸에 얹으므로 비회원에게만 나온다. 회원은 영향이 없다.
 * 순정 파일은 건드리지 않고 훅 두 개만 쓴다.
 *   - kcaptcha_captcha_html  (plugin/kcaptcha/kcaptcha.lib.php)
 *   - write_update_before    (bbs/write_update.php)
 *
 * 주의: 검사 칸이 captcha_html() 에 얹혀 있으므로 cf_captcha 가 kcaptcha 여야 하고,
 * 게시판 스킨이 비회원에게 자동등록방지 칸을 보여 줘야 한다. 그렇지 않은 게시판이
 * 생기면 그곳 비회원은 전부 막힌다. 게시판을 추가하면 글쓰기 화면 소스에
 * name="bg_k" 가 있는지 확인할 것. 막힌 요청은 data/bot_guard.log 에 남는다.
 */

define('BG_FIELD', 'bg_k');                       // 토큰이 담기는 칸 이름
define('BG_SESSION', 'ss_bg_token');              // 세션에 넣어 두는 토큰
define('BG_CHALLENGE', 'ss_bg_challenge');        // 세션에 넣어 두는 문제
define('BG_JS_URL', G5_URL.'/extend/parts/write_check.js.php');
define('BG_VERIFY_URL', G5_URL.'/extend/parts/write_verify.php');
define('BG_LOG', G5_DATA_PATH.'/bot_guard.log');

function bg_random_hex($bytes = 16)
{
    return function_exists('random_bytes')
        ? bin2hex(random_bytes($bytes))
        : md5(uniqid('', true));
}

/**
 * 문제를 만들어 세션에 넣고 돌려준다. write_check.js.php 에서만 부른다.
 *
 * 세션 안에서는 같은 값을 유지한다. 창을 여러 개 열어도 서로 덮어쓰지 않게 하고,
 * 글을 쓰는 도중에 값이 바뀌어 애먼 사람이 막히는 일을 없앤다.
 */
function bg_issue_challenge()
{
    $challenge = get_session(BG_CHALLENGE);
    if (!$challenge) {
        $challenge = bg_random_hex();
        set_session(BG_CHALLENGE, $challenge);
    }
    return $challenge;
}

/**
 * 답이 맞으면 토큰을 만들어 세션에 넣고 돌려준다. write_verify.php 에서만 부른다.
 * 틀리면 빈 문자열을 돌려준다.
 *
 * 답은 문제를 뒤집은 값에 자동등록방지 칸 수(정상 화면이면 1)를 붙인 것이다.
 * 계산 자체는 단순하지만, 이 값은 응답 본문 어디에도 없으므로 파일을 받아
 * 훑는 것만으로는 얻을 수 없다. 실행하거나 옮겨 적어야 한다.
 */
function bg_answer_challenge($challenge, $answer)
{
    $issued = get_session(BG_CHALLENGE);

    if (!$issued || !$challenge || !hash_equals($issued, $challenge)) {
        bg_log('badchallenge');
        return '';
    }
    if (!hash_equals(strrev($issued).':1', (string)$answer)) {
        bg_log('badanswer', substr((string)$answer, 0, 60));
        return '';
    }

    $token = get_session(BG_SESSION);
    if (!$token) {
        $token = bg_random_hex();
        set_session(BG_SESSION, $token);
    }
    return $token;
}

function bg_log($why, $extra = '')
{
    $line = sprintf("%s\t%s\t%s\t%s\t%s\n",
        date('Y-m-d H:i:s'),
        $why,
        $_SERVER['REMOTE_ADDR'],
        isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 80) : '-',
        $extra);
    @file_put_contents(BG_LOG, $line, FILE_APPEND);
}

// ---------------------------------------------------------------------------
// 1) 자동등록방지 칸 뒤에 빈 칸과 스크립트를 얹는다 (비회원에게만 나온다)
// ---------------------------------------------------------------------------
add_replace('kcaptcha_captcha_html', 'bg_append_field', 10, 2);

function bg_append_field($html, $class = '')
{
    // 칸이 스크립트보다 먼저 있어야 스크립트가 칸을 찾을 수 있다.
    // 값은 비어 있다. 채우는 것은 내려받은 자바스크립트다.
    $html .= "\n".'<input type="hidden" name="'.BG_FIELD.'" id="'.BG_FIELD.'" value="">';
    $html .= "\n".'<script src="'.BG_JS_URL.'"></script>';

    return $html;
}

// ---------------------------------------------------------------------------
// 2) 글이 저장되기 전에 토큰을 본다
// ---------------------------------------------------------------------------
add_event('write_update_before', 'bg_check_write', 10, 4);

function bg_check_write($board, $wr_id, $w, $qstr)
{
    global $is_admin, $member;

    // 회원과 관리자는 검사하지 않는다. 검사 칸이 비회원에게만 나오기 때문이다.
    if ($is_admin) return;
    if (!empty($member['mb_id'])) return;

    // 수정·답글이 아닌 새 글만 본다
    if ($w !== '' && $w !== 'r') return;

    $bo_table = isset($board['bo_table']) ? $board['bo_table'] : '';

    $sent   = isset($_POST[BG_FIELD]) ? trim($_POST[BG_FIELD]) : '';
    $issued = get_session(BG_SESSION);

    // 자바스크립트 파일을 받아 간 적이 없다 — 브라우저로 온 요청이 아니다
    if (!$issued) {
        bg_log('noscript', $bo_table);
        alert('글을 저장하지 못했습니다. 브라우저의 자바스크립트를 켠 뒤 글쓰기 화면을 새로 열어 주세요.');
    }

    // 칸이 비었거나 값이 다르다
    if ($sent === '' || !hash_equals($issued, $sent)) {
        bg_log($sent === '' ? 'notoken' : 'badtoken', $bo_table);
        alert('글을 저장하지 못했습니다. 브라우저의 자바스크립트를 켠 뒤 글쓰기 화면을 새로 열어 주세요.');
    }
}
