@extends('layout.default')
@section('content')
<div class="cart-complete">
    <header class="cart-complete-head">
        <h2>주문 상세</h2>
        <p>주문번호 <strong>{{ $order['od_no'] }}</strong> · <span class="cart-status is-{{ cart_order_status_tone($order['od_status']) }}">{{ $status_label }}</span> · {{ substr($order['od_datetime'], 0, 10) }}</p>
    </header>

    @if ($order['od_status'] === 'canceled')
    <section class="cart-co-sec cart-complete-bank">
        <h3>취소된 주문입니다</h3>
        <p>관리자 직권으로 취소되었습니다{{ substr($order['od_canceled_at'], 0, 4) !== '1970' ? ' ('.substr($order['od_canceled_at'], 0, 16).')' : '' }}.</p>

        @if ($order['od_cancel_reason'] !== '')
        <p class="cart-co-note">취소 사유: {{ $order['od_cancel_reason'] }}</p>
        @endif

        @if ($order['od_pay_method'] !== 'bank' && substr($order['od_paid_at'], 0, 4) !== '1970')
        <p class="cart-co-note">결제하신 금액은 카드 승인 취소로 환불되었습니다. 카드사에 따라 며칠 걸릴 수 있습니다.</p>
        @endif

    </section>
    @endif

    @if ($pay_href !== '')
    <section class="cart-co-sec cart-complete-bank">
        <h3>결제 대기 중</h3>
        <p class="cart-co-note">아직 결제가 완료되지 않았습니다.</p>
        <p><a href="{{ $pay_href }}" class="cart-cta">결제하기</a></p>
    </section>
    @endif

    @if ($order['od_pay_method'] === 'bank' && $order['od_status'] === 'unpaid')
    <section class="cart-co-sec cart-complete-bank">
        <h3>입금 안내</h3>
        <p class="cart-complete-amount">입금하실 금액 <strong>{{ number_format($order['od_total']) }}원</strong></p>

        @if ($bank !== '')
        <p>{{ $bank }}</p>
        @endif

        <p class="cart-co-note">입금자명: {{ $order['od_depositor'] }}</p>
    </section>
    @endif

    {{-- 배송 정보 — 발송한 뒤에만. 관리자가 송장을 넣어도 고객이 볼 곳이 없으면
         "보냈다는데 어디쯤인가" 를 전화로 물어야 한다. 택배사를 알아본 경우에만 조회 링크를
         걸고, 못 알아보면 번호만 보여 준다(엉뚱한 곳으로 가는 링크는 없느니만 못하다) --}}
    @if (in_array($order['od_status'], array('shipping', 'delivered', 'confirmed'), true) && $order['od_dc_name'] !== '')
    <section class="cart-co-sec">
        <h3>배송 조회</h3>
        <div class="cart-complete-line">
            <span>{{ $order['od_dc_name'] }}</span>
            <span>

                @if ($order['od_invoice'] !== '')
                    @if ($track_url !== '')
                    <a href="{{ $track_url }}" target="_blank" rel="noopener">{{ $order['od_invoice'] }}</a>
                    @else
                    {{ $order['od_invoice'] }}
                    @endif
                @elseif ($order['od_delivery_note'] !== '')
                {{ $order['od_delivery_note'] }}
                @endif

            </span>
        </div>

        @if (substr($order['od_shipped_at'], 0, 4) !== '1970')
        <p class="cart-co-note">{{ substr($order['od_shipped_at'], 0, 16) }} 발송</p>
        @endif

    </section>
    @endif

    {{-- 구매확정 — 배송완료 주문의 매듭. 되돌릴 수 없으므로 누르기 전에 한 번 묻는다.
         나중에 포인트 적립·반품 마감의 기준이 되는 시각이라 고객이 직접 찍게 한다 --}}
    @if ($can_confirm)
    <section class="cart-co-sec">
        <h3>잘 받으셨나요?</h3>
        <p class="cart-co-note">구매확정을 누르시면 주문이 마무리됩니다. 확정한 뒤에는 되돌릴 수 없습니다.</p>
        <form method="post" action="{{ $action_url }}" data-confirm="구매확정하시겠습니까?&#10;확정한 뒤에는 되돌릴 수 없습니다.">
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="mode" value="confirm">
            <input type="hidden" name="od_no" value="{{ $order['od_no'] }}">
            <button type="submit" class="cart-cta">구매확정</button>
        </form>
    </section>
    @endif

    @if ($order['od_status'] === 'confirmed' && substr($order['od_confirmed_at'], 0, 4) !== '1970')
    <p class="cart-co-note" style="text-align:center">{{ substr($order['od_confirmed_at'], 0, 16) }} 구매확정</p>
    @endif

    <section class="cart-co-sec">
        <h3>주문 상품</h3>

        @foreach ($items as $it)
        <div class="cart-complete-line">
            <span>{{ $it['oi_name'] }}{{ $it['oi_option'] !== '' ? ' ('.$it['oi_option'].')' : '' }} × {{ $it['oi_qty'] }}
                @if (cart_return_item_label($it['oi_status']) !== '')
                <b class="cart-oi-flag">{{ cart_return_item_label($it['oi_status']) }}</b>
                @endif
            </span>
            <span>{{ number_format($it['oi_total']) }}원</span>
        </div>
        @endforeach

        <div class="cart-complete-line is-sub">
            <span>배송비</span>
            <span>{{ number_format($order['od_ship_fee']) }}원</span>
        </div>
        @if ((int)$order['od_coupon'] > 0)
        <div class="cart-complete-line is-sub">
            <span>쿠폰 할인</span>
            <span>-{{ number_format($order['od_coupon']) }}원</span>
        </div>
        @endif
        <div class="cart-complete-line is-total">
            <span>결제 금액</span>
            <span>{{ number_format($order['od_total']) }}원</span>
        </div>

        @if ((int)$order['od_refund'] > 0)
        <div class="cart-complete-line is-sub">
            <span>환불된 금액</span>
            <span>-{{ number_format($order['od_refund']) }}원</span>
        </div>
        @endif

    </section>

    {{-- 반품 이력 — 접수·완료·거절을 모두 보여 준다. 거절도 남긴다: 왜 안 됐는지 모르면
         손님은 같은 신청을 다시 하거나 전화를 건다 --}}
    @if (count($returns))
    <section class="cart-co-sec">
        <h3>반품 내역</h3>

        @foreach ($returns as $rt)
        <div class="cart-return-row">
            <p class="cart-return-head">
                <b>{{ cart_return_status_label($rt['rt_status']) }}</b>
                <span>{{ substr($rt['rt_requested_at'], 0, 16) }} 신청</span>

                @if ((int)$rt['rt_refund'] > 0)
                <span>{{ number_format($rt['rt_refund']) }}원 환불</span>
                @endif

            </p>

            @foreach ($rt['item_names'] as $nm)
            <p class="cart-co-note">{{ $nm }}</p>
            @endforeach

            <p class="cart-co-note">사유: {{ $rt['rt_reason'] }}</p>

            @if ($rt['rt_memo'] !== '')
            <p class="cart-co-note">판매자: {{ $rt['rt_memo'] }}</p>
            @endif

        </div>
        @endforeach

    </section>
    @endif

    {{-- 반품 신청 — 반품할 품목을 골라서 낸다. 수량 일부만 고르는 칸은 두지 않았다:
         한 줄은 통째로 반품한다(그래야 남은 수량을 모든 화면이 다시 세지 않아도 된다) --}}
    @if (count($return_items) && $return_why_not === '')
    <section class="cart-co-sec">
        <h3>반품 신청</h3>
        <p class="cart-co-note">받으신 날부터 {{ $return_days }}일 안에 신청할 수 있습니다. 판매자 확인 후 처리됩니다.</p>
        <form method="post" action="{{ $action_url }}" data-confirm="반품을 신청하시겠습니까?&#10;판매자 확인 후 처리됩니다.">
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="mode" value="return">
            <input type="hidden" name="od_no" value="{{ $order['od_no'] }}">

            <ul class="cart-return-pick">

                @foreach ($return_items as $it)
                <li>
                    <label>
                        <input type="checkbox" name="oi_id[]" value="{{ $it['oi_id'] }}">
                        <span>{{ $it['oi_name'] }}{{ $it['oi_option'] !== '' ? ' ('.$it['oi_option'].')' : '' }} × {{ $it['oi_qty'] }}</span>
                        <em>{{ number_format($it['oi_total']) }}원</em>
                    </label>
                </li>
                @endforeach

            </ul>

            {{-- 사유는 골라서 넣고, 넣은 뒤에도 고칠 수 있다 — 빈 칸 앞에서 무슨 말을 적어야
                 할지 고민하지 않게 하되, 사정이 다른 사람은 직접 쓸 수 있어야 한다 --}}
            <label class="cart-return-field">사유
                <select id="cart_return_preset">
                    <option value="">고르거나 직접 입력</option>

                    @foreach (cart_return_reasons() as $r)
                    <option>{{ $r }}</option>
                    @endforeach

                </select>
            </label>
            <label class="cart-return-field"><span class="sound_only">반품 사유</span>
                <input type="text" name="return_reason" id="cart_return_reason" maxlength="100" required
                       placeholder="반품 사유를 적어 주세요">
            </label>

            @if ($is_bank)
            {{-- 무통장은 되돌려 보낼 곳이 없어 계좌를 받아야 한다. 카드는 원 결제수단으로
                 자동 환불되므로 묻지 않는다. 받은 계좌는 환불을 마치면 지운다 --}}
            <label class="cart-return-field">환불 계좌
                <input type="text" name="return_bank" maxlength="50" required
                       placeholder="예) 국민 123456-01-234567 홍길동">
            </label>
            @endif

            <button type="submit" class="cart-cta is-line">반품 신청</button>
        </form>
    </section>

    <script>
    // 사유 고르기 → 입력칸을 채운다. 채운 뒤에는 손님이 자유롭게 고칠 수 있다(덮어쓰지 않는다).
    $('#cart_return_preset').on('change', function () {
        var v = $(this).val();
        if (v !== '') $('#cart_return_reason').val(v).trigger('focus');
    });
    </script>

    @elseif ($return_why_not !== '' && in_array($order['od_status'], array('delivered', 'confirmed', 'returned'), true))
    <p class="cart-co-note" style="text-align:center">{{ $return_why_not }}</p>
    @endif

    <section class="cart-co-sec">
        <h3>배송지</h3>
        <p>받는분 {{ $order['od_recv_name'] !== '' ? $order['od_recv_name'] : $order['od_name'] }} · {{ $order['od_recv_hp'] !== '' ? $order['od_recv_hp'] : $order['od_hp'] }}</p>
        <p>({{ $order['od_zip'] }}) {{ $order['od_addr1'] }} {{ $order['od_addr2'] }}</p>

        @if ($order['od_memo'] !== '')
        <p class="cart-co-note">요청사항: {{ $order['od_memo'] }}</p>
        @endif

    </section>

    <p style="text-align:center"><a href="{{ $list_href }}" class="cart-cta is-line">{{ $is_member ? '주문 내역으로' : '스토어로' }}</a></p>
</div>
@endsection
