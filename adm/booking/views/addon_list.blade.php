<div class="local_desc01 local_desc">
    <p>조식·바비큐 같은 부가상품을 관리합니다. 한 화면에서 추가·수정·삭제를 하고 마지막에 한 번 저장합니다.</p>
    <p>상품은 <strong>담긴 객실에서만</strong> 판매됩니다. 어느 객실에 담을지는 객실 수정 화면에서 고르며, 공통 상품은 <strong>전 객실에 추가</strong> 버튼으로 한 번에 담고 필요 없는 객실에서만 빼면 됩니다.</p>
    <p><strong>과금 단위</strong>가 "1박당"인 상품(조식 등)은 손님이 고른 수량에 박수를 곱해 계산됩니다 — 조식 4인분 × 3박. "1회"는 숙박 전체에 한 번만 계산됩니다 (픽업·케이크 등).</p>
    <p>예약에 담긴 부가상품은 주문 당시의 이름·금액이 따로 보관되므로, 여기서 지워도 지난 예약은 그대로 남습니다. 판매만 멈추려면 노출을 "숨김"으로 두십시오.</p>
</div>

<form name="faddonlist" id="faddonlist" action="./addon_update.php" method="post" autocomplete="off">
{{-- 토큰 값은 admin.js 가 제출 순간 ajax.token.php 에서 받아 채운다 (관리자 폼 공통 관례) --}}
<input type="hidden" name="token" value="">

<div class="tbl_head01 tbl_wrap">
    <table>
        <caption>부가상품 목록</caption>
        <thead><tr>
            <th scope="col">번호</th><th scope="col">부가상품명</th><th scope="col">금액</th>
            <th scope="col">과금 단위</th><th scope="col">최대 수량</th><th scope="col">출력 순서</th>
            <th scope="col">노출</th><th scope="col">객실 적용</th><th scope="col">삭제</th>
        </tr></thead>
        <tbody>
        {{-- 맨 위 한 줄은 늘 비어 있는 신규 입력칸이다. 이름을 적었을 때만 저장된다 --}}
        <tr>
            <td>신규</td>
            <td>
                <input type="hidden" name="ba_id[new]" value="0">
                <label for="ba_subject_new" class="sound_only">새 부가상품명</label>
                <input type="text" name="ba_subject[new]" value="" id="ba_subject_new" class="frm_input" size="30" maxlength="255">
            </td>
            <td><input type="number" name="ba_price[new]" value="0" class="frm_input" size="10" min="0"> 원</td>
            <td>
                <select name="ba_unit[new]">
                    <option value="once">1회</option>
                    <option value="night">1박당</option>
                </select>
            </td>
            <td><input type="number" name="ba_max_qty[new]" value="10" class="frm_input" size="5" min="1"></td>
            <td><input type="number" name="ba_order[new]" value="0" class="frm_input" size="5"></td>
            <td>
                <select name="ba_use[new]">
                    <option value="1">노출</option>
                    <option value="0">숨김</option>
                </select>
            </td>
            <td>-</td>
            <td>-</td>
        </tr>

        @foreach ($addons as $i => $a)
        <tr>
            <td>{{ $a['ba_id'] }}</td>
            <td>
                <input type="hidden" name="ba_id[{{ $i }}]" value="{{ $a['ba_id'] }}">
                <label for="ba_subject_{{ $i }}" class="sound_only">부가상품명</label>
                <input type="text" name="ba_subject[{{ $i }}]" value="{{ $a['ba_subject'] }}" id="ba_subject_{{ $i }}" required class="frm_input required" size="30" maxlength="255">
            </td>
            <td><input type="number" name="ba_price[{{ $i }}]" value="{{ $a['ba_price'] }}" class="frm_input" size="10" min="0"> 원</td>
            <td>
                <select name="ba_unit[{{ $i }}]">
                    <option value="once" {{ $a['ba_unit'] == 'night' ? '' : 'selected' }}>1회</option>
                    <option value="night" {{ $a['ba_unit'] == 'night' ? 'selected' : '' }}>1박당</option>
                </select>
            </td>
            <td><input type="number" name="ba_max_qty[{{ $i }}]" value="{{ $a['ba_max_qty'] }}" class="frm_input" size="5" min="1"></td>
            <td><input type="number" name="ba_order[{{ $i }}]" value="{{ $a['ba_order'] }}" class="frm_input" size="5"></td>
            <td>
                <select name="ba_use[{{ $i }}]">
                    <option value="1" {{ $a['ba_use'] ? 'selected' : '' }}>노출</option>
                    <option value="0" {{ $a['ba_use'] ? '' : 'selected' }}>숨김</option>
                </select>
            </td>
            <td><button type="submit" name="attach_all" value="{{ $a['ba_id'] }}" class="btn btn_02 addon_attach_all">전 객실에 추가</button></td>
            <td><input type="checkbox" name="del[{{ $i }}]" value="1" class="addon_del"></td>
        </tr>
        @endforeach

        @if (count($addons) == 0)
        <tr><td colspan="9" class="empty_table">등록된 부가상품이 없습니다. 위 칸에 입력하고 저장하십시오.</td></tr>
        @endif
        </tbody>
    </table>
</div>

<div class="btn_confirm01 btn_confirm">
    <input type="submit" value="확인" id="btn_addon_submit" class="btn_submit btn">
</div>
</form>

<script>
jQuery(function($) {
    // 요소에 직접 건 핸들러라 admin.js 의 document 위임 핸들러(토큰 채우기)보다 먼저 돈다.
    // 취소하면 false 를 돌려 전파까지 끊어 제출 자체를 막는다.
    $("#btn_addon_submit").on("click", function() {
        var cnt = $("#faddonlist input.addon_del:checked").length;
        if (cnt > 0 && !confirm(cnt + "개의 부가상품을 삭제할까요? 삭제한 자료는 복구할 수 없습니다.")) return false;
    });

    // 전 객실에 추가 — 저장을 겸하므로 그 사실까지 알린다
    $(".addon_attach_all").on("click", function() {
        if (!confirm("이 상품을 모든 객실에 담을까요? (화면의 수정 내용도 함께 저장됩니다)")) return false;
    });
});
</script>
