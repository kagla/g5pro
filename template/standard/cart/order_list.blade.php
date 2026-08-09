@extends('layout.default')
@section('content')
<header class="bbs-head">
    <h2>주문 내역</h2>
    <div class="bbs-meta">전체 {{ number_format($total) }}건</div>
</header>

@if (count($orders))
{{-- 게시판 목록과 같은 표(.list-table)를 쓴다 — 머리글이 있어야 어느 숫자가 무엇인지 읽히고,
     주문번호처럼 고객센터에 불러 줄 값을 놓을 자리가 생긴다.
     결제금액은 맨 오른쪽 — 여러 줄을 위아래로 훑을 때 자릿수가 한 줄로 맞아야 비교가 된다.
     좁은 화면에서는 표를 접고 아래 카드로 바꾼다(.list-cards, 620px 아래). --}}
<div class="list-panel">
<div class="list-table-wrap">
    <table class="list-table">
        <caption class="sound_only">주문 내역</caption>
        <thead>
            <tr>
                <th class="col-date">주문일</th>
                <th class="col-ordno">주문번호</th>
                <th class="col-subject">주문 상품</th>
                <th class="col-state">상태</th>
                <th class="col-amt">결제금액</th>
            </tr>
        </thead>
        <tbody>

            @foreach ($orders as $od)
            <tr>
                <td class="col-date">{{ substr($od['od_datetime'], 2, 8) }}</td>
                <td class="col-ordno"><a href="{{ $od['href'] }}">{{ $od['od_no'] }}</a></td>
                <td class="col-subject"><a href="{{ $od['href'] }}">{{ $od['summary'] }}</a></td>
                <td class="col-state"><span class="cart-status">{{ $od['status_label'] }}</span></td>
                <td class="col-amt td-num">{{ number_format($od['od_total']) }}원</td>
            </tr>
            @endforeach

        </tbody>
    </table>
</div>

<ul class="list-cards">

    @foreach ($orders as $od)
    <li>
        <div class="s">
            <a class="t" href="{{ $od['href'] }}">{{ $od['summary'] }}</a>
            <strong class="cart-order-total">{{ number_format($od['od_total']) }}원</strong>
        </div>
        <div class="m">
            <span>{{ substr($od['od_datetime'], 2, 8) }}</span>
            <span>{{ $od['od_no'] }}</span>
            <span class="cart-status">{{ $od['status_label'] }}</span>
        </div>
    </li>
    @endforeach

</ul>
</div>

@if ($total_page > 1)
<nav class="paging">

    @foreach ($pages as $p)
    <a href="{{ $p['href'] }}" class="{{ $p['current'] ? 'current' : '' }}">{{ $p['num'] }}</a>
    @endforeach

</nav>
@endif

@else
<p class="empty">아직 주문이 없습니다.</p>
<p style="text-align:center"><a href="{{ $home_href }}" class="cart-cta">상품 보러 가기</a></p>
@endif
@endsection
