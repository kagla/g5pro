{{-- 알림 후 창닫기 (bbs/alert_close.php) — 팝업 안에서 뜬 알림. 확인을 누르면 창이 닫힌다 --}}
@extends('layout.popup')
@section('popup_class', 'popup--card msg-card')
@section('content')
<script>
{!! $script !!}
</script>

<p class="msg-head">{{ $heading }}</p>
<p class="msg-body">{!! $message !!}</p>
<p class="msg-note">{{ $note }}</p>

<div class="msg-btns">
    <button type="button" class="btn btn-primary btn-block" onclick="window.close();">창닫기</button>
</div>
@endsection
