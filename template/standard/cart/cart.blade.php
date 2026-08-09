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

            @if (!$it['avail'])
            <span class="cart-cart-warn">지금은 구매할 수 없는 상품입니다 (품절·판매중지)</span>
            @elseif ($it['over_stock'])
            <span class="cart-cart-warn">재고가 {{ number_format($it['sk_qty']) }}개뿐입니다 — 수량을 줄여 주세요</span>
            @endif

            <span class="cart-cart-price">{{ number_format($it['sk_price']) }}<em>원</em></span>
        </div>
        <div class="cart-cart-ctrl">
            <form method="post" action="{{ $action_url }}" class="cart-cart-qty">
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="mode" value="set">
                <input type="hidden" name="ct_id" value="{{ $it['ct_id'] }}">
                <input type="number" name="qty" value="{{ $it['ct_qty'] }}" min="1" max="999">
                <button type="submit" class="btn-ghost">변경</button>
            </form>
            <form method="post" action="{{ $action_url }}" data-confirm="이 상품을 장바구니에서 뺄까요?" data-confirm-danger>
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="mode" value="del">
                <input type="hidden" name="ct_id" value="{{ $it['ct_id'] }}">
                <button type="submit" class="btn-ghost">삭제</button>
            </form>
            <span class="cart-cart-line">{{ number_format($it['line_total']) }}<em>원</em></span>
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

    $('#cart_seldel').on('click', function () {
        var ids = checked().map(function () { return this.value; }).get();
        if (!ids.length) { alert('삭제할 상품을 선택해 주세요.'); return; }
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

    paint();
});
</script>
@endif
@endsection
