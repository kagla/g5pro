{{-- 목록 변형 4 · 갤러리 그리드 (bo_skin='gallery') — 사진 게시판용 --}}
@extends('layout.bbs')
@section('bbs_content')
@include('partials.bbs_head')

@if ($content_head)<div class="board-extra">{!! $content_head !!}</div>@endif

@include('partials.list_body_gallery')

@if ($content_tail)<div class="board-extra">{!! $content_tail !!}</div>@endif
@endsection
