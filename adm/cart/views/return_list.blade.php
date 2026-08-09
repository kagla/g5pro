{{-- 반품관리 — 주문 상세를 하나씩 열지 않고 여기서 바로 승인·거절한다.
     순정 관리자 어휘만 쓴다(local_sch 검색줄, tbl_head01 표, btn_ov01 요약 칩). --}}
<form method="get" action="{{ $self_url }}" class="local_sch01 local_sch">
    <select name="status">

        @foreach ($statuses as $key => $label)
        <option value="{{ $key }}" {{ $status === $key ? 'selected' : '' }}>{{ $label }}{{ isset($counts[$key]) ? ' ('.number_format($counts[$key]).')' : '' }}</option>
        @endforeach

        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>전체</option>
    </select>
    <input type="text" name="q" value="{{ $q }}" placeholder="주문번호·주문자·연락처·회원ID" class="frm_input" size="24">
    <button type="submit" class="btn_submit btn">검색</button>
    <span class="btn_ov01"><span class="ov_txt">전체 {{ number_format($total) }}건 · {{ $page }}/{{ $total_page }}</span></span>
</form>

<div class="tbl_head01 tbl_wrap">
    <table>
    <thead>
    <tr>
        <th scope="col">신청일시</th><th scope="col">주문</th><th scope="col">주문자</th>
        <th scope="col">반품 품목</th><th scope="col">사유</th>
        <th scope="col">환불</th><th scope="col">상태</th><th scope="col">처리</th>
    </tr>
    </thead>
    <tbody>

    @foreach ($returns as $rt)
    <tr class="bg{{ $loop->index % 2 }}{{ $rt['rt_status'] === 'rejected' ? 'cancel' : '' }}">
        <td class="td_datetime">{{ substr($rt['rt_requested_at'], 0, 16) }}</td>
        <td><a href="{{ $rt['view_url'] }}">{{ $rt['od_no'] }}</a></td>
        <td>{{ $rt['od_name'] }}</td>
        <td class="td_left">

            @foreach ($rt['item_names'] as $nm)
            {{ $nm }}<br>
            @endforeach

            <span class="txt_id">품목 합계 {{ number_format($rt['item_total']) }}원</span>
        </td>
        <td class="td_left">{{ $rt['rt_reason'] }}

            @if ($rt['rt_bank'] !== '')
            <br><span class="txt_id">환불 계좌: {{ $rt['rt_bank'] }}</span>
            @endif

            @if ($rt['rt_memo'] !== '')
            <br><span class="txt_id">메모: {{ $rt['rt_memo'] }}</span>
            @endif

        </td>
        <td class="td_num">{{ (int)$rt['rt_refund'] > 0 ? number_format($rt['rt_refund']).'원' : '-' }}</td>
        <td>{{ $rt['status_label'] }}</td>
        <td>

            @if ($rt['rt_status'] === 'requested' || $rt['rt_status'] === 'approving')
            <button type="button" class="btn btn_02 cart-rt-open"
                    data-rt="{{ $rt['rt_id'] }}" data-od="{{ $rt['od_id'] }}"
                    data-sum="{{ min($rt['item_total'], $rt['refundable']) }}"
                    data-max="{{ $rt['refundable'] }}"
                    data-bank="{{ $rt['is_bank'] ? 1 : 0 }}">승인·거절</button>
            @else
            <span class="txt_id">{{ substr($rt['rt_done_at'], 0, 16) }}<br>{{ $rt['rt_done_by'] }}</span>
            @endif

        </td>
    </tr>
    @endforeach

    @if (!count($returns))
    <tr><td colspan="8" class="empty_table">해당하는 반품 신청이 없습니다.</td></tr>
    @endif

    </tbody>
    </table>
</div>

@if ($total_page > 1)
@php $qs = array('status' => $status, 'q' => $q); @endphp
<nav class="pg_wrap"><span class="pg">

    @if ($page > 1)
    <a href="{{ $self_url.'?'.http_build_query($qs + array('page' => $page - 1)) }}" class="pg_page pg_prev">이전</a>
    @endif

    @for ($p = max(1, $page - 4); $p <= min($total_page, $page + 4); $p++)
    <a href="{{ $self_url.'?'.http_build_query($qs + array('page' => $p)) }}" class="pg_page {{ $p === $page ? 'pg_current' : '' }}">{{ $p }}</a>
    @endfor

    @if ($page < $total_page)
    <a href="{{ $self_url.'?'.http_build_query($qs + array('page' => $page + 1)) }}" class="pg_page pg_next">다음</a>
    @endif

</span></nav>
@endif

{{-- 처리 모달 — 주문 상세의 것과 같은 규칙: 갈래는 select 로 뽑고(버튼이 둘이면 입력칸에서
     Enter 가 늘 앞 버튼을 누른다), 관리자 비밀번호를 다시 받는다. 돈이 나가는 자리다.
     처리 뒤에는 이 목록으로 돌아온다(ret=return) — 여러 건을 이어서 처리하는 화면이다 --}}
