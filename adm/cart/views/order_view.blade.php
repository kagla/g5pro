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
        <td class="td_left">{{ $it['oi_name'] }}</td>
        <td>{{ $it['oi_option'] }}</td>
        <td class="td_num">{{ number_format($it['oi_price']) }}</td>
        <td class="td_num">{{ number_format($it['oi_qty']) }}</td>
        <td class="td_num">{{ number_format($it['oi_total']) }}</td>
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
        <td><input type="text" name="od_delivery_company" value="{{ $order['od_delivery_company'] }}" class="frm_input" size="16"></td>
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
        <td class="td_num">{{ number_format($p['pm_amount']) }}</td>
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
