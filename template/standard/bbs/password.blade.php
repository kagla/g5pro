{{-- 비밀번호 확인 (bbs/password.php) — 비밀글 열람·수정·삭제 전에 한 번 묻는다 --}}
@extends('layout.default')
@section('content')
<div class="member-box">
    <h2>{{ $title }}</h2>
    <form name="fboardpassword" method="post" action="{{ $action }}">
        @foreach ($hidden as $k => $v)
        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endforeach

        <p class="form-lead"><b>{{ $lead[0] }}</b><br>{{ $lead[1] }}</p>

        <div class="field">
            <label for="password_wr_password">비밀번호</label>
            <input type="password" id="password_wr_password" name="wr_password" required autofocus maxlength="20">
        </div>

        <button type="submit" class="btn btn-primary btn-block">확인</button>
    </form>
    <div class="login-links">
        <a href="{{ $list_href }}">목록으로</a>
    </div>
</div>
@endsection
