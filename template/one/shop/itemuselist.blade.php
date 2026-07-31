{{-- 사용후기 모아보기 (shop/itemuselist.php) --}}
@extends('layout.default')
@section('content')
<header class="bbs-head">
    <h2><span class="chip">쇼핑몰</span>사용후기</h2>
    <div class="bbs-meta">전체 {{ number_format($total_count) }}건 · {{ $page }} / {{ max($total_page, 1) }} 페이지</div>
</header>

<div class="list-panel">
    <ul class="list-cards">
        @forelse ($items as $it)
        <li>
            <div class="s">
                <a class="chip c4" href="{{ $it['href'] }}">{{ $it['it_name'] }}</a>
                <span class="t">{{ $it['subject'] }}</span>
            </div>
            <div class="m">
                <span class="stars" aria-label="별점 {{ $it['score'] }}점">{{ str_repeat('★', $it['score']) }}{{ str_repeat('☆', 5 - $it['score']) }}</span>
                <span>{!! $it['name'] !!}</span>
                <span>{{ $it['datetime'] }}</span>
            </div>
        </li>
        @empty
        <li class="bbs-empty">등록된 사용후기가 없습니다.</li>
        @endforelse
    </ul>
</div>

@include('partials.paging', ['page' => $page, 'total_page' => $total_page, 'page_href' => $page_href])

<div class="bbs-toolbar">
    <form class="bbs-search" method="get" action="{{ $action_url }}">
        <label for="use_sfl" class="sound_only">검색 대상</label>
        <select id="use_sfl" name="sfl">
            @foreach ($sfl_options as $v => $label)
            <option value="{{ $v }}" @if ($search['sfl'] === $v) selected @endif>{{ $label }}</option>
            @endforeach
        </select>
        <label for="use_stx" class="sound_only">검색어</label>
        <input type="text" id="use_stx" name="stx" value="{{ $search['stx'] }}" placeholder="사용후기 검색" required>
        <button type="submit" class="btn">검색</button>
    </form>
    <div></div>
</div>
@endsection
