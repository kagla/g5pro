{{-- 1:1 문의 목록 (bbs/qalist.php) — 답변 여부가 핵심 상태라 앞에 표시로 세운다.
     좁은 화면은 CSS 가 표를 숨기고 카드를 보인다 (게시판 목록과 같은 규약). --}}
@extends('layout.default')
@section('content')

@if ($head)<div class="board-extra">{!! $head !!}</div>@endif

<header class="bbs-head">
    <h2><span class="chip">문의</span>1:1 문의</h2>
    <span class="muted">전체 {{ number_format($total) }}건 · {{ $page }} / {{ max(1, $total_page) }} 페이지</span>
</header>

@if (count($categories))
<nav class="bbs-cate">
    <a href="{{ $list_href }}" class="{{ $search['sca'] ? '' : 'active' }}">전체</a>
    @foreach ($categories as $c)
    <a href="{{ $c['href'] }}" class="{{ $c['active'] ? 'active' : '' }}">{{ $c['name'] }}</a>
    @endforeach
</nav>
@endif

@if (!$items)
    <div class="bbs-empty">등록된 문의가 없습니다.</div>
@else
<div class="list-panel">
    <div class="list-table-wrap">
        <table class="list-table qa-table">
            <thead>
            <tr>
                <th scope="col" class="col-no">번호</th>
                <th scope="col" class="col-subject">제목</th>
                <th scope="col">작성자</th>
                <th scope="col">날짜</th>
                <th scope="col">상태</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($items as $it)
            <tr>
                <td class="col-no">{{ $it['num'] }}</td>
                <td class="col-subject">
                    @if ($it['category'])<span class="chip c2">{{ $it['category'] }}</span>@endif
                    <a href="{{ $it['href'] }}">{!! $it['subject'] !!}</a>
                    @if ($it['has_file'])<span class="flag" title="첨부파일">📎</span>@endif
                </td>
                <td>{{ $it['name'] }}</td>
                <td class="col-date">{{ $it['date'] }}</td>
                <td><span class="chip {{ $it['answered'] ? 'c3' : 'c4' }}">{{ $it['answered'] ? '답변완료' : '접수' }}</span></td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <ul class="list-cards">
        @foreach ($items as $it)
        <li>
            <div class="s">
                <span class="chip {{ $it['answered'] ? 'c3' : 'c4' }}">{{ $it['answered'] ? '답변완료' : '접수' }}</span>
                <span class="t"><a href="{{ $it['href'] }}">{!! $it['subject'] !!}</a></span>
            </div>
            <div class="m">
                @if ($it['category'])<span>{{ $it['category'] }}</span>@endif
                <span>{{ $it['name'] }}</span>
                <span>{{ $it['date'] }}</span>
            </div>
        </li>
        @endforeach
    </ul>
</div>
@endif

<div class="bbs-toolbar active">
    <form class="bbs-search" method="get" action="{{ $list_href }}">
        <label for="stx" class="sound_only">검색어</label>
        <input type="text" name="stx" id="stx" value="{{ $search['stx'] }}" placeholder="제목·내용 검색">
        <button type="submit" class="btn">검색</button>
    </form>
    <a class="btn btn-primary" href="{{ $write_href }}">문의하기</a>
</div>

@include('partials.paging', ['page' => $page, 'total_page' => $total_page, 'page_href' => $page_href])

@if ($tail)<div class="board-extra">{!! $tail !!}</div>@endif
@endsection
