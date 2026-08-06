<div class="local_desc01 local_desc">
    <p>분류는 최대 {{ CART_CA_MAX_DEPTH }}단입니다. <strong>행을 끌어 놓아 순서와 부모를 바꿉니다</strong> —
       다른 분류의 위/아래 가장자리에 놓으면 그 위치로, 한가운데에 놓으면 그 분류의 하위로 들어갑니다.
       이름·설명·정렬·이미지·노출은 행에서 고치고 저장을 누르세요.</p>
</div>

<form method="post" action="{{ $action_url }}">
<input type="hidden" name="token" value="">
<input type="hidden" name="w" value="">
<table class="tbl_head01 tbl_wrap">
    <thead>
    <tr><th>새 분류</th><th>처리</th></tr>
    </thead>
    <tbody>
    <tr>
        <td class="td_left">
            <select name="ca_parent">
                <option value="0">최상위</option>

                @foreach ($parent_options as $p)
                <option value="{{ $p['ca_id'] }}">{{ str_repeat('— ', $p['ca_depth'] - 1) }}{{ $p['ca_name'] }}</option>
                @endforeach

            </select>
            <input type="text" name="ca_name" placeholder="새 분류 이름">
            <label><input type="checkbox" name="ca_show" value="1" checked> 노출</label>
        </td>
        <td><button type="submit" class="btn_submit btn">추가</button></td>
    </tr>
    </tbody>
</table>
</form>

{{-- 기능용 최소 스타일 — 드래그 피드백(놓일 자리 표시)만 --}}
<style>
.ca-item { margin-top: -1px; }
.ca-item .ca-handle { cursor: grab; padding: 0 6px; color: #888; user-select: none; }
.ca-item.drag-before > form > table { border-top: 2px solid #2563EB; }
.ca-item.drag-after > form > table { border-bottom: 2px solid #2563EB; }
.ca-item.drag-inside td { background: #E5EFFF !important; }
.ca-item.dragging { opacity: .45; }
.ca-thumb { height: 32px; vertical-align: middle; border-radius: 4px; }
</style>

@foreach ($categories as $c)
<div class="ca-item" draggable="true" data-id="{{ $c['ca_id'] }}" data-parent="{{ $c['ca_parent'] }}">
<form method="post" action="{{ $action_url }}" enctype="multipart/form-data">
<input type="hidden" name="token" value="">
<input type="hidden" name="ca_id" value="{{ $c['ca_id'] }}">
<table class="tbl_head01 tbl_wrap">
    <tbody>
    <tr>
        <td class="td_left" style="padding-left:{{ ($c['ca_depth'] - 1) * 28 + 6 }}px; white-space:nowrap">
            <span class="ca-handle" title="끌어서 이동">⠿</span>
            <input type="text" name="ca_name" value="{{ $c['ca_name'] }}" size="14">
            <span class="txt_id">#{{ $c['ca_id'] }}</span>
        </td>
        <td class="td_left"><input type="text" name="ca_desc" value="{{ $c['ca_desc'] }}" size="26" placeholder="분류 설명 (목록 상단 소개문)"></td>
        <td style="white-space:nowrap">
            <select name="ca_sort" title="기본 정렬">
                <option value="" {{ $c['ca_sort'] === '' ? 'selected' : '' }}>기본(신상품)</option>
                <option value="new" {{ $c['ca_sort'] === 'new' ? 'selected' : '' }}>신상품순</option>
                <option value="low" {{ $c['ca_sort'] === 'low' ? 'selected' : '' }}>낮은가격순</option>
                <option value="high" {{ $c['ca_sort'] === 'high' ? 'selected' : '' }}>높은가격순</option>
            </select>
        </td>
        <td style="white-space:nowrap">

            @if ($c['ca_img'] !== '')
            <img src="{{ cart_category_image_url($c['ca_img']) }}" alt="" class="ca-thumb">
            <button type="submit" name="w" value="imgdel" class="btn btn_02"
                onclick="return confirm('분류 이미지를 삭제할까요?')">이미지 삭제</button>
            @else
            <input type="file" name="ca_img_file" accept="image/*" style="width:150px">
            @endif

        </td>
        <td>{{ isset($counts[$c['ca_id']]) ? number_format($counts[$c['ca_id']]) : 0 }}개</td>
        <td><label><input type="checkbox" name="ca_show" value="1" {{ $c['ca_show'] ? 'checked' : '' }}> 노출</label></td>
        <td style="white-space:nowrap">
            <button type="submit" name="w" value="u" class="btn_submit btn">저장</button>
            <button type="submit" name="w" value="d" class="btn_02 btn"
                onclick="return confirm('분류를 삭제할까요? 하위 분류나 상품이 있으면 거부됩니다.')">삭제</button>
        </td>
    </tr>
    </tbody>
</table>
</form>
</div>
@endforeach

<script>
// 드래그로 순서·부모 변경 — 대상 행의 위/아래 1/3 은 그 위치로(before/after),
// 한가운데 1/3 은 그 분류의 하위로(inside). 서버(cart_category_move)가 깊이 제한과
// 자기 자손 이동 금지를 최종 검증하고, 성공하면 화면을 새로고침해 트리를 다시 그린다.
$(function () {
    var $drag = null;

    $('.ca-item').on('dragstart', function (e) {
        $drag = $(this);
        $(this).addClass('dragging');
        e.originalEvent.dataTransfer.effectAllowed = 'move';
        e.originalEvent.dataTransfer.setData('text/plain', $(this).data('id'));
    }).on('dragend', function () {
        $('.ca-item').removeClass('dragging drag-before drag-after drag-inside');
        $drag = null;
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
        } else {
            parent = $t.data('parent');
            if (z === 'after') {
                after = $t.data('id');
            } else {
                // before: 같은 부모의 바로 앞 형제 뒤에 = 그 형제 id, 없으면 맨 앞(0)
                after = 0;
                $t.prevAll('.ca-item').each(function () {
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
