@extends('layout.default')
@section('content')
<div class="member-box">

    {{-- 가입 흐름(register)과 같은 제목단. 설명은 붙이지 않는다 — 칸 이름이 이미 '아이디'·'비밀번호'라
         한 번 더 적으면 같은 말을 두 번 하는 것이 된다(설명은 알려 줄 것이 있을 때만). --}}
    <header class="auth-head">
        <h2>로그인</h2>
    </header>

    <form name="flogin" method="post" action="{{ $login_action_url }}" autocomplete="off">
        <input type="hidden" name="url" value="{{ $url }}">
        <div class="field">
            <label for="login_id">아이디</label>
            <input type="text" id="login_id" name="mb_id" required autofocus>
        </div>
        <div class="field">
            <label for="login_pw">비밀번호</label>
            {{-- 눈 버튼으로 가림을 껐다 켰다 한다. 동작은 theme.js 가 .pw-wrap 단위로 맡으므로
                 다른 화면에 붙일 때도 이 감싸개와 버튼만 그대로 옮기면 된다. --}}
            <div class="pw-wrap">
                <input type="password" id="login_pw" name="mb_password" required>
                <button type="button" class="pw-eye" aria-label="비밀번호 표시" aria-pressed="false" title="비밀번호 표시">
                    <svg class="pw-eye-i pw-eye-show" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M2 12s3.6-6.5 10-6.5S22 12 22 12s-3.6 6.5-10 6.5S2 12 2 12Z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg class="pw-eye-i pw-eye-hide" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M2 12s3.6-6.5 10-6.5c1.7 0 3.2.4 4.5 1M22 12s-3.6 6.5-10 6.5c-1.7 0-3.2-.4-4.5-1"/>
                        <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>
                        <path d="M3 3l18 18"/>
                    </svg>
                </button>
            </div>
        </div>
        {{-- 자동로그인과 찾기 링크를 한 줄에 — 로그인 폼의 흔한 배치이고, 세로도 한 줄 줄어든다 --}}
        <div class="login-row">
            <label class="auto-login"><input type="checkbox" name="auto_login" value="1"> 자동로그인</label>
            <a href="{{ G5_BBS_URL }}/password_lost.php">아이디·비밀번호 찾기</a>
        </div>
        <button type="submit" class="btn btn-primary btn-block">로그인</button>
    </form>

    <p class="auth-foot">아직 회원이 아니신가요? <a href="{{ G5_BBS_URL }}/register.php">회원가입</a></p>
</div>
@endsection
