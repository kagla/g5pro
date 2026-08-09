<style>
#cart_dc .frm_input { height: 30px; line-height: 28px; }
#cart_dc td { text-align: center; }
#cart_dc td.td_left { text-align: left; }
#cart_dc .dc_url[readonly] { background: #f2f2f2; color: #888; }
</style>

<div class="local_desc01 local_desc">
    <p>배송관리에서 고를 택배사 목록입니다. <strong>사용</strong>을 켠 것만 배송관리에 뜨고,
       <strong>기본</strong>으로 고른 하나는 택배사가 아직 안 정해진 주문에 미리 선택됩니다.</p>
    <p><strong>송장조회 주소</strong>는 송장번호가 뒤에 붙는 데까지 적습니다
       (예: <code>https://trace.cjlogistics.com/next/tracking.html?wblNo=</code>).
       비워 두면 번호만 보여 주고 링크는 걸지 않습니다.</p>
    <p><strong>송장번호</strong>를 끄면 송장 대신 배송안내를 적는 수단이 됩니다
       (직접배송·퀵서비스·방문수령). 이때 조회 주소는 쓰지 않습니다.</p>
    <p>맨 아래 빈 줄에 이름을 적으면 새 택배사가 추가됩니다. 지울 것은 <strong>삭제</strong>에
       표시한 뒤 저장하세요. 이미 그 택배사로 나간 주문에는 그때 이름이 그대로 남습니다.</p>
</div>

<form method="post" action="{{ $action_url }}" id="cart_dc">
<input type="hidden" name="token" value="{{ $token }}">

<div class="tbl_head01 tbl_wrap">
    <table>
    <caption>택배사 목록</caption>
    <thead>
    <tr>
        <th scope="col">순서</th>
        <th scope="col">이름</th>
        <th scope="col">송장조회 주소</th>
        <th scope="col">송장번호</th>
        <th scope="col">사용</th>
        <th scope="col">기본</th>
        <th scope="col">삭제</th>
    </tr>
    </thead>
    <tbody>

    @foreach ($rows as $r)
    <tr class="bg{{ $loop->index % 2 }}">
        <td><input type="text" name="dc[{{ $r['dc_id'] }}][order]" value="{{ $r['dc_order'] }}" size="3" class="frm_input"></td>
        <td><input type="text" name="dc[{{ $r['dc_id'] }}][name]" value="{{ $r['dc_name'] }}" size="14" class="frm_input"></td>
        <td class="td_left"><input type="text" name="dc[{{ $r['dc_id'] }}][url]" value="{{ $r['dc_url'] }}" class="frm_input dc_url" style="width:97%" placeholder="https://…" {{ (int)$r['dc_invoice'] === 1 ? '' : 'readonly' }}></td>
        <td><input type="checkbox" name="dc[{{ $r['dc_id'] }}][invoice]" value="1" class="dc_takes" {{ (int)$r['dc_invoice'] === 1 ? 'checked' : '' }}></td>
        <td><input type="checkbox" name="dc[{{ $r['dc_id'] }}][use]" value="1" {{ (int)$r['dc_use'] === 1 ? 'checked' : '' }}></td>
        <td><input type="radio" name="dc_default" value="{{ $r['dc_id'] }}" {{ (int)$r['dc_default'] === 1 ? 'checked' : '' }}></td>
        <td><input type="checkbox" name="dc_del[]" value="{{ $r['dc_id'] }}" class="dc_del"></td>
    </tr>
    @endforeach

    @for ($i = 1; $i <= $new_count; $i++)
    <tr class="bg{{ (count($rows) + $i - 1) % 2 }}">
        <td><input type="text" name="dc[new{{ $i }}][order]" value="" size="3" class="frm_input"></td>
        <td><input type="text" name="dc[new{{ $i }}][name]" value="" size="14" class="frm_input" placeholder="택배사 이름"></td>
        <td class="td_left"><input type="text" name="dc[new{{ $i }}][url]" value="" class="frm_input dc_url" style="width:97%" placeholder="https://…"></td>
        <td><input type="checkbox" name="dc[new{{ $i }}][invoice]" value="1" class="dc_takes" checked></td>
        <td><input type="checkbox" name="dc[new{{ $i }}][use]" value="1" checked></td>
        <td><input type="radio" name="dc_default" value="new{{ $i }}"></td>
        <td>&nbsp;</td>
    </tr>
    @endfor

    </tbody>
    </table>
</div>

<div class="btn_confirm01 btn_confirm">
    <button type="submit" class="btn_submit btn">저장</button>
</div>
</form>

<script>
// 송장번호를 안 받는 택배사는 조회주소가 쓸 데가 없다 — 흐리게 잠근다.
// disabled 가 아니라 readonly 인 이유: disabled 면 값이 제출되지 않아, 껐다 켜면 주소가 사라진다.
$(function () {
    function sync($cb) {
        $cb.closest('tr').find('.dc_url').prop('readonly', !$cb.is(':checked'));
    }
    $('#cart_dc .dc_takes').each(function () { sync($(this)); })
        .on('change', function () { sync($(this)); });

    // 삭제는 되돌릴 수 없으므로 한 번 묻는다. 버튼을 따로 두지 않는 이유는
    // 폼에 제출 버튼이 둘이면 Enter 가 늘 첫 버튼으로 가기 때문이다.
    $('#cart_dc').on('submit', function () {
        var n = $('#cart_dc .dc_del:checked').length;
        if (n && !confirm(n + '개 택배사를 지웁니다. 계속할까요?')) return false;
        return true;
    });
});
</script>
