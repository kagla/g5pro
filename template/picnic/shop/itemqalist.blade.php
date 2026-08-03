{{-- 상품문의 모아보기 (shop/itemqalist.php) — 비밀글은 제목만 보인다 --}}
@extends('layout.default')
@section('content')
<header class="bbs-head">
    <h2><span class="chip">쇼핑몰</span>상품문의</h2>
    <div class="bbs-meta">전체 {{ number_format($total_count) }}건 · {{ $page }} / {{ max($total_page, 1) }} 페이지</div>
</header>

<div class="list-panel">
    <ul class="list-cards">
        @forelse ($items as $it)
        <li>
            <div class="s">
                <a class="chip c4" href="{{ $it['href'] }}">{{ $it['it_name'] }}</a>
                <span class="t">
                    @if ($it['is_secret'] && !$it['can_read'])
                    비밀글로 보호된 문의입니다
                    @else
                    {{ $it['subject'] }}
                    @endif
                </span>
            </div>
            <div class="m">
                @if ($it['is_secret'])<span class="chip c5">비밀</span>@endif
                <span class="chip {{ $it['answered'] ? 'c3' : '' }}">{{ $it['answered'] ? '답변완료' : '답변대기' }}</span>
                <span>{!! $it['name'] !!}</span>
                <span>{{ $it['datetime'] }}</span>
            </div>
        </li>
        @empty
        <li class="bbs-empty">등록된 상품문의가 없습니다.</li>
        @endforelse
    </ul>
</div>

@include('partials.paging', ['page' => $page, 'total_page' => $total_page, 'page_href' => $page_href])

<div class="bbs-toolbar">
    <form class="bbs-search" method="get" action="{{ $action_url }}">
        <label for="qa_sfl" class="sound_only">검색 대상</label>
        <select id="qa_sfl" name="sfl">
            @foreach ($sfl_options as $v => $label)
            <option value="{{ $v }}" @if ($search['sfl'] === $v) selected @endif>{{ $label }}</option>
            @endforeach
        </select>
        <label for="qa_stx" class="sound_only">검색어</label>
        <input type="text" id="qa_stx" name="stx" value="{{ $search['stx'] }}" placeholder="상품문의 검색" required>
        <button type="submit" class="btn">검색</button>
    </form>
    <div></div>
</div>
@endsection
