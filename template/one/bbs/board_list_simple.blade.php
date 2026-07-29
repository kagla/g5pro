{{-- 목록 변형 2 · 미니멀 (bo_skin='blade_simple') — 여백 넓은 행, 글 읽기 중심 게시판용 --}}
@extends('layout.bbs')
@section('bbs_content')
@include('partials.bbs_head')

<ul class="list-simple">
    @forelse ($items as $it)
    @php $cls = $it['is_notice'] ? 'notice' : ''; @endphp
    <li class="{{ $cls }}">
        <div class="s">
            @if ($it['is_notice'])<span class="chip c3">공지</span>@endif
            <span class="t"><a href="{{ $it['href'] }}">{!! $it['subject'] !!}</a></span>
            @if ($it['comment_cnt'])<span class="n">{{ $it['comment_cnt'] }}</span>@endif
            @if ($it['icon_new'])<span class="badge-new">N</span>@endif
            @if ($it['icon_file'])<span class="chip c4">파일</span>@endif
        </div>
        <div class="m">
            <span>{!! $it['name'] !!}</span>
            <span>{{ $it['datetime'] }}</span>
            <span>조회 {{ $it['hit'] }}</span>
        </div>
    </li>
    @empty
    <li class="bbs-empty">게시물이 없습니다.</li>
    @endforelse
</ul>

@include('partials.bbs_toolbar')
@endsection
