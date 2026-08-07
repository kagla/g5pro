<form method="get" action="{{ $self_url }}" class="local_sch01 local_sch">
    <select name="ca_id">
        <option value="0">전체 분류</option>

        @foreach ($categories as $c)
        <option value="{{ $c['ca_id'] }}" {{ $ca_id === (int)$c['ca_id'] ? 'selected' : '' }}>{{ str_repeat('— ', $c['ca_depth'] - 1) }}{{ $c['ca_name'] }}</option>
        @endforeach

    </select>
    <input type="text" name="q" value="{{ $q }}" placeholder="상품명 또는 상품코드" class="frm_input">
    <button type="submit" class="btn_submit btn">검색</button>

    <select name="per" id="per_select">

        @foreach ($per_options as $po)
        <option value="{{ $po }}" {{ $per === $po ? 'selected' : '' }}>{{ $po === 30 ? '기본(30개)' : $po.'개씩' }}</option>
        @endforeach

    </select>
    <span class="btn_ov01"><span class="ov_txt">전체 {{ number_format($total) }}개 · {{ $page }}/{{ $total_page }}</span></span>

    {{-- 작업 버튼은 검색과 같은 줄 오른쪽 끝. [선택 저장]은 아래 목록 폼(cart_list_form)을
         제출해야 해서 type=button + JS 다 — 이 폼(GET 검색) 안에 있으므로 그냥 submit 하면
         검색으로 가 버린다. 토큰은 인라인 저장이 쓰던 방식대로 직접 채운다. --}}
    <span style="float:right">
        <button type="button" class="btn_submit btn" onclick="cartListSave()">선택 저장</button>
        <a href="{{ $form_url }}" class="btn btn_01">상품 등록</a>
    </span>
</form>

{{-- 표 전체가 폼 하나다 — 행마다 폼을 넣으면(tr 사이 form) 브라우저가 밖으로 밀어내 표가 깨진다.
     행 값은 전부 [행번호] 키로 보내 체크한 행만 골라 저장한다(미체크 체크박스가 빠져도 안 밀린다).
     제출 버튼이 여럿이라 Enter 는 트리 순서상 첫 버튼으로 간다 — 그래서 [선택 저장]을 표 위에
     먼저 두고, 되돌릴 수 없는 행 삭제 버튼은 그 뒤에 둔다. --}}
<form method="post" action="{{ $update_url }}" id="cart_list_form">
<input type="hidden" name="token" value="">
<input type="hidden" name="ret_q" value="{{ $q }}">
<input type="hidden" name="ret_ca_id" value="{{ $ca_id }}">
<input type="hidden" name="ret_page" value="{{ $page }}">
<input type="hidden" name="ret_per" value="{{ $per }}">


