@extends('layout.default')
@section('content')
<div class="memo-box">
    <header class="bbs-head">
        <h2>쪽지 쓰기</h2>
    </header>

    <form name="fmemoform" class="write-form" method="post" action="{{ $action_url }}" autocomplete="off"
          onsubmit="return fmemoform_check(this);">
        <div class="field">
            <label for="me_recv_mb_id">받는 회원 아이디 <span class="muted">(여러 명은 콤마로 구분)</span></label>
            <input type="text" id="me_recv_mb_id" name="me_recv_mb_id" value="{!! $recv_mb_id !!}" required>
        </div>
        <div class="field">
            <label for="me_memo">내용</label>
            <textarea id="me_memo" name="me_memo" rows="8" required>{!! $content !!}</textarea>
        </div>
        {!! $captcha_html !!}
        <div class="bbs-toolbar">
            <a class="btn" href="{{ $list_href }}">취소</a>
            <button type="submit" class="btn btn-primary">보내기</button>
        </div>
    </form>
</div>
<script>
function fmemoform_check(f) {
    if (!f.me_recv_mb_id.value.trim()) { alert("받는 회원 아이디를 입력하세요."); return false; }
    if (!f.me_memo.value.trim()) { alert("내용을 입력하세요."); return false; }
    {!! $captcha_js !!}
    return true;
}
</script>
@endsection
