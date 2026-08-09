@extends('layout.default')
@section('content')
<header class="bbs-head">
    <h2><span class="chip">분류</span>{{ $category['name'] }}</h2>
    <div class="bbs-meta">전체 {{ number_format($total_count) }}개 · {{ $page }} / {{ max($total_page, 1) }} 페이지</div>
</header>

@if (count($categories))
<nav class="bbs-cate">
    @foreach ($categories as $c)
    @php $cls = ($c['ca_id'] === $category['ca_id']) ? 'active' : ''; @endphp
    <a href="{{ $c['href'] }}" class="{{ $cls }}">{{ $c['name'] }}</a>
    @endforeach
</nav>
@endif

<nav class="shop-sort">
    @foreach ($sorts as $s)
    @php $cls = $s['active'] ? 'active' : ''; @endphp
    <a href="{{ $s['href'] }}" class="{{ $cls }}">{{ $s['name'] }}</a>
    @endforeach
</nav>

@include('partials.shop_items', ['items' => $items])

@include('partials.paging', ['page' => $page, 'total_page' => $total_page, 'page_href' => $page_href])
@endsection
