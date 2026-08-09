{{-- 쿠폰관리 — 발급 경로가 넷이지만 목록은 하나다. 어느 길로 발급하든 결과는
     "회원 쿠폰함에 한 장" 이라 종류·발급수·사용수만 보면 상태를 알 수 있다. --}}
<div class="local_desc01 local_desc">
    <p>쿠폰은 회원 쿠폰함에 들어간 뒤 주문서에서 한 장만 골라 쓸 수 있습니다.
       할인은 대상 상품 합계에만 적용되고 배송비는 깎지 않습니다(무료배송 기준이 그 역할).
       가입 축하·첫 구매 쿠폰은 회원이 쿠폰함이나 주문서를 열 때 자동으로 발급됩니다.</p>
</div>

<form method="get" action="{{ $self_url }}" class="local_sch01 local_sch">
    <input type="text" name="q" value="{{ $q }}" placeholder="쿠폰 이름·코드·메모" class="frm_input" size="24">
    <button type="submit" class="btn_submit btn">검색</button>
    <span class="btn_ov01"><span class="ov_txt">전체 {{ number_format($total) }}개 · {{ $page }}/{{ $total_page }}</span></span>
    <a href="{{ $form_url }}" class="btn btn_02">쿠폰 등록</a>
</form>

<div class="tbl_head01 tbl_wrap">
    <table>
    <thead>
    <tr>
        <th scope="col">이름</th><th scope="col">코드</th><th scope="col">발급 방법</th>
        <th scope="col">할인</th><th scope="col">대상</th><th scope="col">기간</th>
        <th scope="col">발급</th><th scope="col">사용</th><th scope="col">상태</th>
    </tr>
    </thead>
    <tbody>

    @foreach ($coupons as $cp)
    <tr class="bg{{ $loop->index % 2 }}">
        <td class="td_left"><a href="{{ $cp['edit_url'] }}">{{ $cp['cp_name'] }}</a>

            @if ($cp['cp_memo'] !== '')
            <br><span class="txt_id">{{ $cp['cp_memo'] }}</span>
            @endif

        </td>
        <td>{{ $cp['cp_code'] !== '' ? $cp['cp_code'] : '-' }}</td>
        <td>{{ $cp['issue_label'] }}</td>
        <td class="td_left">{{ $cp['label'] }}

            @if ((int)$cp['cp_min'] > 0)
            <br><span class="txt_id">{{ number_format($cp['cp_min']) }}원 이상</span>
            @endif

        </td>
        <td class="td_left">{{ $cp['target_label'] }}</td>
        <td class="td_datetime">{{ substr($cp['cp_begin'], 2) }} ~ {{ substr($cp['cp_end'], 2) }}

            @if ((int)$cp['cp_days'] > 0)
            <br><span class="txt_id">받은 날부터 {{ $cp['cp_days'] }}일</span>
            @endif

        </td>
        <td class="td_num">{{ number_format($cp['issued']) }}</td>
        <td class="td_num">{{ number_format($cp['used']) }}

            @if ($cp['used_amount'] > 0)
            <br><span class="txt_id">{{ number_format($cp['used_amount']) }}원</span>
            @endif

        </td>
        <td>{{ $cp['live'] ? '발급 중' : $cp['why_off'] }}</td>
    </tr>
    @endforeach

    @if (!count($coupons))
    <tr><td colspan="9" class="empty_table">등록된 쿠폰이 없습니다.</td></tr>
    @endif

    </tbody>
    </table>
</div>

@if ($total_page > 1)
<nav class="pg_wrap"><span class="pg">

    @for ($p = 1; $p <= $total_page; $p++)
    <a href="{{ $self_url }}?page={{ $p }}&amp;q={{ urlencode($q) }}" class="pg_page {{ $p === $page ? 'pg_current' : '' }}">{{ $p }}</a>
    @endfor

</span></nav>
@endif
