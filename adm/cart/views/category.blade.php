<div class="local_desc01 local_desc">
    <p>왼쪽 트리에서 분류를 클릭해 선택하고, <strong>끌어 놓아 순서와 부모를 바꿉니다</strong> —
       다른 분류의 위/아래 가장자리에 놓으면 그 위치로, 한가운데에 놓으면 그 분류의 하위로 들어갑니다.
       제목·분류코드·설명 등은 오른쪽 패널에서 고칩니다. 최대 {{ CART_CA_MAX_DEPTH }}단.</p>
</div>

<style>
.ca-wrap { display: flex; gap: 16px; align-items: flex-start; flex-wrap: wrap; }
.ca-tree { flex: 0 0 400px; }
.ca-panel { flex: 1 1 auto; min-width: 320px; }
.ca-add { margin-bottom: 10px; }
.ca-add input[type=text] { width: 150px; }
.ca-list { max-height: 62vh; overflow-y: auto; border: 1px solid #d8dde3; background: #fff; }
.ca-item { border-bottom: 1px solid #e6eaef; background: #fff; padding: 6px 8px;
    cursor: pointer; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ca-item:last-child { border-bottom: 0; }
.ca-bulk { margin: 8px 0; }
.ca-item .ca-toggle { display: inline-block; width: 14px; margin-right: 2px; text-align: center;
    color: #5b6673; cursor: pointer; user-select: none; font-size: 0.8em; }
.ca-item .ca-toggle.leaf { cursor: default; visibility: hidden; }
.ca-item.selected { background: #E5EFFF; box-shadow: inset 3px 0 0 #2563EB; }
.ca-item .ca-handle { cursor: grab; margin-right: 2px; color: #b3bac2; user-select: none; }
.ca-item .ca-cnt { color: #888; font-size: 0.92em; }
.ca-item .ca-view { color: #888; text-decoration: none; }
.ca-item .ca-view:hover { color: #2563EB; text-decoration: underline; }
.ca-item .ca-off { display: inline-block; margin-left: 5px; padding: 0 6px; border-radius: 9px;
    background: #e9edf2; color: #6b7683; font-size: 0.78em; vertical-align: 1px; }
.ca-item.off > strong { color: #98a1ab; font-weight: 600; }
.ca-item.drag-before { border-top: 2px solid #2563EB; }
.ca-item.drag-after { border-bottom: 2px solid #2563EB; }
.ca-item.drag-inside { background: #E5EFFF; }
.ca-item.dragging { opacity: .45; }
.ca-thumb { height: 48px; vertical-align: middle; border-radius: 4px; }
</style>

<div class="ca-wrap">

<div class="ca-tree">
    {{-- 넣을 자리를 select 로 보여 준다 — 제출 버튼이 둘이면 Enter 가 늘 첫 버튼(최상위)으로
         가서, 하위로 넣으려던 분류가 조용히 최상위에 생기기 때문 --}}
    <form method="post" action="{{ $action_url }}" class="ca-add">
        <input type="hidden" name="token" value="">
        <input type="hidden" name="w" value="a">
        <input type="hidden" name="ca_id" value="{{ $sel_id }}">
        <input type="text" name="ca_name" placeholder="새 분류 이름" required>

        @if ($can_add_child)
        <select name="ca_parent">
            <option value="{{ $sel_id }}">"{{ $selected['ca_name'] }}" 하위에</option>
            <option value="0">최상위에</option>
        </select>
        @else
        <input type="hidden" name="ca_parent" value="0">
        <span class="ca-cnt">최상위에</span>
        @endif

        <button type="submit" class="btn_submit btn">추가</button>

        @if ($selected && !$can_add_child)
        <span class="ca-cnt">— "{{ $selected['ca_name'] }}"는 {{ CART_CA_MAX_DEPTH }}단이라 하위를 못 만듭니다</span>
        @endif

    </form>

    <div class="ca-bulk">
        <button type="button" class="btn btn_02" id="ca_expand">모두 펼치기</button>
        <button type="button" class="btn btn_02" id="ca_collapse">모두 접기</button>
    </div>

    <div class="ca-list">

        @foreach ($categories as $c)
        @php
            $kid = isset($child_count[$c['ca_id']]) ? (int)$child_count[$c['ca_id']] : 0;
            $items = isset($counts[$c['ca_id']]) ? (int)$counts[$c['ca_id']] : 0;
        @endphp

        <div class="ca-item {{ (int)$c['ca_id'] === $sel_id ? 'selected' : '' }} {{ (int)$c['ca_show'] ? '' : 'off' }}"
             draggable="true"
             data-id="{{ $c['ca_id'] }}" data-parent="{{ $c['ca_parent'] }}"
             data-depth="{{ $c['ca_depth'] }}" data-path="{{ $c['ca_path'] }}"
             data-href="{{ $self_url }}?ca_id={{ $c['ca_id'] }}"
             style="padding-left:{{ ($c['ca_depth'] - 1) * 22 + 8 }}px">
            <span class="ca-handle" title="끌어서 이동">⠿</span>
            <span class="ca-toggle {{ $kid ? '' : 'leaf' }}"
                  data-id="{{ $c['ca_id'] }}" title="하위 분류 접기/펼치기">▼</span>
            <strong>{{ $c['ca_name'] }}</strong>
            <span class="ca-cnt">

                @if (in_array((int)$c['ca_id'], $hidden_ids, true))
                [{{ $c['ca_code'] }}]
                @else
                <a class="ca-view" href="{{ cart_url('list.php', array('ca' => $c['ca_code'])) }}"
                   target="_blank" title="사용자 화면에서 이 분류 보기">[{{ $c['ca_code'] }}]</a>
                @endif

                · {{ $kid ? '하위 '.$kid.' · ' : '' }}상품 {{ number_format($items) }}개</span>

            @if (!(int)$c['ca_show'])
            <span class="ca-off">숨김</span>
            @endif

        </div>
        @endforeach

    </div>

</div>

<div class="ca-panel">

    @if ($selected)
    <form method="post" action="{{ $action_url }}" enctype="multipart/form-data">
    <input type="hidden" name="token" value="">
    <input type="hidden" name="w" value="u">
    <input type="hidden" name="ca_id" value="{{ $selected['ca_id'] }}">
    <div class="tbl_frm01 tbl_wrap">
    <table>
        <caption>{{ $selected['ca_name'] }} 설정</caption>
        <tbody>
        <tr>
            <th scope="row">이름</th>
            <td><input type="text" name="ca_name" value="{{ $selected['ca_name'] }}" required class="frm_input" size="30"></td>
        </tr>
        <tr>
            <th scope="row">분류코드</th>
            <td>
                <input type="text" name="ca_code" value="{{ $selected['ca_code'] }}" class="frm_input" size="24" maxlength="20" pattern="[A-Za-z0-9_-]{1,20}">

                @if (!in_array((int)$selected['ca_id'], $hidden_ids, true))
                <a href="{{ cart_url('list.php', array('ca' => $selected['ca_code'])) }}" target="_blank" class="btn btn_01">사용자 화면에서 보기</a>
                @endif

                <span>영문·숫자·하이픈·언더라인 1~20자 · 목록 주소와 CSV 에 쓰입니다</span>
            </td>
        </tr>
        <tr>
            <th scope="row">설명</th>
            <td><input type="text" name="ca_desc" value="{{ $selected['ca_desc'] }}" class="frm_input" size="60" placeholder="목록 상단 소개문"></td>
        </tr>
        <tr>
            <th scope="row">기본 정렬</th>
            <td>
                <select name="ca_sort">
                    <option value="" {{ $selected['ca_sort'] === '' ? 'selected' : '' }}>기본(신상품)</option>
                    <option value="new" {{ $selected['ca_sort'] === 'new' ? 'selected' : '' }}>신상품순</option>
                    <option value="low" {{ $selected['ca_sort'] === 'low' ? 'selected' : '' }}>낮은가격순</option>
                    <option value="high" {{ $selected['ca_sort'] === 'high' ? 'selected' : '' }}>높은가격순</option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row">순서</th>
            <td><input type="number" name="ca_order" value="{{ $selected['ca_order'] }}" class="frm_input" style="width:90px"> <span>같은 부모 안 정렬값 · 드래그로도 바뀝니다</span></td>
        </tr>
        <tr>
            <th scope="row">노출</th>
            <td><label><input type="checkbox" name="ca_show" value="1" {{ $selected['ca_show'] ? 'checked' : '' }}> 쇼핑몰에 노출</label> <span>숨기면 하위 분류·소속 상품도 함께 숨습니다(다른 노출 분류에 속한 상품은 그쪽에서 보입니다)</span></td>
        </tr>
        <tr>
            <th scope="row">이미지</th>
            <td>

                @if ($selected['ca_img'] !== '')
                <img src="{{ cart_category_image_url($selected['ca_img']) }}" alt="" class="ca-thumb">
                <button type="submit" name="w" value="imgdel" class="btn btn_02"
                    onclick="return confirm('분류 이미지를 삭제할까요?')">이미지 삭제</button>
                @else
                <input type="file" name="ca_img_file" accept="image/*">
                @endif

            </td>
        </tr>
        </tbody>
    </table>
    </div>
    <div class="btn_confirm01 btn_confirm">
        <button type="submit" class="btn_submit btn">저장</button>
        <a href="{{ $link_url }}?ca_id={{ $selected['ca_id'] }}" class="btn btn_01">상품 연결</a>
        <button type="submit" name="w" value="d" class="btn btn_02"
            onclick="return confirm('분류를 삭제할까요? 하위 분류나 연결 상품이 있으면 거부됩니다.')">삭제</button>
    </div>
    </form>
    @else
    <div class="local_desc02 local_desc"><p>왼쪽에서 분류를 클릭하면 여기서 수정합니다.</p></div>
    @endif

</div>

</div>

<script>
// 클릭 = 선택(이동), 드래그 = 순서·부모 변경. 대상의 위/아래 1/3 은 그 위치로(before/after),
// 한가운데 1/3 은 그 분류의 하위로(inside). 서버(cart_category_move)가 깊이 제한과
// 자기 자손 이동 금지를 최종 검증하고, 성공하면 화면을 새로고침해 트리를 다시 그린다.
$(function () {
    var $drag = null;
    var moved = false;

    // ---- 접기/펼치기 ----
    // 접힌 분류 id 를 브라우저에 남겨(localStorage) 저장·선택으로 화면이 새로 그려져도 유지한다.
    // 목록은 트리 순서로 평탄화돼 있으므로, 접힌 분류를 만나면 그보다 깊은 줄을 전부 감추면 된다.
    var STORE = 'cart_ca_collapsed';
    var collapsed = {};
    try { collapsed = JSON.parse(localStorage.getItem(STORE)) || {}; } catch (e) { collapsed = {}; }

    function saveCollapsed() {
        try { localStorage.setItem(STORE, JSON.stringify(collapsed)); } catch (e) {}
    }

    function applyCollapse() {
        var hide_depth = 0;   // 0 = 감출 것 없음, n = n 단보다 깊은 줄은 감춘다
        $('.ca-item').each(function () {
            var $i = $(this);
            var depth = parseInt($i.attr('data-depth'), 10);
            if (hide_depth && depth > hide_depth) { $i.hide(); return; }
            hide_depth = 0;
            $i.show();
            var id = String($i.data('id'));
            var $t = $i.find('.ca-toggle');
            if (!$t.hasClass('leaf')) $t.text(collapsed[id] ? '▲' : '▼');
            if (collapsed[id]) hide_depth = depth;
        });
    }

    // 선택한 분류가 접힌 상위 안에 묻혀 있으면 그 위쪽을 전부 펼친다(자기 자신은 그대로)
    var $sel = $('.ca-item.selected');
    if ($sel.length) {
        var ancestors = $.grep(String($sel.attr('data-path') || '').split('/'), function (v) { return v !== ''; });
        ancestors.pop();
        $.each(ancestors, function (i, id) { delete collapsed[id]; });
        saveCollapsed();
    }
    applyCollapse();
    // 선택 분류가 스크롤 박스 밖에 있으면 가운데로 — 긴 트리에서 선택 위치를 잃지 않게
    if ($sel.length) $sel[0].scrollIntoView({ block: 'center' });

    $('.ca-toggle').on('click', function (e) {
        e.stopPropagation();   // 줄 클릭(=선택 이동)까지 번지지 않게
        var $t = $(this);
        if ($t.hasClass('leaf')) return;
        var id = String($t.data('id'));
        if (collapsed[id]) delete collapsed[id]; else collapsed[id] = 1;
        saveCollapsed();
        applyCollapse();
    });

    $('#ca_collapse').on('click', function () {
        $('.ca-toggle').not('.leaf').each(function () { collapsed[String($(this).data('id'))] = 1; });
        saveCollapsed();
        applyCollapse();
    });
    $('#ca_expand').on('click', function () {
        collapsed = {};
        saveCollapsed();
        applyCollapse();
    });

    $('.ca-item').on('click', function () {
        if (moved) { moved = false; return; }
        location.href = $(this).data('href');
    });

    // 분류코드 = 사용자 화면 바로가기(새 탭) — 줄 클릭(선택 이동)까지 번지지 않게
    $('.ca-view').on('click', function (e) { e.stopPropagation(); });

    $('.ca-item').on('dragstart', function (e) {
        $drag = $(this);
        moved = true;
        $(this).addClass('dragging');
        e.originalEvent.dataTransfer.effectAllowed = 'move';
        e.originalEvent.dataTransfer.setData('text/plain', $(this).data('id'));
    }).on('dragend', function () {
        $('.ca-item').removeClass('dragging drag-before drag-after drag-inside');
        $drag = null;
        setTimeout(function () { moved = false; }, 0);
    });

    function zone($t, e) {
        var r = $t[0].getBoundingClientRect();
        var y = (e.originalEvent.clientY - r.top) / r.height;
        return y < 0.33 ? 'before' : (y > 0.67 ? 'after' : 'inside');
    }

    $('.ca-item').on('dragover', function (e) {
        if (!$drag || this === $drag[0]) return;
        e.preventDefault();
        e.originalEvent.dataTransfer.dropEffect = 'move';
        var z = zone($(this), e);
        $(this).removeClass('drag-before drag-after drag-inside').addClass('drag-' + z);
    }).on('dragleave', function () {
        $(this).removeClass('drag-before drag-after drag-inside');
    }).on('drop', function (e) {
        if (!$drag || this === $drag[0]) return;
        e.preventDefault();
        var $t = $(this);
        var z = zone($t, e);
        var caId = $drag.data('id');
        var parent, after;

        if (z === 'inside') {
            parent = $t.data('id');
            after = -1; // 형제 목록에 없는 값 → 서버가 맨 뒤로 붙인다
            // 접힌 분류에 넣으면 새로고침 뒤 안 보인다 — 받은 쪽을 펼쳐 둔다
            delete collapsed[String(parent)];
            saveCollapsed();
        } else {
            parent = $t.data('parent');
            if (z === 'after') {
                after = $t.data('id');
            } else {
                // before: 같은 부모의 바로 앞 형제 뒤에 = 그 형제 id, 없으면 맨 앞(0)
                after = 0;
                $t.prevAll('.ca-item:visible').each(function () {
                    if ($(this).data('parent') === parent && $(this).data('id') !== caId) {
                        after = $(this).data('id');
                        return false;
                    }
                });
            }
        }

        var token = get_ajax_token();
        if (!token) { alert('토큰 정보가 올바르지 않습니다.'); return; }
        $.post('{{ $action_url }}',
            { token: token, w: 'move', ca_id: caId, parent: parent, after: after }, null, 'json')
            .done(function (r) {
                if (r && r.ok) { location.reload(); return; }
                alert((r && r.error) || '이동에 실패했습니다.');
                $('.ca-item').removeClass('drag-before drag-after drag-inside');
            })
            .fail(function () { alert('이동 요청에 실패했습니다.'); });
    });
});
</script>
