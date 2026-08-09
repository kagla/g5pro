{{-- 게시판 하단: 페이징·검색·글쓰기 — 목록 변형 4종이 공용으로 쓴다 --}}
@include('partials.paging', ['page' => $page, 'total_page' => $total_page, 'page_href' => $page_href])

<div class="bbs-toolbar">
    {{-- bo_use_search 를 끈 게시판은 검색을 내보내지 않는다 --}}
    @if ($use_search)
    <form class="bbs-search" method="get" action="{{ G5_BBS_URL }}/board.php">
        <input type="hidden" name="bo_table" value="{{ $board['bo_table'] }}">
        <input type="hidden" name="sca" value="{{ $search['sca'] }}">
        <input type="hidden" name="sop" value="and">
        <label for="bbs_sfl" class="sound_only">검색 대상</label>
        {{-- 순정 get_board_sfl_select_options() — 관리자에게는 회원아이디 검색도 들어 있다 --}}
        <select id="bbs_sfl" name="sfl">{!! $sfl_options !!}</select>
        <label for="bbs_stx" class="sound_only">검색어</label>
        <input type="text" id="bbs_stx" name="stx" value="{{ $search['stx'] }}" placeholder="이 게시판에서 검색" required>
        <button type="submit" class="btn">검색</button>
    </form>
    @else
    <div></div>
    @endif
    <div class="bbs-actions">
        @if ($rss_href)<a class="btn" href="{{ $rss_href }}">RSS</a>@endif
        @if ($write_href)<a class="btn btn-primary" href="{{ $write_href }}">글쓰기</a>@endif
    </div>
</div>
