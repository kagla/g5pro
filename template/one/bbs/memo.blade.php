@extends('layout.default')
@section('content')
<div class="memo-box">
    <header class="bbs-head">
        <h2>{{ $kind_title }} 쪽지</h2>
        <div class="bbs-meta">전체 {{ number_format($total_count) }}건</div>
    </header>

    <nav class="bbs-cate">
        @php $rc = $kind === 'recv' ? 'active' : ''; $sc = $kind === 'send' ? 'active' : ''; @endphp
        <a href="{{ $recv_href }}" class="{{ $rc }}">받은 쪽지</a>
        <a href="{{ $send_href }}" class="{{ $sc }}">보낸 쪽지</a>
    </nav>

    <ul class="bbs-list">
        @forelse ($items as $it)
        @php $cls = $it['is_read'] ? 'bbs-row read' : 'bbs-row'; @endphp
        <li class="{{ $cls }}">
            <div class="bbs-row-subject">
                @unless ($it['is_read'])<span class="badge new">N</span>@endunless
                <a href="{!! $it['view_href'] !!}">{!! $it['preview'] !!}</a>
            </div>
            <div class="bbs-row-meta">
                <span class="name">{!! $it['name'] !!}</span>
                <span>{{ $it['datetime'] }}</span>
                <a class="linklike" href="{!! $it['del_href'] !!}" onclick="return confirm('이 쪽지를 삭제하시겠습니까?');">삭제</a>
            </div>
        </li>
        @empty
        <li class="bbs-empty">쪽지가 없습니다.</li>
        @endforelse
    </ul>

    @include('partials.paging', ['page' => $page, 'total_page' => $total_page, 'page_href' => $page_href])

    <div class="bbs-toolbar">
        <span></span>
        <a class="btn btn-primary" href="{{ $form_href }}">쪽지 쓰기</a>
    </div>
</div>
@endsection
