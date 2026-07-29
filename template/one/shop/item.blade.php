@extends('layout.default')
@section('content')
<nav class="bbs-cate">
    <a href="{{ G5_SHOP_URL }}/">쇼핑몰</a>
    @if ($category['name'])<a href="{{ $category['href'] }}" class="active">{{ $category['name'] }}</a>@endif
</nav>

<article class="item-detail">
    <div class="item-detail-photo">
        @if ($item['img'])
        <img src="{{ $item['img'] }}" alt="{{ $item['name'] }}">
        @else
        <span class="item-noimg">이미지 준비중</span>
        @endif
    </div>

    <div class="item-detail-body">
        <h2 class="item-detail-name">{{ $item['name'] }}</h2>

        <div class="item-detail-price">
            @if ($item['cust_price'] > $item['price'])
            <del>{{ number_format($item['cust_price']) }}원</del>
            <span class="item-sale-txt">{{ $item['discount'] }}% 할인</span>
            @endif
            <strong>{{ number_format($item['price']) }}<span class="won">원</span></strong>
        </div>

        <dl class="item-spec">
            @if ($item['maker'])<dt>제조사</dt><dd>{{ $item['maker'] }}</dd>@endif
            @if ($item['brand'])<dt>브랜드</dt><dd>{{ $item['brand'] }}</dd>@endif
            @if ($item['model'])<dt>모델</dt><dd>{{ $item['model'] }}</dd>@endif
            @if ($item['origin'])<dt>원산지</dt><dd>{{ $item['origin'] }}</dd>@endif
            @if ($item['point'])<dt>적립</dt><dd>{{ number_format($item['point']) }}점</dd>@endif
            <dt>배송비</dt>
            <dd>{{ $item['delivery'] ? number_format($item['delivery']).'원' : '무료' }}</dd>
            <dt>재고</dt>
            <dd>{{ $item['is_soldout'] ? '품절' : number_format($item['stock']).'개' }}</dd>
        </dl>

        @if ($item['basic'])
        <div class="item-basic">{!! $item['basic'] !!}</div>
        @endif

        {{-- 순정 구입폼(옵션·수량·장바구니·바로구매) 을 그대로 사용한다 --}}
        <div class="item-form">{!! $form_html !!}</div>
    </div>
</article>

@if ($item['explan'])
<section class="item-explan">
    <h3>상품 상세정보</h3>
    <div class="post-content">{!! $item['explan'] !!}</div>
</section>
@endif

@if (count($related))
<section class="shop-block">
    <header class="bbs-head"><h2>함께 보면 좋은 상품</h2></header>
    @include('partials.shop_items', ['items' => $related])
</section>
@endif
@endsection
