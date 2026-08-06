@extends('layout.default')
@section('content')
<div class="cart-complete">
    <header class="cart-complete-head">
        <h2>주문 상세</h2>
        <p>주문번호 <strong>{{ $order['od_no'] }}</strong> · <span class="cart-status">{{ $status_label }}</span> · {{ substr($order['od_datetime'], 0, 10) }}</p>
    </header>

    @if ($order['od_status'] === 'canceled')
    <section class="cart-co-sec cart-complete-bank">
        <h3>취소된 주문입니다</h3>
        <p>관리자 직권으로 취소되었습니다{{ substr($order['od_canceled_at'], 0, 4) !== '1970' ? ' ('.substr($order['od_canceled_at'], 0, 16).')' : '' }}.</p>

        @if ($order['od_cancel_reason'] !== '')
        <p class="cart-co-note">취소 사유: {{ $order['od_cancel_reason'] }}</p>
        @endif

        @if ($order['od_pay_method'] !== 'bank' && substr($order['od_paid_at'], 0, 4) !== '1970')
        <p class="cart-co-note">결제하신 금액은 카드 승인 취소로 환불되었습니다. 카드사에 따라 며칠 걸릴 수 있습니다.</p>
        @endif

    </section>
    @endif

    @if ($pay_href !== '')
    <section class="cart-co-sec cart-complete-bank">
        <h3>결제 대기 중</h3>
        <p class="cart-co-note">아직 결제가 완료되지 않았습니다.</p>
        <p><a href="{{ $pay_href }}" class="cart-cta">결제하기</a></p>
    </section>
    @endif

    @if ($order['od_pay_method'] === 'bank' && $order['od_status'] === 'unpaid')
    <section class="cart-co-sec cart-complete-bank">
        <h3>입금 안내</h3>
        <p class="cart-complete-amount">입금하실 금액 <strong>{{ number_format($order['od_total']) }}원</strong></p>

        @if ($bank !== '')
        <p>{{ $bank }}</p>
        @endif

        <p class="cart-co-note">입금자명: {{ $order['od_depositor'] }}</p>
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

    <p style="text-align:center"><a href="{{ $list_href }}" class="cart-cta is-line">{{ $is_member ? '주문 내역으로' : '스토어로' }}</a></p>
</div>
@endsection
