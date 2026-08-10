<form method="get" action="{{ $self_url }}" class="local_sch01 local_sch">
    {{-- 주문 목록과 같은 달력 칸(adm/cart/views/order_list.blade.php 주석 참고) --}}
    <input type="date" name="from" value="{{ $from }}" class="frm_input cart-date" max="{{ $today }}"
           title="시작일" aria-label="시작일">
    ~
    <input type="date" name="to" value="{{ $to }}" class="frm_input cart-date" max="{{ $today }}"
           title="종료일" aria-label="종료일">
    <button type="submit" class="btn_submit btn">조회</button>
    <span class="btn_ov01"><span class="ov_txt">결제 {{ number_format($sum['cnt']) }}건</span><span class="ov_num">{{ number_format($sum['total']) }}원</span></span>
    <span class="btn_ov01"><span class="ov_txt">기간 내 취소</span><span class="ov_num">{{ number_format($canceled['cnt']) }}건 · {{ number_format($canceled['amt']) }}원</span></span>
</form>

<h2 class="h2_frm">일별 매출 (결제 확정 기준)</h2>
{{-- 폭을 적어 두는 이유 — 순정 .td_num 은 60px 이라 숫자 여섯 칸이 오른쪽 끝에 몰리고,
     폭을 안 적은 날짜가 남는 자리를 전부 삼켜 1000px 짜리 열이 된다. 열마다 몫을 정해 준다.
     퍼센트인 이유: 관리자 본문은 창 너비를 따라 늘어난다(#container min-width 1200px). --}}
<div class="tbl_head01 tbl_wrap">
    <table>
    <colgroup>
        <col style="width:14%"><col style="width:10%"><col style="width:16%">
        <col style="width:12%"><col style="width:16%"><col style="width:14%"><col style="width:18%">
    </colgroup>
    <thead>
    <tr>
        <th scope="col">날짜</th><th scope="col">결제 건수</th><th scope="col">상품 합계</th>
        <th scope="col">배송비</th><th scope="col">쿠폰</th><th scope="col">총액</th>
        <th scope="col">환불</th><th scope="col">순매출</th>
    </tr>
    </thead>
    <tbody>

    {{-- 돈은 오른쪽 정렬 — 자릿수가 세로로 맞아야 어느 날이 큰지 눈으로 바로 읽힌다.
         건수는 자릿수 비교가 아니므로 가운데 그대로 둔다. --}}
    @foreach ($daily as $d)
    <tr class="bg{{ $loop->index % 2 }}">
        <td>{{ $d['d'] }}</td>
        <td class="td_num">{{ number_format($d['cnt']) }}</td>
        <td class="td_num_right">{{ number_format($d['item_amt']) }}</td>
        <td class="td_num_right">{{ number_format($d['ship_amt']) }}</td>
        <td class="td_num_right">{{ (int)$d['coupon_amt'] > 0 ? '-'.number_format($d['coupon_amt']) : '-' }}</td>
        <td class="td_num_right">{{ number_format($d['total_amt']) }}</td>
        <td class="td_num_right">{{ (int)$d['refund_amt'] > 0 ? '-'.number_format($d['refund_amt']) : '-' }}</td>
        <td class="td_num_right">{{ number_format($d['net_amt']) }}</td>
    </tr>
    @endforeach

    @if (count($daily))
    <tr>
        <th scope="row">합계</th>
        <td class="td_num"><strong>{{ number_format($sum['cnt']) }}</strong></td>
        <td class="td_num_right"><strong>{{ number_format($sum['item']) }}</strong></td>
        <td class="td_num_right"><strong>{{ number_format($sum['ship']) }}</strong></td>
        <td class="td_num_right"><strong>{{ $sum['coupon'] > 0 ? '-'.number_format($sum['coupon']) : '-' }}</strong></td>
        <td class="td_num_right"><strong>{{ number_format($sum['total']) }}</strong></td>
        <td class="td_num_right"><strong>{{ $sum['refund'] > 0 ? '-'.number_format($sum['refund']) : '-' }}</strong></td>
        <td class="td_num_right"><strong>{{ number_format($sum['net']) }}</strong></td>
    </tr>
    @else
    <tr><td colspan="8" class="empty_table">기간 내 결제 매출이 없습니다.</td></tr>
    @endif

    </tbody>
    </table>
</div>

<h2 class="h2_frm">결제수단별 합계</h2>
{{-- 세 줄짜리 요약이라 폭을 묶는다 — 본문 전폭(1300px 안팎)으로 늘리면 수단 이름과 금액이
     화면 양 끝으로 갈라져 한 줄로 안 읽힌다. 위 일별 매출과 달리 열이 셋뿐이라 늘릴 이유가 없다. --}}
<div class="tbl_head01 tbl_wrap" style="max-width:520px">
    <table>
    <colgroup>
        <col style="width:40%"><col style="width:24%"><col style="width:36%">
    </colgroup>
    <thead>
    <tr><th scope="col">수단</th><th scope="col">건수</th><th scope="col">금액</th></tr>
    </thead>
    <tbody>

    @foreach ($by_method as $m)
    <tr class="bg{{ $loop->index % 2 }}">
        <td>{{ $m['label'] }}</td>
        <td class="td_num">{{ number_format($m['cnt']) }}</td>
        <td class="td_num_right">{{ number_format($m['amt']) }}</td>
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
{{-- 사유·TID 만 폭을 비워 둔다 — 길이가 제각각인 두 열이 남는 자리를 내용대로 나눠 갖는다.
     시각은 초까지 한 줄에 들어가야 해서 155px 로 못 박는다(줄바꿈되면 표가 두 줄로 벌어진다). --}}
<div class="tbl_head01 tbl_wrap">
    <table>
    <colgroup>
        <col style="width:155px"><col style="width:150px"><col style="width:80px">
        <col style="width:110px"><col><col style="width:80px"><col>
    </colgroup>
    <thead>
    <tr>
        <th scope="col">시각</th><th scope="col">주문번호</th><th scope="col">수단</th>
        <th scope="col">금액</th><th scope="col">사유</th><th scope="col">전송</th><th scope="col">TID</th>
    </tr>
    </thead>
    <tbody>

    {{-- 초까지 보여 준다 — 같은 분에 여러 건이 몰리면 PG 관리자와 대사할 때 순서를 못 가린다.
         연도 앞 두 자리는 뗀다(yy-mm-dd hh:ii:ss) — 열 하나를 더 벌릴 만한 정보가 아니다. --}}
    @foreach ($netcancels as $n)
    <tr class="bg{{ $loop->index % 2 }}{{ $n['alarm'] ? 'cancel' : '' }}">
        <td class="td_datetime">{{ substr($n['pm_datetime'], 2) }}</td>
        <td><a href="{{ $n['view_url'] }}">{{ $n['od_no'] }}</a></td>
        <td>{{ $n['pm_method'] }}</td>
        <td class="td_num_right">{{ number_format($n['pm_amount']) }}</td>
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
