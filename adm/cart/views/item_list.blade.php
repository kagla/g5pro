<form method="get" action="{{ $self_url }}" class="local_sch01 local_sch">
    <select name="ca_id">
        <option value="0">전체 분류</option>

        @foreach ($categories as $c)
        <option value="{{ $c['ca_id'] }}" {{ $ca_id === (int)$c['ca_id'] ? 'selected' : '' }}>{{ str_repeat('— ', $c['ca_depth'] - 1) }}{{ $c['ca_name'] }}</option>
        @endforeach

    </select>
    <input type="text" name="q" value="{{ $q }}" placeholder="상품명 또는 상품코드" class="frm_input">
    <button type="submit" class="btn_submit btn">검색</button>
    <a href="{{ $form_url }}" class="btn btn_01">상품 등록</a>
    <span class="btn_ov01"><span class="ov_txt">전체 {{ number_format($total) }}개 · {{ $page }}/{{ $total_page }}</span></span>
</form>

<!--
    행 안에 <form style="display:contents"> 를 넣지 않는다 — tbody/tr 사이에 낀 form 은
    HTML 파서가 밖으로 밀어낼 수 있어(브라우저마다 다르게) 표 레이아웃이 깨질 위험이 있다.
    대신 표 밖에 인라인 저장용 hidden form 을 하나만 두고, 행의 저장 버튼이 JS 로 그 행의
    입력값만 모아 이 폼에 채운 뒤 제출한다.
-->
<form method="post" action="{{ $update_url }}" id="cart_inline_form" style="display:none">
<input type="hidden" name="token" id="f_token" value="">
<input type="hidden" name="it_id" id="f_it_id" value="">
<input type="hidden" name="sk_id" id="f_sk_id" value="">
<input type="hidden" name="sk_price" id="f_sk_price" value="">
<input type="hidden" name="sk_qty" id="f_sk_qty" value="">
<input type="hidden" name="it_show" id="f_it_show" value="">
<input type="hidden" name="ret_q" value="{{ $q }}">
<input type="hidden" name="ret_ca_id" value="{{ $ca_id }}">
<input type="hidden" name="ret_page" value="{{ $page }}">
</form>

<table class="tbl_head01 tbl_wrap">
    <thead>
    <tr><th>번호</th><th>이미지</th><th>상품</th><th>판매가</th><th>재고</th><th>노출</th><th>저장</th><th>수정</th></tr>
    </thead>
    <tbody>

    @foreach ($items as $it)
    <tr>
        <td>{{ $it['it_id'] }}</td>
        <td>
            @php $imgs = cart_item_images((int)$it['it_id']); @endphp

            @if (count($imgs))
            <img src="{{ G5_DATA_URL }}/cart/item/{{ $imgs[0]['im_file'] }}" alt="" style="max-height:44px">
            @endif

        </td>
        <td class="td_left">
            <a href="{{ $form_url }}?w=u&it_id={{ $it['it_id'] }}"><strong>{{ $it['it_name'] }}</strong></a>
            <br><span class="txt_id">{{ $it['it_code'] }} · SKU {{ count($it['skus']) }}종</span>
        </td>

        @if ($it['single'])
        <td><input type="text" data-role="sk_price" value="{{ $it['skus'][0]['sk_price'] }}" size="9" style="text-align:right"></td>
        <td><input type="text" data-role="sk_qty" value="{{ $it['skus'][0]['sk_qty'] }}" size="6" style="text-align:right"></td>
        @else
        <td style="text-align:right">{{ number_format($it['it_price']) }}~</td>
        <td style="text-align:right">{{ number_format($it['it_stock']) }}</td>
        @endif

        <td><input type="checkbox" data-role="it_show" {{ $it['it_show'] ? 'checked' : '' }}></td>
        <td><button type="button" class="btn_submit btn" onclick="cartInlineSave({{ $it['it_id'] }}, {{ $it['single'] ? $it['skus'][0]['sk_id'] : 0 }}, this)">저장</button></td>
        <td><a href="{{ $form_url }}?w=u&it_id={{ $it['it_id'] }}" class="btn btn_02">수정</a></td>
    </tr>
    @endforeach

    </tbody>
</table>

@if ($total_page > 1)
<nav class="pg_wrap">
    <span class="pg">

    @for ($p = max(1, $page - 4); $p <= min($total_page, $page + 4); $p++)
    @php $link = $self_url.'?'.http_build_query(array('q' => $q, 'ca_id' => $ca_id, 'page' => $p)); @endphp
    <a href="{{ $link }}" class="pg_page {{ $p === $page ? 'pg_current' : '' }}">{{ $p }}</a>
    @endfor

    </span>
</nav>
@endif

<script>
// 행의 판매가·재고(단일 SKU 상품만) · 노출 체크박스를 모아 표 밖 hidden form 에 채워 제출한다.
// 저장 버튼이 type=button 이라 admin.js 의 클릭 위임(form input:submit, form button:submit)이
// 걸리지 않는다 — delete_confirm() 과 같은 방식으로 get_ajax_token() 을 직접 불러 토큰을 채운다.
function cartInlineSave(itId, skId, btn) {
    var tr = btn.closest('tr');
    var token = get_ajax_token();
    if (!token) {
        alert('토큰 정보가 올바르지 않습니다.');
        return;
    }
    document.getElementById('f_token').value = token;
    document.getElementById('f_it_id').value = itId;
    document.getElementById('f_sk_id').value = skId;
    var priceEl = tr.querySelector('[data-role="sk_price"]');
    var qtyEl = tr.querySelector('[data-role="sk_qty"]');
    document.getElementById('f_sk_price').value = priceEl ? priceEl.value : '';
    document.getElementById('f_sk_qty').value = qtyEl ? qtyEl.value : '';
    var showEl = tr.querySelector('[data-role="it_show"]');
    document.getElementById('f_it_show').value = (showEl && showEl.checked) ? 1 : 0;
    document.getElementById('cart_inline_form').submit();
}
</script>
