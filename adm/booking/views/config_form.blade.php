<div class="local_desc01 local_desc">
    <p>예약 전반의 규칙과 결제 정보를 설정합니다. 저장하면 즉시 예약 화면에 적용됩니다.</p>
</div>

<form name="fbookingconfig" id="fbookingconfig" action="./config_update.php" method="post" autocomplete="off">
{{-- 토큰 값은 admin.js 가 제출 순간 ajax.token.php 에서 받아 채운다 (관리자 폼 공통 관례) --}}
<input type="hidden" name="token" value="">

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption>숙박 규칙</caption>
        <colgroup><col class="grid_4"><col></colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="bc_checkin_time">체크인 시간</label></th>
            <td><input type="text" name="bc_checkin_time" value="{{ $bc['bc_checkin_time'] }}" id="bc_checkin_time" required class="frm_input required" size="6" maxlength="5" placeholder="15:00">
                <span class="frm_info">24시간제 HH:MM 으로 적습니다.</span></td>
        </tr>
        <tr>
            <th scope="row"><label for="bc_checkout_time">체크아웃 시간</label></th>
            <td><input type="text" name="bc_checkout_time" value="{{ $bc['bc_checkout_time'] }}" id="bc_checkout_time" required class="frm_input required" size="6" maxlength="5" placeholder="11:00"></td>
        </tr>
        <tr>
            <th scope="row"><label for="bc_sameday_deadline">당일 예약 마감 시각</label></th>
            <td><input type="text" name="bc_sameday_deadline" value="{{ $bc['bc_sameday_deadline'] }}" id="bc_sameday_deadline" required class="frm_input required" size="6" maxlength="5" placeholder="18:00">
                <span class="frm_info">이 시각을 넘기면 오늘 들어오는 예약을 받지 않습니다.</span></td>
        </tr>
        <tr>
            <th scope="row"><label for="bc_hold_minutes">결제 대기 시간</label></th>
            <td><input type="number" name="bc_hold_minutes" value="{{ $bc['bc_hold_minutes'] }}" id="bc_hold_minutes" class="frm_input" size="5" min="1"> 분
                <span class="frm_info">예약을 잡아 둔 채 결제를 기다리는 시간입니다. 지나면 자리를 다시 풉니다.</span></td>
        </tr>
        <tr>
            <th scope="row"><label for="bc_open_months">예약 오픈 기간</label></th>
            <td><input type="number" name="bc_open_months" value="{{ $bc['bc_open_months'] }}" id="bc_open_months" class="frm_input" size="5" min="1"> 개월
                <span class="frm_info">오늘부터 몇 개월 뒤까지 예약을 받을지 정합니다.</span></td>
        </tr>
        <tr>
            <th scope="row"><label for="bc_min_nights">최소 숙박</label></th>
            <td><input type="number" name="bc_min_nights" value="{{ $bc['bc_min_nights'] }}" id="bc_min_nights" class="frm_input" size="5" min="1"> 박</td>
        </tr>
        <tr>
            <th scope="row"><label for="bc_max_nights">최대 숙박</label></th>
            <td><input type="number" name="bc_max_nights" value="{{ $bc['bc_max_nights'] }}" id="bc_max_nights" class="frm_input" size="5" min="1"> 박
                <span class="frm_info">최소보다 작게 적으면 최소값으로 맞춰 저장됩니다.</span></td>
        </tr>
        </tbody>
    </table>
</div>

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption>취소·환불</caption>
        <colgroup><col class="grid_4"><col></colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="bc_cancel_policy">취소 수수료 단계</label></th>
            <td><textarea name="bc_cancel_policy" id="bc_cancel_policy" rows="6" class="frm_input" style="width:100%">{{ $bc['bc_cancel_policy'] }}</textarea>
                <span class="frm_info">한 줄에 하나씩 <strong>남은일수:환불율</strong> 로 적습니다. 예를 들어 <code>7:100</code> 은 체크인 7일 전까지 취소하면 100% 환불이라는 뜻입니다.</span>
                <span class="frm_info">남은 일수가 큰 줄부터 차례로 적으면 읽기 쉽습니다. (예: 7:100 / 3:50 / 1:30 / 0:0)</span></td>
        </tr>
        <tr>
            <th scope="row"><label for="bc_refund_terms">취소·환불 규정 문구</label></th>
            <td><textarea name="bc_refund_terms" id="bc_refund_terms" rows="8" class="frm_input" style="width:100%">{{ $bc['bc_refund_terms'] }}</textarea>
                <span class="frm_info">예약 화면에 그대로 보여 줄 안내 문구입니다.</span></td>
        </tr>
        </tbody>
    </table>
