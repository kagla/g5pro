<style>
/* 상세 설명 에디터 높이 — 이 화면에 맞춰 250px 로 잡는다(플러그인 기본은 200px).
   CKEditor 가 인라인 높이를 넣으므로 !important 가 필요하고, 이 폼 안으로만 한정해
   게시판·FAQ 등 다른 화면의 에디터는 건드리지 않는다.
   순정 플러그인(plugin/editor/ckeditor4)은 손대지 않는다. */
#cart_item_form .cke_contents { height: 250px !important; }
#cart_item_form textarea#it_content { height: 250px; }
</style>

<form method="post" action="{{ $action_url }}" enctype="multipart/form-data" id="cart_item_form">
<input type="hidden" name="token" value="">
<input type="hidden" name="w" value="{{ $w }}">
<input type="hidden" name="it_id" value="{{ $item['it_id'] }}">

{{-- 저장·목록은 위아래 같은 자리에 둔다 — 폼이 길어 아래까지 내려가지 않고도 저장하게.
     위 버튼도 같은 폼의 제출이라 Enter 가 어느 쪽으로 잡히든 결과가 같다(동작이 하나뿐). --}}
<div class="btn_confirm01 btn_confirm cart-form-btns">
    <a href="{{ $list_url }}" class="btn btn_02">목록</a>
    <button type="submit" class="btn_submit btn">저장</button>
</div>

<div class="tbl_frm01 tbl_wrap">
<table>
    <caption>상품 기본</caption>
    <tbody>
    <tr>
        <th scope="row">상품 이름</th>
        <td>
            <input type="text" name="it_name" value="{{ $item['it_name'] }}" required class="frm_input" size="60">

            @if ($view_url !== '')
            <a href="{{ $view_url }}" class="btn btn_01" target="_blank">바로가기</a>
            @endif

        </td>
    </tr>
    <tr>
        <th scope="row">분류</th>
        <td>

            {{-- 체크박스가 한 줄로 흘러 계층이 안 보였다 — 한 줄 한 분류인 다중 선택 목록으로.
                 선택이 하나도 없으면 브라우저가 ca_ids 를 아예 안 보내는데, 저장 쪽이 그 경우를
                 빈 배열(= 분류 없음)로 처리하므로 그대로 맞는다 --}}
            <select name="ca_ids[]" multiple size="{{ min(25, max(10, count($categories))) }}" style="min-width:320px; min-height:260px">

                @foreach ($categories as $c)
                <option value="{{ $c['ca_id'] }}" {{ in_array((int)$c['ca_id'], $ca_ids, true) ? 'selected' : '' }}>{{ str_repeat('　', $c['ca_depth'] - 1) }}{{ $c['ca_depth'] > 1 ? '└ ' : '' }}{{ $c['ca_name'] }} [{{ $c['ca_code'] }}]</option>
                @endforeach

            </select>
            <div>
                <span>Ctrl(⌘)+클릭으로 여러 개 선택 · 선택 없음 = 분류 없이 단독 노출</span>
                <a href="{{ $category_item_url }}" class="btn btn_02" target="_blank">상품 연결</a>
                <a href="{{ $category_url }}" class="btn btn_02" target="_blank">분류 관리</a>
            </div>
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
        <td>{!! $editor_html !!}</td>
    </tr>
    </tbody>
</table>
</div>

<h2 class="h2_frm">옵션·SKU</h2>
<div class="local_desc02 local_desc">
    <p>옵션이 없으면 그대로 두세요 — 저장하면 단일 SKU 가 자동 생성됩니다. 옵션 조합을 만들려면 옵션명·값을 넣고 [조합 생성]을 누르세요. 재고 칸을 바꾸면 저장 시 그 값으로 설정되고, 전 변경이 재고 이력에 남습니다.</p>
    <p>칸 옆 <strong>▼</strong> 를 누르면 그 칸의 값이 <strong>아래 행에 모두</strong> 채워집니다. 값이 같은 조합을 한 번에 채울 때 쓰세요. (SKU 코드는 서로 달라야 해서 채우기가 없습니다)</p>
    <p>자주 쓰는 옵션 묶음은 <strong>[현재 조합 저장]</strong> 으로 이름 붙여 두면 다음 상품에서 <strong>[불러오기]</strong> 로 그대로 씁니다.</p>
