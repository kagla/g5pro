@extends('layout.default')
@section('content')
<div class="member-box">
    <h2>회원가입 약관</h2>
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

    <button type="submit" class="btn btn-primary btn-block">동의하고 가입하기</button>
    </form>
</div>
<script>
function fregister_check(f) {
    if (!f.agree.checked) { alert("회원가입약관에 동의해 주세요."); return false; }
    if (!f.agree2.checked) { alert("개인정보처리방침에 동의해 주세요."); return false; }
    return true;
}
</script>
@endsection
