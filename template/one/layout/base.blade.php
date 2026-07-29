<!DOCTYPE html>
<html lang="ko" data-template="{{ $template['name'] }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title }}</title>
{!! $site['add_meta'] !!}
<script>
(function () {
    var t = null;
    try { t = localStorage.getItem('g5-theme'); } catch (e) {}
    var q = new URLSearchParams(location.search).get('g5theme');
    if (q === 'dark' || q === 'light') t = q; // URL 오버라이드 (저장 안 함)
    if (t === 'dark' || (t !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.dataset.theme = 'dark';
    } else {
        document.documentElement.dataset.theme = 'light';
    }
})();
</script>
<link rel="stylesheet" href="{{ $template['assets'] }}/style.css">
<script>
var g5_url = "{{ G5_URL }}", g5_bbs_url = "{{ G5_BBS_URL }}", g5_admin_url = "{{ G5_ADMIN_URL }}",
    g5_is_member = {{ $me ? 1 : 0 }}, g5_is_mobile = false, g5_bo_table = "", g5_sca = "",
    g5_editor = "", g5_cookie_domain = "";
</script>
<script src="{{ G5_JS_URL }}/jquery-1.12.4.min.js"></script>
<script src="{{ G5_JS_URL }}/jquery-migrate-1.4.1.min.js"></script>
<script src="{{ G5_JS_URL }}/common.js"></script>
@yield('head')
</head>
<body>
<header class="site-header">
    <div class="wrap">
        <h1 class="logo"><a href="{{ G5_URL }}/">{{ $site['title'] }}</a></h1>
        <nav class="gnb" id="gnb">
            @foreach ($menu as $m)
            <div class="gnb-item">
                <a href="{{ $m['link'] }}" target="{{ $m['target'] ?: '_self' }}">{{ $m['name'] }}</a>
                @if (count($m['sub']))
                <div class="gnb-sub">
                    @foreach ($m['sub'] as $s)
                    <a href="{{ $s['link'] }}" target="{{ $s['target'] ?: '_self' }}">{{ $s['name'] }}</a>
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
            {{-- 접힘 모드에서 헤더의 검색·회원가입을 대신 받는 자리 --}}
            <div class="gnb-extra">
                <button type="button" class="btn btn-block search-open">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.2-4.2"/></svg>
                    검색
                </button>
                @unless ($me)
                <a class="btn btn-primary btn-block" href="{{ G5_BBS_URL }}/register.php">회원가입</a>
                @endunless
            </div>
        </nav>
        <div class="header-util">
            <button type="button" class="icon-btn search-open" id="search-open" aria-label="검색" title="검색">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.2-4.2"/></svg>
            </button>
            <button type="button" class="icon-btn" id="theme-toggle" aria-label="테마 전환" title="테마 전환">
                <svg class="icon-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 13.3A8.2 8.2 0 0 1 10.7 3.5a8.5 8.5 0 1 0 9.8 9.8Z"/></svg>
                <svg class="icon-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4.2"/><path d="M12 2.6v2.2M12 19.2v2.2M4.2 12H2M22 12h-2.2M6.5 6.5 5 5M19 19l-1.5-1.5M17.5 6.5 19 5M5 19l1.5-1.5"/></svg>
            </button>

            @if ($me)
            <div class="profile" id="profile">
                <button type="button" class="profile-btn" aria-controls="profile-menu" aria-expanded="false">
                    <img src="{{ $me['photo'] }}" alt="" aria-hidden="true">
                    <span class="sound_only">{{ $me['mb_nick'] }} 메뉴 열기</span>
                </button>
                <div class="profile-menu" id="profile-menu">
                    <div class="profile-nick">{{ $me['mb_nick'] }}</div>
                    <a href="{{ G5_BBS_URL }}/member_confirm.php?url={{ urlencode(G5_BBS_URL.'/register_form.php') }}">정보수정</a>
                    <a href="{{ G5_BBS_URL }}/point.php">포인트 <b>{{ number_format($me['mb_point']) }}</b></a>
                    <a href="{{ G5_BBS_URL }}/scrap.php">스크랩</a>
                    <a href="{{ G5_BBS_URL }}/memo.php?kind=recv">쪽지
                        @if ($me['memo_cnt'])<b class="dot">{{ $me['memo_cnt'] }}</b>@endif
                    </a>
                    @if ($me['mb_level'] >= 10)
                    <a href="{{ G5_ADMIN_URL }}/">관리자</a>
                    @endif
                    <a href="{{ G5_BBS_URL }}/logout.php" class="sep">로그아웃</a>
                </div>
            </div>
            @else
            <a class="login-link" href="{{ G5_BBS_URL }}/login.php?url={{ urlencode(G5_URL) }}">로그인</a>
            <a class="btn btn-primary join-link" href="{{ G5_BBS_URL }}/register.php">회원가입</a>
            @endif
        </div>
        <button type="button" class="nav-toggle" aria-controls="gnb" aria-expanded="false">
            <span class="bars" aria-hidden="true"></span>
            <span class="sound_only">메뉴 열기</span>
        </button>
    </div>
</header>
<main class="site-main wrap">
@yield('content')
</main>
<footer class="site-footer">
    <div class="wrap">
        <p>{{ $site['title'] }} · powered by gnuboard5 + bladeone</p>
    </div>
</footer>
@include('partials.popups')

{{-- 검색 모달 — 헤더 돋보기 버튼으로 연다 --}}
<div class="search-modal" id="search-modal" role="dialog" aria-modal="true" aria-labelledby="search-modal-title" hidden>
    <div class="search-backdrop" data-close></div>
    <div class="search-panel">
        <h2 id="search-modal-title" class="sound_only">사이트 검색</h2>
        {{-- 닫기는 자체 줄에 둔다 — 절대배치는 폼과 겹칠 수 있다 --}}
        <div class="search-panel-head">
            <button type="button" class="icon-btn search-close" data-close aria-label="검색 닫기">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>
        <form name="fsearchbox" method="get" action="{{ G5_BBS_URL }}/search.php" onsubmit="return hd_search_check(this);">
            <input type="hidden" name="sfl" value="wr_subject||wr_content">
            <input type="hidden" name="sop" value="and">
            <svg class="search-panel-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.2-4.2"/></svg>
            <label for="hd_stx" class="sound_only">검색어</label>
            <input type="text" id="hd_stx" name="stx" maxlength="20" placeholder="무엇을 찾으시나요?" autocomplete="off">
            <button type="submit" class="btn btn-primary">검색</button>
        </form>
        <p class="search-hint">두 글자 이상 입력하세요<span class="esc-hint"> · <kbd>Esc</kbd> 로 닫기</span></p>
    </div>
</div>
<script>
function hd_search_check(f) {
    var stx = f.stx.value.trim();
    if (stx.length < 2) { alert("검색어는 두 글자 이상 입력하세요."); f.stx.focus(); return false; }
    f.stx.value = stx;
    return true;
}
</script>
<script src="{{ $template['assets'] }}/theme.js"></script>
</body>
</html>
