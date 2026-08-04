<div class="local_desc01 local_desc">
    <p>날짜별 요금과 판매 실수를 관리합니다. 지정하지 않은 날은 객실에 등록한 기본 요금·기본 실수를 그대로 씁니다.</p>
    <p>파랗게 강조된 값이 이 캘린더에서 따로 지정한 값입니다.</p>
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

<form name="fcalendarform" id="fcalendarform" action="./calendar_update.php" method="post" autocomplete="off">
{{-- 토큰 값은 admin.js 가 제출 순간 ajax.token.php 에서 받아 채운다 (관리자 폼 공통 관례) --}}
<input type="hidden" name="token" value="">
<input type="hidden" name="br_id" value="{{ $room['br_id'] }}">
<input type="hidden" name="ym" value="{{ $ym }}">

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption>기간 일괄 적용</caption>
        <colgroup><col class="grid_4"><col></colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="start_date">기간</label></th>
            <td>
                <input type="date" name="start_date" value="{{ $first_date }}" id="start_date" required class="frm_input required">
                ~
                <label for="end_date" class="sound_only">종료일</label>
                <input type="date" name="end_date" value="{{ $last_date }}" id="end_date" required class="frm_input required">
                <span class="frm_info">종료일 당일까지 적용됩니다. 한 번에 366일까지.</span>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="set_price">요금</label></th>
            <td><input type="number" name="set_price" value="" id="set_price" class="frm_input" size="10" min="-1"> 원
                <span class="frm_info">비워 두면 요금은 건드리지 않습니다. -1 을 넣으면 지정을 해제해 객실 기본 요금(주중/주말)으로 되돌립니다.</span></td>
        </tr>
        <tr>
            <th scope="row"><label for="set_count">판매 실수</label></th>
            <td><input type="number" name="set_count" value="" id="set_count" class="frm_input" size="5" min="-1"> 개
                <span class="frm_info">비워 두면 실수는 건드리지 않습니다. -1 을 넣으면 지정을 해제해 객실 기본 실수({{ $room['br_room_count'] }}개)로 되돌립니다.</span>
                <span class="frm_info">수리 중인 객실은 해당 기간의 판매 실수를 줄이세요. 0 = 판매 중지</span></td>
        </tr>
        </tbody>
    </table>
</div>

<div class="btn_confirm01 btn_confirm">
    <input type="submit" value="적용" class="btn_submit btn">
</div>
</form>
@endif
