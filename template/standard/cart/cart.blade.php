@extends('layout.default')
@section('content')
<header class="bbs-head">
    <h2>장바구니</h2>
    <div class="bbs-meta" id="cart_ship_notice">{{ $ship_notice }}</div>
</header>

@if (count($items))
{{-- 고른 것만 주문한다 — 담아 둔 것을 다 사야 하는 것은 아니다.
     예전에는 품절이 한 줄이라도 있으면 주문 자체를 막았는데, 이제 그 줄만 못 고르게 하고
     나머지는 그대로 주문할 수 있다(막다른 골목이 사라진다). --}}
<div class="cart-tools">
    <label class="cart-chk-all">
        <input type="checkbox" id="cart_all" checked>
        <span>전체 선택 <b id="cart_pick_n">0</b>/<span id="cart_pick_all">0</span></span>
    </label>
    <button type="button" class="btn btn-ico" id="cart_seldel">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4.5 7h15"/><path d="M9.5 7V5h5v2"/><path d="M6.5 7 7.6 20h8.8L17.5 7"/></svg>
        선택 삭제
    </button>
</div>

<div class="cart-cart">

    @foreach ($items as $it)
    @php $blocked = (!$it['avail'] || $it['over_stock']); @endphp
    <div class="cart-cart-row {{ $blocked ? 'is-blocked' : '' }}">
        <label class="cart-pick-box">
            <input type="checkbox" class="cart-pick" value="{{ $it['ct_id'] }}"
                   data-total="{{ $it['line_total'] }}"
                   {{ $blocked ? 'disabled' : 'checked' }}>
            <span class="sound_only">{{ $it['it_name'] }} 선택</span>
        </label>
        <a href="{{ $it['href'] }}" class="cart-cart-thumb">

            @if ($it['img'])
            <img src="{{ $it['img'] }}" alt="">
            @endif

        </a>
        <div class="cart-cart-info">
            <a href="{{ $it['href'] }}" class="cart-cart-name">{{ $it['it_name'] }}</a>

            @if ($it['opt_label'] !== '')
            <span class="cart-cart-opt">{{ $it['opt_label'] }}</span>
            @endif

            <span class="cart-cart-warn" data-role="warn" {{ $blocked ? '' : 'hidden' }}>
                @if (!$it['avail'])지금은 구매할 수 없는 상품입니다 (품절·판매중지)@elseif ($it['over_stock'])재고가 {{ number_format($it['sk_qty']) }}개뿐입니다 — 수량을 줄여 주세요@endif
            </span>

            <span class="cart-cart-price">{{ number_format($it['sk_price']) }}<em>원</em></span>
        </div>
        <div class="cart-cart-ctrl">
            {{-- 수량은 누르는 즉시 저장된다 — '변경' 버튼을 두었더니 수량만 고치고 누르지 않은 채
                 주문해서 옛 수량으로 나갔다. 버튼을 없애고 저장을 자동으로 옮긴다. --}}
            <div class="cart-qty" data-ct="{{ $it['ct_id'] }}" data-max="{{ (int)$it['sk_qty'] }}">
                <button type="button" class="cart-qty-btn" data-d="-1" aria-label="수량 줄이기">−</button>
                <input type="number" class="cart-qty-input" value="{{ $it['ct_qty'] }}" min="1"
                       max="{{ max(1, (int)$it['sk_qty']) }}" aria-label="{{ $it['it_name'] }} 수량">
                <button type="button" class="cart-qty-btn" data-d="1" aria-label="수량 늘리기">+</button>
            </div>
            <form method="post" action="{{ $action_url }}" data-confirm="이 상품을 장바구니에서 뺄까요?" data-confirm-danger>
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="mode" value="del">
                <input type="hidden" name="ct_id" value="{{ $it['ct_id'] }}">
                <button type="submit" class="btn-ghost">삭제</button>
            </form>
            <span class="cart-cart-line" data-role="line">{{ number_format($it['line_total']) }}<em>원</em></span>
        </div>
    </div>
    @endforeach

</div>

<aside class="cart-cart-sum">
    <dl>
        <dt>선택 상품</dt>
        <dd class="is-total" id="cart_sum">{{ number_format($total) }}원</dd>
        <dt>배송비</dt>
        <dd>주문서에서 계산 (기본 {{ number_format($ship_base) }}원)</dd>
    </dl>
    <div class="cart-sum-act">
        <p class="cart-order-block" id="cart_left_out" hidden></p>
        <a href="{{ $checkout_href }}" class="cart-cta" id="cart_order">주문하기</a>
    </div>
</aside>
@else
<p class="empty">장바구니가 비어 있습니다.</p>
<p style="text-align:center"><a href="{{ $list_href }}" class="cart-cta">상품 보러 가기</a></p>
@endif

