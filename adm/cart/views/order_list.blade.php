<form method="get" action="{{ $self_url }}" class="local_sch01 local_sch">
    <select name="status">
        <option value="">전체 상태</option>

        @foreach ($statuses as $key => $label)
        <option value="{{ $key }}" {{ $status === $key ? 'selected' : '' }}>{{ $label }}{{ isset($status_counts[$key]) ? ' ('.number_format($status_counts[$key]).')' : '' }}</option>
        @endforeach

    </select>
    <input type="text" name="from" value="{{ $from }}" placeholder="시작일 2026-01-01" class="frm_input" size="12">
    <input type="text" name="to" value="{{ $to }}" placeholder="종료일" class="frm_input" size="12">
    <input type="text" name="q" value="{{ $q }}" placeholder="주문번호·주문자·연락처·회원ID" class="frm_input" size="24">
    <button type="submit" class="btn_submit btn">검색</button>
    <span class="btn_ov01"><span class="ov_txt">전체 {{ number_format($total) }}건 · {{ $page }}/{{ $total_page }}</span></span>
</form>

<div class="tbl_head01 tbl_wrap">
    <table>
    <thead>
    <tr>
        <th scope="col">주문번호</th><th scope="col">주문자</th><th scope="col">상품</th>
        <th scope="col">결제수단</th><th scope="col">금액</th><th scope="col">상태</th>
        <th scope="col">주문일시</th><th scope="col">관리</th>
    </tr>
    </thead>
    <tbody>

    @foreach ($orders as $o)
    <tr class="bg{{ $loop->index % 2 }}{{ $o['od_status'] === 'canceled' ? 'cancel' : '' }}">
        <td><a href="{{ $o['view_url'] }}">{{ $o['od_no'] }}</a></td>
        <td>{{ $o['od_name'] }}{{ $o['mb_id'] !== '' ? ' ('.$o['mb_id'].')' : '' }}</td>
        <td class="td_left">{{ $o['summary'] }}</td>
        <td>{{ $o['od_pay_method'] === 'bank' ? '무통장' : ($o['od_pay_method'] === 'inicis' ? '이니시스' : '토스') }}</td>
        <td class="td_num">{{ number_format($o['od_total']) }}</td>
        <td>{{ $o['status_label'] }}</td>
        <td class="td_datetime">{{ substr($o['od_datetime'], 0, 16) }}</td>
        <td><a href="{{ $o['view_url'] }}" class="btn btn_02">상세</a></td>
    </tr>
    @endforeach

    @if (!count($orders))
    <tr><td colspan="8" class="empty_table">조건에 맞는 주문이 없습니다.</td></tr>
    @endif

    </tbody>
    </table>
</div>

@if ($total_page > 1)
{{-- 처음·이전·다음·맨끝은 순정 pg_* 클래스(아이콘) 그대로 — 첫/끝 페이지에서는 감춘다 --}}
@php $qs = array('status' => $status, 'q' => $q, 'from' => $from, 'to' => $to); @endphp

<nav class="pg_wrap">
    <span class="pg">

    @if ($page > 1)
    <a href="{{ $self_url.'?'.http_build_query($qs + array('page' => 1)) }}" class="pg_page pg_start">처음</a>
    <a href="{{ $self_url.'?'.http_build_query($qs + array('page' => $page - 1)) }}" class="pg_page pg_prev">이전</a>
    @endif

    @for ($p = max(1, $page - 4); $p <= min($total_page, $page + 4); $p++)
    <a href="{{ $self_url.'?'.http_build_query($qs + array('page' => $p)) }}" class="pg_page {{ $p === $page ? 'pg_current' : '' }}">{{ $p }}</a>
    @endfor

    @if ($page < $total_page)
    <a href="{{ $self_url.'?'.http_build_query($qs + array('page' => $page + 1)) }}" class="pg_page pg_next">다음</a>
    <a href="{{ $self_url.'?'.http_build_query($qs + array('page' => $total_page)) }}" class="pg_page pg_end">맨끝</a>
    @endif

    </span>
</nav>
@endif
