<div class="local_ov01 local_ov">
    <span class="btn_ov01"><span class="ov_txt">상태</span><span class="ov_num">{{ $status_label }}</span></span>
    <span class="btn_ov01"><span class="ov_txt">결제수단</span><span class="ov_num">{{ $order['od_pay_method'] === 'bank' ? '무통장' : ($order['od_pay_method'] === 'inicis' ? '이니시스' : '토스') }}</span></span>
    <span class="btn_ov01"><span class="ov_txt">결제 금액</span><span class="ov_num">{{ number_format($order['od_total']) }}원</span></span>
    <a href="{{ $list_url }}" class="btn btn_02">목록</a>
</div>

<div class="tbl_frm01 tbl_wrap">
    <table>
    <caption>주문 정보</caption>
    <tbody>
    <tr>
        <th scope="row">주문번호</th><td>{{ $order['od_no'] }}</td>
        <th scope="row">주문일시</th><td>{{ $order['od_datetime'] }}</td>
    </tr>
    <tr>
        <th scope="row">주문자</th><td>{{ $order['od_name'] }}{{ $order['mb_id'] !== '' ? ' ('.$order['mb_id'].')' : ' (비회원)' }}</td>
        <th scope="row">연락처</th><td>{{ $order['od_hp'] }}</td>
    </tr>
    <tr>
        <th scope="row">이메일</th><td>{{ $order['od_email'] }}</td>
        <th scope="row">입금자명</th><td>{{ $order['od_depositor'] }}</td>
    </tr>
    <tr>
        <th scope="row">받는분</th><td>{{ $order['od_recv_name'] !== '' ? $order['od_recv_name'] : $order['od_name'] }}</td>
        <th scope="row">받는분 연락처</th><td>{{ $order['od_recv_hp'] !== '' ? $order['od_recv_hp'] : $order['od_hp'] }}</td>
    </tr>
    <tr>
        <th scope="row">배송지</th>
        <td colspan="3">[{{ $order['od_zip'] }}] {{ $order['od_addr1'] }} {{ $order['od_addr2'] }}</td>
    </tr>
    <tr>
        <th scope="row">배송 요청</th><td>{{ $order['od_memo'] }}</td>
        <th scope="row">결제일시</th><td>{{ substr($order['od_paid_at'], 0, 4) !== '1970' ? $order['od_paid_at'] : '-' }}</td>
    </tr>

    @if ($order['od_status'] === 'canceled')
    <tr>
        <th scope="row">취소 사유</th><td>{{ $order['od_cancel_reason'] !== '' ? $order['od_cancel_reason'] : '-' }}</td>
        <th scope="row">취소 처리</th>
        <td>관리자 직권 취소{{ $order['od_canceled_by'] !== '' ? ' ('.$order['od_canceled_by'].')' : '' }}{{ substr($order['od_canceled_at'], 0, 4) !== '1970' ? ' · '.$order['od_canceled_at'] : '' }}</td>
    </tr>
    @endif

    <tr>
        <th scope="row">상품 합계</th><td>{{ number_format($order['od_item_total']) }}원</td>
        <th scope="row">배송비</th><td>{{ number_format($order['od_ship_fee']) }}원</td>
    </tr>

    {{-- 깎인 금액만 있으면 왜 깎였는지 알 수 없다 — 어느 쿠폰이었는지 함께 적는다 --}}
    @if ((int)$order['od_coupon'] > 0)
    <tr>
        <th scope="row">쿠폰 할인</th>
        <td colspan="3">-{{ number_format($order['od_coupon']) }}원

            @if ($coupon)
            <span class="txt_id">{{ $coupon['cp_name'] }}{{ $coupon['cp_code'] !== '' ? ' ('.$coupon['cp_code'].')' : '' }}</span>
            @endif

        </td>
    </tr>
    @endif
    </tbody>
    </table>
</div>

<h2 class="h2_frm">주문 상품</h2>
<div class="tbl_head01 tbl_wrap">
    <table>
    <thead>
    <tr>
        <th scope="col">상품명</th><th scope="col">옵션</th><th scope="col">단가</th>
        <th scope="col">수량</th><th scope="col">합계</th>
    </tr>
    </thead>
    <tbody>

    @foreach ($items as $it)
    <tr class="bg{{ $loop->index % 2 }}">
        <td class="td_left">

            {{-- 상품명을 누르면 그 상품의 수정 화면으로 간다. 주문서는 스냅샷이라
                 상품이 이미 지워졌으면 링크 없이 이름만 남는다(서버가 판단해 내려준다) --}}
            @if ($it['edit_url'] !== '')
            <a href="{{ $it['edit_url'] }}" target="_blank" class="oi-link" title="상품 수정 화면 열기">{{ $it['oi_name'] }}</a>
            @else
            {{ $it['oi_name'] }} <span class="oi-gone">삭제된 상품</span>
            @endif

        </td>
        <td>{{ $it['oi_option'] }}</td>
        <td class="td_num">{{ number_format($it['oi_price']) }}원</td>
        <td class="td_num">{{ number_format($it['oi_qty']) }}</td>
        <td class="td_num">{{ number_format($it['oi_total']) }}원</td>
    </tr>
    @endforeach

    </tbody>
    </table>
