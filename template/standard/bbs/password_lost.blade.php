@extends('layout.default')
@section('content')
<div class="member-box">

    {{-- 로그인·가입과 같은 제목단. 여기 설명은 남긴다 — 무엇을 넣어야 하고 그 뒤 무슨 일이
         일어나는지(메일이 온다)는 화면만 봐서는 알 수 없다 --}}
    <header class="auth-head">
        <h2>아이디·비밀번호 찾기</h2>
        <p class="auth-sub">가입할 때 쓴 이메일로 다시 정할 수 있는 메일을 보내드립니다.</p>
    </header>

    {{-- password_lost2.php 는 mb_email 과 캡차만 읽는다 (순정 계약).
         cert_no 는 본인인증 창이 채워 넣는 자리 — 인증을 쓰지 않는 사이트에서는 빈 값으로 간다. --}}
    <form name="fpasswordlost" method="post" action="{{ $action_url }}" autocomplete="off"
          onsubmit="return fpasswordlost_submit(this);">
        <input type="hidden" name="cert_no" value="">
        <div class="field">
            <label for="mb_email">이메일 주소</label>
            <input type="email" id="mb_email" name="mb_email" required autofocus
                   autocomplete="email" placeholder="가입할 때 쓴 이메일">
        </div>

        {!! $captcha_html !!}

        <button type="submit" class="btn btn-primary btn-block">인증메일 보내기</button>
    </form>

    @if ($cert_find && ($cert_simple !== '' || $cert_ipin !== '' || $cert_hp))
    {{-- 본인인증으로 찾기 — 메일을 못 받는 경우의 두 번째 길. 창을 여는 일은 순정 certify.js 가 한다 --}}
    <div class="auth-alt">
        <p class="auth-alt-or"><span>또는</span></p>
        <p class="auth-alt-lead">가입한 이메일이 기억나지 않으면 본인인증으로 찾을 수 있습니다.</p>
        <div class="auth-alt-btns">

            @if ($cert_simple !== '')
            <button type="button" id="win_sa_kakao_cert" class="btn btn-ghost win_sa_cert" data-type="">간편인증</button>
            @endif

            @if ($cert_hp)
            <button type="button" id="win_hp_cert" class="btn btn-ghost">휴대폰 본인확인</button>
            @endif

            @if ($cert_ipin !== '')
            <button type="button" id="win_ipin_cert" class="btn btn-ghost">아이핀 본인확인</button>
            @endif

        </div>
    </div>
    @endif

    <p class="auth-foot">
        <a href="{{ G5_BBS_URL }}/login.php">로그인</a> ·
        <a href="{{ G5_BBS_URL }}/register.php">회원가입</a>
    </p>
</div>
@if ($certify_js !== '')
<script src="{{ $certify_js }}"></script>
@endif

<script>
function fpasswordlost_submit(f) {
    if (!f.mb_email.value.trim()) { alert("이메일 주소를 입력하세요."); f.mb_email.focus(); return false; }
    {!! $captcha_js !!}
    return true;
}

// 본인인증 — 여는 주소·유형은 서버(매퍼)가 정해 준다. 여기는 창만 띄운다.
// pageType=find 는 인증창이 "찾기" 흐름임을 알아보는 값(순정 스킨과 같은 값을 보낸다).
$(function () {
    var pageTypeParam = 'pageType=find';

    @if ($cert_simple !== '')
    $('.win_sa_cert').on('click', function () {
        var type = $(this).data('type') || '';
        call_sa('{{ $cert_simple }}' + '?directAgency=' + type + '&' + pageTypeParam);
    });
    @endif

    @if ($cert_ipin !== '')
    $('#win_ipin_cert').on('click', function () {
        certify_win_open('kcb-ipin', '{{ $cert_ipin }}' + '?' + pageTypeParam);
    });
    @endif

    @if ($cert_hp)
    $('#win_hp_cert').on('click', function () {
        certify_win_open('{{ $cert_hp['type'] }}', '{{ $cert_hp['url'] }}' + '?' + pageTypeParam);
    });
    @endif

});
</script>
@endsection
