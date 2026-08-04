<div class="local_desc01 local_desc">
    <p>객실 타입을 관리합니다. 예약이 있는 객실은 삭제되지 않고 숨김 처리됩니다.</p>
</div>

<div class="btn_add01 btn_add">
    <a href="{{ $admin_url }}/booking/room_form.php" class="btn btn_01">객실 추가</a>
</div>

<div class="tbl_head01 tbl_wrap">
    <table>
        <caption>객실 목록</caption>
        <thead><tr>
            <th scope="col">번호</th><th scope="col">객실명</th><th scope="col">실수</th><th scope="col">기준/최대 인원</th>
            <th scope="col">주중/주말 요금</th><th scope="col">예약수</th><th scope="col">노출</th><th scope="col">관리</th>
        </tr></thead>
        <tbody>
        @foreach ($rooms as $r)
        <tr>
            <td>{{ $r['br_id'] }}</td>
            <td>{{ $r['br_subject'] }}</td>
            <td>{{ $r['br_room_count'] }}</td>
            <td>{{ $r['br_base_person'] }} / {{ $r['br_max_person'] }}</td>
            <td>{{ number_format($r['br_weekday_price']) }} / {{ number_format($r['br_weekend_price']) }}</td>
            {{-- status=active(확정+취소요청) — 예약수가 세는 범위 그대로 열어야 누른 숫자와 건수가 맞는다 --}}
            <td><a href="{{ $admin_url }}/booking/booking_list.php?br_id={{ $r['br_id'] }}&amp;status=active">{{ $r['booking_cnt'] }}</a></td>
            <td>{{ $r['br_use'] ? '노출' : '숨김' }}</td>
            <td>
                <a href="{{ $admin_url }}/booking/room_form.php?w=u&amp;br_id={{ $r['br_id'] }}" class="btn btn_03">수정</a>
                {{-- 숨김 객실은 room.php 가 열어 주지 않으므로 링크를 내지 않는다 --}}
                @if ($r['br_use'])
                <a href="{{ $g5_url }}/booking/room.php?br_id={{ $r['br_id'] }}" class="btn btn_02" target="_blank">사용자 보기</a>
                @endif
            </td>
        </tr>
        @endforeach

        @if (count($rooms) == 0)
        <tr><td colspan="8" class="empty_table">등록된 객실이 없습니다.</td></tr>
        @endif
        </tbody>
    </table>
</div>
