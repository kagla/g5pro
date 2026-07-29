{{-- 목록 변형 3 · 소셜 피드 카드 (bo_skin='blade_card') — 모바일 비중 큰 게시판용 --}}
@extends('layout.bbs')
@section('bbs_content')
@include('partials.bbs_head')

<ul class="list-card">
    @forelse ($items as $it)
    <li>
        @if ($it['thumb'] && $it['thumb']['src'])
        <a class="thumb" href="{{ $it['href'] }}">
            <img src="{{ $it['thumb']['src'] }}" alt="{{ $it['thumb']['alt'] }}" loading="lazy">
        </a>
        @else
        @php $initial = mb_substr(strip_tags($it['name']), 0, 1, 'UTF-8'); @endphp
        <span class="ava" aria-hidden="true">{{ $initial }}</span>
        @endif
        <div class="info">
            <div class="s">
                @if ($it['is_notice'])<span class="chip c3">공지</span>@endif
                <span class="t"><a href="{{ $it['href'] }}">{!! $it['subject'] !!}</a></span>
                @if ($it['icon_new'])<span class="badge-new">N</span>@endif
            </div>
            <div class="m">
                <span>{!! $it['name'] !!}</span>
                <span>{{ $it['datetime'] }}</span>
                @if ($it['comment_cnt'])<span>댓글 <b class="n">{{ $it['comment_cnt'] }}</b></span>@endif
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
