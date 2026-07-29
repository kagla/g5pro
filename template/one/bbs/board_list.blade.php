{{-- 목록 변형 1 · 표 (기본, bo_skin='blade') --}}
@extends('layout.bbs')
@section('bbs_content')
@include('partials.bbs_head')

@if ($is_checkbox)
{{-- 관리자 전용 선택 도구. 순정 board_list_update.php 가 btn_submit 값(선택삭제/복사/이동)으로 갈라진다 --}}
<form name="fboardlist" id="fboardlist" method="post" action="{{ $list_update_action }}"
      data-delete-action="{{ $list_update_action }}" data-move-action="{{ $move_action }}">
<input type="hidden" name="bo_table" value="{{ $board['bo_table'] }}">
<input type="hidden" name="sw" value="">{{-- move.php 가 copy/move 를 여기서 읽는다 --}}

<div class="list-tools">
    <label class="chk-all-label"><input type="checkbox" class="chk-all"> 전체 선택</label>
    <span class="chk-count" aria-live="polite"></span>
    <div class="kebab">
        <button type="button" class="icon-btn kebab-btn" aria-haspopup="true" aria-expanded="false"
                aria-label="선택한 게시물 관리" title="선택한 게시물 관리">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
        </button>
        <div class="kebab-menu" role="menu">
            <button type="submit" name="btn_submit" value="선택이동" role="menuitem">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h14"/><path d="m13 7 5 5-5 5"/></svg>
                선택이동
            </button>
            <button type="submit" name="btn_submit" value="선택복사" role="menuitem">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M15 5.5A1.5 1.5 0 0 0 13.5 4h-8A1.5 1.5 0 0 0 4 5.5v8A1.5 1.5 0 0 0 5.5 15"/></svg>
                선택복사
            </button>
            <button type="submit" name="btn_submit" value="선택삭제" role="menuitem" class="danger">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4.5 7h15"/><path d="M9.5 7V5h5v2"/><path d="M6.5 7 7.6 20h8.8L17.5 7"/></svg>
                선택삭제
            </button>
        </div>
    </div>
</div>
@endif

<div class="list-panel">
<div class="list-table-wrap">
    <table class="list-table">
        <thead>
            <tr>
                @if ($is_checkbox)<th class="col-chk"><input type="checkbox" class="chk-all" aria-label="전체 선택"></th>@endif
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
</form>
@endif

@include('partials.bbs_toolbar')
@endsection
