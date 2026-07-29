@extends('layout.bbs')
@section('bbs_content')
<header class="bbs-head">
    <h2>{{ $board['bo_subject'] }}</h2>
    <div class="bbs-meta">전체 {{ number_format($total_count) }}건 · {{ $page }} 페이지</div>
</header>

@if (count($categories))
<nav class="bbs-cate">
    <a href="{{ $board_url }}">전체</a>
    @foreach ($categories as $c)
    @php $cls = $c['active'] ? 'active' : ''; @endphp
    <a href="{{ $c['href'] }}" class="{{ $cls }}">{{ $c['name'] }}</a>
    @endforeach
</nav>
@endif

<ul class="gallery-grid">
    @forelse ($items as $it)
    <li class="gallery-card">
        <a href="{{ $it['href'] }}" class="gallery-thumb">
            @if ($it['thumb'] && $it['thumb']['src'])
            <img src="{{ $it['thumb']['src'] }}" alt="{{ $it['thumb']['alt'] }}" loading="lazy">
            @else
            <span class="gallery-noimg">이미지 없음</span>
            @endif
        </a>
        <div class="gallery-info">
            <div class="gallery-subject">
                @if ($it['is_notice'])<span class="badge">공지</span>@endif
                <a href="{{ $it['href'] }}">{!! $it['subject'] !!}</a>
                @if ($it['comment_cnt'])<span class="cmt-cnt">{{ $it['comment_cnt'] }}</span>@endif
                @if ($it['icon_new'])<span class="badge new">N</span>@endif
            </div>
            <div class="bbs-row-meta">
                <span class="name">{!! $it['name'] !!}</span>
                <span>{{ $it['datetime'] }}</span>
            </div>
        </div>
    </li>
    @empty
    <li class="bbs-empty">게시물이 없습니다.</li>
    @endforelse
</ul>

@include('partials.paging', ['page' => $page, 'total_page' => $total_page, 'page_href' => $page_href])

<div class="bbs-toolbar">
    <form class="bbs-search" method="get" action="{{ G5_BBS_URL }}/board.php">
        <input type="hidden" name="bo_table" value="{{ $board['bo_table'] }}">
        <select name="sfl">
            @php $flds = ['wr_subject' => '제목', 'wr_content' => '내용', 'wr_name,1' => '글쓴이']; @endphp
            @foreach ($flds as $v => $label)
            @php $sel = ($search['sfl'] === $v) ? 'selected' : ''; @endphp
            <option value="{{ $v }}" {{ $sel }}>{{ $label }}</option>
            @endforeach
        </select>
        <input type="text" name="stx" value="{{ $search['stx'] }}" required>
        <button type="submit" class="btn">검색</button>
    </form>
    <div class="bbs-actions">
        @if ($rss_href)<a class="btn" href="{{ $rss_href }}">RSS</a>@endif
        @if ($write_href)<a class="btn btn-primary" href="{{ $write_href }}">글쓰기</a>@endif
    </div>
</div>
@endsection
