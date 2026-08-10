<style>
#cart_sz .frm_input { height: 30px; line-height: 28px; }
/* 순정 admin.css 가 .tbl_head01 안의 .frm_input 을 width:100% 로 잡는다 — 한 칸에 입력이
   둘(우편번호 시작~끝) 있으면 세로로 쌓인다. id 로 이겨서 칸마다 폭을 준다. */
#cart_sz td { text-align: center; white-space: nowrap; }
#cart_sz .sz_name { width: 130px; }
#cart_sz .sz_zip { width: 76px; text-align: center; }
#cart_sz .sz_fee { width: 92px; text-align: right; }
#cart_sz .sz_tilde { display: inline-block; padding: 0 4px; color: #888; }
#cart_sz .sz_rm { border: 0; background: none; color: #999; font-size: 15px; cursor: pointer; }
#cart_sz tr.is-new td { background: #eef4ff; }
#sz_tools { margin: 14px 0; padding: 18px; border: 1px solid #e0e0e0; border-radius: 6px; background: #fafafa; }
/* 관리자 기본 글씨가 12px 이고 .txt_id 는 더 작다. 여기 안내는 "무엇을 어디서 복사해 오는가"
   를 처음 알려 주는 글이라 읽히는 크기여야 한다 — 표 안의 짧은 힌트와는 성격이 다르다.
   em 은 monospace 에서 브라우저 기본 고정폭 크기에 걸려 되레 작아지므로 px 로 못 박는다. */
#sz_tools h3 { margin: 0 0 10px; font-size: 16px; }
#sz_tools .txt_id { display: block; font-size: 14px; line-height: 1.7; color: #555; }
#sz_tools textarea { width: 100%; height: 110px; font-family: monospace; font-size: 14px; line-height: 1.6; }
#sz_tools .sz_row { margin-top: 10px; font-size: 14px; }
#sz_tools .sz_row input { height: 32px; line-height: 30px; font-size: 14px; }
#sz_tools .btn { font-size: 14px; }
/* 글자마다 미리보기가 생겼다 사라지면 아래 표가 그만큼 위아래로 튄다.
   빈 줄일 때도 한 줄 높이를 잡아 두어 화면이 흔들리지 않게 한다. */
#sz_preview { margin-top: 10px; color: #555; font-size: 14px; line-height: 22px; min-height: 22px; }
#sz_preview b { color: #1D5FD1; }
#cart_sz_add { margin: 10px 0 0; }
</style>

<div class="local_desc01 local_desc">
    <p>배송지 우편번호가 아래 구간에 들면 그만큼 <strong>더</strong> 받습니다.
       기본 배송비 {{ number_format($ship_base) }}원{{ $ship_free > 0 ? ' · '.number_format($ship_free).'원 이상 무료' : '' }}는
       <a href="{{ $config_url }}">환경설정</a>에서 정합니다.</p>
    <p><strong>조건부 무료배송을 충족해도 이 추가비는 남습니다.</strong> 실제 택배 원가가 남는 구간이라 몰 관례를 따릅니다.</p>
    <p>한 이름에 구간을 여러 줄 둘 수 있습니다. 구간이 겹치면 <strong>가장 비싼 것 하나만</strong> 붙습니다.
       우편번호를 짧게 적으면 5자리로 채웁니다 (예: <code>63</code> → 63000~63999).</p>
</div>

