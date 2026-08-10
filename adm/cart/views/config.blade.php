<div class="local_desc01 local_desc">
    <p>배송비는 몰 전체에 하나로 적용되는 정책입니다. 제주·도서산간 추가비는 아래 권역 표에서 정합니다.</p>
</div>

<form method="post" action="{{ $action_url }}">
<input type="hidden" name="token" value="">

<div class="tbl_frm01 tbl_wrap">
<table>
    <caption>배송비 정책</caption>
    <tbody>
    <tr>
        <th scope="row">기본 배송비</th>
        <td><input type="text" name="cc_ship_base" value="{{ $cc['cc_ship_base'] }}" size="10" style="text-align:right" class="frm_input"> 원</td>
    </tr>
    <tr>
        <th scope="row">조건부 무료배송</th>
        <td><input type="text" name="cc_ship_free" value="{{ $cc['cc_ship_free'] }}" size="10" style="text-align:right" class="frm_input"> 원 이상 주문 시 무료 <span class="txt_id">(0 이면 조건부 무료 없음)</span></td>
    </tr>
    </tbody>
</table>
</div>

<style>
#cart_zone .frm_input { height: 30px; line-height: 28px; }
#cart_zone td { text-align: center; }
#cart_zone .zone_rm { border: 0; background: none; color: #999; font-size: 15px; cursor: pointer; }
#cart_zone_add { margin: 10px 0 0; }
</style>

<div class="local_desc01 local_desc">
    <p><strong>권역별 추가 배송비</strong> — 배송지 우편번호가 아래 구간에 들면 그만큼 더 받습니다.
       조건부 무료배송을 충족해도 이 추가비는 남습니다(실제 택배 원가가 남는 구간이라 몰 관례를 따릅니다).</p>
    <p>한 이름에 구간을 여러 줄 둘 수 있습니다. 도서 지역은 우편번호가 흩어져 있어서,
       <strong>거래하는 택배사에서 받은 "도서산간 추가운임 지역" 목록</strong>을 보고 채우세요.
       기본으로 넣어 둔 것은 제주(63000~63644)와 울릉군(40200~40240) 둘뿐입니다.</p>
    <p>구간이 겹치면 <strong>가장 비싼 것 하나만</strong> 붙습니다. 우편번호를 짧게 적으면 5자리로 채웁니다
       (예: <code>63</code> → 63000~63999).</p>
</div>

<div class="tbl_head01 tbl_wrap" id="cart_zone">
    <table>
    <caption>권역별 추가 배송비</caption>
    <thead>
    <tr>
        <th scope="col">구간 이름</th>
        <th scope="col">우편번호</th>
        <th scope="col">추가 배송비</th>
        <th scope="col">사용</th>
        <th scope="col">삭제</th>
    </tr>
    </thead>
    <tbody id="cart_zone_body">

    @foreach ($zones as $z)
    <tr>
        <td><input type="text" name="sz[{{ $z['sz_id'] }}][name]" value="{{ $z['sz_name'] }}" size="12" class="frm_input"></td>
        <td><input type="text" name="sz[{{ $z['sz_id'] }}][from]" value="{{ $z['sz_zip_from'] }}" size="6" class="frm_input"> ~
            <input type="text" name="sz[{{ $z['sz_id'] }}][to]" value="{{ $z['sz_zip_to'] }}" size="6" class="frm_input"></td>
        <td><input type="text" name="sz[{{ $z['sz_id'] }}][fee]" value="{{ $z['sz_fee'] }}" size="8" style="text-align:right" class="frm_input"> 원</td>
        <td><input type="checkbox" name="sz[{{ $z['sz_id'] }}][use]" value="1" {{ (int)$z['sz_use'] === 1 ? 'checked' : '' }}></td>
        <td><input type="checkbox" name="sz_del[]" value="{{ $z['sz_id'] }}" class="zone_del"></td>
    </tr>
    @endforeach

    </tbody>
    </table>
</div>

<p id="cart_zone_add"><button type="button" class="btn btn_02" id="cart_zone_add_btn">+ 구간 추가</button></p>

