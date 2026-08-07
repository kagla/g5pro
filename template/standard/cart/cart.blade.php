@extends('layout.default')
@section('content')
<header class="bbs-head">
    <h2>장바구니</h2>

    @if ($ship_notice !== '')
    <div class="bbs-meta">{{ $ship_notice }}</div>
    @endif

</header>

@if (count($items))
<div class="cart-cart">

    @foreach ($items as $it)
    <div class="cart-cart-row {{ (!$it['avail'] || $it['over_stock']) ? 'is-blocked' : '' }}">
        <a href="{{ $it['href'] }}" class="cart-cart-thumb">

            @if ($it['img'])
            <img src="{{ $it['img'] }}" alt="">
            @endif

        </a>
        <div class="cart-cart-info">
            <a href="{{ $it['href'] }}" class="cart-cart-name">{{ $it['it_name'] }}</a>

            @if ($it['opt_label'] !== '')
            <span class="cart-cart-opt">{{ $it['opt_label'] }}</span>
            @endif

            @if (!$it['avail'])
            <span class="cart-cart-warn">지금은 구매할 수 없는 상품입니다 (품절·판매중지)</span>
            @elseif ($it['over_stock'])
            <span class="cart-cart-warn">재고가 {{ number_format($it['sk_qty']) }}개뿐입니다 — 수량을 줄여 주세요</span>
            @endif

            <span class="cart-cart-price">{{ number_format($it['sk_price']) }}<em>원</em></span>
        </div>
        <div class="cart-cart-ctrl">
            <form method="post" action="{{ $action_url }}" class="cart-cart-qty">
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="mode" value="set">
                <input type="hidden" name="ct_id" value="{{ $it['ct_id'] }}">
                <input type="number" name="qty" value="{{ $it['ct_qty'] }}" min="1" max="999">
                <button type="submit" class="btn-ghost">변경</button>
            </form>
            <form method="post" action="{{ $action_url }}">
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="mode" value="del">
                <input type="hidden" name="ct_id" value="{{ $it['ct_id'] }}">
                <button type="submit" class="btn-ghost">삭제</button>
            </form>
            <span class="cart-cart-line">{{ number_format($it['line_total']) }}<em>원</em></span>
        </div>
    </div>
    @endforeach

</div>

<aside class="cart-cart-sum">
    <dl>
        <dt>상품 합계</dt>
        <dd>{{ number_format($total) }}원</dd>
        <dt>배송비</dt>
        <dd>주문서에서 계산 (기본 {{ number_format($ship_base) }}원)</dd>
    </dl>

    {{-- 재고가 모자란 줄이 하나라도 있으면 주문 단계로 넘기지 않는다. 조용히 빼고 진행하면
         손님은 빠진 줄 모르고 결제한다. 서버(checkout.php)도 같은 것을 막는다. --}}
    @if ($blocked > 0)
    <p class="cart-cart-warn">주문할 수 없는 상품이 {{ number_format($blocked) }}개 있습니다. 수량을 줄이거나 삭제해 주세요.</p>
    <span class="cart-cta is-disabled">주문할 수 없는 상품이 있습니다</span>
    @elseif ($buyable > 0)
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
