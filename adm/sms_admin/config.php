<?php
$sub_menu = "900100";
include_once("./_common.php");

auth_check_menu($auth, $sub_menu, "r");

$g5['title'] = "SMS 기본설정";

if (!$config['cf_icode_server_ip'])   $config['cf_icode_server_ip'] = '211.172.232.124';
if (!$config['cf_icode_server_port']) $config['cf_icode_server_port'] = '7295';

// 아이코드 토큰키 추가
if( ! isset($config['cf_icode_token_key']) ){
    $sql = "ALTER TABLE `{$g5['config_table']}` 
            ADD COLUMN `cf_icode_token_key` VARCHAR(100) NOT NULL DEFAULT '' AFTER `cf_icode_server_port`; ";
    sql_query($sql, false);
    $config['cf_icode_token_key'] = '';
}

// 배열코드 초기화
$userinfo = array('payment'=>'', 'coin'=>'');

// 아이코드 계정 조회는 아이코드를 쓸 때만 — 뿌리오 모드에서 아이코드 서버를 부를 이유가 없다
if ($config['cf_sms_use'] == 'icode' && $config['cf_icode_id'] && $config['cf_icode_pw'])
{
    $userinfo = get_icode_userinfo($config['cf_icode_id'], $config['cf_icode_pw']);
}

if (!$config['cf_icode_id'])
    $config['cf_icode_id'] = 'sir_';

if (! (isset($sms5['cf_skin']) && $sms5['cf_skin']))
    $sms5['cf_skin'] = 'basic';

include_once(G5_ADMIN_PATH.'/admin.head.php');

?>
<?php if (!($config['cf_icode_pw'] || $config['cf_icode_token_key']) && $config['cf_sms_use'] !== 'ppurio') { ?>
<div class="local_desc01 local_desc">
    <p>
        SMS 기능을 사용하시려면 먼저 아이코드에 서비스 신청을 하셔야 합니다.<br>
        <a href="http://icodekorea.com/res/join_company_fix_a.php?sellid=sir2" target="_blank">아이코드 서비스 신청하기</a>
    </p>
</div>
<?php } ?>

