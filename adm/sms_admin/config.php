<?php
$sub_menu = "900100";
include_once("./_common.php");

auth_check_menu($auth, $sub_menu, "r");

$g5['title'] = "SMS 기본설정";

if (! (isset($sms5['cf_skin']) && $sms5['cf_skin']))
    $sms5['cf_skin'] = 'basic';

// 업체 선택과 연동 계정 설정은 기본환경설정 > SMS설정 한 곳에서만 한다 —
// 같은 설정이 두 화면에 있으면 어느 쪽이 진실인지 흐려져 관리가 어렵다.
// 여기는 SMS 관리 고유 설정(회신번호)만 맡는다
$sms_use_label = array('' => '사용안함', 'icode' => '아이코드', 'ppurio' => '뿌리오(비즈뿌리오)');
$sms_use = isset($sms_use_label[$config['cf_sms_use']])
    ? $sms_use_label[$config['cf_sms_use']] : $config['cf_sms_use'];

include_once(G5_ADMIN_PATH.'/admin.head.php');
?>

<div class="local_desc01 local_desc">
    <p>현재 SMS 발송 업체: <strong><?php echo get_text($sms_use); ?></strong></p>
    <p>업체 선택과 연동 계정(아이코드·뿌리오) 설정은
        <a href="../config_form.php#anc_cf_sms" class="btn_frmline">환경설정 &gt; 기본환경설정 &gt; SMS설정</a>
        에서 합니다.</p>
    <?php if (!$config['cf_sms_use']) { ?>
    <p><strong>SMS 를 사용하지 않는 상태라 문자를 보낼 수 없습니다.</strong> 위 화면에서 업체를 먼저 선택하십시오.</p>
    <?php } ?>
</div>

<form name="fconfig" method="post" action="./config_update.php">
<input type="hidden" name="token" value="">
<div class="tbl_frm01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?></caption>
    <colgroup>
        <col class="grid_4">
        <col>
    </colgroup>
    <tbody>
    <tr>
        <th scope="row"><label for="cf_phone">회신번호<strong class="sound_only"> 필수</strong></label></th>
        <td>
            <?php echo help("문자 보내기 화면의 기본 회신번호입니다. 발신번호로 사전 등록된 번호와 동일해야 합니다.<br>예) 010-123-4567"); ?>
            <input type="text" name="cf_phone" value="<?php echo isset($sms5['cf_phone']) ? get_sanitize_input($sms5['cf_phone']) : ''; ?>" id="cf_phone" required class="frm_input required" size="13">
        </td>
    </tr>
    </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <input type="submit" value="확인" class="btn_submit btn" accesskey="s">
</div>
</form>

<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');
