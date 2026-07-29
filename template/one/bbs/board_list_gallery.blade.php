{{-- 목록 변형 4 · 갤러리 그리드 (bo_skin='blade_gallery') — 사진 게시판용 --}}
@extends('layout.bbs')
@section('bbs_content')
@include('partials.bbs_head')

<ul class="gallery-grid">
    @forelse ($items as $it)
    <li class="gallery-card">
        <a href="{{ $it['href'] }}" class="gallery-thumb">
            @if ($it['thumb'] && $it['thumb']['src'])
            <img src="{{ $it['thumb']['src'] }}" alt="{{ $it['thumb']['alt'] }}" loading="lazy">
            @else
            <span class="gallery-noimg">이미지 없음</span>
            @endif
        </a>
        <div class="gallery-info">
            <div class="gallery-subject">
                @if ($it['is_notice'])<span class="chip c3">공지</span>@endif
                <a href="{{ $it['href'] }}">{!! $it['subject'] !!}</a>
                @if ($it['comment_cnt'])<span class="n">{{ $it['comment_cnt'] }}</span>@endif
                @if ($it['icon_new'])<span class="badge-new">N</span>@endif
            </div>
            <div class="m">
                <span>{!! $it['name'] !!}</span>
                <span>{{ $it['datetime'] }}</span>
            </div>
        </div>
    </li>
    @empty
    <li class="bbs-empty">게시물이 없습니다.</li>
    @endforelse
</ul>

@include('partials.bbs_toolbar')
@endsection
