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
            <img src="{{ $images[0]['view'] }}" alt="{{ $item['it_name'] }}" id="cart_main_img"
                 width="900" height="900">

            @if (count($images) > 1)
            {{-- 한 줄로 늘어놓고 넘치면 옆으로 민다 — 줄바꿈으로 쌓이면 상품 정보가 화면 밖으로
                 밀린다. 첫 장 말고는 lazy 로 두어 스크롤해야 받는다. --}}
            <div class="shop-item-thumbs">

                @foreach ($images as $i => $im)
                <img src="{{ $im['thumb'] }}" data-view="{{ $im['view'] }}" alt=""
                     width="64" height="64" loading="lazy"
                     class="{{ $i === 0 ? 'is-on' : '' }}">
                @endforeach

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

@endsection
