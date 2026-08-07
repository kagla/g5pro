<?php
/**
 * 비회원 글쓰기 관문 1단계 — 풀어야 할 문제를 내려 준다.
 *
 * 토큰은 이 응답 어디에도 없다. 여기 있는 것은 문제뿐이고,
 * 답을 계산해서 write_verify.php 로 보내야 비로소 토큰이 나온다.
 * 파일을 받아 가서 본문을 훑는 것만으로는 얻을 것이 없다.
 *
 * 답은 문제 문자열만이 아니라 글쓰기 폼의 생김새에도 기댄다.
 * 화면을 그리지 않는 쪽은 그 값을 알 수 없다.
 *
 * 짝이 되는 검사는 extend/bot_guard.extend.php 에 있다.
 */

include_once(dirname(dirname(__DIR__)).'/common.php');

header('Content-Type: application/javascript; charset=utf-8');
// 세션이 바뀌면 문제도 바뀐다. 지난 세션 것이 캐시에서 나오면 사람이 막힌다.
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$challenge = bg_issue_challenge();

?>
(function () {
    var el = document.getElementById(<?php echo json_encode(BG_FIELD) ?>);
    if (!el) return;

    var c = <?php echo json_encode($challenge) ?>;

    // 답은 문제를 뒤집은 값과, 이 화면에 실제로 그려진 자동등록방지 칸 수를 엮는다.
    // 화면을 그리지 않으면 뒤쪽 값을 알 수 없다.
    var n = document.getElementsByName('captcha_key').length;
    var a = c.split('').reverse().join('') + ':' + n;

    // 한 번 실패하면 글을 다 쓴 사람이 저장 단계에서 거부당한다. 한 번 더 시도한다.
    function ask(retry) {
        var x = new XMLHttpRequest();
        x.open('POST', <?php echo json_encode(BG_VERIFY_URL) ?>, true);
        x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        x.onload = function () {
            if (x.status === 200 && x.responseText) el.value = x.responseText;
            else if (retry) setTimeout(function () { ask(false); }, 1500);
        };
        x.onerror = function () {
            if (retry) setTimeout(function () { ask(false); }, 1500);
        };
        x.send('c=' + encodeURIComponent(c) + '&a=' + encodeURIComponent(a));
    }
    ask(true);
})();
