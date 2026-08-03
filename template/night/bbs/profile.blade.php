{{-- 회원 프로필 (bbs/profile.php) — 글쓴이 이름에서 새 창으로 열린다 --}}
@extends('layout.popup')
@section('popup_class', 'popup--card')
@section('content')
<div class="pf-head">
    <img class="pf-photo" src="{{ $photo }}" alt="" aria-hidden="true">
    <div>
        <div class="pf-nick">{{ $nick }}</div>
        <div class="pf-sub">회원권한 {{ $level }} · 포인트 {{ number_format($point) }}</div>
    </div>
</div>

<dl class="pf-list">
    <dt>회원가입일</dt>
    <dd>@if ($join_date){{ $join_date }} <span class="muted">({{ number_format($join_days) }}일째)</span>@else <span class="muted">알 수 없음</span>@endif</dd>
    <dt>최종접속일</dt>
    <dd>@if ($last_login){{ $last_login }}@else <span class="muted">알 수 없음</span>@endif</dd>
    @if ($homepage)
    <dt>홈페이지</dt>
    <dd><a href="{{ $homepage }}" target="_blank" rel="noopener">{{ $homepage }}</a></dd>
    @endif
</dl>

<div class="pf-intro">
    <h2>인사말</h2>
    <div class="pf-intro-body">{!! $profile !!}</div>
</div>

<div class="popup-btns">
    <button type="button" class="btn" onclick="window.close();">창닫기</button>
</div>
@endsection
