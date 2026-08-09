@extends('layout.default')
@section('content')
<article class="shop-item">

    {{-- 관리자만 보는 안내 — 손님에게 안 보이는 상품을 관리자가 열었을 때 그 이유를 알린다 --}}
    @if ($admin_notice !== '')
    <p class="shop-item-offnotice">{{ $admin_notice }}</p>
    @endif

    <header class="bbs-head">
        <h2>{{ $item['it_name'] }}</h2>
        <div class="bbs-head-right">
            <div class="bbs-meta">

                @if ($category)
                <a href="{{ $list_href }}">{{ $category['ca_name'] }}</a> ·
                @endif

                상품코드 {{ $item['it_code'] }}

            </div>

            {{-- 게시판·영카트 상품의 '관리' 톱니와 같은 모양·같은 자리.
                 최고관리자에게만 URL 이 채워진다 --}}
            @if ($admin_edit_url !== '')
            <a class="icon-btn bbs-admin-link" href="{{ $admin_edit_url }}" title="상품 수정" aria-label="상품 수정">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3.2"/><path d="M19.1 14.6a1.5 1.5 0 0 0 .3 1.7l.1.1a1.9 1.9 0 1 1-2.7 2.7l-.1-.1a1.5 1.5 0 0 0-1.7-.3 1.5 1.5 0 0 0-.9 1.4v.2a1.9 1.9 0 1 1-3.8 0v-.1a1.5 1.5 0 0 0-1-1.4 1.5 1.5 0 0 0-1.7.3l-.1.1a1.9 1.9 0 1 1-2.7-2.7l.1-.1a1.5 1.5 0 0 0 .3-1.7 1.5 1.5 0 0 0-1.4-.9h-.2a1.9 1.9 0 1 1 0-3.8h.1a1.5 1.5 0 0 0 1.4-1 1.5 1.5 0 0 0-.3-1.7l-.1-.1a1.9 1.9 0 1 1 2.7-2.7l.1.1a1.5 1.5 0 0 0 1.7.3h.1a1.5 1.5 0 0 0 .9-1.4v-.2a1.9 1.9 0 1 1 3.8 0v.1a1.5 1.5 0 0 0 .9 1.4 1.5 1.5 0 0 0 1.7-.3l.1-.1a1.9 1.9 0 1 1 2.7 2.7l-.1.1a1.5 1.5 0 0 0-.3 1.7v.1a1.5 1.5 0 0 0 1.4.9h.2a1.9 1.9 0 1 1 0 3.8h-.1a1.5 1.5 0 0 0-1.4.9Z"/></svg>
            </a>
            @endif
        </div>
    </header>

    <div class="shop-item-top">
        <div class="shop-item-gallery">

            @if (count($images))
            {{-- 눌러서 크게 — 잘리지 않은 원본을 덮개 화면에서 보여 준다(cart_lightbox) --}}
            <img src="{{ $images[0]['view'] }}" alt="{{ $item['it_name'] }}" id="cart_main_img"
                 width="900" height="900" class="is-zoomable" role="button" tabindex="0"
                 title="눌러서 크게 보기">

            @if (count($images) > 1)
            {{-- 한 줄로 늘어놓고 넘치면 좌우 화살표로 민다 — 줄바꿈으로 쌓이면 상품 정보가
                 화면 밖으로 밀린다. 화살표는 줄 밖에 둔다(64px 사진 위에 얹으면 한 장을 가린다).
                 첫 장 말고는 lazy 로 두어 넘겨야 받는다. --}}
            <div class="shop-thumbnav" id="cart_thumbnav">
                <button type="button" class="shop-thumbnav-btn" data-d="-1" aria-label="이전 사진들">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg>
                </button>
                <div class="shop-item-thumbs">

                    @foreach ($images as $i => $im)
                    <img src="{{ $im['thumb'] }}" data-view="{{ $im['view'] }}" alt=""
                         width="64" height="64" loading="lazy"
                         class="{{ $i === 0 ? 'is-on' : '' }}">
                    @endforeach

                </div>
                <button type="button" class="shop-thumbnav-btn" data-d="1" aria-label="다음 사진들">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
            @endif

            @else
            <div class="shop-thumb-empty">이미지 준비 중</div>
            @endif

        </div>

        <div class="shop-item-info">
            <p class="shop-price"><strong>{{ number_format($item['it_price']) }}</strong>원{{ $single ? '' : '부터' }}</p>


            @if ((int)$item['it_stock'] === 0)
            <p class="shop-soldout">품절된 상품입니다.</p>
            @endif

            @if (count($buyable_skus))
            <form method="post" action="{{ $cart_action }}" class="cart-buy">
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="mode" value="add">

                @if ($single)
                <input type="hidden" name="sk_id" value="{{ $buyable_skus[0]['sk_id'] }}">
                @elseif (count($opt_names))
                {{-- 값을 눌러서 고른다 — 드롭다운을 열지 않고 한눈에 보고 한 번에 고른다.
                     칸은 JS 가 채운다(앞 선택에 따라 살아 있는 값이 달라지므로 한 곳에서 그린다). --}}
                <div class="opt-picker" id="cart_opts">

                    @foreach ($opt_names as $oi => $name)
                    <div class="opt-axis" data-axis="{{ $oi }}">
                        <p class="opt-axis-head">
                            <span class="opt-axis-name">{{ $name }}</span>
                            <span class="opt-axis-pick" data-role="pick"></span>
                        </p>
                        <div class="opt-chips" role="group" aria-label="{{ $name }} 선택"></div>
                    </div>
                    @endforeach

                </div>

                {{-- 고른 옵션이 한 줄씩 쌓인다 — 다른 색·다른 사이즈를 이어서 고를 수 있다.
                     줄마다 sk_id[]·qty[] 로 전송되고, 서버는 짝을 맞춰 한 번에 담는다. --}}
                <p class="cart-pick-msg" id="cart_pick_msg" hidden></p>
                <ul class="cart-picks" id="cart_picks"></ul>
                <p class="cart-picks-empty" id="cart_picks_empty">옵션을 고르면 여기에 담깁니다. 여러 개 고를 수 있어요.</p>
                <p class="cart-picks-total" id="cart_picks_total" hidden></p>
                @else
                <label class="cart-buy-label">옵션
                    <select name="sk_id" required>
                        <option value="">옵션을 선택하세요</option>

                        @foreach ($buyable_skus as $s)
                        <option value="{{ $s['sk_id'] }}">{{ $s['opt_label'] }} — {{ number_format($s['sk_price']) }}원</option>
                        @endforeach

                    </select>
                </label>
                @endif

                {{-- 옵션 단계 선택일 때는 수량을 줄마다 따로 조절하므로 여기 수량칸이 없다 --}}
                @if ($single || !count($opt_names))
                <label class="cart-buy-label">수량
                    <input type="number" name="qty" value="1" min="1" max="999">
                </label>
                @endif

                <div class="cart-buy-btns">
                    <button type="submit" name="dest" value="cart" class="cart-cta is-line">장바구니</button>
                    <button type="submit" name="dest" value="buy" class="cart-cta">바로구매</button>
                    @include('cart.wish_btn', ['item' => $item, 'wish_on' => $wish_on, 'wish_count' => $wish_count])
                </div>
            </form>
            @else
            {{-- 품절이라 구매 폼이 없는 상품 — 찜은 그때가 오히려 쓸모 있다(재입고 기다리기).
                 버튼 줄과 같은 클래스를 써서 폼이 있을 때와 높이·모양이 어긋나지 않게 한다. --}}
            <div class="cart-buy-btns cart-buy-btns-alone">
                @include('cart.wish_btn', ['item' => $item, 'wish_on' => $wish_on, 'wish_count' => $wish_count])
            </div>
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
                <img src="{{ $r['img'] }}" alt="{{ $r['it_name'] }}" loading="lazy">
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
// 누른 썸네일의 큰 사진을 위에 건다 — this.src 는 64px 짜리라 그대로 걸면 흐릿하다
$('.shop-item-thumbs img').on('click', function () {
    $('#cart_main_img').attr('src', $(this).data('view'));
    $('.shop-item-thumbs img').removeClass('is-on');
    $(this).addClass('is-on');
    cartLightboxIndex = $('.shop-item-thumbs img').index(this);
});

