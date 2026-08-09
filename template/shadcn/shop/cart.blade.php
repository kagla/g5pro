@extends('layout.default')
@section('content')
<header class="bbs-head">
    <h2><span class="chip">장바구니</span>담은 상품 {{ number_format($count) }}개</h2>
</header>

@if (!$count)
<div class="cart-empty">
    <p class="bbs-empty">장바구니에 담긴 상품이 없습니다.</p>
    <a class="btn btn-primary" href="{{ $shop_url }}">쇼핑 계속하기</a>
</div>
@else
<form name="frmcartlist" id="sod_bsk_list" method="post" action="{{ $action_url }}">
<input type="hidden" name="url" value="{{ $order_url }}">
<input type="hidden" name="records" value="{{ $count }}">
<input type="hidden" name="act" value="">

<div class="list-panel">
    <div class="list-table-wrap">
        <table class="list-table cart-table">
            <thead>
                <tr>
                    <th class="col-chk"><label class="chk-cell"><input type="checkbox" class="ct-all" aria-label="상품 전체 선택"></label></th>
                    <th class="col-subject">상품</th>
                    <th>수량</th>
                    <th>판매가</th>
                    <th>포인트</th>
                    <th>배송비</th>
                    <th>합계</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $it)
                <tr>
                    <td class="col-chk">
                        {{-- 처음엔 비워 둔다 — '비우기' 가 따로 있으므로 전체 선택이 기본일 이유가 없고,
                             실수로 선택삭제/주문에 딸려 들어가는 것을 막는다 --}}
                        <label class="chk-cell" for="ct_chk_{{ $it['idx'] }}">
                            <input type="checkbox" name="ct_chk[{{ $it['idx'] }}]" value="1" id="ct_chk_{{ $it['idx'] }}">
                            <span class="sound_only">{{ $it['name'] }} 선택</span>
                        </label>
                    </td>
                    <td class="col-subject">
                        <input type="hidden" name="it_id[{{ $it['idx'] }}]" value="{{ $it['it_id'] }}">
                        <input type="hidden" name="it_name[{{ $it['idx'] }}]" value="{{ $it['name'] }}">
                        <div class="cart-prd">
                            @if ($it['img'])
                            <a class="cart-thumb" href="{{ $it['href'] }}"><img src="{{ $it['img'] }}" alt=""></a>
                            @endif
                            <div class="cart-prd-info">
                                <a class="cart-name" href="{{ $it['href'] }}">{{ $it['name'] }}</a>
                                @if ($it['options'])<div class="cart-opt">{!! $it['options'] !!}</div>@endif
                            </div>
                        </div>
                    </td>
                    <td>{{ number_format($it['qty']) }}</td>
                    <td>{{ number_format($it['price']) }}</td>
                    <td>{{ number_format($it['point']) }}</td>
                    <td>{{ $it['send_label'] }}</td>
                    <td class="cart-sum"><strong>{{ number_format($it['sell_price']) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="cart-cards-head">
        <label><input type="checkbox" class="ct-all"> 전체 선택</label>
        <span class="chk-count" aria-live="polite"></span>
    </div>
    <ul class="list-cards">
        @foreach ($items as $it)
        <li>
            <div class="s">
                <input type="checkbox" name="ct_chk[{{ $it['idx'] }}]" value="1" aria-label="{{ $it['name'] }} 선택">
                <span class="t"><a href="{{ $it['href'] }}">{{ $it['name'] }}</a></span>
            </div>
            <div class="m cart-m">
                <span>{{ number_format($it['qty']) }}개</span>
                <span>{{ $it['send_label'] }}</span>
                <span><strong>{{ number_format($it['sell_price']) }}원</strong></span>
            </div>
        </li>
        @endforeach
    </ul>
</div>

<div class="cart-acts">
    <button type="button" class="btn" onclick="return form_check('seldelete');">선택삭제</button>
    <button type="button" class="btn" onclick="return form_check('alldelete');">비우기</button>
</div>

<div class="cart-total">
    <dl>
        <dt>배송비</dt><dd>{{ number_format($send_cost) }}원</dd>
        <dt>적립 예정</dt><dd>{{ number_format($tot_point) }}점</dd>
        <dt class="cart-total-key">총계</dt>
        <dd class="cart-total-val">{{ number_format($tot_price) }}<span class="won">원</span></dd>
    </dl>
</div>

<div class="bbs-toolbar">
    <a class="btn" href="{{ $continue_url }}">쇼핑 계속하기</a>
    <button type="button" class="btn btn-primary" onclick="return form_check('buy');">주문하기</button>
</div>
</form>

<script>
// 순정 form_check() 는 shop/cart.php 가 매핑 훅 뒤에서 echo 하므로 blade 화면에는 오지 않는다.
// 계약(act = buy | seldelete | alldelete → cartupdate.php)은 그대로 두고 여기서 다시 정의한다.
// 전체선택 — 표와 카드 두 레이아웃에 각각 있으므로 지금 보이는 쪽만 다루고 서로 맞춘다
(function () {
    var f = document.frmcartlist;
    var alls = [].slice.call(f.querySelectorAll('.ct-all'));
    var count = f.querySelector('.chk-count');

    function visible() {
        return [].slice.call(f.querySelectorAll('input[name^="ct_chk"]'))
                 .filter(function (c) { return c.offsetParent !== null; });
    }
    function sync() {
        var v = visible(), n = v.filter(function (c) { return c.checked; }).length;
        alls.forEach(function (a) {
            a.checked = (n > 0 && n === v.length);
            a.indeterminate = (n > 0 && n < v.length);
        });
        if (count) count.textContent = n ? n + '개 선택' : '';
    }
    alls.forEach(function (a) {
        a.addEventListener('change', function () {
            visible().forEach(function (c) { c.checked = a.checked; });
            sync();
        });
    });
    f.addEventListener('change', function (e) {
        if (e.target.name && e.target.name.indexOf('ct_chk') === 0) sync();
    });
    window.addEventListener('resize', sync);   // 보이는 레이아웃이 바뀐다
    sync();
})();

function form_check(act) {
    var f = document.frmcartlist;
    var boxes = [].slice.call(f.querySelectorAll('input[name^="ct_chk"]'));
    // 표(넓은 화면)와 카드(좁은 화면)가 같은 상품의 체크박스를 각각 그린다 — 보이는 쪽이 기준
    var visible = boxes.filter(function (c) { return c.offsetParent !== null; });
    var checked = visible.filter(function (c) { return c.checked; }).length;

    if (act === 'buy' && !checked) {
        alert('주문하실 상품을 하나이상 선택해 주십시오.');
        return false;
    }
    // 선택삭제는 고른 것만 빠지고 다시 담기 쉬우므로 묻지 않는다 (비우기는 전체라 확인)
    if (act === 'seldelete' && !checked) {
        alert('삭제하실 상품을 하나이상 선택해 주십시오.');
        return false;
    }
    // 비우기는 되돌릴 수 없어 한 번 묻는다. 대화상자는 값을 돌려주지 않으므로
    // 일단 멈추고, 확인을 받으면 이 함수를 다시 부른다(플래그로 두 번 묻지 않게).
    if (act === 'alldelete' && f.dataset.confirmed !== '1') {
        g5Confirm({ title: '장바구니를 비울까요?', message: '담아 둔 상품이 모두 빠집니다.',
                    okText: '비우기', danger: true },
            function () { f.dataset.confirmed = '1'; form_check(act); });
        return false;
    }
    f.dataset.confirmed = '';

    // 안 보이는 쪽 체크는 전송에서 뺀다 — 그래야 화면에서 푼 체크가 반영된다
    boxes.forEach(function (c) { c.disabled = (c.offsetParent === null); });
    f.act.value = act;
    f.submit();
    return true;
}
</script>
@endif
@endsection
