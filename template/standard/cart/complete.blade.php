@extends('layout.default')
@section('content')
<div class="cart-complete">
    <header class="cart-complete-head">
        <h2>주문이 접수됐습니다</h2>
        <p>주문번호 <strong>{{ $order['od_no'] }}</strong> · <span class="cart-status is-{{ cart_order_status_tone($order['od_status']) }}">{{ $status_label }}</span></p>
    </header>

    @if ($order['od_pay_method'] === 'bank')
    <section class="cart-co-sec cart-complete-bank">
        <h3>입금 안내</h3>
        <p class="cart-complete-amount">입금하실 금액 <strong>{{ number_format($order['od_total']) }}원</strong></p>

        @if ($bank !== '')
        <p>{{ $bank }}</p>
        @endif

        <p class="cart-co-note">입금자명: {{ $order['od_depositor'] }} — 입금 확인 후 배송이 시작됩니다.</p>
    </section>
    @endif

    <section class="cart-co-sec">
        <h3>주문 상품</h3>

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
        @if ((int)$order['od_coupon'] > 0)
        <div class="cart-complete-line is-sub">
            <span>쿠폰 할인</span>
            <span>-{{ number_format($order['od_coupon']) }}원</span>
        </div>
        @endif
        <div class="cart-complete-line is-total">
            <span>결제 금액</span>
            <span>{{ number_format($order['od_total']) }}원</span>
        </div>
    </section>

    <section class="cart-co-sec">
        <h3>배송지</h3>
        <p>받는분 {{ $order['od_recv_name'] !== '' ? $order['od_recv_name'] : $order['od_name'] }} · {{ $order['od_recv_hp'] !== '' ? $order['od_recv_hp'] : $order['od_hp'] }}</p>
        <p>({{ $order['od_zip'] }}) {{ $order['od_addr1'] }} {{ $order['od_addr2'] }}</p>

        @if ($order['od_memo'] !== '')
        <p class="cart-co-note">요청사항: {{ $order['od_memo'] }}</p>
        @endif

    </section>

    <p style="text-align:center"><a href="{{ $home_href }}" class="cart-cta">쇼핑 계속하기</a></p>
</div>
@endsection
