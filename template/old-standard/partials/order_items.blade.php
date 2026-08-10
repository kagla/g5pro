{{-- 주문 상품과 금액 — 주문완료·주문 상세가 함께 쓴다(order_bank 와 같은 이유).
     받는 값: $order · $items (cart_order_items_for_view 가 href·img 를 얹어 준 배열)

     장바구니와 같은 말투다 — 사진 · 이름 · 옵션 · 금액. 사진과 이름은 그 상품으로 가는 문이고,
     지금 열 수 없는 상품(지워짐·노출 중지)은 문 없이 글자만 남는다(서버가 정한다). --}}
<section class="cart-co-sec">
    <h3>주문 상품</h3>

    <ul class="cart-oi">

        @foreach ($items as $it)
        <li class="cart-oi-row">

            @if ($it['href'] !== '')
            <a href="{{ $it['href'] }}" class="cart-oi-thumb">

                @if ($it['img'] !== '')
                <img src="{{ $it['img'] }}" alt="">
                @endif

            </a>
            @else
            <span class="cart-oi-thumb">

                @if ($it['img'] !== '')
                <img src="{{ $it['img'] }}" alt="">
                @endif

            </span>
            @endif

            <div class="cart-oi-info">
                <span class="cart-oi-name">

                    @if ($it['href'] !== '')
                    <a href="{{ $it['href'] }}">{{ $it['oi_name'] }}</a>
                    @else
                    {{ $it['oi_name'] }}
                    @endif

                    @if (cart_return_item_label($it['oi_status']) !== '')
                    <b class="cart-oi-flag">{{ cart_return_item_label($it['oi_status']) }}</b>
                    @endif

                </span>
                <span class="cart-oi-opt">{{ $it['oi_option'] !== '' ? $it['oi_option'].' · ' : '' }}{{ $it['oi_qty'] }}개</span>
            </div>
            <span class="cart-oi-price">{{ number_format($it['oi_total']) }}원</span>
        </li>
        @endforeach

    </ul>

    {{-- 권역 추가비가 붙었으면 근거를 그 줄에 적는다 — "왜 이 배송비냐" 는 문의가 잦은 자리다 --}}
    <div class="cart-complete-line is-sub">
        <span>배송비{{ (int)$order['od_ship_extra'] > 0 && $order['od_ship_zone'] !== '' ? ' ('.$order['od_ship_zone'].' 추가 '.number_format($order['od_ship_extra']).'원 포함)' : '' }}</span>
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
