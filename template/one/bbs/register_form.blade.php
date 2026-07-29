@extends('layout.default')
@section('content')
<div class="member-box wide">
    <h2>{{ $w === 'u' ? '회원정보 수정' : '회원가입' }}</h2>

    <form id="fregisterform" name="fregisterform" method="post" action="{{ $action_url }}"
          enctype="multipart/form-data" autocomplete="off" onsubmit="return fregisterform_check(this);">
    <input type="hidden" name="w" value="{{ $w }}">
    <input type="hidden" name="url" value="{{ $url }}">
    <input type="hidden" name="agree" value="{{ $agree }}">
    <input type="hidden" name="agree2" value="{{ $agree2 }}">
    <input type="hidden" name="mb_nick_default" value="{!! $me['mb_nick'] !!}">
    @if ($w === 'u')
    <input type="hidden" name="old_email" value="{{ $me['mb_email'] }}">
    @endif

    <div class="field">
        <label for="reg_mb_id">아이디</label>
        @if ($w === 'u')
        <input type="text" id="reg_mb_id" value="{{ $me['mb_id'] }}" readonly>
        <input type="hidden" name="mb_id" value="{{ $me['mb_id'] }}">
        @else
        <input type="text" id="reg_mb_id" name="mb_id" required minlength="3" maxlength="20"
               onblur="reg_mb_id_check();">
        @endif
    </div>

    <div class="field">
        <label for="reg_mb_password">비밀번호 @if ($w === 'u')<span class="muted">(변경 시에만 입력)</span>@endif</label>
        <input type="password" id="reg_mb_password" name="mb_password" maxlength="20" {{ $w === 'u' ? '' : 'required' }}>
    </div>
    <div class="field">
        <label for="reg_mb_password_re">비밀번호 확인</label>
        <input type="password" id="reg_mb_password_re" name="mb_password_re" maxlength="20" {{ $w === 'u' ? '' : 'required' }}>
    </div>

    <div class="field">
        <label for="reg_mb_name">이름</label>
        <input type="text" id="reg_mb_name" name="mb_name" value="{!! $me['mb_name'] !!}" required>
    </div>
    <div class="field">
        <label for="reg_mb_nick">닉네임</label>
        <input type="text" id="reg_mb_nick" name="mb_nick" value="{!! $me['mb_nick'] !!}" required
               onblur="reg_mb_nick_check();">
    </div>
    <div class="field">
        <label for="reg_mb_email">이메일</label>
        <input type="email" id="reg_mb_email" name="mb_email" value="{{ $me['mb_email'] }}" required
               onblur="reg_mb_email_check();">
    </div>
    <div class="field">
        <label for="reg_mb_homepage">홈페이지 <span class="muted">(선택)</span></label>
        <input type="text" id="reg_mb_homepage" name="mb_homepage" value="{!! $me['mb_homepage'] !!}">
    </div>

    <div class="field">{!! $captcha_html !!}</div>

    <button type="submit" class="btn btn-primary btn-block">{{ $w === 'u' ? '정보수정' : '회원가입' }}</button>
    </form>
</div>

<script src="{{ G5_JS_URL }}/jquery.register_form.js"></script>
{!! $captcha_js !!}
<script>
function fregisterform_check(f) {
    if (f.mb_password.value !== f.mb_password_re.value) {
        alert("비밀번호가 일치하지 않습니다."); f.mb_password_re.focus(); return false;
    }
    @if ($w !== 'u')
    var msg = reg_mb_id_check() || reg_mb_nick_check() || reg_mb_email_check();
    @else
    var msg = reg_mb_nick_check() || reg_mb_email_check();
    @endif
    if (msg) { alert(msg); return false; }
    return true;
}
</script>
@endsection
