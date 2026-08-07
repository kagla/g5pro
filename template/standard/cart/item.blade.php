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
            <p class="shop-price"><strong>{{ number_format($item["it_price"]) }}</strong>원

                @if (!$single)
                <em>부터</em>
                @endif

            </p>


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
                {{-- 옵션 축마다 선택칸 하나. 앞 축을 고르면 뒤 축은 그 조합에 있는 값만 남고,
                     마지막 축에는 값마다 가격·재고가 함께 보인다. 실제로 전송되는 건 sk_id 뿐. --}}
                <div class="cart-opts" id="cart_opts">

                    @foreach ($opt_names as $oi => $name)
                    <label class="cart-buy-label">{{ $name }}
                        <select class="cart-opt" data-axis="{{ $oi }}" {{ $oi > 0 ? 'disabled' : '' }}>
                            <option value="">{{ $oi > 0 ? '앞 옵션을 먼저 고르세요' : $name.' 선택' }}</option>

                            @if ($oi === 0)
                            @foreach ($opt_values[$name] as $v)
                            <option value="{{ $v }}">{{ $v }}</option>
                            @endforeach
                            @endif

                        </select>
                    </label>
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

@if (!$single && count($opt_names))
<script>
// 옵션 단계 선택 — 앞 축을 고르면 뒤 축을 그 조합에 실제로 있는 값으로 다시 채운다.
// 마지막 축에는 값마다 가격과 재고를 함께 적고, 품절 조합은 고를 수 없게 막는다.
var CART_SKUS = {!! json_encode(array_values(array_map(function ($s) {
    return array('id' => $s['sk_id'], 'path' => $s['opt_path'], 'price' => $s['sk_price'], 'qty' => $s['sk_qty']);
}, $opt_skus)), JSON_UNESCAPED_UNICODE) !!};

$(function () {
    var $axes = $('#cart_opts .cart-opt'),
        last = $axes.length - 1,
        won = function (n) { return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ','); };

    // 앞 축들에서 고른 값(부분 조합)에 해당하는 SKU 만 남긴다
    function matching(prefix) {
        return $.grep(CART_SKUS, function (s) {
            for (var i = 0; i < prefix.length; i++) if (s.path[i] !== prefix[i]) return false;
            return true;
        });
    }

    function chosen(upto) {
        var v = [];
        $axes.each(function (i) { if (i < upto) v.push($(this).val()); });
        return v;
    }

    // axis 번째 선택칸을 앞 선택에 맞춰 다시 채운다
    function refill(axis) {
        var $sel = $axes.eq(axis),
            prefix = chosen(axis),
            rows = matching(prefix),
            seen = {}, html = '<option value="">' + $sel.data('label') + ' 선택</option>';

        $.each(rows, function (i, s) {
            var v = s.path[axis];
            if (seen[v] !== undefined) { seen[v] = Math.max(seen[v], s.qty); return; }
            seen[v] = s.qty;
        });
        $.each(seen, function (v, qty) {
            var text = v, sold = (qty <= 0);
            if (axis === last) {
                var row = $.grep(rows, function (s) { return s.path[axis] === v; })[0];
                text = v + ' — ' + won(row.price) + '원 · ' + (sold ? '품절' : '재고 ' + won(qty) + '개');
            } else if (sold) {
                text = v + ' (품절)';
            }
            html += '<option value="' + v + '"' + (sold ? ' disabled' : '') + '>' + text + '</option>';
        });
        $sel.html(html).prop('disabled', false);
    }

    function reset(fromAxis) {
        for (var i = fromAxis; i <= last; i++) {
            $axes.eq(i).html('<option value="">앞 옵션을 먼저 고르세요</option>').prop('disabled', true);
        }
    }

    // 잠깐 뜨는 안내 — 같은 옵션을 또 고른 경우처럼 막을 일이 아니라 알려 줄 일에 쓴다
    var noticeTimer = null;
    function notice(text) {
        var $n = $('#cart_pick_msg');
        $n.text(text).removeAttr('hidden');
        clearTimeout(noticeTimer);
        noticeTimer = setTimeout(function () { $n.attr('hidden', true); }, 2500);
    }

    // 담긴 줄들의 수량·금액을 다시 센다
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

    // 선택이 다 차면 그 조합을 아래 목록에 한 줄 얹는다. 이미 있으면 수량만 올린다.
    // 그리고 선택칸을 비워 다음 조합을 이어서 고를 수 있게 한다(요즘 쇼핑몰 방식).
    function settle() {
        var rows = matching(chosen(last + 1));
        if (!rows.length) return;
        var s = rows[0],
            $exist = $('#cart_picks .cart-pick[data-sk="' + s.id + '"]');

        if ($exist.length) {
            // 이미 담긴 조합 — 수량을 몰래 올리지 않고 알린다. 수량은 그 줄에서 직접 조절한다.
            notice('이미 선택된 옵션입니다. 수량은 아래에서 조절하세요.');
            $exist.addClass('is-bump');
            setTimeout(function () { $exist.removeClass('is-bump'); }, 900);
            $axes.eq(0).val('');
            reset(1);
            return;
        } else {
            var $li = $('<li class="cart-pick"></li>')
                .attr('data-sk', s.id).attr('data-price', s.price);
            $li.append($('<span class="cart-pick-name"></span>').text(s.path.join(' / ')));
            $li.append(
                '<span class="cart-pick-qty">' +
                '<button type="button" class="cart-qty-btn" data-d="-1" aria-label="수량 줄이기">−</button>' +
                '<input type="number" name="qty[]" value="1" min="1" max="' + Math.max(1, s.qty) + '">' +
                '<button type="button" class="cart-qty-btn" data-d="1" aria-label="수량 늘리기">+</button>' +
                '</span>' +
                '<span class="cart-pick-price">' + won(s.price) + '원</span>' +
                '<button type="button" class="cart-pick-del" aria-label="선택 취소">삭제</button>' +
                '<input type="hidden" name="sk_id[]" value="' + s.id + '">');
            $('#cart_picks').append($li);
        }
        retotal();

        // 다음 조합을 고를 수 있게 처음으로 되돌린다
        $axes.eq(0).val('');
        reset(1);
    }

    // 줄 수량 조절·삭제
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

    $axes.each(function (i) { $(this).data('label', $(this).closest('label').contents().first().text().trim()); });
    // 첫 축도 같은 규칙으로 한 번 채운다 — 조합이 전부 품절인 값에 (품절) 이 붙는다
    refill(0);

    $axes.on('change', function () {
        var axis = parseInt($(this).data('axis'), 10);
        reset(axis + 1);
        if (!$(this).val()) return;
        if (axis < last) refill(axis + 1); else settle();
    });

    // 담기 전에 고른 옵션이 있는지 확인
    $('.cart-buy').on('submit', function () {
        if (!$('#cart_picks .cart-pick').length) {
            alert('옵션을 선택해 주세요.');
            return false;
        }
    });
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