</div>

<h2 class="h2_frm">배송 정보</h2>
<form method="post" action="{{ $update_url }}">
<input type="hidden" name="token" value="{{ $token }}">
<input type="hidden" name="od_id" value="{{ $order['od_id'] }}">
<input type="hidden" name="mode" value="invoice">
<div class="tbl_frm01 tbl_wrap">
    <table>
    <tbody>
    <tr>
        <th scope="row">택배사</th>
        <td><input type="text" name="od_dc_name" value="{{ $order['od_dc_name'] }}" class="frm_input" size="16"></td>
        <th scope="row">송장번호</th>
        <td>
            <input type="text" name="od_invoice" value="{{ $order['od_invoice'] }}" class="frm_input" size="24">
            <button type="submit" class="btn_submit btn">송장 저장</button>

            @if (substr($order['od_shipped_at'], 0, 4) !== '1970')
            <span>발송 {{ $order['od_shipped_at'] }}</span>
            @endif

        </td>
    </tr>
    </tbody>
    </table>
</div>
</form>

<h2 class="h2_frm">주문 처리</h2>

@if ($pg_paid)
<div class="local_desc02 local_desc">
    <p><strong>PG 결제 주문입니다 — 주문 취소 시 카드 승인이 자동으로 환불(전체취소)됩니다.</strong>
       환불이 실패하면 주문 취소도 진행되지 않습니다.</p>
</div>
@endif

{{-- 반품 신청 — 접수 건은 여기서 승인·거절한다. 처리된 건도 이력으로 함께 보여 준다 --}}
@if (count($returns))
<h2 class="h2_frm">반품</h2>
<div class="tbl_head01 tbl_wrap">
    <table>
    <thead>
    <tr>
        <th scope="col">신청</th><th scope="col">상태</th><th scope="col">품목</th>
        <th scope="col">사유</th><th scope="col">환불</th><th scope="col">처리</th>
    </tr>
    </thead>
    <tbody>

    @foreach ($returns as $rt)
    <tr>
        <td>{{ substr($rt['rt_requested_at'], 0, 16) }}</td>
        <td>{{ $rt['status_label'] }}</td>
        <td style="text-align:left">

            @foreach ($rt['item_names'] as $nm)
            {{ $nm }}<br>
            @endforeach

            <span class="txt_id">품목 합계 {{ number_format($rt['item_total']) }}원</span>
        </td>
        <td style="text-align:left">{{ $rt['rt_reason'] }}

            @if ($rt['rt_bank'] !== '')
            <br><span class="txt_id">환불 계좌: {{ $rt['rt_bank'] }}</span>
            @endif

            @if ($rt['rt_memo'] !== '')
            <br><span class="txt_id">메모: {{ $rt['rt_memo'] }}</span>
            @endif

        </td>
        <td>{{ (int)$rt['rt_refund'] > 0 ? number_format($rt['rt_refund']).'원' : '-' }}</td>
        <td>

            @if ($rt['rt_status'] === 'requested')
            <button type="button" class="btn btn_02 cart-rt-open"
                    data-rt="{{ $rt['rt_id'] }}" data-sum="{{ $rt['item_total'] }}">승인·거절</button>
            @else
            <span class="txt_id">{{ substr($rt['rt_done_at'], 0, 16) }} {{ $rt['rt_done_by'] }}</span>
            @endif

        </td>
    </tr>
    @endforeach

    </tbody>
    </table>
</div>

{{-- 반품 처리 모달 — 취소 모달과 같은 규칙(관리자 비밀번호 재확인). 돈이 나가는 자리다.
     환불 금액 기본값은 고른 품목 합계지만 고칠 수 있다 — 왕복 배송비 공제 같은 판단은 사람 몫 --}}
