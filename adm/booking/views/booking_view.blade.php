<div class="local_desc01 local_desc">
    <p>예약 한 건의 모든 내용과 결제 기록입니다. 취소·환불은 이 화면에서만 처리합니다.</p>
</div>

<style>
.bkv_top { margin-bottom:10px }
.bkv_st { display:inline-block; padding:2px 8px; border-radius:3px; background:#eee; color:#555; font-weight:bold }
.bkv_st_confirmed { background:#e6f0fd; color:#0b57d0 }
.bkv_st_cancel_req { background:#fff3e0; color:#b35c00 }
.bkv_st_cancelled { background:#f2f2f2; color:#888 }
.bkv_st_hold { background:#f7f7f7; color:#777 }
.bkv_pre { white-space:pre-line }
.bkv_right { text-align:right }
.bkv_sum { font-weight:bold }
.bkv_note { border:1px solid #e4e4e4; border-radius:3px; padding:8px 10px; margin-bottom:6px }
.bkv_note_guest { background:#fbfbfb }
.bkv_note_admin { background:#f4f8ff }
.bkv_note_head { color:#777; font-size:0.92em; margin-bottom:4px }
.bkv_new { display:inline-block; padding:1px 5px; border-radius:9px; background:#e8180c; color:#fff; font-size:0.85em; font-weight:bold }
.bkv_warn { color:#e8180c }
.bkv_act { border:1px solid #e4e4e4; border-radius:3px; padding:12px; margin-bottom:10px }
.bkv_act h3 { margin:0 0 8px }
.bkv_act p { margin:0 0 8px; color:#555 }
</style>

<div class="btn_fixed_top bkv_top">
    <a href="./booking_list.php" class="btn btn_02">목록</a>
</div>

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption>예약 정보</caption>
        <colgroup><col class="grid_4"><col></colgroup>
        <tbody>
        <tr>
            <th scope="row">예약번호</th>
            <td>{{ $bk['bk_no'] }}
                <span class="bkv_st bkv_st_{{ $bk['bk_status'] }}">{{ $status_text }}</span>
            </td>
        </tr>
        <tr>
            <th scope="row">객실</th>
            <td>{{ $br_subject == '' ? '(삭제된 객실)' : $br_subject }}</td>
        </tr>
        <tr>
            <th scope="row">일정</th>
            <td>{{ $bk['bk_checkin'] }} ~ {{ $bk['bk_checkout'] }} ({{ $nights }}박) · {{ $bk['bk_person'] }}명</td>
        </tr>
        <tr>
            <th scope="row">예약자</th>
            <td>{{ $bk['bk_name'] }} · {{ $bk['bk_hp'] }}
                {{ $bk['bk_email'] == '' ? '' : ' · '.$bk['bk_email'] }}
                {{ $bk['mb_id'] == '' ? ' · 비회원' : ' · 회원('.$bk['mb_id'].')' }}
            </td>
        </tr>
        <tr>
            <th scope="row">요청사항</th>
            <td class="bkv_pre">{{ $bk['bk_request'] == '' ? '-' : $bk['bk_request'] }}</td>
        </tr>
        <tr>
            <th scope="row">예약일시</th>
            <td>{{ $bk['bk_datetime'] }} · IP {{ $bk['bk_ip'] }}</td>
        </tr>
        @if ($bk['bk_status'] == 'hold')
        <tr>
            <th scope="row">결제 유효시간</th>
            <td>{{ $hold_expire == '' ? '-' : $hold_expire }} 까지</td>
        </tr>
        @endif
        </tbody>
    </table>
</div>

<div class="tbl_head01 tbl_wrap">
    <table>
        <caption>금액</caption>
        <thead><tr>
            <th scope="col">항목</th><th scope="col">단가</th><th scope="col">수량</th><th scope="col">금액</th>
        </tr></thead>
        <tbody>
        <tr>
            <td>객실료 ({{ $nights }}박)</td><td class="bkv_right">-</td><td class="bkv_right">-</td>
            <td class="bkv_right">{{ number_format($bk['bk_room_price']) }}원</td>
        </tr>
        @if ($bk['bk_person_price'] > 0)
        <tr>
            <td>인원 추가</td><td class="bkv_right">-</td><td class="bkv_right">-</td>
            <td class="bkv_right">{{ number_format($bk['bk_person_price']) }}원</td>
        </tr>
        @endif
        @foreach ($addon_items as $it)
        <tr>
            <td>{{ $it['bt_subject'] }}</td>
            <td class="bkv_right">{{ number_format($it['bt_price']) }}원{{ $it['bt_unit'] == 'night' ? ' /1박' : '' }}</td>
            <td class="bkv_right">{{ $it['bt_qty'] }}{{ $it['bt_unit'] == 'night' ? ' × '.$nights.'박' : '' }}</td>
            <td class="bkv_right">{{ number_format($it['bt_amount']) }}원</td>
        </tr>
        @endforeach
        <tr class="bkv_sum">
            <td>합계</td><td class="bkv_right">-</td><td class="bkv_right">-</td>
            <td class="bkv_right">{{ number_format($bk['bk_total_price']) }}원</td>
        </tr>
        </tbody>
    </table>
</div>

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption>결제 정보</caption>
        <colgroup><col class="grid_4"><col></colgroup>
        <tbody>
        <tr>
            <th scope="row">주문번호(OID)</th>
            <td>{{ $bk['bk_oid'] == '' ? '-' : $bk['bk_oid'] }}</td>
        </tr>
        <tr>
            <th scope="row">거래번호(TID)</th>
            <td>
                {{ $bk['bk_tid'] == '' ? '-' : $bk['bk_tid'] }}
                @if ($bk['bk_tid'] == '' && $bk['bk_status'] == 'confirmed')
                <span class="bkv_warn">확정된 예약인데 거래번호가 없습니다. 결제 점검 화면에서 확인하십시오.</span>
                @endif
            </td>
        </tr>
        <tr>
            <th scope="row">결제일시</th>
            <td>{{ $pay_time == '' ? '-' : $pay_time }}</td>
        </tr>
        <tr>
            <th scope="row">취소일시</th>
            <td>{{ $cancel_time == '' ? '-' : $cancel_time }}
                {{ $bk['bk_cancel_memo'] == '' ? '' : ' · '.$bk['bk_cancel_memo'] }}</td>
        </tr>
        <tr>
            <th scope="row">환불</th>
            <td>
                @if ($refund_time == '')
                {{ $bk['bk_refund_plan_price'] > 0 ? '예정액 '.number_format($bk['bk_refund_plan_price']).'원 (아직 환불 전)' : '-' }}
                @else
                {{ number_format($bk['bk_refund_price']) }}원 · {{ $refund_time }}
                @endif
            </td>
        </tr>
        </tbody>
    </table>
</div>

<div class="tbl_head01 tbl_wrap">
    <table>
        <caption>환불·망취소 기록</caption>
        <thead><tr>
            <th scope="col">일시</th><th scope="col">구분</th><th scope="col">금액</th>
            <th scope="col">결과</th><th scope="col">거래번호</th>
        </tr></thead>
        <tbody>
        @foreach ($refund_logs as $lg)
        <tr>
            <td>{{ $lg['bl_datetime'] }}</td>
            <td>{{ $lg['type_text'] }}</td>
            <td class="bkv_right">{{ number_format($lg['bl_price']) }}원</td>
            <td>{{ $lg['ok'] ? '성공' : $lg['bl_result_code'] }}</td>
            <td>{{ $lg['bl_tid'] == '' ? '-' : $lg['bl_tid'] }}</td>
        </tr>
        @endforeach

        @if (count($refund_logs) == 0)
        <tr><td colspan="5" class="empty_table">환불·망취소 기록이 없습니다.</td></tr>
        @endif
        </tbody>
    </table>
</div>

@if ($can_approve || $can_force)
<h2>취소 처리</h2>

@if ($can_approve)
<div class="bkv_act">
    <h3>취소 승인</h3>
    <p>손님이 신청한 취소를 그대로 받아들입니다. 환불액은 <strong>신청 시점의 정책으로 굳은 금액</strong>이며 지금 다시 계산하지 않습니다.</p>
    <form name="fbookingapprove" method="post" action="./booking_update.php" onsubmit="return confirm('취소를 승인하고 {{ number_format($bk['bk_refund_plan_price']) }}원을 환불합니다.\n환불은 되돌릴 수 없습니다. 진행할까요?');">
    <input type="hidden" name="token" value="">
    <input type="hidden" name="act" value="cancel_approve">
    <input type="hidden" name="bk_id" value="{{ $bk['bk_id'] }}">
    <input type="submit" value="취소 승인(환불 {{ number_format($bk['bk_refund_plan_price']) }}원)" class="btn_submit btn">
    </form>
</div>
@endif

@if ($can_force)
<div class="bkv_act">
    <h3>직권 취소</h3>
    <p>정책 밖의 결정(업주 사정·협의 환불 등)입니다. 돌려줄 금액을 직접 적습니다. 0 을 적으면 환불 없이 취소만 합니다.</p>
    <form name="fbookingforce" method="post" action="./booking_update.php" onsubmit="return bkv_force_confirm(this);">
    <input type="hidden" name="token" value="">
    <input type="hidden" name="act" value="force_cancel">
    <input type="hidden" name="bk_id" value="{{ $bk['bk_id'] }}">
    <label for="refund_price">환불 금액</label>
    <input type="number" name="refund_price" value="{{ $bk['bk_total_price'] }}" id="refund_price" class="frm_input" size="10" min="0" max="{{ $bk['bk_total_price'] }}"> 원
    <span class="frm_info">결제 금액 {{ number_format($bk['bk_total_price']) }}원을 넘을 수 없습니다.</span>
    <input type="submit" value="직권 취소" class="btn_submit btn">
    </form>
</div>
@endif
@endif

<h2>요청 · 답변</h2>

<div class="local_desc01 local_desc">
    <p>여기에 남긴 글은 <strong>고객의 예약 조회 화면에 그대로 보입니다.</strong> 내부 메모가 아닙니다.</p>
</div>

@foreach ($notes as $n)
<div class="bkv_note {{ $n['is_guest'] ? 'bkv_note_guest' : 'bkv_note_admin' }}">
    <div class="bkv_note_head">
        {{ $n['writer_text'] }} · {{ $n['bn_datetime'] }}
        @if ($n['is_guest'] && !$n['bn_checked'])
        <span class="bkv_new">미확인</span>
        @endif
    </div>
    <div class="bkv_pre">{{ $n['bn_content'] }}</div>

    @if ($n['is_guest'] && !$n['bn_checked'])
    <form name="fbookingcheck{{ $n['bn_id'] }}" method="post" action="./booking_update.php">
    <input type="hidden" name="token" value="">
    <input type="hidden" name="act" value="note_check">
    <input type="hidden" name="bk_id" value="{{ $bk['bk_id'] }}">
    <input type="hidden" name="bn_id" value="{{ $n['bn_id'] }}">
    <input type="submit" value="확인" class="btn btn_03">
    </form>
    @endif
</div>
@endforeach

@if (count($notes) == 0)
<div class="empty_list">주고받은 글이 없습니다.</div>
@endif

<form name="fbookingnote" method="post" action="./booking_update.php" autocomplete="off">
<input type="hidden" name="token" value="">
<input type="hidden" name="act" value="note_add">
<input type="hidden" name="bk_id" value="{{ $bk['bk_id'] }}">

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption>답변 쓰기</caption>
        <colgroup><col class="grid_4"><col></colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="bn_content">고객에게 보이는 답변</label></th>
            <td><textarea name="bn_content" id="bn_content" rows="4" class="frm_input" style="width:100%" required></textarea>
                <span class="frm_info">고객의 예약 조회 화면에 '업주' 로 그대로 실립니다. 2,000자까지.</span></td>
        </tr>
        </tbody>
    </table>
</div>

<div class="btn_confirm01 btn_confirm">
    <input type="submit" value="답변 등록" class="btn_submit btn">
</div>
</form>

<script>
function bkv_force_confirm(f)
{
    var v = f.refund_price.value.replace(/[, ]/g, "");
    if (!/^\d+$/.test(v)) { alert("환불 금액을 0 이상의 숫자로 입력하세요."); f.refund_price.focus(); return false; }
    return confirm("예약을 직권 취소하고 " + Number(v).toLocaleString() + "원을 환불합니다.\n환불은 되돌릴 수 없습니다. 진행할까요?");
}
</script>
