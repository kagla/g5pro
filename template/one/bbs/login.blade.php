@extends('layout.default')
@section('content')
<div class="login-box">
    <h2>로그인</h2>
    <form name="flogin" method="post" action="{{ $login_action_url }}" autocomplete="off">
        <input type="hidden" name="url" value="{{ $url }}">
        <div class="field">
            <label for="login_id">아이디</label>
            <input type="text" id="login_id" name="mb_id" required autofocus>
        </div>
        <div class="field">
            <label for="login_pw">비밀번호</label>
            <input type="password" id="login_pw" name="mb_password" required>
        </div>
        <label class="auto-login"><input type="checkbox" name="auto_login" value="1"> 자동로그인</label>
        <button type="submit" class="btn btn-primary btn-block">로그인</button>
    </form>
    <div class="login-links">
        <a href="{{ G5_BBS_URL }}/register.php">회원가입</a>
        <a href="{{ G5_BBS_URL }}/password_lost.php">아이디/비밀번호 찾기</a>
    </div>
</div>
@endsection
