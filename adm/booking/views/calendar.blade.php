<div class="local_desc01 local_desc">
    <p>날짜별 요금과 판매 객실 수를 관리합니다. 지정하지 않은 날은 객실에 등록한 기본 요금과 방 개수를 그대로 씁니다. 파랗게 강조된 값이 이 캘린더에서 따로 지정한 값입니다.</p>
    <p>달력 칸의 <strong>판매 5 / 예약 2 / 잔여 3</strong>은 "그날 이 타입의 방을 5개 파는데 2개가 예약되어 3개 남았다"는 뜻입니다. 예약이 판매 개수에 차면 그날은 마감됩니다.</p>
    <p>아래 폼은 하나가 한 가지 일만 합니다 — <strong>성수기·비수기</strong>(전체 객실 요금),
        <strong>판매 중지·재개</strong>(이 객실 수리·휴업), <strong>날짜별 요금 지정</strong>(이 객실 특정일 요금).</p>
</div>

@if (!$room)
<div class="empty_list">등록된 객실이 없습니다. 먼저 <a href="{{ $admin_url }}/booking/room_list.php">객실</a>을 등록하십시오.</div>
@else

<style>
.bcal table { table-layout:fixed }
.bcal td { height:5.5em; vertical-align:top; text-align:left; padding:5px }
.bcal .bcal_day { font-weight:bold }
.bcal .bcal_price { margin-top:3px }
.bcal .bcal_stock { color:#777; font-size:0.92em }
.bcal .bcal_set { color:#0b57d0; font-weight:bold }
.bcal .bcal_sun { color:#e8180c }
.bcal .bcal_sat { color:#0b57d0 }
.bcal .bcal_over { background:#fde8e6 }
.bcal .bcal_warn { color:#e8180c; font-weight:bold }
.bcal .bcal_blank { background:#f7f7f7 }
.bcal_nav { margin-bottom:10px }
.bcal_nav .bcal_ym { margin:0 10px; font-size:1.15em; font-weight:bold }
</style>

<form name="fcalendarsearch" id="fcalendarsearch" method="get" action="./calendar.php">
<input type="hidden" name="ym" value="{{ $ym }}">
<div class="local_sch01 local_sch">
    <label for="br_id">객실</label>
    <select name="br_id" id="br_id" onchange="this.form.submit()">
        @foreach ($rooms as $r)
        <option value="{{ $r['br_id'] }}" {{ $r['br_id'] == $room['br_id'] ? 'selected' : '' }}>{{ $r['br_subject'] }}{{ $r['br_use'] ? '' : ' (숨김)' }}</option>
        @endforeach
    </select>
    <input type="submit" value="보기" class="btn_submit">
</div>
</form>

<div class="bcal_nav">
    <a href="./calendar.php?br_id={{ $room['br_id'] }}&amp;ym={{ $prev_ym }}" class="btn btn_02">◀ 이전달</a>
    <span class="bcal_ym">{{ $ym }}</span>
    <a href="./calendar.php?br_id={{ $room['br_id'] }}&amp;ym={{ $next_ym }}" class="btn btn_02">다음달 ▶</a>
</div>

<div class="tbl_head01 tbl_wrap bcal">
    <table>
        <caption>{{ $room['br_subject'] }} {{ $ym }} 요금·재고 캘린더</caption>
        <thead><tr>
            <th scope="col">일</th><th scope="col">월</th><th scope="col">화</th><th scope="col">수</th>
            <th scope="col">목</th><th scope="col">금</th><th scope="col">토</th>
        </tr></thead>
        <tbody>
        <tr>
        @for ($i = 0; $i < $lead_blank; $i++)
            <td class="bcal_blank"></td>
        @endfor

        @foreach ($days as $idx => $day)
            <td class="{{ $day['oversold'] ? 'bcal_over' : '' }}">
                <div class="bcal_day {{ $day['w'] == 0 ? 'bcal_sun' : ($day['w'] == 6 ? 'bcal_sat' : '') }}">{{ $day['day'] }}</div>
                <div class="bcal_price {{ $day['price_override'] ? 'bcal_set' : '' }}">{{ number_format($day['price']) }}원</div>
                <div class="bcal_stock">판매 <span class="{{ $day['count_override'] ? 'bcal_set' : '' }}">{{ $day['sellable'] }}</span> / 예약 {{ $day['booked'] }} / 잔여 {{ $day['remain'] }}</div>
                @if ($day['oversold'])
                <div class="bcal_warn">⚠ 초과예약</div>
                @endif
            </td>

            {{-- 7칸을 채울 때마다 줄을 바꾼다. 마지막 날 뒤에는 아래 빈칸이 이어지므로 열지 않는다 --}}
            @if (($lead_blank + $idx + 1) % 7 == 0 && $idx + 1 < count($days))
        </tr>
        <tr>
            @endif
        @endforeach

        @for ($i = 0; $i < $tail_blank; $i++)
            <td class="bcal_blank"></td>
        @endfor
        </tr>
        </tbody>
    </table>
</div>

{{-- 성수기·비수기 — 전체 객실에 비율로만 적용한다. 방마다 요금이 달라도 각자 제 기본요금 기준이라 한 번이면 된다 --}}
<form name="fseasonform" id="fseasonform" action="./calendar_update.php" method="post" autocomplete="off">
{{-- 토큰 값은 admin.js 가 제출 순간 ajax.token.php 에서 받아 채운다 (관리자 폼 공통 관례) --}}
<input type="hidden" name="token" value="">
<input type="hidden" name="act" value="season">
<input type="hidden" name="br_id" value="{{ $room['br_id'] }}">
<input type="hidden" name="ym" value="{{ $ym }}">

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption>성수기·비수기 (전체 객실)</caption>
        <colgroup><col class="grid_4"><col></colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="season_start">기간</label></th>
            <td>
                <input type="date" name="start_date" value="{{ $first_date }}" id="season_start" required class="frm_input required">
                ~
                <label for="season_end" class="sound_only">종료일</label>
                <input type="date" name="end_date" value="{{ $last_date }}" id="season_end" required class="frm_input required">
                <span class="frm_info">종료일 당일까지, 한 번에 366일까지. <strong>전체 객실 {{ count($rooms) }}개</strong>에 적용됩니다.</span>
            </td>
        </tr>
        <tr>
            <th scope="row">구분</th>
            <td>
                <label><input type="radio" name="season" value="peak" required> 성수기 — 기본요금의</label>
                <label for="peak_percent" class="sound_only">성수기 요금 비율</label>
                <input type="number" name="peak_percent" value="150" id="peak_percent" class="frm_input" size="4" min="1" max="999"> %
                <br>
                <label><input type="radio" name="season" value="off"> 비수기 — 기본요금의</label>
                <label for="off_percent" class="sound_only">비수기 요금 비율</label>
                <input type="number" name="off_percent" value="80" id="off_percent" class="frm_input" size="4" min="1" max="999"> %
                <br>
                <label><input type="radio" name="season" value="reset"> 기본요금으로 되돌리기</label>
                <span class="frm_info">방마다 요금이 달라도 각 객실의 주중/주말 기본요금을 기준으로 계산하므로 한 번에 적용할 수 있습니다. 100원 단위로 반올림됩니다.</span>
            </td>
        </tr>
        </tbody>
    </table>
</div>

<div class="btn_confirm01 btn_confirm">
    <input type="submit" value="전체 객실에 적용" id="btn_season_apply" class="btn_submit btn">
</div>
</form>

{{-- 수리·휴업은 여기서 동작으로 고른다 — 업주가 개수를 계산해 넣지 않게 한다 --}}
<form name="fstockform" id="fstockform" action="./calendar_update.php" method="post" autocomplete="off">
<input type="hidden" name="token" value="">
<input type="hidden" name="act" value="stock">
<input type="hidden" name="br_id" value="{{ $room['br_id'] }}">
<input type="hidden" name="ym" value="{{ $ym }}">

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption>판매 중지·재개 — {{ $room['br_subject'] }}</caption>
        <colgroup><col class="grid_4"><col></colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="stock_start">기간</label></th>
            <td>
                <input type="date" name="start_date" value="{{ $first_date }}" id="stock_start" required class="frm_input required">
                ~
                <label for="stock_end" class="sound_only">종료일</label>
                <input type="date" name="end_date" value="{{ $last_date }}" id="stock_end" required class="frm_input required">
                <span class="frm_info">종료일 당일까지, <strong>{{ $room['br_subject'] }}</strong> 에만 적용됩니다.</span>
            </td>
        </tr>
        <tr>
            <th scope="row">처리</th>
            <td>
                <label><input type="radio" name="stock_mode" value="stop" required> <strong>판매 중지</strong> — 이 기간에는 이 객실을 팔지 않습니다 (수리·휴업)</label>
                <br>
                <label><input type="radio" name="stock_mode" value="resume"> <strong>판매 재개</strong> — 중지·조정을 풀고 평소대로 팝니다</label>
                @if ($room['br_room_count'] > 1)
                <br>
                <label><input type="radio" name="stock_mode" value="partial"> 일부만 판매 — 보유 {{ $room['br_room_count'] }}개 중</label>
                <label for="partial_count" class="sound_only">판매할 방 개수</label>
                <input type="number" name="partial_count" value="" id="partial_count" class="frm_input" size="4" min="1" max="{{ $room['br_room_count'] - 1 }}"> 개만
                <span class="frm_info">같은 타입 여러 방 중 일부만 수리할 때 씁니다.</span>
                @endif
            </td>
        </tr>
        </tbody>
    </table>
</div>

<div class="btn_confirm01 btn_confirm">
    <input type="submit" value="적용" id="btn_stock_apply" class="btn_submit btn">
</div>
</form>

{{-- 특정 날짜 요금을 액수로 고정하는 자리 — 성수기 비율과 같은 칸에 저장되므로 나중에 적용한 값이 남는다 --}}
<form name="fpriceform" id="fpriceform" action="./calendar_update.php" method="post" autocomplete="off">
<input type="hidden" name="token" value="">
<input type="hidden" name="br_id" value="{{ $room['br_id'] }}">
<input type="hidden" name="ym" value="{{ $ym }}">

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption>날짜별 요금 지정 — {{ $room['br_subject'] }}</caption>
        <colgroup><col class="grid_4"><col></colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="start_date">기간</label></th>
            <td>
                <input type="date" name="start_date" value="{{ $first_date }}" id="start_date" required class="frm_input required">
                ~
                <label for="end_date" class="sound_only">종료일</label>
                <input type="date" name="end_date" value="{{ $last_date }}" id="end_date" required class="frm_input required">
                <span class="frm_info">종료일 당일까지, <strong>{{ $room['br_subject'] }}</strong> 에만 적용됩니다.</span>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="set_price">1박 요금</label></th>
            <td><input type="number" name="set_price" value="" id="set_price" required class="frm_input required" size="10" min="-1"> 원
                <span class="frm_info">이 기간의 1박 요금을 이 액수로 고정합니다. -1 을 넣으면 지정을 해제해 객실 기본 요금(주중/주말)으로 되돌립니다.</span></td>
        </tr>
        </tbody>
    </table>
</div>

<div class="btn_confirm01 btn_confirm">
    <input type="submit" value="요금 적용" class="btn_submit btn">
</div>
</form>

<script>
jQuery(function($) {
    // 요소에 직접 건 핸들러라 admin.js 의 document 위임 핸들러(토큰 채우기)보다 먼저 돈다.
    // 취소하면 false 를 돌려 전파까지 끊어 제출 자체를 막는다.
    $("#btn_season_apply").on("click", function() {
        var season = $("input[name=season]:checked").val();
        var msg = { peak: "성수기 요금을", off: "비수기 요금을", reset: "요금 지정을 해제해 기본요금을" }[season];
        if (msg && !confirm("전체 객실 {{ count($rooms) }}개에 " + msg + " 적용할까요?")) return false;
    });

    // 비율 칸을 만지면 그 줄의 구분이 저절로 선택된다 — 라디오 따로 비율 따로 누르는 수고를 던다
    $("#peak_percent").on("focus input", function() { $("input[name=season][value=peak]").prop("checked", true); });
    $("#off_percent").on("focus input", function() { $("input[name=season][value=off]").prop("checked", true); });
    $("#partial_count").on("focus input", function() { $("input[name=stock_mode][value=partial]").prop("checked", true); });

    // 판매 중지는 예약이 막히는 일이다 — 기간을 되짚어 준다
    $("#btn_stock_apply").on("click", function() {
        if ($("input[name=stock_mode]:checked").val() !== "stop") return;
        var s = $("#stock_start").val(), e = $("#stock_end").val();
        if (!confirm(s + " ~ " + e + " 동안 이 객실 판매를 중지할까요?")) return false;
    });
});
</script>
@endif
