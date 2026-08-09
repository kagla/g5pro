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
        <td class="td_left">{{ $s['opt_label'] }}<input type="hidden" name="sk_id[]" value="{{ $s['sk_id'] }}"><input type="hidden" name="sk_option[]" value="{{ $s['sk_option'] }}"></td>
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
<div class="local_desc02 local_desc">
    <p>사진을 아래 상자로 <strong>끌어다 놓으면</strong> 올릴 목록에 담깁니다. 타일을 끌어 <strong>순서를 바꾸고</strong>, <strong>맨 앞 사진이 대표</strong>가 됩니다. 사진을 누르면 원본을 크게 봅니다.</p>
    <p>담기·순서·삭제 모두 <strong>[저장]을 눌러야</strong> 실제로 반영됩니다.</p>
</div>

{{-- im_seq[] — 살아 있는 타일 순서대로의 토큰. 제출 순간 JS 가 여기에 채운다.
     기존 사진은 e:<im_id>, 새로 담은 파일은 n:<im_files[] 안에서의 인덱스>. --}}
<div id="im_posts"></div>

<div id="im_zone" class="im-zone">
    <div id="im_grid" class="im-grid">

        @foreach ($images as $img)
        <div class="im-tile{{ $loop->first ? ' is-main' : '' }}" draggable="true" data-kind="e" data-id="{{ $img['im_id'] }}" data-full="{{ $img['full_url'] }}">
            <img src="{{ $img['thumb_url'] }}" alt="">
            <span class="im-flag">대표</span>
            <span class="im-no"></span>
            <button type="button" class="im-x" title="삭제">×</button>
            <button type="button" class="im-undo">되돌리기</button>
            {{-- JS 가 꺼진 브라우저를 위한 원래 창구. JS 가 켜지면 숨기고 위 × 가 이 칸을 대신 켠다 --}}
            <label class="im-delbox"><input type="checkbox" name="im_del[]" value="{{ $img['im_id'] }}"> 삭제</label>
        </div>
        @endforeach

        <button type="button" id="im_add" class="im-add"><span>＋</span>사진 추가</button>
    </div>
    <p class="im-hint">여기로 사진을 끌어다 놓으세요 · 현재 <b id="im_count">{{ count($images) }}</b>장</p>
</div>

{{-- im_pick 은 고르는 창구(이름 없음 = 제출되지 않음), im_files 가 실제로 올라가는 목록이다.
     둘로 나눠야 여러 번 나눠 골라도 앞서 고른 파일이 날아가지 않는다. --}}
<input type="file" id="im_pick" accept="image/*" multiple class="im-off">
<input type="file" name="im_files[]" id="im_files" multiple class="im-off">
<p id="im_plain"><input type="file" name="im_files[]" multiple accept="image/*"></p>

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
            '<tr><td class="td_left">' +
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

