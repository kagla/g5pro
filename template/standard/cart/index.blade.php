@extends('layout.default')
@section('content')
<header class="cart-hero">
    <h2>오늘 뭐가 필요하세요?</h2>
    <p class="cart-hero-sub">신상품과 분류별 추천을 한눈에 둘러보세요.</p>
    <form method="get" action="{{ $search_url }}" class="bbs-search">
        <input type="text" name="q" value="" placeholder="상품 검색">
        <button type="submit">검색</button>
    </form>
</header>

@if (count($top_cats))
<nav class="cart-cats">

    @foreach ($top_cats as $c)
    <a href="{{ $c['href'] }}">
        <span class="cart-cat-circle">{{ $c['initial'] }}</span>
        <span class="cart-cat-name">{{ $c['name'] }}</span>
    </a>
    @endforeach

</nav>
@endif

@if (count($new_items))
<section class="cart-sec">
    <header class="cart-sec-head">
        <h3>신상품</h3>
        <a href="{{ $all_href }}">전체보기</a>
    </header>
    <ul class="shop-grid">

        @foreach ($new_items as $it)
        <li class="shop-card">
            <a href="{{ $it['href'] }}">
                <span class="shop-thumb">

                    @if ($it['img'])
                    <img src="{{ $it['img'] }}" alt="{{ $it['it_name'] }}" loading="lazy">
                    @endif

                </span>
                <span class="shop-name">{{ $it['it_name'] }}</span>
                <span class="shop-price">{{ number_format($it['it_price']) }}원</span>

                @if ((int)$it['it_stock'] === 0)
                <span class="shop-soldout">품절</span>
                @endif

            </a>
        </li>
        @endforeach

    </ul>
</section>
@endif

@foreach ($sections as $sec)
<section class="cart-sec">
    <header class="cart-sec-head">
        <h3>{{ $sec['name'] }}</h3>
        <a href="{{ $sec['href'] }}">전체보기</a>
    </header>
    <ul class="shop-row">

        @foreach ($sec['items'] as $it)
        <li class="shop-card">
            <a href="{{ $it['href'] }}">
                <span class="shop-thumb">

                    @if ($it['img'])
                    <img src="{{ $it['img'] }}" alt="{{ $it['it_name'] }}" loading="lazy">
                    @endif

                </span>
                <span class="shop-name">{{ $it['it_name'] }}</span>
                <span class="shop-price">{{ number_format($it['it_price']) }}원</span>

                @if ((int)$it['it_stock'] === 0)
                <span class="shop-soldout">품절</span>
                @endif

            </a>
        </li>
        @endforeach

    </ul>
</section>
@endforeach

@if (!count($new_items))
<p class="empty">아직 노출 중인 상품이 없습니다. 관리자에서 상품을 등록해 보세요.</p>
@endif
@endsection
