@extends('layout.default')
@section('content')
<div class="member-box wide">

    {{-- 제목 한 줄만 있던 자리 — 어디까지 왔는지(약관 다음), 무엇을 요구하는지 먼저 말한다 --}}
    <header class="auth-head">

        @if ($w === 'u')
        <h2>회원정보 수정</h2>
        <p class="auth-sub">바꾸고 싶은 항목만 고쳐서 저장하세요.</p>
        @else
        <p class="auth-step"><span>약관 동의</span><em>정보 입력</em></p>
        <h2>회원가입</h2>
        <p class="auth-sub">별표(<span class="req-mark">*</span>)만 채우면 가입이 끝납니다.</p>
        @endif

    </header>

    <form id="fregisterform" name="fregisterform" method="post" action="{{ $action_url }}"
          enctype="multipart/form-data" autocomplete="off" onsubmit="return fregisterform_check(this);">
    <input type="hidden" name="w" value="{{ $w }}">
    <input type="hidden" name="url" value="{{ $url }}">
    <input type="hidden" name="agree" value="{{ $agree }}">
    <input type="hidden" name="agree2" value="{{ $agree2 }}">
    <input type="hidden" name="mb_nick_default" value="{!! $form['mb_nick'] !!}">

    @if ($w === 'u')
    <input type="hidden" name="old_email" value="{{ $form['mb_email'] }}">
    @endif

    {{-- 한 줄에 늘어놓던 칸들을 뜻으로 묶는다 — 로그인에 쓰는 것 / 남에게 보이는 것 / 연락받는 것 --}}
    <section class="form-sec">
        <h3>로그인 정보</h3>

        <div class="field">
            <label for="reg_mb_id"><span class="req">아이디</span></label>

            @if ($w === 'u')
            <input type="text" id="reg_mb_id" value="{{ $form['mb_id'] }}" readonly>
            <input type="hidden" name="mb_id" value="{{ $form['mb_id'] }}">
            @else
            {{-- autocomplete 를 끄는 이유: 아이디 칸 뒤에 password 칸이 오면 브라우저가 이 폼을
                 로그인 폼으로 보고 저장해 둔 아이디·비밀번호를 채워 넣는다. 가입 폼은 늘 빈칸이어야
                 한다 — 폼 전체의 autocomplete="off" 는 자격증명 칸에서 무시된다. --}}
            <input type="text" id="reg_mb_id" name="mb_id" required minlength="3" maxlength="20"
                   autocomplete="off" placeholder="영문·숫자 3~20자">
            <p class="field-msg" data-for="reg_mb_id" hidden></p>
            @endif

        </div>

        {{-- 비밀번호와 확인은 나란히 — 세로로 쌓으면 폼이 그만큼 길어지고, 둘은 함께 보며 맞추는 값이다 --}}
        <div class="field-row">
        <div class="field">
            <label for="reg_mb_password">
                @if ($w === 'u')
                비밀번호 <span class="muted">(변경할 때만)</span>
                @else
                <span class="req">비밀번호</span>
                @endif
            </label>
            {{-- new-password — 저장된 비밀번호를 채우지 말라는 표준 신호(가입·변경 폼의 정답).
                 눈 버튼 동작은 theme.js 가 .pw-wrap 단위로 맡는다(로그인 화면과 같은 조각). --}}
            <div class="pw-wrap">
                <input type="password" id="reg_mb_password" name="mb_password" maxlength="20"
                       autocomplete="new-password" {{ $w === 'u' ? '' : 'required' }}>
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

        <div class="field">
            <label for="reg_mb_password_re">
                @if ($w === 'u')
                비밀번호 확인
                @else
                <span class="req">비밀번호 확인</span>
                @endif
            </label>
            <div class="pw-wrap">
                <input type="password" id="reg_mb_password_re" name="mb_password_re" maxlength="20"
                       autocomplete="new-password" {{ $w === 'u' ? '' : 'required' }}>
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
            <p class="field-msg" data-for="reg_mb_password_re" hidden></p>
        </div>
        </div>
    </section>

    <section class="form-sec">
        <h3>프로필</h3>

        {{-- 짧은 칸 둘은 넓은 화면에서 한 줄에 — 세로로만 쌓이면 폼이 끝없이 길어 보인다 --}}
        <div class="field-row">
            <div class="field">
                <label for="reg_mb_name"><span class="req">이름</span></label>
                <input type="text" id="reg_mb_name" name="mb_name" value="{!! $form['mb_name'] !!}" required>
            </div>
            <div class="field">
                <label for="reg_mb_nick"><span class="req">닉네임</span></label>
                <input type="text" id="reg_mb_nick" name="mb_nick" value="{!! $form['mb_nick'] !!}" required>
                <p class="field-msg" data-for="reg_mb_nick" hidden></p>
            </div>
        </div>
    </section>

    <section class="form-sec">
        <h3>연락처</h3>

        <div class="field-row">
            <div class="field">
                <label for="reg_mb_email"><span class="req">이메일</span></label>
                <input type="email" id="reg_mb_email" name="mb_email" value="{{ $form['mb_email'] }}" required
                       placeholder="you@example.com">
                <p class="field-msg" data-for="reg_mb_email" hidden></p>
            </div>

            @if ($use_hp)
            <div class="field">
                <label for="reg_mb_hp">

                    @if ($hp_required)
                    <span class="req">휴대폰</span>
                    @else
                    휴대폰 <span class="muted">선택</span>
                    @endif

                </label>
                {{-- data-hp — 입력하는 동안 하이픈을 자동으로 끼운다(규칙은 theme.js 한 곳) --}}
                <input type="tel" id="reg_mb_hp" name="mb_hp" value="{{ $form['mb_hp'] }}"
                       placeholder="010-0000-0000" maxlength="20" data-hp
                       {{ $hp_required ? 'required' : '' }} {{ $hp_readonly ? 'readonly' : '' }}>
            </div>
            @endif

        </div>

        @if ($use_addr)
        {{-- 주소 — 순정 win_zip() 이 form 이름과 input 이름으로 찾아 채운다(끼워 넣을 자리도 스스로 만든다).
             그래서 이름은 mb_zip·mb_addr1~3·mb_addr_jibeon 그대로여야 하고, 저장 쪽(register_form_update)이
             mb_zip 한 칸을 받아 앞3·뒤2 로 다시 쪼갠다. --}}
        <div class="field">
            <label for="reg_mb_zip">

                @if ($req_addr)
                <span class="req">주소</span>
                @else
                주소 <span class="muted">선택</span>
                @endif

            </label>
            <div class="addr-zip">
                <input type="text" id="reg_mb_zip" name="mb_zip" value="{{ $form['mb_zip'] }}"
                       maxlength="6" placeholder="우편번호" {{ $req_addr ? 'required' : '' }}>
                <button type="button" class="btn-ghost"
                        onclick="win_zip('fregisterform', 'mb_zip', 'mb_addr1', 'mb_addr2', 'mb_addr3', 'mb_addr_jibeon');">주소 검색</button>
            </div>
            <input type="text" name="mb_addr1" value="{{ $form['mb_addr1'] }}" placeholder="기본 주소"
                   {{ $req_addr ? 'required' : '' }}>
            <input type="text" name="mb_addr2" value="{{ $form['mb_addr2'] }}" placeholder="상세 주소">
            {{-- 참고 항목은 win_zip 이 채워 주는 값이라 칸으로 보여 줄 것이 없다 — 값만 실어 보낸다 --}}
            <input type="hidden" name="mb_addr3" value="{{ $form['mb_addr3'] }}">
            <input type="hidden" name="mb_addr_jibeon" value="{{ $form['mb_addr_jibeon'] }}">
        </div>
        @endif

    </section>

    {!! $captcha_html !!}

    <button type="submit" class="btn btn-primary btn-block">{{ $w === 'u' ? '정보수정' : '가입 완료' }}</button>

    @if ($w !== 'u')
    <p class="auth-foot">이미 계정이 있으신가요? <a href="{{ G5_BBS_URL }}/login.php">로그인</a></p>
    @endif

    </form>
