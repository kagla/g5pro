@extends('layout.default')
@section('content')
<article class="shop-item">
    <header class="bbs-head">
        <h2>{{ $item['it_name'] }}</h2>
        <div class="bbs-meta">

            @if ($category)
            <a href="{{ $list_href }}">{{ $category['ca_name'] }}</a> ·
            @endif

            상품코드 {{ $item['it_code'] }}

            @if ($admin_edit_url !== '')
            · <a href="{{ $admin_edit_url }}">관리자 수정</a>
            @endif

        </div>
    </header>

    <div class="shop-item-top">
        <div class="shop-item-gallery">

            @if (count($images))
            <img src="{{ $images[0] }}" alt="{{ $item['it_name'] }}" id="cart_main_img">

            @if (count($images) > 1)
            <div class="shop-item-thumbs">

                @foreach ($images as $src)
                <img src="{{ $src }}" alt="">
                @endforeach

            </div>
            @endif

            @else
            <div class="shop-thumb-empty">이미지 준비 중</div>
            @endif

        </div>

        <div class="shop-item-info">
            <p class="shop-price"><strong>{{ number_format($item['it_price']) }}</strong>원{{ $single ? '' : '부터' }}</p>

            @if (count($skus) && !$single)
            <table class="shop-sku-table">
                <thead>
                <tr><th>옵션</th><th>가격</th><th>재고</th></tr>
                </thead>
                <tbody>

                @foreach ($skus as $s)
                <tr>
                    <td>{{ $s['opt_label'] }}</td>
                    <td>{{ number_format($s['sk_price']) }}원</td>
                    <td>{{ $s['soldout'] ? '품절' : number_format($s['sk_qty']) }}</td>
                </tr>
                @endforeach

                </tbody>
            </table>
            @endif

            @if ((int)$item['it_stock'] === 0)
            <p class="shop-soldout">품절된 상품입니다.</p>
            @endif

            @if (count($buyable_skus))
            <form method="post" action="{{ $cart_action }}" class="cart-buy">
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="mode" value="add">

                @if ($single)
                <input type="hidden" name="sk_id" value="{{ $buyable_skus[0]['sk_id'] }}">
                @else
                <label class="cart-buy-label">옵션
                    <select name="sk_id" id="cart_sku_select" required>
                        <option value="">옵션을 선택하세요</option>

                        @foreach ($buyable_skus as $s)
                        <option value="{{ $s['sk_id'] }}" data-price="{{ $s['sk_price'] }}">{{ $s['opt_label'] }} — {{ number_format($s['sk_price']) }}원</option>
                        @endforeach

                    </select>
                </label>
                @endif

                <label class="cart-buy-label">수량
                    <input type="number" name="qty" value="1" min="1" max="999">
                </label>
                <div class="cart-buy-btns">
                    <button type="submit" name="dest" value="cart" class="cart-cta is-line">장바구니</button>
                    <button type="submit" name="dest" value="buy" class="cart-cta">바로구매</button>
                </div>
            </form>
            @endif

        </div>
    </div>

    <div class="shop-item-content">
        {!! $item['it_content'] !!}
    </div>
</article>

@if (count($images) > 1)
<script>
$('.shop-item-thumbs img').on('click', function () {
    $('#cart_main_img').attr('src', this.src);
});
</script>
@endif

@endsection
