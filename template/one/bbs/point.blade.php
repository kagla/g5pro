@extends('layout.default')
@section('content')
<div class="memo-box">
    <header class="bbs-head">
        <h2>포인트 내역</h2>
        <div class="bbs-meta">보유 포인트 <strong>{{ number_format($sum_point) }}</strong>점 · 전체 {{ number_format($total_count) }}건</div>
    </header>

    <ul class="bbs-list">
        @forelse ($items as $it)
        <li class="bbs-row">
            <div class="bbs-row-subject">{!! $it['content'] !!}</div>
            <div class="bbs-row-meta">
                @php $pcls = $it['point'] >= 0 ? 'point-plus' : 'point-minus'; @endphp
                <span class="{{ $pcls }}">{{ $it['point'] > 0 ? '+' : '' }}{{ number_format($it['point']) }}</span>
                <span>{{ $it['datetime'] }}</span>
            </div>
        </li>
        @empty
        <li class="bbs-empty">포인트 내역이 없습니다.</li>
        @endforelse
    </ul>

    @include('partials.paging', ['page' => $page, 'total_page' => $total_page, 'page_href' => $page_href])
</div>
@endsection
