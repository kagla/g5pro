<form method="post" action="{{ $action_url }}" enctype="multipart/form-data" id="cart_item_form">
<input type="hidden" name="token" value="">
<input type="hidden" name="w" value="{{ $w }}">
<input type="hidden" name="it_id" value="{{ $item['it_id'] }}">

<div class="tbl_frm01 tbl_wrap">
<table>
    <caption>상품 기본</caption>
    <tbody>
    <tr>
        <th scope="row">상품 이름</th>
        <td><input type="text" name="it_name" value="{{ $item['it_name'] }}" required class="frm_input" size="60"></td>
    </tr>
    <tr>
        <th scope="row">분류</th>
        <td>

            @foreach ($categories as $c)
            <label style="display:inline-block; margin:2px 14px 2px 0">
                <input type="checkbox" name="ca_ids[]" value="{{ $c['ca_id'] }}" {{ in_array((int)$c['ca_id'], $ca_ids, true) ? 'checked' : '' }}>
                {{ str_repeat('— ', $c['ca_depth'] - 1) }}{{ $c['ca_name'] }}
            </label>
            @endforeach

            <div><span>여러 개 선택 가능 · 선택 없음 = 분류 없이 단독 노출</span></div>
        </td>
    </tr>
    <tr>
        <th scope="row">상품코드</th>
        <td><input type="text" name="it_code" value="{{ $item['it_code'] }}" class="frm_input" size="30" placeholder="비우면 자동(P번호)"> <span>CSV 일괄 작업의 기준 키</span></td>
    </tr>
    <tr>
        <th scope="row">노출</th>
        <td><label><input type="checkbox" name="it_show" value="1" {{ $item['it_show'] ? 'checked' : '' }}> 쇼핑몰에 노출</label></td>
    </tr>
    <tr>
        <th scope="row">검색 키워드</th>
        <td><input type="text" name="it_keyword" value="{{ $item['it_keyword'] }}" class="frm_input" size="60" placeholder="쉼표 없이 띄어쓰기로"></td>
    </tr>
    <tr>
        <th scope="row">상세 설명</th>
        <td><textarea name="it_content" rows="10" style="width:100%">{{ $item['it_content'] }}</textarea></td>
    </tr>
    </tbody>
</table>
</div>

<h2 class="h2_frm">옵션·SKU</h2>
<div class="local_desc02 local_desc">
    <p>옵션이 없으면 그대로 두세요 — 저장하면 단일 SKU 가 자동 생성됩니다. 옵션 조합을 만들려면 옵션명·값을 넣고 [조합 생성]을 누르세요. 재고 칸을 바꾸면 저장 시 그 값으로 설정되고, 전 변경이 재고 이력에 남습니다.</p>
</div>

<div id="opt_builder">
    <input type="text" id="opt_name1" placeholder="옵션명 (예: 색상)" class="frm_input">
    <input type="text" id="opt_vals1" placeholder="값들 (예: 빨강,파랑)" class="frm_input" size="40">
    <br>
    <input type="text" id="opt_name2" placeholder="옵션명2 (선택)" class="frm_input">
    <input type="text" id="opt_vals2" placeholder="값들" class="frm_input" size="40">
    <button type="button" class="btn btn_02" onclick="cartBuildSkus()">조합 생성</button>
</div>

<table class="tbl_head01 tbl_wrap" id="sku_table">
    <thead>
    <tr><th>옵션</th><th>SKU 코드</th><th>판매가</th><th>재고</th><th>바코드</th><th>사용</th><th>삭제</th></tr>
    </thead>
    <tbody>

    @foreach ($skus as $s)
    <tr>
        <td>{{ $s['opt_label'] }}<input type="hidden" name="sk_id[]" value="{{ $s['sk_id'] }}"><input type="hidden" name="sk_option[]" value="{{ $s['sk_option'] }}"></td>
        <td><input type="text" name="sk_code[]" value="{{ $s['sk_code'] }}" size="16"></td>
        <td><input type="text" name="sk_price[]" value="{{ $s['sk_price'] }}" size="10" style="text-align:right"></td>
        <td><input type="text" name="sk_qty[]" value="{{ $s['sk_qty'] }}" size="8" style="text-align:right"></td>
        <td><input type="text" name="sk_barcode[]" value="{{ $s['sk_barcode'] }}" size="14"></td>
        <td><input type="checkbox" name="sk_use[{{ $loop->index }}]" value="1" {{ $s['sk_use'] ? 'checked' : '' }}></td>
        <td><label><input type="checkbox" name="sk_del[]" value="{{ $s['sk_id'] }}"> 삭제</label></td>
    </tr>
    @endforeach

    </tbody>
