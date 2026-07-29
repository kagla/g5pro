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

@if ($is_checkbox)
<form name="fboardlist" id="fboardlist" method="post" action="{{ $list_update_action }}"
      onsubmit="return fboardlist_check(this);">
<input type="hidden" name="bo_table" value="{{ $board['bo_table'] }}">
@endif
<ul class="bbs-list">
    @forelse ($items as $it)
    @php $cls = $it['is_notice'] ? 'bbs-row notice' : 'bbs-row'; @endphp
    <li class="{{ $cls }}">
        <div class="bbs-row-subject">
            @if ($is_checkbox)<input type="checkbox" name="chk_wr_id[]" value="{{ $it['wr_id'] }}" aria-label="선택">@endif
            @if ($it['is_notice'])<span class="badge">공지</span>@endif
            <a href="{{ $it['href'] }}">{!! $it['subject'] !!}</a>
            @if ($it['comment_cnt'])<span class="cmt-cnt">{{ $it['comment_cnt'] }}</span>@endif
            @if ($it['icon_new'])<span class="badge new">N</span>@endif
            @if ($it['icon_file'])<span class="badge file">파일</span>@endif
        </div>
        <div class="bbs-row-meta">
            <span class="name">{!! $it['name'] !!}</span>
            <span>{{ $it['datetime'] }}</span>
            <span>조회 {{ $it['hit'] }}</span>
        </div>
    </li>
    @empty
    <li class="bbs-empty">게시물이 없습니다.</li>
    @endforelse
</ul>
@if ($is_checkbox)
<div class="bbs-admin-acts">
    <button type="submit" name="btn_submit" value="선택삭제" class="btn"
            onclick="return confirm('선택한 게시물을 정말 삭제하시겠습니까?\n\n한번 삭제한 자료는 복구할 수 없습니다.');">선택삭제</button>
</div>
</form>
<script>
function fboardlist_check(f) {
    var n = f.querySelectorAll('input[name="chk_wr_id[]"]:checked').length;
    if (!n) { alert("삭제할 게시물을 하나 이상 선택하세요."); return false; }
    return true;
}
</script>
@endif

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
