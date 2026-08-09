<style>
#cart_dc .frm_input { height: 30px; line-height: 28px; }
#cart_dc td { text-align: center; }
#cart_dc td.td_left { text-align: left; }
#cart_dc .dc_url[readonly] { background: #f2f2f2; color: #888; }
#cart_dc .dc_grip { width: 28px; cursor: grab; color: #aaa; font-size: 15px; user-select: none; }
#cart_dc tr.is-drag { opacity: .35; }
#cart_dc tr.is-del td { opacity: .45; }
#cart_dc .dc_badge { display: inline-block; padding: 1px 7px; border-radius: 10px;
    background: #1D5FD1; color: #fff; font-size: 11px; line-height: 18px; }
#cart_dc .dc_badge_off { color: #bbb; font-size: 11px; }
#cart_dc .dc_rm { border: 0; background: none; color: #999; font-size: 15px; cursor: pointer; }
#cart_dc_add { margin: 10px 0 0; }
#cart_dc .btn_confirm { text-align: right; }
</style>

<div class="local_desc01 local_desc">
    <p>배송관리에서 고를 택배사 목록입니다. <strong>사용</strong>을 켠 것만 배송관리에 뜹니다.</p>
    <p><strong>대표 연락처</strong>는 배송이 늦거나 물건이 안 보일 때 걸 곳입니다. 모양은 자유롭게 적습니다.</p>
    <p>왼쪽 <strong>≡</strong> 를 끌어 순서를 바꿉니다. <strong>사용을 켠 것 중 맨 위</strong>가
       기본 택배사가 되어, 택배사가 아직 안 정해진 주문에 미리 선택됩니다.</p>
    <p><strong>송장조회 주소</strong>는 송장번호가 뒤에 붙는 데까지 적습니다
       (예: <code>https://trace.cjlogistics.com/next/tracking.html?wblNo=</code>).
       비워 두면 번호만 보여 주고 링크는 걸지 않습니다.</p>
    <p><strong>송장번호</strong>를 끄면 송장 대신 배송안내를 적는 수단이 됩니다
       (직접배송·퀵서비스·방문수령). 이때 조회 주소는 쓰지 않습니다.</p>
    <p>지울 것은 <strong>삭제</strong>에 표시한 뒤 저장하세요. 이미 그 택배사로 나간 주문에는
       그때 이름이 그대로 남습니다.</p>
</div>

<form method="post" action="{{ $action_url }}" id="cart_dc">
<input type="hidden" name="token" value="{{ $token }}">
{{-- dc_seq[] — 화면에 놓인 순서. 제출 직전에 JS 가 <tr> 순서대로 채운다(item_form 의 im_seq[] 관례) --}}
<div id="cart_dc_seq"></div>

<div class="tbl_head01 tbl_wrap">
    <table>
    <caption>택배사 목록</caption>
    <thead>
    <tr>
        <th scope="col"><span class="sound_only">순서 바꾸기</span>&nbsp;</th>
        <th scope="col">이름</th>
        <th scope="col">대표 연락처</th>
        <th scope="col">송장조회 주소</th>
        <th scope="col">송장번호</th>
        <th scope="col">사용</th>
        <th scope="col">기본</th>
        <th scope="col">삭제</th>
    </tr>
    </thead>
    <tbody id="cart_dc_body">

    @foreach ($rows as $r)
    <tr draggable="true" data-key="{{ $r['dc_id'] }}">
        <td class="dc_grip" title="끌어서 순서 바꾸기">≡</td>
        <td><input type="text" name="dc[{{ $r['dc_id'] }}][name]" value="{{ $r['dc_name'] }}" size="14" class="frm_input"></td>
        <td><input type="text" name="dc[{{ $r['dc_id'] }}][tel]" value="{{ $r['dc_tel'] }}" size="12" class="frm_input" placeholder="1588-0000"></td>
        <td class="td_left"><input type="text" name="dc[{{ $r['dc_id'] }}][url]" value="{{ $r['dc_url'] }}" class="frm_input dc_url" style="width:97%" placeholder="https://…" {{ (int)$r['dc_invoice'] === 1 ? '' : 'readonly' }}></td>
        <td><input type="checkbox" name="dc[{{ $r['dc_id'] }}][invoice]" value="1" class="dc_takes" {{ (int)$r['dc_invoice'] === 1 ? 'checked' : '' }}></td>
        <td><input type="checkbox" name="dc[{{ $r['dc_id'] }}][use]" value="1" class="dc_use" {{ (int)$r['dc_use'] === 1 ? 'checked' : '' }}></td>
        <td class="dc_flag"></td>
        <td><input type="checkbox" name="dc_del[]" value="{{ $r['dc_id'] }}" class="dc_del"></td>
    </tr>
    @endforeach

    </tbody>
    </table>
</div>

<p id="cart_dc_add"><button type="button" class="btn btn_02" id="cart_dc_add_btn">+ 택배사 추가</button></p>

<div class="btn_confirm01 btn_confirm">
    <button type="submit" class="btn_submit btn">저장</button>
</div>
</form>

<script>
$(function () {
    var $form = $('#cart_dc'), $body = $('#cart_dc_body'), newSeq = 0, dragEl = null;

    // ── 기본 택배사 배지 — 고르는 것이 아니라 "사용 켠 것 중 맨 위" 다.
    // 지울 줄과 이름이 빈 줄은 건너뛴다(서버가 그 둘을 없는 셈 치므로 화면도 같아야 한다).
    function markDefault() {
        $body.find('.dc_flag').empty();
        var $hit = null;
        $body.find('tr').each(function () {
            var $tr = $(this);
            if ($hit) return;
            if ($tr.find('.dc_del').is(':checked')) return;
            if ($.trim($tr.find('input[name$="[name]"]').val() || '') === '') return;
            if (!$tr.find('.dc_use').is(':checked')) return;
            $hit = $tr;
        });
        if ($hit) $hit.find('.dc_flag').html('<span class="dc_badge">기본</span>');
        else $body.find('tr:first .dc_flag').html('<span class="dc_badge_off">없음</span>');
    }

    // ── 송장번호를 안 받는 택배사는 조회주소가 쓸 데가 없다 — 흐리게 잠근다.
    // disabled 가 아니라 readonly 인 이유: disabled 면 값이 제출되지 않아, 껐다 켜면 주소가 사라진다.
    function syncUrl($cb) {
        $cb.closest('tr').find('.dc_url').prop('readonly', !$cb.is(':checked'));
    }
    function syncAll() {
        $body.find('.dc_takes').each(function () { syncUrl($(this)); });
        $body.find('tr').each(function () {
            $(this).toggleClass('is-del', $(this).find('.dc_del').is(':checked'));
        });
        markDefault();
    }

    $form.on('change', '.dc_takes', function () { syncUrl($(this)); });
    $form.on('change', '.dc_use, .dc_del', syncAll);
    $form.on('input', 'input[name$="[name]"]', markDefault);

    // ── 줄 추가 — 몇 개든. type=button 이라 Enter 가 여기로 가지 않는다(제출 버튼은 하나뿐).
    $('#cart_dc_add_btn').on('click', function () {
        newSeq += 1;
        var k = 'new' + newSeq;
        $body.append(
            '<tr draggable="true" data-key="' + k + '">'
          + '<td class="dc_grip" title="끌어서 순서 바꾸기">≡</td>'
          + '<td><input type="text" name="dc[' + k + '][name]" value="" size="14" class="frm_input" placeholder="택배사 이름"></td>'
          + '<td><input type="text" name="dc[' + k + '][tel]" value="" size="12" class="frm_input" placeholder="1588-0000"></td>'
          + '<td class="td_left"><input type="text" name="dc[' + k + '][url]" value="" class="frm_input dc_url" style="width:97%" placeholder="https://…"></td>'
          + '<td><input type="checkbox" name="dc[' + k + '][invoice]" value="1" class="dc_takes" checked></td>'
          + '<td><input type="checkbox" name="dc[' + k + '][use]" value="1" class="dc_use" checked></td>'
          + '<td class="dc_flag"></td>'
          + '<td><button type="button" class="dc_rm" title="이 줄 없애기">×</button></td>'
          + '</tr>');
        $body.find('tr:last input[name$="[name]"]').trigger('focus');
        syncAll();
    });

    // 아직 저장 안 된 줄은 그냥 없앤다 — 지울 것이 없으니 확인도 필요 없다
    $form.on('click', '.dc_rm', function () {
        $(this).closest('tr').remove();
        syncAll();
    });

    // ── 끌어서 순서 바꾸기 (분류관리·상품 이미지 격자와 같은 방식)
    // 포인터가 놓인 자리 바로 뒤에 오는 줄. 없으면(맨 아래면) null
    function afterRow(y) {
        var hit = null;
        $body.find('tr').not('.is-drag').each(function () {
            var r = this.getBoundingClientRect();
            if (y < r.top + r.height / 2) { hit = this; return false; }
        });
        return hit;
    }

    $body.on('dragstart', 'tr', function (e) {
        // 입력칸 안에서 글자를 끄는 것과 줄을 끄는 것을 가른다
        if ($(e.target).is('input, button')) { e.preventDefault(); return; }
        dragEl = this;
        var dt = e.originalEvent.dataTransfer, el = this;
        dt.effectAllowed = 'move';
        dt.setData('text/plain', 'dc');   // 파이어폭스는 데이터를 넣어야 끌기가 시작된다
        // 지금 프레임에 흐리게 만들면 그 모습이 끌고 다니는 그림으로 굳는다 — 한 박자 뒤에
        setTimeout(function () { $(el).addClass('is-drag'); }, 0);
    });

    $body.on('dragend', 'tr', function () {
        $(this).removeClass('is-drag');
        dragEl = null;
        markDefault();
    });

    $body.on('dragover', function (e) {
        if (!dragEl) return;
        e.preventDefault();
        e.originalEvent.dataTransfer.dropEffect = 'move';
        // 끌고 다니는 동안 실제로 자리를 옮겨 둔다 — 놓기 전에 결과가 보인다
        var after = afterRow(e.originalEvent.clientY);
        if (!after) $body.append(dragEl);
        else if (after !== dragEl) after.parentNode.insertBefore(dragEl, after);
        markDefault();
    });

    $body.on('drop', function (e) { e.preventDefault(); markDefault(); });

    // ── 제출 — 화면 순서를 dc_seq[] 로 담고, 지울 것이 있으면 한 번 묻는다.
    // 확인 버튼을 따로 두지 않는 이유는 폼에 제출 버튼이 둘이면 Enter 가 늘 첫 버튼으로 가기 때문이다.
    $form.on('submit', function () {
        var n = $body.find('.dc_del:checked').length;
        if (n && !confirm(n + '개 택배사를 지웁니다. 계속할까요?')) return false;
        var $seq = $('#cart_dc_seq').empty();
        $body.find('tr').each(function () {
            $seq.append($('<input type="hidden" name="dc_seq[]">').val($(this).data('key')));
        });
        return true;
    });

    syncAll();
});
</script>
