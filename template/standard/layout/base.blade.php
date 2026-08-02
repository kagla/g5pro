<!DOCTYPE html>
<html lang="ko" data-template="{{ $template['name'] }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title }}</title>
{{-- 값이 없으면 태그를 아예 안 낸다. 빈 description 은 없느니만 못하다 --}}
@if ($seo['description'])
<meta name="description" content="{{ $seo['description'] }}">
@endif
<link rel="canonical" href="{{ $seo['canonical'] }}">
<meta property="og:type" content="{{ $seo['og']['type'] }}">
<meta property="og:site_name" content="{{ $seo['og']['site_name'] }}">
<meta property="og:title" content="{{ $seo['og']['title'] }}">
<meta property="og:url" content="{{ $seo['og']['url'] }}">
@if ($seo['description'])
<meta property="og:description" content="{{ $seo['description'] }}">
@endif
@if ($seo['og']['image'])
<meta property="og:image" content="{{ $seo['og']['image'] }}">
@endif
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
<link rel="stylesheet" href="{{ g5_pro_asset('style.css') }}">
<script>
var g5_url = "{{ G5_URL }}", g5_bbs_url = "{{ G5_BBS_URL }}", g5_admin_url = "{{ G5_ADMIN_URL }}",
    g5_data_url = "{{ G5_DATA_URL }}",
    g5_is_member = {{ $me ? 1 : 0 }}, g5_is_mobile = false, g5_bo_table = "", g5_sca = "",
    g5_editor = "", g5_cookie_domain = "";
</script>
<script src="{{ G5_JS_URL }}/jquery-1.12.4.min.js"></script>
<script src="{{ G5_JS_URL }}/jquery-migrate-1.4.1.min.js"></script>
<script src="{{ G5_JS_URL }}/common.js"></script>
{{-- 순정이 add_javascript()/add_stylesheet() 로 요청한 것들 (주소검색 postcode.v2.js 등) --}}
{!! $page_assets !!}
@yield('head')
</head>
<body>
<header class="site-header">
    <div class="wrap">
        <h1 class="logo"><a href="{{ G5_URL }}/">{{ $site['title'] }}</a></h1>
        {{-- 쇼핑몰이 설치된 경우에만 커뮤니티/쇼핑몰 전환을 보여준다.
             두 영역을 모두 표시하고 현재 위치에 on — 하나만 보이면 현재 위치로 오해된다.
             메뉴(.gnb) 밖에 두어 햄버거로 접히지 않고 항상 상단에 남는다 --}}
        @if (count($areas))
        <nav class="area-switch" aria-label="커뮤니티/쇼핑몰 전환">
            @foreach ($areas as $a)
            @php $acls = 'area-link area-'.$a['icon'].($a['active'] ? ' on' : ''); @endphp
            <a class="{{ $acls }}" href="{{ $a['href'] }}" title="{{ $a['name'] }}" aria-label="{{ $a['name'] }}"
               @if ($a['active']) aria-current="page" @endif>
                @if ($a['icon'] === 'bag')
                {{-- 쇼핑몰: 쇼핑백. 차양 상점은 은행·건물로도 읽혀 알아보기 어려웠다.
                     카트는 헤더 장바구니 버튼이 쓰므로 형태가 겹치지 않는 백으로 간다 --}}
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5.4 8h13.2l.85 11.3a1.7 1.7 0 0 1-1.7 1.8H6.25a1.7 1.7 0 0 1-1.7-1.8L5.4 8Z"/><path d="M9 10.2V7a3 3 0 0 1 6 0v3.2"/></svg>
                @else
                {{-- 커뮤니티: 말풍선 --}}
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v7a2.5 2.5 0 0 1-2.5 2.5H9.5L4 20.5V6.5Z"/><path d="M8.5 9h7M8.5 12h4.5"/></svg>
                @endif
            </a>
            @endforeach
        </nav>
        @endif
        <nav class="gnb" id="gnb">
            @foreach ($menu as $m)
            @php $cls = 'gnb-item'.($m['on'] ? ' on' : ($m['section'] ? ' section' : '')); @endphp
            <div class="{{ $cls }}">
                <a href="{{ $m['link'] }}" target="{{ $m['target'] ?: '_self' }}"
                   @if ($m['on']) aria-current="page" @endif>{{ $m['name'] }}</a>
                @if (count($m['sub']))
                <div class="gnb-sub">
                    @foreach ($m['sub'] as $s)
                    @php $scls = $s['on'] ? 'on' : ''; @endphp
                    <a href="{{ $s['link'] }}" target="{{ $s['target'] ?: '_self' }}" class="{{ $scls }}"
                       @if ($s['on']) aria-current="page" @endif>{{ $s['name'] }}</a>
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
            {{-- 접속자는 헤더에 두지 않는다 — 모든 화면을 따라다닐 이유가 없어
                 첫 화면 카드(partials/connect_card)로 옮겼다 --}}
            {{-- 장바구니 — 쇼핑몰 설치 시 상시 노출. 담긴 게 있으면 개수 배지를 단다
                 (프로필 메뉴 안에만 있으면 열어 봐야 알 수 있어 밖으로 뺐다) --}}
            @if ($cart)
            @php $cart_label = $cart['count'] ? '장바구니 '.$cart['count'].'개' : '장바구니 (비어 있음)'; @endphp
            <a class="icon-btn cart-btn" href="{{ $cart['href'] }}" title="{{ $cart_label }}" aria-label="{{ $cart_label }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9.5" cy="20" r="1.4"/><circle cx="17" cy="20" r="1.4"/><path d="M3 4h2.4l2.2 11.2h10.2L20 7H6.2"/></svg>
                @if ($cart['count'])<b class="cart-count">{{ $cart['count'] > 99 ? '99+' : $cart['count'] }}</b>@endif
            </a>
            @endif
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
                    <a href="{{ G5_BBS_URL }}/scrap.php" class="win_scrap" target="_blank">스크랩</a>
                    <a href="{{ G5_BBS_URL }}/memo.php?kind=recv">쪽지
                        @if ($me['memo_cnt'])<b class="dot">{{ $me['memo_cnt'] }}</b>@endif
                    </a>
                    {{-- 쇼핑몰이 설치된 경우에만 — 순정은 마이페이지에만 있어 진입점이 없었다.
                         장바구니는 헤더 아이콘으로 상시 노출하므로 여기 두지 않는다 --}}
                    @if ($cart)
                    <a href="{{ G5_SHOP_URL }}/orderinquiry.php" class="sep">주문내역</a>
                    @endif
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
        @if (count($footer['links']))
        <nav class="ft-links" aria-label="사이트 정보">
            @foreach ($footer['links'] as $l)
            <a href="{{ $l['href'] }}">{{ $l['label'] }}</a>
            @endforeach
        </nav>
        @endif

        @if (count($footer['company']))
        {{-- 통신판매업자 정보 — 라벨과 값의 짝이라 dl 로 짠다.
             값이 빈 항목은 g5_pro_footer() 가 미리 걸러 여기까지 오지 않는다.

             details 로 감싸고 열린 채로 내보낸다. 데스크톱은 summary 를 숨겨 지금 모습
             그대로이고, 좁은 화면에서는 theme.js 가 접는다. 스크립트가 죽어도 정보는
             펼쳐진 채 남는다 — 의무 노출이라 안 보이는 쪽으로 실패하면 안 된다 --}}
        <details class="ft-fold" open>
            <summary>사업자정보</summary>
            <dl class="ft-company">
                @foreach ($footer['company'] as $c)
                <div class="ft-company-row">
                    <dt>{{ $c['label'] }}</dt>
                    <dd>{{ $c['value'] }}</dd>
                </div>
                @endforeach
            </dl>
        </details>
        @endif

        {{-- 연도는 해마다 손대지 않도록 서버 시각에서 뽑는다 --}}
        <p class="ft-copy">{{ date('Y') }} &copy; GNUBOARD5 PRO</p>
    </div>
