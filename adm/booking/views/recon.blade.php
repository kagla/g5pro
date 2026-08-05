<div class="local_desc01 local_desc">
    <p>카드 결제와 예약이 서로 맞는지 점검합니다. <strong>손님 카드에서 돈은 빠져나갔는데 예약이 잡히지 않은 건</strong>처럼 어긋난 것만 골라 보여 줍니다. 하루 한 번은 열어 보십시오 — 아무것도 없으면 정상입니다.</p>
    <p>망취소·환불 기록이 남은 건은 이미 되돌리려 손을 댄 것으로 보고 제외합니다.</p>
</div>

<style>
.brc_ok { border:1px solid #e4e4e4; border-radius:3px; padding:20px; text-align:center; color:#555 }
.brc_right { text-align:right }
.brc_sub { color:#777; font-size:0.92em }
.brc_warn { color:#e8180c }
.brc_act form { display:inline }
.brc_act input.btn_submit { margin-right:4px }
.brc_mono { font-family:monospace; font-size:0.92em; word-break:break-all }
</style>

<h2>A. 승인은 성공했는데 확정되지 않은 결제</h2>

<div class="local_desc01 local_desc">
    <p><strong>확정</strong>은 그때 못한 예약 확정을 지금 합니다(자리가 남아 있어야 하며, 고객에게 확정 안내 메일이 나갑니다). <strong>환불</strong>은 승인 금액 전액을 돌려주고 예약을 취소합니다.</p>
</div>

<div class="tbl_head01 tbl_wrap brc_act">
    <table>
        <caption>미확정 승인</caption>
        <thead><tr>
            <th scope="col">승인일시</th><th scope="col">주문번호 / 거래번호</th><th scope="col">예약</th>
            <th scope="col">승인금액</th><th scope="col">상태</th><th scope="col">조치</th>
        </tr></thead>
        <tbody>
        @foreach ($unmatched as $u)
        <tr>
            <td>{{ $u['bl_datetime'] }}</td>
            <td class="brc_mono">{{ $u['bl_oid'] }}<div class="brc_sub brc_mono">{{ $u['bl_tid'] == '' ? '거래번호 없음' : $u['bl_tid'] }}</div></td>
            <td>
                @if ($u['bk_id'])
                <a href="./booking_view.php?bk_id={{ $u['bk_id'] }}">{{ $u['bk_no'] }}</a>
                <div class="brc_sub">{{ $u['bk_name'] }} · {{ $u['bk_hp'] }}</div>
                <div class="brc_sub">{{ $u['br_subject'] }} {{ $u['stay'] }}</div>
                @else
                <span class="brc_warn">예약 없음</span>
                @endif
            </td>
            <td class="brc_right">{{ number_format($u['bl_price']) }}원
                @if ($u['bk_id'] && $u['bl_price'] != $u['bk_total_price'])
                <div class="brc_sub brc_warn">청구액 {{ number_format($u['bk_total_price']) }}원</div>
                @endif
            </td>
            <td>{{ $u['status_text'] }}</td>
            <td>
                @if ($u['blocked'] == '')
                <form name="frecon_c{{ $u['bl_id'] }}" method="post" action="./booking_update.php" onsubmit="return confirm('예약 {{ $u['bk_no'] }} 을(를) 확정합니다.\n고객에게 확정 안내 메일이 나갑니다. 진행할까요?');">
                <input type="hidden" name="token" value="">
                <input type="hidden" name="act" value="recon_confirm">
                <input type="hidden" name="bk_id" value="{{ $u['bk_id'] }}">
                <input type="hidden" name="bl_id" value="{{ $u['bl_id'] }}">
                <input type="submit" value="확정" class="btn_submit btn">
                </form>
                <form name="frecon_r{{ $u['bl_id'] }}" method="post" action="./booking_update.php" onsubmit="return confirm('{{ number_format($u['bl_price']) }}원을 전액 환불하고 예약을 취소합니다.\n환불은 되돌릴 수 없습니다. 진행할까요?');">
                <input type="hidden" name="token" value="">
                <input type="hidden" name="act" value="recon_refund">
                <input type="hidden" name="bk_id" value="{{ $u['bk_id'] }}">
                <input type="hidden" name="bl_id" value="{{ $u['bl_id'] }}">
                <input type="submit" value="환불" class="btn_submit btn">
                </form>
                @else
                <span class="brc_warn">{{ $u['blocked'] }}</span>
                <div class="brc_sub">이니시스 상점관리자에서 직접 처리하십시오.</div>
                @endif
            </td>
        </tr>
        @endforeach

        @if (count($unmatched) == 0)
        <tr><td colspan="6" class="empty_table">어긋난 결제가 없습니다. 정상입니다.</td></tr>
        @endif
        </tbody>
    </table>
</div>

<h2>B. 확정된 예약인데 거래번호가 없는 건</h2>

<div class="local_desc01 local_desc">
    <p>이 예약은 <strong>화면에서 환불할 수 없습니다</strong>(거래번호가 없어 취소 전문을 보낼 수 없습니다). 이니시스 상점관리자에서 주문번호로 거래를 찾아 맞춰 주십시오.</p>
</div>

<div class="tbl_head01 tbl_wrap">
    <table>
        <caption>거래번호 없는 확정 예약</caption>
        <thead><tr>
            <th scope="col">예약번호</th><th scope="col">주문번호</th><th scope="col">예약자</th>
            <th scope="col">객실 / 일정</th><th scope="col">결제일시</th><th scope="col">금액</th>
        </tr></thead>
        <tbody>
        @foreach ($notid as $b)
        <tr>
            <td><a href="./booking_view.php?bk_id={{ $b['bk_id'] }}">{{ $b['bk_no'] }}</a></td>
            <td class="brc_mono">{{ $b['bk_oid'] == '' ? '-' : $b['bk_oid'] }}</td>
            <td>{{ $b['bk_name'] }}<div class="brc_sub">{{ $b['bk_hp'] }}</div></td>
            <td>{{ $b['br_subject'] }}<div class="brc_sub">{{ $b['stay'] }}</div></td>
            <td>{{ $b['pay_time'] == '' ? '-' : $b['pay_time'] }}</td>
            <td class="brc_right">{{ number_format($b['bk_total_price']) }}원</td>
        </tr>
        @endforeach

        @if (count($notid) == 0)
        <tr><td colspan="6" class="empty_table">어긋난 결제가 없습니다. 정상입니다.</td></tr>
        @endif
        </tbody>
    </table>
</div>
