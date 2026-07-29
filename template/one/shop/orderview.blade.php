{{-- 주문 상세 조회 (shop/orderinquiryview.php) --}}
@extends('layout.default')
@section('content')
<header class="bbs-head">
    <h2><span class="chip">주문</span>주문상세내역</h2>
    <div class="bbs-head-right">
        <div class="bbs-meta">{{ $od_id }} · {{ $od_time }}</div>
        @if ($admin_href)
        <a class="icon-btn bbs-admin-link" href="{!! $admin_href !!}" title="주문 관리" aria-label="주문 관리">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3.2"/><path d="M19.1 14.6a1.5 1.5 0 0 0 .3 1.7l.1.1a1.9 1.9 0 1 1-2.7 2.7l-.1-.1a1.5 1.5 0 0 0-1.7-.3 1.5 1.5 0 0 0-.9 1.4v.2a1.9 1.9 0 1 1-3.8 0v-.1a1.5 1.5 0 0 0-1-1.4 1.5 1.5 0 0 0-1.7.3l-.1.1a1.9 1.9 0 1 1-2.7-2.7l.1-.1a1.5 1.5 0 0 0 .3-1.7 1.5 1.5 0 0 0-1.4-.9h-.2a1.9 1.9 0 1 1 0-3.8h.1a1.5 1.5 0 0 0 1.4-1 1.5 1.5 0 0 0-.3-1.7l-.1-.1a1.9 1.9 0 1 1 2.7-2.7l.1.1a1.5 1.5 0 0 0 1.7.3h.1a1.5 1.5 0 0 0 .9-1.4v-.2a1.9 1.9 0 1 1 3.8 0v.1a1.5 1.5 0 0 0 .9 1.4 1.5 1.5 0 0 0 1.7-.3l.1-.1a1.9 1.9 0 1 1 2.7 2.7l-.1.1a1.5 1.5 0 0 0-.3 1.7v.1a1.5 1.5 0 0 0 1.4.9h.2a1.9 1.9 0 1 1 0 3.8h-.1a1.5 1.5 0 0 0-1.4.9Z"/></svg>
        </a>
        @endif
    </div>
</header>

{{-- 순정 orderinquiryview.php 출력 그대로 — 주문취소·환불 폼과 영수증 팝업 JS 가
     이 안의 id/class 를 잡으므로 구조는 손대지 않고 CSS 로만 다듬는다 --}}
<div class="odv">{!! $body_html !!}</div>

<div class="bbs-toolbar">
    <a class="btn" href="{{ $list_href }}">주문 목록</a>
    <a class="btn btn-primary" href="{{ $shop_href }}">쇼핑 계속하기</a>
</div>
@endsection
