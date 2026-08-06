@extends('layout.default')
@section('content')
<header class="bbs-head">
    <h2>주문서 작성</h2>

    @if ($blocked_count > 0)
    <div class="bbs-meta">구매할 수 없는 상품 {{ $blocked_count }}개는 <a href="{{ $basket_href }}">장바구니</a>에 남겨두었습니다.</div>
    @endif

</header>

<div class="cart-checkout">
<form method="post" action="{{ $action_url }}" class="cart-checkout-form">
    <input type="hidden" name="token" value="{{ $token }}">
    <input type="hidden" name="expect_bk_ids" value="{{ $expect_bk_ids }}">
    <input type="hidden" name="expect_item_total" value="{{ $item_total }}">

    <section class="cart-co-sec">
        <h3>주문 상품</h3>

        @foreach ($lines as $l)
        <div class="cart-basket-row">
            <span class="cart-basket-thumb">

                @if ($l['img'])
                <img src="{{ $l['img'] }}" alt="">
                @endif

            </span>
            <div class="cart-basket-info">
                <span class="cart-basket-name">{{ $l['it_name'] }}</span>

                @if ($l['opt_label'] !== '')
                <span class="cart-basket-opt">{{ $l['opt_label'] }}</span>
                @endif

                <span class="cart-basket-opt">{{ number_format($l['sk_price']) }}원 × {{ $l['bk_qty'] }}개</span>
            </div>
            <span class="cart-basket-line">{{ number_format($l['line_total']) }}<em>원</em></span>
        </div>
        @endforeach

    </section>

    <section class="cart-co-sec">
        <h3>주문하시는 분</h3>
        <div class="cart-co-grid">
            <label>이름 <input type="text" name="od_name" value="{{ $default_name }}" required></label>
            <label>연락처 <input type="text" name="od_hp" value="{{ $default_hp }}" placeholder="010-0000-0000" required></label>
            <label>이메일 <input type="email" name="od_email" value="{{ $default_email }}"></label>

            @if (!$is_member)
            <label>주문 비밀번호 <input type="password" name="guest_pw" minlength="4" placeholder="주문 조회에 사용 (4자 이상)" required></label>
            @endif

        </div>

        @if (!$is_member)
        <p class="cart-co-note">비회원 주문입니다 — 주문번호와 비밀번호로 주문을 조회할 수 있습니다.</p>
        @endif

    </section>

    <section class="cart-co-sec">
        <h3>배송지</h3>
        <div class="cart-co-addr">
            <div class="cart-co-zip">
                <input type="text" name="od_zip" id="od_zip" value="" placeholder="우편번호" readonly required>
                <button type="button" class="btn-ghost" onclick="cartSearchZip()">주소 검색</button>
            </div>
            <input type="text" name="od_addr1" id="od_addr1" value="" placeholder="주소" readonly required>
            <input type="text" name="od_addr2" id="od_addr2" value="" placeholder="상세 주소">
            <input type="text" name="od_memo" value="" placeholder="배송 요청사항 (선택)">
        </div>
    </section>

    <section class="cart-co-sec">
        <h3>결제 수단</h3>
        <label class="cart-co-pay"><input type="radio" name="pay" value="bank" checked> 무통장입금</label>
        <label>입금자명 <input type="text" name="od_depositor" value="" placeholder="비우면 주문자 이름"></label>
    </section>

    <aside class="cart-basket-sum">
        <dl>
            <dt>상품 합계</dt>
            <dd>{{ number_format($item_total) }}원</dd>
            <dt>배송비</dt>
            <dd id="cart_ship_fee">계산 중</dd>
            <dt>결제 예정</dt>
            <dd id="cart_pay_total">{{ number_format($item_total) }}원</dd>
        </dl>
        <button type="submit" class="cart-cta">주문하기</button>
    </aside>
</form>
</div>

<script src="https://t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<script>
// 배송비 미리보기 — 서버 규칙(cart_shipping_fee)의 JS 거울. 확정은 항상 서버가 한다.
var CART_SHIP = { base: {{ $ship['base'] }}, free: {{ $ship['free'] }}, jeju: {{ $ship['jeju'] }}, itemTotal: {{ $item_total }} };
function cartShipPreview() {
    var zip = document.getElementById('od_zip').value.replace(/[^0-9]/g, '');
    var fee = CART_SHIP.base;
    if (CART_SHIP.free > 0 && CART_SHIP.itemTotal >= CART_SHIP.free) fee = 0;
    if (zip.length === 5 && zip.substring(0, 2) === '63') fee += CART_SHIP.jeju;
    document.getElementById('cart_ship_fee').textContent = fee.toLocaleString() + '원';
    document.getElementById('cart_pay_total').textContent = (CART_SHIP.itemTotal + fee).toLocaleString() + '원';
}
function cartSearchZip() {
    new daum.Postcode({
        oncomplete: function (data) {
            document.getElementById('od_zip').value = data.zonecode;
            document.getElementById('od_addr1').value = data.roadAddress || data.jibunAddress;
            document.getElementById('od_addr2').focus();
            cartShipPreview();
        }
    }).open();
}
cartShipPreview();
</script>
@endsection
