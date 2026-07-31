{{-- 1:1 문의 쓰기 (bbs/qawrite.php) — w 값으로 신규·수정·답변·추가질문이 갈린다.
     순정 qawrite_update.php 계약: token · w · qa_id · 검색 상태(page·sca·stx·sfl)를 그대로 넘긴다. --}}
@extends('layout.default')
@section('content')

@if ($head)<div class="board-extra">{!! $head !!}</div>@endif

<header class="bbs-head">
    <h2><span class="chip">문의</span>{{ $title }}</h2>
</header>

<form name="fwrite" method="post" action="{{ $action }}" enctype="multipart/form-data"
      onsubmit="return qa_write_submit(this);" autocomplete="off">
    <input type="hidden" name="token" value="{{ $token }}">
    <input type="hidden" name="w" value="{{ $w }}">
    <input type="hidden" name="qa_id" value="{{ $qa_id }}">
    @foreach ($params as $k => $v)
    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
    @endforeach

    <div class="write-form">
        @if ($category_option)
        <div class="field">
            <label for="qa_category">분류</label>
            <select name="qa_category" id="qa_category" required>{!! $category_option !!}</select>
        </div>
        @endif

        <div class="field">
            <label for="qa_subject">제목</label>
            <input type="text" name="qa_subject" id="qa_subject" required maxlength="255" value="{{ $write['subject'] }}">
        </div>

        <div class="field">
            <label for="qa_content">내용</label>
            <textarea name="qa_content" id="qa_content" required rows="12">{{ $write['content'] }}</textarea>
        </div>

        <div class="field">
            <label for="qa_email">이메일</label>
            <input type="email" name="qa_email" id="qa_email" value="{{ $write['email'] }}">
            <label class="inline-chk"><input type="checkbox" name="qa_email_recv" value="1" @if ($write['email_recv']) checked @endif> 답변 알림 메일 받기</label>
        </div>

        <div class="field">
            <label for="qa_hp">휴대폰</label>
            <input type="text" name="qa_hp" id="qa_hp" value="{{ $write['hp'] }}">
            <label class="inline-chk"><input type="checkbox" name="qa_sms_recv" value="1" @if ($write['sms_recv']) checked @endif> 답변 알림 문자 받기</label>
        </div>

        @if ($use_file)
        <div class="field">
            <label for="bf_file1">첨부파일</label>
            <input type="file" name="bf_file[]" id="bf_file1">
            <input type="file" name="bf_file[]" id="bf_file2">
        </div>
        @endif
    </div>

    <div class="bbs-toolbar active">
        <a class="btn" href="{{ $list_href }}">취소</a>
        <button type="submit" class="btn btn-primary">등록</button>
    </div>
</form>

<script>
function qa_write_submit(f) {
    if (!f.qa_subject.value.trim()) { alert("제목을 입력하세요."); f.qa_subject.focus(); return false; }
    if (!f.qa_content.value.trim()) { alert("내용을 입력하세요."); f.qa_content.focus(); return false; }
    return true;
}
</script>

@if ($tail)<div class="board-extra">{!! $tail !!}</div>@endif
@endsection
