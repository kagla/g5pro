<form method="get" action="{{ $self_url }}" class="local_sch01 local_sch">
    <select name="tab" onchange="this.form.submit()">

        @foreach ($tabs as $key => $label)
        <option value="{{ $key }}" {{ $tab === $key ? 'selected' : '' }}>{{ $label }}{{ $key !== '' && isset($tab_counts[$key]) ? ' ('.number_format($tab_counts[$key]).')' : '' }}</option>
        @endforeach

    </select>
    <span class="btn_ov01"><span class="ov_txt">전체 {{ number_format($total) }}건 · {{ $page }}/{{ $total_page }}</span></span>
</form>

<!--
    행 안에 form 을 걸치면 표가 깨질 수 있다(item_list 와 같은 이유) — 표 밖 hidden form 하나에
    행의 택배사·송장 값을 jQuery 로 모아 제출한다. 저장과 단계 전환이 같은 폼을 쓴다.
-->
<form method="post" action="{{ $update_url }}" id="cart_dlv_form" style="display:none">
<input type="hidden" name="token" value="{{ $token }}">
<input type="hidden" name="od_id" id="dlv_od_id" value="">
<input type="hidden" name="mode" id="dlv_mode" value="">
<input type="hidden" name="action" id="dlv_action" value="">
<input type="hidden" name="od_delivery_company" id="dlv_company" value="">
<input type="hidden" name="od_invoice" id="dlv_invoice" value="">
<input type="hidden" name="ret" value="delivery">
</form>

<div class="tbl_head01 tbl_wrap">
    <table>
    <thead>
    <tr>
        <th scope="col">주문번호</th><th scope="col">받는분</th><th scope="col">상품</th>
        <th scope="col">상태</th><th scope="col">택배사</th><th scope="col">송장번호</th>
        <th scope="col">저장</th><th scope="col">단계</th>
    </tr>
    </thead>
    <tbody>

    @foreach ($orders as $o)
    <tr class="bg{{ $loop->index % 2 }}">
        <td><a href="{{ $o['view_url'] }}">{{ $o['od_no'] }}</a></td>
        <td>{{ $o['od_recv_name'] !== '' ? $o['od_recv_name'] : $o['od_name'] }}</td>
        <td class="td_left">{{ $o['summary'] }}</td>
        <td>{{ $o['status_label'] }}</td>
        <td><input type="text" data-role="company" value="{{ $o['od_delivery_company'] }}" size="10"></td>
        <td><input type="text" data-role="invoice" value="{{ $o['od_invoice'] }}" size="16"></td>
        <td><button type="button" class="btn btn_02" data-save="{{ $o['od_id'] }}">송장 저장</button></td>
        <td><button type="button" class="btn_submit btn" data-next="{{ $o['od_id'] }}" data-action="{{ $o['next_action'] }}">{{ $o['next_label'] }}</button></td>
    </tr>
    @endforeach

    @if (!count($orders))
    <tr><td colspan="8" class="empty_table">배송할 주문이 없습니다.</td></tr>
    @endif

    </tbody>
    </table>
</div>

@if ($total_page > 1)
{{-- 처음·이전·다음·맨끝은 순정 pg_* 클래스(아이콘) 그대로 — 첫/끝 페이지에서는 감춘다 --}}
@php $qs = array('tab' => $tab); @endphp

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

<script>
// 행의 택배사·송장 값을 hidden form 에 모아 제출한다. 단계 전환도 같은 폼으로,
// 그때도 행의 송장 값을 함께 저장한다(발송 버튼 하나로 송장+상태가 같이 처리되게).
// 버튼이 type=button 이라 admin.js 의 토큰 자동 주입이 안 걸린다 — item_list 인라인 저장과
// 같은 방식으로 get_ajax_token() 을 직접 불러 채운다(서버는 check_admin_token).
$(function () {
    function fill(odId, $tr) {
        var token = get_ajax_token();
        if (!token) { alert('토큰 정보가 올바르지 않습니다.'); return false; }
        $('#cart_dlv_form input[name="token"]').val(token);
        $('#dlv_od_id').val(odId);
        $('#dlv_company').val($tr.find('[data-role="company"]').val());
        $('#dlv_invoice').val($tr.find('[data-role="invoice"]').val());
        return true;
    }
    $('button[data-save]').on('click', function () {
        if (!fill($(this).data('save'), $(this).closest('tr'))) return;
        $('#dlv_mode').val('invoice');
        $('#cart_dlv_form').trigger('submit');
    });
    $('button[data-next]').on('click', function () {
        var $tr = $(this).closest('tr');
        if (!fill($(this).data('next'), $tr)) return;
        // 발송 처리 전에 송장부터 저장되도록 mode=ship 묶음 처리로 보낸다
        $('#dlv_mode').val('ship');
        $('#dlv_action').val($(this).data('action'));
        $('#cart_dlv_form').trigger('submit');
    });
});
</script>
