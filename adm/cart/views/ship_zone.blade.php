<style>
#cart_sz .frm_input { height: 30px; line-height: 28px; }
/* 순정 admin.css 가 .tbl_head01 안의 .frm_input 을 width:100% 로 잡는다 — 한 칸에 입력이
   둘(우편번호 시작~끝) 있으면 세로로 쌓인다. id 로 이겨서 칸마다 폭을 준다. */
#cart_sz td { text-align: center; white-space: nowrap; }
#cart_sz .sz_name { width: 130px; }
#cart_sz .sz_zip { width: 76px; text-align: center; }
#cart_sz .sz_fee { width: 92px; text-align: right; }
#cart_sz .sz_tilde { display: inline-block; padding: 0 4px; color: #888; }
/* 표 머리를 눌러 정렬한다. 순정 목록은 링크로 서버에 다시 묻지만 여기는 폼이라 그럴 수 없다 —
   다시 불러오면 아직 저장 안 한 편집이 날아간다. 그래서 화면에서 줄만 옮긴다. */
#cart_sz th .sz_sort { border: 0; background: none; padding: 0; font: inherit; color: inherit;
    cursor: pointer; white-space: nowrap; }
#cart_sz th .sz_sort:hover { text-decoration: underline; }
#cart_sz th .sz_arrow { display: inline-block; width: 12px; color: #1D5FD1; }
#sz_sorted { margin: 8px 0 0; font-size: 13px; color: #1D5FD1; min-height: 20px; }
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
        거래하는 택배사가 주는 <strong>도서산간 추가운임 지역</strong> 파일을 열어 복사해 붙여넣으세요.
        두 가지 모양을 알아서 가려 읽습니다.
    </p>
    <ul class="txt_id" style="margin:0 0 10px; padding-left:18px; list-style:disc">
        <li><strong>구간표 그대로</strong> — <code>지역명, 시작우편번호, 끝우편번호, 배송비</code> 네 칸.
            로젠택배가 이 모양으로 줍니다. <strong>이름과 요금도 파일에서 가져옵니다</strong>(아래 칸은 안 씁니다).</li>
        <li><strong>우편번호만</strong> — 번호가 죽 나열된 모양. 5자리만 골라
            <strong>연속된 것끼리 구간으로 묶고</strong>, 이름과 요금은 아래 칸 값을 붙입니다.</li>
    </ul>
    <textarea id="sz_paste" placeholder='"제주도",63000,63644,"5000.00"&#10;"울릉군",40200,40240,"10000.00"&#10;&#10;— 또는 —&#10;&#10;58800&#10;58801&#10;58802'></textarea>
    <div class="sz_row" id="sz_manual">
        구간 이름 <input type="text" id="sz_paste_name" value="도서산간" size="12" class="frm_input">
        추가 배송비 <input type="text" id="sz_paste_fee" value="5000" size="8" style="text-align:right" class="frm_input"> 원
        <span class="txt_id" id="sz_manual_off" style="display:none; color:#1D5FD1">← 구간표를 붙여넣었으니 이 칸은 안 씁니다</span>
    </div>
    <div class="sz_row"><button type="button" class="btn btn_02" id="sz_paste_btn">표에 넣기</button></div>
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
        <th scope="col"><button type="button" class="sz_sort" data-key="name">구간 이름<span class="sz_arrow"></span></button></th>
        <th scope="col"><button type="button" class="sz_sort" data-key="zip">우편번호<span class="sz_arrow"></span></button></th>
        <th scope="col"><button type="button" class="sz_sort" data-key="fee">추가 배송비<span class="sz_arrow"></span></button></th>
        <th scope="col"><button type="button" class="sz_sort" data-key="use">사용<span class="sz_arrow"></span></button></th>
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

