{{-- 쿠폰 내역 (shop/coupon.php) — 주문서·마이페이지에서 새 창으로 연다 --}}
@extends('layout.popup')
@section('content')
<ul class="cou-list">
    @forelse ($items as $it)
    <li>
        <div class="cou-top">
            <span class="cou-tit">{{ $it['subject'] }}</span>
            <strong class="cou-pri">{{ $it['amount'] }}</strong>
        </div>
        <div class="cou-meta">
            <span>{{ $it['target'] }}</span>
            <span>{{ $it['period'] }}</span>
        </div>
    </li>
    @empty
    <li class="bbs-empty">쓸 수 있는 쿠폰이 없습니다.</li>
    @endforelse
</ul>

<div class="popup-btns">
    <button type="button" class="btn" onclick="window.close();">창닫기</button>
</div>
@endsection