</div>

<div id="opt_builder">
    {{-- 저장해 둔 조합 — 고르면 아래 입력칸이 채워진다. 바로 만들지 않는 이유:
         값을 조금 고쳐 쓰는 경우가 흔하고, 조합 생성은 되돌리기가 번거롭다 --}}
    <p class="opt-preset">
        <select id="opt_preset">
            <option value="">저장된 조합 불러오기…</option>

            @foreach ($presets as $p)
            <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
            @endforeach

        </select>
        <button type="button" class="btn btn_02" onclick="cartPresetLoad()">불러오기</button>
        <button type="button" class="btn btn_02" onclick="cartPresetSave()">현재 조합 저장</button>
        <button type="button" class="btn btn_02" onclick="cartPresetDelete()">삭제</button>
    </p>
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
        <td><input type="text" name="sk_price[]" value="{{ $s['sk_price'] }}" size="8" style="text-align:right"><button type="button" class="fill-dn" title="아래 행에 모두 채우기">▼</button></td>
        <td><input type="text" name="sk_qty[]" value="{{ $s['sk_qty'] }}" size="6" style="text-align:right"><button type="button" class="fill-dn" title="아래 행에 모두 채우기">▼</button></td>
        <td><input type="text" name="sk_barcode[]" value="{{ $s['sk_barcode'] }}" size="12"><button type="button" class="fill-dn" title="아래 행에 모두 채우기">▼</button></td>
        <td><input type="checkbox" name="sk_use[{{ $loop->index }}]" value="1" {{ $s['sk_use'] ? 'checked' : '' }}><button type="button" class="fill-dn" title="아래 행에 모두 채우기">▼</button></td>
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

<div class="btn_confirm01 btn_confirm cart-form-btns">
    <a href="{{ $list_url }}" class="btn btn_02">목록</a>
    <button type="submit" class="btn_submit btn">저장</button>
</div>
</form>

<script>
// 옵션명·값 입력으로 SKU 행을 만든다 — 서버 계약: sk_id[]=0, sk_option[]=JSON
// sk_use[] 는 체크 안 되면 폼에서 통째로 빠져 배열 인덱스가 밀린다(브라우저 공통 동작).
// 그래서 sk_use 는 행마다 명시 인덱스를 쓴다: 서버 렌더 행은 뷰의 $loop->index(0..N-1),
// JS 로 새로 만든 행은 기존 행 수(N)부터 이어지는 skRowIndex 로 맞춘다 — 둘 다
// item_form_update.php 의 foreach ($sk_ids as $i => $sid) 가 도는 $i 와 같은 순서(DOM 순서)를 전제한다.
var skRowIndex = {{ count($skus) }};

// 아래로 채우기 버튼 — 서버가 그린 행과 같은 모양으로 새 행에도 붙인다
var FILL_BTN = '<button type="button" class="fill-dn" title="아래 행에 모두 채우기">▼</button>';

// ▼ — 누른 칸의 값을 같은 열의 아래 행에 모두 넣는다(엑셀 채우기와 같은 방향).
// 위임으로 걸어 [조합 생성]이 나중에 만든 행에서도 그대로 동작한다.
$(function () {
    $('#sku_table').on('click', '.fill-dn', function () {
        var $td = $(this).closest('td'),
            col = $td.index(),
            $src = $td.find('input').first(),
            isChk = $src.is(':checkbox'),
            val = isChk ? $src.prop('checked') : $src.val(),
            hit = 0;

        $td.closest('tr').nextAll('tr').each(function () {
            var $t = $(this).children('td').eq(col).find('input').first();
            if (!$t.length) return;
            if (isChk) $t.prop('checked', val);
            else $t.val(val);
            // 무엇이 바뀌었는지 눈에 보이게 잠깐 표시 — 표가 길면 클릭이 먹었는지 알 수 없다
            $t.addClass('fill-hit');
            setTimeout(function () { $t.removeClass('fill-hit'); }, 700);
            hit++;
        });
        if (!hit) alert('아래에 채울 행이 없습니다. 맨 아래 행입니다.');
    });
});