<div id="sz_tools">
    <h3>택배사 목록 붙여넣기</h3>
    <p class="txt_id" style="margin:0 0 8px">
        거래하는 택배사가 주는 <strong>도서산간 추가운임 지역</strong> 파일을 열어 우편번호 열을 복사해 붙여넣으세요.
        줄바꿈·쉼표·탭 아무거나 됩니다. 5자리 숫자만 골라 <strong>연속된 것끼리 구간으로 묶어</strong> 표에 넣습니다.
        넣은 뒤에도 표에서 고칠 수 있고, <strong>저장을 눌러야 반영</strong>됩니다.
    </p>
    <textarea id="sz_paste" placeholder="58800&#10;58801&#10;58802&#10;58805&#10;…"></textarea>
    <div class="sz_row">
        구간 이름 <input type="text" id="sz_paste_name" value="도서산간" size="12" class="frm_input">
        추가 배송비 <input type="text" id="sz_paste_fee" value="5000" size="8" style="text-align:right" class="frm_input"> 원
        <button type="button" class="btn btn_02" id="sz_paste_btn">표에 넣기</button>
    </div>
    <p id="sz_preview"></p>

    <h3 style="margin-top:16px">기본값 넣기</h3>
    <p class="txt_id" style="margin:0 0 8px">
        경계가 확실한 두 곳만 넣습니다 — <strong>제주도 전체</strong>와 <strong>울릉군 전체</strong>는 우편번호가 한 덩어리입니다.
        흑산도·백령도처럼 흩어진 섬, 연륙교가 생겨 택배사마다 판정이 다른 곳(완도·진도 등)은
        넣지 않았습니다. 그건 위의 붙여넣기로 택배사 목록을 그대로 채우세요.
    </p>
    <button type="button" class="btn btn_02" id="sz_preset_btn">제주 · 울릉군 넣기</button>
</div>

<form method="post" action="{{ $action_url }}" id="cart_sz">
<input type="hidden" name="token" value="{{ $token }}">

<div class="tbl_head01 tbl_wrap">
    <table>
    <caption>권역별 추가 배송비</caption>
    <thead>
    <tr>
        <th scope="col">구간 이름</th>
        <th scope="col">우편번호</th>
        <th scope="col">추가 배송비</th>
        <th scope="col">사용</th>
        <th scope="col">삭제</th>
    </tr>
    </thead>
    <tbody id="cart_sz_body">

    @foreach ($zones as $z)
    <tr>
        <td><input type="text" name="sz[{{ $z['sz_id'] }}][name]" value="{{ $z['sz_name'] }}" class="frm_input sz_name"></td>
        <td><input type="text" name="sz[{{ $z['sz_id'] }}][from]" value="{{ $z['sz_zip_from'] }}" class="frm_input sz_zip"><span class="sz_tilde">~</span><input type="text" name="sz[{{ $z['sz_id'] }}][to]" value="{{ $z['sz_zip_to'] }}" class="frm_input sz_zip"></td>
        <td><input type="text" name="sz[{{ $z['sz_id'] }}][fee]" value="{{ $z['sz_fee'] }}" class="frm_input sz_fee"> 원</td>
        <td><input type="checkbox" name="sz[{{ $z['sz_id'] }}][use]" value="1" {{ (int)$z['sz_use'] === 1 ? 'checked' : '' }}></td>
        <td><input type="checkbox" name="sz_del[]" value="{{ $z['sz_id'] }}" class="sz_del"></td>
    </tr>
    @endforeach

    @if (!count($zones))
    <tr id="sz_empty"><td colspan="5" class="empty_table">등록된 권역이 없습니다. 위에서 기본값을 넣거나 택배사 목록을 붙여넣으세요.</td></tr>
    @endif

    </tbody>
    </table>
</div>

<p id="cart_sz_add"><button type="button" class="btn btn_02" id="cart_sz_add_btn">+ 구간 직접 추가</button></p>

<div class="btn_confirm01 btn_confirm" style="text-align:right">
    <button type="submit" class="btn_submit btn">저장</button>
</div>
</form>

