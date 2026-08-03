@extends('layout.default')
@section('content')
@php
    $stats = g5_pro_stats();
    // 메인 구성 게시판 — 미설치 게시판은 헬퍼가 알아서 건너뛴다
    $mixed   = g5_latest_mixed(array('notice', 'free', 'qa', 'gallery'), 8);
    $gallery = g5_latest_thumb_rows('gallery', 4, 480, 320);
    $hot     = g5_hot_rows(array('free', 'qa', 'gallery'), 5);
    // 게시판별 칩 색 — 게시판에 색을 고정해 어디서든 같은 색으로 읽히게
    $chips = array('notice' => 'c3', 'free' => '', 'qa' => 'c2', 'gallery' => 'c4');
@endphp

<section class="hero home-hero">
    <div>
        <h2>👋 어서 오세요, {{ $site['title'] }}입니다</h2>
        <p>회원 {{ number_format($stats['members']) }}명이 함께하는 커뮤니티 · 오늘 하루 이야기를 편하게 나눠 보세요</p>
    </div>
    <div class="hero-pills">
        <span>✍️ 전체 글 {{ number_format($stats['posts']) }}</span>
        <span>👥 접속자 {{ number_format($stats['online']) }}</span>
    </div>
</section>

<div class="home-grid">
    <div class="home-main">
        <section class="card feed-card">
            <h3>🕒 방금 올라온 글</h3>
            <ul class="feed-list">
                @forelse ($mixed as $row)
                <li>
                    <span class="chip {{ $chips[$row['bo_table']] ?? '' }}">{{ $row['bo_subject'] }}</span>
                    <a class="t" href="{{ $row['item']['href'] }}">{!! $row['item']['subject'] !!}</a>
                    @if ($row['item']['wr_comment'])<span class="n">{{ $row['item']['wr_comment'] }}</span>@endif
                    <span class="muted">{{ $row['item']['datetime2'] }}</span>
                </li>
                @empty
                <li class="muted">아직 글이 없습니다.</li>
                @endforelse
            </ul>
        </section>

        @if ($gallery['board'])
        <section class="card home-gallery">
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
                        {{-- 이미지 없는 글 — 인디고 자리 표시 --}}
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
    </div>

    <div class="home-side">
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
        @include('partials.poll_card')
        @include('partials.connect_card')
    </div>
</div>
@endsection
