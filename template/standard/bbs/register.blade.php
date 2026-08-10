@extends('layout.default')
@section('content')
<div class="member-box wide">

    {{-- 다음 화면(register_form)과 같은 제목단 — 같은 흐름의 1·2단계라 모양이 어긋나면 안 된다 --}}
    <header class="auth-head">
        <p class="auth-step"><em>약관 동의</em><span>정보 입력</span></p>
        <h2>회원가입</h2>
        <p class="auth-sub">아래 두 가지에 동의하면 다음 단계로 넘어갑니다.</p>
    </header>
    <form name="fregister" id="fregister" method="post" action="{{ $action_url }}" autocomplete="off"
          onsubmit="return fregister_check(this);">

    <section class="stip">
        <h3>회원가입약관</h3>
        <textarea readonly rows="8">{!! $stipulation !!}</textarea>
        <label><input type="checkbox" name="agree" value="1"> 회원가입약관에 동의합니다.</label>
    </section>

    <section class="stip">
        <h3>개인정보처리방침</h3>
        <textarea readonly rows="8">{!! $privacy !!}</textarea>
        <label><input type="checkbox" name="agree2" value="1"> 개인정보처리방침에 동의합니다.</label>
    </section>

    {{-- 전체 동의 — 두 칸을 한 번에 켜고 끈다. 제출되는 값은 지금과 같은 agree·agree2 뿐이라
         순정 register_form.php 가 받는 것은 달라지지 않는다(이 칸에는 name 을 주지 않는다). --}}
    <label class="stip-all">
        <input type="checkbox" id="stip_all"> <strong>전체 동의</strong>
        <span class="muted">회원가입약관 · 개인정보처리방침에 모두 동의합니다.</span>
    </label>

    <button type="submit" class="btn btn-primary btn-block">동의하고 가입하기</button>
    </form>
</div>
<script>
function fregister_check(f) {
    if (!f.agree.checked) { alert("회원가입약관에 동의해 주세요."); return false; }
    if (!f.agree2.checked) { alert("개인정보처리방침에 동의해 주세요."); return false; }
    return true;
}

// 전체 동의 ↔ 개별 동의. 한쪽만 풀면 전체 동의도 함께 풀려야 "전체"라는 말이 거짓이 되지 않는다.
$(function () {
    var $all = $('#stip_all'), $each = $('#fregister input[name="agree"], #fregister input[name="agree2"]');

    $all.on('change', function () {
        $each.prop('checked', this.checked);
    });
    $each.on('change', function () {
        $all.prop('checked', $each.length === $each.filter(':checked').length);
    });
});
</script>
@endsection
