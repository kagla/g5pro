{{-- 예약 폼 (booking/reserve.php) → reserve_update.php 가 hold 를 만든다 --}}
@extends('layout.default')

{{-- 스타일을 뷰가 지고 다닌다. 이 화면은 template/standard 에만 있고 다른 템플릿에서는
     폴백(extend/pro.10.extend.php 의 $views)으로 그려지므로, 그 템플릿의 style.css 에
     예약 규칙이 있으리라 기대할 수 없다. 색·여백은 어느 템플릿에나 있는 토큰만 쓴다 --}}
@section('head')
<style>
.bk-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: var(--s5); align-items: start; }
.bk-side > section + section { margin-top: var(--s4); }
@media (max-width: 760px) {
    .bk-grid { grid-template-columns: 1fr; }
}
.bk-form { display: flex; flex-direction: column; gap: var(--s4); }
.bk-form fieldset { border: 0; padding: 0; margin: 0; }
.bk-form legend { padding: 0; font-size: var(--t-lg); font-weight: 700; margin-bottom: var(--s3); }
.bk-row { display: flex; gap: var(--s4); flex-wrap: wrap; }
.bk-row > .field { flex: 1 1 200px; min-width: 0; }
.field select { width: 100%; }
.bk-form textarea { width: 100%; min-height: 96px; }
/* 한 fieldset 안에서 줄과 줄 사이 */
.bk-form .bk-row + .bk-row, .bk-form .bk-row + .field, .bk-form dl + .field { margin-top: var(--s4); }

.bk-facts { display: flex; flex-direction: column; gap: var(--s2); margin: 0; }
.bk-facts > div { display: flex; gap: var(--s3); font-size: var(--t-md); }
.bk-facts dt { flex: 0 0 86px; color: var(--muted); }
.bk-facts dd { margin: 0; font-variant-numeric: tabular-nums; }

.bk-sum { display: flex; flex-direction: column; gap: var(--s2); margin: 0 0 var(--s3); }
.bk-sum > div { display: flex; justify-content: space-between; gap: var(--s3); font-size: var(--t-md); }
.bk-sum dt { color: var(--muted); }
.bk-sum dd { margin: 0; font-variant-numeric: tabular-nums; }
.bk-sum .bk-total { padding-top: var(--s2); border-top: 1px solid var(--line); }
.bk-sum .bk-total dt { color: var(--fg); font-weight: 700; }
.bk-sum b { font-size: var(--t-lg); color: var(--accent); }
.bk-note { margin: 0 0 var(--s3); font-size: var(--t-sm); color: var(--muted); }
.bk-note-tight { margin: 0; }

.bk-addon { display: flex; align-items: center; justify-content: space-between; gap: var(--s3);
    padding: 8px 0; border-bottom: 1px solid var(--line); }
.bk-addon:last-of-type { border-bottom: 0; }
.bk-addon > span { min-width: 0; }
.bk-addon select { flex: 0 0 84px; }
.bk-addon-price { display: block; font-size: var(--t-sm); color: var(--muted); font-variant-numeric: tabular-nums; }

/* 환불 약관은 관리자가 넣은 평문이다. 줄바꿈만 CSS 로 살린다 —
   HTML 로 내보내면 값 안의 태그가 그대로 먹는다 */
.bk-terms { white-space: pre-line; line-height: 1.7; margin: 0 0 var(--s3);
    max-height: 220px; overflow-y: auto; font-size: var(--t-sm); color: var(--muted); }
.bk-agree { display: flex; align-items: flex-start; gap: var(--s2); font-size: var(--t-md); font-weight: 700; }
</style>
@endsection

@section('content')
<div class="bbs-head">
    <h2>{{ $room['br_subject'] }} 예약</h2>
    <div class="bbs-head-right"><a class="btn" href="{{ G5_URL }}/booking/room.php?br_id={{ $room['br_id'] }}">날짜 다시 고르기</a></div>
</div>

