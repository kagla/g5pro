<?php
include_once('./_common.php');

// g5pro 직통 화면. 관리자 화면은 이 프로젝트 범위 밖이라 순정 그대로 둔다.
if (!defined('G5_IS_ADMIN') && !defined('G5_PRO_PAGE'))
    define('G5_PRO_PAGE', true);

include_once(G5_PATH.'/head.sub.php');

$pattern1 = "/[\<\>\'\"\\\'\\\"\(\)]/";
$pattern2 = "/\r\n|\r|\n|[^\x20-\x7e]/";

$url1 = isset($url1) ? preg_replace($pattern1, "", clean_xss_tags($url1, 1)) : '';
$url1 = preg_replace($pattern2, "", $url1);
$url2 = isset($url2) ? preg_replace($pattern1, "", clean_xss_tags($url2, 1)) : '';
$url2 = preg_replace($pattern2, "", $url2);
$url3 = isset($url3) ? preg_replace($pattern1, "", clean_xss_tags($url3, 1)) : '';
$url3 = preg_replace($pattern2, "", $url3);

$msg = isset($msg) ? $msg : '';
$header = isset($header) ? $msg : '';
$confirm_msg = str_replace(array('<br>', '<br/>', '<br />'), "\n", $msg);
$confirm_msg = strip_tags($confirm_msg);
$js_replace = array('\\' => '\\\\', '"' => '\\"', "'" => '\\u0027', '/' => '\\/', "\r" => '\\r', "\n" => '\\n', "\t" => '\\t', '<' => '\\u003C', '>' => '\\u003E', '&' => '\\u0026', "\xE2\x80\xA8" => '\\u2028', "\xE2\x80\xA9" => '\\u2029');
$js_confirm_msg = function_exists('get_js_safe_string') ? get_js_safe_string($confirm_msg) : '"'.strtr((string)$confirm_msg, $js_replace).'"';
$js_url1 = function_exists('get_js_safe_string') ? get_js_safe_string($url1) : '"'.strtr((string)$url1, $js_replace).'"';
$js_url2 = function_exists('get_js_safe_string') ? get_js_safe_string($url2) : '"'.strtr((string)$url2, $js_replace).'"';

// url 체크
check_url_host($url1);
check_url_host($url2);
check_url_host($url3);

// g5pro — confirm 스크립트는 순정과 같고, 감싸는 화면만 blade 로 그린다
if (pro_takeover()) {

    $pro_script = 'var conf = '.$js_confirm_msg.";\n"
                  . "if (confirm(conf)) {\n    document.location.replace(".$js_url1.");\n"
                  . "} else {\n    document.location.replace(".$js_url2.");\n}";

    g5_map_confirm($pro_script, nl2br(get_text(strip_tags($msg))), get_text(strip_tags($header)), $url1, $url2, $url3);

} else {
?>

<script>
var conf = <?php echo $js_confirm_msg; ?>;
if (confirm(conf)) {
    document.location.replace(<?php echo $js_url1; ?>);
} else {
    document.location.replace(<?php echo $js_url2; ?>);
}
</script>

<noscript>
<article id="confirm_check">
<header>
    <hgroup>
        <h1><?php echo get_text(strip_tags($header)); ?></h1> <!-- 수행 중이던 작업 내용 -->
        <h2>아래 내용을 확인해 주세요.</h2>
    </hgroup>
</header>
<p>
    <?php echo get_text(strip_tags($msg)); ?>
</p>

<a href="<?php echo $url1; ?>">확인</a>
<a href="<?php echo $url2; ?>">취소</a><br><br>
<a href="<?php echo $url3; ?>">돌아가기</a>
</article>
</noscript>

<?php
} // g5pro — 순정 출력 끝

include_once(G5_PATH.'/tail.sub.php');