</div>

<script src="{{ G5_JS_URL }}/jquery.register_form.js"></script>
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
    {!! $captcha_js !!}
    return true;
}

// 칸을 떠날 때 그 자리에서 알려 준다.
// 순정 검사 함수(reg_mb_*_check)는 문제가 있으면 문구를, 없으면 빈 문자열을 돌려주는데
// 예전에는 onblur 에서 부르기만 하고 그 값을 버려서, 손님은 [가입] 을 누르는 순간에야
// 경고창으로 처음 알았다. 같은 함수를 쓰되 결과를 칸 아래에 적는다.
$(function () {
    function say(id, text, ok) {
        var $m = $('.field-msg[data-for="' + id + '"]');
        if (!$m.length) return;
        $m.prop('hidden', text === '').text(text).toggleClass('is-ok', !!ok).toggleClass('is-err', !ok);
        $('#' + id).toggleClass('is-invalid', !ok && text !== '');
    }

    function bindCheck(id, fn, okText) {
        $('#' + id).on('blur', function () {
            if ($.trim(this.value) === '') { say(id, '', true); return; }
            var msg = fn();
            say(id, msg ? msg : okText, !msg);
        });
    }

    @if ($w !== 'u')
    bindCheck('reg_mb_id', reg_mb_id_check, '사용할 수 있는 아이디입니다.');
    @endif

    bindCheck('reg_mb_nick', reg_mb_nick_check, '사용할 수 있는 닉네임입니다.');
    bindCheck('reg_mb_email', reg_mb_email_check, '사용할 수 있는 이메일입니다.');

    // 비밀번호 확인 — 다 치고 [가입] 을 누른 뒤에야 틀린 것을 아는 일이 없게 그 자리에서 맞춘다
    var $pw = $('#reg_mb_password'), $pw2 = $('#reg_mb_password_re');
    function matchCheck() {
        if ($pw2.val() === '') { say('reg_mb_password_re', '', true); return; }
        var same = ($pw.val() === $pw2.val());
        say('reg_mb_password_re', same ? '비밀번호가 일치합니다.' : '비밀번호가 일치하지 않습니다.', same);
    }
    $pw.add($pw2).on('input', matchCheck);
});
</script>
@endsection
