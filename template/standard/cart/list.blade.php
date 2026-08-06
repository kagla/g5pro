@extends('layout.default')
@section('content')
<header class="bbs-head">
    <h2>

        @if ($category)
        <span class="chip">분류</span>{{ $category['ca_name'] }}
        @elseif ($q !== '')
        <span class="chip">검색</span>{{ $q }}
        @else
        전체 상품
        @endif

    </h2>
    <div class="bbs-meta">전체 {{ number_format($total_count) }}개 · {{ $page }} / {{ $total_page }} 페이지</div>
</header>

<form method="get" action="{{ $search_url }}" class="bbs-search">
    <input type="text" name="q" value="{{ $q }}" placeholder="상품 검색">
    <button type="submit">검색</button>
</form>

@if (count($categories))
<nav class="bbs-cate">

    @foreach ($categories as $c)
    <a href="{{ $c['href'] }}">{{ $c['ca_name'] }}</a>
    @endforeach

</nav>
@endif

<nav class="shop-sort">

    @foreach ($sorts as $s)
    <a href="{{ $s['href'] }}" class="{{ $s['active'] ? 'active' : '' }}">{{ $s['name'] }}</a>
    @endforeach

</nav>

@if (count($items))
<ul class="shop-grid">

    @foreach ($items as $it)
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
@else
<p class="empty">조건에 맞는 상품이 없습니다.</p>
@endif

@if ($total_page > 1)
<nav class="paging">

    @foreach ($pages as $p)
    <a href="{{ $p['href'] }}" class="{{ $p['current'] ? 'current' : '' }}">{{ $p['num'] }}</a>
    @endforeach

</nav>
@endif
@endsection
