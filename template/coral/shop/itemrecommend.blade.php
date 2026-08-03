{{-- 상품 추천 메일 (shop/itemrecommend.php) — 상품 상세의 공유 패널 ✉ 버튼으로 열린다 --}}
@extends('layout.popup')
@section('popup_class', 'popup--card')
@section('content')
<p class="popup-subject">{{ $it_name }}</p>

<form name="fitemrecommend" method="post" action="{{ $action }}" autocomplete="off"
      onsubmit="return fitemrecommend_check(this);">
    <input type="hidden" name="token" value="{{ $token }}">
    <input type="hidden" name="it_id" value="{{ $it_id }}">

    <div class="field">
        <label for="to_email">추천받는 분 이메일</label>
        <input type="email" id="to_email" name="to_email" required autofocus>
    </div>
    <div class="field">
        <label for="subject">제목</label>
        <input type="text" id="subject" name="subject" required value="{{ $it_name }} 상품을 추천합니다">
    </div>
    <div class="field">
        <label for="content">내용</label>
        <textarea id="content" name="content" rows="6" required></textarea>
    </div>

    <div class="popup-btns">
        <button type="button" class="btn" onclick="window.close();">닫기</button>
        <button type="submit" id="btn_submit" class="btn btn-primary">보내기</button>
    </div>
</form>

<script>
function fitemrecommend_check(f) {
    if (!f.to_email.value.trim()) { alert("추천받는 분의 이메일을 입력하세요."); f.to_email.focus(); return false; }
    document.getElementById('btn_submit').disabled = true;
    return true;
}
</script>
@endsection