// ---- 저장된 옵션 조합 ----
// 서버가 내려 준 목록을 그대로 들고 있다가, 저장·삭제 응답이 오면 통째로 갈아 끼운다.
// HEX_TAG — 이름에 닫는 스크립트 태그가 들어가도 이 블록이 끊기지 않게(관리자 입력이지만 값이 여기 그대로 실린다).
// 그 태그를 주석에조차 그대로 적으면 안 된다 — HTML 파서는 주석 안이라도 그 자리에서 스크립트를 닫는다.
var cartPresets = {!! json_encode($presets, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!};

// 지금 입력칸에 적힌 옵션 묶음 — 저장할 때도, 조합을 만들 때도 같은 규칙으로 읽는다
function cartReadOptionSets() {
    var sets = [];
    $.each([['#opt_name1', '#opt_vals1'], ['#opt_name2', '#opt_vals2']], function (i, pair) {
        var name = $.trim($(pair[0]).val());
        var vals = $.map($(pair[1]).val().split(','), function (v) { return $.trim(v) || null; });
        if (name && vals.length) sets.push({name: name, vals: vals});
    });
    return sets;
}

function cartPresetRedraw() {
    var $sel = $('#opt_preset'), keep = $sel.val();
    $sel.empty().append($('<option>').val('').text('저장된 조합 불러오기…'));
    $.each(cartPresets, function (i, p) {
        $sel.append($('<option>').val(p.id).text(p.name));   // 이름은 text() 로 — 사용자 입력이다
    });
    $sel.val(keep);
}

function cartPresetLoad() {
    var id = $('#opt_preset').val();
    if (!id) { alert('불러올 조합을 고르세요.'); return; }
    var hit = null;
    $.each(cartPresets, function (i, p) { if (String(p.id) === String(id)) { hit = p; return false; } });
    if (!hit) { alert('조합을 찾지 못했습니다. 새로고침해 주세요.'); return; }

    // 입력칸은 두 벌뿐이라 앞의 두 묶음만 채운다. 나머지는 비워 옛 값이 섞이지 않게 한다
    var pairs = [['#opt_name1', '#opt_vals1'], ['#opt_name2', '#opt_vals2']];
    $.each(pairs, function (i, pair) {
        var set = hit.sets[i];
        $(pair[0]).val(set ? set.name : '');
        $(pair[1]).val(set ? set.vals.join(',') : '');
    });
}

// 저장·삭제 공통 — 토큰은 쓸 때마다 새로 받는다(check_admin_token 이 세션 값을 지운다)
function cartPresetPost(data, done) {
    var token = get_ajax_token();
    if (!token) { alert('토큰 정보가 올바르지 않습니다.'); return; }
    $.ajax({
        type: 'POST',
        url: '{{ $preset_url }}',
        data: $.extend({token: token}, data),
        dataType: 'json'
    }).done(function (res) {
        if (!res || !res.ok) { alert((res && res.msg) ? res.msg : '처리하지 못했습니다.'); return; }
        cartPresets = res.presets || [];
        cartPresetRedraw();
        if (done) done();
    }).fail(function () {
        alert('처리하지 못했습니다. 로그인 상태를 확인해 주세요.');
    });
}

function cartPresetSave() {
    var sets = cartReadOptionSets();
    if (!sets.length) { alert('옵션명과 값을 먼저 입력하세요.'); return; }

    // 고른 조합이 있으면 그 이름을 먼저 보여 준다 — 같은 이름으로 저장하면 덮어쓰기(갱신)다
    var cur = '';
    var id = $('#opt_preset').val();
    $.each(cartPresets, function (i, p) { if (String(p.id) === String(id)) { cur = p.name; return false; } });

    var name = window.prompt('이 조합을 어떤 이름으로 저장할까요?\n같은 이름이면 덮어씁니다.', cur);
    if (name === null) return;
    name = $.trim(name);
    if (!name) { alert('조합 이름을 입력하세요.'); return; }

    cartPresetPost({w: 'w', op_name: name, op_data: JSON.stringify(sets)}, function () {
        // 방금 저장한 것을 골라 둔다 — 이어서 [삭제]나 재저장을 할 때 헷갈리지 않게
        $.each(cartPresets, function (i, p) { if (p.name === name) { $('#opt_preset').val(p.id); return false; } });
        alert('저장했습니다.');
    });
}

function cartPresetDelete() {
    var id = $('#opt_preset').val();
    if (!id) { alert('지울 조합을 고르세요.'); return; }
    var name = $('#opt_preset option:selected').text();
    if (!confirm('저장된 조합 "' + name + '" 을 지울까요?\n이미 만든 옵션·SKU 는 그대로 남습니다.')) return;
    cartPresetPost({w: 'd', op_id: id});
}

function cartBuildSkus() {
    var sets = cartReadOptionSets();
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
            '<td><input type="text" name="sk_price[]" value="0" size="8" style="text-align:right">' + FILL_BTN + '</td>' +
            '<td><input type="text" name="sk_qty[]" value="0" size="6" style="text-align:right">' + FILL_BTN + '</td>' +
            '<td><input type="text" name="sk_barcode[]" value="" size="12">' + FILL_BTN + '</td>' +
            '<td><input type="checkbox" name="sk_use[' + idx + ']" value="1" checked>' + FILL_BTN + '</td>' +
            '<td>신규</td></tr>'
        );
        // 옵션 라벨·JSON 은 사용자 입력이라 문자열 결합 대신 text()/val() 로 넣는다
        $tr.find('td').first().prepend(document.createTextNode(label));
        $tr.find('input[name="sk_option[]"]').val(JSON.stringify(c));
        $tbody.append($tr);
    });
}