<div id="cart_rt_modal" style="display:none; position:fixed; inset:0; z-index:1000; background:rgba(0,0,0,.45)">
    <div style="max-width:460px; margin:10vh auto 0; background:#fff; border-radius:8px; padding:24px">
        <h3 style="margin:0 0 6px">반품 처리</h3>
        <p style="margin:0 0 14px; color:#666; font-size:0.95em" id="cart_rt_desc"></p>
        <form method="post" action="{{ $update_url }}">
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="ret" value="return">
        <input type="hidden" name="od_id" id="cart_rt_od" value="">
        <input type="hidden" name="rt_id" id="cart_rt_id" value="">
        <p style="margin:0 0 10px">
            <label>처리<br>
            <select name="mode" id="cart_rt_mode" class="frm_input" style="width:100%">
                <option value="return_approve">승인 — 환불하고 반품 처리</option>
                <option value="return_reject">거절 — 되돌려 보내고 원래대로</option>
            </select></label>
        </p>
        <p style="margin:0 0 10px" id="cart_rt_money">
            <label>환불 금액<br>
            <input type="text" name="rt_refund" id="cart_rt_refund" class="frm_input" style="width:100%" autocomplete="off"></label>
            <span class="txt_id" id="cart_rt_max"></span>
        </p>
        <p style="margin:0 0 10px" id="cart_rt_stock">
            <label><input type="checkbox" name="rt_restock" value="1" checked> 재고를 되돌린다</label>
            <span class="txt_id">물건이 훼손돼 다시 팔 수 없으면 체크를 해제하세요.</span>
        </p>
        <p style="margin:0 0 10px">
            <label>메모 / 거절 사유<br>
            <input type="text" name="rt_memo" class="frm_input" style="width:100%" maxlength="255"
                   autocomplete="off" placeholder="거절할 때는 사유를 꼭 적어 주세요(고객에게 보입니다)"></label>
        </p>
        <p style="margin:0 0 16px">
            <label>관리자 비밀번호 확인<br>
            <input type="password" name="admin_pw" class="frm_input" style="width:100%" autocomplete="new-password"></label>
        </p>
        <div style="text-align:right">
            <button type="button" class="btn btn_02" id="cart_rt_close">닫기</button>
            <button type="submit" class="btn_submit btn" id="cart_rt_submit">승인 · 환불</button>
        </div>
        </form>
    </div>
</div>

<script>
$(function () {
    var $modal = $('#cart_rt_modal'), $mode = $('#cart_rt_mode');

    function paint() {
        var approve = ($mode.val() === 'return_approve');
        $('#cart_rt_money, #cart_rt_stock').toggle(approve);
        $('#cart_rt_submit').text(approve ? '승인 · 환불' : '거절').toggleClass('btn_submit', approve);
    }

    $('.cart-rt-open').on('click', function () {
        var $b = $(this);
        $('#cart_rt_id').val($b.data('rt'));
        $('#cart_rt_od').val($b.data('od'));
        // 환불 기본값은 고른 품목 합계 — 제안일 뿐이고 최종 금액은 사람이 정한다
        $('#cart_rt_refund').val($b.data('sum'));
        $('#cart_rt_max').text('남은 결제 금액 ' + Number($b.data('max')).toLocaleString()
            + '원까지. 배송비는 환불하지 않는 것이 기본입니다.');
        $('#cart_rt_desc').text($b.data('bank') === 1
            ? '승인하면 환불 기록이 남습니다. 계좌 송금은 직접 하셔야 합니다. 되돌릴 수 없습니다.'
            : '승인하면 전자결제가 그 금액만큼 부분취소됩니다. 되돌릴 수 없습니다.');
        $modal.find('input[name="rt_memo"], input[name="admin_pw"]').val('');
        $mode.val('return_approve');
        paint();
        $modal.show();
    });
    $mode.on('change', paint);
    // 닫기 버튼으로만 닫는다 — 배경 클릭에 닫히면 쓰던 값이 실수로 날아간다
    $('#cart_rt_close').on('click', function () { $modal.hide(); });

    $modal.find('form').on('submit', function () {
        var $f = $(this), approve = ($mode.val() === 'return_approve');
        if (!approve && $.trim($f.find('input[name="rt_memo"]').val()) === '') {
            alert('거절 사유를 입력하세요. 고객 화면에 그대로 보입니다.');
            $f.find('input[name="rt_memo"]').trigger('focus');
            return false;
        }
        if ($f.find('input[name="admin_pw"]').val() === '') {
            alert('관리자 비밀번호를 입력하세요.');
            $f.find('input[name="admin_pw"]').trigger('focus');
            return false;
        }
        if (approve) {
            var amt = parseInt(String($('#cart_rt_refund').val()).replace(/[^0-9]/g, ''), 10) || 0;
            return confirm('환불 ' + amt.toLocaleString() + '원으로 반품을 승인합니다. 되돌릴 수 없습니다.');
        }
        return true;
    });
});
</script>
