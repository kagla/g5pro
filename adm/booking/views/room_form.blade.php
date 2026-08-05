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
            <th scope="row"><label for="br_room_count">객실 수</label></th>
            <td><input type="number" name="br_room_count" value="{{ $room['br_room_count'] }}" id="br_room_count" class="frm_input" size="5" min="0"> 개
                <span class="frm_info">같은 타입의 방이 몇 개인지 — 날짜별 판매 개수의 기본값이며, 캘린더에서 날짜별로 줄일 수 있습니다.</span></td>
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

<style>
.bra_wrap { display:flex; gap:12px; margin:15px 0 }
.bra_col { flex:1; min-width:0; border:1px solid #d0d3db; border-radius:4px; background:#fff }
.bra_col h3 { margin:0; padding:8px 12px; border-bottom:1px solid #e6e8ee; background:#f5f6fa; font-size:1em }
.bra_col h3 .bra_hint { font-weight:normal; color:#888; font-size:0.9em }
.bra_list { list-style:none; margin:0; padding:6px; min-height:120px }
.bra_list li { display:flex; align-items:center; gap:8px; margin:4px 0; padding:6px 10px;
    border:1px solid #dfe2ea; border-radius:4px; background:#fafbfd; cursor:grab }
.bra_list li.bra_dragging { opacity:0.4 }
.bra_list li .bra_name { flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap }
.bra_list li .bra_price { color:#777; font-size:0.92em }
.bra_list li .bra_hidden { color:#b35c00; font-size:0.88em }
.bra_list li button { flex:none }
.bra_list:empty { display:flex; align-items:center; justify-content:center }
.bra_list:empty::after { content:attr(data-empty); color:#aaa; font-size:0.92em }
</style>

<div class="local_desc02 local_desc">
    <p>이 객실에서 판매할 <strong>부가상품</strong>을 고릅니다. 끌어다 놓거나 담기/빼기 버튼을 누르십시오.
        오른쪽 목록의 위에서부터 노출되며, 순서도 끌어서 바꿀 수 있습니다. 저장은 아래 확인 버튼으로 함께 됩니다.</p>
</div>

<div class="bra_wrap">
    <div class="bra_col">
        <h3>전체 부가상품 <span class="bra_hint">— 아직 안 담긴 상품</span></h3>
        <ul id="bra_pool" class="bra_list" data-empty="담을 수 있는 상품이 없습니다">
            @foreach ($addon_pool as $a)
        <li draggable="true" data-id="{{ $a['ba_id'] }}"><span class="bra_name">{{ $a['ba_subject'] }}</span>
            @if (!$a['ba_use'])
            <span class="bra_hidden">(숨김)</span>
            @endif
            <span class="bra_price">{{ number_format($a['ba_price']) }}원</span>
            <button type="button" class="btn btn_02">담기</button></li>
            @endforeach
        </ul>
    </div>
    <div class="bra_col">
        <h3>이 객실 부가상품 <span class="bra_hint">— 위에서부터 노출 순</span></h3>
        <ul id="bra_sel" class="bra_list" data-empty="여기로 끌어다 놓으세요">
            @foreach ($addon_sel as $a)
        <li draggable="true" data-id="{{ $a['ba_id'] }}"><span class="bra_name">{{ $a['ba_subject'] }}</span>
            @if (!$a['ba_use'])
            <span class="bra_hidden">(숨김)</span>
            @endif
            <span class="bra_price">{{ number_format($a['ba_price']) }}원</span>
            <button type="button" class="btn btn_02">빼기</button></li>
            @endforeach
        </ul>
    </div>
</div>
<input type="hidden" name="addon_ids" id="addon_ids" value="{{ implode(',', array_column($addon_sel, 'ba_id')) }}">

<div class="btn_confirm01 btn_confirm">
    <a href="./room_list.php" class="btn btn_02">목록</a>
    <input type="submit" value="확인" class="btn_submit btn">

    @if ($w == 'u')
    <button type="submit" name="act" value="delete" id="btn_room_delete" class="btn btn_02" formnovalidate>삭제</button>
    @endif
</div>
</form>

<script>
(function() {
    var pool = document.getElementById("bra_pool"),
        sel = document.getElementById("bra_sel"),
        hidden = document.getElementById("addon_ids"),
        lists = [pool, sel],
        dragging = null;

    // 제출값과 버튼 라벨은 늘 "지금 어느 목록에 있나"에서 다시 계산한다 —
    // 이동 경로(드래그였나 버튼이었나)마다 따로 맞추면 하나는 반드시 어긋난다
    function sync() {
        var ids = [];
        Array.prototype.forEach.call(sel.children, function(li) { ids.push(li.dataset.id); });
        hidden.value = ids.join(",");
        lists.forEach(function(list) {
            Array.prototype.forEach.call(list.children, function(li) {
                li.querySelector("button").textContent = (list === sel) ? "빼기" : "담기";
            });
        });
    }

    // 세로 좌표로 끼워 넣을 자리를 찾는다 — 커서보다 아래에 있는 첫 항목 앞
    function afterAt(list, y) {
        var items = list.querySelectorAll("li:not(.bra_dragging)");
        for (var i = 0; i < items.length; i++) {
            var box = items[i].getBoundingClientRect();
            if (y < box.top + box.height / 2) return items[i];
        }
        return null;
    }

    document.querySelector(".bra_wrap").addEventListener("dragstart", function(e) {
        var li = e.target.closest ? e.target.closest("li") : null;
        if (!li) return;
        dragging = li;
        li.classList.add("bra_dragging");
        e.dataTransfer.effectAllowed = "move";
        // Firefox 는 데이터가 있어야 드래그가 시작된다
        e.dataTransfer.setData("text/plain", li.dataset.id);
    });
    document.querySelector(".bra_wrap").addEventListener("dragend", function() {
        if (dragging) dragging.classList.remove("bra_dragging");
        dragging = null;
        sync();
    });

    lists.forEach(function(list) {
        list.addEventListener("dragover", function(e) {
            if (!dragging) return;
            e.preventDefault();
            var after = afterAt(list, e.clientY);
            if (after) list.insertBefore(dragging, after);
            else list.appendChild(dragging);
        });
        list.addEventListener("drop", function(e) { e.preventDefault(); sync(); });
        // 터치·키보드용 — 버튼 하나로 반대쪽 목록 맨 아래로 옮긴다
        list.addEventListener("click", function(e) {
            if (e.target.tagName !== "BUTTON") return;
            var li = e.target.closest("li");
            ((list === sel) ? pool : sel).appendChild(li);
            sync();
        });
    });

    sync();
})();
</script>

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
