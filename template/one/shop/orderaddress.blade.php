{{-- 배송지 목록 (shop/orderaddress.php) — 주문서에서 새 창으로 연다.
     "선택" 은 부모 창의 주문 폼을 채우고 배송비를 다시 계산시킨다 (순정 계약) --}}
@extends('layout.popup')
@section('head')
<script src="{{ G5_JS_URL }}/jquery-1.12.4.min.js"></script>
@endsection
@section('content')
<form name="forderaddress" method="post" action="{{ $action }}" autocomplete="off">
<ul class="addr-list">
    @foreach ($items as $it)
    <li>
        <div class="addr-top">
            <input type="hidden" name="ad_id[{{ $it['i'] }}]" value="{{ $it['ad_id'] }}">
            <label class="addr-chk">
                <input type="checkbox" name="chk[]" value="{{ $it['i'] }}">
                <span class="sound_only">이 배송지 고르기</span>
            </label>
            <label for="ad_subject{{ $it['i'] }}" class="sound_only">배송지명</label>
            <input type="text" class="addr-subject" id="ad_subject{{ $it['i'] }}"
                   name="ad_subject[{{ $it['i'] }}]" value="{{ $it['subject'] }}" maxlength="20" placeholder="배송지명">
            @if ($it['is_default'])<span class="chip c3">기본</span>@endif
        </div>

        <div class="addr-body">
            <b>{{ $it['name'] }}</b>
            <span class="muted">{{ $it['tel'] }}@if ($it['hp']) · {{ $it['hp'] }}@endif</span>
            <div>{!! $it['address'] !!}</div>
        </div>

        <div class="addr-acts">
            <button type="button" class="btn btn-primary sel_address" data-addr="{{ $it['raw'] }}">이 주소 선택</button>
            <label class="addr-default">
                <input type="radio" name="ad_default" value="{{ $it['ad_id'] }}" @if ($it['is_default']) checked @endif>
                기본배송지
            </label>
            <a class="linklike del_address" href="{!! $del_href !!}{{ $it['ad_id'] }}">삭제</a>
        </div>
    </li>
    @endforeach
</ul>

@include('partials.paging', ['page' => $page, 'total_page' => $total_page, 'page_href' => $page_href])

<div class="popup-btns">
    <button type="button" class="btn" onclick="self.close();">닫기</button>
    <button type="submit" name="act_button" value="선택수정" class="btn btn-primary btn-submit">선택수정</button>
</div>
</form>

<script>
$(function () {
    // 부모 창(주문서)의 받는분 칸을 채운다 — 값의 순서는 순정과 같다
    $(".sel_address").on("click", function () {
        var addr = $(this).data("addr").split(String.fromCharCode(30));
        var f = window.opener && window.opener.forderform;
        if (!f) { alert("주문서 창을 찾을 수 없습니다."); return; }

        f.od_b_name.value        = addr[0];
        f.od_b_tel.value         = addr[1];
        f.od_b_hp.value          = addr[2];
        f.od_b_zip.value         = addr[3] + addr[4];
        f.od_b_addr1.value       = addr[5];
        f.od_b_addr2.value       = addr[6];
        f.od_b_addr3.value       = addr[7];
        f.od_b_addr_jibeon.value = addr[8];
        f.ad_subject.value       = addr[9];

        var zip1 = addr[3].replace(/[^0-9]/g, "");
        var zip2 = addr[4].replace(/[^0-9]/g, "");
        if (zip1 !== "" && zip2 !== "") {
            var code = String(zip1) + String(zip2);
            if (window.opener.zipcode != code) {
                window.opener.zipcode = code;
                window.opener.calculate_sendcost(code);
            }
        }
        window.close();
    });

    $(".del_address").on("click", function () {
        return confirm("이 배송지를 삭제하시겠습니까?");
    });

    $(".btn-submit").on("click", function () {
        if ($("input[name^='chk[']:checked").length === 0) {
            alert("수정할 배송지를 하나 이상 고르세요.");
            return false;
        }
    });
});
</script>
@endsection
