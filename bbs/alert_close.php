<?php
include_once('./_common.php');

// g5pro 직통 화면. 관리자 화면은 이 프로젝트 범위 밖이라 순정 그대로 둔다.
if (!defined('G5_IS_ADMIN') && !defined('G5_PRO_PAGE'))
    define('G5_PRO_PAGE', true);

include_once(G5_PATH.'/head.sub.php');

$msg = isset($msg) ? strip_tags($msg) : '';

$msg2 = str_replace(array("\\r\\n", "\\n", "\\r"), "<br>", $msg);
$alert_msg = str_replace(array("\\r\\n", "\\n", "\\r"), "\n", $msg);
$js_replace = array('\\' => '\\\\', '"' => '\\"', "'" => '\\u0027', '/' => '\\/', "\r" => '\\r', "\n" => '\\n', "\t" => '\\t', '<' => '\\u003C', '>' => '\\u003E', '&' => '\\u0026', "\xE2\x80\xA8" => '\\u2028', "\xE2\x80\xA9" => '\\u2029');
$js_alert_msg = function_exists('get_js_safe_string') ? get_js_safe_string($alert_msg) : '"'.strtr((string)$alert_msg, $js_replace).'"';

if($error) {
    $header2 = "다음 항목에 오류가 있습니다.";
    $msg3 = "새창을 닫으시고 이전 작업을 다시 시도해 주세요.";
} else {
    $header2 = "다음 내용을 확인해 주세요.";
    $msg3 = "새창을 닫으신 후 서비스를 이용해 주세요.";
}

// g5pro — 알림·창닫기 스크립트는 순정과 같고, 감싸는 화면만 blade 로 그린다
if (pro_takeover()) {

    $pro_script = 'alert('.$js_alert_msg.');'."\n"
                  . "try {\n    window.close();\n} catch(error) {\n    history.back();\n}\n\n"
                  . "setTimeout(function() {\n    if (window.history.length) {\n        window.history.back();\n    }\n}, 500);";

    g5_map_alert_close($pro_script, $msg2, $header2, $msg3);

} else {
?>

<script>
alert(<?php echo $js_alert_msg; ?>);
try {
    window.close();
} catch(error) {
    history.back();
}

setTimeout(function() {
    if (window.history.length) {
        window.history.back();
    }
}, 500);
</script>

<noscript>
<div id="validation_check">
    <h1><?php echo $header2 ?></h1>
    <p class="cbg">
        <?php echo $msg2 ?>
    </p>
    <p class="cbg">
        <?php echo $msg3 ?>
    </p>

</div>

<?php /*
<article id="validation_check">
<header>
    <hgroup>
        <!-- <h1>회원가입 정보 입력 확인</h1> --> <!-- 수행 중이던 작업 내용 -->
        <h1><?php echo $header ?></h1> <!-- 수행 중이던 작업 내용 -->
        <h2><?php echo $header2 ?></h2>
    </hgroup>
</header>
<p>
    <!-- <strong>항목</strong> 오류내역 -->
    <!--
    <strong>이름</strong> 필수 입력입니다. 한글만 입력할 수 있습니다.<br>
    <strong>이메일</strong> 올바르게 입력하지 않았습니다.<br>
    -->
    <?php echo $msg2 ?>
</p>
<p>
    <?php echo $msg3 ?>
</p>

</article>
*/ ?>

</noscript>

<?php
} // g5pro — 순정 출력 끝

include_once(G5_PATH.'/tail.sub.php');
