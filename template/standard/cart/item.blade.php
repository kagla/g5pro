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
                <img src="{{ $src }}" alt="" onclick="document.getElementById('cart_main_img').src=this.src">
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

            <p class="shop-item-note">장바구니·구매는 2단계에서 열립니다.</p>
        </div>
    </div>

    <div class="shop-item-content">
        {!! $item['it_content'] !!}
    </div>
</article>
@endsection
