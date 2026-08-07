<div class="local_desc01 local_desc">
    <p>왼쪽에서 분류를 고르면 그 분류에 <strong>직접 연결된</strong> 상품이 가운데에 나옵니다.
       오른쪽에서 상품을 검색해 체크하고 일괄 추가하세요. 상품은 여러 분류에 속할 수 있고,
       아무 분류에도 안 속할 수도 있습니다. 하위 분류 소속 상품은 그 분류를 선택해 관리합니다.</p>
</div>

<style>
.cm-wrap { display: flex; gap: 16px; align-items: flex-start; flex-wrap: wrap; }
.cm-tree { flex: 0 0 260px; }
.cm-linked { flex: 1 1 auto; min-width: 300px; }
.cm-search { flex: 0 0 340px; }
.cm-list { max-height: 62vh; overflow-y: auto; border: 1px solid #d8dde3; background: #fff; }
.cm-cat { display: block; border-bottom: 1px solid #e6eaef; background: #fff;
    padding: 6px 8px; color: inherit; text-decoration: none; white-space: nowrap;
    overflow: hidden; text-overflow: ellipsis; }
.cm-cat:last-child { border-bottom: 0; }
.cm-cat.selected { background: #E5EFFF; box-shadow: inset 3px 0 0 #2563EB; }
.cm-cat .cm-cnt { color: #888; font-size: 0.92em; }
.cm-cat .cm-toggle { display: inline-block; width: 16px; text-align: center;
    color: #5b6673; cursor: pointer; user-select: none; font-size: 0.85em; }
.cm-cat .cm-toggle.leaf { cursor: default; visibility: hidden; }
.cm-row-form { display: inline; }
</style>

<div class="cm-wrap">

<div class="cm-tree">
    <div class="cm-list">

        @foreach ($categories as $c)
        <a class="cm-cat {{ (int)$c['ca_id'] === $sel_id ? 'selected' : '' }}"
           href="{{ $self_url }}?ca_id={{ $c['ca_id'] }}"
           data-id="{{ $c['ca_id'] }}" data-depth="{{ $c['ca_depth'] }}" data-path="{{ $c['ca_path'] }}"
           style="padding-left:{{ ($c['ca_depth'] - 1) * 18 + 8 }}px">
            <span class="cm-toggle {{ isset($has_child[$c['ca_id']]) ? '' : 'leaf' }}"
                  data-id="{{ $c['ca_id'] }}" title="하위 분류 접기/펼치기">▼</span>
            {{ $c['ca_name'] }}
            <span class="cm-cnt">{{ isset($counts[$c['ca_id']]) ? number_format($counts[$c['ca_id']]) : 0 }}개</span>
        </a>
        @endforeach

    </div>
</div>

@if ($selected)
<div class="cm-linked">
    @php $total_linked = isset($counts[$sel_id]) ? $counts[$sel_id] : 0; @endphp
    <h2 class="h2_frm">"{{ $selected['ca_name'] }}" 연결 상품 {{ number_format($total_linked) }}개{{ $total_linked > count($linked) ? ' (최신 '.count($linked).'개만 표시)' : '' }}</h2>
    <table class="tbl_head01 tbl_wrap">
        <thead>
        <tr><th>번호</th><th>코드</th><th>상품명</th><th>가격</th><th>노출</th><th>해제</th></tr>
        </thead>
        <tbody>

        @foreach ($linked as $r)
        <tr>
            <td>{{ $r['it_id'] }}</td>
            <td>{{ $r['it_code'] }}</td>
            <td class="td_left">{{ $r['it_name'] }}</td>
            <td class="td_num">{{ number_format($r['it_price']) }}</td>
            <td>{{ (int)$r['it_show'] ? '노출' : '숨김' }}</td>
            <td>
                <form method="post" action="{{ $action_url }}" class="cm-row-form">
                    <input type="hidden" name="token" value="">
                    <input type="hidden" name="w" value="del">
                    <input type="hidden" name="ca_id" value="{{ $sel_id }}">
                    <input type="hidden" name="it_id" value="{{ $r['it_id'] }}">
                    <input type="hidden" name="q" value="{{ $q }}">
                    <button type="submit" class="btn btn_02">해제</button>
                </form>
            </td>
        </tr>
        @endforeach

        @if (!count($linked))
        <tr><td colspan="6" class="empty_table">연결된 상품이 없습니다.</td></tr>
        @endif

        </tbody>
    </table>
    <div><a href="{{ $category_url }}?ca_id={{ $sel_id }}" class="btn btn_01">분류 설정으로</a></div>
</div>

<div class="cm-search">
    <h2 class="h2_frm">상품 찾아 추가</h2>
    <form method="get" action="{{ $self_url }}">
        <input type="hidden" name="ca_id" value="{{ $sel_id }}">
        <input type="text" name="q" value="{{ $q }}" placeholder="상품명 또는 코드" class="frm_input">
        <button type="submit" class="btn_submit btn">검색</button>
    </form>

    @if ($q !== '')
    <form method="post" action="{{ $action_url }}">
        <input type="hidden" name="token" value="">
        <input type="hidden" name="w" value="add">
        <input type="hidden" name="ca_id" value="{{ $sel_id }}">
        <input type="hidden" name="q" value="{{ $q }}">
        <table class="tbl_head01 tbl_wrap">
            <thead>
            <tr><th><label><input type="checkbox" id="cm_all"> 전체</label></th><th>코드</th><th>상품명</th></tr>
            </thead>
            <tbody>

            @foreach ($found as $r)
            <tr>
                <td>

                    @if ($r['already'])
                    <span class="cm-cnt">연결됨</span>
                    @else
                    <input type="checkbox" name="it_ids[]" value="{{ $r['it_id'] }}">
                    @endif

                </td>
                <td>{{ $r['it_code'] }}</td>
                <td class="td_left">{{ $r['it_name'] }}</td>
            </tr>
            @endforeach

            @if (!count($found))
            <tr><td colspan="3" class="empty_table">검색 결과가 없습니다.</td></tr>
            @endif

            </tbody>
        </table>
        <button type="submit" class="btn_submit btn">체크한 상품을 "{{ $selected['ca_name'] }}"에 추가</button>
    </form>
    @endif

</div>
@else
<div class="cm-linked">
    <div class="local_desc02 local_desc"><p>왼쪽에서 분류를 선택하세요.</p></div>
</div>
@endif

</div>

<script>
$(function () {
    $('#cm_all').on('change', function () {
        $('input[name="it_ids[]"]').prop('checked', this.checked);
    });

    // ---- 접기/펼치기 ----
    // 분류관리 화면과 같은 저장소를 써서 두 화면의 펼침 상태가 따로 놀지 않는다.
    var STORE = 'cart_ca_collapsed';
    var collapsed = {};
    try { collapsed = JSON.parse(localStorage.getItem(STORE)) || {}; } catch (e) { collapsed = {}; }

    function saveCollapsed() {
        try { localStorage.setItem(STORE, JSON.stringify(collapsed)); } catch (e) {}
    }

    function applyCollapse() {
        var hide_depth = 0;   // 0 = 감출 것 없음, n = n 단보다 깊은 줄은 감춘다
        $('.cm-cat').each(function () {
            var $i = $(this);
            var depth = parseInt($i.attr('data-depth'), 10);
            if (hide_depth && depth > hide_depth) { $i.hide(); return; }
            hide_depth = 0;
            $i.show();
            var id = String($i.data('id'));
            var $t = $i.find('.cm-toggle');
            if (!$t.hasClass('leaf')) $t.text(collapsed[id] ? '▲' : '▼');
            if (collapsed[id]) hide_depth = depth;
        });
    }

    // 선택한 분류가 접힌 상위 안에 묻혀 있으면 그 위쪽을 전부 펼친다(자기 자신은 그대로)
    var $sel = $('.cm-cat.selected');
    if ($sel.length) {
        var ancestors = $.grep(String($sel.attr('data-path') || '').split('/'), function (v) { return v !== ''; });
        ancestors.pop();
        $.each(ancestors, function (i, id) { delete collapsed[id]; });
        saveCollapsed();
    }
    applyCollapse();
    // 선택 분류가 스크롤 박스 밖에 있으면 가운데로 — 긴 트리에서 선택 위치를 잃지 않게
    if ($sel.length) $sel[0].scrollIntoView({ block: 'center' });

    $('.cm-toggle').on('click', function (e) {
        e.preventDefault();    // 링크(분류 선택)로 넘어가지 않게
        e.stopPropagation();
        var $t = $(this);
        if ($t.hasClass('leaf')) return;
        var id = String($t.data('id'));
        if (collapsed[id]) delete collapsed[id]; else collapsed[id] = 1;
        saveCollapsed();
        applyCollapse();
    });
});
</script>
