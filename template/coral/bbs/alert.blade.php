{{-- 알림 (bbs/alert.php) — 순정 alert() 의 화면. 스크립트가 곧바로 알림을 띄우고 이동하므로
     이 화면은 그 사이에 잠깐 보이거나, JS 를 끈 브라우저에서만 남는다 --}}
@extends('layout.popup')
@section('popup_class', 'popup--card msg-card')
@section('content')
<script>
{!! $script !!}
</script>

<p class="msg-head">{{ $heading }}</p>
<p class="msg-body">{!! $message !!}</p>

@if (count($post_fields))
{{-- 적어 넣던 값을 들고 돌아간다 (순정과 같은 계약 — 비밀번호·캡차는 싣지 않는다) --}}
<form method="post" action="{{ $url }}" class="msg-btns">
    @foreach ($post_fields as $k => $v)
    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
    @endforeach
    <button type="submit" class="btn btn-primary btn-block">돌아가기</button>
</form>
@else
<div class="msg-btns">
    @if ($url)<a class="btn btn-primary btn-block" href="{{ $url }}">돌아가기</a>@endif
    <a class="btn btn-block" href="{{ G5_URL }}/">처음으로</a>
</div>
@endif
@endsection
