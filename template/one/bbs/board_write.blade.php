@extends('layout.bbs')
@section('bbs_content')
<header class="bbs-head">
    <h2>{{ $board['bo_subject'] }} — {{ $w === 'u' ? '수정' : '글쓰기' }}</h2>
</header>

<form name="fwrite" id="fwrite" class="write-form" method="post" action="{{ $action_url }}"
      enctype="multipart/form-data" onsubmit="return fwrite_check(this);" autocomplete="off">
{!! $option_hidden !!}
<input type="hidden" name="token" value="{{ $token }}">

@if (!$is_member && $is_name)
<div class="field">
    <label for="wr_name">이름</label>
    <input type="text" id="wr_name" name="wr_name" value="{{ $name }}" required>
</div>
<div class="field">
    <label for="wr_password">비밀번호</label>
    <input type="password" id="wr_password" name="wr_password" required>
</div>
@endif

@if (count($categories))
<div class="field">
    <label for="ca_name">분류</label>
    <select id="ca_name" name="ca_name" required>
        <option value="">분류 선택</option>
        @foreach ($categories as $c)
        @php $sel = $c['selected'] ? 'selected' : ''; @endphp
        <option value="{{ $c['name'] }}" {{ $sel }}>{{ $c['name'] }}</option>
        @endforeach
    </select>
</div>
@endif

<div class="field">
    <label for="wr_subject">제목</label>
    <input type="text" id="wr_subject" name="wr_subject" value="{{ $subject }}" required>
</div>

<div class="field">
    <label>내용</label>
    {!! $editor_html !!}
</div>

@if ($is_secret)
<div class="field-inline">
    @php $chk = ($secret_checked || $is_secret == 2) ? 'checked' : ''; @endphp
    <label><input type="checkbox" name="secret" value="secret" {{ $chk }}> 비밀글</label>
</div>
@endif

@for ($i = 0; $i < $file_count; $i++)
<div class="field">
    <label for="bf_file_{{ $i }}">파일 #{{ $i + 1 }}</label>
    <input type="file" id="bf_file_{{ $i }}" name="bf_file[]">
</div>
@endfor

@if ($is_use_captcha)
<div class="field">{!! $captcha_html !!}</div>
@endif

<div class="bbs-toolbar">
    <a class="btn" href="{{ $list_href }}">취소</a>
    <button type="submit" class="btn btn-primary">작성완료</button>
</div>
</form>

{!! $editor_js !!}
{!! $captcha_js !!}
<script>
function fwrite_check(f) {
    if (typeof g5_editor_check === "function" && !g5_editor_check()) return false;
    if (!f.wr_subject.value.trim()) { alert("제목을 입력하세요."); f.wr_subject.focus(); return false; }
    return true;
}
</script>
@endsection