</div>

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption>카드결제 (KG이니시스)</caption>
        <colgroup><col class="grid_4"><col></colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="bc_card_test">테스트 결제</label></th>
            <td>
                <input type="checkbox" name="bc_card_test" value="1" id="bc_card_test" {{ $bc['bc_card_test'] ? 'checked' : '' }}>
                <label for="bc_card_test">테스트 결제로 진행</label>
                <span class="frm_info" id="bc_card_test_info" style="{{ $bc['bc_card_test'] ? '' : 'display:none' }}">
                    이니시스 테스트 상점 정보(MID·키)를 자동으로 사용합니다. 아래 실 결제 정보는 입력하지 않아도 됩니다.
                    실제 결제를 받으려면 체크를 풀고 아래 값을 채우십시오.
                </span>
                <span class="frm_info" id="bc_card_real_info" style="{{ $bc['bc_card_test'] ? 'display:none' : '' }}">
                    실 결제로 동작합니다. 아래 값이 비어 있으면 결제창이 열리지 않습니다.
                </span>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bc_inicis_mid">상점아이디(MID)</label></th>
            <td><input type="text" name="bc_inicis_mid" value="{{ $bc['bc_inicis_mid'] }}" id="bc_inicis_mid" class="frm_input" size="30" maxlength="20"></td>
        </tr>
        <tr>
            <th scope="row"><label for="bc_inicis_sign_key">웹 결제 사인키</label></th>
            <td><input type="text" name="bc_inicis_sign_key" value="{{ $bc['bc_inicis_sign_key'] }}" id="bc_inicis_sign_key" class="frm_input" size="60" maxlength="64">
                <span class="frm_info">이니시스 상점관리자 &gt; 상점정보에서 확인합니다.</span></td>
        </tr>
        <tr>
            <th scope="row"><label for="bc_inicis_iniapi_key">INIAPI 키</label></th>
            <td><input type="text" name="bc_inicis_iniapi_key" value="{{ $bc['bc_inicis_iniapi_key'] }}" id="bc_inicis_iniapi_key" class="frm_input" size="40" maxlength="64">
                <span class="frm_info">환불(부분취소)에 쓰는 키입니다.</span></td>
        </tr>
        <tr>
            <th scope="row"><label for="bc_inicis_iniapi_iv">INIAPI IV</label></th>
            <td><input type="text" name="bc_inicis_iniapi_iv" value="{{ $bc['bc_inicis_iniapi_iv'] }}" id="bc_inicis_iniapi_iv" class="frm_input" size="40" maxlength="64"></td>
        </tr>
        </tbody>
    </table>
</div>

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption>알림</caption>
        <colgroup><col class="grid_4"><col></colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="bc_admin_email">업주 알림 이메일</label></th>
            <td><input type="text" name="bc_admin_email" value="{{ $bc['bc_admin_email'] }}" id="bc_admin_email" class="frm_input" size="40" maxlength="255">
                <span class="frm_info">예약·취소 안내를 함께 받을 주소입니다. 비워 두면 업주 알림을 보내지 않습니다.</span></td>
        </tr>
        </tbody>
    </table>
</div>

<div class="btn_confirm01 btn_confirm">
    <input type="submit" value="확인" class="btn_submit btn">
</div>
</form>

<script>
jQuery(function($) {
    // 테스트 결제를 켜면 아래 실 키를 채우지 않아도 된다는 것을 그 자리에서 알려 준다
    $("#bc_card_test").on("change", function() {
        $("#bc_card_test_info").toggle(this.checked);
        $("#bc_card_real_info").toggle(!this.checked);
    });
});
</script>