// ---- 이미지 격자 ----
// 타일 하나가 사진 하나다. 이미 올라간 사진(kind 'e')과 방금 담은 파일(kind 'n')이 한 격자에
// 섞여 있고, 눈에 보이는 DOM 순서가 곧 저장될 순서다 — 맨 앞이 대표.
// 제출 순간 딱 한 번의 순회가 im_seq[] 히든과 im_files 의 파일 목록을 같이 만든다.
// 한 순회로 둘을 만들기 때문에 토큰의 n:<인덱스> 가 파일 목록과 어긋날 수 없다.
$(function () {
    var $zone = $('#im_zone'), $grid = $('#im_grid'), $add = $('#im_add');
    if (!$zone.length) return;

    // 골라 둔 파일을 file input 에 되돌려 넣지 못하는 브라우저면 아예 손대지 않는다 —
    // 순정 파일 선택칸(#im_plain)과 삭제 체크박스가 그대로 남아 예전 방식으로 쓸 수 있다.
    try { new DataTransfer(); } catch (e) { $('#im_pick, #im_files').remove(); return; }

    $('#im_plain').remove();
    $zone.addClass('im-on');

    var dragEl = null;

    function imTiles() { return $grid.children('.im-tile'); }
    function imLive() { return imTiles().not('.is-del'); }

    // 자리 번호와 대표 배지를 다시 붙인다 — 순서가 바뀔 때마다 부른다.
    // 지운 타일은 자리를 세지 않는다(저장하면 사라질 것이라 번호를 차지하면 헷갈린다).
    function imRelabel() {
        var $live = imLive();
        imTiles().removeClass('is-main').find('.im-no').text('');
        $live.each(function (i) {
            $(this).find('.im-no').text(i + 1);
            if (i === 0) $(this).addClass('is-main');
        });
        $('#im_count').text($live.length);
    }

    // ---- 파일 담기 ----

    function imAddTile(file) {
        var url = URL.createObjectURL(file);
        var $t = $('<div class="im-tile" draggable="true" data-kind="n">'
            + '<img alt=""><span class="im-flag">대표</span><span class="im-no"></span>'
            + '<span class="im-tag">신규</span>'
            + '<button type="button" class="im-x" title="빼기">×</button>'
            + '<button type="button" class="im-undo">되돌리기</button></div>');
        // 파일명은 사용자 입력이라 문자열 결합 대신 attr()/data() 로 넣는다
        $t.find('img').attr('src', url);
        $t.attr('title', file.name).data('file', file).data('full', url);
        $add.before($t);
    }

    function imAddFiles(files) {
        var bad = [];
        for (var i = 0; i < files.length; i++) {
            var f = files[i];
            // 폴더째 끌어오면 type 이 빈 항목이 섞여 온다 — 확장자로도 한 번 본다.
            // 진짜 이미지인지는 서버가 다시 확인하므로 여기 검사는 실수 거르기 용도다.
            if (!/^image\//.test(f.type) && !/\.(jpe?g|png|gif|webp)$/i.test(f.name)) { bad.push(f.name); continue; }
            imAddTile(f);
        }
        imRelabel();
        if (bad.length) alert('이미지가 아니라 건너뛴 파일: ' + bad.join(', '));
    }

    // 파일 선택창은 네이티브 click() 으로 연다 — jQuery trigger 는 핸들러만 부르는 것으로
    // 끝날 수 있고, 브라우저는 사용자 클릭 안에서 부른 것만 창을 열어 준다
    $add.on('click', function () { document.getElementById('im_pick').click(); });
    $('#im_pick').on('change', function () {
        imAddFiles(this.files);
        this.value = '';   // 비워야 같은 파일을 다시 골랐을 때도 change 가 난다
    });

    // ---- 빼기·되돌리기 ----
    // 지운 타일을 없애지 않고 흐리게 남긴다 — 저장 전이라면 그 자리에서 되돌린다.
    // 기존 사진은 무JS 용으로 심어 둔 im_del[] 체크박스를 그대로 켜고 끈다.

    $grid.on('click', '.im-x', function (e) {
        e.stopPropagation();
        $(this).closest('.im-tile').addClass('is-del').attr('draggable', 'false')
            .find('.im-delbox input').prop('checked', true);
        imRelabel();
    });

    $grid.on('click', '.im-undo', function (e) {
        e.stopPropagation();
        $(this).closest('.im-tile').removeClass('is-del').attr('draggable', 'true')
            .find('.im-delbox input').prop('checked', false);
        imRelabel();
    });

    // ---- 끌어서 순서 바꾸기 / 끌어다 올리기 ----
    // 두 가지가 같은 drag 이벤트를 쓴다. 밖에서 들어온 파일인지는 dataTransfer.types 로 가른다.

    function imHasFiles(dt) {
        if (!dt || !dt.types) return false;
        for (var i = 0; i < dt.types.length; i++) if (dt.types[i] === 'Files') return true;
        return false;
    }

    // 포인터가 놓인 자리 바로 뒤에 오는 타일. 없으면(맨 끝이면) null
    function imAfter(x, y) {
        var hit = null;
        imTiles().not('.is-drag').each(function () {
            var r = this.getBoundingClientRect();
            if (y < r.top) { hit = this; return false; }                                 // 윗줄로 올라간 자리
            if (y <= r.bottom && x < r.left + r.width / 2) { hit = this; return false; } // 같은 줄, 이 타일 왼쪽 절반
        });
        return hit;
    }

    $grid.on('dragstart', '.im-tile', function (e) {
        if ($(this).hasClass('is-del')) { e.preventDefault(); return; }
        dragEl = this;
        var dt = e.originalEvent.dataTransfer, el = this;
        dt.effectAllowed = 'move';
        dt.setData('text/plain', 'tile');   // 파이어폭스는 데이터를 넣어야 끌기가 시작된다
        // 지금 프레임에 흐리게 만들면 그 모습이 끌고 다니는 그림으로 굳는다 — 한 박자 뒤에
        setTimeout(function () { $(el).addClass('is-drag'); }, 0);
    });

    $grid.on('dragend', '.im-tile', function () {
        $(this).removeClass('is-drag');
        dragEl = null;
        imRelabel();
    });

    $zone.on('dragover', function (e) {
        var dt = e.originalEvent.dataTransfer;
        if (imHasFiles(dt)) {
            e.preventDefault();
            dt.dropEffect = 'copy';
            $zone.addClass('is-over');
            return;
        }
        if (!dragEl) return;
        e.preventDefault();
        dt.dropEffect = 'move';
        // 끌고 다니는 동안 실제로 자리를 옮겨 둔다 — 놓기 전에 결과가 보인다
        var after = imAfter(e.originalEvent.clientX, e.originalEvent.clientY);
        if (!after) $add.before(dragEl);
        else if (after !== dragEl) after.parentNode.insertBefore(dragEl, after);
    });

    $zone.on('dragleave', function (e) {
        // 안쪽 타일로 옮겨갈 때도 dragleave 가 난다 — 상자 밖으로 나갔을 때만 강조를 끈다
        var to = e.originalEvent.relatedTarget;
        if (to && $.contains(this, to)) return;
        $zone.removeClass('is-over');
    });

    $zone.on('drop', function (e) {
        e.preventDefault();
        $zone.removeClass('is-over');
        var dt = e.originalEvent.dataTransfer;
        if (imHasFiles(dt)) imAddFiles(dt.files);
        else imRelabel();
    });

    // 상자를 살짝 빗나가 떨어뜨리면 브라우저가 그 사진 파일로 이동해 작성 중인 폼이 통째로
    // 날아간다 — 상자 밖의 놓기는 아무 일도 하지 않게 막는다
    $(document).on('dragover drop', function (e) {
        if ($(e.target).closest('#im_zone').length) return;
        e.preventDefault();
    });

    // ---- 크게 보기 ----

    var $box = null, boxList = [], boxAt = 0;

    function imBox() {
        if ($box) return $box;
        $box = $('<div class="im-box">'
            + '<button type="button" class="im-box-btn im-box-prev" title="이전">‹</button>'
            + '<img alt="">'
            + '<button type="button" class="im-box-btn im-box-next" title="다음">›</button>'
            + '<button type="button" class="im-box-x" title="닫기">×</button>'
            + '<div class="im-box-n"></div></div>').appendTo('body');
        $box.on('click', function (e) { if (e.target === this) imBoxClose(); });
        $box.find('.im-box-x').on('click', imBoxClose);
        $box.find('.im-box-prev').on('click', function () { imBoxGo(-1); });
        $box.find('.im-box-next').on('click', function () { imBoxGo(1); });
        return $box;
    }

    function imBoxGo(step) {
        if (!boxList.length) return;
        boxAt = (boxAt + step + boxList.length) % boxList.length;
        imBox().find('img').attr('src', boxList[boxAt]);
        imBox().find('.im-box-n').text((boxAt + 1) + ' / ' + boxList.length);
    }

    function imBoxClose() {
        if ($box) $box.removeClass('is-open');
        $('body').removeClass('im-lock');
    }

    $grid.on('click', '.im-tile', function () {
        var me = this;
        if ($(me).hasClass('is-del')) return;
        boxList = [];
        boxAt = 0;
        imLive().each(function (i) {
            boxList.push($(this).data('full'));
            if (this === me) boxAt = i;
        });
        if (!boxList.length) return;
        imBox().addClass('is-open');
        $('body').addClass('im-lock');
        imBoxGo(0);
    });

    $(document).on('keydown', function (e) {
        if (!$box || !$box.hasClass('is-open')) return;
        if (e.which === 27) imBoxClose();
        else if (e.which === 37) imBoxGo(-1);
        else if (e.which === 39) imBoxGo(1);
        else return;
        e.preventDefault();
    });

    // ---- 제출 ----

    $('#cart_item_form').on('submit', function () {
        var dt = new DataTransfer(), $posts = $('#im_posts').empty();
        imTiles().each(function () {
            var $t = $(this);
            if ($t.hasClass('is-del')) return;   // 지운 것은 순서에서 빠진다(im_del 체크는 이미 켜 뒀다)
            var tok;
            if ($t.data('kind') === 'e') {
                tok = 'e:' + $t.data('id');
            } else {
                tok = 'n:' + dt.items.length;
                dt.items.add($t.data('file'));
            }
            $posts.append($('<input type="hidden" name="im_seq[]">').val(tok));
        });
        document.getElementById('im_files').files = dt.files;
    });

    imRelabel();
});
</script>

<style>
/* 저장·목록 — 오른쪽에 두고 서로 떨어뜨린다(붙어 있으면 잘못 누른다) */
/* 위아래 버튼 줄 모두 앞 요소와 띄운다 — 바로 앞이 표든 이미지 상자든 붙어 보이지 않게.
   앞 요소마다 선택자를 다는 대신 버튼 줄 자신이 여백을 갖는다(요소가 늘어도 안 깨진다) */
.cart-form-btns { margin-top: 18px; text-align: right; }
.cart-form-btns button, .cart-form-btns a { margin-left: 8px; }
/* 위쪽 버튼 줄 다음에 오는 첫 표도 붙지 않게 */
.cart-form-btns + .tbl_frm01 { margin-top: 10px; }
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

/* ---- 이미지 격자 ----
   .im-on 은 JS 가 켜졌을 때만 붙는다. 붙지 않으면 타일이 그냥 사진 목록이 되고
   칸마다 놓인 삭제 체크박스가 드러나 예전 화면과 같은 일을 할 수 있다. */
.im-off { display: none; }
.im-zone {
    padding: 14px; border: 2px dashed #d7dce3; border-radius: 12px; background: #fbfcfe;
    transition: border-color .15s, background-color .15s;
}
.im-zone.is-over { border-color: #1D5FD1; background: #eef4ff; }
/* 타일 한 변 100px 남짓 — 한 줄에 더 많이 보이는 쪽이 사진이 많은 상품에서 낫다.
   더 줄이면(75px 안팎) 무슨 사진인지 알아보기 어려워 확대 보기를 매번 열게 된다. */
.im-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; }

.im-tile {
    position: relative; aspect-ratio: 1 / 1; overflow: hidden;
    border-radius: 10px; background: #fff; box-shadow: 0 1px 3px rgba(16,24,40,.14);
    user-select: none; transition: transform .12s, box-shadow .12s;
}
/* 사진 자체는 이벤트를 받지 않는다 — 받으면 타일 대신 그림이 끌려 나가 순서 바꾸기가 깨진다 */
.im-tile img { display: block; width: 100%; height: 100%; object-fit: cover; pointer-events: none; }
.im-tile:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(16,24,40,.18); }
.im-on .im-tile { cursor: grab; }
.im-on .im-tile:active { cursor: grabbing; }
.im-tile.is-drag { opacity: .3; box-shadow: none; transform: none; }