// 썸네일 줄의 좌우 화살표 — 한 번에 보이는 만큼씩 민다. 넘치지 않으면 화살표를 감추고,
// 끝에 닿으면 그쪽 화살표를 끈다(눌러도 안 움직이는 버튼이 남아 있으면 고장으로 읽힌다).
$(function () {
    var $nav = $('#cart_thumbnav'), $strip = $nav.find('.shop-item-thumbs');
    if (!$strip.length) return;

    function sync() {
        // 끝 판정에 여유를 둔다 — 안쪽 여백·소수점 때문에 정확히 0 이나 max 로 안 떨어진다
        var el = $strip[0], max = el.scrollWidth - el.clientWidth, slack = 4;
        $nav.toggleClass('is-static', max <= slack);
        $nav.find('[data-d="-1"]').prop('disabled', el.scrollLeft <= slack);
        $nav.find('[data-d="1"]').prop('disabled', el.scrollLeft >= max - slack);
    }

    // 미는 것은 값만 바꾸고, 부드럽게 미끄러지는 일은 브라우저(CSS scroll-behavior)에 맡긴다.
    // 다만 그 기능이 동작하지 않는 환경이 있어(값을 넣어도 제자리에 머문다) 잠시 뒤 도착했는지
    // 확인하고, 안 갔으면 부드럽게 미는 것만 끄고 그 자리에 놓는다 — 눌렀는데 아무 일도
    // 일어나지 않는 버튼은 만들지 않는다.
    $nav.on('click', '.shop-thumbnav-btn', function () {
        var el = $strip[0],
            page = Math.max(el.clientWidth - 72, 72),      // 한 장은 겹쳐 보이게 남긴다
            to = Math.max(0, Math.min(el.scrollLeft + page * parseInt($(this).data('d'), 10),
                el.scrollWidth - el.clientWidth));
        el.scrollLeft = to;
        setTimeout(function () {
            if (Math.abs(el.scrollLeft - to) > 4) {
                var keep = el.style.scrollBehavior;
                el.style.scrollBehavior = 'auto';
                el.scrollLeft = to;
                el.style.scrollBehavior = keep;
            }
            sync();
        }, 400);
        sync();
    });

    // 사진이 늦게 도착하면 줄 길이가 달라진다 — 그때도 화살표 상태를 다시 맞춘다
    $strip.on('scroll', sync).find('img').on('load', sync);
    $(window).on('resize', sync);
    sync();
});
</script>
@endif

