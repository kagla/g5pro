@extends('layout.default')
@section('content')
@php $stats = g5_pro_stats(); @endphp
<section class="hero">
    <h2>오늘도 새 글이 기다리고 있어요 💧</h2>
    {{-- 접속 인원은 아래 접속자 카드가 맡는다 — 같은 화면에 두 번 적지 않는다 --}}
    <p>회원 {{ number_format($stats['members']) }}명이 함께하는 커뮤니티 · 전체 글 {{ number_format($stats['posts']) }}개</p>
</section>

<h2 class="sound_only">게시판별 최신글</h2>
<div class="card-grid">
    @include('partials.latest', ['bo_table' => 'notice',  'rows' => 5, 'chip' => 'c3', 'label' => '공지'])
    @include('partials.latest', ['bo_table' => 'free',    'rows' => 5, 'chip' => '',   'label' => '자유'])
    @include('partials.latest', ['bo_table' => 'qa',      'rows' => 5, 'chip' => 'c2', 'label' => 'Q&A'])
    @include('partials.latest', ['bo_table' => 'gallery', 'rows' => 5, 'chip' => 'c4', 'label' => '갤러리'])
    {{-- 게시판 최신글이 먼저, 곁다리 위젯이 뒤 --}}
    @include('partials.poll_card')
    @include('partials.connect_card')
</div>
@endsection
