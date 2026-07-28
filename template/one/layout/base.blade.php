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
        <nav class="gnb">
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
        <div class="header-util">
            @if ($me)
            <span class="me">{{ $me['mb_nick'] }}</span>
            <a href="{{ G5_BBS_URL }}/logout.php">로그아웃</a>
            @else
            <a href="{{ G5_BBS_URL }}/login.php?url={{ urlencode(G5_URL) }}">로그인</a>
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
<script src="{{ $template['assets'] }}/theme.js"></script>
</body>
</html>
