{{-- 결제 화면 (booking/pay.php) — 이니시스 표준결제(INIStdPay) 결제창을 띄운다 --}}
@extends('layout.default')

@section('head')
{{-- 스타일을 뷰가 지고 다닌다 (reserve.blade.php 와 같은 이유) — 색·여백은 어느 템플릿에나
     있는 토큰만 쓴다 --}}
<style>
.bk-pay { max-width: 620px; margin: 0 auto; }
.bk-pay > section + section { margin-top: var(--s4); }

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

/* 남은 시간 — 숫자가 바뀔 때 폭이 흔들리지 않게 tabular-nums 로 고정한다 */
.bk-timer { display: flex; align-items: baseline; gap: var(--s2); justify-content: center;
    margin: 0 0 var(--s3); font-size: var(--t-md); color: var(--muted); }
.bk-timer b { font-size: var(--t-lg); color: var(--accent); font-variant-numeric: tabular-nums; }
.bk-timer.is-over b { color: var(--muted); }
.bk-note { margin: 0; font-size: var(--t-sm); color: var(--muted); }
.bk-note + .bk-note { margin-top: var(--s2); }
</style>
@endsection

@section('content')
<div class="bbs-head">
    <h2>결제</h2>
</div>

<div class="bk-pay">
    <section class="card">
        <h3>예약 내용</h3>
        <dl class="bk-facts">
            <div><dt>객실</dt><dd>{{ $room['br_subject'] }}</dd></div>
            <div><dt>체크인</dt><dd>{{ $bk['bk_checkin'] }} {{ $checkin_time }}</dd></div>
            <div><dt>체크아웃</dt><dd>{{ $bk['bk_checkout'] }} {{ $checkout_time }}</dd></div>
            <div><dt>숙박</dt><dd>{{ $nights }}박</dd></div>
            <div><dt>인원</dt><dd>{{ $bk['bk_person'] }}명</dd></div>
            <div><dt>예약자</dt><dd>{{ $bk['bk_name'] }} · {{ $bk['bk_hp'] }}</dd></div>
            <div><dt>예약번호</dt><dd>{{ $bk['bk_no'] }}</dd></div>
        </dl>
    </section>

    <section class="card">
        <h3>결제 금액</h3>
        <dl class="bk-sum">
            <div><dt>객실 요금 ({{ $nights }}박)</dt><dd>{{ number_format($bk['bk_room_price']) }}원</dd></div>
            <div><dt>인원 추가</dt><dd>{{ number_format($bk['bk_person_price']) }}원</dd></div>
            @foreach ($addon_items as $item)
            <div><dt>{{ $item['bt_subject'] }} × {{ $item['bt_qty'] }}</dt><dd>{{ number_format($item['bt_amount']) }}원</dd></div>
            @endforeach
            <div class="bk-total"><dt>합계</dt><dd><b>{{ number_format($bk['bk_total_price']) }}</b>원</dd></div>
        </dl>

        {{-- 남은 시간이 0 이 되면 아래 JS 가 버튼을 잠근다. 잠기지 않아도 서버가 다시 막는다
             (makesignature.php 가 만료된 hold 에는 서명을 내주지 않는다) --}}
        <p class="bk-timer" id="bk-timer" data-left="{{ $left }}">
            결제 유효시간 <b id="bk-timer-left">--:--</b>
        </p>

        <button type="button" class="btn btn-primary btn-block" id="bk-pay-btn">카드로 결제하기</button>
        <p class="bk-note">결제창이 뜨지 않으면 브라우저의 팝업 차단을 해제한 뒤 다시 눌러 주세요.</p>
        <p class="bk-note">유효시간이 지나면 예약이 자동으로 취소되고 객실이 다시 열립니다.</p>
    </section>
</div>

{{-- 이니시스 결제창은 폼을 id 로 찾는다 (INIStdPay.pay 에 넘기는 것이 name 이 아니라 id 다).
     금액·주문번호는 화면에 보이는 값이 아니라 서버가 다시 읽은 값으로 서명된다 —
     이 칸을 개발자도구로 고쳐도 서명이 맞지 않아 결제창이 열리지 않는다 --}}
<form name="fbookingpay" id="fbookingpay" method="post" autocomplete="off">
    <input type="hidden" name="version" value="1.0">
    <input type="hidden" name="mid" value="{{ $conf['mid'] }}">
    <input type="hidden" name="oid" value="{{ $oid }}">
    <input type="hidden" name="goodname" value="{{ $room['br_subject'] }} {{ $bk['bk_checkin'] }} {{ $nights }}박">
    <input type="hidden" name="price" value="{{ $bk['bk_total_price'] }}">
    <input type="hidden" name="buyername" value="{{ $bk['bk_name'] }}">
    <input type="hidden" name="buyeremail" value="{{ $bk['bk_email'] }}">
    <input type="hidden" name="buyertel" value="{{ $bk['bk_hp'] }}">
    <input type="hidden" name="currency" value="WON">
    <input type="hidden" name="gopaymethod" value="Card">
    <input type="hidden" name="acceptmethod" value="below1000:centerCd(Y)">
    <input type="hidden" name="timestamp" value="">
    <input type="hidden" name="signature" value="">
    <input type="hidden" name="mKey" value="">
    <input type="hidden" name="returnUrl" value="{{ $return_url }}">
    <input type="hidden" name="closeUrl" value="{{ $close_url }}">
    <input type="hidden" name="charset" value="UTF-8">
    <input type="hidden" name="payViewType" value="overlay">
</form>

<script src="{{ $conf['js_url'] }}" charset="UTF-8"></script>
<script>
jQuery(function ($) {
    var $btn = $('#bk-pay-btn');
    var $timer = $('#bk-timer');
    var left = +$timer.attr('data-left') || 0;

    function two(n) { return (n < 10 ? '0' : '') + n; }

    function tick()
    {
        if (left <= 0) {
            $timer.addClass('is-over').html('결제 유효시간이 지났습니다. 처음부터 다시 예약해 주세요.');
            $btn.prop('disabled', true).text('유효시간 만료');
            return;
        }
        $('#bk-timer-left').text(two(Math.floor(left / 60)) + ':' + two(left % 60));
        left--;
        setTimeout(tick, 1000);
    }
    tick();

    // 서명은 서버에서 받아 온다. 결제창을 여는 것과 순서가 뒤바뀌면 안 되므로 동기 호출이다
    // (shop/inicis/orderform.1.php 의 make_signature() 와 같은 방식)
    function makeSignature(frm)
    {
        var ok = true;
        $.ajax({
            url: "{{ $sign_url }}",
            type: "POST",
            dataType: "json",
            async: false,
            cache: false,
            success: function (data) {
                if (data.error === "") {
                    frm.timestamp.value = data.timestamp;
                    frm.signature.value = data.sign;
                    frm.mKey.value = data.mKey;
                } else {
                    alert(data.error);
                    ok = false;
                }
            },
            error: function () {
                alert('결제 준비 중 오류가 생겼습니다. 잠시 후 다시 시도해 주세요.');
                ok = false;
            }
        });
        return ok;
    }

    $btn.on('click', function () {
        if (left <= 0) { alert('결제 유효시간이 지났습니다. 다시 예약해 주세요.'); return; }
        var frm = document.getElementById('fbookingpay');
        if (!makeSignature(frm)) return;
        if (typeof INIStdPay === 'undefined') {
            alert('결제 모듈을 불러오지 못했습니다. 새로고침 후 다시 시도해 주세요.');
            return;
        }
        INIStdPay.pay(frm.id);   // name 이 아니라 id 로 찾는다
    });
});
</script>
@endsection
