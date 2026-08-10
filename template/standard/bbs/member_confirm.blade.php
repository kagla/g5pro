@extends('layout.default')
@section('content')
<div class="member-box">

    {{-- 로그인·가입과 같은 제목단. 여기는 "왜 또 묻는지" 를 먼저 말해야 하는 화면이다 --}}
    <header class="auth-head">
        <h2>비밀번호 확인</h2>
        <p class="auth-sub">회원정보를 보호하기 위해 한 번 더 확인합니다.</p>
    </header>

    <form name="fmemberconfirm" method="post" action="{{ $action_url }}" autocomplete="off">
        <input type="hidden" name="mb_id" value="{{ $mb_id }}">
        <input type="hidden" name="w" value="u">

        {{-- 누구로 확인하는지 보여 준다 — 계정을 여럿 쓰는 사람이 엉뚱한 비밀번호를 넣고
             "틀렸다" 만 보는 일을 막는다 --}}
        <p class="confirm-who"><span class="muted">아이디</span> <strong>{{ $mb_id }}</strong></p>

        <div class="field">
            <label for="confirm_mb_password">비밀번호</label>
            {{-- 눈 버튼 동작은 theme.js 가 .pw-wrap 단위로 맡는다(로그인 화면과 같은 조각) --}}
            <div class="pw-wrap">
                <input type="password" id="confirm_mb_password" name="mb_password" required autofocus
                       autocomplete="current-password">
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

        <button type="submit" class="btn btn-primary btn-block">확인</button>
    </form>

    {{-- 비밀번호 찾기로 바로 보내면 안 된다 — 이미 로그인한 상태라 password_lost.php 가
         "이미 로그인중입니다" 로 튕겨내고 첫 화면으로 내보낸다(막다른 길).
         로그아웃을 거쳐 찾기 화면에 내려놓는다.
         url 은 반드시 상대 경로 — logout.php 는 스킴이나 도메인이 붙으면
         "url에 도메인을 지정할 수 없습니다" 로 거부한다(오픈 리다이렉트 방어).
         설치 위치가 하위 폴더일 수 있어 G5_BBS_URL 에서 경로만 떼어 쓴다. --}}
    @php $lost_path = parse_url(G5_BBS_URL, PHP_URL_PATH).'/password_lost.php'; @endphp

    <p class="auth-foot">
        비밀번호가 기억나지 않으세요?
        <a href="{{ G5_BBS_URL }}/logout.php?url={{ urlencode($lost_path) }}">로그아웃 후 찾기</a>
    </p>
</div>
@endsection
