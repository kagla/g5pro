{{-- 내 스크랩 (bbs/scrap.php) — win_scrap 600px 창. 제목을 누르면 부모 창에서 글이 열린다 --}}
@extends('layout.popup')
@section('content')
<p class="popup-lead">전체 <b>{{ number_format($total_count) }}</b>건 · 제목을 누르면 원래 화면에서 열립니다.</p>

<ul class="scrap-list">
    @forelse ($items as $it)
    <li>
        <div class="s">
            <a class="chip c4" href="{{ $it['board_href'] }}" target="_blank" onclick="return scrapOpen(this);">{{ $it['bo_subject'] }}</a>
            <a class="t" href="{{ $it['href'] }}" target="_blank" onclick="return scrapOpen(this);">{!! $it['subject'] !!}</a>
        </div>
        <div class="m">
            <span>{{ $it['datetime'] }}</span>
            <a class="linklike" href="{!! $it['del_href'] !!}" onclick="return confirm('이 스크랩을 삭제하시겠습니까?');">삭제</a>
        </div>
    </li>
    @empty
    <li class="bbs-empty">스크랩한 게시물이 없습니다.</li>
    @endforelse
</ul>

@include('partials.paging', ['page' => $page, 'total_page' => $total_page, 'page_href' => $page_href])

<div class="popup-btns">
    <button type="button" class="btn" onclick="window.close();">창닫기</button>
</div>

<script>
// 부모 창이 살아 있으면 그쪽에서 열고, 없으면 새 탭으로 (순정 opener 계약)
function scrapOpen(a) {
    if (window.opener && !window.opener.closed) {
        window.opener.document.location.href = a.href;
        return false;
    }
    return true;
}
</script>
@endsection
