@extends('layout.default')
@section('content')
@php
    $stats = g5_pro_stats();
    // 돗자리(게시판)별 최신글이 주인공 — 미설치 게시판은 partial 이 알아서 건너뛴다
    $gallery = g5_latest_thumb_rows('gallery', 4, 480, 320);
    $hot     = g5_hot_rows(array('free', 'qa', 'gallery'), 5);
@endphp

<section class="hero home-hero">
    <div>
        <h2>🧺 오늘도 즐거운 {{ $site['title'] }}!</h2>
        <p>가볍게 펼쳐 놓고 도란도란 — 관심사별 돗자리에 앉아 보세요</p>
    </div>
    <div class="hero-pills">
        <span>✍️ 전체 글 {{ number_format($stats['posts']) }}</span>
        <span>👥 접속자 {{ number_format($stats['online']) }}</span>
    </div>
</section>

<h2 class="sound_only">게시판별 최신글</h2>
<div class="card-grid">
    @include('partials.latest', ['bo_table' => 'free',    'rows' => 5, 'chip' => '',   'label' => '💬 자유'])
    @include('partials.latest', ['bo_table' => 'qa',      'rows' => 5, 'chip' => 'c2', 'label' => '❓ 질문'])
    @include('partials.latest', ['bo_table' => 'notice',  'rows' => 5, 'chip' => 'c3', 'label' => '📢 공지'])
</div>

@if ($gallery['board'])
<section class="card home-gallery home-row">
    <h3>
        📸 갤러리 새 사진
        <a class="more" href="{{ G5_BBS_URL }}/board.php?bo_table={{ $gallery['board']['bo_table'] }}">더 보기 →</a>
    </h3>
    <div class="home-gallery-grid">
        @forelse ($gallery['items'] as $it)
        <a class="hg-item" href="{{ $it['href'] }}">
            <span class="hg-thumb">
                @if ($it['thumb'] && $it['thumb']['src'])
                <img src="{{ $it['thumb']['src'] }}" alt="{{ $it['thumb']['alt'] }}" loading="lazy">
                @else
                {{-- 이미지 없는 글 — 파스텔 자리 표시 --}}
                <span class="hg-noimg" aria-hidden="true">📷</span>
                @endif
            </span>
            <span class="hg-subject">{!! $it['subject'] !!}</span>
            <span class="hg-meta muted">{{ $it['datetime2'] }}@if ($it['wr_comment']) · 💬 {{ $it['wr_comment'] }}@endif</span>
        </a>
        @empty
        <p class="muted">아직 사진이 없습니다.</p>
        @endforelse
    </div>
</section>
@endif

<section class="home-strip">
    <h3>📊 지금 {{ $site['title'] }}은</h3>
    <div class="nums">
        <span><b>{{ number_format($stats['posts']) }}</b>전체 글 ✍️</span>
        <span><b>{{ number_format($stats['members']) }}</b>회원 🙌</span>
        <span><b>{{ number_format($stats['online']) }}</b>접속자 👥</span>
    </div>
</section>

<div class="home-row-2col">
    <section class="card hot-card">
        <h3>🔥 요즘 인기글</h3>
        <ol class="hot-list">
            @forelse ($hot as $i => $row)
            <li>
                <b class="no">{{ $i + 1 }}</b>
                <a href="{{ $row['item']['href'] }}">{!! $row['item']['subject'] !!}</a>
            </li>
            @empty
            <li class="muted">아직 글이 없습니다.</li>
            @endforelse
        </ol>
    </section>
    <div>
        @include('partials.poll_card')
        @include('partials.connect_card')
    </div>
</div>
@endsection
