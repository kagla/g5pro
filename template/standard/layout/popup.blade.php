<!DOCTYPE html>
<html lang="ko" data-template="{{ $template['name'] }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title }}</title>
<script>
(function () {
    var t = null;
    try { t = localStorage.getItem('g5-theme'); } catch (e) {}
    if (t === 'dark' || (t !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.dataset.theme = 'dark';
    } else {
        document.documentElement.dataset.theme = 'light';
    }
})();
</script>
<link rel="stylesheet" href="{{ g5_pro_asset('style.css') }}">
{{-- 순정 화면이 <head> 에서 깔아 주던 전역과 스크립트를, 새 창에도 똑같이 깐다.
     head.sub.php 는 g5pro 화면에서 맨 윗줄 pro_takeover() 로 그냥 돌아서므로
     jQuery·common.js 를 큐에 넣는 대목까지 통째로 건너뛴다 — 여기서 안 실으면
     새 창에는 jQuery 가 아예 없다. 실제로 자동등록방지가 이것 때문에 안 떴다:
     captcha_html() 이 본문에 끼워 넣는 kcaptcha.js 가 `$(function(){…})` 로 시작해
     첫 줄에서 죽고, #captcha_img 는 자리표시용 dot.gif 인 채로 남는다.
     본문보다 먼저 와야 한다 — kcaptcha.js 는 본문 한가운데서 바로 실행된다. --}}
<script>
var g5_url = "{{ G5_URL }}", g5_bbs_url = "{{ G5_BBS_URL }}", g5_admin_url = "{{ G5_ADMIN_URL }}",
    g5_data_url = "{{ G5_DATA_URL }}",
    g5_is_member = {{ $me ? 1 : 0 }}, g5_is_mobile = false, g5_bo_table = "", g5_sca = "",
    g5_editor = "", g5_cookie_domain = "";
</script>
<script src="{{ G5_JS_URL }}/jquery-3.7.1.min.js"></script>
<script src="{{ G5_JS_URL }}/jquery-migrate-3.6.0.min.js"></script>
<script src="{{ G5_JS_URL }}/common.js"></script>
{!! $page_assets !!}
@yield('head')
</head>
{{-- 새 창(window.open)으로 열리는 작은 화면 — 헤더·메뉴·푸터 없이 내용만 --}}
<body class="popup-body">
{{-- 화면별 변형 클래스 — 쓰지 않는 화면은 빈 문자열이라 지금까지와 같다 --}}
<div class="popup @yield('popup_class')">
    <h1 class="popup-title">{{ $title }}</h1>
    @yield('content')
</div>
{{-- 본문 화면과 같은 동작 묶음 — g5Toast·g5Confirm 이 여기 있다(메일 보내기 팝업이 쓴다).
     모듈마다 제 요소가 없으면 곧바로 돌아서므로 헤더·메뉴가 없는 새 창에서도 조용하다 --}}
<script src="{{ g5_pro_asset('theme.js') }}"></script>
</body>
</html>
