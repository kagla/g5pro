@extends('layout.bbs')
@section('bbs_content')
<header class="bbs-head">
    <h2><a href="{{ $list_href }}">{{ $board['bo_subject'] }}</a></h2>
</header>

<article class="post">
    <header class="post-head">
        <h3>@if ($post['ca_name'])<span class="badge">{{ $post['ca_name'] }}</span>@endif {!! $post['subject'] !!}</h3>
        <div class="post-meta">
            <span class="name">{!! $post['name'] !!}</span>
            <span>{{ $post['datetime'] }}</span>
            <span>조회 {{ $post['hit'] }}</span>
        </div>
    </header>

    <div class="post-content">{!! $post['content'] !!}</div>

    @if (count($files))
    <ul class="post-files">
        @foreach ($files as $f)
        <li><a href="{{ $f['href'] }}">{{ $f['source'] }}</a> <span class="muted">({{ $f['size'] }}) {{ $f['download'] }}회</span></li>
        @endforeach
    </ul>
    @endif

    @if (count($links))
    <ul class="post-links">
        @foreach ($links as $l)
        <li><a href="{{ $l['href'] }}" target="_blank" rel="noopener">{{ $l['url'] }}</a> <span class="muted">{{ $l['hit'] }}회</span></li>
        @endforeach
    </ul>
    @endif
</article>

<section class="comments">
    <h4>댓글 {{ count($comments) }}</h4>
    @foreach ($comments as $c)
    @php $pad = min($c['depth'], 5) * 20; @endphp
    <div class="comment" style="margin-left: {{ $pad }}px">
        <div class="comment-meta">
            <span class="name">{!! $c['name'] !!}</span> <span class="muted">{{ $c['datetime'] }}</span>
            <span class="comment-acts">
                @if ($is_member && $c['is_reply'])<button type="button" class="linklike c-reply" data-id="{{ $c['id'] }}">답글</button>@endif
                @if ($c['is_edit'])<button type="button" class="linklike c-edit" data-id="{{ $c['id'] }}" data-raw="{{ $c['raw'] }}">수정</button>@endif
                @if ($c['del_link'])<a class="linklike" href="{!! $c['del_link'] !!}" onclick="return confirm('이 댓글을 삭제하시겠습니까?');">삭제</a>@endif
            </span>
        </div>
        <div class="comment-body">{!! $c['content'] !!}</div>
    </div>
    @endforeach

    @if ($is_member)
    <form name="fviewcomment" id="fviewcomment" class="comment-form" method="post" action="{{ $comment_action }}"
          onsubmit="return fviewcomment_submit(this);" autocomplete="off">
        @foreach ($comment_hidden as $hname => $hval)
        <input type="hidden" name="{{ $hname }}" value="{{ $hval }}">
        @endforeach
        <textarea name="wr_content" rows="3" required placeholder="댓글을 남겨주세요"></textarea>
        <button type="submit" class="btn btn-primary">댓글 등록</button>
    </form>
    <script>
    function fviewcomment_submit(f) {
        if (!f.wr_content.value.trim()) { alert("댓글 내용을 입력하세요."); f.wr_content.focus(); return false; }
        set_comment_token(f); // 순정 common.js — ajax.comment_token.php 에서 일회용 토큰 주입
        return true;
    }
    // 답글: 대상 댓글 지정(w=c + comment_id), 수정: 원문 채움(w=cu)
    document.querySelectorAll('.c-reply').forEach(function (b) {
        b.addEventListener('click', function () {
            var f = document.fviewcomment;
            f.w.value = 'c'; f.comment_id.value = b.dataset.id;
            f.wr_content.value = ''; f.wr_content.focus();
        });
    });
    document.querySelectorAll('.c-edit').forEach(function (b) {
        b.addEventListener('click', function () {
            var f = document.fviewcomment;
            f.w.value = 'cu'; f.comment_id.value = b.dataset.id;
            f.wr_content.value = b.dataset.raw; f.wr_content.focus();
        });
    });
    </script>
    @else
    <p class="muted">댓글을 쓰려면 <a href="{{ G5_BBS_URL }}/login.php">로그인</a>하세요.</p>
    @endif
</section>

<div class="bbs-toolbar">
    <div class="bbs-actions">
        <a class="btn" href="{{ $list_href }}">목록</a>
        @if ($reply_href)<a class="btn" href="{!! $reply_href !!}">답변</a>@endif
    </div>
    <div class="bbs-actions">
        @if ($update_href)<a class="btn" href="{!! $update_href !!}">수정</a>@endif
        @if ($delete_href)<a class="btn" href="{!! $delete_href !!}" onclick="return confirm('삭제하시겠습니까?');">삭제</a>@endif
        @if ($write_href)<a class="btn btn-primary" href="{{ $write_href }}">글쓰기</a>@endif
    </div>
</div>

<nav class="post-nav">
    @if ($prev_href)<a class="btn" href="{!! $prev_href !!}">&laquo; 이전글</a>@endif
    @if ($next_href)<a class="btn" href="{!! $next_href !!}">다음글 &raquo;</a>@endif
</nav>
@endsection
