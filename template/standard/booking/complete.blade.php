{{-- 예약 완료 (booking/complete.php) — 결제 승인이 끝나고 예약이 확정된 자리 --}}
@extends('layout.default')

@section('head')
<style>
.bk-done { max-width: 620px; margin: 0 auto; }
.bk-done > section + section { margin-top: var(--s4); }

/* 예약번호는 이 화면에서 가장 중요한 정보다 — 크게, 고르게, 그대로 옮겨 적을 수 있게 */
.bk-done-head { text-align: center; }
.bk-done-head h3 { margin: 0 0 var(--s2); }
.bk-done-no { display: block; margin: var(--s3) 0; font-size: var(--t-xl); font-weight: 700;
    letter-spacing: .12em; color: var(--accent); font-variant-numeric: tabular-nums;
    word-break: break-all; }

.bk-facts { display: flex; flex-direction: column; gap: var(--s2); margin: 0; }
.bk-facts > div { display: flex; gap: var(--s3); font-size: var(--t-md); }
.bk-facts dt { flex: 0 0 86px; color: var(--muted); }
.bk-facts dd { margin: 0; font-variant-numeric: tabular-nums; }

.bk-sum { display: flex; flex-direction: column; gap: var(--s2); margin: 0; }
.bk-sum > div { display: flex; justify-content: space-between; gap: var(--s3); font-size: var(--t-md); }
.bk-sum dt { color: var(--muted); }
.bk-sum dd { margin: 0; font-variant-numeric: tabular-nums; }
.bk-sum .bk-total { padding-top: var(--s2); border-top: 1px solid var(--line); }
.bk-sum .bk-total dt { color: var(--fg); font-weight: 700; }
.bk-sum b { font-size: var(--t-lg); color: var(--accent); }

.bk-note { margin: 0; font-size: var(--t-sm); color: var(--muted); line-height: 1.7; }
.bk-note + .bk-note { margin-top: var(--s2); }
/* 환불 약관은 관리자가 넣은 평문이다. 줄바꿈만 CSS 로 살린다 */
.bk-terms { white-space: pre-line; line-height: 1.7; margin: 0;
    max-height: 220px; overflow-y: auto; font-size: var(--t-sm); color: var(--muted); }
.bk-actions { display: flex; gap: var(--s3); margin-top: var(--s4); }
.bk-actions > a { flex: 1 1 0; }
</style>
@endsection

@section('content')
<div class="bbs-head">
    <h2>예약 완료</h2>
</div>

<div class="bk-done">
    <section class="card bk-done-head">
        <h3>결제가 완료되어 예약이 확정되었습니다.</h3>
        <b class="bk-done-no">{{ $bk['bk_no'] }}</b>
        <p class="bk-note">위 <b>예약번호</b>를 꼭 보관해 주세요. 예약 확인·변경·취소에 쓰입니다.</p>
        @if ($bk['bk_email'])
        <p class="bk-note">{{ $bk['bk_email'] }} 로 예약 확정 안내 메일을 보냈습니다.</p>
        @endif
    </section>

    <section class="card">
        <h3>예약 내용</h3>
        <dl class="bk-facts">
            <div><dt>객실</dt><dd>{{ $room['br_subject'] }}</dd></div>
            <div><dt>체크인</dt><dd>{{ $bk['bk_checkin'] }} {{ $conf['checkin_time'] }}</dd></div>
            <div><dt>체크아웃</dt><dd>{{ $bk['bk_checkout'] }} {{ $conf['checkout_time'] }}</dd></div>
            <div><dt>숙박</dt><dd>{{ $nights }}박</dd></div>
            <div><dt>인원</dt><dd>{{ $bk['bk_person'] }}명</dd></div>
            <div><dt>예약자</dt><dd>{{ $bk['bk_name'] }} · {{ $bk['bk_hp'] }}</dd></div>
            @if (trim($bk['bk_request']) !== '')
            <div><dt>요청사항</dt><dd>{{ $bk['bk_request'] }}</dd></div>
            @endif
        </dl>
    </section>

    <section class="card">
        <h3>결제 금액</h3>
        <dl class="bk-sum">
            <div><dt>객실 요금 ({{ $nights }}박)</dt><dd>{{ number_format($bk['bk_room_price']) }}원</dd></div>
            <div><dt>인원 추가</dt><dd>{{ number_format($bk['bk_person_price']) }}원</dd></div>
            @foreach ($addon_items as $item)
            <div><dt>{{ $item['bt_subject'] }} × {{ $item['bt_qty'] }}{{ $item['bt_unit'] == 'night' ? ' × '.$nights.'박' : '' }}</dt><dd>{{ number_format($item['bt_amount']) }}원</dd></div>
            @endforeach
            <div class="bk-total"><dt>결제하신 금액</dt><dd><b>{{ number_format($bk['bk_total_price']) }}</b>원</dd></div>
        </dl>
    </section>

    <section class="card">
        <h3>예약 확인 방법</h3>
        @if ($is_member)
        <p class="bk-note">로그인한 계정의 예약 내역에서 언제든 확인하실 수 있습니다.</p>
        @else
        <p class="bk-note">비회원 예약입니다. <b>예약번호</b>와 예약할 때 입력하신 <b>확인 비밀번호</b>로
            예약 내용을 조회하고 취소를 요청할 수 있습니다.</p>
        <p class="bk-note">비밀번호는 다시 알려 드릴 수 없습니다. 잊으셨다면 연락처로 문의해 주세요.</p>
        @endif
        <p class="bk-note"><a href="{{ G5_URL }}/booking/lookup.php">예약 조회하기</a></p>
    </section>

    @if (trim($conf['refund_terms']) !== '')
    <section class="card">
        <h3>취소·환불 규정</h3>
        <p class="bk-terms">{{ $conf['refund_terms'] }}</p>
    </section>
    @endif

    <div class="bk-actions">
        <a class="btn btn-block" href="{{ G5_URL }}/booking/">객실 목록</a>
        <a class="btn btn-primary btn-block" href="{{ G5_URL }}/">홈으로</a>
    </div>
</div>
@endsection
