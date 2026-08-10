{{-- 배송지 — 주문완료·주문 상세가 함께 쓴다(order_bank 와 같은 이유).
     받는 값: $order · $can_edit_receiver

     배송지는 손님이 못 고친다 — 이름·연락처만 열어도 "주소는 왜 안 되나" 가 따라오고,
     주소는 배송비가 우편번호에 걸려 있어 금액이 달라지는 자리다. 한 건씩 사정을 듣고
     판단할 수 있는 판매자에게 통째로 넘긴다. 안내는 아직 고칠 수 있는 동안에만 뜬다 —
     발송한 뒤에 "문의하세요" 는 헛말이다. --}}
<section class="cart-co-sec">
    <h3>배송지</h3>
    <p>받는분 {{ $order['od_recv_name'] !== '' ? $order['od_recv_name'] : $order['od_name'] }} · {{ $order['od_recv_hp'] !== '' ? $order['od_recv_hp'] : $order['od_hp'] }}</p>
    <p>({{ $order['od_zip'] }}) {{ $order['od_addr1'] }} {{ $order['od_addr2'] }}</p>

    @if ($order['od_memo'] !== '')
    <p class="cart-co-note">요청사항: {{ $order['od_memo'] }}</p>
    @endif

    @if ($can_edit_receiver)
    <p class="cart-co-note">받는분·주소를 바꾸시려면 판매자에게 문의해 주세요.</p>
    @endif

</section>
