{{-- 카트 대시보드 — 순정 관리자 어휘만 쓴다: btn_ov01 요약 칩, shop_admin 의 sidx 그래프,
     tbl_head01 표. 전용 CSS 없음. 요약 칩의 글자 크기는 전 관리자 화면 공통으로 올렸다
     (adm/css/admin_extend_chip.css — 여기서만 키우면 다른 화면 칩과 어긋난다). --}}

{{-- 숫자를 보고 나면 바로 그 목록으로 가고 싶어진다. 갈 곳이 있는 칩은 링크로 만든다 —
     단, **누른 뒤 화면의 숫자가 칩과 같아야** 칩을 믿을 수 있다(어느 기준으로 맞췄는지는
     index.php 주석에 적었다). "담긴 장바구니" 는 볼 화면이 없어 그대로 둔다 --}}
<div class="local_ov01 local_ov" id="cart_dash_ov">
    <a class="btn_ov01" href="{{ $today_sales_url }}" title="오늘 정산 보기"><span class="ov_txt">오늘 매출</span><span class="ov_num">{{ number_format($today_sales) }}원 · {{ number_format($today_paid_cnt) }}건</span></a>
    <a class="btn_ov01" href="{{ $today_orders_url }}" title="오늘 들어온 주문 보기"><span class="ov_txt">오늘 주문</span><span class="ov_num">{{ number_format($today_orders) }}건</span></a>
    <a class="btn_ov01" href="{{ $item_show_url }}" title="판매 중인 상품 목록"><span class="ov_txt">판매 중 상품</span><span class="ov_num">{{ number_format($item_cnt) }}</span></a>
    <span class="btn_ov01"><span class="ov_txt">담긴 장바구니</span><span class="ov_num">{{ number_format($cart_cnt) }}행</span></span>
    <a class="btn_ov01" href="#anc_low" title="재고 임박 목록으로"><span class="ov_txt">재고 임박</span><span class="ov_num">{{ number_format($low_total) }} SKU</span></a>

    {{-- 반품 신청 — 기다리는 사람이 있는 일이라 0 이 아닐 때만, 대신 눌러서 바로 갈 수 있게 --}}
    @if ($return_pending)
    <a class="btn_ov01" href="{{ $return_url }}" title="반품관리에서 승인·거절"><span class="ov_txt">반품 신청</span><span class="ov_num">{{ number_format($return_pending) }}건 대기</span></a>
    @endif

</div>

<div class="sidx">
    <section id="anc_sidx_ord">
        <h2>최근 7일 매출 (결제 확정 기준)</h2>
        <div id="sidx_graph" style="height:305px">
            <ul id="sidx_graph_price">

                @foreach ($y_val as $v)
                <li><span></span>{{ number_format($v) }}</li>
                @endforeach

            </ul>
            <ul id="sidx_graph_area">

                @foreach ($days as $d)
                <li class="bg{{ $loop->index % 2 }}" style="z-index:{{ 10 - $loop->index }}">
                    <div class="graph order" style="height:{{ $d['h'] }}px"
                         title="{{ $d['label'] }} 매출: {{ number_format($d['amt']) }}원 ({{ $d['cnt'] }}건)"></div>
                </li>
                @endforeach

            </ul>
            <ul id="sidx_graph_date">

                @foreach ($days as $d)
                <li><span></span>{{ $d['label'] }}</li>
                @endforeach

            </ul>
        </div>
    </section>

    <div id="sidx_stat">
        <h2>주문 현황 (전체 누적)</h2>
        <div class="tbl_head01 tbl_wrap">
            <table>
            <thead>
            <tr><th scope="col">상태</th><th scope="col">건수</th><th scope="col">금액</th></tr>
            </thead>
            <tbody>
            <tr><th scope="row"><a href="{{ $status_url }}unpaid">입금대기</a></th><td class="td_num">{{ number_format($status_sum['unpaid']['cnt']) }}</td><td class="td_num">{{ number_format($status_sum['unpaid']['amt']) }}</td></tr>
            {{-- 결제완료·배송 진행은 여러 상태를 묶은 값이라 상태 필터 하나로는 같은 숫자가
                 안 나온다. 배송 진행은 대신 그 일을 하는 화면(배송관리)으로 보낸다 --}}
            <tr><th scope="row">결제완료</th><td class="td_num">{{ number_format($status_sum['paid']['cnt']) }}</td><td class="td_num">{{ number_format($status_sum['paid']['amt']) }}</td></tr>
            <tr><th scope="row"><a href="{{ $delivery_url }}">배송 진행</a></th><td class="td_num">{{ number_format($status_sum['shipping']['cnt']) }}</td><td class="td_num">{{ number_format($status_sum['shipping']['amt']) }}</td></tr>
            <tr><th scope="row"><a href="{{ $status_url }}canceled">취소</a></th><td class="td_num">{{ number_format($status_sum['canceled']['cnt']) }}</td><td class="td_num">{{ number_format($status_sum['canceled']['amt']) }}</td></tr>
            </tbody>
            </table>
        </div>
    </div>