<div class="tbl_frm01 tbl_wrap">
<table>
    <caption>무통장 입금</caption>
    <tbody>
    <tr>
        <th scope="row">입금 계좌 안내</th>
        <td><input type="text" name="cc_bank" value="{{ $cc['cc_bank'] }}" size="60" placeholder="예) 국민은행 000-00-0000-000 (주)데모" class="frm_input"> </td>
    </tr>
    <tr>
        <th scope="row">입금 기한</th>
        <td><input type="text" name="cc_unpaid_days" value="{{ $cc['cc_unpaid_days'] }}" size="5" style="text-align:right" class="frm_input"> 일
            <span class="txt_id">지나면 자동 취소하고 재고를 되돌립니다. 무통장 주문은 접수 즉시 재고를 빼 두므로,
            기한이 없으면 입금하지 않은 주문이 재고를 계속 잡고 있습니다. (0 이면 자동 취소하지 않음)</span></td>
    </tr>
    <tr>
        <th scope="row">반품 신청 기간</th>
        <td><input type="text" name="cc_return_days" value="{{ $cc['cc_return_days'] }}" size="5" style="text-align:right" class="frm_input"> 일
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
        <td><input type="text" name="cc_inicis_mid" value="{{ $cc['cc_inicis_mid'] }}" size="20" placeholder="테스트: INIpayTest" class="frm_input"></td>
    </tr>
    <tr>
        <th scope="row">사인키(signKey)</th>
        <td><input type="text" name="cc_inicis_signkey" value="{{ $cc['cc_inicis_signkey'] }}" size="50" class="frm_input"></td>
    </tr>
    <tr>
        <th scope="row">INIAPI 키</th>
        <td><input type="text" name="cc_inicis_apikey" value="{{ $cc['cc_inicis_apikey'] }}" size="50" placeholder="주문취소(환불)용 — 가맹점관리자  class="frm_input"> INIAPI key. INIpayTest 는 비워두면 됩니다"></td>
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
        <td><input type="text" name="cc_toss_ckey" value="{{ $cc['cc_toss_ckey'] }}" size="50" placeholder="test_ck_..." class="frm_input"></td>
    </tr>
    <tr>
        <th scope="row">시크릿 키</th>
        <td><input type="text" name="cc_toss_skey" value="{{ $cc['cc_toss_skey'] }}" size="50" placeholder="test_sk_..." class="frm_input"></td>
    </tr>
    </tbody>
</table>
</div>

<div class="btn_confirm01 btn_confirm" style="text-align:right">
    <button type="submit" class="btn_submit btn">저장</button>
</div>
</form>

<script>
// 권역 줄 추가 — 몇 개든. type=button 이라 Enter 를 가져가지 않는다(제출 버튼은 하나뿐).
// 아직 저장 안 된 줄은 × 로 그냥 없앤다(지울 것이 없으니 확인도 필요 없다).
$(function () {
    var seq = 0, $body = $('#cart_zone_body');

    $('#cart_zone_add_btn').on('click', function () {
        seq += 1;
        var k = 'new' + seq;
        $body.append(
            '<tr>'
          + '<td><input type="text" name="sz[' + k + '][name]" value="" size="12" class="frm_input" placeholder="예: 도서산간"></td>'
          + '<td><input type="text" name="sz[' + k + '][from]" value="" size="6" class="frm_input" placeholder="40200"> ~ '
          + '<input type="text" name="sz[' + k + '][to]" value="" size="6" class="frm_input" placeholder="40240"></td>'
          + '<td><input type="text" name="sz[' + k + '][fee]" value="" size="8" style="text-align:right" class="frm_input"> 원</td>'
          + '<td><input type="checkbox" name="sz[' + k + '][use]" value="1" checked></td>'
          + '<td><button type="button" class="zone_rm" title="이 줄 없애기">×</button></td>'
          + '</tr>');
        $body.find('tr:last input[name$="[name]"]').trigger('focus');
    });

    $body.on('click', '.zone_rm', function () { $(this).closest('tr').remove(); });

    // 권역 삭제는 되돌릴 수 없으므로 한 번 묻는다
    $('#cart_zone').closest('form').on('submit', function () {
        var n = $body.find('.zone_del:checked').length;
        if (n && !confirm(n + '개 권역을 지웁니다. 그 구간의 추가 배송비는 더 이상 붙지 않습니다.\n계속할까요?')) return false;
        return true;
    });
});
</script>