</table>

<h2 class="h2_frm">이미지</h2>

@if (count($images))
<table class="tbl_head01 tbl_wrap">
    <thead>
    <tr><th>미리보기</th><th>대표</th><th>삭제</th></tr>
    </thead>
    <tbody>

    @foreach ($images as $img)
    <tr>
        <td><img src="{{ $image_url_base.$img['im_file'] }}" alt="" style="max-height:80px"></td>
        <td><input type="radio" name="im_main" value="{{ $img['im_id'] }}" {{ $img['im_main'] ? 'checked' : '' }}></td>
        <td><label><input type="checkbox" name="im_del[]" value="{{ $img['im_id'] }}"> 삭제</label></td>
    </tr>
    @endforeach

    </tbody>
</table>
@endif

<p><input type="file" name="im_files[]" multiple accept="image/*"></p>

<div class="btn_confirm01 btn_confirm">
    <button type="submit" class="btn_submit btn">저장</button>
    <a href="{{ $list_url }}" class="btn btn_02">목록</a>
</div>
</form>

<script>
// 옵션명·값 입력으로 SKU 행을 만든다 — 서버 계약: sk_id[]=0, sk_option[]=JSON
// sk_use[] 는 체크 안 되면 폼에서 통째로 빠져 배열 인덱스가 밀린다(브라우저 공통 동작).
// 그래서 sk_use 는 행마다 명시 인덱스를 쓴다: 서버 렌더 행은 뷰의 $loop->index(0..N-1),
// JS 로 새로 만든 행은 기존 행 수(N)부터 이어지는 skRowIndex 로 맞춘다 — 둘 다
// item_form_update.php 의 foreach ($sk_ids as $i => $sid) 가 도는 $i 와 같은 순서(DOM 순서)를 전제한다.
var skRowIndex = {{ count($skus) }};

function cartBuildSkus() {
    var sets = [];
    $.each([['#opt_name1', '#opt_vals1'], ['#opt_name2', '#opt_vals2']], function (i, pair) {
        var name = $.trim($(pair[0]).val());
        var vals = $.map($(pair[1]).val().split(','), function (v) { return $.trim(v) || null; });
        if (name && vals.length) sets.push({name: name, vals: vals});
    });
    if (!sets.length) { alert('옵션명과 값을 입력하세요.'); return; }

    var combos = [{}];
    $.each(sets, function (i, set) {
        var next = [];
        $.each(combos, function (j, c) {
            $.each(set.vals, function (k, v) {
                var copy = $.extend({}, c);
                copy[set.name] = v;
                next.push(copy);
            });
        });
        combos = next;
    });

    var $tbody = $('#sku_table tbody');
    $.each(combos, function (i, c) {
        var label = $.map(c, function (v, k) { return k + '=' + v; }).join(' / ');
        var idx = skRowIndex++;
        var $tr = $(
            '<tr><td>' +
            '<input type="hidden" name="sk_id[]" value="0">' +
            '<input type="hidden" name="sk_option[]"></td>' +
            '<td><input type="text" name="sk_code[]" value="" size="16" placeholder="자동"></td>' +
            '<td><input type="text" name="sk_price[]" value="0" size="10" style="text-align:right"></td>' +
            '<td><input type="text" name="sk_qty[]" value="0" size="8" style="text-align:right"></td>' +
            '<td><input type="text" name="sk_barcode[]" value="" size="14"></td>' +
            '<td><input type="checkbox" name="sk_use[' + idx + ']" value="1" checked></td>' +
            '<td>신규</td></tr>'
        );
        // 옵션 라벨·JSON 은 사용자 입력이라 문자열 결합 대신 text()/val() 로 넣는다
        $tr.find('td').first().prepend(document.createTextNode(label));
        $tr.find('input[name="sk_option[]"]').val(JSON.stringify(c));
        $tbody.append($tr);
    });
}
</script>
