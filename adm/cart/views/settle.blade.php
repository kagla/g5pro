<form method="get" action="{{ $self_url }}" class="local_sch01 local_sch">
    <input type="text" name="from" value="{{ $from }}" class="frm_input" size="12">
    ~
    <input type="text" name="to" value="{{ $to }}" class="frm_input" size="12">
    <button type="submit" class="btn_submit btn">조회</button>
    <span class="btn_ov01"><span class="ov_txt">결제 {{ number_format($sum['cnt']) }}건</span><span class="ov_num">{{ number_format($sum['total']) }}원</span></span>
    <span class="btn_ov01"><span class="ov_txt">기간 내 취소</span><span class="ov_num">{{ number_format($canceled['cnt']) }}건 · {{ number_format($canceled['amt']) }}원</span></span>
</form>

<h2 class="h2_frm">일별 매출 (결제 확정 기준)</h2>
<div class="tbl_head01 tbl_wrap">
    <table>
    <thead>
    <tr>
        <th scope="col">날짜</th><th scope="col">결제 건수</th><th scope="col">상품 합계</th>
        <th scope="col">배송비</th><th scope="col">총액</th>
        <th scope="col">환불</th><th scope="col">순매출</th>
    </tr>
    </thead>
    <tbody>

    @foreach ($daily as $d)
    <tr class="bg{{ $loop->index % 2 }}">
        <td>{{ $d['d'] }}</td>
        <td class="td_num">{{ number_format($d['cnt']) }}</td>
        <td class="td_num">{{ number_format($d['item_amt']) }}</td>
        <td class="td_num">{{ number_format($d['ship_amt']) }}</td>
        <td class="td_num">{{ number_format($d['total_amt']) }}</td>
        <td class="td_num">{{ (int)$d['refund_amt'] > 0 ? '-'.number_format($d['refund_amt']) : '-' }}</td>
        <td class="td_num">{{ number_format($d['net_amt']) }}</td>
    </tr>
    @endforeach

    @if (count($daily))
    <tr>
        <th scope="row">합계</th>
        <td class="td_num"><strong>{{ number_format($sum['cnt']) }}</strong></td>
        <td class="td_num"><strong>{{ number_format($sum['item']) }}</strong></td>
        <td class="td_num"><strong>{{ number_format($sum['ship']) }}</strong></td>
        <td class="td_num"><strong>{{ number_format($sum['total']) }}</strong></td>
        <td class="td_num"><strong>{{ $sum['refund'] > 0 ? '-'.number_format($sum['refund']) : '-' }}</strong></td>
        <td class="td_num"><strong>{{ number_format($sum['net']) }}</strong></td>
    </tr>
    @else
    <tr><td colspan="7" class="empty_table">기간 내 결제 매출이 없습니다.</td></tr>
    @endif

    </tbody>
    </table>
</div>

<h2 class="h2_frm">결제수단별 합계</h2>
<div class="tbl_head01 tbl_wrap">
    <table>
    <thead>
    <tr><th scope="col">수단</th><th scope="col">건수</th><th scope="col">금액</th></tr>
    </thead>
    <tbody>

    @foreach ($by_method as $m)
    <tr class="bg{{ $loop->index % 2 }}">
        <td>{{ $m['label'] }}</td>
        <td class="td_num">{{ number_format($m['cnt']) }}</td>
        <td class="td_num">{{ number_format($m['amt']) }}</td>
    </tr>
    @endforeach

    @if (!count($by_method))
    <tr><td colspan="3" class="empty_table">기간 내 결제가 없습니다.</td></tr>
    @endif

    </tbody>
    </table>
</div>

<h2 class="h2_frm">PG 자동취소(망취소) 이력 — 최근 50건</h2>
<div class="local_desc02 local_desc">
    <p>결제 확정에 실패해 승인을 자동으로 되돌린 기록입니다. <strong>붉게 표시된 행(취소 미확인)</strong>은
       취소 전송이 실패했을 수 있으니 PG 관리자에서 해당 거래를 반드시 대사하세요. sent=skip 은 승인 자체가 거절돼 취소가 필요 없던 경우입니다.</p>
</div>
<div class="tbl_head01 tbl_wrap">
    <table>
    <thead>
    <tr>
        <th scope="col">시각</th><th scope="col">주문번호</th><th scope="col">수단</th>
        <th scope="col">금액</th><th scope="col">사유</th><th scope="col">전송</th><th scope="col">TID</th>
    </tr>
    </thead>
    <tbody>

    @foreach ($netcancels as $n)
    <tr class="bg{{ $loop->index % 2 }}{{ $n['alarm'] ? 'cancel' : '' }}">
        <td class="td_datetime">{{ substr($n['pm_datetime'], 0, 16) }}</td>
        <td><a href="{{ $n['view_url'] }}">{{ $n['od_no'] }}</a></td>
        <td>{{ $n['pm_method'] }}</td>
        <td class="td_num">{{ number_format($n['pm_amount']) }}</td>
        <td>{{ $n['reason'] }}</td>
        <td>{{ $n['sent'] }}{{ $n['alarm'] ? ' ⚠' : '' }}</td>
        <td>{{ $n['pm_tid'] }}</td>
    </tr>
    @endforeach

    @if (!count($netcancels))
    <tr><td colspan="7" class="empty_table">망취소 이력이 없습니다.</td></tr>
    @endif

    </tbody>
    </table>
</div>
