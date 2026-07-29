{{-- 게시판 하단: 페이징·검색·글쓰기 — 목록 변형 4종이 공용으로 쓴다 --}}
<div class="paging-wrap">
    @include('partials.paging', ['page' => $page, 'total_page' => $total_page, 'page_href' => $page_href])
</div>

<div class="bbs-toolbar">
    <form class="bbs-search" method="get" action="{{ G5_BBS_URL }}/board.php">
        <input type="hidden" name="bo_table" value="{{ $board['bo_table'] }}">
        <label for="bbs_sfl" class="sound_only">검색 대상</label>
        <select id="bbs_sfl" name="sfl">
            @php $flds = ['wr_subject' => '제목', 'wr_content' => '내용', 'wr_name,1' => '글쓴이']; @endphp
            @foreach ($flds as $v => $label)
            @php $sel = ($search['sfl'] === $v) ? 'selected' : ''; @endphp
            <option value="{{ $v }}" {{ $sel }}>{{ $label }}</option>
            @endforeach
        </select>
        <label for="bbs_stx" class="sound_only">검색어</label>
        <input type="text" id="bbs_stx" name="stx" value="{{ $search['stx'] }}" required>
        <button type="submit" class="btn">검색</button>
    </form>
    <div class="bbs-actions">
        @if ($rss_href)<a class="btn" href="{{ $rss_href }}">RSS</a>@endif
        @if ($write_href)<a class="btn btn-primary" href="{{ $write_href }}">글쓰기</a>@endif
    </div>
</div>
