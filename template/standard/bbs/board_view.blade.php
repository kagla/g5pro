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

    {{-- 이미지 첨부 — 순정과 같이 본문 위에 둔다 (view.skin.php 의 bo_v_img).
         본문이 {이미지:n} 으로 이미 부른 것은 매퍼가 걸러 보낸다 --}}
    @if (count($images))
    <div class="post-images">
        @foreach ($images as $img)
        <div class="post-image">{!! $img !!}</div>
        @endforeach
    </div>
    @endif

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
    <div id="comment-form-home" hidden></div>{{-- 답글·수정을 마치면 폼이 이 자리로 돌아온다 --}}
    <form name="fviewcomment" id="fviewcomment" class="comment-form" method="post" action="{{ $comment_action }}"
          onsubmit="return fviewcomment_submit(this);" autocomplete="off">
        @foreach ($comment_hidden as $hname => $hval)
        <input type="hidden" name="{{ $hname }}" value="{{ $hval }}">
        @endforeach
        <div class="comment-form-state" hidden>
            <span class="s"></span>
            <button type="button" class="linklike c-cancel">취소</button>
        </div>
        <textarea name="wr_content" rows="3" required placeholder="댓글을 남겨주세요"></textarea>
        <button type="submit" class="btn btn-primary">댓글 등록</button>
    </form>
    <script>
    function fviewcomment_submit(f) {
        if (!f.wr_content.value.trim()) { alert("댓글 내용을 입력하세요."); f.wr_content.focus(); return false; }
        set_comment_token(f); // 순정 common.js — ajax.comment_token.php 에서 일회용 토큰 주입
        return true;
    }
    // 답글(w=c + comment_id)·수정(w=cu)은 해당 댓글 바로 아래로 입력폼을 옮겨서 받는다.
    // 폼은 하나뿐이라 순정 write_comment_update.php 계약이 그대로 유지된다.
    (function () {
        var f = document.getElementById('fviewcomment');
        var home = document.getElementById('comment-form-home');
        var state = f.querySelector('.comment-form-state');
        var label = state.querySelector('.s');
        var submit = f.querySelector('button[type="submit"]');

        function reset() {
            f.w.value = 'c'; f.comment_id.value = ''; f.wr_content.value = '';
            state.hidden = true; submit.textContent = '댓글 등록';
            f.classList.remove('inline'); f.style.marginLeft = '';
            home.insertAdjacentElement('afterend', f);
        }
        function attach(btn, w, content, text, btnText) {
            var c = btn.closest('.comment');
            c.insertAdjacentElement('afterend', f);
            f.classList.add('inline'); f.style.marginLeft = c.style.marginLeft;
            f.w.value = w; f.comment_id.value = btn.dataset.id; f.wr_content.value = content;
            label.textContent = text; state.hidden = false; submit.textContent = btnText;
            f.wr_content.focus();
        }
        document.querySelectorAll('.c-reply').forEach(function (b) {
            b.addEventListener('click', function () { attach(b, 'c', '', '이 댓글에 답글 쓰는 중', '답글 등록'); });
        });
        document.querySelectorAll('.c-edit').forEach(function (b) {
            b.addEventListener('click', function () { attach(b, 'cu', b.dataset.raw, '댓글 수정 중', '수정 완료'); });
        });
        state.querySelector('.c-cancel').addEventListener('click', reset);
    })();
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
        @if ($update_href)<a class="btn" href="{!! $update_href !!}">수정</a>@endif
        @if ($write_href)<a class="btn btn-primary" href="{{ $write_href }}">글쓰기</a>@endif
        {{-- 가끔 쓰는 복사(관리자)·이동(관리자)·삭제는 점 세 개 메뉴로 접어 줄 폭을 아낀다 --}}
        @if ($copy_href || $move_href || $delete_href)
        <div class="kebab">
            <button type="button" class="icon-btn kebab-btn" aria-haspopup="true" aria-expanded="false"
                    aria-label="게시물 관리" title="게시물 관리">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
            </button>
            <div class="kebab-menu" role="menu">
                @if ($copy_href)
                <a href="{!! $copy_href !!}" role="menuitem" onclick="window.open(this.href, 'g5move', 'left=60,top=60,width=560,height=640,scrollbars=1'); return false;">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M15 5.5A1.5 1.5 0 0 0 13.5 4h-8A1.5 1.5 0 0 0 4 5.5v8A1.5 1.5 0 0 0 5.5 15"/></svg>
                    복사
                </a>
                @endif
                @if ($move_href)
                <a href="{!! $move_href !!}" role="menuitem" onclick="window.open(this.href, 'g5move', 'left=60,top=60,width=560,height=640,scrollbars=1'); return false;">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h14"/><path d="m13 7 5 5-5 5"/></svg>
                    이동
                </a>
                @endif
                @if ($delete_href)
                <a href="{!! $delete_href !!}" role="menuitem" class="danger" onclick="return confirm('삭제하시겠습니까?');">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4.5 7h15"/><path d="M9.5 7V5h5v2"/><path d="M6.5 7 7.6 20h8.8L17.5 7"/></svg>
                    삭제
                </a>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

@if ($prev || $next)
<nav class="post-nav">
    @if ($prev)
    <a class="post-nav-item prev" href="{!! $prev['href'] !!}">
        <span class="k">← 이전글</span>
        <span class="t">{{ $prev['subject'] }}</span>
        <span class="d">{{ $prev['date'] }}</span>
    </a>
    @endif
    @if ($next)
    <a class="post-nav-item next" href="{!! $next['href'] !!}">
        <span class="k">다음글 →</span>
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
