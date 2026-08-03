@extends('layout.default')
@section('content')
<div class="member-box">
    <h2>비밀번호 확인</h2>
    <p class="muted">회원정보를 보호하기 위해 비밀번호를 다시 한 번 확인합니다.</p>
    <form name="fmemberconfirm" method="post" action="{{ $action_url }}" autocomplete="off">
        <input type="hidden" name="mb_id" value="{{ $mb_id }}">
        <input type="hidden" name="w" value="u">
        <div class="field">
            <label for="confirm_mb_password">비밀번호</label>
            <input type="password" id="confirm_mb_password" name="mb_password" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary btn-block">확인</button>
    </form>
</div>
@endsection
