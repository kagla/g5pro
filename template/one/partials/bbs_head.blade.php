{{-- 게시판 상단: 이름·건수·카테고리 — 목록 변형 4종이 공용으로 쓴다 --}}
<header class="bbs-head">
    <h2><span class="chip">게시판</span>{{ $board['bo_subject'] }}</h2>
    <div class="bbs-head-right">
        <div class="bbs-meta">전체 {{ number_format($total_count) }}건 · {{ $page }} / {{ max($total_page, 1) }} 페이지</div>
        {{-- 최고관리자·그룹관리자에게만 채워진다 (bbs/board.php).
             URL 이 이미 &amp; 로 인코딩돼 있어 이스케이프 없이 내보낸다 --}}
        @if ($admin_href)
        <a class="icon-btn bbs-admin-link" href="{!! $admin_href !!}" title="게시판 관리" aria-label="게시판 관리">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3.2"/><path d="M19.1 14.6a1.5 1.5 0 0 0 .3 1.7l.1.1a1.9 1.9 0 1 1-2.7 2.7l-.1-.1a1.5 1.5 0 0 0-1.7-.3 1.5 1.5 0 0 0-.9 1.4v.2a1.9 1.9 0 1 1-3.8 0v-.1a1.5 1.5 0 0 0-1-1.4 1.5 1.5 0 0 0-1.7.3l-.1.1a1.9 1.9 0 1 1-2.7-2.7l.1-.1a1.5 1.5 0 0 0 .3-1.7 1.5 1.5 0 0 0-1.4-.9h-.2a1.9 1.9 0 1 1 0-3.8h.1a1.5 1.5 0 0 0 1.4-1 1.5 1.5 0 0 0-.3-1.7l-.1-.1a1.9 1.9 0 1 1 2.7-2.7l.1.1a1.5 1.5 0 0 0 1.7.3h.1a1.5 1.5 0 0 0 .9-1.4v-.2a1.9 1.9 0 1 1 3.8 0v.1a1.5 1.5 0 0 0 .9 1.4 1.5 1.5 0 0 0 1.7-.3l.1-.1a1.9 1.9 0 1 1 2.7 2.7l-.1.1a1.5 1.5 0 0 0-.3 1.7v.1a1.5 1.5 0 0 0 1.4.9h.2a1.9 1.9 0 1 1 0 3.8h-.1a1.5 1.5 0 0 0-1.4.9Z"/></svg>
        </a>
        @endif
    </div>
</header>

@if (count($categories))
<nav class="bbs-cate">
    <a href="{{ $board_url }}">전체</a>
    @foreach ($categories as $c)
    @php $cls = $c['active'] ? 'active' : ''; @endphp
    <a href="{{ $c['href'] }}" class="{{ $cls }}">{{ $c['name'] }}</a>
    @endforeach
</nav>
@endif