<p id="sz_sorted"></p>
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

    function pad5(s) { s = String(s).replace(/[^0-9]/g, ''); return s.length >= 5 ? s.slice(0, 5) : ('00000' + s).slice(-5); }

    // 따옴표 안의 쉼표를 지키며 한 줄을 칸으로 가른다(지역명에 쉼표가 들어올 수 있다)
    function splitCsv(line) {
        var out = [], cur = '', q = false;
        for (var i = 0; i < line.length; i++) {
            var c = line.charAt(i);
            if (c === '"') { q = !q; continue; }
            if (c === ',' && !q) { out.push(cur); cur = ''; continue; }
            cur += c;
        }
        out.push(cur);
        return out;
    }

    // ── 모양 ①: 구간표 (지역명, 시작, 끝, 배송비). 이름과 요금까지 파일에서 가져온다.
    // 머리글 줄과 꼬리의 빈 줄("",,,"")은 숫자가 아니라서 저절로 걸러진다.
    // 요금은 "5000.00" 으로 오므로 parseFloat 로 읽는다 — 숫자만 남기면 500000 이 된다.
    function parseTable(text) {
        var lines = String(text).replace(/^﻿/, '').split(/\r?\n/);
        var rows = [], skipped = 0;
        for (var i = 0; i < lines.length; i++) {
            var line = $.trim(lines[i]);
            if (line === '') continue;
            var f = splitCsv(line);
            if (f.length < 4) { skipped += 1; continue; }
            var a = $.trim(f[f.length - 3]).replace(/[^0-9]/g, '');
            var b = $.trim(f[f.length - 2]).replace(/[^0-9]/g, '');
            var fee = parseFloat($.trim(f[f.length - 1]).replace(/[",\s]/g, ''));
            if (a === '' || b === '' || isNaN(fee)) { skipped += 1; continue; }
            // 이름은 남은 앞칸 전부(쉼표로 잘렸으면 도로 붙인다)
            var name = $.trim(f.slice(0, f.length - 3).join(',')) || '추가배송';
            var from = pad5(a), to = pad5(b);
            if (from > to) { var t = from; from = to; to = t; }
            rows.push({ name: name, from: from, to: to, fee: Math.round(fee) });
        }
        return { rows: rows, skipped: skipped };
    }

    // ── 모양 ②: 우편번호만. 5자리만 골라 연속된 것끼리 묶는다.
    // 엑셀에서 복사하면 줄바꿈·탭이 섞여 오고 "58805 (홍도)" 처럼 설명이 붙기도 한다.
    function parseZips(text) {
        var found = String(text).match(/\d+/g) || [];
        var set = {};
        for (var i = 0; i < found.length; i++) if (found[i].length === 5) set[found[i]] = true;
        var list = Object.keys(set).sort();
        var out = [];
        for (var j = 0; j < list.length; j++) {
            var from = list[j], to = list[j];
            while (j + 1 < list.length && parseInt(list[j + 1], 10) === parseInt(to, 10) + 1) {
                j += 1; to = list[j];
            }
            out.push({ from: from, to: to });
        }
        return { zips: list.length, ranges: out };
    }

    // 앞 2자리가 다른 구간은 십중팔구 원본 파일의 오타다. 로젠 목록 112줄 중 이 판별식에
    // 걸리는 것은 26242~37246(11,005개를 덮는다) 한 줄뿐이었고, 나머지 111줄은 전부 같았다.
    // 막지는 않는다 — 진짜로 도를 걸치는 구간이 있을 수 있으니 사람이 보고 정하게 한다.
    function wideOnes(rows) {
        var bad = [];
        for (var i = 0; i < rows.length; i++) {
            if (rows[i].from.slice(0, 2) !== rows[i].to.slice(0, 2)) bad.push(rows[i]);
        }
        return bad;
    }

    // 붙여넣은 것이 구간표인가 낱개 번호인가 — 4칸으로 읽히는 줄이 하나라도 있으면 구간표다
    function read(text) {
        var t = parseTable(text);
        if (t.rows.length) return { mode: 'table', rows: t.rows, skipped: t.skipped };
        var z = parseZips(text);
        var rows = [];
        for (var i = 0; i < z.ranges.length; i++) rows.push({ from: z.ranges[i].from, to: z.ranges[i].to });
        return { mode: 'zips', rows: rows, zips: z.zips };
    }

    $('#sz_paste').on('input', function () {
        var r = read($(this).val());
        $('#sz_manual_off').toggle(r.mode === 'table' && r.rows.length > 0);
        if (!r.rows.length) { $('#sz_preview').html('&nbsp;'); return; }
        if (r.mode === 'zips') {
            $('#sz_preview').html('우편번호 <b>' + r.zips + '</b>개 → 구간 <b>' + r.rows.length + '</b>개로 묶입니다.');
            return;
        }
        var fees = {}, bad = wideOnes(r.rows);
        for (var i = 0; i < r.rows.length; i++) fees[r.rows[i].fee] = true;
        var list = Object.keys(fees).map(Number).sort(function (a, b) { return a - b; });
        var msg = '구간표 <b>' + r.rows.length + '</b>줄 · 요금 <b>'
            + list.map(function (f) { return f.toLocaleString() + '원'; }).join(' / ') + '</b>';
        if (r.skipped) msg += ' <span style="color:#888">(머리글 등 ' + r.skipped + '줄은 건너뜀)</span>';
        if (bad.length) {
            msg += '<br><b style="color:#C4314B">⚠ 우편번호 앞 2자리가 다른 구간 ' + bad.length + '개</b> — '
                + bad.map(function (x) { return x.from + '~' + x.to; }).join(', ')
                + ' · 원본 파일의 오타일 수 있습니다. 넣은 뒤 표에서 확인하세요.';
        }
        $('#sz_preview').html(msg);
    });

    $('#sz_paste_btn').on('click', function () {
        var r = read($('#sz_paste').val());
        if (!r.rows.length) { alert('읽을 수 있는 우편번호를 찾지 못했습니다.'); return; }

        var msg, rows = r.rows;
        if (r.mode === 'zips') {
            var name = $.trim($('#sz_paste_name').val());
            if (name === '') { alert('구간 이름을 적어 주세요.'); $('#sz_paste_name').trigger('focus'); return; }
            var fee = parseInt(String($('#sz_paste_fee').val()).replace(/[^0-9]/g, ''), 10) || 0;
            for (var i = 0; i < rows.length; i++) { rows[i].name = name; rows[i].fee = fee; }
            msg = '우편번호 ' + r.zips + '개를 구간 ' + rows.length + '개로 묶어 표에 넣습니다.\n'
                + '"' + name + '" · ' + fee.toLocaleString() + '원';
        } else {
            var bad = wideOnes(rows);
            msg = '구간표 ' + rows.length + '줄을 표에 넣습니다. 이름과 요금은 파일 값을 씁니다.';
            if (bad.length) {
                msg += '\n\n⚠ 우편번호 앞 2자리가 다른 구간이 ' + bad.length + '개 있습니다:\n'
                    + bad.slice(0, 5).map(function (x) { return '  ' + x.name + '  ' + x.from + '~' + x.to; }).join('\n')
                    + (bad.length > 5 ? '\n  … 외 ' + (bad.length - 5) + '개' : '')
                    + '\n원본 파일의 오타일 수 있습니다 — 넣은 뒤 표에서 확인하세요.';
            }
        }
        if (!confirm(msg + '\n\n저장을 눌러야 반영됩니다. 계속할까요?')) return;

        for (var j = 0; j < rows.length; j++) addRow(rows[j].name, rows[j].from, rows[j].to, rows[j].fee);
        $('#sz_paste').val('').trigger('input');
        $('#sz_preview').text(rows.length + '줄을 표에 넣었습니다. 확인하고 저장하세요.');
    });

    // 경계가 확실한 둘만 — 나머지를 지어 넣으면 틀린 값이 조용히 손님 청구서에 붙는다
    $('#sz_preset_btn').on('click', function () {
        addRow('제주', '63000', '63644', 3000);
        addRow('울릉군', '40200', '40240', 5000);
        $('#sz_preview').text('제주·울릉군을 표에 넣었습니다. 요금을 확인하고 저장하세요.');
    });

    // ── 표 머리를 눌러 정렬. 서버에 다시 묻지 않고 줄만 옮기므로 편집 중인 값이 따라간다.
    // 저장하면 이 순서가 그대로 sz_order 가 된다(cart_ship_zone_save 는 받은 순서로 매긴다) —
    // 112줄을 이름순으로 정리해 두면 다음에 열 때도 그 순서다. 그래서 아래에 그렇게 적어 둔다.
    var sortKey = '', sortAsc = true;
    var LABEL = { name: '구간 이름', zip: '우편번호', fee: '추가 배송비', use: '사용' };

    function cellVal(tr, key) {
        var $tr = $(tr);
        if (key === 'name') return $.trim($tr.find('input[name$="[name]"]').val() || '');
        if (key === 'zip')  return $tr.find('input[name$="[from]"]').val() || '';
        if (key === 'fee')  return parseInt(String($tr.find('input[name$="[fee]"]').val()).replace(/[^0-9]/g, ''), 10) || 0;
        return $tr.find('input[name$="[use]"]').is(':checked') ? 1 : 0;
    }

    function sortBy(key) {
        // 같은 칸을 다시 누르면 방향을 뒤집는다. 처음 누를 때는 그 칸에서 쓸모 있는 쪽으로 —
        // 사용은 "켜진 것 먼저" 가 알고 싶은 것이지 꺼진 것 먼저가 아니다.
        if (sortKey === key) sortAsc = !sortAsc;
        else { sortKey = key; sortAsc = (key !== 'use'); }

        var rows = $body.find('tr').filter(function () { return $(this).find('input').length > 0; }).get();
        rows.sort(function (a, b) {
            // 이름이 빈 줄(방금 추가한 빈 칸)은 어느 방향이든 늘 맨 아래 — 정렬하다 잃어버리지 않게
            var an = cellVal(a, 'name'), bn = cellVal(b, 'name');
            if (an === '' && bn !== '') return 1;
            if (bn === '' && an !== '') return -1;

            var x = cellVal(a, key), y = cellVal(b, key), r;
            if (key === 'name') r = String(x).localeCompare(String(y), 'ko');
            else if (key === 'zip') r = String(x).localeCompare(String(y));   // 5자리 문자열이라 앞자리 0 이 산다
            else r = (x < y ? -1 : (x > y ? 1 : 0));
            if (r === 0 && key !== 'zip') {
                // 값이 같으면 우편번호로 갈라 순서가 튀지 않게 한다
                r = String(cellVal(a, 'zip')).localeCompare(String(cellVal(b, 'zip')));
            }
            return sortAsc ? r : -r;
        });
        $body.append(rows);

        $('#cart_sz .sz_arrow').text('');
        $('#cart_sz .sz_sort[data-key="' + key + '"] .sz_arrow').text(sortAsc ? ' ▲' : ' ▼');
        $('#sz_sorted').text(LABEL[key] + (sortAsc ? ' 오름차순' : ' 내림차순')
            + '으로 정렬했습니다 — 저장하면 이 순서로 기억합니다.');
    }

    $('#cart_sz').on('click', '.sz_sort', function () { sortBy($(this).data('key')); });

    $('#cart_sz').on('submit', function () {
        var n = $body.find('.sz_del:checked').length;
        if (n && !confirm(n + '개 권역을 지웁니다. 그 구간의 추가 배송비는 더 이상 붙지 않습니다.\n계속할까요?')) return false;
        return true;
    });
});
</script>
