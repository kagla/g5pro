@extends('layout.default')
@section('content')
<div class="memo-box">
    <header class="bbs-head">
        <h2><span class="chip">스크랩</span>내 스크랩</h2>
        <div class="bbs-meta">전체 {{ number_format($total_count) }}건 · {{ $page }} / {{ max($total_page, 1) }} 페이지</div>
    </header>

    <div class="list-panel">
        <div class="list-table-wrap">
            <table class="list-table">
                <thead>
                    <tr>
                        <th class="col-no">번호</th>
                        <th>게시판</th>
                        <th class="col-subject">제목</th>
                        <th>날짜</th>
                        <th>삭제</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $it)
                    <tr>
                        <td class="col-no">{{ $it['num'] }}</td>
                        <td>{{ $it['bo_subject'] }}</td>
                        <td class="col-subject"><a href="{{ $it['href'] }}">{!! $it['subject'] !!}</a></td>
                        <td>{{ $it['datetime'] }}</td>
                        <td><a class="linklike" href="{!! $it['del_href'] !!}" onclick="return confirm('이 스크랩을 삭제하시겠습니까?');">삭제</a></td>
                    </tr>
                    @empty
                    <tr><td class="bbs-empty" colspan="5">스크랩한 게시물이 없습니다.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <ul class="list-cards">
            @forelse ($items as $it)
            <li>
                <div class="s">
                    <span class="chip c4">{{ $it['bo_subject'] }}</span>
                    <span class="t"><a href="{{ $it['href'] }}">{!! $it['subject'] !!}</a></span>
                </div>
                <div class="m">
                    <span>{{ $it['datetime'] }}</span>
                    <span><a class="linklike" href="{!! $it['del_href'] !!}" onclick="return confirm('이 스크랩을 삭제하시겠습니까?');">삭제</a></span>
                </div>
            </li>
            @empty
            <li class="bbs-empty">스크랩한 게시물이 없습니다.</li>
            @endforelse
        </ul>
    </div>

    <div class="paging-wrap">
        @include('partials.paging', ['page' => $page, 'total_page' => $total_page, 'page_href' => $page_href])
    </div>
</div>
@endsection
