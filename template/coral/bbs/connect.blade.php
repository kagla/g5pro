{{-- 현재접속자 (bbs/current_connect.php) — 지금 사이트를 보고 있는 사람 목록.
     비회원은 순정이 IP 를 가려서 넘긴다 (관리자에게만 온전히 보인다). --}}
@extends('layout.default')
@section('content')

<header class="bbs-head">
    <h2><span class="chip">접속</span>현재접속자</h2>
    <span class="muted">{{ number_format($total) }}명</span>
</header>

@if (!$items)
    <div class="bbs-empty">접속자가 없습니다.</div>
@else
<ul class="list-simple connect-list">
    @foreach ($items as $it)
    <li>
        <div class="s">
            <span class="chip {{ $it['is_member'] ? '' : 'c4' }}">{{ $it['is_member'] ? '회원' : '손님' }}</span>
            <span class="t">{!! $it['name'] !!}</span>
        </div>
        @if ($it['location'])
        <div class="m"><span>{!! $it['location'] !!}</span></div>
        @endif
    </li>
    @endforeach
</ul>
@endif
@endsection
