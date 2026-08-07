<?php
// 캡챠 세션값과 비교하여 맞는지? 틀린지? 결과값을 출력합니다.
include_once("_common.php");

$captcha_key = isset($_POST['captcha_key']) ? $_POST['captcha_key'] : '';

$count = (int)get_session("ss_captcha_count");
if ($count >= 10) { // 설정값 이상이면 자동등록방지 입력 문자가 맞아도 오류 처리
    echo false;
} else {
    if( $captcha_key && function_exists('get_string_encrypt') ){
        $ip = md5(sha1($_SERVER['REMOTE_ADDR']));
        $captcha_key = get_string_encrypt($ip.$captcha_key);
    }

    if (get_session("ss_captcha_key") === $captcha_key) {
        echo true;
    } else {
        // 오답일 때만 카운트를 올린다. 정답을 확인하는 정상 사용자는 잠기지 않고,
        // 무차별 추측(오라클 남용)만 세션 전체 시도 횟수 제한에 걸린다.
        set_session("ss_captcha_count", $count + 1);
        echo false;
    }
}