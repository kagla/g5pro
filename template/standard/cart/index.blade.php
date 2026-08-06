@extends('layout.default')
@section('content')
<div class="cart-home">

<form method="get" action="{{ $search_url }}" class="bbs-search cart-home-search">
    <input type="text" name="q" value="" placeholder="어떤 상품을 찾으세요?">
    <button type="submit">검색</button>
</form>

<a href="{{ $all_href }}" class="cart-banner {{ $banner_url === '' ? 'no-photo' : '' }}">

    @if ($banner_url !== '')
    <img src="{{ $banner_url }}" alt="">
    @endif

    <span class="cart-banner-text">
        <strong>새 시즌, 집 단장 시작</strong>
        <em>신상품과 분류별 추천을 한눈에 둘러보세요</em>
        <span class="cart-banner-cta">전체 상품 보기</span>
    </span>
</a>

@if (count($top_cats))
<nav class="cart-cats">

    @foreach ($top_cats as $c)
    <a href="{{ $c['href'] }}">
        <span class="cart-cat-thumb">

            @if ($c['img'] !== '')
            <img src="{{ $c['img'] }}" alt="">
            @else
            <span class="cart-cat-initial">{{ $c['initial'] }}</span>
            @endif

        </span>
        <span class="cart-cat-name">{{ $c['name'] }}</span>
    </a>
    @endforeach

</nav>
@endif

@if (count($new_items))
<section class="cart-sec">
    <header class="cart-sec-head">
        <h3>새로 나왔어요</h3>
        <a href="{{ $all_href }}">더보기 &gt;</a>
    </header>
    <ul class="shop-grid">

        @foreach ($new_items as $it)
        <li class="shop-card">
            <a href="{{ $it['href'] }}">
                <span class="shop-thumb">

                    @if ($it['img'])
                    <img src="{{ $it['img'] }}" alt="{{ $it['it_name'] }}" loading="lazy">
                    @endif

                    @if ((int)$it['it_stock'] === 0)
                    <span class="shop-soldout">품절</span>
                    @endif

                </span>
                <span class="shop-cat-line">{{ $it['ca_name'] }}</span>
                <span class="shop-name">{{ $it['it_name'] }}</span>
                <span class="shop-price">{{ number_format($it['it_price']) }}<em>원</em></span>
            </a>
        </li>
        @endforeach

    </ul>
</section>
@endif

@foreach ($sections as $sec)
<section class="cart-sec">
    <header class="cart-sec-head">
        <h3>{{ $sec['name'] }} 둘러보기</h3>
        <a href="{{ $sec['href'] }}">더보기 &gt;</a>
    </header>
    <ul class="shop-row">

        @foreach ($sec['items'] as $it)
        <li class="shop-card">
            <a href="{{ $it['href'] }}">
                <span class="shop-thumb">

                    @if ($it['img'])
                    <img src="{{ $it['img'] }}" alt="{{ $it['it_name'] }}" loading="lazy">
                    @endif

                    @if ((int)$it['it_stock'] === 0)
                    <span class="shop-soldout">품절</span>
                    @endif

                </span>
                <span class="shop-name">{{ $it['it_name'] }}</span>
                <span class="shop-price">{{ number_format($it['it_price']) }}<em>원</em></span>
            </a>
        </li>
        @endforeach

    </ul>
</section>
@endforeach

@if (!count($new_items))
<p class="empty">아직 노출 중인 상품이 없습니다. 관리자에서 상품을 등록해 보세요.</p>
@endif

</div>
@endsection
