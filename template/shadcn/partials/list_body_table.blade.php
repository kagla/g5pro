{{-- list_body_table — 목록 본문. 게시판 목록 화면과, 전체목록보이기를 켠 읽기 화면이 함께 쓴다 --}}
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

@php
    // 정렬 표시 — 지금 이 열로 정렬 중이면 방향 화살표를 붙인다
    $sort_mark = function ($col) use ($sort_now) {
        if ($sort_now['sst'] !== $col) return '';
        return $sort_now['sod'] === 'asc' ? ' ▲' : ' ▼';
    };
    $cols = 5 + ($is_checkbox ? 1 : 0) + ($is_good ? 1 : 0) + ($is_nogood ? 1 : 0);
@endphp

<div class="list-panel">
<div class="list-table-wrap">
    <table class="list-table">
        <caption class="sound_only">{{ $board['bo_subject'] }} 목록</caption>
        <thead>
            <tr>
                @if ($is_checkbox)<th class="col-chk"><label class="chk-cell"><input type="checkbox" class="chk-all" aria-label="전체 선택"></label></th>@endif
                <th class="col-no">번호</th>
                <th class="col-subject">제목</th>
                <th>글쓴이</th>
                <th class="col-sort"><a href="{!! $sort['wr_datetime']['href'] !!}">날짜{{ $sort_mark('wr_datetime') }}</a></th>
                <th class="col-sort"><a href="{!! $sort['wr_hit']['href'] !!}">조회{{ $sort_mark('wr_hit') }}</a></th>
                @if ($is_good)<th class="col-sort"><a href="{!! $sort['wr_good']['href'] !!}">추천{{ $sort_mark('wr_good') }}</a></th>@endif
                @if ($is_nogood)<th class="col-sort"><a href="{!! $sort['wr_nogood']['href'] !!}">비추천{{ $sort_mark('wr_nogood') }}</a></th>@endif
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $it)
            @php $cls = trim(($it['is_notice'] ? 'notice ' : '').($it['is_current'] ? 'current' : '')); @endphp
            <tr class="{{ $cls }}">
                @if ($is_checkbox)<td class="col-chk"><label class="chk-cell"><input type="checkbox" name="chk_wr_id[]" value="{{ $it['wr_id'] }}" aria-label="선택"></label></td>@endif
                <td class="col-no">
                    @if ($it['is_notice'])공지
                    @elseif ($it['is_current'])<span class="chip here">열람중</span>
                    @else {{ $it['num'] }}
                    @endif
                </td>
                <td class="col-subject" style="padding-left: {{ 16 + min($it['depth'], 8) * 14 }}px">
                    @if ($it['depth'])<span class="reply-arrow" aria-hidden="true">↳</span>@endif
                    @if ($it['ca_name'])<a class="chip cate" href="{!! $it['ca_href'] !!}">{{ $it['ca_name'] }}</a>@endif
                    <a href="{{ $it['href'] }}">{!! $it['subject'] !!}</a>
                    @include('partials.list_flags', ['it' => $it])
                    @if ($use_content && $it['excerpt'])<p class="row-excerpt">{{ $it['excerpt'] }}</p>@endif
                    @if ($use_file && count($it['files']))
                    <p class="row-files">
                        @foreach ($it['files'] as $f)
                        <a href="{{ $f['href'] }}">{{ $f['source'] }} <span class="muted">{{ $f['size'] }}</span></a>
                        @endforeach
                    </p>
                    @endif
                </td>
                <td>{!! $it['name'] !!}</td>
                <td>{{ $it['datetime'] }}</td>
                <td>{{ $it['hit'] }}</td>
                @if ($is_good)<td>{{ number_format($it['good']) }}</td>@endif
                @if ($is_nogood)<td>{{ number_format($it['nogood']) }}</td>@endif
            </tr>
            @empty
            <tr><td class="bbs-empty" colspan="{{ $cols }}">게시물이 없습니다.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- 620px 이하에서 표 대신 표시 --}}
<ul class="list-cards">
    @forelse ($items as $it)
    @php $cls = trim(($it['is_notice'] ? 'notice ' : '').($it['is_current'] ? 'current' : '')); @endphp
    <li class="{{ $cls }}" style="padding-left: {{ 16 + min($it['depth'], 5) * 12 }}px">
        <div class="s">
            @if ($is_checkbox)<input type="checkbox" name="chk_wr_id[]" value="{{ $it['wr_id'] }}" aria-label="선택">@endif
            @if ($it['is_notice'])<span class="chip notice">공지</span>@endif
            @if ($it['ca_name'])<a class="chip cate" href="{!! $it['ca_href'] !!}">{{ $it['ca_name'] }}</a>@endif
            <span class="t"><a href="{{ $it['href'] }}">{!! $it['subject'] !!}</a></span>
            @include('partials.list_flags', ['it' => $it])
        </div>
        @if ($use_content && $it['excerpt'])<p class="row-excerpt">{{ $it['excerpt'] }}</p>@endif
        <div class="m">
            <span>{!! $it['name'] !!}</span>
            <span>{{ $it['datetime'] }}</span>
            <span>조회 {{ $it['hit'] }}</span>
            @if ($is_good)<span>추천 {{ number_format($it['good']) }}</span>@endif
            @if ($is_nogood)<span>비추천 {{ number_format($it['nogood']) }}</span>@endif
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
