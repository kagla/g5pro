<form method="get" action="{{ $self_url }}" class="local_sch01 local_sch">
    {{-- 등록 버튼은 검색줄 오른쪽 끝(순정 목록 화면 관례). float 이라 소스에서 먼저 나와야
         같은 줄에 붙는다 — .local_sch 가 :after 로 clear 하므로 줄 높이는 안 무너진다 --}}
    <a href="{{ $form_url }}" class="btn btn_01" style="float:right">상품 등록</a>
    <select name="ca_id">
        <option value="0">전체 분류</option>

        @foreach ($categories as $c)
        <option value="{{ $c['ca_id'] }}" {{ $ca_id === (int)$c['ca_id'] ? 'selected' : '' }}>{{ str_repeat('— ', $c['ca_depth'] - 1) }}{{ $c['ca_name'] }}</option>
        @endforeach

    </select>
    <input type="text" name="q" value="{{ $q }}" placeholder="상품명 또는 상품코드" class="frm_input">
    <button type="submit" class="btn_submit btn">검색</button>
    <span class="btn_ov01"><span class="ov_txt">전체 {{ number_format($total) }}개 · {{ $page }}/{{ $total_page }}</span></span>
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

<div class="btn_add01">
    <button type="submit" class="btn_submit btn">선택 저장</button>
    <span class="txt_id">체크한 상품의 판매가·재고·노출을 한 번에 저장합니다</span>
</div>

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

<div class="btn_confirm01 btn_confirm">
    <button type="submit" class="btn_submit btn">선택 저장</button>
</div>
</form>

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
$(function () {
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
