@extends('layout.default')
@section('content')
<header class="bbs-head">
    <h2><span class="chip">주문</span>주문서 작성</h2>
    <a class="btn" href="{{ $cart_url }}">장바구니로</a>
</header>

{{-- 순정 orderform.sub.php 출력 그대로 — 결제수단·PG 연동 JS 가 얽혀 있어 새로 만들지 않는다 --}}
<div class="order-form">{!! $form_html !!}</div>
@endsection
