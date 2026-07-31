{{-- 목록 변형 1 · 표 (기본, bo_skin='pro' 또는 순정 'basic') --}}
@extends('layout.bbs')
@section('bbs_content')
@include('partials.bbs_head')

@if ($content_head)<div class="board-extra">{!! $content_head !!}</div>@endif

@include('partials.list_body_table')

@if ($content_tail)<div class="board-extra">{!! $content_tail !!}</div>@endif
@endsection