{{-- data-* 는 아래 JS 가 총액을 다시 셀 때 쓰는 값이다. 계산식은 booking_calc_price() 와 같다 --}}
<form name="fbooking" id="bk-form" method="post" action="{{ G5_URL }}/booking/reserve_update.php"
      autocomplete="off"
      data-nights="{{ $nights }}"
      data-room-price="{{ $price['room'] }}"
      data-base-person="{{ $room['br_base_person'] }}"
      data-person-price="{{ $room['br_person_price'] }}">
<input type="hidden" name="token" value="{{ $token }}">
<input type="hidden" name="br_id" value="{{ $room['br_id'] }}">
<input type="hidden" name="checkin" value="{{ $checkin }}">
<input type="hidden" name="checkout" value="{{ $checkout }}">

<div class="bk-grid">
    <div class="bk-form">
        <section class="card">
            <fieldset>
                <legend>일정 · 인원</legend>
                <dl class="bk-facts">
                    <div><dt>체크인</dt><dd>{{ $checkin }} {{ $conf['checkin_time'] }}</dd></div>
                    <div><dt>체크아웃</dt><dd>{{ $checkout }} {{ $conf['checkout_time'] }}</dd></div>
                    <div><dt>숙박</dt><dd>{{ $nights }}박</dd></div>
                </dl>
                <div class="field">
                    <label for="bk-person">인원 <span class="muted">(기준 {{ $room['br_base_person'] }}명 · 최대 {{ $room['br_max_person'] }}명)</span></label>
                    <select name="person" id="bk-person">
                        @for ($n = 1; $n <= (int)$room['br_max_person']; $n++)
                        <option value="{{ $n }}" @if ($n == $person) selected @endif>{{ $n }}명</option>
                        @endfor
                    </select>
                    @if ($room['br_person_price'])
                    <p class="bk-note bk-note-tight">기준 인원 초과 시 1명 · 1박 {{ number_format($room['br_person_price']) }}원이 더해집니다.</p>
                    @endif
                </div>
            </fieldset>
        </section>

        @if (count($addons))
        <section class="card">
            <fieldset>
                <legend>부가상품</legend>
                @foreach ($addons as $addon)
                <div class="bk-addon">
                    <span>
                        {{ $addon['ba_subject'] }}
                        {{-- 1박당 상품은 총액 기준을 함께 보여 준다 — 수량 × 여기 적힌 값 = 청구액 --}}
                        @if ($addon['ba_unit'] == 'night')
                        <b class="bk-addon-price">{{ number_format($addon['ba_price']) }}원 /1박당 · {{ $nights }}박 = 개당 {{ number_format($addon['ba_price'] * $nights) }}원</b>
                        @else
                        <b class="bk-addon-price">{{ number_format($addon['ba_price']) }}원</b>
                        @endif
                    </span>
                    {{-- data-price 는 "수량 1개당 총액"이다 — 1박당 상품은 박수를 곱해 두어
                         화면 JS 합산이 서버 계산(booking_calc_price)과 같은 답을 낸다 --}}
                    <select name="addon[{{ $addon['ba_id'] }}]" class="bk-addon-qty" data-price="{{ $addon['ba_unit'] == 'night' ? $addon['ba_price'] * $nights : $addon['ba_price'] }}">
                        @for ($q = 0; $q <= (int)$addon['ba_max_qty']; $q++)
                        <option value="{{ $q }}">{{ $q }}</option>
                        @endfor
                    </select>
                </div>
                @endforeach
            </fieldset>
        </section>
        @endif

        <section class="card">
            <fieldset>
                <legend>예약자 정보</legend>
                <div class="bk-row">
                    <div class="field">
                        <label for="bk-name">이름</label>
                        <input type="text" name="bk_name" id="bk-name" required maxlength="20" value="{{ $guest['name'] }}">
                    </div>
                    <div class="field">
                        <label for="bk-hp">연락처</label>
                        <input type="text" name="bk_hp" id="bk-hp" required maxlength="20" value="{{ $guest['hp'] }}" placeholder="010-0000-0000">
                    </div>
                </div>
                <div class="bk-row">
                    <div class="field">
                        <label for="bk-email">이메일 <span class="muted">(선택 · 예약 안내 메일)</span></label>
                        <input type="email" name="bk_email" id="bk-email" maxlength="100" value="{{ $guest['email'] }}">
                    </div>
                    @if (!$is_member)
                    <div class="field">
                        <label for="bk-password">예약 확인 비밀번호 <span class="muted">(4자 이상)</span></label>
                        <input type="password" name="bk_password" id="bk-password" required minlength="4" maxlength="20">
                    </div>
                    @endif
                </div>
                <div class="field">
                    <label for="bk-request">요청사항 <span class="muted">(선택)</span></label>
                    <textarea name="bk_request" id="bk-request" rows="4"></textarea>
                </div>
            </fieldset>
        </section>
    </div>

    <div class="bk-side">
        <section class="card">
            <h3>결제 금액</h3>
            <dl class="bk-sum">
                <div><dt>객실 요금 ({{ $nights }}박)</dt><dd id="bk-p-room">{{ number_format($price['room']) }}원</dd></div>
                <div><dt>인원 추가</dt><dd id="bk-p-person">{{ number_format($price['person']) }}원</dd></div>
                <div><dt>부가상품</dt><dd id="bk-p-addon">{{ number_format($price['addon']) }}원</dd></div>
                <div class="bk-total"><dt>합계</dt><dd><b id="bk-p-total">{{ number_format($price['total']) }}</b>원</dd></div>
            </dl>
            <p class="bk-note">예약 신청 후 {{ $conf['hold_minutes'] }}분 안에 결제를 마쳐야 합니다. 시간이 지나면 자동으로 취소됩니다.</p>
            <button type="submit" class="btn btn-primary btn-block">결제하기</button>
        </section>

        <section class="card">
            <h3>취소·환불 규정</h3>
            @if (trim($conf['refund_terms']) !== '')
            <p class="bk-terms">{{ $conf['refund_terms'] }}</p>
            @endif
            <label class="bk-agree">
                <input type="checkbox" name="agree" id="bk-agree" value="1" required>
                <span>취소·환불 규정을 확인했으며 이에 동의합니다.</span>
            </label>
        </section>
    </div>
