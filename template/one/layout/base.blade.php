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
        <button type="button" class="nav-toggle" aria-controls="gnb" aria-expanded="false">
            <span class="bars" aria-hidden="true"></span>
            <span class="sound_only">메뉴 열기</span>
        </button>
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
            @if ($me)
            <span class="me">{{ $me['mb_nick'] }}</span>
            <a href="{{ G5_BBS_URL }}/logout.php">로그아웃</a>
            @else
            <a href="{{ G5_BBS_URL }}/login.php?url={{ urlencode(G5_URL) }}">로그인</a>
            <a href="{{ G5_BBS_URL }}/register.php">회원가입</a>
            @endif
            <button type="button" id="theme-toggle" aria-label="테마 전환">◐</button>
        </div>
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
