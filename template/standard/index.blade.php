@extends('layout.default')
@section('content')
@php $stats = g5_pro_stats(); @endphp
{{-- 머리말 — 그라디언트 띠 대신 숫자. 인사말 한 줄보다 "회원 n명 · 글 n개" 가
     이 커뮤니티가 살아 있다는 근거를 더 잘 댄다.
     접속 인원은 아래 접속자 카드가 맡는다 — 같은 화면에 두 번 적지 않는다 --}}
<section class="hero">
    <h2>오늘도 새 글이 기다리고 있어요</h2>
    <p>회원들이 남긴 이야기를 게시판별로 모았습니다.</p>
    <div class="hero-stats">
        <div><b>{{ number_format($stats['members']) }}</b><span>회원</span></div>
        <div><b>{{ number_format($stats['posts']) }}</b><span>전체 글</span></div>
    </div>
</section>

{{-- 게시판 라벨 칩은 두지 않는다 — 칩과 카드 제목이 같은 말을 두 번 했다("[공지] 공지사항") --}}
<h2 class="sound_only">게시판별 최신글</h2>
<div class="card-grid">
    @include('partials.latest', ['bo_table' => 'notice',  'rows' => 5])
    @include('partials.latest', ['bo_table' => 'free',    'rows' => 5])
    @include('partials.latest', ['bo_table' => 'qa',      'rows' => 5])
    @include('partials.latest', ['bo_table' => 'gallery', 'rows' => 5])
    {{-- 게시판 최신글이 먼저, 곁다리 위젯이 뒤 --}}
    @include('partials.poll_card')
    @include('partials.connect_card')
</div>
@endsection
