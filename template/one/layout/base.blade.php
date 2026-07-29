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
            {{-- 접힘 모드에서만 보이는 계정 영역 --}}
            <div class="gnb-util">
                @if ($me)
                <span class="me">{{ $me['mb_nick'] }}님</span>
                <a href="{{ G5_BBS_URL }}/member_confirm.php?url={{ urlencode(G5_BBS_URL.'/register_form.php') }}">정보수정</a>
                <a href="{{ G5_BBS_URL }}/memo.php?kind=recv">쪽지함</a>
                <a href="{{ G5_BBS_URL }}/point.php">포인트</a>
                <a href="{{ G5_BBS_URL }}/logout.php">로그아웃</a>
                @else
                <a href="{{ G5_BBS_URL }}/login.php?url={{ urlencode(G5_URL) }}">로그인</a>
                <a href="{{ G5_BBS_URL }}/register.php">회원가입</a>
                @endif
            </div>
        </nav>
        <form class="hd-search" name="fsearchbox" method="get" action="{{ G5_BBS_URL }}/search.php"
              onsubmit="return hd_search_check(this);">
            <input type="hidden" name="sfl" value="wr_subject||wr_content">
            <input type="hidden" name="sop" value="and">
            <label for="hd_stx" class="sound_only">검색어</label>
            <input type="text" id="hd_stx" name="stx" maxlength="20" placeholder="검색어">
            <button type="submit" class="btn">검색</button>
        </form>
        <div class="header-util">
            <span class="util-links">
                @if ($me)
                <span class="me">{{ $me['mb_nick'] }}</span>
                <a href="{{ G5_BBS_URL }}/logout.php">로그아웃</a>
                @else
                <a href="{{ G5_BBS_URL }}/login.php?url={{ urlencode(G5_URL) }}">로그인</a>
                <a href="{{ G5_BBS_URL }}/register.php">회원가입</a>
                @endif
            </span>
            <button type="button" id="theme-toggle" aria-label="테마 전환" title="테마 전환">
                <svg class="icon-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 13.3A8.2 8.2 0 0 1 10.7 3.5a8.5 8.5 0 1 0 9.8 9.8Z"/></svg>
                <svg class="icon-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4.2"/><path d="M12 2.6v2.2M12 19.2v2.2M4.2 12H2M22 12h-2.2M6.5 6.5 5 5M19 19l-1.5-1.5M17.5 6.5 19 5M5 19l1.5-1.5"/></svg>
            </button>
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
