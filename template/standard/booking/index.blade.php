{{-- 객실 목록 (booking/index.php) --}}
@extends('layout.default')

{{-- 스타일을 뷰가 지고 다닌다. 이 화면은 template/standard 에만 있고 다른 템플릿에서는
     폴백(extend/pro.10.extend.php 의 $views)으로 그려지므로, 그 템플릿의 style.css 에
     예약 규칙이 있으리라 기대할 수 없다. 색·여백은 어느 템플릿에나 있는 토큰만 쓴다 --}}
@section('head')
<style>
/* 예약 화면 사이의 미니 내비. 사이트 전역 GNB 는 관리자 메뉴관리 소관이라, 예약 모듈 안에서
   객실 안내 ↔ 예약 조회를 오갈 길은 이 두 화면(index·lookup)이 스스로 지고 있어야 한다.
   현재 화면은 aria-current 로 표시하고 색으로도 같이 알린다 — 색만으로 알리지 않는다 */
.bk-nav { display: flex; flex-wrap: wrap; gap: var(--s2); margin-bottom: var(--s5); }
.bk-nav a { display: inline-flex; align-items: center; padding: var(--s2) var(--s4);
    border: 1px solid var(--btn-line); border-radius: var(--r-full);
    background: var(--card); color: var(--fg); font-size: var(--t-md); text-decoration: none; }
.bk-nav a:hover { border-color: var(--accent); color: var(--accent); }
.bk-nav a[aria-current="page"] { background: var(--accent); border-color: var(--accent);
    color: var(--accent-fg); font-weight: 700; }
.bk-nav a[aria-current="page"]:hover { color: var(--accent-fg); }

/* 관리자에게만 붙는 네 번째 칸. 손님용 두 칸과 섞이지 않게 색으로도 갈라 둔다 */
.bk-nav a.bk-nav-admin { margin-left: auto; border-color: var(--accent); color: var(--accent); }

.bk-price { margin-top: var(--s2); font-size: var(--t-md); color: var(--fg); }
.bk-price b { font-size: var(--t-lg); color: var(--accent); font-variant-numeric: tabular-nums; }
.bk-card-btn { margin-top: var(--s3); }

/* 관리자 바로가기 — 게시판·상품의 .icon-btn.bbs-admin-link 와 같은 모양이지만
   그 규칙은 standard 의 style.css 에 있다. 이 화면은 폴백으로 다른 템플릿에도 뜨므로
   기대지 않고 제 규칙을 지고 다닌다 (이 파일 머리말 참고) */
.bk-gear { display: inline-flex; align-items: center; justify-content: center; flex: none;
    width: 32px; height: 32px; border-radius: var(--r-md); color: var(--muted);
    transition: color .15s, background .15s; }
.bk-gear:hover { color: var(--accent); background: var(--accent-soft); }
.bk-gear svg { width: 17px; height: 17px; fill: none;
    stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
/* 카드의 톱니는 제목과 한 줄에 둔다. 제목이 길어지면 톱니가 밀리지 않게 옆에 붙여 놓는다 */
.gallery-subject { display: flex; align-items: center; gap: var(--s2); }
.gallery-subject a { min-width: 0; }
.gallery-subject .bk-gear { width: 26px; height: 26px; }
.gallery-subject .bk-gear svg { width: 15px; height: 15px; }
</style>
@endsection

@section('content')
<nav class="bk-nav" aria-label="예약 메뉴">
    <a href="{{ G5_URL }}/booking/" aria-current="page">객실 안내</a>
    <a href="{{ G5_URL }}/booking/lookup.php">예약 조회</a>
    {{-- 최고관리자에게만 채워진다 (booking/index.php). 아니면 이 칸 자체가 안 나간다 --}}
    @if ($admin_links['booking'])
    <a class="bk-nav-admin" href="{{ $admin_links['booking'] }}">예약관리</a>
    @endif
</nav>

<section class="hero">
    <h2>객실 예약</h2>
    <p>객실을 고르고 달력에서 묵으실 날짜를 선택하세요.</p>
</section>

@if (count($rooms))
<div class="gallery-grid">
    @foreach ($rooms as $room)
    {{-- 톱니 URL 은 객실마다 미리 담겨 온다. 관리자가 아니면 빈 문자열이라 아무것도 안 나간다 --}}
    @php $href = G5_URL.'/booking/room.php?br_id='.$room['br_id']; @endphp
    @php $gear = $admin_links['rooms'][$room['br_id']]; @endphp
    <div class="gallery-card">
        <a class="gallery-thumb" href="{{ $href }}">
            @if ($room['image'])
            <img src="{{ $room['image'] }}" alt="{{ $room['br_subject'] }}">
            @else
            <span class="gallery-noimg">이미지 준비 중</span>
            @endif
        </a>
        <div class="gallery-info">
            <h3 class="gallery-subject">
                <a href="{{ $href }}">{{ $room['br_subject'] }}</a>
                @if ($gear)
                <a class="bk-gear" href="{{ $gear }}" title="객실 수정" aria-label="{{ $room['br_subject'] }} 객실 수정">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3.2"/><path d="M19.1 14.6a1.5 1.5 0 0 0 .3 1.7l.1.1a1.9 1.9 0 1 1-2.7 2.7l-.1-.1a1.5 1.5 0 0 0-1.7-.3 1.5 1.5 0 0 0-.9 1.4v.2a1.9 1.9 0 1 1-3.8 0v-.1a1.5 1.5 0 0 0-1-1.4 1.5 1.5 0 0 0-1.7.3l-.1.1a1.9 1.9 0 1 1-2.7-2.7l.1-.1a1.5 1.5 0 0 0 .3-1.7 1.5 1.5 0 0 0-1.4-.9h-.2a1.9 1.9 0 1 1 0-3.8h.1a1.5 1.5 0 0 0 1.4-1 1.5 1.5 0 0 0-.3-1.7l-.1-.1a1.9 1.9 0 1 1 2.7-2.7l.1.1a1.5 1.5 0 0 0 1.7.3h.1a1.5 1.5 0 0 0 .9-1.4v-.2a1.9 1.9 0 1 1 3.8 0v.1a1.5 1.5 0 0 0 .9 1.4 1.5 1.5 0 0 0 1.7-.3l.1-.1a1.9 1.9 0 1 1 2.7 2.7l-.1.1a1.5 1.5 0 0 0-.3 1.7v.1a1.5 1.5 0 0 0 1.4.9h.2a1.9 1.9 0 1 1 0 3.8h-.1a1.5 1.5 0 0 0-1.4.9Z"/></svg>
                </a>
                @endif
            </h3>
            <p class="m">
                <span>기준 {{ $room['br_base_person'] }}명</span>
                <span>최대 {{ $room['br_max_person'] }}명</span>
            </p>
            <p class="bk-price">1박 <b>{{ number_format($room['br_weekday_price']) }}</b>원부터</p>
            <a class="btn btn-primary btn-block bk-card-btn" href="{{ $href }}">날짜 보기</a>
        </div>
    </div>
    @endforeach
</div>
@else
<p class="bbs-empty">등록된 객실이 없습니다.</p>
@endif
@endsection