<div id="cart_rt_modal" style="display:none; position:fixed; inset:0; z-index:1000; background:rgba(0,0,0,.45)">
    <div style="max-width:460px; margin:10vh auto 0; background:#fff; border-radius:8px; padding:24px">
        <h3 style="margin:0 0 6px">반품 처리</h3>
        <p style="margin:0 0 14px; color:#666; font-size:0.95em">
            승인하면 {{ $is_bank ? '환불 기록이 남습니다(계좌 송금은 직접 하셔야 합니다)' : '전자결제가 그 금액만큼 부분취소됩니다' }}.
            되돌릴 수 없습니다.
        </p>
        <form method="post" action="{{ $update_url }}">
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="od_id" value="{{ $order['od_id'] }}">
        <input type="hidden" name="rt_id" id="cart_rt_id" value="">
        {{-- 갈래는 버튼이 아니라 select 로 뽑는다 — 승인·거절 버튼이 나란히 있으면 입력칸에서
             Enter 를 쳤을 때 늘 앞 버튼이 눌린다. 돈이 나가는 화면이라 더더욱 --}}
        <p style="margin:0 0 10px">
            <label>처리<br>
            <select name="mode" id="cart_rt_mode" class="frm_input" style="width:100%">
                <option value="return_approve">승인 — 환불하고 반품 처리</option>
                <option value="return_reject">거절 — 되돌려 보내고 원래대로</option>
            </select></label>
        </p>
        <p style="margin:0 0 10px" id="cart_rt_money">
            <label>환불 금액<br>
            <input type="text" name="rt_refund" id="cart_rt_refund" class="frm_input" style="width:100%"
                   autocomplete="off"></label>
            <span class="txt_id">남은 결제 금액 {{ number_format($refundable) }}원까지. 배송비는 환불하지 않는 것이 기본입니다.</span>
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
@endif

@if (count($actions) || $can_cancel)
<div class="btn_confirm01 btn_confirm" style="text-align:left">

    @if (count($actions))
    <form method="post" action="{{ $update_url }}" style="display:inline">
    <input type="hidden" name="token" value="{{ $token }}">
    <input type="hidden" name="od_id" value="{{ $order['od_id'] }}">
    <input type="hidden" name="mode" value="transition">

    @foreach ($actions as $key => $label)
    <button type="submit" name="action" value="{{ $key }}" class="btn_submit btn">{{ $label }}</button>
    @endforeach

    </form>
    @endif

    @if ($can_cancel)
    <button type="button" class="btn btn_02" id="cart_cancel_open">주문 취소{{ $pg_paid ? ' (자동 환불)' : '' }}</button>
    @endif

</div>
@else
<div class="local_desc02 local_desc"><p>이 상태에서 할 수 있는 처리가 없습니다.</p></div>
@endif

@if ($can_cancel)
{{-- 취소 모달 — 사유와 관리자 비밀번호를 받아야 제출된다. 기능용 최소 스타일만 인라인으로 --}}
<div id="cart_cancel_modal" style="display:none; position:fixed; inset:0; z-index:1000; background:rgba(0,0,0,.45)">
    <div style="max-width:420px; margin:12vh auto 0; background:#fff; border-radius:8px; padding:24px">
        <h3 style="margin:0 0 6px">주문 취소</h3>
        <p style="margin:0 0 14px; color:#666; font-size:0.95em">
            재고가 복원되고{{ $pg_paid ? ', 전자결제 승인이 자동으로 환불됩니다' : ' 취소 상태로 바뀝니다' }}. 되돌릴 수 없습니다.
        </p>
        <form method="post" action="{{ $update_url }}">
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="od_id" value="{{ $order['od_id'] }}">
        <input type="hidden" name="mode" value="cancel">
        <p style="margin:0 0 10px">
            <label>취소 사유<br>
            <select id="cart_cancel_preset" style="width:100%; margin-bottom:6px">
                <option value="">사유 선택 (또는 직접 입력)</option>
                <option>고객 요청 단순 변심</option>
                <option>상품 품절·재고 없음</option>
                <option>배송 지연</option>
                <option>중복 주문</option>
                <option>결제 오류·금액 상이</option>
                <option>배송지 오류로 배송 불가</option>
            </select>
            <input type="text" name="cancel_reason" class="frm_input" style="width:100%" maxlength="255" value="" autocomplete="off" placeholder="사유를 선택하거나 직접 입력하세요"></label>
        </p>
        <p style="margin:0 0 16px">
            <label>관리자 비밀번호 확인<br>
            {{-- new-password: current-password 로 두면 브라우저가 (아이디+비밀번호) 쌍으로 오인해
                 위 사유칸에 계정명을 자동완성해 버린다 --}}
            <input type="password" name="admin_pw" class="frm_input" style="width:100%" autocomplete="new-password"></label>
        </p>
        <div style="text-align:right">
            <button type="button" class="btn btn_02" id="cart_cancel_close">닫기</button>
            <button type="submit" class="btn_submit btn">취소 확정</button>
        </div>
        </form>
    </div>
</div>
@endif