</div>

<h2 class="h2_frm">최근 주문</h2>
<div class="tbl_head01 tbl_wrap">
    <table>
    <thead>
    <tr>
        <th scope="col">주문번호</th><th scope="col">상품</th><th scope="col">주문자</th>
        <th scope="col">상태</th><th scope="col">금액</th><th scope="col">일시</th>
    </tr>
    </thead>
    <tbody>

    @foreach ($recent as $o)
    <tr class="bg{{ $loop->index % 2 }}{{ $o['od_status'] === 'canceled' ? 'cancel' : '' }}">
        <td><a href="{{ $o['view_url'] }}">{{ $o['od_no'] }}</a></td>
        <td class="td_left">{{ $o['summary'] }}</td>
        <td>{{ $o['od_name'] }}</td>
        <td><span class="cart-od-status is-{{ cart_order_status_tone($o['od_status']) }}">{{ $o['status_label'] }}</span></td>
        <td class="td_num">{{ number_format($o['od_total']) }}</td>
        <td class="td_datetime">{{ substr($o['od_datetime'], 0, 16) }}</td>
    </tr>
    @endforeach

    @if (!count($recent))
    <tr><td colspan="6" class="empty_table">아직 주문이 없습니다.</td></tr>
    @endif

    </tbody>
    </table>
</div>

<h2 class="h2_frm" id="anc_low">재고 임박 ({{ $low_limit }}개 이하 · {{ number_format($low_total) }} SKU)</h2>
<div class="tbl_head01 tbl_wrap">
    <table>
    <thead>
    <tr>
        <th scope="col">상품</th><th scope="col">옵션</th><th scope="col">SKU 코드</th>
        <th scope="col">재고</th><th scope="col">관리</th>
    </tr>
    </thead>
    <tbody>

    @foreach ($low_rows as $s)
    <tr class="bg{{ $loop->index % 2 }}{{ (int)$s['sk_qty'] === 0 ? 'cancel' : '' }}">
        <td class="td_left"><a href="{{ $s['edit_url'] }}">{{ $s['it_name'] }}</a></td>
        <td>{{ $s['opt_label'] }}</td>
        <td>{{ $s['sk_code'] }}</td>
        <td class="td_num">{{ (int)$s['sk_qty'] === 0 ? '품절' : number_format($s['sk_qty']) }}</td>
        <td><a href="{{ $s['edit_url'] }}" class="btn btn_02">수정</a></td>
    </tr>
    @endforeach

    @if (!count($low_rows))
    <tr><td colspan="5" class="empty_table">재고가 {{ $low_limit }}개 이하인 SKU 가 없습니다.</td></tr>
    @endif

    </tbody>
    </table>
</div>
