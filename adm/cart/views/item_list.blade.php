<form method="get" action="{{ $self_url }}" class="local_sch01 local_sch">
    <select name="ca_id">
        <option value="0">전체 분류</option>

        @foreach ($categories as $c)
        <option value="{{ $c['ca_id'] }}" {{ $ca_id === (int)$c['ca_id'] ? 'selected' : '' }}>{{ str_repeat('— ', $c['ca_depth'] - 1) }}{{ $c['ca_name'] }}</option>
        @endforeach

    </select>
    <select name="show">
        <option value="">노출여부 전체</option>
        <option value="1" {{ $show === '1' ? 'selected' : '' }}>노출만</option>
        <option value="0" {{ $show === '0' ? 'selected' : '' }}>숨김만</option>
    </select>
    <input type="text" name="q" value="{{ $q }}" placeholder="상품명 또는 상품코드" class="frm_input">
    <button type="submit" class="btn_submit btn">검색</button>

    <select name="per" id="per_select">

        @foreach ($per_options as $po)
        <option value="{{ $po }}" {{ $per === $po ? 'selected' : '' }}>{{ $po === $per_options[0] ? '기본('.$po.'개)' : $po.'개씩' }}</option>
        @endforeach

    </select>
    <span class="btn_ov01"><span class="ov_txt">전체 {{ number_format($total) }}개 · {{ $page }}/{{ $total_page }}</span></span>

    {{-- 작업 버튼은 검색과 같은 줄 오른쪽 끝. 두 가지를 지켜야 한다:
         ① btn_submit 클래스를 쓰지 않는다 — 순정의 .local_sch01 .btn_submit 이 이 폼 안의
            btn_submit 을 전부 30x30 돋보기 아이콘(글자 숨김)으로 바꿔 버린다(검색 버튼용 규칙).
         ② [선택 저장]은 아래 목록 폼(cart_list_form)을 제출해야 해서 type=button + JS 다 —
            이 폼(GET 검색) 안에 있어 그냥 submit 하면 검색으로 가 버린다. 토큰도 직접 채운다. --}}
    <span style="float:right">
        <button type="button" class="btn btn_01" onclick="cartListSave()">선택 저장</button>
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
<input type="hidden" name="ret_show" value="{{ $show }}">


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
        <td>

            @if ($it['it_code'] !== '')
            <a href="{{ cart_url('item.php', array('code' => $it['it_code'])) }}" target="_blank" title="사용자 화면에서 보기">{{ $it['it_code'] }}</a>
            @else
            -
            @endif

        </td>
        {{-- 이미지 칸 — 눌러서 그 자리에서 바로 갈아 끼운다.
             올린 사진이 대표가 되므로 바뀐 것이 이 칸에서 바로 보인다.
             file 입력에 name 을 주지 않는 이유 — 이 표는 통째로 폼 하나라(cart_list_form),
             이름이 있으면 [선택 저장]에 딸려 나간다. 업로드는 아래 JS 가 따로 보낸다. --}}
        <td>
            <label class="it-img {{ $it['thumb_url'] !== '' ? '' : 'it-img-empty' }}" title="눌러서 사진 올리기">
                @if ($it['thumb_url'] !== '')
                <img src="{{ $it['thumb_url'] }}" alt="{{ $it['it_name'] }}">
                @else
                <span class="it-img-none">사진<br>추가</span>
                @endif
                <input type="file" accept="image/*" class="it-img-file" data-it-id="{{ $it['it_id'] }}">
            </label>
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
{{-- 처음·이전·다음·맨끝은 순정 pg_* 클래스(아이콘) 그대로 — 첫/끝 페이지에서는 감춘다 --}}
@php $qs = array('q' => $q, 'ca_id' => $ca_id, 'per' => $per, 'show' => $show); @endphp

