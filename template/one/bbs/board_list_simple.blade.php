{{-- 목록 변형 2 · 미니멀 (bo_skin='blade_simple') — 여백 넓은 행, 글 읽기 중심 게시판용 --}}
@extends('layout.bbs')
@section('bbs_content')
@include('partials.bbs_head')

@if ($content_head)<div class="board-extra">{!! $content_head !!}</div>@endif

@include('partials.list_body_simple')

@if ($content_tail)<div class="board-extra">{!! $content_tail !!}</div>@endif
@endsection
