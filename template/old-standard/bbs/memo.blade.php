@extends('layout.default')
@section('content')
<div class="memo-box">
    <header class="bbs-head">
        <h2>{{ $kind_title }} 쪽지</h2>
        <div class="bbs-meta">전체 {{ number_format($total_count) }}건 · {{ $page }} / {{ max($total_page, 1) }} 페이지</div>
    </header>

    <nav class="bbs-cate">
        @php $rc = $kind === 'recv' ? 'active' : ''; $sc = $kind === 'send' ? 'active' : ''; @endphp
        <a href="{{ $recv_href }}" class="{{ $rc }}">받은 쪽지</a>
        <a href="{{ $send_href }}" class="{{ $sc }}">보낸 쪽지</a>
    </nav>

    <div class="list-table-wrap">
        <table class="list-table">
            <thead>
                <tr>
                    <th class="col-subject">내용</th>
                    <th>{{ $kind === 'recv' ? '보낸이' : '받는이' }}</th>
                    <th>날짜</th>
                    <th>삭제</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $it)
                @php $cls = $it['is_read'] ? 'read' : ''; @endphp
                <tr class="{{ $cls }}">
                    <td class="col-subject">
                        @unless ($it['is_read'])<span class="badge-new">N</span>@endunless
                        <a href="{!! $it['view_href'] !!}">{!! $it['preview'] !!}</a>
                    </td>
                    <td>{!! $it['name'] !!}</td>
                    <td>{{ $it['datetime'] }}</td>
                    <td><a class="linklike" href="{!! $it['del_href'] !!}" onclick="return confirm('이 쪽지를 삭제하시겠습니까?');">삭제</a></td>
                </tr>
                @empty
                <tr><td class="bbs-empty" colspan="4">쪽지가 없습니다.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <ul class="list-cards">
        @forelse ($items as $it)
        @php $cls = $it['is_read'] ? 'read' : ''; @endphp
        <li class="{{ $cls }}">
            <div class="s">
                @unless ($it['is_read'])<span class="badge-new">N</span>@endunless
                <span class="t"><a href="{!! $it['view_href'] !!}">{!! $it['preview'] !!}</a></span>
            </div>
            <div class="m">
                <span>{!! $it['name'] !!}</span>
                <span>{{ $it['datetime'] }}</span>
                <span><a class="linklike" href="{!! $it['del_href'] !!}" onclick="return confirm('이 쪽지를 삭제하시겠습니까?');">삭제</a></span>
            </div>
        </li>
        @empty
        <li class="bbs-empty">쪽지가 없습니다.</li>
        @endforelse
    </ul>

    @include('partials.paging', ['page' => $page, 'total_page' => $total_page, 'page_href' => $page_href])
    <div class="bbs-toolbar">
        <span></span>
        <div class="bbs-actions">
            <a class="btn btn-primary" href="{{ $form_href }}">쪽지 쓰기</a>
        </div>
    </div>
</div>
@endsection
