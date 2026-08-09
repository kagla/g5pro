@extends('layout.default')
@section('content')
<header class="bbs-head">
    <h2>주문 내역</h2>
    <div class="bbs-meta">{{ $searched ? '조건에 맞는 주문' : '전체' }} {{ number_format($total) }}건</div>
</header>

{{-- 검색 — 손님이 기억하는 것은 주문번호와 상품명뿐이라 한 칸으로 둘 다 찾는다.
     "어디서 찾을지" 를 먼저 고르게 하면 고르는 일부터 실수한다.
     기간·상태는 기본이 '전체' 다 — 기본값으로 기간을 걸면 오래전 주문 하나뿐인 사람에게
     "주문이 없습니다" 가 뜬다. --}}
<form method="get" action="{{ $search_url }}" class="bbs-search ord-search">
    <select name="period" aria-label="기간">
        <option value="">전체 기간</option>

        @foreach ($periods as $key => $label)
        <option value="{{ $key }}" {{ $period === $key ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach

    </select>
    <select name="status" aria-label="주문 상태">
        <option value="">전체 상태</option>

        @foreach ($statuses as $key => $label)
        <option value="{{ $key }}" {{ $status === $key ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach

    </select>
    <input type="text" name="q" value="{{ $q }}" placeholder="주문번호 · 상품명"
           aria-label="주문번호 또는 상품명 검색">
    <button type="submit">검색</button>
</form>

{{-- 0건일 때는 이 줄을 감춘다 — 아래 빈 화면 안내가 같은 말을 하고 같은 곳으로 보내서,
     한 화면에 '전체 보기' 링크가 둘이 됐다 --}}
@if ($searched && count($orders))
<p class="ord-search-on">
    <span class="chip here">검색 중</span>
    <a href="{{ $search_url }}">조건 지우고 전체 보기</a>
</p>
@endif

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
                <td class="col-state"><span class="cart-status is-{{ cart_order_status_tone($od['od_status']) }}">{{ $od['status_label'] }}</span></td>
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
            <span class="cart-status is-{{ cart_order_status_tone($od['od_status']) }}">{{ $od['status_label'] }}</span>
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

@elseif ($searched)
{{-- 0건의 두 가지 뜻을 갈라 말한다 — 조건 때문에 안 보이는 것과 주문이 없는 것은 할 일이 다르다 --}}
<p class="empty">조건에 맞는 주문이 없습니다. 기간·상태를 넓히거나 검색어를 지워 보세요.</p>
<p style="text-align:center"><a href="{{ $search_url }}" class="cart-cta">전체 주문 보기</a></p>

@else
<p class="empty">아직 주문이 없습니다.</p>
<p style="text-align:center"><a href="{{ $home_href }}" class="cart-cta">상품 보러 가기</a></p>
@endif
@endsection
