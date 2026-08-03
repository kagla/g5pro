@extends('layout.default')
@section('content')
<div class="bbs-cate-row">
<nav class="bbs-cate">
    <a href="{{ G5_SHOP_URL }}/">쇼핑몰</a>
    @if ($category['name'])<a href="{{ $category['href'] }}" class="active">{{ $category['name'] }}</a>@endif
</nav>
    {{-- 게시판 목록의 '게시판 관리' 톱니와 같은 모양·같은 자리.
         .bbs-cate 안에 두면 분류 칩 스타일이 덧씌워지므로 밖으로 뺀다.
         최고관리자에게만 채워진다 (순정 itemform.php 와 같은 조건) --}}
    @if ($admin_href)
    <a class="icon-btn bbs-admin-link" href="{!! $admin_href !!}" title="상품 수정" aria-label="상품 수정">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3.2"/><path d="M19.1 14.6a1.5 1.5 0 0 0 .3 1.7l.1.1a1.9 1.9 0 1 1-2.7 2.7l-.1-.1a1.5 1.5 0 0 0-1.7-.3 1.5 1.5 0 0 0-.9 1.4v.2a1.9 1.9 0 1 1-3.8 0v-.1a1.5 1.5 0 0 0-1-1.4 1.5 1.5 0 0 0-1.7.3l-.1.1a1.9 1.9 0 1 1-2.7-2.7l.1-.1a1.5 1.5 0 0 0 .3-1.7 1.5 1.5 0 0 0-1.4-.9h-.2a1.9 1.9 0 1 1 0-3.8h.1a1.5 1.5 0 0 0 1.4-1 1.5 1.5 0 0 0-.3-1.7l-.1-.1a1.9 1.9 0 1 1 2.7-2.7l.1.1a1.5 1.5 0 0 0 1.7.3h.1a1.5 1.5 0 0 0 .9-1.4v-.2a1.9 1.9 0 1 1 3.8 0v.1a1.5 1.5 0 0 0 .9 1.4 1.5 1.5 0 0 0 1.7-.3l.1-.1a1.9 1.9 0 1 1 2.7 2.7l-.1.1a1.5 1.5 0 0 0-.3 1.7v.1a1.5 1.5 0 0 0 1.4.9h.2a1.9 1.9 0 1 1 0 3.8h-.1a1.5 1.5 0 0 0-1.4.9Z"/></svg>
    </a>
    @endif
</div>

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
// 이전/다음 상품 — 순정은 해당 상품이 없으면 그 링크를 아예 그리지 않는다.
// 자리가 들쭉날쭉해지므로 없는 쪽은 비활성 버튼으로 채우고 순서(이전 → 다음)를 맞춘다.
(function () {
    var wrap = document.getElementById('sit_siblings');
    if (!wrap) return;
    [['siblings_prev', '이전상품'], ['siblings_next', '다음 상품']].forEach(function (pair) {
        var el = document.getElementById(pair[0]);
        if (!el) {
            el = document.createElement('span');
            el.id = pair[0];
            el.className = 'sit-sibling-off';
            el.setAttribute('aria-disabled', 'true');
            el.textContent = pair[1];
        }
        wrap.appendChild(el);   // 이미 있던 것도 다시 붙여 순서를 정리한다
    });
})();

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