</div>
</form>

<script>
jQuery(function ($) {
    var $form = $('#bk-form');
    // 서버(booking_calc_price)와 같은 값을 내야 한다. 식이 바뀌면 두 곳을 함께 고친다
    var nights      = +$form.attr('data-nights');
    var roomPrice   = +$form.attr('data-room-price');
    var basePerson  = +$form.attr('data-base-person');
    var personPrice = +$form.attr('data-person-price');

    function comma(n)
    {
        return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function recalc()
    {
        var person = +$('#bk-person').val() || 0;
        var extra = person - basePerson;
        if (extra < 0) extra = 0;
        var personSum = extra * nights * personPrice;

        var addonSum = 0;
        $('.bk-addon-qty').each(function () {
            addonSum += (+$(this).attr('data-price')) * (+$(this).val() || 0);
        });

        $('#bk-p-person').text(comma(personSum) + '원');
        $('#bk-p-addon').text(comma(addonSum) + '원');
        $('#bk-p-total').text(comma(roomPrice + personSum + addonSum));
    }

    $form.on('change', '#bk-person, .bk-addon-qty', recalc);

    $form.on('submit', function () {
        // required 를 지원하지 않는 브라우저를 위한 마지막 확인. 서버도 같은 항목을 다시 본다
        if (!$.trim($('#bk-name').val())) { alert('예약자 이름을 입력해 주세요.'); $('#bk-name').focus(); return false; }
        if (!$.trim($('#bk-hp').val())) { alert('연락처를 입력해 주세요.'); $('#bk-hp').focus(); return false; }
        var $pw = $('#bk-password');
        if ($pw.length && $pw.val().length < 4) { alert('예약 확인용 비밀번호를 4자 이상 입력해 주세요.'); $pw.focus(); return false; }
        if (!$('#bk-agree').prop('checked')) { alert('취소·환불 규정에 동의해 주세요.'); return false; }
        return true;
    });

    recalc();   // 새로고침으로 select 값이 남아 있을 수 있다
});
</script>
@endsection
