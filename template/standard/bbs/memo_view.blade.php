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
            {{-- 답장도 쪽지 쓰기 화면이라 새 창에서 연다(.win_memo → common.js) --}}
            @if ($reply_href)<a class="btn btn-primary win_memo" href="{!! $reply_href !!}" target="_blank" rel="noopener">답장</a>@endif
        </div>
        <div class="bbs-actions">
            <a class="btn" href="{!! $del_href !!}" data-confirm="이 쪽지를 삭제하시겠습니까?" data-confirm-danger>삭제</a>
        </div>
    </div>

    <nav class="post-nav">
        @if ($prev_href)<a class="btn" href="{!! $prev_href !!}">&laquo; 이전 쪽지</a>@endif
        @if ($next_href)<a class="btn" href="{!! $next_href !!}">다음 쪽지 &raquo;</a>@endif
    </nav>
</div>
@endsection