.im-tile.is-main { outline: 2px solid #1D5FD1; outline-offset: -2px; }
/* 타일이 100px 이라 배지·버튼은 작게 — 크면 사진을 가려 무엇인지 안 보인다 */
.im-flag {
    display: none; position: absolute; left: 5px; top: 5px; padding: 2px 7px;
    border-radius: 999px; background: #1D5FD1; color: #fff; font-size: 10px; font-weight: 700; line-height: 1.4;
}
.im-tile.is-main .im-flag { display: block; }
.im-no {
    position: absolute; right: 5px; bottom: 5px; min-width: 18px; padding: 1px 5px;
    border-radius: 5px; background: rgba(16,24,40,.6); color: #fff; font-size: 10px; line-height: 1.5; text-align: center;
}
/* 번호는 JS 가 매긴다 — 비어 있으면 빈 알약만 남으므로 아예 감춘다 */
.im-no:empty { display: none; }
.im-tag {
    position: absolute; left: 5px; bottom: 5px; padding: 1px 6px;
    border-radius: 5px; background: #12b76a; color: #fff; font-size: 10px; line-height: 1.5;
}
.im-x {
    position: absolute; right: 4px; top: 4px; width: 22px; height: 22px; padding: 0;
    border: 0; border-radius: 50%; background: rgba(16,24,40,.55); color: #fff;
    font-size: 14px; line-height: 22px; cursor: pointer; opacity: 0; transition: opacity .12s, background-color .12s;
}
.im-tile:hover .im-x { opacity: 1; }
.im-x:hover { background: #d92d20; }
.im-undo {
    display: none; position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%);
    padding: 5px 9px; border: 0; border-radius: 6px; background: #344054; color: #fff;
    font-size: 11px; white-space: nowrap; cursor: pointer;
}
.im-on .im-tile.is-del .im-undo { display: block; }
.im-tile.is-del { cursor: default; }
.im-tile.is-del:hover { transform: none; box-shadow: 0 1px 3px rgba(16,24,40,.14); }
.im-tile.is-del img { filter: grayscale(1); opacity: .28; }
.im-tile.is-del .im-flag, .im-tile.is-del .im-no,
.im-tile.is-del .im-tag, .im-tile.is-del .im-x { display: none; }

.im-delbox {
    position: absolute; left: 6px; bottom: 6px; padding: 2px 6px;
    border-radius: 6px; background: rgba(255,255,255,.92); font-size: 12px;
}
.im-on .im-delbox { display: none; }

.im-add {
    display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px;
    aspect-ratio: 1 / 1; border: 2px dashed #c7cfda; border-radius: 10px; background: #fff;
    color: #667085; font-size: 11px; cursor: pointer; transition: border-color .12s, color .12s;
}
.im-add span { font-size: 20px; line-height: 1; }
.im-add:hover { border-color: #1D5FD1; color: #1D5FD1; }
.im-hint { margin: 12px 0 0; text-align: center; color: #98a2b3; font-size: 12px; }
/* 끌어다 놓기도 [사진 추가]도 JS 가 하는 일이다 — 없으면 못 지키는 약속을 걸어두지 않는다 */
.im-zone:not(.im-on) .im-add, .im-zone:not(.im-on) .im-hint { display: none; }

/* ---- 크게 보기 ----
   body 바로 밑에 붙는다(폼 바깥) — 그래서 폼 안쪽으로 선택자를 좁히지 않는다 */
.im-lock { overflow: hidden; }
.im-box {
    display: none; position: fixed; left: 0; top: 0; width: 100%; height: 100%;
    z-index: 10000; background: rgba(8,11,17,.88); align-items: center; justify-content: center;
}
.im-box.is-open { display: flex; }
.im-box img { max-width: 88vw; max-height: 88vh; object-fit: contain; border-radius: 6px; }
.im-box-btn, .im-box-x {
    position: absolute; border: 0; background: rgba(255,255,255,.12); color: #fff; cursor: pointer;
}
.im-box-btn:hover, .im-box-x:hover { background: rgba(255,255,255,.26); }
.im-box-btn { top: 50%; transform: translateY(-50%); width: 52px; height: 76px; border-radius: 10px; font-size: 34px; line-height: 1; }
.im-box-prev { left: 20px; }
.im-box-next { right: 20px; }
.im-box-x { right: 20px; top: 20px; width: 40px; height: 40px; border-radius: 50%; font-size: 22px; line-height: 1; }
.im-box-n { position: absolute; left: 0; right: 0; bottom: 22px; text-align: center; color: #cbd5e1; font-size: 13px; }
</style>