<h2 class="h2_frm">결제 이력</h2>
<div class="tbl_head01 tbl_wrap">
    <table>
    <thead>
    <tr>
        <th scope="col">시각</th><th scope="col">수단</th><th scope="col">단계</th>
        <th scope="col">금액</th><th scope="col">TID</th><th scope="col">내용</th>
    </tr>
    </thead>
    <tbody>

    @foreach ($payments as $p)
    <tr class="bg{{ $loop->index % 2 }}{{ $p['alarm'] ? 'cancel' : '' }}">
        <td class="td_datetime">{{ substr($p['pm_datetime'], 5, 11) }}</td>
        <td>{{ $p['pm_method'] }}</td>
        <td>{{ $p['pm_status'] }}{{ $p['alarm'] ? ' ⚠ 취소 미확인('.$p['sent'].')' : '' }}</td>
        <td class="td_num">{{ number_format($p['pm_amount']) }}원</td>
        <td>{{ $p['pm_tid'] }}</td>
        <td class="td_left" style="word-break:break-all">{{ $p['data_short'] }}</td>
    </tr>
    @endforeach

    @if (!count($payments))
    <tr><td colspan="6" class="empty_table">결제 이력이 없습니다.</td></tr>
    @endif

    </tbody>
    </table>
</div>

<script>
// 취소 모달 — 열고 닫기, 제출 전 사유·비밀번호 화면 검증
$(function () {
    var $modal = $('#cart_cancel_modal');
    $('#cart_cancel_open').on('click', function () {
        // 자동완성이 미리 채워 놨어도 항상 빈 칸으로 시작한다
        $modal.find('input[name="cancel_reason"], input[name="admin_pw"]').val('');
        $modal.find('#cart_cancel_preset').val('');
        $modal.show().find('input[name="cancel_reason"]').trigger('focus');
    });
    // 닫기 버튼으로만 닫는다 — 배경 클릭에 닫히면 쓰던 사유·비밀번호가 실수로 날아간다
    $('#cart_cancel_close').on('click', function () { $modal.hide(); });
    // 사유 사례 선택 → 입력칸에 채운다(이후 자유 수정 가능). 기본 항목이면 건드리지 않는다.
    $('#cart_cancel_preset').on('change', function () {
        var v = $(this).val();
        if (v !== '') $modal.find('input[name="cancel_reason"]').val(v).trigger('focus');
    });
    $modal.find('form').on('submit', function () {
        var $f = $(this);
        if ($.trim($f.find('input[name="cancel_reason"]').val()) === '') {
            alert('취소 사유를 입력하세요.');
            $f.find('input[name="cancel_reason"]').trigger('focus');
            return false;
        }
        if ($f.find('input[name="admin_pw"]').val() === '') {
            alert('관리자 비밀번호를 입력하세요.');
            $f.find('input[name="admin_pw"]').trigger('focus');
            return false;
        }
        return true;
    });
});
</script>

@if (count($returns))
<script>
// 반품 모달 — 승인·거절을 select 로 가른다. 고른 갈래에 따라 필요한 칸만 남기고
// 버튼 글자도 바꾼다: 무엇이 일어날지 버튼에 적혀 있어야 잘못 누르지 않는다.
$(function () {
    var $modal = $('#cart_rt_modal'), $mode = $('#cart_rt_mode');

    function paint() {
        var approve = ($mode.val() === 'return_approve');
        $('#cart_rt_money, #cart_rt_stock').toggle(approve);
        $('#cart_rt_submit').text(approve ? '승인 · 환불' : '거절').toggleClass('btn_submit', approve);
    }

    $('.cart-rt-open').on('click', function () {
        $('#cart_rt_id').val($(this).data('rt'));
        // 환불 기본값은 고른 품목 합계 — 제안일 뿐이고 최종 금액은 사람이 정한다
        $('#cart_rt_refund').val($(this).data('sum'));
        $modal.find('input[name="rt_memo"], input[name="admin_pw"]').val('');
        $mode.val('return_approve');
        paint();
        $modal.show();
    });
    $mode.on('change', paint);
    // 닫기 버튼으로만 닫는다 — 배경 클릭에 닫히면 쓰던 값이 실수로 날아간다(취소 모달과 같은 규칙)
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
@endif

<style>
/* 주문 상품 → 상품 수정 바로가기. 표 안에서 눌러도 되는 자리임을 색과 밑줄로 알린다.
   순정 admin.css 의 `a:link`(특이도 0,1,1)가 링크 색·밑줄을 지우므로 여기서 더 좁게 잡는다 */
a.oi-link:link, a.oi-link:visited {
    color: #1D5FD1; text-decoration: underline; text-underline-offset: 2px;
}
a.oi-link:hover { color: #0f3f96; }
/* 상품이 지워진 주문 — 링크가 없는 이유를 그 자리에서 알려 준다 */
.oi-gone { margin-left: 6px; padding: 1px 6px; border-radius: 5px; background: #f2f4f7; color: #98a2b3; font-size: 11px; }
</style>