@if (count($images))
{{-- 확대해 보기 — 큰 사진을 누르면 화면을 덮고 크게 보여 준다.
     확대: 휠·＋− 버튼·두 번 누르기(모바일은 두 손가락으로 벌리기), 최대 6배.
     이동: 확대된 상태에서 끌기(모바일도 손가락으로 끌기).
     넘기기: 화살표·좌우 키·썸네일, 모바일은 원래 크기에서 좌우로 밀기.
     닫기: ✕·바깥 클릭·Esc. --}}
<div class="cart-lb" id="cart_lb" role="dialog" aria-modal="true" aria-label="상품 이미지 확대" hidden>
    <div class="cart-lb-bar">
        <span class="cart-lb-count" id="cart_lb_count"></span>
        <div class="cart-lb-tools">
            <button type="button" class="cart-lb-tool" id="cart_lb_out" aria-label="축소">&minus;</button>
            <button type="button" class="cart-lb-pct" id="cart_lb_pct" aria-label="원래 크기로">100%</button>
            <button type="button" class="cart-lb-tool" id="cart_lb_in" aria-label="확대">+</button>
        </div>
        <button type="button" class="cart-lb-x" id="cart_lb_close" aria-label="닫기">&times;</button>
    </div>

    @if (count($images) > 1)
    <button type="button" class="cart-lb-nav cart-lb-prev" id="cart_lb_prev" aria-label="이전 이미지">&#8249;</button>
    <button type="button" class="cart-lb-nav cart-lb-next" id="cart_lb_next" aria-label="다음 이미지">&#8250;</button>
    @endif

    <div class="cart-lb-stage" id="cart_lb_stage">
        {{-- draggable=false — 브라우저 기본 이미지 끌기가 우리 이동과 부딪힌다 --}}
        <img src="" alt="{{ $item['it_name'] }}" id="cart_lb_img" draggable="false">
    </div>
    <p class="cart-lb-hint" id="cart_lb_hint">두 번 누르거나 휠을 굴려 확대 · 확대한 뒤 끌어서 이동</p>

    @if (count($images) > 1)
    <div class="cart-lb-thumbs" id="cart_lb_thumbs">

        @foreach ($images as $i => $im)
        <img src="{{ $im['thumb'] }}" alt="" data-idx="{{ $i }}" loading="lazy"
             class="{{ $i === 0 ? 'is-on' : '' }}">
        @endforeach

    </div>
    @endif

</div>

