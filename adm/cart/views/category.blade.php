<div class="local_desc01 local_desc">
    <p>분류는 최대 3단입니다. 이름·순서·노출은 행에서 바로 고치고 저장을 누르세요. 부모 변경은 지원하지 않습니다(삭제 후 다시 만드세요).</p>
</div>

<form method="post" action="{{ $action_url }}">
<input type="hidden" name="token" value="">
<input type="hidden" name="w" value="">
<table class="tbl_head01 tbl_wrap">
    <thead>
    <tr><th>분류</th><th>상품 수</th><th>순서</th><th>노출</th><th>처리</th></tr>
    </thead>
    <tbody>
    <tr>
        <td>
            <select name="ca_parent">
                <option value="0">최상위</option>

                @foreach ($parent_options as $p)
                <option value="{{ $p['ca_id'] }}">{{ str_repeat('— ', $p['ca_depth'] - 1) }}{{ $p['ca_name'] }}</option>
                @endforeach

            </select>
            <input type="text" name="ca_name" placeholder="새 분류 이름">
        </td>
        <td></td>
        <td><input type="text" name="ca_order" value="0" size="4"></td>
        <td><input type="checkbox" name="ca_show" value="1" checked></td>
        <td><button type="submit" class="btn_submit btn">추가</button></td>
    </tr>
    </tbody>
</table>
</form>

@foreach ($categories as $c)
<form method="post" action="{{ $action_url }}" style="display:contents">
<input type="hidden" name="token" value="">
<input type="hidden" name="ca_id" value="{{ $c['ca_id'] }}">
<table class="tbl_head01 tbl_wrap" style="margin-top:-1px">
    <tbody>
    <tr>
        <td style="padding-left:{{ ($c['ca_depth'] - 1) * 24 + 10 }}px">
            <input type="text" name="ca_name" value="{{ $c['ca_name'] }}">
            <span class="txt_id">#{{ $c['ca_id'] }}</span>
        </td>
        <td>{{ isset($counts[$c['ca_id']]) ? number_format($counts[$c['ca_id']]) : 0 }}</td>
        <td><input type="text" name="ca_order" value="{{ $c['ca_order'] }}" size="4"></td>
        <td><input type="checkbox" name="ca_show" value="1" {{ $c['ca_show'] ? 'checked' : '' }}></td>
        <td>
            <button type="submit" name="w" value="u" class="btn_submit btn">저장</button>
            <button type="submit" name="w" value="d" class="btn_02 btn"
                onclick="return confirm('분류를 삭제할까요? 하위 분류나 상품이 있으면 거부됩니다.')">삭제</button>
        </td>
    </tr>
    </tbody>
</table>
</form>
@endforeach
