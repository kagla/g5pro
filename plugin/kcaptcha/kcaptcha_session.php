<?php
include_once("_common.php");
include_once(dirname(__FILE__).'/kcaptcha_config.php');
include_once('captcha.lib.php');

while(true){
    $keystring='';
    for($i=0;$i<$length;$i++){
        $keystring.=$allowed_symbols[random_int(0,strlen($allowed_symbols)-1)];
    }
    if(!preg_match('/cp|cb|ck|c6|c9|rn|rm|mm|co|do|cl|db|qp|qb|dp|ww/', $keystring)) break;
}

if( $keystring && function_exists('get_string_encrypt') ){
    $ip = md5(sha1($_SERVER['REMOTE_ADDR']));
    $keystring = get_string_encrypt($ip.$keystring);
}

// 실패 카운터(ss_captcha_count)는 이미지 재발급 시 리셋하지 않는다.
// 리셋하면 result.php 오라클을 이미지만 다시 받아 무제한 재시도할 수 있으므로,
// 잠금이 세션 전체에 걸리도록 유지한다. 카운터는 실제 통과(chk_captcha) 시에만 0으로 돌아간다.
set_session("ss_captcha_key", $keystring);
$captcha = new KCAPTCHA();
$captcha->setKeyString(get_session("ss_captcha_key"));