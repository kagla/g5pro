@extends('layout.default')
@section('content')
<div class="memo-box">
    <header class="bbs-head">
        <h2>{{ $kind === 'recv' ? '받은' : '보낸' }} 쪽지</h2>
    </header>

    <article class="post">
        <header class="post-head">
            <div class="post-meta">
                <span class="name">{!! $name !!}</span>
                <span>{{ $datetime }}</span>
            </div>
        </header>
        <div class="post-content">{!! $content !!}</div>
    </article>

    <div class="bbs-toolbar">
        <div class="bbs-actions">
            <a class="btn" href="{{ $list_href }}">목록</a>
            @if ($reply_href)<a class="btn btn-primary" href="{!! $reply_href !!}">답장</a>@endif
        </div>
        <div class="bbs-actions">
            <a class="btn" href="{!! $del_href !!}" onclick="return confirm('이 쪽지를 삭제하시겠습니까?');">삭제</a>
        </div>
    </div>

    <nav class="post-nav">
        @if ($prev_href)<a class="btn" href="{!! $prev_href !!}">&laquo; 이전 쪽지</a>@endif
        @if ($next_href)<a class="btn" href="{!! $next_href !!}">다음 쪽지 &raquo;</a>@endif
    </nav>
</div>
@endsection
