{{-- 목록 변형 1 · 표 (기본, bo_skin='blade') --}}
@extends('layout.bbs')
@section('bbs_content')
@include('partials.bbs_head')

@if ($is_checkbox)
<form name="fboardlist" id="fboardlist" method="post" action="{{ $list_update_action }}"
      onsubmit="return fboardlist_check(this);">
<input type="hidden" name="bo_table" value="{{ $board['bo_table'] }}">
@endif

<div class="list-panel">
<div class="list-table-wrap">
    <table class="list-table">
        <thead>
            <tr>
                @if ($is_checkbox)<th class="col-chk"><span class="sound_only">선택</span></th>@endif
                <th class="col-no">번호</th>
                <th class="col-subject">제목</th>
                <th>글쓴이</th>
                <th>날짜</th>
                <th>조회</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $it)
            @php $cls = $it['is_notice'] ? 'notice' : ''; @endphp
            <tr class="{{ $cls }}">
                @if ($is_checkbox)<td class="col-chk"><input type="checkbox" name="chk_wr_id[]" value="{{ $it['wr_id'] }}" aria-label="선택"></td>@endif
                <td class="col-no">{{ $it['is_notice'] ? '공지' : $it['num'] }}</td>
                <td class="col-subject">
                    <a href="{{ $it['href'] }}">{!! $it['subject'] !!}</a>
                    @if ($it['comment_cnt'])<span class="n">[{{ $it['comment_cnt'] }}]</span>@endif
                    @if ($it['icon_new'])<span class="badge-new">N</span>@endif
                    @if ($it['icon_file'])<span class="chip c4">파일</span>@endif
                </td>
                <td>{!! $it['name'] !!}</td>
                <td>{{ $it['datetime'] }}</td>
                <td>{{ $it['hit'] }}</td>
            </tr>
            @empty
            <tr><td class="bbs-empty" colspan="{{ $is_checkbox ? 6 : 5 }}">게시물이 없습니다.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- 620px 이하에서 표 대신 표시 --}}
<ul class="list-cards">
    @forelse ($items as $it)
    @php $cls = $it['is_notice'] ? 'notice' : ''; @endphp
    <li class="{{ $cls }}">
        <div class="s">
            @if ($is_checkbox)<input type="checkbox" name="chk_wr_id[]" value="{{ $it['wr_id'] }}" aria-label="선택">@endif
            @if ($it['is_notice'])<span class="chip c3">공지</span>@endif
            <span class="t"><a href="{{ $it['href'] }}">{!! $it['subject'] !!}</a></span>
            @if ($it['comment_cnt'])<span class="n">[{{ $it['comment_cnt'] }}]</span>@endif
            @if ($it['icon_new'])<span class="badge-new">N</span>@endif
        </div>
        <div class="m">
            <span>{!! $it['name'] !!}</span>
            <span>{{ $it['datetime'] }}</span>
            <span>조회 {{ $it['hit'] }}</span>
        </div>
    </li>
    @empty
    <li class="bbs-empty">게시물이 없습니다.</li>
    @endforelse
</ul>
</div>

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

@include('partials.bbs_toolbar')
@endsection
