{{-- 메일 쓰기 (bbs/formmail.php) — 사이드뷰의 "메일보내기". 새 창으로 열린다 --}}
@extends('layout.popup')
@section('popup_class', 'popup--card')
@section('content')
<p class="form-lead"><b>{!! $name !!}</b> 님께 메일을 보냅니다.</p>

<form name="fformmail" method="post" action="{{ $action }}" enctype="multipart/form-data"
      onsubmit="return fformmail_submit(this);">
    <input type="hidden" name="to" value="{{ $email }}">
    <input type="hidden" name="attach" value="2">
    @if ($is_member)
    <input type="hidden" name="fnick" value="{!! $mb_nick !!}">
    <input type="hidden" name="fmail" value="{{ $mb_email }}">
    @else
    <div class="field">
        <label for="fnick">보내는 사람</label>
        <input type="text" id="fnick" name="fnick" required>
    </div>
    <div class="field">
        <label for="fmail">회신받을 이메일</label>
        <input type="email" id="fmail" name="fmail" required>
    </div>
    @endif

    <div class="field">
        <label for="subject">제목</label>
        <input type="text" id="subject" name="subject" required autofocus>
    </div>

    <div class="field">
        <span class="field-label">형식</span>
        <div class="field-inline">
            @foreach (['0' => 'TEXT', '1' => 'HTML', '2' => 'TEXT+HTML'] as $v => $label)
            <label for="type_{{ $v }}">
                <input type="radio" id="type_{{ $v }}" name="type" value="{{ $v }}" @if ((int)$v === $type) checked @endif>
                {{ $label }}
            </label>
            @endforeach
        </div>
    </div>

    <div class="field">
        <label for="content">내용</label>
        <textarea id="content" name="content" rows="8" required></textarea>
    </div>

    <div class="field">
        <label for="file1">첨부 파일 <span class="muted">(선택 · 2개까지)</span></label>
        <input type="file" id="file1" name="file1">
        <input type="file" id="file2" name="file2">
        <p class="form-lead">첨부가 누락될 수 있으니 보낸 뒤 확인해 주세요.</p>
    </div>

    {!! $captcha_html !!}

    <div class="popup-btns">
        <button type="button" class="btn" onclick="window.close();">창닫기</button>
        <button type="submit" id="btn_submit" class="btn btn-primary">메일발송</button>
    </div>
</form>

<script>
function fformmail_submit(f) {
    {!! $captcha_js !!}
    if (f.file1.value || f.file2.value) {
        if (!confirm("첨부파일이 크면 전송에 시간이 걸립니다.\n\n보내기가 끝나기 전에 창을 닫거나 새로고침 하지 마세요.")) return false;
    }
    document.getElementById('btn_submit').disabled = true;
    return true;
}
</script>
@endsection
