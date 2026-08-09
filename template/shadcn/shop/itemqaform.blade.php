{{-- 상품문의 쓰기 (shop/itemqaform.php) — 상품 상세에서 새 창으로 연다 --}}
@extends('layout.popup')
@section('popup_class', 'popup--card')
@section('content')
<p class="popup-subject">{{ $it_name }}</p>

<form name="fitemqa" method="post" action="{{ $action }}" autocomplete="off"
      onsubmit="return fitemqa_submit(this);">
    <input type="hidden" name="w" value="{{ $w }}">
    <input type="hidden" name="it_id" value="{{ $it_id }}">
    <input type="hidden" name="iq_id" value="{{ $iq_id }}">

    <label class="auto-login">
        <input type="checkbox" name="iq_secret" id="iq_secret" value="1" @if ($is_secret) checked @endif>
        비밀글로 문의 <span class="muted">(작성자와 관리자만 봅니다)</span>
    </label>

    <div class="field">
        <label for="iq_email">답변받을 이메일 <span class="muted">(선택)</span></label>
        <input type="email" id="iq_email" name="iq_email" value="{{ $email }}">
    </div>
    <div class="field">
        <label for="iq_hp">답변받을 휴대폰 <span class="muted">(선택)</span></label>
        <input type="text" id="iq_hp" name="iq_hp" value="{{ $hp }}" inputmode="tel">
    </div>

    <div class="field">
        <label for="iq_subject">제목</label>
        <input type="text" id="iq_subject" name="iq_subject" value="{{ $subject }}" required maxlength="250">
    </div>

    <div class="field">
        <label for="iq_question">문의 내용</label>
        {!! $editor_html !!}
    </div>

    <div class="popup-btns">
        <button type="button" class="btn" onclick="window.close();">닫기</button>
        <button type="submit" class="btn btn-primary">등록</button>
    </div>
</form>

<script>
function fitemqa_submit(f) {
    {!! $editor_js !!}
    if (!f.iq_subject.value.trim()) { alert("제목을 입력하세요."); f.iq_subject.focus(); return false; }
    return true;
}
</script>
@endsection
