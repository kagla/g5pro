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
<link rel="stylesheet" href="{{ $template['assets'] }}/style.css">
@yield('head')
</head>
{{-- 새 창(window.open)으로 열리는 작은 화면 — 헤더·메뉴·푸터 없이 내용만 --}}
<body class="popup-body">
<div class="popup">
    <h1 class="popup-title">{{ $title }}</h1>
    @yield('content')
</div>
</body>
</html>
