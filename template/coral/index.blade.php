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
    // 피처 배너 — 요즘 인기글 1위를 크게. 알림판은 공지 최신 3건
    $feature = count($hot) ? $hot[0] : null;
    $notices = g5_latest_rows('notice', 3);
@endphp

<h2 class="sound_only">오늘의 이야기와 알림판</h2>
<div class="home-feature">
    @if ($feature)
    <a class="feat-main" href="{{ $feature['item']['href'] }}">
        <span class="cat">⭐ 오늘의 이야기</span>
        <h3>{!! $feature['item']['subject'] !!}</h3>
        <p class="m">{{ $feature['bo_subject'] }} · 👀 조회 {{ number_format($feature['item']['wr_hit']) }}@if ($feature['item']['wr_comment']) · 💬 댓글 {{ $feature['item']['wr_comment'] }}@endif</p>
    </a>
    @endif
    <aside class="feat-side">
        <h3>📢 알림판</h3>
        <ul>
            @forelse ($notices['items'] as $it)
            <li><a href="{{ $it['href'] }}">{!! $it['subject'] !!}</a><span class="m">{{ $it['datetime2'] }}</span></li>
            @empty
            <li class="muted">등록된 공지가 없습니다.</li>
            @endforelse
        </ul>
    </aside>
</div>

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
