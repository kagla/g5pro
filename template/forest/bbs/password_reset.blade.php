{{-- 비밀번호 재설정 (bbs/password_reset.php)
     본인인증으로 아이디를 찾은 뒤 새 비밀번호를 정하는 화면. 여기 오기 전에 순정이
     세션으로 본인 확인을 끝냈으므로 이 폼은 새 비밀번호만 받는다.
     구성은 로그인 화면(.member-box · .field)을 그대로 따른다. --}}
@extends('layout.default')
@section('content')
<div class="member-box">
    <h2>비밀번호 재설정</h2>
    <p class="muted">회원 아이디 <b>{{ $mb_id }}</b></p>
    <form name="fpasswordreset" method="post" action="{{ $action }}"
          onsubmit="return password_reset_submit(this);" autocomplete="off">
        <input type="hidden" name="mb_id" value="{{ $mb_id }}">
        <div class="field">
            <label for="mb_pw">새 비밀번호</label>
            <input type="password" id="mb_pw" name="mb_password" required maxlength="20" autofocus>
        </div>
        <div class="field">
            <label for="mb_pw2">새 비밀번호 확인</label>
            <input type="password" id="mb_pw2" name="mb_password_re" required maxlength="20">
        </div>
        <button type="submit" class="btn btn-primary btn-block">확인</button>
    </form>
</div>

<script>
// 순정 password_reset_update.php 계약 — 두 칸이 같아야 보낸다
function password_reset_submit(f) {
    if (f.mb_password.value !== f.mb_password_re.value) {
        alert("새 비밀번호와 비밀번호 확인이 일치하지 않습니다.");
        f.mb_password_re.focus();
        return false;
    }
    return true;
}
</script>
@endsection
