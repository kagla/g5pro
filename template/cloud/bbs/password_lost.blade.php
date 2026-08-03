@extends('layout.default')
@section('content')
<div class="member-box">
    <h2>회원정보 찾기</h2>
    {{-- password_lost2.php 는 mb_email 과 캡차만 읽는다 (순정 계약) --}}
    <form name="fpasswordlost" method="post" action="{{ $action_url }}" autocomplete="off"
          onsubmit="return fpasswordlost_submit(this);">
        <p class="form-lead">
            가입할 때 등록한 이메일 주소를 적어 주세요.<br>
            그 주소로 아이디와 비밀번호를 다시 정할 수 있는 메일을 보내드립니다.
        </p>
        <div class="field">
            <label for="mb_email">이메일 주소</label>
            <input type="email" id="mb_email" name="mb_email" required autofocus placeholder="가입할 때 쓴 이메일">
        </div>

        {!! $captcha_html !!}

        <button type="submit" class="btn btn-primary btn-block">인증메일 보내기</button>
    </form>
    <div class="login-links">
        <a href="{{ G5_BBS_URL }}/login.php">로그인</a>
        <a href="{{ G5_BBS_URL }}/register.php">회원가입</a>
    </div>
</div>
<script>
function fpasswordlost_submit(f) {
    if (!f.mb_email.value.trim()) { alert("이메일 주소를 입력하세요."); f.mb_email.focus(); return false; }
    {!! $captcha_js !!}
    return true;
}
</script>
@endsection
