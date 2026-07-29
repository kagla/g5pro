@extends('layout.bbs')
@section('bbs_content')
<header class="bbs-head">
    <h2><a href="{{ $list_href }}">{{ $board['bo_subject'] }}</a></h2>
    <div class="bbs-meta">조회 {{ number_format($post['hit']) }} · 댓글 {{ number_format($post['comment_cnt']) }}</div>
</header>

@if ($content_head)<div class="board-extra">{!! $content_head !!}</div>@endif

<article class="post">
    <header class="post-head">
        <h3>@if ($post['ca_name'])<a class="chip cate" href="{!! $post['ca_href'] !!}">{{ $post['ca_name'] }}</a>@endif {!! $post['subject'] !!}</h3>
        <div class="post-meta">
            <span class="name">{!! $post['name'] !!}</span>
            {{-- bo_use_ip_view 를 켠 게시판에서만 값이 온다 --}}
            @if ($post['ip'])<span class="post-ip">{{ $post['ip'] }}</span>@endif
            <span>{{ $post['datetime'] }}</span>
            <span>조회 {{ number_format($post['hit']) }}</span>
            <span>댓글 {{ number_format($post['comment_cnt']) }}</span>
        </div>
    </header>

    <div class="post-content">{!! $post['content'] !!}</div>

    @if (count($files))
    <ul class="post-files">
        @foreach ($files as $f)
        <li><a href="{{ $f['href'] }}">{{ $f['source'] }}</a> <span class="muted">{{ $f['size'] }} · 다운로드 {{ $f['download'] }}회</span></li>
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

    {{-- 서명 — bo_use_signature + 회원글 --}}
    @if ($signature)<div class="post-sign">{!! $signature !!}</div>@endif

    @if ($good['use'] || $nogood['use'] || $use_sns)
    <div class="post-react">
        @if ($good['use'])
        <button type="button" class="react" data-href="{!! $good['href'] !!}" data-kind="good" @if (!$good['href']) disabled @endif>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 21V10l4.5-7A2 2 0 0 1 14 4.6L13 9h5.5a2 2 0 0 1 2 2.4l-1.5 7A2 2 0 0 1 17 20H7Z"/></svg>
            추천 <b>{{ number_format($good['count']) }}</b>
        </button>
        @endif
        @if ($nogood['use'])
        <button type="button" class="react" data-href="{!! $nogood['href'] !!}" data-kind="nogood" @if (!$nogood['href']) disabled @endif>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 3v11l-4.5 7A2 2 0 0 1 10 19.4L11 15H5.5a2 2 0 0 1-2-2.4l1.5-7A2 2 0 0 1 7 4h10Z"/></svg>
            비추천 <b>{{ number_format($nogood['count']) }}</b>
        </button>
        @endif
        <span class="react-msg" id="react-msg" aria-live="polite"></span>

        {{-- bo_use_sns + 사이트 SNS 키가 있을 때만 --}}
        @if ($use_sns)
        <span class="post-share">
            <a class="react" target="_blank" rel="noopener"
               href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($share_url) }}">페이스북</a>
            <a class="react" target="_blank" rel="noopener"
               href="https://twitter.com/intent/tweet?url={{ urlencode($share_url) }}&text={{ urlencode(strip_tags($post['subject'])) }}">트위터</a>
        </span>
        @endif
    </div>
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
        {{-- 검색으로 들어왔을 때만 — 검색 결과로 되돌아간다 --}}
        @if ($search_href)<a class="btn" href="{!! $search_href !!}">검색결과</a>@endif
        @if ($reply_href)<a class="btn" href="{!! $reply_href !!}">답변</a>@endif
        @if ($scrap_href)<a class="btn" href="{!! $scrap_href !!}" target="_blank" onclick="win_scrap(this.href); return false;">스크랩</a>@endif
    </div>
    <div class="bbs-actions">
        {{-- 복사·이동은 게시판 관리자 이상에게만 값이 온다 --}}
        @if ($copy_href)<a class="btn" href="{!! $copy_href !!}" onclick="window.open(this.href, 'g5move', 'left=60,top=60,width=560,height=640,scrollbars=1'); return false;">복사</a>@endif
        @if ($move_href)<a class="btn" href="{!! $move_href !!}" onclick="window.open(this.href, 'g5move', 'left=60,top=60,width=560,height=640,scrollbars=1'); return false;">이동</a>@endif
        @if ($update_href)<a class="btn" href="{!! $update_href !!}">수정</a>@endif
        @if ($delete_href)<a class="btn" href="{!! $delete_href !!}" onclick="return confirm('삭제하시겠습니까?');">삭제</a>@endif
        @if ($write_href)<a class="btn btn-primary" href="{{ $write_href }}">글쓰기</a>@endif
    </div>
</div>

@if ($prev || $next)
<nav class="post-nav">
    @if ($prev)
    <a class="post-nav-item" href="{!! $prev['href'] !!}">
        <span class="k">↑ 이전글</span>
        <span class="t">{{ $prev['subject'] }}</span>
        <span class="d">{{ $prev['date'] }}</span>
    </a>
    @endif
    @if ($next)
    <a class="post-nav-item" href="{!! $next['href'] !!}">
        <span class="k">↓ 다음글</span>
        <span class="t">{{ $next['subject'] }}</span>
        <span class="d">{{ $next['date'] }}</span>
    </a>
    @endif
</nav>
@endif

{{-- 전체목록보이기(bo_use_list_view) — 글 아래에 같은 게시판 목록을 그대로 --}}
@if ($list_below)
<div class="list-below">
    <h4 class="list-below-title">{{ $board['bo_subject'] }} 목록</h4>
    @include($list_below['body'], $list_below['data'])
</div>
@endif

@if ($content_tail)<div class="board-extra">{!! $content_tail !!}</div>@endif

<script>
// 추천·비추천 — 순정 good.php 의 js=on 규약 (JSON {error, count})
document.querySelectorAll('.post-react .react[data-href]').forEach(function (b) {
    b.addEventListener('click', function () {
        var msg = document.getElementById('react-msg');
        var body = new URLSearchParams({ js: 'on' });
        fetch(b.dataset.href.replace(/&amp;/g, '&'), {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        }).then(function (r) { return r.json(); }).then(function (d) {
            if (d.error) { alert(d.error); return; }
            if (d.count) {
                b.querySelector('b').textContent = Number(d.count).toLocaleString();
                msg.textContent = (b.dataset.kind === 'good' ? '추천' : '비추천') + '했습니다.';
                setTimeout(function () { msg.textContent = ''; }, 2500);
            }
        }).catch(function () { alert('처리하지 못했습니다.'); });
    });
});
</script>
@endsection
