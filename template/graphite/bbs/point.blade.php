@extends('layout.default')
@section('content')
<div class="memo-box">
    <header class="bbs-head">
        <h2>포인트 내역</h2>
        <div class="bbs-meta">보유 <strong>{{ number_format($sum_point) }}</strong>점 · 전체 {{ number_format($total_count) }}건</div>
    </header>

    <div class="list-table-wrap">
        <table class="list-table">
            <thead>
                <tr>
                    <th class="col-subject">내용</th>
                    <th>증감</th>
                    <th>날짜</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $it)
                <tr>
                    <td class="col-subject">{!! $it['content'] !!}</td>
                    @php $pcls = $it['point'] >= 0 ? 'point-plus' : 'point-minus'; @endphp
                    <td class="{{ $pcls }}">{{ $it['point'] > 0 ? '+' : '' }}{{ number_format($it['point']) }}</td>
                    <td>{{ $it['datetime'] }}</td>
                </tr>
                @empty
                <tr><td class="bbs-empty" colspan="3">포인트 내역이 없습니다.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <ul class="list-cards">
        @forelse ($items as $it)
        <li>
            <div class="s"><span class="t">{!! $it['content'] !!}</span></div>
            <div class="m">
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
