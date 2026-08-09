@extends('layout.default')
@section('content')
<header class="bbs-head">
    <h2>주문 내역</h2>
    <div class="bbs-meta">전체 {{ number_format($total) }}건</div>
</header>

@if (count($orders))
<div class="cart-orders">

    @foreach ($orders as $od)
    <a href="{{ $od['href'] }}" class="cart-order-row">
        <span class="cart-order-date">{{ substr($od['od_datetime'], 0, 10) }}</span>
        <span class="cart-order-summary">{{ $od['summary'] }}</span>
        <span class="cart-order-total">{{ number_format($od['od_total']) }}원</span>
        <span class="cart-order-status">{{ $od['status_label'] }}</span>
    </a>
    @endforeach

</div>

@if ($total_page > 1)
<nav class="paging">

    @foreach ($pages as $p)
    <a href="{{ $p['href'] }}" class="{{ $p['current'] ? 'current' : '' }}">{{ $p['num'] }}</a>
    @endforeach

</nav>
@endif

@else
<p class="empty">아직 주문이 없습니다.</p>
<p style="text-align:center"><a href="{{ $home_href }}" class="cart-cta">상품 보러 가기</a></p>
@endif
@endsection
