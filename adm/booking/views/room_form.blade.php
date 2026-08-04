<div class="local_desc01 local_desc">
    <p>객실 타입의 기본 정보와 요금, 이미지를 등록합니다. 요금은 캘린더에서 날짜별로 덮어쓸 수 있습니다.</p>
</div>

<form name="froomform" id="froomform" action="./room_form_update.php" method="post" enctype="multipart/form-data" autocomplete="off">
{{-- 토큰 값은 admin.js 가 제출 순간 ajax.token.php 에서 받아 채운다 (관리자 폼 공통 관례) --}}
<input type="hidden" name="token" value="">
<input type="hidden" name="w" value="{{ $w }}">
<input type="hidden" name="br_id" value="{{ $room['br_id'] }}">

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption>객실 정보 입력</caption>
        <colgroup><col class="grid_4"><col></colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="br_subject">객실명<strong class="sound_only">필수</strong></label></th>
            <td><input type="text" name="br_subject" value="{{ $room['br_subject'] }}" id="br_subject" required class="frm_input required" size="60" maxlength="255"></td>
        </tr>
        <tr>
            <th scope="row"><label for="br_content">객실 설명</label></th>
            <td><textarea name="br_content" id="br_content" rows="6" class="frm_input" style="width:100%">{{ $room['br_content'] }}</textarea></td>
        </tr>
        <tr>
            <th scope="row"><label for="br_room_count">객실 실수</label></th>
            <td><input type="number" name="br_room_count" value="{{ $room['br_room_count'] }}" id="br_room_count" class="frm_input" size="5" min="0"> 개
                <span class="frm_info">같은 타입의 방이 몇 개인지 — 날짜별 재고의 기본값입니다.</span></td>
        </tr>
        <tr>
            <th scope="row"><label for="br_base_person">기준 인원</label></th>
            <td><input type="number" name="br_base_person" value="{{ $room['br_base_person'] }}" id="br_base_person" class="frm_input" size="5" min="1"> 명</td>
        </tr>
        <tr>
            <th scope="row"><label for="br_max_person">최대 인원</label></th>
            <td><input type="number" name="br_max_person" value="{{ $room['br_max_person'] }}" id="br_max_person" class="frm_input" size="5" min="1"> 명</td>
        </tr>
        <tr>
            <th scope="row"><label for="br_person_price">인원 추가 요금</label></th>
            <td><input type="number" name="br_person_price" value="{{ $room['br_person_price'] }}" id="br_person_price" class="frm_input" size="10" min="0"> 원
                <span class="frm_info">기준 인원 초과 1명 × 1박 당 금액입니다.</span></td>
        </tr>
        <tr>
            <th scope="row"><label for="br_weekday_price">주중 요금</label></th>
            <td><input type="number" name="br_weekday_price" value="{{ $room['br_weekday_price'] }}" id="br_weekday_price" class="frm_input" size="10" min="0"> 원</td>
        </tr>
        <tr>
            <th scope="row"><label for="br_weekend_price">주말 요금</label></th>
            <td><input type="number" name="br_weekend_price" value="{{ $room['br_weekend_price'] }}" id="br_weekend_price" class="frm_input" size="10" min="0"> 원
                <span class="frm_info">금요일·토요일 밤에 적용됩니다.</span></td>
        </tr>
        <tr>
            <th scope="row"><label for="br_order">출력 순서</label></th>
            <td><input type="number" name="br_order" value="{{ $room['br_order'] }}" id="br_order" class="frm_input" size="5"></td>
        </tr>
        <tr>
            <th scope="row"><label for="br_use">노출 여부</label></th>
            <td>
                <select name="br_use" id="br_use">
                    <option value="1" {{ $room['br_use'] ? 'selected' : '' }}>노출</option>
                    <option value="0" {{ $room['br_use'] ? '' : 'selected' }}>숨김</option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bi_files">이미지 추가</label></th>
            <td><input type="file" name="bi_files[]" id="bi_files" multiple accept="image/*">
                <span class="frm_info">jpg, jpeg, png, gif, webp 만 등록됩니다.</span></td>
        </tr>
        </tbody>
    </table>
</div>

@if (count($images) > 0)
<div class="tbl_head01 tbl_wrap">
    <table>
        <caption>등록된 이미지</caption>
        <thead><tr>
            <th scope="col">이미지</th><th scope="col">순서</th><th scope="col">대표</th><th scope="col">삭제</th>
        </tr></thead>
        <tbody>
        @foreach ($images as $img)
        <tr>
            <td><img src="{{ G5_DATA_URL }}/booking/{{ $img['bi_file'] }}" alt="객실 이미지" style="max-width:160px;height:auto"></td>
            <td><input type="number" name="bi_order[{{ $img['bi_id'] }}]" value="{{ $img['bi_order'] }}" class="frm_input" size="4"></td>
            <td><input type="radio" name="bi_main" value="{{ $img['bi_id'] }}" {{ $img['bi_main'] ? 'checked' : '' }}></td>
            <td><input type="checkbox" name="bi_del[{{ $img['bi_id'] }}]" value="1"></td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="btn_confirm01 btn_confirm">
    <a href="./room_list.php" class="btn btn_02">목록</a>
    <input type="submit" value="확인" class="btn_submit btn">

    @if ($w == 'u')
    <button type="submit" name="act" value="delete" id="btn_room_delete" class="btn btn_02" formnovalidate>삭제</button>
    @endif
</div>
</form>

@if ($w == 'u')
<script>
jQuery(function($) {
    // 요소에 직접 건 핸들러라 admin.js 의 document 위임 핸들러(토큰 채우기)보다 먼저 돈다.
    // 취소하면 false 를 돌려 전파까지 끊어 제출 자체를 막는다.
    $("#btn_room_delete").on("click", function() {
        var msg = "이 객실을 삭제할까요? 삭제한 자료는 복구할 수 없습니다.";

        @if ($booking_cnt > 0)
        msg = "예약이 {{ $booking_cnt }}건 있어 삭제 대신 숨김 처리됩니다. 진행할까요?";
        @endif

        if (!confirm(msg)) return false;
    });
});
</script>
@endif