<?php // 한 폼에서 업체를 고르고 그 업체의 설정만 보이게 한다 — 아이코드↔뿌리오 전환이 이 화면에서 된다 ?>
<form name="fconfig" method="post" action="./config_update.php" enctype="multipart/form-data" >
<input type="hidden" name="token" value="">
<input type="hidden" name="cf_icode_server_ip" value="<?php echo $config['cf_icode_server_ip']?>">
<div class="tbl_frm01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?></caption>
    <colgroup>
        <col class="grid_4">
        <col>
    </colgroup>
    <tbody>
    <tr>
        <th scope="row"><label for="cf_sms_use">SMS 사용</label></th>
        <td>
            <select id="cf_sms_use" name="cf_sms_use">
                <option value="" <?php echo get_selected($config['cf_sms_use'], ''); ?>>사용안함</option>
                <option value="icode" <?php echo get_selected($config['cf_sms_use'], 'icode'); ?>>아이코드</option>
                <option value="ppurio" <?php echo get_selected($config['cf_sms_use'], 'ppurio'); ?>>뿌리오(비즈뿌리오)</option>
            </select>
            <?php echo help("기본환경설정 > SMS설정과 같은 값입니다. 어느 화면에서 바꿔도 함께 바뀝니다."); ?>
        </td>
    </tr>
    <tr class="sms_only_ppurio">
        <th scope="row">뿌리오 연동</th>
        <td>
            연동 계정·비밀번호·발신번호는 <a href="../config_form.php#anc_cf_sms" class="btn_frmline">환경설정 &gt; 기본환경설정 &gt; SMS설정</a> 에서 관리합니다.<br>
            SMS/LMS 는 문자 길이(90바이트 기준)에 따라 자동으로 구분되어 발송됩니다.
        </td>
    </tr>
    <tr class="sms_only_icode">
        <th scope="row"><label for="cf_sms_type">SMS 전송유형</label></th>
        <td>
            <?php echo help("전송유형을 SMS로 선택하시면 최대 80바이트까지 전송하실 수 있으며<br>LMS로 선택하시면 90바이트 이하는 SMS로, 그 이상은 ".G5_ICODE_LMS_MAX_LENGTH."바이트까지 LMS로 전송됩니다.<br>요금은 건당 SMS는 16원, LMS는 48원입니다."); ?>
            <select id="cf_sms_type" name="cf_sms_type">
                <option value="" <?php echo get_selected($config['cf_sms_type'], ''); ?>>SMS</option>
                <option value="LMS" <?php echo get_selected($config['cf_sms_type'], 'LMS'); ?>>LMS</option>
            </select>
        </td>
    </tr>
    <tr class="sms_only_icode icode_old_version">
        <th scope="row"><label for="cf_icode_id">아이코드 회원아이디<br>(구버전)<strong class="sound_only"> 필수</strong></label></th>
        <td>
            <?php echo help("아이코드에서 사용하시는 회원아이디를 입력합니다."); ?>
            <input type="text" name="cf_icode_id" value="<?php echo $config['cf_icode_id']; ?>" id="cf_icode_id" class="frm_input">
        </td>
    </tr>
    <tr class="sms_only_icode icode_old_version">
        <th scope="row"><label for="cf_icode_pw">아이코드 비밀번호<br>(구버전)<strong class="sound_only"> 필수</strong></label></th>
        <td>
            <?php echo help("아이코드에서 사용하시는 비밀번호를 입력합니다."); ?>
            <input type="password" name="cf_icode_pw" value="<?php echo $config['cf_icode_pw']; ?>" id="cf_icode_pw" class="frm_input">
        </td>
    </tr>
    <tr class="sms_only_icode icode_old_version <?php if(!(isset($userinfo['payment']) && $userinfo['payment'])){ echo 'cf_tr_hide'; } ?>">
        <th scope="row">요금제<br>(구버전)</th>
        <td>
            <?php
                if ($userinfo['payment'] == 'A') {
                   echo '충전제';
                    echo '<input type="hidden" name="cf_icode_server_port" value="7295">';
                } else if ($userinfo['payment'] == 'C') {
                    echo '정액제';
                    echo '<input type="hidden" name="cf_icode_server_port" value="7296">';
                } else {
                    echo '<input type="hidden" name="cf_icode_server_port" value="7295">';
                }
            ?>
        </td>
    </tr>
    <?php if ($userinfo['payment'] == 'A') { ?>
    <tr class="sms_only_icode icode_old_version">
        <th scope="row">충전 잔액<br>(구버전)</th>
        <td>
            <?php echo number_format($userinfo['coin'])?> 원
            <a href="http://www.icodekorea.com/smsbiz/credit_card_amt.php?icode_id=<?php echo $config['cf_icode_id']; ?>&amp;icode_passwd=<?php echo $config['cf_icode_pw']; ?>" target="_blank" class="btn_frmline">충전하기</a>
        </td>
    </tr>
    <?php } ?>
    <tr class="sms_only_icode icode_json_version">
        <th scope="row"><label for="cf_icode_token_key">아이코드 토큰키<br>(JSON버전)</label></th>
        <td>
            <?php echo help("아이코드 JSON 버전의 경우 아이코드 토큰키를 입력시 실행됩니다.<br>SMS 전송유형을 LMS로 설정시 90바이트 이내는 SMS, 90 ~ 2000 바이트는 LMS 그 이상은 절삭 되어 LMS로 발송됩니다."); ?>
            <input type="text" name="cf_icode_token_key" value="<?php echo $config['cf_icode_token_key']; ?>" id="cf_icode_token_key" class="frm_input" size="40">
            <?php echo help("아이코드 사이트 -> 토큰키관리 메뉴에서 생성한 토큰키를 입력합니다."); ?>
            <br>
            서버아이피 : <?php echo $_SERVER['SERVER_ADDR']; ?>
        </td>
    </tr>
    <tr class="sms_only">
        <th scope="row"><label for="cf_phone">회신번호<strong class="sound_only"> 필수</strong></label></th>
        <td>
            <?php echo help("회신받을 휴대폰 번호를 입력하세요. 회신번호는 발신번호로 사전등록된 번호와 동일해야 합니다.<br>예) 010-123-4567"); ?>
            <input type="text" name="cf_phone" value="<?php echo isset($sms5['cf_phone']) ? get_sanitize_input($sms5['cf_phone']) : ''; ?>" id="cf_phone" class="frm_input" size="13">
        </td>
    </tr>
    </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <input type="submit" value="확인" class="btn_submit btn" accesskey="s">
</div>
</form>

<script>
jQuery(function($) {
    // 고른 업체의 설정 줄만 보인다. 요금제·잔액 줄(cf_tr_hide)은 아이코드 계정 조회가
    // 성공했을 때만 서버가 보여 주므로, JS 로 다시 펴지 않는다
    function sms5_config_toggle() {
        var v = $("#cf_sms_use").val();
        $(".sms_only, .sms_only_icode, .sms_only_ppurio").hide();
        if (v !== "") $(".sms_only").show();
        if (v === "icode") $(".sms_only_icode").not(".cf_tr_hide").show();
        if (v === "ppurio") $(".sms_only_ppurio").show();
    }
    $("#cf_sms_use").on("change", sms5_config_toggle);
    sms5_config_toggle();
});
</script>

<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');