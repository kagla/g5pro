<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 자동등록방지 칸의 쓰임새를 손본다.
 *
 * 순정 captcha_html() 은 입력칸에 아무 안내도 두지 않아, 무엇을 옮겨 적는지
 * 아래 회색 보조설명을 읽어야 알 수 있다. 버튼도 "숫자음성듣기"·"새로고침" 이
 * 글자 그대로 나와 캡차보다 시선을 끈다.
 *
 * 화면 배치는 template/standard/assets/style.css 가 맡는다. 여기서는 CSS 로는
 * 만들 수 없는 것만 얹는다 — placeholder, 모바일 숫자 자판, 버튼 툴팁.
 *
 * 파일을 고치지 않고 출력에 얹으므로 순정 마크업은 그대로다. id 도 손대지 않아
 * kcaptcha.js 와의 계약이 깨지지 않는다.
 */

add_replace('kcaptcha_captcha_html', 'captcha_ui_polish', 20, 2);

function captcha_ui_polish($html, $class = '')
{
    // 입력칸 — 안내를 칸 안으로 옮긴다. 순정은 칸 아래 회색 줄(#captcha_info)에만
    // 적어 두는데, 정작 입력할 곳에서 눈을 떼야 읽힌다. 아래 줄은 style.css 에서 감춘다.
    // 휴대전화에서 숫자 자판이 뜨게 하고, 지난 캡차 값이 후보로 뜨지 않게 자동완성은 끈다.
    $html = str_replace(
        'id="captcha_key" required',
        'id="captcha_key" placeholder="숫자를 순서대로 입력하세요" inputmode="numeric" autocomplete="off" required',
        $html
    );

    // 버튼 — 화면에서는 아이콘만 남기므로(style.css) 마우스로 짚었을 때 이름이 뜨게 한다.
    // 버튼 안의 글자는 지우지 않으므로 스크린리더는 그대로 읽는다.
    $html = str_replace('id="captcha_mp3">',    'id="captcha_mp3" title="숫자음성듣기">', $html);
    $html = str_replace('id="captcha_reload">', 'id="captcha_reload" title="새로고침">', $html);

    return $html;
}
