{{-- 사용후기 쓰기 (shop/itemuseform.php) — 상품 상세에서 새 창으로 연다 --}}
@extends('layout.popup')
@section('popup_class', 'popup--card')
@section('content')
<p class="popup-subject">{{ $it_name }}</p>

<form name="fitemuse" method="post" action="{{ $action }}" autocomplete="off"
      onsubmit="return fitemuse_submit(this);">
    <input type="hidden" name="w" value="{{ $w }}">
    <input type="hidden" name="it_id" value="{{ $it_id }}">
    <input type="hidden" name="is_id" value="{{ $is_id }}">

    <div class="field">
        <span class="field-label">별점</span>
        <div class="field-inline star-pick">
            @for ($s = 5; $s >= 1; $s--)
            <label for="is_score{{ $s }}">
                <input type="radio" id="is_score{{ $s }}" name="is_score" value="{{ $s }}" @if ($score === $s) checked @endif>
                {{ str_repeat('★', $s) }}
            </label>
            @endfor
        </div>
    </div>

    <div class="field">
        <label for="is_subject">제목</label>
        <input type="text" id="is_subject" name="is_subject" value="{{ $subject }}" required maxlength="250">
    </div>

    <div class="field">
        <label for="is_content">내용</label>
        {{-- 순정 editor_html() 결과 — 에디터를 쓰면 textarea 대신 에디터가 들어온다 --}}
        {!! $editor_html !!}
    </div>

    <div class="popup-btns">
        <button type="button" class="btn" onclick="window.close();">닫기</button>
        <button type="submit" class="btn btn-primary">등록</button>
    </div>
</form>

<script>
function fitemuse_submit(f) {
    // $editor_js 는 완성된 스크립트가 아니라 submit 검사 안에 들어가야 할 조각이다
    {!! $editor_js !!}
    if (!f.is_subject.value.trim()) { alert("제목을 입력하세요."); f.is_subject.focus(); return false; }
    return true;
}
</script>
@endsection
