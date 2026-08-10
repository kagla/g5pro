{{-- 주문 완료 (cart/complete.php) — 결제·주문 생성이 끝나는 네 곳이 모두 여기로 보낸다.
     주문 상세(cart/order.php)와 주소를 나눠 두는 이유는 말투다: 여기는 "접수됐습니다,
     지금 이것만 하시면 됩니다" 를 말하고 쇼핑으로 돌려보낸다. 상태를 살피는 곳이 아니라
     방금 끝난 일을 확인하는 곳이라 취소·구매확정·반품 버튼을 두지 않는다.
     대신 그리는 코드는 주문 상세와 한 벌이다(partials/order_*) — 한때 따로 그리다가
     한쪽만 손보는 일이 반복돼 같은 주문이 화면마다 다른 말을 했다. --}}
@extends('layout.default')
@section('content')
<div class="cart-complete">
    <header class="cart-complete-head">
        <h2>주문이 접수됐습니다</h2>
        <p>주문번호 <strong>{{ $order['od_no'] }}</strong> · <span class="cart-status is-{{ cart_order_status_tone($order['od_status']) }}">{{ $status_label }}</span></p>
    </header>

    @include('partials.order_bank', ['order' => $order, 'bank' => $bank,
        'can_edit_depositor' => $can_edit_depositor, 'action_url' => $action_url,
        'token' => $token, 'ret' => 'complete'])

    @include('partials.order_items', ['order' => $order, 'items' => $items])

    @include('partials.order_address', ['order' => $order, 'can_edit_receiver' => $can_edit_receiver])

    <p style="text-align:center"><a href="{{ $home_href }}" class="cart-cta">쇼핑 계속하기</a></p>
</div>
@endsection
