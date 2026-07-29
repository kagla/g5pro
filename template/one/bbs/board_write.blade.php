@extends('layout.bbs')
@section('bbs_content')
<header class="bbs-head">
    <h2>{{ $board['bo_subject'] }} — {{ $w === 'u' ? '수정' : ($w === 'r' ? '답변' : '글쓰기') }}</h2>
</header>

<form name="fwrite" id="fwrite" class="write-form" method="post" action="{{ $action_url }}"
      enctype="multipart/form-data" onsubmit="return fwrite_check(this);" autocomplete="off">
@foreach ($hidden as $hname => $hval)
<input type="hidden" name="{{ $hname }}" value="{{ $hval }}">
@endforeach
{!! $option_hidden !!}

@if (!$is_member && $is_name)
<div class="field">
    <label for="wr_name">이름</label>
    <input type="text" id="wr_name" name="wr_name" value="{{ $name }}" required>
</div>
@endif
@if ($is_password)
<div class="field">
    <label for="wr_password">비밀번호</label>
    <input type="password" id="wr_password" name="wr_password" {{ $w === 'u' ? '' : 'required' }}>
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
    <input type="text" id="wr_subject" name="wr_subject" value="{!! $subject !!}" required>
</div>

<div class="field">
    <label>내용</label>
    {!! $editor_html !!}
</div>

@if (count($options))
<div class="field-inline">
    @foreach ($options as $o)
    @php $chk = $o['checked'] ? 'checked' : ''; @endphp
    <label><input type="checkbox" name="{{ $o['name'] }}" value="{{ $o['value'] }}" {{ $chk }}> {{ $o['label'] }}</label>
    @endforeach
</div>
@endif

@for ($i = 0; $i < $file_count; $i++)
<div class="field">
    <label for="bf_file_{{ $i }}">파일 #{{ $i + 1 }}
        @if (!empty($files_exist[$i]))<span class="muted">(현재: {{ $files_exist[$i] }})</span>@endif
    </label>
    <input type="file" id="bf_file_{{ $i }}" name="bf_file[]">
</div>
@endfor

@if ($is_use_captcha)
{{-- 순정 captcha_html() 그대로 — id 들이 kcaptcha.js 와의 계약이라 손대지 않고 CSS 로만 다듬는다 --}}
{!! $captcha_html !!}
@endif

<div class="bbs-toolbar">
    <a class="btn" href="{{ $list_href }}">취소</a>
    <button type="submit" class="btn btn-primary">작성완료</button>
</div>
</form>

<script>
// $editor_js·$captcha_js 는 완성된 스크립트가 아니라 submit 검사 안에 들어가야 할 조각이다
// (순정 write.skin.php 의 fwrite_submit 과 같은 자리). 밖에 두면 글자로 찍힌다.
function fwrite_check(f) {
    if (!f.wr_subject.value.trim()) { alert("제목을 입력하세요."); f.wr_subject.focus(); return false; }
    {!! $editor_js !!}
    {!! $captcha_js !!}
    return true;
}
</script>
@endsection
