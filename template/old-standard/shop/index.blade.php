@extends('layout.default')
@section('content')
<section class="hero">
    <h2>오늘의 추천 상품 🛍️</h2>
    <p>새로 들어온 상품과 인기 상품을 한눈에 만나보세요</p>
</section>

@if (count($categories))
<nav class="bbs-cate">
    @foreach ($categories as $c)
    <a href="{{ $c['href'] }}">{{ $c['name'] }}</a>
    @endforeach
</nav>
@endif

@forelse ($blocks as $b)
<section class="shop-block">
    <header class="bbs-head">
        <h2><a href="{{ $b['href'] }}">{{ $b['title'] }}</a></h2>
        <a class="btn" href="{{ $b['href'] }}">더보기</a>
    </header>
    @include('partials.shop_items', ['items' => $b['items']])
</section>
@empty
<p class="bbs-empty">등록된 상품이 없습니다. 관리자에서 상품을 추가해 주세요.</p>
@endforelse
@endsection
