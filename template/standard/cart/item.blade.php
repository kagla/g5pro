@extends('layout.default')
@section('content')
<article class="shop-item">

    {{-- 관리자만 보는 안내 — 손님에게 안 보이는 상품을 관리자가 열었을 때 그 이유를 알린다 --}}
    @if ($admin_notice !== '')
    <p class="shop-item-offnotice">{{ $admin_notice }}</p>
    @endif

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

    {{-- 상세 아래는 탭으로 나눈다 — 스마트스토어식 배치. 후기·문의는 4단계에 열리므로
         지금은 빈 상태를 정직하게 보여 준다(개수도 0으로 표시). --}}
    <nav class="shop-tabs" id="shop_tabs">
        <a href="#tab-detail" class="shop-tab is-on">상세정보</a>
        <a href="#tab-review" class="shop-tab">리뷰 <span class="shop-tab-n">{{ number_format($review_cnt) }}</span></a>
        <a href="#tab-qa" class="shop-tab">문의 <span class="shop-tab-n">{{ number_format($qa_cnt) }}</span></a>
        <a href="#tab-seller" class="shop-tab">판매자정보</a>

        @if (count($reco))
        <a href="#tab-reco" class="shop-tab">추천</a>
        @endif

    </nav>

    <section class="shop-panel is-on" id="tab-detail">
        <div class="shop-item-content">
            {!! $item['it_content'] !!}
        </div>
    </section>

    <section class="shop-panel" id="tab-review">
        <p class="shop-empty">아직 등록된 리뷰가 없습니다.</p>
    </section>

    <section class="shop-panel" id="tab-qa">
        <p class="shop-empty">등록된 문의가 없습니다. 궁금한 점은 판매자정보의 연락처로 문의해 주세요.</p>
    </section>

    <section class="shop-panel" id="tab-seller">
        <table class="shop-seller">
            <tbody>

            @if ($seller['company'] !== '')
            <tr><th>상호</th><td>{{ $seller['company'] }}{{ $seller['owner'] !== '' ? ' · '.$seller['owner'] : '' }}</td></tr>
            @endif

            @if ($seller['saupja_no'] !== '')
            <tr><th>사업자등록번호</th><td>{{ $seller['saupja_no'] }}</td></tr>
            @endif

            @if ($seller['tongsin_no'] !== '')
            <tr><th>통신판매업 신고</th><td>{{ $seller['tongsin_no'] }}</td></tr>
            @endif

            @if ($seller['addr'] !== '')
            <tr><th>주소</th><td>{{ $seller['addr'] }}</td></tr>
            @endif

            @if ($seller['tel'] !== '')
            <tr><th>전화</th><td>{{ $seller['tel'] }}</td></tr>
            @endif

            @if ($seller['email'] !== '')
            <tr><th>문의</th><td>{{ $seller['email'] }}</td></tr>
            @endif

            <tr>
                <th>배송비</th>
                <td>{{ number_format($seller['ship_base']) }}원 · {{ number_format($seller['ship_free']) }}원 이상 무료{{ $seller['ship_jeju'] > 0 ? ' · 제주·도서 '.number_format($seller['ship_jeju']).'원 추가' : '' }}</td>
            </tr>
            <tr><th>교환·반품</th><td>받으신 날부터 7일 안에 신청할 수 있습니다. 사용했거나 포장을 훼손한 상품은 어렵습니다.</td></tr>

            @if ($seller['bank'] !== '')
            <tr><th>무통장 입금</th><td>{{ $seller['bank'] }}</td></tr>
            @endif

            </tbody>
        </table>
    </section>

    @if (count($reco))
    <section class="shop-panel" id="tab-reco">
        <div class="shop-grid">

            @foreach ($reco as $r)
            <a class="shop-card" href="{{ $r['href'] }}">

                @if ($r['img'] !== '')
                <img src="{{ $r['img'] }}" alt="{{ $r['it_name'] }}">
                @else
                <span class="shop-card-noimg">{{ mb_substr($r['it_name'], 0, 1, 'utf-8') }}</span>
                @endif

                <strong class="shop-card-name">{{ $r['it_name'] }}</strong>
                <span class="shop-price">{{ number_format($r['it_price']) }}원</span>
            </a>
            @endforeach

        </div>
    </section>
    @endif

</article>

@if (count($images) > 1)
<script>
$('.shop-item-thumbs img').on('click', function () {
    $('#cart_main_img').attr('src', this.src);
});
</script>
@endif

<script>
// 탭 — 주소에 #tab-… 이 남아 새로고침·뒤로가기에도 보던 탭이 유지된다
$(function () {
    function showTab(id) {
        var $tab = $('.shop-tab[href="' + id + '"]');
        if (!$tab.length) return;
        $('.shop-tab').removeClass('is-on');
        $tab.addClass('is-on');
        $('.shop-panel').removeClass('is-on');
        $(id).addClass('is-on');
    }
    $('.shop-tab').on('click', function (e) {
        e.preventDefault();
        var id = $(this).attr('href');
        showTab(id);
        if (window.history && history.replaceState) history.replaceState(null, '', id);
        else location.hash = id;
    });
    if (location.hash) showTab(location.hash);
});
</script>

@endsection
