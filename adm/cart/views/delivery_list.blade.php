<style>
#cart_dlv_tbl td { text-align: center; }
#cart_dlv_tbl td.td_left { text-align: left; }
#cart_dlv_tbl .dlv_old { display: block; margin-top: 3px; font-size: 0.92em; color: #888; }
</style>

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
<input type="hidden" name="od_dc_id" id="dlv_dc_id" value="">
<input type="hidden" name="od_invoice" id="dlv_invoice" value="">
<input type="hidden" name="od_delivery_note" id="dlv_note" value="">
<input type="hidden" name="od_delivery_admin_memo" id="dlv_memo" value="">
<input type="hidden" name="ret" value="delivery">
</form>

<div class="tbl_head01 tbl_wrap" id="cart_dlv_tbl">
    <table>
    <thead>
    <tr>
        <th scope="col">주문번호</th><th scope="col">받는분</th><th scope="col">상품</th>
        <th scope="col">상태</th><th scope="col">택배사</th><th scope="col">송장번호 / 배송안내</th>
        <th scope="col">내부메모</th><th scope="col">저장</th><th scope="col">단계</th>
    </tr>
    </thead>
    <tbody>

    @foreach ($orders as $o)
    <tr class="bg{{ $loop->index % 2 }}">
        <td><a href="{{ $o['view_url'] }}">{{ $o['od_no'] }}</a></td>
        <td>{{ $o['od_recv_name'] !== '' ? $o['od_recv_name'] : $o['od_name'] }}</td>
        <td class="td_left">{{ $o['summary'] }}</td>
        <td>{{ $o['status_label'] }}</td>
        <td>
            <select data-role="dc" class="frm_input">
                <option value="">택배사 선택</option>

                {{-- 기본 택배사를 미리 골라 두는 것은 "아직 아무것도 안 적힌" 행에만 한다.
                     옛 자유입력 주문(od_dc_id 는 0 인데 이름은 있다)에 기본을 걸면, 관리자가
                     못 보고 저장했을 때 그때 찍힌 이름이 엉뚱한 택배사로 덮인다. --}}
                @php $preselect = ((int)$o['od_dc_id'] === 0 && $o['od_dc_name'] === '' && $default_dc) ? (int)$default_dc['dc_id'] : (int)$o['od_dc_id']; @endphp

                @foreach ($companies as $c)
                <option value="{{ $c['dc_id'] }}" data-invoice="{{ (int)$c['dc_invoice'] }}" {{ $preselect === (int)$c['dc_id'] ? 'selected' : '' }}>{{ $c['dc_name'] }}</option>
                @endforeach

                {{-- 사용을 끈 택배사로 이미 잡아 둔 주문 — 그 하나만 목록에 끼워 선택해 둔다 --}}
                @if ($o['extra_dc'] && (int)$o['extra_dc']['dc_use'] === 0)
                <option value="{{ $o['extra_dc']['dc_id'] }}" data-invoice="{{ (int)$o['extra_dc']['dc_invoice'] }}" selected>{{ $o['extra_dc']['dc_name'] }} (사용 안 함)</option>
                @endif

            </select>

            @if ((int)$o['od_dc_id'] === 0 && $o['od_dc_name'] !== '')
            <span class="dlv_old">이전 기록: {{ $o['od_dc_name'] }}</span>
            @endif

        </td>
        <td>
            <input type="text" data-role="invoice" value="{{ $o['od_invoice'] }}" size="16" placeholder="송장번호">
            <input type="text" data-role="note" value="{{ $o['od_delivery_note'] }}" size="16" placeholder="예: 8/12 오후 도착 예정">
        </td>
        <td><input type="text" data-role="memo" value="{{ $o['od_delivery_admin_memo'] }}" size="14" placeholder="관리자만 봅니다"></td>
        <td><button type="button" class="btn btn_02" data-save="{{ $o['od_id'] }}">송장 저장</button></td>
        <td><button type="button" class="btn_submit btn" data-next="{{ $o['od_id'] }}" data-action="{{ $o['next_action'] }}" @if ($o['next_confirm'] !== '') data-confirm="{{ $o['next_confirm'] }}" @endif>{{ $o['next_label'] }}</button></td>
    </tr>
    @endforeach

    @if (!count($orders))
    <tr><td colspan="9" class="empty_table">배송할 주문이 없습니다.</td></tr>
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
    // 택배사에 따라 송장번호 칸과 배송안내 칸 중 하나만 보여 준다. 나란히 두면 표가 넓어진다.
    // 안 보이는 쪽 값도 DOM 에 남긴다 — 잘못 골랐다 되돌렸을 때 적던 것이 날아가지 않게.
    // 택배사를 안 고른 행은 둘 다 잠근다: 서버가 그 값을 안 받으므로, 적을 수 있게 두면
    // 적고 저장했는데 사라지는 꼴이 된다.
    function syncRow($tr) {
        var $sel = $tr.find('[data-role="dc"]');
        if (!$sel.length) return;
        var picked = $sel.val() !== '';
        var takes = $sel.find('option:selected').data('invoice') === 1;
        $tr.find('[data-role="invoice"]').toggle(!picked || takes).prop('disabled', !picked);
        $tr.find('[data-role="note"]').toggle(picked && !takes).prop('disabled', !picked);
    }
    $('#cart_dlv_tbl tbody tr').each(function () { syncRow($(this)); });
    $('#cart_dlv_tbl').on('change', '[data-role="dc"]', function () {
        syncRow($(this).closest('tr'));
    });

    function fill(odId, $tr) {
        var token = get_ajax_token();
        if (!token) { alert('토큰 정보가 올바르지 않습니다.'); return false; }
        $('#cart_dlv_form input[name="token"]').val(token);
        $('#dlv_od_id').val(odId);
        $('#dlv_dc_id').val($tr.find('[data-role="dc"]').val());
        $('#dlv_invoice').val($tr.find('[data-role="invoice"]').val());
        $('#dlv_note').val($tr.find('[data-role="note"]').val());
        $('#dlv_memo').val($tr.find('[data-role="memo"]').val());
        return true;
    }
    $('button[data-save]').on('click', function () {
        if (!fill($(this).data('save'), $(this).closest('tr'))) return;
        $('#dlv_mode').val('invoice');
        $('#cart_dlv_form').trigger('submit');
    });
    $('button[data-next]').on('click', function () {
        // 배송완료만 한 번 묻는다(서버가 문구를 실어 준다). 발송은 연속 처리라 안 묻는다.
        var ask = $(this).data('confirm');
        if (ask && !confirm(ask)) return;
        var $tr = $(this).closest('tr');
        if (!fill($(this).data('next'), $tr)) return;
        // 발송 처리 전에 송장부터 저장되도록 mode=ship 묶음 처리로 보낸다
        $('#dlv_mode').val('ship');
        $('#dlv_action').val($(this).data('action'));
        $('#cart_dlv_form').trigger('submit');
    });
});
</script>
