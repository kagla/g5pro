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

.bk-price { margin-top: var(--s2); font-size: var(--t-md); color: var(--fg); }
.bk-price b { font-size: var(--t-lg); color: var(--accent); font-variant-numeric: tabular-nums; }
.bk-card-btn { margin-top: var(--s3); }
</style>
@endsection

@section('content')
<nav class="bk-nav" aria-label="예약 메뉴">
    <a href="{{ G5_URL }}/booking/" aria-current="page">객실 안내</a>
    <a href="{{ G5_URL }}/booking/lookup.php">예약 조회</a>
</nav>

<section class="hero">
    <h2>객실 예약</h2>
    <p>객실을 고르고 달력에서 묵으실 날짜를 선택하세요.</p>
</section>

@if (count($rooms))
<div class="gallery-grid">
    @foreach ($rooms as $room)
    @php $href = G5_URL.'/booking/room.php?br_id='.$room['br_id']; @endphp
    <div class="gallery-card">
        <a class="gallery-thumb" href="{{ $href }}">
            @if ($room['image'])
            <img src="{{ $room['image'] }}" alt="{{ $room['br_subject'] }}">
            @else
            <span class="gallery-noimg">이미지 준비 중</span>
            @endif
        </a>
        <div class="gallery-info">
            <h3 class="gallery-subject"><a href="{{ $href }}">{{ $room['br_subject'] }}</a></h3>
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