<table class="tbl_head01 tbl_wrap">
    <thead>
    <tr><th><label><input type="checkbox" id="chk_all"> 전체</label></th><th>상품코드</th><th>이미지</th><th>상품</th><th>판매가</th><th>재고</th><th>노출</th><th>관리</th></tr>
    </thead>
    <tbody>

    @foreach ($items as $it)
    @php $i = $loop->index; @endphp

    <tr>
        <td>
            <input type="checkbox" name="chk[]" value="{{ $i }}">
            <input type="hidden" name="it_id[{{ $i }}]" value="{{ $it['it_id'] }}">
            <input type="hidden" name="sk_id[{{ $i }}]" value="{{ $it['single'] ? $it['skus'][0]['sk_id'] : 0 }}">
        </td>
        <td>{{ $it['it_code'] !== '' ? $it['it_code'] : '-' }}</td>
        <td>
            @php $imgs = cart_item_images((int)$it['it_id']); @endphp

            @if (count($imgs))
            <a href="{{ cart_url('item.php', array('code' => $it['it_code'])) }}" target="_blank"><img src="{{ G5_DATA_URL }}/cart/item/{{ $imgs[0]['im_file'] }}" alt="{{ $it['it_name'] }} 상품보기" style="max-height:44px"></a>
            @endif

        </td>
        <td class="td_left">
            <a href="{{ $form_url }}?w=u&it_id={{ $it['it_id'] }}"><strong>{{ $it['it_name'] }}</strong></a>
            <br><span class="txt_id">#{{ $it['it_id'] }} · SKU {{ count($it['skus']) }}종</span>
        </td>

        @if ($it['single'])
        <td><input type="text" name="sk_price[{{ $i }}]" value="{{ $it['skus'][0]['sk_price'] }}" size="9" style="text-align:right"></td>
        <td><input type="text" name="sk_qty[{{ $i }}]" value="{{ $it['skus'][0]['sk_qty'] }}" size="6" style="text-align:right"></td>
        @else
        <td style="text-align:right">{{ number_format($it['it_price']) }}~</td>
        <td style="text-align:right">{{ number_format($it['it_stock']) }}</td>
        @endif

        <td><input type="checkbox" name="it_show[{{ $i }}]" value="1" {{ $it['it_show'] ? 'checked' : '' }}></td>
        <td style="white-space:nowrap">
            <a href="{{ $form_url }}?w=u&it_id={{ $it['it_id'] }}" class="btn btn_02">수정</a>
            <button type="submit" name="del_it_id" value="{{ $it['it_id'] }}" class="btn btn_02"
                onclick="return confirm('이 상품을 삭제할까요?\n옵션·재고 이력·이미지·분류 연결이 함께 지워집니다.\n팔린 적 있는 상품은 삭제되지 않습니다(노출을 꺼서 숨기세요).')">삭제</button>
        </td>
    </tr>
    @endforeach

    </tbody>
</table>

</form>

@if ($total_page > 1)
<nav class="pg_wrap">
    <span class="pg">

    @for ($p = max(1, $page - 4); $p <= min($total_page, $page + 4); $p++)
    @php $link = $self_url.'?'.http_build_query(array('q' => $q, 'ca_id' => $ca_id, 'per' => $per, 'page' => $p)); @endphp
    <a href="{{ $link }}" class="pg_page {{ $p === $page ? 'pg_current' : '' }}">{{ $p }}</a>
    @endfor

    </span>
</nav>
@endif

<script>
// 검색줄의 [선택 저장] — 목록 폼은 아래에 따로 있어서 직접 제출한다.
// admin.js 는 '제출 버튼 클릭'에만 토큰을 채워 주므로(폼 submit 이벤트가 아니다) 여기서 직접 넣는다.
function cartListSave() {
    var token = get_ajax_token();
    if (!token) { alert('토큰 정보가 올바르지 않습니다.'); return; }
    $('#cart_list_form input[name="token"]').val(token);
    $('#cart_list_form').trigger('submit');
}

$(function () {
    // 개수 선택은 고르는 즉시 반영 — 1페이지부터 다시 본다
    $('#per_select').on('change', function () {
        var $f = $(this).closest('form');
        $f.find('input[name="page"]').remove();
        $f.trigger('submit');
    });

    // 목록 폼 안에서 Enter — 첫 제출 버튼이 행 삭제라 그대로 두면 위험하다. 저장으로 돌린다.
    $('#cart_list_form').on('keydown', 'input[type="text"]', function (e) {
        if (e.which === 13) { e.preventDefault(); cartListSave(); }
    });

    // 머리글 전체 체크 — 행 값을 고쳐도 체크를 잊으면 저장이 안 되므로, 입력칸을 건드리면
    // 그 행을 자동으로 체크해 준다(고쳤는데 안 저장되는 헛걸음 방지)
    $('#chk_all').on('change', function () {
        $('input[name="chk[]"]').prop('checked', this.checked);
    });
    $('#cart_list_form tbody').on('input change', 'input[type="text"], input[type="checkbox"]', function () {
        var $tr = $(this).closest('tr');
        if (!$(this).is('input[name="chk[]"]')) $tr.find('input[name="chk[]"]').prop('checked', true);
    });
});
</script>
