@extends('layout.default')
@section('content')
<section class="hero">
    <h2>{!! $group['gr_subject'] !!}</h2>
    <p>게시판 {{ count($boards) }}개의 최신글을 한눈에 봅니다.</p>
</section>

<h2 class="sound_only">{!! $group['gr_subject'] !!} 게시판별 최신글</h2>
@if ($boards)
<div class="card-grid">
    @foreach ($boards as $bo)
        @include('partials.latest', ['bo_table' => $bo['bo_table'], 'rows' => 5, 'chip' => '', 'label' => ''])
    @endforeach
</div>
@else
<p class="muted">이 그룹에는 접근할 수 있는 게시판이 없습니다.</p>
@endif
@endsection
