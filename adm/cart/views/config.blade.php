<div class="local_desc01 local_desc">
    <p>배송비는 몰 전체에 하나로 적용되는 정책입니다. 조건부 무료를 충족해도 제주 추가 배송비는 더해집니다.</p>
</div>

<form method="post" action="{{ $action_url }}">
<input type="hidden" name="token" value="">

<div class="tbl_frm01 tbl_wrap">
<table>
    <caption>배송비 정책</caption>
    <tbody>
    <tr>
        <th scope="row">기본 배송비</th>
        <td><input type="text" name="cc_ship_base" value="{{ $cc['cc_ship_base'] }}" size="10" style="text-align:right"> 원</td>
    </tr>
    <tr>
        <th scope="row">조건부 무료배송</th>
        <td><input type="text" name="cc_ship_free" value="{{ $cc['cc_ship_free'] }}" size="10" style="text-align:right"> 원 이상 주문 시 무료 <span class="txt_id">(0 이면 조건부 무료 없음)</span></td>
    </tr>
    <tr>
        <th scope="row">제주 추가 배송비</th>
        <td><input type="text" name="cc_ship_jeju" value="{{ $cc['cc_ship_jeju'] }}" size="10" style="text-align:right"> 원 <span class="txt_id">(배송지 우편번호가 63 으로 시작하면 자동 적용)</span></td>
    </tr>
    </tbody>
</table>
</div>

<div class="tbl_frm01 tbl_wrap">
<table>
    <caption>무통장 입금</caption>
    <tbody>
    <tr>
        <th scope="row">입금 계좌 안내</th>
        <td><input type="text" name="cc_bank" value="{{ $cc['cc_bank'] }}" size="60" placeholder="예) 국민은행 000-00-0000-000 (주)데모"> </td>
    </tr>
    <tr>
        <th scope="row">입금 기한</th>
        <td><input type="text" name="cc_unpaid_days" value="{{ $cc['cc_unpaid_days'] }}" size="5" style="text-align:right"> 일
            <span class="txt_id">지나면 자동 취소하고 재고를 되돌립니다. 무통장 주문은 접수 즉시 재고를 빼 두므로,
            기한이 없으면 입금하지 않은 주문이 재고를 계속 잡고 있습니다. (0 이면 자동 취소하지 않음)</span></td>
    </tr>
    <tr>
        <th scope="row">반품 신청 기간</th>
        <td><input type="text" name="cc_return_days" value="{{ $cc['cc_return_days'] }}" size="5" style="text-align:right"> 일
            <span class="txt_id">배송완료된 날부터 이 기간 안에만 고객이 반품을 신청할 수 있습니다.
            구매확정한 주문은 기간과 상관없이 신청을 받지 않습니다. (0 이면 기간 제한 없음)</span></td>
    </tr>
    </tbody>
</table>
</div>

<div class="local_desc02 local_desc">
    <p>PG 키를 채우면 그 결제수단이 주문서에 나타납니다. 비우면 무통장만 노출됩니다. 키는 DB 에만 저장됩니다.</p>
</div>

<div class="tbl_frm01 tbl_wrap">
<table>
    <caption>이니시스 표준결제</caption>
    <tbody>
    <tr>
        <th scope="row">상점아이디(MID)</th>
        <td><input type="text" name="cc_inicis_mid" value="{{ $cc['cc_inicis_mid'] }}" size="20" placeholder="테스트: INIpayTest"></td>
    </tr>
    <tr>
        <th scope="row">사인키(signKey)</th>
        <td><input type="text" name="cc_inicis_signkey" value="{{ $cc['cc_inicis_signkey'] }}" size="50"></td>
    </tr>
    <tr>
        <th scope="row">INIAPI 키</th>
        <td><input type="text" name="cc_inicis_apikey" value="{{ $cc['cc_inicis_apikey'] }}" size="50" placeholder="주문취소(환불)용 — 가맹점관리자 > INIAPI key. INIpayTest 는 비워두면 됩니다"></td>
    </tr>
    </tbody>
</table>
</div>

<div class="tbl_frm01 tbl_wrap">
<table>
    <caption>토스페이먼츠</caption>
    <tbody>
    <tr>
        <th scope="row">클라이언트 키</th>
        <td><input type="text" name="cc_toss_ckey" value="{{ $cc['cc_toss_ckey'] }}" size="50" placeholder="test_ck_..."></td>
    </tr>
    <tr>
        <th scope="row">시크릿 키</th>
        <td><input type="text" name="cc_toss_skey" value="{{ $cc['cc_toss_skey'] }}" size="50" placeholder="test_sk_..."></td>
    </tr>
    </tbody>
</table>
</div>

<div class="btn_confirm01 btn_confirm">
    <button type="submit" class="btn_submit btn">저장</button>
</div>
</form>