<script>
$(function () {
    var seq = 0, $body = $('#cart_sz_body');

    // 새 줄 하나. 붙여넣기·프리셋·직접추가가 모두 이 함수를 지난다.
    function addRow(name, from, to, fee) {
        $('#sz_empty').remove();
        seq += 1;
        var k = 'new' + seq;
        function esc(v) { return String(v == null ? '' : v).replace(/"/g, '&quot;'); }
        $body.append(
            '<tr class="is-new">'
          + '<td><input type="text" name="sz[' + k + '][name]" value="' + esc(name) + '" class="frm_input sz_name" placeholder="예: 도서산간"></td>'
          + '<td><input type="text" name="sz[' + k + '][from]" value="' + esc(from) + '" class="frm_input sz_zip" placeholder="40200">'
          + '<span class="sz_tilde">~</span>'
          + '<input type="text" name="sz[' + k + '][to]" value="' + esc(to) + '" class="frm_input sz_zip" placeholder="40240"></td>'
          + '<td><input type="text" name="sz[' + k + '][fee]" value="' + esc(fee) + '" class="frm_input sz_fee"> 원</td>'
          + '<td><input type="checkbox" name="sz[' + k + '][use]" value="1" checked></td>'
          + '<td><button type="button" class="sz_rm" title="이 줄 없애기">×</button></td>'
          + '</tr>');
    }

    $('#cart_sz_add_btn').on('click', function () {
        addRow('', '', '', '');
        $body.find('tr:last input[name$="[name]"]').trigger('focus');
    });

    $body.on('click', '.sz_rm', function () { $(this).closest('tr').remove(); });

    // 붙여넣은 글에서 5자리 숫자만 골라 연속된 것끼리 묶는다.
    // 엑셀에서 복사하면 줄바꿈·탭이 섞여 오고, "58800 (흑산면)" 처럼 설명이 붙기도 한다 —
    // 5자리 숫자만 뽑으므로 그대로 붙여넣어도 된다. 6자리 옛 우편번호는 걸리지 않는다.
    function parseZips(text) {
        var found = String(text).match(/\d+/g) || [];
        var set = {};
        for (var i = 0; i < found.length; i++) if (found[i].length === 5) set[found[i]] = true;
        var list = Object.keys(set).sort();
        var out = [];
        for (var j = 0; j < list.length; j++) {
            var from = list[j], to = list[j];
            // 다음 번호가 바로 이어지면 한 구간으로 늘린다(문자열이라 숫자로 바꿔 견준다)
            while (j + 1 < list.length && parseInt(list[j + 1], 10) === parseInt(to, 10) + 1) {
                j += 1; to = list[j];
            }
            out.push({ from: from, to: to });
        }
        return { zips: list.length, ranges: out };
    }

    $('#sz_paste').on('input', function () {
        var r = parseZips($(this).val());
        $('#sz_preview').html(!r.zips ? ''
            : '우편번호 <b>' + r.zips + '</b>개 → 구간 <b>' + r.ranges.length + '</b>개로 묶입니다.');
    });

    $('#sz_paste_btn').on('click', function () {
        var name = $.trim($('#sz_paste_name').val());
        if (name === '') { alert('구간 이름을 적어 주세요.'); $('#sz_paste_name').trigger('focus'); return; }
        var fee = parseInt(String($('#sz_paste_fee').val()).replace(/[^0-9]/g, ''), 10) || 0;
        var r = parseZips($('#sz_paste').val());
        if (!r.zips) { alert('5자리 우편번호를 찾지 못했습니다.'); return; }
        // 줄이 많으면 표가 길어지므로 미리 알린다 — 넣고 나서 놀라지 않게
        if (!confirm('우편번호 ' + r.zips + '개를 구간 ' + r.ranges.length + '개로 묶어 표에 넣습니다.\n'
            + '"' + name + '" · ' + fee.toLocaleString() + '원\n\n저장을 눌러야 반영됩니다. 계속할까요?')) return;
        for (var i = 0; i < r.ranges.length; i++) addRow(name, r.ranges[i].from, r.ranges[i].to, fee);
        $('#sz_paste').val('');
        $('#sz_preview').text('구간 ' + r.ranges.length + '개를 표에 넣었습니다. 확인하고 저장하세요.');
    });

    // 경계가 확실한 둘만 — 나머지를 지어 넣으면 틀린 값이 조용히 손님 청구서에 붙는다
    $('#sz_preset_btn').on('click', function () {
        addRow('제주', '63000', '63644', 3000);
        addRow('울릉군', '40200', '40240', 5000);
        $('#sz_preview').text('제주·울릉군을 표에 넣었습니다. 요금을 확인하고 저장하세요.');
    });

    $('#cart_sz').on('submit', function () {
        var n = $body.find('.sz_del:checked').length;
        if (n && !confirm(n + '개 권역을 지웁니다. 그 구간의 추가 배송비는 더 이상 붙지 않습니다.\n계속할까요?')) return false;
        return true;
    });
});
</script>
