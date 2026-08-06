@extends('layout.default')
@section('content')
<div class="cart-complete">
    <header class="cart-complete-head">
        <h2>결제를 진행합니다</h2>
        <p>주문번호 <strong>{{ $order['od_no'] }}</strong> · {{ $method_label }}</p>
    </header>

    <section class="cart-co-sec">
        <h3>결제 금액</h3>

        @foreach ($items as $it)
        <div class="cart-complete-line">
            <span>{{ $it['oi_name'] }}{{ $it['oi_option'] !== '' ? ' ('.$it['oi_option'].')' : '' }} × {{ $it['oi_qty'] }}</span>
            <span>{{ number_format($it['oi_total']) }}원</span>
        </div>
        @endforeach

        <div class="cart-complete-line is-sub">
            <span>배송비</span>
            <span>{{ number_format($order['od_ship_fee']) }}원</span>
        </div>
        <div class="cart-complete-line is-total">
            <span>결제 금액</span>
            <span>{{ number_format($order['od_total']) }}원</span>
        </div>
    </section>

    <p style="text-align:center">
        <button type="button" class="cart-cta" id="cart_pay_btn">결제창 열기</button>
    </p>
</div>

@if ($method === 'inicis')
<form id="cart_pg_form" method="post" style="display:none">

    @foreach ($pg['fields'] as $k => $v)
    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
    @endforeach

</form>
<script src="{{ $pg['js_url'] }}"></script>
<script>
function cartOpenPay() { INIStdPay.pay('cart_pg_form'); }
document.getElementById('cart_pay_btn').addEventListener('click', cartOpenPay);
cartOpenPay();
</script>
@endif

@if ($method === 'toss')
<script src="{{ $pg['js_url'] }}"></script>
<script>
var cartToss = TossPayments('{{ $pg['ckey'] }}');
var cartTossParams = {!! $pg['params_json'] !!};
function cartOpenPay() {
    cartToss.requestPayment('카드', cartTossParams);
}
document.getElementById('cart_pay_btn').addEventListener('click', cartOpenPay);
cartOpenPay();
</script>
@endif
@endsection
