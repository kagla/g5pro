@extends('layout.default')
@section('content')
@php $stats = g5_pro_stats(); @endphp
<section class="hero">
    <h2>오늘도 새 글이 기다리고 있어요 💧</h2>
    <p>회원 {{ number_format($stats['members']) }}명이 함께하는 커뮤니티 · 지금 접속 {{ number_format($stats['online']) }}명 · 전체 글 {{ number_format($stats['posts']) }}개</p>
</section>

<h2 class="sound_only">게시판별 최신글</h2>
<div class="card-grid">
    @include('partials.latest', ['bo_table' => 'notice',  'rows' => 5, 'chip' => 'c3', 'label' => '공지'])
    @include('partials.latest', ['bo_table' => 'free',    'rows' => 5, 'chip' => '',   'label' => '자유'])
    @include('partials.latest', ['bo_table' => 'qa',      'rows' => 5, 'chip' => 'c2', 'label' => 'Q&A'])
    @include('partials.latest', ['bo_table' => 'gallery', 'rows' => 5, 'chip' => 'c4', 'label' => '갤러리'])
</div>
@endsection
