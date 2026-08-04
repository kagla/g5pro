{{-- 예약 상세 (booking/view.php) — 본인 확인을 통과한 사람만 닿는 자리 --}}
@extends('layout.default')

{{-- 스타일을 뷰가 지고 다닌다. 이 화면은 template/standard 에만 있고 다른 템플릿에서는
     폴백(extend/pro.10.extend.php 의 $views)으로 그려지므로, 그 템플릿의 style.css 에
     예약 규칙이 있으리라 기대할 수 없다. 색·여백은 어느 템플릿에나 있는 토큰만 쓴다 --}}
@section('head')
<style>
.bk-detail { max-width: 620px; margin: 0 auto; }
.bk-detail > section + section { margin-top: var(--s4); }

.bk-sum-head { display: flex; align-items: center; justify-content: space-between; gap: var(--s3); flex-wrap: wrap; }
.bk-sum-head h3 { margin: 0; }
.bk-detail-no { font-size: var(--t-lg); font-weight: 700; letter-spacing: .1em;
    color: var(--accent); font-variant-numeric: tabular-nums; word-break: break-all; }

.bk-facts { display: flex; flex-direction: column; gap: var(--s2); margin: var(--s3) 0 0; }
.bk-facts > div { display: flex; gap: var(--s3); font-size: var(--t-md); }
.bk-facts dt { flex: 0 0 86px; color: var(--muted); }
.bk-facts dd { margin: 0; min-width: 0; font-variant-numeric: tabular-nums; }
/* 손님이 적은 자유 글이다. 줄바꿈만 CSS 로 살린다 —
   HTML 로 내보내면 값 안의 태그가 그대로 먹는다 */
.bk-facts dd.bk-free { white-space: pre-line; line-height: 1.7; }

.bk-sum { display: flex; flex-direction: column; gap: var(--s2); margin: 0; }
.bk-sum > div { display: flex; justify-content: space-between; gap: var(--s3); font-size: var(--t-md); }
.bk-sum dt { color: var(--muted); }
.bk-sum dd { margin: 0; font-variant-numeric: tabular-nums; }
.bk-sum .bk-total { padding-top: var(--s2); border-top: 1px solid var(--line); }
.bk-sum .bk-total dt { color: var(--fg); font-weight: 700; }
.bk-sum b { font-size: var(--t-lg); color: var(--accent); }

.bk-note { margin: 0; font-size: var(--t-sm); color: var(--muted); line-height: 1.7; }
.bk-note + .bk-note { margin-top: var(--s2); }
.bk-terms { white-space: pre-line; line-height: 1.7; margin: 0;
    max-height: 220px; overflow-y: auto; font-size: var(--t-sm); color: var(--muted); }

.bk-log { list-style: none; margin: var(--s3) 0 0; padding: 0;
    display: flex; flex-direction: column; gap: var(--s3); }
.bk-log li { padding: var(--s3); border-radius: var(--r-sm); background: var(--bg); }
.bk-log li.is-admin { background: var(--accent-soft); }
.bk-log-head { display: flex; gap: var(--s2); align-items: baseline;
    font-size: var(--t-sm); color: var(--muted); margin-bottom: var(--s1); }
.bk-log-head b { color: var(--fg); }
.bk-log-body { margin: 0; white-space: pre-line; line-height: 1.7; font-size: var(--t-md); }

.bk-ask textarea { width: 100%; min-height: 96px; }
.bk-ask button { margin-top: var(--s3); }
.bk-cancel { margin-top: var(--s3); }
.bk-actions { display: flex; gap: var(--s3); margin-top: var(--s4); }
.bk-actions > a { flex: 1 1 0; }
</style>
@endsection

@section('content')
<div class="bbs-head">
    <h2>예약 상세</h2>
    <div class="bbs-head-right"><a class="btn" href="{{ G5_URL }}/booking/lookup.php">예약 조회</a></div>
</div>

@php
    $chip = ($bk['bk_status'] === 'confirmed') ? 'c2' : (($bk['bk_status'] === 'cancelled') ? 'c4' : 'c3');
@endphp

