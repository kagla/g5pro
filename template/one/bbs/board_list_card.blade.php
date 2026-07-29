{{-- 목록 변형 3 · 카드 (bo_skin='blade_card') — 모바일 비중 큰 게시판용, 썸네일 있으면 좌측 표시 --}}
@extends('layout.bbs')
@section('bbs_content')
@include('partials.bbs_head')

<ul class="list-card">
    @forelse ($items as $it)
    @php $cls = $it['is_notice'] ? 'notice' : ''; @endphp
    <li class="{{ $cls }}">
        @if ($it['thumb'] && $it['thumb']['src'])
        <a class="thumb" href="{{ $it['href'] }}">
            <img src="{{ $it['thumb']['src'] }}" alt="{{ $it['thumb']['alt'] }}" loading="lazy">
        </a>
        @endif
        <div class="info">
            <div class="s">
                @if ($it['is_notice'])<span class="badge">공지</span>@endif
                <span class="t"><a href="{{ $it['href'] }}">{!! $it['subject'] !!}</a></span>
                @if ($it['comment_cnt'])<span class="cmt-cnt">{{ $it['comment_cnt'] }}</span>@endif
                @if ($it['icon_new'])<span class="badge new">N</span>@endif
            </div>
            <div class="m">
                <span>{!! $it['name'] !!}</span>
                <span>{{ $it['datetime'] }}</span>
                <span>조회 {{ $it['hit'] }}</span>
            </div>
        </div>
    </li>
    @empty
    <li class="bbs-empty">게시물이 없습니다.</li>
    @endforelse
</ul>

@include('partials.bbs_toolbar')
@endsection
