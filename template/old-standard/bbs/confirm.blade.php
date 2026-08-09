{{-- 확인 (bbs/confirm.php) — 순정 confirm() 의 화면. 예/아니오에 따라 url1/url2 로 갈린다 --}}
@extends('layout.popup')
@section('popup_class', 'popup--card msg-card')
@section('content')
<script>
{!! $script !!}
</script>

<p class="msg-head">{{ $heading }}</p>
<p class="msg-body">{!! $message !!}</p>

<div class="msg-btns row">
    <a class="btn btn-primary" href="{{ $url1 }}">확인</a>
    <a class="btn" href="{{ $url2 }}">취소</a>
</div>
@if ($url3)
<div class="msg-btns">
    <a class="btn btn-block" href="{{ $url3 }}">돌아가기</a>
</div>
@endif
@endsection
