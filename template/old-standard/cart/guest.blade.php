@extends('layout.default')
@section('content')
<div class="cart-complete">
    <header class="cart-complete-head">
        <h2>비회원 주문 조회</h2>
        <p>주문 완료 화면의 주문번호와, 주문 시 입력한 비밀번호를 넣어 주세요.</p>
    </header>

    <form method="post" action="{{ $action_url }}" class="cart-co-sec cart-guest-form">
        <input type="hidden" name="token" value="{{ $token }}">
        <label>주문번호 <input type="text" name="od_no" placeholder="예) 260806-4B8F3851" required></label>
        <label>비밀번호 <input type="password" name="od_pw" required></label>
        <button type="submit" class="cart-cta">조회</button>
    </form>
</div>
@endsection
