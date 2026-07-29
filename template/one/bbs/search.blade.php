@extends('layout.default')
@section('content')
<div class="search-summary">
    <h2><span class="q">{{ $stx }}</span> 검색 결과</h2>
    <p class="muted">게시판 {{ number_format($board_count) }}곳에서 {{ number_format($total_count) }}건을 찾았습니다.</p>
</div>

@forelse ($groups as $g)
<section class="search-group">
    <div class="search-group-head">
        <h3><a href="{{ $g['href'] }}">{{ $g['bo_subject'] }}</a></h3>
        <span class="bbs-meta">{{ count($g['items']) }}건 표시</span>
    </div>
    <ul class="list-simple">
        @foreach ($g['items'] as $it)
        <li>
            <div class="s">
                <span class="t"><a href="{{ $it['href'] }}">{!! $it['subject'] !!}</a></span>
                @if ($it['comment_cnt'])<span class="n">{{ $it['comment_cnt'] }}</span>@endif
            </div>
            @if ($it['content'])<div class="muted">{!! $it['content'] !!}</div>@endif
            <div class="m">
                <span>{!! $it['name'] !!}</span>
                <span>{{ $it['datetime'] }}</span>
                <span>조회 {{ $it['hit'] }}</span>
            </div>
        </li>
        @endforeach
    </ul>
</section>
@empty
<p class="bbs-empty">검색 결과가 없습니다. 다른 검색어로 찾아보세요.</p>
@endforelse

<div class="paging-wrap">
    @include('partials.paging', ['page' => $page, 'total_page' => $total_page, 'page_href' => $page_href])
</div>

<div class="bbs-toolbar">
    <form class="bbs-search" method="get" action="{{ $action_url }}">
        <input type="hidden" name="sop" value="{{ $sop }}">
        <label for="sch_sfl" class="sound_only">검색 대상</label>
        <select id="sch_sfl" name="sfl">
            @php $flds = ['wr_subject||wr_content' => '제목+내용', 'wr_subject' => '제목', 'wr_content' => '내용', 'mb_id,1' => '회원아이디']; @endphp
            @foreach ($flds as $v => $label)
            @php $sel = ($sfl === $v) ? 'selected' : ''; @endphp
            <option value="{{ $v }}" {{ $sel }}>{{ $label }}</option>
            @endforeach
        </select>
        <label for="sch_stx" class="sound_only">검색어</label>
        <input type="text" id="sch_stx" name="stx" value="{{ $stx }}" placeholder="검색어" required>
        <button type="submit" class="btn">검색</button>
    </form>
</div>
@endsection