<div class="bk-detail">
    <section class="card">
        <div class="bk-sum-head">
            <h3><span class="bk-detail-no">{{ $bk['bk_no'] }}</span></h3>
            <span class="chip {{ $chip }}">{{ $status_text }}</span>
        </div>
        <dl class="bk-facts">
            <div><dt>객실</dt><dd>{{ $room['br_subject'] }}</dd></div>
            <div><dt>체크인</dt><dd>{{ $bk['bk_checkin'] }} {{ $conf['checkin_time'] }}</dd></div>
            <div><dt>체크아웃</dt><dd>{{ $bk['bk_checkout'] }} {{ $conf['checkout_time'] }}</dd></div>
            <div><dt>숙박</dt><dd>{{ $nights }}박</dd></div>
            <div><dt>인원</dt><dd>{{ $bk['bk_person'] }}명</dd></div>
            <div><dt>예약자</dt><dd>{{ $bk['bk_name'] }} · {{ $bk['bk_hp'] }}</dd></div>
            @if (trim($bk['bk_request']) !== '')
            <div><dt>요청사항</dt><dd class="bk-free">{{ $bk['bk_request'] }}</dd></div>
            @endif
        </dl>
    </section>

    <section class="card">
        <h3>결제 내역</h3>
        <dl class="bk-sum">
            <div><dt>객실 요금 ({{ $nights }}박)</dt><dd>{{ number_format($bk['bk_room_price']) }}원</dd></div>
            <div><dt>인원 추가</dt><dd>{{ number_format($bk['bk_person_price']) }}원</dd></div>
            @foreach ($addon_items as $item)
            <div><dt>{{ $item['bt_subject'] }} × {{ $item['bt_qty'] }}</dt><dd>{{ number_format($item['bt_amount']) }}원</dd></div>
            @endforeach
            <div class="bk-total"><dt>결제 금액</dt><dd><b>{{ number_format($bk['bk_total_price']) }}</b>원</dd></div>
        </dl>
        @if ($pay_time)
        <dl class="bk-facts">
            <div><dt>결제수단</dt><dd>신용카드</dd></div>
            <div><dt>결제일시</dt><dd>{{ $pay_time }}</dd></div>
        </dl>
        @endif
    </section>

    @if ($can_cancel)
    <section class="card">
        <h3>예약 취소</h3>
        <p class="bk-note">지금 취소하시면 취소·환불 규정에 따라
            결제 금액의 <b>{{ $refund_rate }}%</b>인 <b>{{ number_format($refund_price) }}원</b>이 환불될 예정입니다.
            (체크인까지 {{ $days_before }}일)</p>
        <p class="bk-note">실제 환불 금액은 취소 처리 시점에 다시 계산됩니다.</p>
        @if (trim($conf['refund_terms']) !== '')
        <p class="bk-terms">{{ $conf['refund_terms'] }}</p>
        @endif
        <form name="fbkcancel" id="bk-cancel-form" method="post" action="{{ G5_URL }}/booking/cancel_update.php" class="bk-cancel">
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="bk_no" value="{{ $bk['bk_no'] }}">
            <button type="submit" class="btn btn-block">취소 신청</button>
        </form>
    </section>
    @endif

    <section class="card">
        <h3>요청 · 답변</h3>
        @if (count($notes))
        <ul class="bk-log">
            @foreach ($notes as $note)
            <li @if ($note['bn_writer'] === 'admin') class="is-admin" @endif>
                <div class="bk-log-head"><b>{{ $note['writer_text'] }}</b><span>{{ $note['bn_datetime'] }}</span></div>
                <p class="bk-log-body">{{ $note['bn_content'] }}</p>
            </li>
            @endforeach
        </ul>
        @else
        <p class="bk-note">아직 주고받은 내용이 없습니다.</p>
        @endif

        <form name="fbknote" id="bk-note-form" method="post" action="{{ G5_URL }}/booking/note_update.php" class="bk-ask">
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="bk_no" value="{{ $bk['bk_no'] }}">
            <div class="field">
                <label for="bk-note">추가 요청</label>
                <textarea name="bn_content" id="bk-note" rows="4" required></textarea>
            </div>
            <p class="bk-note">금액이 변경되는 요청(인원 · 부가상품 추가)은 전화로 문의해 주세요.</p>
            <button type="submit" class="btn btn-primary btn-block">요청 남기기</button>
        </form>
    </section>

    <div class="bk-actions">
        <a class="btn" href="{{ G5_URL }}/booking/lookup.php">예약 조회</a>
        <a class="btn" href="{{ G5_URL }}/booking/">객실 목록</a>
    </div>
</div>

<script>
jQuery(function ($) {
    // 되돌릴 수 없는 신청이다. 한 번 더 묻는다
    $('#bk-cancel-form').on('submit', function () {
        return confirm('예약 취소를 신청하시겠습니까?');
    });

    // required 를 지원하지 않는 브라우저를 위한 마지막 확인. 서버도 같은 항목을 다시 본다
    $('#bk-note-form').on('submit', function () {
        if (!$.trim($('#bk-note').val())) { alert('요청 내용을 입력해 주세요.'); $('#bk-note').focus(); return false; }
        return true;
    });
});
</script>
@endsection