@if (count($items))
<script>
// 고른 것만 주문한다. 서버(checkout.php)는 이미 buy=ct_id,… 로 일부만 받는 통로가 있다
// — 바로구매가 쓰던 그 통로다. 화면은 고른 것을 그 주소에 실어 보내기만 하면 된다.
// 금액도 화면에서 다시 센다(줄마다 line_total 을 지니고 있어 서버에 다시 묻지 않는다).
$(function () {
    var $all = $('#cart_all'), $order = $('#cart_order'),
        base = '{{ $checkout_href }}', shipFree = {{ (int)$ship_free }};

    function won(n) { return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ','); }
    function picks() { return $('.cart-pick:not(:disabled)'); }
    function checked() { return picks().filter(':checked'); }

    function paint() {
        var $on = checked(), sum = 0;
        $on.each(function () { sum += parseInt($(this).data('total'), 10) || 0; });

        $('#cart_sum').text(won(sum) + '원');
        $('#cart_pick_n').text($on.length);
        $('#cart_pick_all').text(picks().length);

        // 고른 줄을 옅게 칠해 무엇이 계산에 들었는지 눈으로도 보이게
        $('.cart-cart-row').each(function () {
            var $c = $(this).find('.cart-pick');
            $(this).toggleClass('is-picked', $c.length > 0 && $c.prop('checked'));
        });

        // 전체 선택은 세 상태다 — 다 골랐나 / 하나도 안 골랐나 / 그 사이(indeterminate)
        $all.prop('checked', $on.length > 0 && $on.length === picks().length);
        $all.prop('indeterminate', $on.length > 0 && $on.length < picks().length);

        // 무료배송 안내도 고른 금액 기준으로 다시 쓴다 — 서버가 처음 그린 규칙과 같다
        if (shipFree > 0) {
            var remain = shipFree - sum;
            $('#cart_ship_notice').text(remain > 0 ? won(remain) + '원 더 담으면 무료배송' : '무료배송 조건 충족');
        }

        // 아무것도 안 골랐으면 주문 단계로 넘어갈 것이 없다
        if (!$on.length) {
            $order.addClass('is-disabled').removeAttr('href').text('주문할 상품을 선택하세요');
            return;
        }
        var ids = $on.map(function () { return this.value; }).get();
        // 스코프를 생략해도 되는 때는 **장바구니 전체 줄**을 고른 때뿐이다.
        // "고를 수 있는 것을 전부" 골랐다고 생략하면, 막힌 줄이 있을 때 주문서가 장바구니
        // 전체를 보고 그 줄 때문에 거절한다 — 화면은 주문된다고 하고 서버는 막는 어긋남이 난다.
        var allRows = $('.cart-cart-row').length;
        $order.removeClass('is-disabled').text('주문하기 (' + $on.length + ')')
              .attr('href', ids.length === allRows ? base : base + '?buy=' + ids.join(','));

        // 못 고른 줄이 있으면 그 사실을 버튼 옆에 적어 둔다 — 조용히 빼고 보내면
        // 손님은 무엇이 빠졌는지 모른 채 결제한다
        var off = allRows - picks().length;
        $('#cart_left_out').text(off ? '재고·판매중지로 주문할 수 없는 상품 ' + off + '개는 빠집니다.' : '')
                           .prop('hidden', !off);
    }

    $(document).on('change', '.cart-pick', paint);
    $all.on('change', function () { picks().prop('checked', $all.prop('checked')); paint(); });

    // ── 수량 — 누르는 즉시 저장한다 ──
    // 연타를 매번 보내면 요청이 줄줄이 밀리고 마지막 응답이 먼저 온 것을 덮을 수 있다.
    // 잠깐 기다렸다가 마지막 값 한 번만 보낸다.
    var timers = {};

    // 재고에 닿으면 + 를 눌러도 더 늘지 않는다 — 넘긴 뒤에 꾸짖는 것보다 아예 못 넘게 하는 쪽이 낫다.
    // 버튼 모양으로도 한계를 알리고(비활성), 왜 안 늘어나는지는 토스트가 잠깐 말해 준다.
    function paintQty($box) {
        var max = parseInt($box.data('max'), 10) || 0,
            v = parseInt($box.find('.cart-qty-input').val(), 10) || 1;
        // disabled 를 쓰지 않는다 — 브라우저가 클릭 자체를 안 넘겨줘서 "왜 안 되는지" 를 말할 수 없다.
        // 흐리게만 칠하고(is-limit) 누르면 이유를 알려 준다. aria-disabled 로 낭독기에도 같은 뜻을 준다.
        function mark($b, off) { $b.toggleClass('is-limit', off).attr('aria-disabled', off ? 'true' : 'false'); }
        mark($box.find('[data-d="-1"]'), v <= 1);
        mark($box.find('[data-d="1"]'), max > 0 && v >= max);
    }

    // 한계에서 눌렀을 때 하는 말 — 숫자를 넣어 "얼마까지" 가 바로 읽히게
    function limitMsg(max) {
        return max > 0 ? '재고가 ' + won(max) + '개뿐이라 더 담을 수 없습니다'
                       : '품절된 상품이라 더 담을 수 없습니다';
    }

    function saveQty($row, hitMax) {
        var $box = $row.find('.cart-qty'), ct = $box.data('ct'),
            $in = $box.find('.cart-qty-input'),
            max = parseInt($box.data('max'), 10) || 0,
            qty = Math.max(1, parseInt($in.val(), 10) || 1);

        if (max > 0 && qty > max) { qty = max; hitMax = true; }
        if (hitMax) g5Toast(limitMsg(max));
        $in.val(qty);
        paintQty($box);

        clearTimeout(timers[ct]);
        timers[ct] = setTimeout(function () {
            // 저장 중 표시는 늦어질 때만 켠다 — 요청마다 흐렸다 밝히면 누를 때마다 번쩍인다.
            // 대부분은 500ms 안에 끝나 아무 일도 안 일어난 것처럼 보인다.
            var slow = setTimeout(function () { $box.addClass('is-saving'); }, 500);
            $.post('{{ $action_url }}', { token: '{{ $token }}', mode: 'set', ajax: '1', ct_id: ct, qty: qty },
                null, 'json')
            .done(function (res) {
                if (!res || !res.ok) { g5Toast('수량을 바꾸지 못했습니다. 새로고침해 주세요.'); return; }
                if (res.removed) { $row.remove(); paint(); return; }

                $row.find('[data-role=line]').html(won(res.line_total) + '<em>원</em>');
                // 담은 뒤 관리자가 재고를 줄였을 수 있다 — 서버가 알려 준 지금 재고로 한계를 다시 심는다
                $box.data('max', res.max).find('.cart-qty-input').val(res.qty).attr('max', Math.max(1, res.max));
                paintQty($box);
                if (res.clamped) g5Toast(limitMsg(res.max));
                var $chk = $row.find('.cart-pick');
                $chk.data('total', res.line_total);

                // 수량을 올리다 재고를 넘길 수 있다 — 넘긴 줄은 그 자리에서 못 고르게 막고 이유를 적는다
                var blocked = (!res.avail || res.over_stock);
                $row.toggleClass('is-blocked', blocked);
                $chk.prop('disabled', blocked);
                if (blocked) $chk.prop('checked', false);
                $row.find('[data-role=warn]')
                    .text(!res.avail ? '지금은 구매할 수 없는 상품입니다 (품절·판매중지)'
                                     : (res.over_stock ? '재고가 ' + won(res.sk_qty) + '개뿐입니다 — 수량을 줄여 주세요' : ''))
                    .prop('hidden', !blocked);
                paint();
            })
            .fail(function () { g5Toast('수량을 바꾸지 못했습니다. 새로고침해 주세요.'); })
            .always(function () { clearTimeout(slow); $box.removeClass('is-saving'); });
        }, 350);
    }

    $(document).on('click', '.cart-qty-btn', function () {
        var $box = $(this).closest('.cart-qty'), $in = $box.find('.cart-qty-input'),
            max = parseInt($box.data('max'), 10) || 0,
            v = parseInt($in.val(), 10) || 1, d = parseInt($(this).data('d'), 10);

        if (d > 0 && (max <= 0 || v >= max)) { g5Toast(limitMsg(max)); return; }
        if (d < 0 && v <= 1) { g5Toast('수량은 1개부터입니다. 빼시려면 삭제를 눌러 주세요'); return; }
        $in.val(v + d);
        saveQty($box.closest('.cart-cart-row'));
    });
    // 직접 쳐 넣은 값도 같은 규칙으로 자른다
    $(document).on('change', '.cart-qty-input', function () {
        saveQty($(this).closest('.cart-cart-row'));
    });

    $('#cart_seldel').on('click', function () {
        var ids = checked().map(function () { return this.value; }).get();
        if (!ids.length) { g5Toast('삭제할 상품을 선택해 주세요.'); return; }
        g5Confirm({
            title: '선택한 상품을 뺄까요?',
            message: ids.length + '개를 장바구니에서 뺍니다.',
            okText: '빼기', danger: true
        }, function () {
            // 줄마다 수량·삭제 폼이 이미 있어 목록을 폼으로 감쌀 수 없다(폼은 겹칠 수 없다).
            // 보낼 때 폼을 만들어 보낸다.
            var $f = $('<form method="post" style="display:none">').attr('action', '{{ $action_url }}')
                .append($('<input type="hidden" name="token">').val('{{ $token }}'))
                .append($('<input type="hidden" name="mode" value="seldel">'));
            $.each(ids, function (i, v) {
                $f.append($('<input type="hidden" name="ct_id[]">').val(v));
            });
            $f.appendTo('body').trigger('submit');
        });
    });

    $('.cart-qty').each(function () { paintQty($(this)); });
    paint();
});
</script>
@endif
@endsection