<script>
// 확대해 보기 — 원본 주소만 들고 있다가 열 때 비로소 내려받는다(상세 화면 무게를 늘리지 않게)
var CART_LB_IMAGES = {!! json_encode(array_map(function ($im) { return $im['full']; }, $images), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!};
var cartLightboxIndex = 0;

$(function () {
    var $lb = $('#cart_lb'), $img = $('#cart_lb_img'), $stage = $('#cart_lb_stage'),
        $count = $('#cart_lb_count'), $pct = $('#cart_lb_pct'), $hint = $('#cart_lb_hint'),
        $thumbs = $('#cart_lb_thumbs img'), total = CART_LB_IMAGES.length;

    // 배율은 1배(화면에 맞춘 크기)에서 6배까지. 한 번 누를 때마다 한 칸씩 오른다 —
    // 2배 하나만 두면 작은 글씨·재질을 볼 수 없다는 지적이 있었다.
    var MIN = 1, MAX = 6, STEP = 1.5;
    var scale = 1, tx = 0, ty = 0;          // 이동은 화면 픽셀 기준(가운데가 0,0)

    function apply(animate) {
        $img.toggleClass('is-anim', !!animate)
            .css('transform', 'translate(' + tx + 'px,' + ty + 'px) scale(' + scale + ')');
        $img.toggleClass('is-zoom', scale > 1);
        $pct.text(Math.round(scale * 100) + '%');
        $lb.toggleClass('is-zoomed', scale > 1);
    }

    // 확대한 사진이 화면 밖으로 빠져나가지 않게 이동 범위를 가둔다.
    // 사진이 화면보다 작은 축은 가운데 고정(0).
    function clamp() {
        var sw = $stage[0].clientWidth, sh = $stage[0].clientHeight,
            iw = $img[0].offsetWidth * scale, ih = $img[0].offsetHeight * scale,
            mx = Math.max(0, (iw - sw) / 2), my = Math.max(0, (ih - sh) / 2);
        tx = Math.max(-mx, Math.min(mx, tx));
        ty = Math.max(-my, Math.min(my, ty));
    }

    // 화면의 한 점(무대 가운데 기준 좌표)을 붙잡은 채 배율만 바꾼다 —
    // 그래야 가리킨 자리가 그대로 있고 엉뚱한 곳으로 튀지 않는다.
    function zoomTo(next, px, py, animate) {
        next = Math.max(MIN, Math.min(MAX, next));
        if (next === scale) return;
        if (px === undefined) { px = 0; py = 0; }
        var k = next / scale;
        tx = px - (px - tx) * k;
        ty = py - (py - ty) * k;
        scale = next;
        if (scale === MIN) { tx = 0; ty = 0; }
        clamp();
        apply(animate !== false);
    }

    // 이벤트 좌표 → 무대 가운데 기준 좌표
    function stagePoint(clientX, clientY) {
        var r = $stage[0].getBoundingClientRect();
        return {x: clientX - r.left - r.width / 2, y: clientY - r.top - r.height / 2};
    }

    function reset(animate) {
        scale = 1; tx = 0; ty = 0;
        apply(!!animate);
    }

    function show(i) {
        if (i < 0) i = total - 1;
        if (i >= total) i = 0;
        cartLightboxIndex = i;
        reset(false);
        $img.attr('src', CART_LB_IMAGES[i]);
        $count.text(total > 1 ? (i + 1) + ' / ' + total : '');
        $thumbs.removeClass('is-on').eq(i).addClass('is-on');
    }

    function open() {
        show(cartLightboxIndex);
        // 뒤 문서가 같이 스크롤되지 않게 잠근다. 스크롤 위치는 닫을 때 되돌린다
        $lb.data('scroll', $(window).scrollTop());
        $('body').addClass('cart-lb-lock');
        $lb.prop('hidden', false);
        // hidden 을 푼 다음 프레임에 클래스를 붙여야 전환(페이드)이 보인다
        setTimeout(function () { $lb.addClass('is-open'); }, 10);
        $hint.removeClass('is-gone');
        setTimeout(function () { $hint.addClass('is-gone'); }, 2600);
        $('#cart_lb_close').trigger('focus');
    }

    function close() {
        $lb.removeClass('is-open');
        $('body').removeClass('cart-lb-lock');
        $(window).scrollTop($lb.data('scroll') || 0);
        setTimeout(function () { $lb.prop('hidden', true); reset(false); }, 200);
        $('#cart_main_img').trigger('focus');
    }

    $('#cart_main_img').on('click', open).on('keydown', function (e) {
        if (e.which === 13 || e.which === 32) { e.preventDefault(); open(); }
    });
    $('#cart_lb_close').on('click', close);
    $('#cart_lb_prev').on('click', function () { show(cartLightboxIndex - 1); });
    $('#cart_lb_next').on('click', function () { show(cartLightboxIndex + 1); });
    $thumbs.on('click', function () { show(parseInt($(this).data('idx'), 10)); });

    $('#cart_lb_in').on('click', function () { zoomTo(scale * STEP); });
    $('#cart_lb_out').on('click', function () { zoomTo(scale / STEP); });
    $('#cart_lb_pct').on('click', function () { reset(true); });

    // 바깥(어두운 바탕)을 누르면 닫는다 — 사진 자체를 누른 것과 구분한다
    $stage.on('click', function (e) {
        if (e.target === this) close();
    });

    // 휠 — 가리키는 지점을 기준으로 조금씩. 페이지는 이미 잠겨 있지만 확대가 스크롤로
    // 새지 않게 기본 동작을 막는다
    $stage.on('wheel', function (e) {
        e.preventDefault();
        var d = e.originalEvent.deltaY, p = stagePoint(e.originalEvent.clientX, e.originalEvent.clientY);
        zoomTo(scale * (d < 0 ? 1.25 : 1 / 1.25), p.x, p.y, false);
    });

    // 두 번 누르기 — 원래 크기면 그 자리를 3배로, 이미 확대돼 있으면 원래대로
    $img.on('dblclick', function (e) {
        var p = stagePoint(e.clientX, e.clientY);
        if (scale > 1) reset(true);
        else zoomTo(3, p.x, p.y);
    });

    // 끌어서 이동(마우스) — 확대돼 있을 때만. 누른 지점과의 거리만큼 그대로 따라온다
    var drag = null;
    $img.on('mousedown', function (e) {
        if (scale <= 1) return;
        e.preventDefault();
        drag = {x: e.clientX - tx, y: e.clientY - ty};
        $img.addClass('is-grabbing');
    });
    $(document).on('mousemove.cartlb', function (e) {
        if (!drag) return;
        tx = e.clientX - drag.x; ty = e.clientY - drag.y;
        clamp(); apply(false);
    }).on('mouseup.cartlb', function () {
        drag = null;
        $img.removeClass('is-grabbing');
    });

    // ---- 손가락 조작 ----
    // 한 손가락: 확대 상태면 이동, 원래 크기면 좌우로 밀어 사진 넘기기.
    // 두 손가락: 벌려서 확대·오므려서 축소(가운데 지점 기준).
    var touch = null;

    function dist(t) {
        var dx = t[0].clientX - t[1].clientX, dy = t[0].clientY - t[1].clientY;
        return Math.sqrt(dx * dx + dy * dy);
    }

    $stage.on('touchstart', function (e) {
        var t = e.originalEvent.touches;
        if (t.length === 2) {
            var p = stagePoint((t[0].clientX + t[1].clientX) / 2, (t[0].clientY + t[1].clientY) / 2);
            touch = {mode: 'pinch', d0: dist(t), s0: scale, px: p.x, py: p.y};
        } else if (t.length === 1) {
            touch = {mode: scale > 1 ? 'pan' : 'swipe',
                     x: t[0].clientX - tx, y: t[0].clientY - ty, sx: t[0].clientX, moved: 0};
        }
    }).on('touchmove', function (e) {
        if (!touch) return;
        var t = e.originalEvent.touches;
        if (touch.mode === 'pinch' && t.length === 2) {
            e.preventDefault();
            zoomTo(touch.s0 * (dist(t) / touch.d0), touch.px, touch.py, false);
        } else if (touch.mode === 'pan' && t.length === 1) {
            e.preventDefault();
            tx = t[0].clientX - touch.x; ty = t[0].clientY - touch.y;
            clamp(); apply(false);
        } else if (touch.mode === 'swipe' && t.length === 1) {
            touch.moved = t[0].clientX - touch.sx;
        }
    }).on('touchend', function () {
        // 원래 크기에서 좌우로 충분히 밀었으면 앞뒤 사진으로
        if (touch && touch.mode === 'swipe' && total > 1 && Math.abs(touch.moved) > 60) {
            show(cartLightboxIndex + (touch.moved < 0 ? 1 : -1));
        }
        touch = null;
    });

    // 창 크기가 바뀌면 가둔 범위도 달라진다 — 밖으로 나가 있던 사진을 안으로 들인다
    $(window).on('resize.cartlb', function () {
        if ($lb.prop('hidden')) return;
        clamp(); apply(false);
    });

    $(document).on('keydown', function (e) {
        if ($lb.prop('hidden')) return;
        if (e.which === 27) { if (scale > 1) reset(true); else close(); }
        else if (e.which === 37) { if (scale <= 1) show(cartLightboxIndex - 1); }
        else if (e.which === 39) { if (scale <= 1) show(cartLightboxIndex + 1); }
        else if (e.which === 187 || e.which === 107) { e.preventDefault(); zoomTo(scale * STEP); }
        else if (e.which === 189 || e.which === 109) { e.preventDefault(); zoomTo(scale / STEP); }
        else if (e.which === 48 || e.which === 96) { e.preventDefault(); reset(true); }
    });
});
</script>
@endif
@if (!$single && count($opt_names))
<script>
// 옵션은 눌러서 고른다 — 값을 칩으로 늘어놓고, 앞 축을 고르면 뒤 축에서 살아 있는 값만 남긴다.
// 고를 수 없는 값은 지우지 않고 흐리게 두어 "이 색엔 이 사이즈가 없다"가 보이게 한다.
var CART_SKUS = {!! json_encode(array_values(array_map(function ($s) {
    return array('id' => $s['sk_id'], 'path' => $s['opt_path'], 'price' => $s['sk_price'], 'qty' => $s['sk_qty']);
}, $opt_skus)), JSON_UNESCAPED_UNICODE) !!};
var CART_AXES = {!! json_encode(array_values($opt_names), JSON_UNESCAPED_UNICODE) !!};

$(function () {
    var $picker = $('#cart_opts'),
        axes = CART_AXES.length,
        sel = [],                      // 축별로 고른 값
        won = function (n) { return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ','); };

    // 앞 축들에서 고른 값에 맞는 SKU 만
    function matching(prefix) {
        return $.grep(CART_SKUS, function (s) {
            for (var i = 0; i < prefix.length; i++) {
                if (prefix[i] !== null && s.path[i] !== prefix[i]) return false;
            }
            return true;
        });
    }

    // 축 하나를 다시 그린다. 값마다 살아 있는지(재고 있는 조합이 있는지)를 함께 계산한다.
    function drawAxis(i) {
        var prefix = sel.slice(0, i),
            rows = matching(prefix),
            order = [], stock = {}, exists = {};

        $.each(CART_SKUS, function (n, s) {          // 값의 등장 순서는 전체 기준으로 고정
            var v = s.path[i];
            if (order.indexOf(v) < 0) order.push(v);
        });
        $.each(rows, function (n, s) {
            var v = s.path[i];
            exists[v] = true;
            stock[v] = Math.max(stock[v] || 0, s.qty);
        });

        var $wrap = $picker.find('.opt-axis[data-axis="' + i + '"]'),
            $chips = $wrap.find('.opt-chips').empty(),
            locked = (i > 0 && sel[i - 1] == null);

        $wrap.toggleClass('is-locked', locked);
        $.each(order, function (n, v) {
            var live = !locked && exists[v] && stock[v] > 0,
                $b = $('<button type="button" class="opt-chip"></button>')
                    .attr('data-axis', i).attr('data-val', v)
                    .attr('aria-pressed', sel[i] === v ? 'true' : 'false');

            $b.append($('<span class="opt-chip-label"></span>').text(v));

            // 마지막 축은 값마다 남은 재고를 적는다 — 고르기 전에 몇 개 살 수 있는지 보이게.
            // 얼마 안 남았으면 색을 달리해 눈에 띄게 한다.
            if (i === axes - 1 && live) {
                $b.append($('<span class="opt-chip-left"></span>')
                    .addClass(stock[v] <= 5 ? 'is-low' : '')
                    .text(stock[v] + '개'));
            }
            if (!live) {
                $b.addClass('is-off').prop('disabled', true)
                  .attr('title', locked ? '앞 옵션을 먼저 고르세요' : '품절');
                if (!locked) $b.append($('<span class="opt-chip-left"></span>').text('품절'));
            }
            if (sel[i] === v) $b.addClass('is-on');
            $chips.append($b);
        });

        // 고른 값을 축 이름 옆에 적는다. 아직 못 고르는 축이면 대신 이유를 적는다 —
        // 칩만 회색이면 "왜 안 눌리지" 로 남는다
        var $pick = $wrap.find('[data-role="pick"]');
        if (locked) {
            var prev = $picker.find('.opt-axis[data-axis="' + (i - 1) + '"] .opt-axis-name').text();
            $pick.addClass('is-hint').text(prev + ' 먼저 고르세요');
        } else {
            $pick.removeClass('is-hint').text(sel[i] == null ? '' : sel[i]);
        }
    }

    function drawFrom(i) { for (var k = i; k < axes; k++) drawAxis(k); }

    // 잠깐 뜨는 안내 — 막을 일이 아니라 알려 줄 일에 쓴다
    var noticeTimer = null;
    function notice(text) {
        var $n = $('#cart_pick_msg');
        $n.text(text).removeAttr('hidden');
        clearTimeout(noticeTimer);
        noticeTimer = setTimeout(function () { $n.attr('hidden', true); }, 2500);
    }

    function retotal() {
        var qty = 0, sum = 0;
        $('#cart_picks .cart-pick').each(function () {
            var n = parseInt($(this).find('input[name="qty[]"]').val(), 10) || 0;
            qty += n;
            sum += n * parseInt($(this).data('price'), 10);
        });
        var has = $('#cart_picks .cart-pick').length > 0;
        $('#cart_picks_empty').toggle(!has);
        $('#cart_picks_total').attr('hidden', !has).html('')
            .append($('<span></span>').text('총 ' + won(qty) + '개'))
            .append($('<strong class="cart-total-sum"></strong>').text(won(sum) + '원'));
    }

    // 축을 다 고르면 그 조합을 아래 목록에 얹고, 칩 선택은 비워 다음 조합을 잇는다
    function settle() {
        var rows = matching(sel);
        if (!rows.length) return;
        var s = rows[0],
            $exist = $('#cart_picks .cart-pick[data-sk="' + s.id + '"]');

        if ($exist.length) {
            notice('이미 선택된 옵션입니다. 수량은 아래에서 조절하세요.');
            $exist.addClass('is-bump');
            setTimeout(function () { $exist.removeClass('is-bump'); }, 900);
        } else {
            var $li = $('<li class="cart-pick"></li>')
                .attr('data-sk', s.id).attr('data-price', s.price);
            $li.append($('<span class="cart-pick-name"></span>')
                .text(s.path.join(' / '))
                .append($('<span class="cart-pick-stock"></span>').text('재고 ' + won(s.qty) + '개')));
            $li.append(
                '<span class="cart-pick-qty">' +
                '<button type="button" class="cart-qty-btn" data-d="-1" aria-label="수량 줄이기">−</button>' +
                '<input type="number" name="qty[]" value="1" min="1" max="' + Math.max(1, s.qty) + '">' +
                '<button type="button" class="cart-qty-btn" data-d="1" aria-label="수량 늘리기">+</button>' +
                '</span>' +
                '<span class="cart-pick-price">' + won(s.price) + '원</span>' +
                '<button type="button" class="cart-pick-del" aria-label="선택 취소">✕</button>' +
                '<input type="hidden" name="sk_id[]" value="' + s.id + '">');
            // 방금 고른 조합이 맨 위 — 목록이 길어져도 손이 간 줄이 눈앞에 있다.
            // sk_id[]·qty[] 는 짝으로 읽히므로 순서가 바뀌어도 짝은 그대로다.
            $('#cart_picks').prepend($li);
            retotal();
        }

        sel = [];
        drawFrom(0);
    }

    $picker.on('click', '.opt-chip', function () {
        var i = parseInt($(this).data('axis'), 10),
            v = String($(this).data('val'));

        sel[i] = (sel[i] === v) ? null : v;        // 같은 칩을 다시 누르면 해제
        for (var k = i + 1; k < axes; k++) sel[k] = null;
        drawFrom(i);

        var done = true;
        for (var n = 0; n < axes; n++) if (sel[n] == null) done = false;
        if (done) settle();
    });

    $('#cart_picks').on('click', '.cart-qty-btn', function () {
        var $q = $(this).siblings('input[name="qty[]"]'),
            max = parseInt($q.attr('max'), 10),
            next = (parseInt($q.val(), 10) || 1) + parseInt($(this).data('d'), 10);
        $q.val(Math.min(max, Math.max(1, next)));
        retotal();
    }).on('change input', 'input[name="qty[]"]', function () {
        var max = parseInt($(this).attr('max'), 10);
        $(this).val(Math.min(max, Math.max(1, parseInt($(this).val(), 10) || 1)));
        retotal();
    }).on('click', '.cart-pick-del', function () {
        $(this).closest('.cart-pick').remove();
        retotal();
    });

    $('.cart-buy').on('submit', function () {
        if (!$('#cart_picks .cart-pick').length) {
            alert('옵션을 선택해 주세요.');
            return false;
        }
    });

    // 담기에 성공한 뒤 고른 줄을 비우는 데 쓴다(아래 장바구니 스크립트) — 화면에 그대로 남으면
    // 한 번 더 눌러 같은 옵션을 두 번 담게 된다. 지우는 규칙(빈 안내·합계)이 retotal 에 있어
    // 그쪽에서 문을 열어 준다.
    window.cartClearPicks = function () {
        $('#cart_picks').empty();
        retotal();
    };

    for (var i = 0; i < axes; i++) sel[i] = null;
    drawFrom(0);
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

@if (count($buyable_skus))
<script>
// 장바구니 담기 — 담고 나서 "장바구니로 갈까요?" 를 묻는다. 그러려면 서버가 이동시키면 안 되므로
// ajax=1 로 보내 결과만 받는다(cart_update.php 가 그때만 JSON 으로 답한다).
// 바로구매는 묻지 않는다 — 결제하러 가겠다고 이미 말한 사람에게 다시 묻는 셈이다.
$(function () {
    var $form = $('.cart-buy');
    if (!$form.length) return;

    // 어느 버튼으로 보냈는지 — submitter 는 오래된 브라우저에서 비어 있어 눌린 값을 직접 적어 둔다.
    // 아무 것도 안 눌린 제출(수량칸에서 Enter)은 서버가 장바구니로 치므로 여기서도 그렇게 본다 —
    // 같은 동작이 버튼이냐 Enter 냐에 따라 묻고 안 묻고가 갈리면 그게 더 이상하다.
    var dest = '';
    $form.find('button[name=dest]').on('click', function () { dest = $(this).val(); });

    $form.on('submit', function (e) {
        if (dest === 'buy') return;               // 바로구매는 지금까지처럼 폼 그대로 넘어간다
        if (e.isDefaultPrevented()) return;       // 앞의 옵션 검사(옵션을 선택해 주세요)가 막았다
        e.preventDefault();

        var $btn = $form.find('button[value=cart]');
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true);

        $.post('{{ $cart_action }}', $form.serialize() + '&dest=cart&ajax=1', null, 'json')
            .done(function (res) {
                if (!res || !res.ok) {
                    alert(res && res.msg ? res.msg : '잠시 후 다시 시도해 주세요.');
                    return;
                }
                if (window.cartClearPicks) window.cartClearPicks();
                var n = res.count ? ' (장바구니 ' + res.count + '개)' : '';
                if (confirm('장바구니에 담았습니다.' + n + '\n장바구니로 이동하시겠습니까?')) {
                    location.href = res.href;
                }
            })
            .fail(function () { alert('잠시 후 다시 시도해 주세요.'); })
            .always(function () { $btn.prop('disabled', false); });
    });
});
</script>
@endif

<script>
// 찜 — 화면을 다시 읽지 않고 하트만 고쳐 그린다. 상세는 사진·옵션이 무거워 새로고침이 아깝고,
// 무엇보다 고르던 옵션이 날아간다. 서버가 바뀐 상태(on·count)를 돌려주므로 여기서 세지 않는다.
// 비회원은 하트가 보이되 누르면 로그인으로 안내한다(login:true).
$(function () {
    var $btn = $('#cart_wish');
    if (!$btn.length) return;

    $btn.on('click', function () {
        if ($btn.data('busy')) return;      // 연타로 담기·빼기가 엇갈리지 않게 한 번에 하나만
        $btn.data('busy', 1);

        $.post('{{ $wish_action }}', {
            token: '{{ $token }}',
            mode: 'toggle',
            it_id: $btn.data('it-id')
        }, null, 'json').done(function (res) {
            if (res && res.ok) {
                $btn.toggleClass('is-on', !!res.on)
                    .attr('aria-pressed', res.on ? 'true' : 'false')
                    .attr('title', res.on ? '찜 취소' : '찜하기');
                $btn.find('.wish-btn-label').text(res.on ? '찜함' : '찜하기');
                $btn.find('.wish-btn-n').text(res.count).prop('hidden', !res.count);
                // 담았을 때만 묻는다 — 뺀 사람에게 목록으로 갈지 묻는 건 하려던 일과 반대다
                if (res.on && confirm('찜 목록에 담았습니다.\n찜 목록으로 이동하시겠습니까?')) {
                    location.href = '{!! $wish_href !!}';
                }
                return;
            }
            if (res && res.login) {
                if (confirm('찜은 로그인 후 이용할 수 있습니다. 로그인하시겠어요?')) {
                    location.href = '{!! $wish_login_url !!}';
                }
                return;
            }
            alert(res && res.msg ? res.msg : '잠시 후 다시 시도해 주세요.');
        }).fail(function () {
            alert('잠시 후 다시 시도해 주세요.');
        }).always(function () {
            $btn.removeData('busy');
        });
    });
});
</script>

@endsection
