<style>
#cart_dep td { text-align: center; }
#cart_dep td.td_left { text-align: left; }
#cart_dep td.td_num { text-align: right; }
#cart_dep .dep_due { font-weight: bold; }
#cart_dep .dep_over { color: #C4314B; font-weight: bold; }
#cart_dep_sum { margin: 10px 0 0; color: #555; }
#cart_dep_sum strong { color: #1D5FD1; }
</style>

<div class="local_desc01 local_desc">
    <p>입금을 기다리는 <strong>무통장</strong> 주문입니다. 입금확인을 누르면 결제완료가 되고 이 화면을 떠납니다.</p>

    @if ($bank !== '')
    <p>입금 계좌: <strong>{{ $bank }}</strong></p>
    @endif

    @if ($days > 0)
    <p>입금 기한은 주문일로부터 <strong>{{ $days }}일</strong>입니다. 기한이 지나면 자동으로 취소되고 재고와 쿠폰이 돌아갑니다.</p>
    @else
    <p>자동 취소가 꺼져 있습니다(환경설정의 무통장 입금 기한 0일). 입금이 없는 주문이 재고를 계속 잡고 있게 됩니다.</p>
    @endif

</div>

<form method="get" action="{{ $self_url }}" class="local_sch01 local_sch">
    <select name="tab" onchange="this.form.submit()">

        @foreach ($tabs as $key => $label)
        <option value="{{ $key }}" {{ $tab === $key ? 'selected' : '' }}>{{ $label }}{{ isset($tab_counts[$key]) ? ' ('.number_format($tab_counts[$key]).')' : '' }}</option>
        @endforeach

    </select>
    <span class="btn_ov01"><span class="ov_txt">{{ number_format($total) }}건 · {{ number_format($total_amt) }}원</span></span>
</form>

<form method="post" action="{{ $update_url }}" id="cart_dep">
<input type="hidden" name="token" value="{{ $token }}">
<input type="hidden" name="tab" value="{{ $tab }}">

<div class="tbl_head01 tbl_wrap">
    <table>
    <caption>입금 대기 주문</caption>
    <thead>
    <tr>
        <th scope="col"><input type="checkbox" id="cart_dep_all" title="전체 선택"></th>
        <th scope="col">주문번호</th>
        <th scope="col">입금자명</th>
        <th scope="col">주문자</th>
        <th scope="col">상품</th>
        <th scope="col">금액</th>
        <th scope="col">주문일시</th>
        <th scope="col">기한</th>
    </tr>
    </thead>
    <tbody>

    @foreach ($orders as $o)
    <tr class="bg{{ $loop->index % 2 }}{{ ($o['left_days'] !== null && $o['left_days'] < 0) ? 'cancel' : '' }}">
        <td><input type="checkbox" name="od_id[]" value="{{ $o['od_id'] }}" class="dep_pick" data-amt="{{ $o['od_total'] }}"></td>
        <td><a href="{{ $o['view_url'] }}">{{ $o['od_no'] }}</a></td>
        <td><strong>{{ $o['od_depositor'] !== '' ? $o['od_depositor'] : '-' }}</strong></td>
        <td>{{ $o['od_name'] }}</td>
        <td class="td_left">{{ $o['summary'] }}</td>
        <td class="td_num">{{ number_format($o['od_total']) }}원</td>
        <td>{{ substr($o['od_datetime'], 2, 14) }}</td>
        <td>

            @if ($o['left_days'] === null)
            -
            @elseif ($o['left_days'] < 0)
            <span class="dep_over">기한 지남</span>
            @elseif ($o['left_days'] === 0)
            <span class="dep_over">오늘 마감</span>
            @elseif ($o['left_days'] <= 1)
            <span class="dep_due">D-{{ $o['left_days'] }}</span>
            @else
            D-{{ $o['left_days'] }}
            @endif

        </td>
    </tr>
    @endforeach

    @if (!count($orders))
    <tr><td colspan="8" class="empty_table">입금을 기다리는 주문이 없습니다.</td></tr>
    @endif

    </tbody>
    </table>
</div>

<p id="cart_dep_sum">선택 <strong id="cart_dep_cnt">0</strong>건 · <strong id="cart_dep_amt">0</strong>원</p>

<div class="btn_confirm01 btn_confirm" style="text-align:right">
    <button type="submit" class="btn_submit btn">선택한 주문 입금확인</button>
</div>
</form>

@if ($total_page > 1)
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
// 고른 건수와 합계를 늘 보여 준다 — 통장 내역과 맞춰 보는 화면이라 "얼마어치를 넘기는가" 가
// 확인 창에서 처음 보이면 늦다. 입금확인은 되돌릴 수 있지만(주문 상세) 되돌릴 일은 없는 게 낫다.
$(function () {
    var $form = $('#cart_dep');

    function sum() {
        var n = 0, amt = 0;
        $form.find('.dep_pick:checked').each(function () {
            n += 1;
            amt += parseInt($(this).data('amt'), 10) || 0;
        });
        $('#cart_dep_cnt').text(n.toLocaleString());
        $('#cart_dep_amt').text(amt.toLocaleString());
        return { n: n, amt: amt };
    }

    $('#cart_dep_all').on('change', function () {
        $form.find('.dep_pick').prop('checked', $(this).is(':checked'));
        sum();
    });
    $form.on('change', '.dep_pick', function () {
        $('#cart_dep_all').prop('checked', $form.find('.dep_pick:not(:checked)').length === 0);
        sum();
    });

    $form.on('submit', function () {
        var s = sum();
        if (!s.n) { alert('처리할 주문을 선택하세요.'); return false; }
        return confirm(s.n + '건 · ' + s.amt.toLocaleString() + '원을 입금확인 처리합니다.\n\n'
            + '결제완료가 되어 배송관리로 넘어갑니다.\n계속할까요?');
    });

    sum();
});
</script>
