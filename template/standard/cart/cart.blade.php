@extends('layout.default')
@section('content')
<header class="bbs-head">
    <h2>장바구니</h2>

    @if ($ship_notice !== '')
    <div class="bbs-meta">{{ $ship_notice }}</div>
    @endif

</header>

@if (count($items))
<div class="cart-basket">

    @foreach ($items as $it)
    <div class="cart-basket-row {{ (!$it['avail'] || $it['over_stock']) ? 'is-blocked' : '' }}">
        <a href="{{ $it['href'] }}" class="cart-basket-thumb">

            @if ($it['img'])
            <img src="{{ $it['img'] }}" alt="">
            @endif

        </a>
        <div class="cart-basket-info">
            <a href="{{ $it['href'] }}" class="cart-basket-name">{{ $it['it_name'] }}</a>

            @if ($it['opt_label'] !== '')
            <span class="cart-basket-opt">{{ $it['opt_label'] }}</span>
            @endif

            @if (!$it['avail'])
            <span class="cart-basket-warn">지금은 구매할 수 없는 상품입니다 (품절·판매중지)</span>
            @elseif ($it['over_stock'])
            <span class="cart-basket-warn">재고가 {{ number_format($it['sk_qty']) }}개뿐입니다 — 수량을 줄여 주세요</span>
            @endif

            <span class="cart-basket-price">{{ number_format($it['sk_price']) }}<em>원</em></span>
        </div>
        <div class="cart-basket-ctrl">
            <form method="post" action="{{ $action_url }}" class="cart-basket-qty">
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="mode" value="set">
                <input type="hidden" name="bk_id" value="{{ $it['bk_id'] }}">
                <input type="number" name="qty" value="{{ $it['bk_qty'] }}" min="1" max="999">
                <button type="submit" class="btn-ghost">변경</button>
            </form>
            <form method="post" action="{{ $action_url }}">
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="mode" value="del">
                <input type="hidden" name="bk_id" value="{{ $it['bk_id'] }}">
                <button type="submit" class="btn-ghost">삭제</button>
            </form>
            <span class="cart-basket-line">{{ number_format($it['line_total']) }}<em>원</em></span>
        </div>
    </div>
    @endforeach

</div>

<aside class="cart-basket-sum">
    <dl>
        <dt>상품 합계</dt>
        <dd>{{ number_format($total) }}원</dd>
        <dt>배송비</dt>
        <dd>주문서에서 계산 (기본 {{ number_format($ship_base) }}원)</dd>
    </dl>

    @if ($buyable > 0)
    <a href="{{ $checkout_href }}" class="cart-cta">주문하기</a>
    @else
    <span class="cart-cta is-disabled">주문 가능한 상품이 없습니다</span>
    @endif

</aside>
@else
<p class="empty">장바구니가 비어 있습니다.</p>
<p style="text-align:center"><a href="{{ $list_href }}" class="cart-cta">상품 보러 가기</a></p>
@endif
@endsection