</footer>
@include('partials.popups')

{{-- 검색 모달 — 헤더 돋보기 버튼으로 연다 --}}
<div class="search-modal" id="search-modal" role="dialog" aria-modal="true" aria-labelledby="search-modal-title" hidden>
    <div class="search-backdrop" data-close></div>
    <div class="search-panel">
        <h2 id="search-modal-title" class="sound_only">사이트 검색</h2>
        <form name="fsearchbox" method="get" action="{{ G5_BBS_URL }}/search.php" onsubmit="return hd_search_check(this);">
            <input type="hidden" name="sfl" value="wr_subject||wr_content">
            <input type="hidden" name="sop" value="and">
            <svg class="search-panel-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.2-4.2"/></svg>
            <label for="hd_stx" class="sound_only">검색어</label>
            <input type="text" id="hd_stx" name="stx" maxlength="20" placeholder="무엇을 찾으시나요?" autocomplete="off">
            <button type="submit" class="btn btn-primary">검색</button>
        </form>
        <p class="search-hint">두 글자 이상 입력하세요</p>
        {{-- 인기검색어 — 순정 head.php 의 popular() 자리. 검색어를 아직 안 쳤을 때만
             보이는 자리라 빈 모달을 채워 준다. 쌓기는 bbs/search.php 가 이미 하고 있다 --}}
        @php $pop = g5_popular_words(); @endphp
        @if (count($pop))
        <div class="search-popular">
            <h3>인기검색어</h3>
            <ol>
                @foreach ($pop as $p)
                <li><a href="{{ $p['href'] }}">{{ $p['word'] }}</a></li>
                @endforeach
            </ol>
        </div>
        @endif
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
<script src="{{ g5_pro_asset('theme.js') }}"></script>
</body>
</html>
