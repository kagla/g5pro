@extends('layout.default')
@section('content')
<header class="bbs-head">
    <h2><span class="chip">주문</span>주문내역</h2>
    <div class="bbs-head-right">
        <div class="bbs-meta">전체 {{ number_format($total_count) }}건 · {{ $page }} / {{ max($total_page, 1) }} 페이지</div>
        <a class="btn" href="{{ $shop_href }}">쇼핑 계속하기</a>
    </div>
</header>

<div class="list-panel">
<div class="list-table-wrap">
    <table class="list-table">
        <thead>
            <tr>
                <th class="col-subject">주문번호</th>
                <th>주문일시</th>
                <th>상품수</th>
                <th>주문금액</th>
                <th>입금액</th>
                <th>미입금액</th>
                <th>상태</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $it)
            <tr>
                <td class="col-subject"><a href="{{ $it['href'] }}">{{ $it['od_id'] }}</a></td>
                <td>{{ $it['datetime'] }} ({{ $it['yoil'] }})</td>
                <td>{{ number_format($it['count']) }}</td>
                <td class="td-num">{{ number_format($it['price']) }}원</td>
                <td class="td-num">{{ number_format($it['receipt']) }}원</td>
                <td class="td-num">{{ number_format($it['misu']) }}원</td>
                <td>@php $scls = 'od-status '.$it['status_cls']; @endphp<span class="{{ $scls }}">{{ $it['status'] }}</span></td>
            </tr>
            @empty
            <tr><td class="bbs-empty" colspan="7">주문 내역이 없습니다.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- 620px 이하에서 표 대신 표시 --}}
<ul class="list-cards">
    @forelse ($items as $it)
    <li>
        <div class="s">
            <span class="t"><a href="{{ $it['href'] }}">{{ $it['od_id'] }}</a></span>
            @php $scls = 'od-status '.$it['status_cls']; @endphp
            <span class="{{ $scls }}">{{ $it['status'] }}</span>
        </div>
        <div class="m">
            <span>{{ $it['datetime'] }}</span>
            <span>{{ number_format($it['count']) }}개</span>
            <span><strong>{{ number_format($it['price']) }}원</strong></span>
            @if ($it['misu'] > 0)<span class="od-misu">미입금 {{ number_format($it['misu']) }}원</span>@endif
        </div>
    </li>
    @empty
    <li class="bbs-empty">주문 내역이 없습니다.</li>
    @endforelse
</ul>
</div>

@include('partials.paging', ['page' => $page, 'total_page' => $total_page, 'page_href' => $page_href])
@endsection
