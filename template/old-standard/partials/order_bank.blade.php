{{-- 입금 안내 — 주문완료(cart/complete.php)와 주문 상세(cart/order.php)가 함께 쓴다.
     한때 두 화면이 이 칸을 따로 그렸는데, 한쪽만 손보는 일이 반복되면서 같은 주문이
     화면마다 다른 말을 하게 됐다. 그리는 코드는 한 벌이고 주소만 둘이다.

     받는 값: $order · $bank · $can_edit_depositor · $action_url · $token · $ret
     $ret 은 저장 뒤 돌아올 화면('' = 주문 상세, 'complete' = 주문완료). --}}
@if ($order['od_pay_method'] === 'bank' && $order['od_status'] === 'unpaid')
<section class="cart-co-sec cart-complete-bank">
    <h3>입금 안내</h3>
    <p class="cart-complete-amount">입금하실 금액 <strong>{{ number_format($order['od_total']) }}원</strong></p>

    @if ($bank !== '')
    <p>{{ $bank }}</p>
    @endif

    {{-- 입금자명은 손님만 아는 값이다 — 다른 이름으로 이체할 거면 여기서 미리 바꾼다.
         주문 직후가 "아, 회사 이름으로 보낼게요" 가 나오는 바로 그 순간이라 주문완료에도 둔다.
         입금이 확인되면 칸이 닫힌다(그 뒤에 바꾸면 통장 기록과 갈린다) --}}
    @if ($can_edit_depositor)
    <form method="post" action="{{ $action_url }}" class="cart-edit">
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="mode" value="depositor">
        <input type="hidden" name="od_no" value="{{ $order['od_no'] }}">
        <input type="hidden" name="ret" value="{{ $ret }}">
        <label class="sound_only" for="od_depositor">입금자명</label>
        <input type="text" id="od_depositor" name="od_depositor" value="{{ $order['od_depositor'] }}"
               maxlength="50" placeholder="비우면 주문자 이름">
        <button type="submit" class="cart-cta is-line">입금자명 바꾸기</button>
    </form>
    <p class="cart-co-note">가족·회사 이름으로 보내실 거면 미리 바꿔 주세요. 입금이 확인된 뒤에는 바꿀 수 없습니다.</p>
    @else
    <p class="cart-co-note">입금자명: {{ $order['od_depositor'] }}</p>
    @endif

</section>
@endif