<nav class="pg_wrap">
    <span class="pg">

    @if ($page > 1)
    <a href="{{ $self_url.'?'.http_build_query($qs + array('page' => 1)) }}" class="pg_page pg_start">처음</a>
    <a href="{{ $self_url.'?'.http_build_query($qs + array('page' => $page - 1)) }}" class="pg_page pg_prev">이전</a>
    @endif

    @for ($p = max(1, $page - 4); $p <= min($total_page, $page + 4); $p++)
    <a href="{{ $self_url.'?'.http_build_query($qs + array('page' => $p)) }}" class="pg_page {{ $p === $page ? 'pg_current' : '' }}">{{ $p }}</a>
    @endfor

    @if ($page < $total_page)
    <a href="{{ $self_url.'?'.http_build_query($qs + array('page' => $page + 1)) }}" class="pg_page pg_next">다음</a>
    <a href="{{ $self_url.'?'.http_build_query($qs + array('page' => $total_page)) }}" class="pg_page pg_end">맨끝</a>
    @endif

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

    // 이미지 칸 — 고르는 즉시 올리고 그 자리 사진만 바꾼다.
    // 토큰은 쓸 때마다 새로 받는다. check_admin_token() 이 세션 값을 지우므로
    // 미리 받아 두면 [선택 저장]과 서로 무효로 만든다.
    $('#cart_list_form tbody').on('change', '.it-img-file', function () {
        var input = this,
            file  = input.files && input.files[0],
            $cell = $(input).closest('.it-img');
        if (!file) return;

        var token = get_ajax_token();
        if (!token) { alert('토큰 정보가 올바르지 않습니다.'); input.value = ''; return; }

        var fd = new FormData();
        fd.append('token', token);
        fd.append('it_id', $(input).data('itId'));
        fd.append('im_file', file);

        $cell.addClass('is-busy');
        $.ajax({
            type: 'POST',
            url: '{{ $image_upload_url }}',
            data: fd,
            processData: false,
            contentType: false,
            cache: false,
            dataType: 'json'
        }).done(function (res) {
            if (!res || !res.ok) { alert((res && res.msg) ? res.msg : '올리지 못했습니다.'); return; }
            // 캐시된 옛 사진이 나오지 않게 주소 뒤에 시각을 붙인다
            var src = res.url + (res.url.indexOf('?') < 0 ? '?' : '&') + 't=' + (new Date()).getTime();
            var $img = $cell.find('img');
            if ($img.length) $img.attr('src', src);
            else $cell.find('.it-img-none').replaceWith($('<img>').attr({src: src, alt: ''}));
        }).fail(function () {
            alert('올리지 못했습니다. 파일 크기나 로그인 상태를 확인해 주세요.');
        }).always(function () {
            $cell.removeClass('is-busy');
            input.value = '';   // 같은 파일을 다시 골라도 change 가 나게 비운다
        });
    });
});
</script>

<style>
/* 이미지 칸 — 라벨 전체가 파일 고르기 버튼이다 */
.it-img {
    position: relative; display: inline-flex; align-items: center; justify-content: center;
    width: 56px; height: 46px; cursor: pointer; border-radius: 6px;
    border: 1px solid #d5d9e0; background: #fff; overflow: hidden;
}
/* 사진에도 따로 라운드를 준다 — 사진이 칸보다 작게 앉는 비율이면(가로로 긴 사진 등)
   칸의 라운드가 사진 모서리를 깎지 못해 각진 채로 남는다 */
.it-img img { display: block; max-width: 100%; max-height: 44px; border-radius: 4px; }
.it-img:hover { border-color: #3b7ddd; }
/* 사진이 없는 칸 — 여기를 누르면 된다는 것이 보이게 점선으로 비워 둔다 */
.it-img-none { font-size: 11px; line-height: 1.25; color: #98a2b3; text-align: center; }
.it-img-empty { border-style: dashed; background: #fafbfc; }
.it-img input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
/* 올리는 동안 — 눌러도 반응 없게 막고 흐리게 */
.it-img.is-busy { opacity: .45; pointer-events: none; }
</style>
