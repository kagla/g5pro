{{-- 1:1 문의 읽기 (bbs/qaview.php) — 질문 아래에 답변을 잇는다.
     답변은 순정에서 별도 행(qa_type=1)이라 없을 수도 있다. --}}
@extends('layout.default')
@section('content')

@if ($head)<div class="board-extra">{!! $head !!}</div>@endif

<article class="post qa-post">
    <header class="post-head">
        <h2>
            @if ($item['category'])<span class="chip c2">{{ $item['category'] }}</span>@endif
            {{ $item['subject'] }}
        </h2>
        <div class="post-meta">
            <span>{{ $item['name'] }}</span>
            <span>{{ $item['datetime'] }}</span>
            <span class="chip {{ $item['answered'] ? 'c3' : 'c4' }}">{{ $item['answered'] ? '답변완료' : '접수' }}</span>
        </div>
    </header>

    <div class="post-content">{!! $item['content'] !!}</div>

    @if (count($images))
    <div class="qa-images">
        @foreach ($images as $img)
        <div class="qa-image">{!! $img !!}</div>
        @endforeach
    </div>
    @endif

    @if (count($files))
    <div class="post-files">
        @foreach ($files as $f)
        <a href="{{ $f['href'] }}">{{ $f['source'] }}</a>
        @endforeach
    </div>
    @endif
</article>

@if ($answer)
<article class="post qa-answer">
    <header class="post-head">
        <h2><span class="chip c3">답변</span></h2>
        <div class="post-meta"><span>{{ $answer['datetime'] }}</span></div>
    </header>
    <div class="post-content">{!! $answer['content'] !!}</div>
    @if ($is_admin && $links['answer_update'])
    <div class="post-files">
        <a href="{!! $links['answer_update'] !!}">답변수정</a>
        <a href="{!! $links['answer_delete'] !!}" onclick="return confirm('답변을 삭제하시겠습니까?');">답변삭제</a>
    </div>
    @endif
</article>
@endif

<div class="bbs-toolbar active">
    <div class="qa-nav">
        @if ($links['prev'])<a class="btn" href="{!! $links['prev'] !!}">이전</a>@endif
        @if ($links['next'])<a class="btn" href="{!! $links['next'] !!}">다음</a>@endif
    </div>
    <div class="qa-acts">
        @if ($links['update'])<a class="btn" href="{!! $links['update'] !!}">수정</a>@endif
        @if ($links['delete'])<a class="btn" href="{!! $links['delete'] !!}" onclick="return confirm('이 문의를 삭제하시겠습니까?');">삭제</a>@endif
        <a class="btn" href="{{ $links['list'] }}">목록</a>
        <a class="btn btn-primary" href="{{ $links['write'] }}">문의하기</a>
    </div>
</div>

@if ($tail)<div class="board-extra">{!! $tail !!}</div>@endif
@endsection
