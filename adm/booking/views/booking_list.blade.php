<div class="local_desc01 local_desc">
    <p>예약을 검색하고 상세로 들어갑니다. 상태를 고르지 않으면 <strong>결제대기</strong>는 빼고 보여 줍니다 — 결제를 마치지 못한 자리라 유효시간이 지나면 저절로 풀립니다.</p>
    <p><span class="bkl_new">3</span> 처럼 붉게 표시된 숫자는 <strong>아직 확인하지 않은 고객 요청</strong> 건수입니다.</p>
</div>

<style>
.bkl_new { display:inline-block; min-width:1.6em; padding:1px 5px; border-radius:9px; background:#e8180c; color:#fff; font-size:0.88em; font-weight:bold; text-align:center }
.bkl_st { display:inline-block; padding:1px 6px; border-radius:3px; background:#eee; color:#555; font-size:0.92em }
.bkl_st_confirmed { background:#e6f0fd; color:#0b57d0 }
.bkl_st_cancel_req { background:#fff3e0; color:#b35c00 }
.bkl_st_cancelled { background:#f2f2f2; color:#888 }
.bkl_st_hold { background:#f7f7f7; color:#777 }
.bkl_sub { color:#777; font-size:0.92em }
.bkl_total { text-align:right }
.local_sch01 .bkl_date { width:9.5em }
</style>

<form name="fbookingsearch" id="fbookingsearch" method="get" action="./booking_list.php">
<div class="local_sch01 local_sch">
    <label for="status">상태</label>
    <select name="status" id="status">
        <option value="" {{ $status == '' ? 'selected' : '' }}>결제대기 제외</option>
        <option value="all" {{ $status == 'all' ? 'selected' : '' }}>전체(결제대기 포함)</option>
        <option value="confirmed" {{ $status == 'confirmed' ? 'selected' : '' }}>예약확정</option>
        <option value="cancel_req" {{ $status == 'cancel_req' ? 'selected' : '' }}>취소요청</option>
        <option value="cancelled" {{ $status == 'cancelled' ? 'selected' : '' }}>취소완료</option>
        <option value="hold" {{ $status == 'hold' ? 'selected' : '' }}>결제대기</option>
    </select>

    <label for="sdate">체크인 기간</label>
    <input type="date" name="sdate" value="{{ $sdate }}" id="sdate" class="frm_input bkl_date">
    ~
    <label for="edate" class="sound_only">체크인 종료일</label>
    <input type="date" name="edate" value="{{ $edate }}" id="edate" class="frm_input bkl_date">

    <label for="stx">검색어</label>
    <input type="text" name="stx" value="{{ $stx }}" id="stx" class="frm_input" size="20" placeholder="이름 · 연락처 · 예약번호">

    <input type="submit" value="검색" class="btn_submit">
    <a href="./booking_list.php" class="btn btn_02">전체목록</a>
</div>
</form>

<div class="local_ov01 local_ov">검색된 예약 <b>{{ number_format($total_count) }}</b>건</div>

<div class="tbl_head01 tbl_wrap">
    <table>
        <caption>예약 목록</caption>
        <thead><tr>
            <th scope="col">예약번호</th><th scope="col">객실</th><th scope="col">체크인 ~ 체크아웃</th>
            <th scope="col">예약자</th><th scope="col">인원</th><th scope="col">총액</th>
            <th scope="col">상태</th><th scope="col">요청</th><th scope="col">관리</th>
        </tr></thead>
        <tbody>
        @foreach ($list as $b)
        <tr>
            <td>{{ $b['bk_no'] }}<div class="bkl_sub">{{ $b['bk_datetime'] }}</div></td>
            <td>{{ $b['br_subject'] == '' ? '(삭제된 객실)' : $b['br_subject'] }}</td>
            <td>{{ $b['bk_checkin'] }} ~ {{ $b['bk_checkout'] }}<div class="bkl_sub">{{ $b['nights'] }}박</div></td>
            <td>{{ $b['bk_name'] }}<div class="bkl_sub">{{ $b['bk_hp'] }}</div></td>
            <td>{{ $b['bk_person'] }}명</td>
            <td class="bkl_total">{{ number_format($b['bk_total_price']) }}원</td>
            <td><span class="bkl_st bkl_st_{{ $b['bk_status'] }}">{{ $b['status_text'] }}</span></td>
            <td>
                @if ($b['new_note_cnt'] > 0)
                <span class="bkl_new">{{ $b['new_note_cnt'] }}</span>
                @endif
            </td>
            <td><a href="./booking_view.php?bk_id={{ $b['bk_id'] }}" class="btn btn_03">상세</a></td>
        </tr>
        @endforeach

        @if (count($list) == 0)
        <tr><td colspan="9" class="empty_table">조건에 맞는 예약이 없습니다.</td></tr>
        @endif
        </tbody>
    </table>
</div>
