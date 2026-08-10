{{-- 쪽지 쓰기 (bbs/memo_form.php) — 사이드뷰의 "쪽지보내기". 새 창으로 열린다.
     그래서 layout.default(헤더·메뉴·푸터) 가 아니라 layout.popup 이다. 목록으로 나가는 길도
     두지 않는다 — 새 창에는 돌아갈 자리가 없다. 보내고 나면 memo_form_update.php 가 창을 닫는다 --}}
@extends('layout.popup')
@section('popup_class', 'popup--card popup--form')
@section('content')
<form name="fmemoform" method="post" action="{{ $action_url }}" autocomplete="off"
      onsubmit="return fmemoform_check(this);">
    <div class="field">
        <label for="me_recv_mb_id">받는 회원 아이디 <span class="muted">(여러 명은 콤마로 구분)</span></label>
        <input type="text" id="me_recv_mb_id" name="me_recv_mb_id" value="{!! $recv_mb_id !!}" required
               @if (!$recv_mb_id) autofocus @endif>
    </div>
    <div class="field">
        <label for="me_memo">내용</label>
        <textarea id="me_memo" name="me_memo" rows="8" required @if ($recv_mb_id) autofocus @endif>{!! $content !!}</textarea>
    </div>
    {!! $captcha_html !!}
    <div class="popup-btns">
        <button type="button" class="btn" onclick="window.close();">창닫기</button>
        <button type="submit" class="btn btn-primary">보내기</button>
    </div>
</form>
<script>
function fmemoform_check(f) {
    if (!f.me_recv_mb_id.value.trim()) { alert("받는 회원 아이디를 입력하세요."); return false; }
    if (!f.me_memo.value.trim()) { alert("내용을 입력하세요."); return false; }
    {!! $captcha_js !!}
    return true;
}
</script>
@endsection
