{{-- 새글 모아보기 (bbs/new.php) — 여러 게시판의 최근 글·댓글을 한 줄로 모은다.
     관리자는 선택 삭제를 할 수 있다 (순정 new_delete.php 계약: chk_bn_id[] · pressed) --}}
@extends('layout.default')
@section('content')

<header class="bbs-head">
    <h2><span class="chip">모아보기</span>새글
        @if ($mb_id)<span class="muted">· {{ $mb_id }}</span>@endif
    </h2>
    <span class="muted">전체 {{ number_format($total) }}건 · {{ $page }} / {{ max(1, $total_page) }} 페이지</span>
</header>

{{-- 필터 — 게시판 분류 줄(.bbs-cate)과 같은 모양으로 맞춘다. 새 부품을 만들지 않는다 --}}
<nav class="bbs-cate new-filters">
    @foreach ($views as $v)
    <a href="{{ $v['href'] }}" class="{{ $view === $v['key'] ? 'active' : '' }}">{{ $v['label'] }}</a>
    @endforeach

    <label for="gr_id" class="sound_only">그룹</label>
    <select id="gr_id" onchange="location.href = this.value;">
        <option value="{{ str_replace('__GR__', '', $group_href) }}"@if (!$gr_id) selected @endif>전체그룹</option>
        @foreach ($groups as $g)
        <option value="{{ str_replace('__GR__', $g['id'], $group_href) }}"@if ($gr_id === $g['id']) selected @endif>{{ $g['subject'] }}</option>
    @endforeach
    </select>
</nav>

@if (!$items)
    <div class="bbs-empty">새 글이 없습니다.</div>
@else
{{-- 선택삭제는 관리자만 쓴다. 비회원에게는 폼도 스크립트도 내보내지 않는다 --}}
@if ($is_admin)
<form name="fnewlist" id="fnewlist" method="post" action="{{ G5_BBS_URL }}/new_delete.php" onsubmit="return new_submit(this);">
<input type="hidden" name="pressed" value="선택삭제">
@endif

<div class="list-panel">
    <div class="list-table-wrap">
        <table class="list-table new-table">
            <thead>
            <tr>
                @if ($is_admin)<th scope="col" class="col-chk"><label class="chk-cell"><input type="checkbox" class="chk-all" aria-label="전체 선택"></label></th>@endif
                <th scope="col">게시판</th>
                <th scope="col" class="col-subject">제목</th>
                <th scope="col">글쓴이</th>
                <th scope="col">날짜</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($items as $i => $it)
            <tr>
                @if ($is_admin)
                {{-- 순정 new_delete.php 계약: 체크박스 값은 행 번호이고, 그 번호로
                     bo_table[n]·wr_id[n] 을 찾아 지운다. 세 값이 한 벌이어야 한다 --}}
                <td class="col-chk">
                    <label class="chk-cell"><input type="checkbox" name="chk_bn_id[]" value="{{ $i }}" aria-label="{{ $it['subject'] }} 선택"></label>
                    <input type="hidden" name="bo_table[{{ $i }}]" value="{{ $it['bo_table'] }}">
                    <input type="hidden" name="wr_id[{{ $i }}]" value="{{ $it['wr_id'] }}">
                </td>
                @endif
                <td class="col-board">
                    <a class="chip c2" href="{{ str_replace('__GR__', $it['gr_id'], $group_href) }}">{{ $it['gr_subject'] }}</a>
                    <a class="board-name" href="{{ G5_BBS_URL }}/board.php?bo_table={{ $it['bo_table'] }}">{{ $it['bo_subject'] }}</a>
                </td>
                <td class="col-subject">
                    <a href="{{ $it['href'] }}">
                        @if ($it['is_comment'])<span class="chip c3">댓글</span>@endif
                        {{ $it['subject'] }}
                    </a>
                </td>
                <td class="col-name">{!! $it['name'] !!}</td>
                <td class="col-date">{{ $it['datetime'] }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    {{-- 좁은 화면 — CSS 가 위 표를 숨기고 이 목록을 보인다 (게시판 목록과 같은 구조) --}}
    <ul class="list-cards">
        @foreach ($items as $i => $it)
        <li>
            <div class="s">
                @if ($is_admin)<input type="checkbox" name="chk_bn_id[]" value="{{ $i }}" aria-label="선택">@endif
                @if ($it['is_comment'])<span class="chip c3">댓글</span>@endif
                <span class="t"><a href="{{ $it['href'] }}">{{ $it['subject'] }}</a></span>
            </div>
            <div class="m">
                <span>{{ $it['bo_subject'] }}</span>
                <span>{!! $it['name'] !!}</span>
                <span>{{ $it['datetime'] }}</span>
            </div>
        </li>
        @endforeach
    </ul>
</div>

@if ($is_admin)
<div class="new-tools">
    <button type="submit" class="btn">선택삭제</button>
</div>
</form>
@endif
@endif

@include('partials.paging', ['page' => $page, 'total_page' => $total_page, 'page_href' => $page_href])

@if ($is_admin)
<script>
// 순정 new_delete.php 계약 — 하나 이상 골라야 하고, 지운 글은 되돌릴 수 없다
function boxes() {
    return [].slice.call(document.querySelectorAll('#fnewlist input[name="chk_bn_id[]"]'));
}
// 넓은 화면은 표, 좁은 화면은 카드가 같은 글을 각각 그린다. 지금 보이는 쪽만 다뤄야
// 같은 행 번호가 두 번 전송되지 않는다 (게시판 목록과 같은 방식).
function visible() {
    return boxes().filter(function (c) { return c.offsetParent !== null; });
}

function new_submit(f) {
    var n = visible().filter(function (c) { return c.checked; }).length;
    if (!n) { alert("삭제할 게시물을 하나 이상 고르세요."); return false; }
    if (!confirm("선택한 게시물을 정말 삭제하시겠습니까?\n\n한번 삭제한 자료는 되돌릴 수 없습니다.")) return false;
    boxes().forEach(function (c) { c.disabled = (c.offsetParent === null); });   // 안 보이는 쪽은 전송에서 뺀다
    return true;
}
(function () {
    var all = document.querySelector('#fnewlist .chk-all');
    if (!all) return;
    all.addEventListener('change', function () {
        visible().forEach(function (c) { c.checked = all.checked; });
    });
})();
</script>
@endif
@endsection
