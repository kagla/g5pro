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
                    <th class="col-chk"><span class="sound_only">선택</span></th>
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
                        <input type="checkbox" name="ct_chk[{{ $it['idx'] }}]" value="1" id="ct_chk_{{ $it['idx'] }}" checked>
                        <label for="ct_chk_{{ $it['idx'] }}" class="sound_only">{{ $it['name'] }} 선택</label>
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

    <ul class="list-cards">
        @foreach ($items as $it)
        <li>
            <div class="s">
                <input type="checkbox" name="ct_chk[{{ $it['idx'] }}]" value="1" checked aria-label="{{ $it['name'] }} 선택">
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

<div class="bbs-admin-acts">
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
// 표(넓은 화면)와 카드(좁은 화면)가 같은 상품의 체크박스를 각각 그린다.
// 안 보이는 쪽이 함께 전송되면 화면에서 푼 체크가 무시되므로 전송에서 뺀다.
document.getElementById('sod_bsk_list').addEventListener('submit', function (e) {
    e.currentTarget.querySelectorAll('input[name^="ct_chk"]').forEach(function (c) {
        c.disabled = (c.offsetParent === null);
    });
});
</script>
@endif
@endsection
