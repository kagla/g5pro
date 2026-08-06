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
    </tbody>
</table>
</div>

<div class="btn_confirm01 btn_confirm">
    <button type="submit" class="btn_submit btn">저장</button>
</div>
</form>