// 상세 설명 에디터 — 제출 순간 에디터 내용을 폼 필드로 내린다.
// 어떤 에디터를 쓰는지는 환경설정이 정하므로, 동기화 코드도 서버가 그 에디터 것으로 넣어 준다
// (순정 글쓰기 화면과 같은 방식). CKEditor 는 자체 제출 훅으로도 한 번 더 맞춘다.
$(function () {
    $('#cart_item_form').on('submit', function () {
        if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.it_content) {
            CKEDITOR.instances.it_content.updateElement();
        }
        {!! $editor_js !!}
    });
});
</script>

<style>
/* 저장·목록 — 오른쪽에 두고 서로 떨어뜨린다(붙어 있으면 잘못 누른다) */
.cart-form-btns { text-align: right; }
.cart-form-btns button, .cart-form-btns a { margin-left: 8px; }
/* 위쪽 버튼 줄은 첫 표와 너무 붙지 않게 */
.tbl_frm01 + .cart-form-btns, .cart-form-btns + .tbl_frm01 { margin-top: 10px; }
/* 저장된 조합 줄 — 옵션 입력칸 위에 한 줄 */
#opt_builder .opt-preset { margin: 0 0 8px; }
/* 아래로 채우기 — 테두리·배경 없는 글자 하나. 버튼처럼 보이면 표가 조잡해진다 */
#sku_table .fill-dn {
    margin-left: 2px; padding: 0; border: 0; background: none;
    font-size: 12px; line-height: 1; color: #b0b8c1; cursor: pointer;
}
#sku_table .fill-dn:hover { color: #1D5FD1; }
/* 채워진 칸을 잠깐 물들여 무엇이 바뀌었는지 보이게 한다 */
#sku_table .fill-hit { background: #fff6cc; }
</style>
