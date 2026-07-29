@extends('layout.default')
@section('content')
<nav class="bbs-cate">
    <a href="{{ G5_SHOP_URL }}/">쇼핑몰</a>
    @if ($category['name'])<a href="{{ $category['href'] }}" class="active">{{ $category['name'] }}</a>@endif
</nav>

{{-- 순정 shop/item.php 는 이 스크립트를 스킨보다 먼저 echo 하는데, blade 화면에서는
     그 출력이 버퍼와 함께 버려진다. 수량 ±·옵션·합계가 전부 여기 있으므로 직접 싣는다. --}}
@if ($is_orderable)
<script src="{{ $shop_js }}"></script>
@endif

{{-- 상품 개요·구입폼 — 순정 item.form.skin.php 출력 그대로.
     이미지 갤러리, 옵션, 수량, 장바구니/바로구매, 위시, SNS 가 모두 이 안에 있고
     js/shop.js 가 이 안의 id/class 를 잡는다. 다시 그리지 않고 CSS 로만 다듬는다. --}}
<div class="sit-wrap">{!! $form_html !!}</div>

<section class="sit-tabs" id="sit-tabs">
    <div class="tab-tit" role="tablist">
        <button type="button" role="tab" aria-selected="true"  aria-controls="tab-inf" id="tabbtn-inf">상품설명</button>
        <button type="button" role="tab" aria-selected="false" aria-controls="tab-use" id="tabbtn-use">사용후기 <b>{{ number_format($use_count) }}</b></button>
        <button type="button" role="tab" aria-selected="false" aria-controls="tab-qa"  id="tabbtn-qa">상품문의 <b>{{ number_format($qa_count) }}</b></button>
        <button type="button" role="tab" aria-selected="false" aria-controls="tab-dex" id="tabbtn-dex">배송/교환</button>
    </div>

    <div class="tab-con" id="tab-inf" role="tabpanel" aria-labelledby="tabbtn-inf">
        @if ($item['explan'])
        <div class="sit-explan">{!! $item['explan'] !!}</div>
        @endif

        @if (count($info_notice))
        <h3 class="sit-sub">상품 정보 고시</h3>
        <div class="sit-notice-wrap">
            <table class="sit-notice">
                <tbody>
                @foreach ($info_notice as $n)
                <tr><th scope="row">{{ $n['title'] }}</th><td>{{ $n['value'] }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @if (!$item['explan'] && !count($info_notice))
        <p class="bbs-empty">등록된 상세정보가 없습니다.</p>
        @endif
    </div>

    {{-- 사용후기·상품문의는 순정 화면(itemuse.php / itemqa.php)을 그대로 담는다.
         목록·글쓰기·페이징·팝업이 얽혀 있어 새로 만들지 않고 CSS 로만 맞춘다. --}}
    <div class="tab-con" id="tab-use" role="tabpanel" aria-labelledby="tabbtn-use" hidden>
        <div class="sit-embed">{!! $use_html !!}</div>
    </div>

    <div class="tab-con" id="tab-qa" role="tabpanel" aria-labelledby="tabbtn-qa" hidden>
        <div class="sit-embed">{!! $qa_html !!}</div>
    </div>

    <div class="tab-con" id="tab-dex" role="tabpanel" aria-labelledby="tabbtn-dex" hidden>
        @if ($delivery_html)
        <h3 class="sit-sub">배송</h3>
        <div class="sit-explan">{!! $delivery_html !!}</div>
        @endif
        @if ($change_html)
        <h3 class="sit-sub">교환/반품</h3>
        <div class="sit-explan">{!! $change_html !!}</div>
        @endif
        @if (!$delivery_html && !$change_html)
        <p class="bbs-empty">등록된 배송/교환 정보가 없습니다.</p>
        @endif
    </div>
</section>

@if (count($related))
<section class="shop-block">
    <header class="bbs-head"><h2>관련상품</h2></header>
    @include('partials.shop_items', ['items' => $related])
</section>
@endif

<script>
// 위시리스트 담기 — 순정 item_wish() 는 확인 없이 바로 폼을 보낸다.
// 마크업(javascript: href)은 그대로 두고 클릭을 가로채 한 번 묻는다.
document.querySelectorAll('.sit_btn_wish').forEach(function (a) {
    a.addEventListener('click', function (e) {
        if (!confirm('이 상품을 위시리스트에 보관하시겠습니까?')) e.preventDefault();
    });
});
</script>

<script>
// 탭 — 클릭한 것만 보인다. 순정 링크가 쓰는 #sit_use 같은 앵커도 받아 준다.
(function () {
    var wrap = document.getElementById('sit-tabs');
    var tabs = [].slice.call(wrap.querySelectorAll('.tab-tit button'));

    function show(id) {
        tabs.forEach(function (t) {
            var on = t.getAttribute('aria-controls') === id;
            t.setAttribute('aria-selected', String(on));
            document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
        });
    }
    tabs.forEach(function (t) {
        t.addEventListener('click', function () { show(t.getAttribute('aria-controls')); });
    });

    var map = { '#sit_use': 'tab-use', '#sit_qa': 'tab-qa', '#sit_inf': 'tab-inf', '#sit_dex': 'tab-dex' };
    if (map[location.hash]) { show(map[location.hash]); wrap.scrollIntoView(); }
})();
</script>
@endsection
