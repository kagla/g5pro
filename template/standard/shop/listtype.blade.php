@extends('layout.default')
@section('content')
<header class="bbs-head">
    <h2><span class="chip">모아보기</span>{{ $type_name }}</h2>
    <div class="bbs-meta">전체 {{ number_format($total_count) }}개 · {{ $page }} / {{ max($total_page, 1) }} 페이지</div>
</header>

{{-- 유형 전환 — 메인에 있는 블록들을 여기서 바로 오갈 수 있게 --}}
@if (count($tabs) > 1)
<nav class="bbs-cate">
    @foreach ($tabs as $t)
    @php $cls = $t['active'] ? 'active' : ''; @endphp
    <a href="{{ $t['href'] }}" class="{{ $cls }}">{{ $t['name'] }}</a>
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
