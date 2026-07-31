{{-- 재입고 알림 (shop/itemstocksms.php) — 품절 상품에서 연다 --}}
@extends('layout.popup')
@section('popup_class', 'popup--card')
@section('content')
<p class="popup-subject">{{ $it_name }}</p>
<p class="form-lead">다시 들어오면 문자로 알려드립니다.</p>

<form name="fstocksms" method="post" action="{{ $action }}" autocomplete="off"
      onsubmit="return fstocksms_submit(this);">
    <input type="hidden" name="it_id" value="{{ $it_id }}">

    <div class="field">
        <label for="ss_hp">휴대폰번호</label>
        <input type="text" id="ss_hp" name="ss_hp" value="{{ $hp }}" required inputmode="tel" placeholder="숫자만">
    </div>

    <div class="field">
        <label for="sms_privacy">개인정보처리방침 안내</label>
        <textarea id="sms_privacy" rows="6" readonly>{{ $privacy }}</textarea>
    </div>

    <label class="auto-login">
        <input type="checkbox" name="agree" id="agree" value="1"> 개인정보처리방침 안내에 동의합니다.
    </label>

    <div class="popup-btns">
        <button type="button" class="btn" onclick="window.close();">닫기</button>
        <button type="submit" class="btn btn-primary">확인</button>
    </div>
</form>

<script>
function fstocksms_submit(f) {
    if (!f.agree.checked) { alert("개인정보처리방침 안내에 동의해 주십시오."); return false; }
    if (confirm("재입고 알림을 신청하시겠습니까?")) return true;
    window.close();
    return false;
}
</script>
@endsection
