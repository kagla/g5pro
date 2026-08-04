{{-- 예약 조회 (booking/lookup.php) — 회원은 내 예약 목록, 비회원은 예약번호+비밀번호 확인 --}}
@extends('layout.default')

{{-- 스타일을 뷰가 지고 다닌다. 이 화면은 template/standard 에만 있고 다른 템플릿에서는
     폴백(extend/pro.10.extend.php 의 $views)으로 그려지므로, 그 템플릿의 style.css 에
     예약 규칙이 있으리라 기대할 수 없다. 색·여백은 어느 템플릿에나 있는 토큰만 쓴다 --}}
@section('head')
<style>
.bk-find { max-width: 420px; margin: 0 auto; }
.bk-find .field + .field { margin-top: var(--s4); }
.bk-find button { margin-top: var(--s4); }
.bk-note { margin: 0; font-size: var(--t-sm); color: var(--muted); line-height: 1.7; }
.bk-note + .bk-note { margin-top: var(--s2); }
.bk-find .bk-note { margin-top: var(--s3); }

.bk-my { display: flex; flex-direction: column; gap: var(--s3); }
.bk-my-item { display: flex; align-items: center; gap: var(--s4); flex-wrap: wrap; }
.bk-my-main { flex: 1 1 240px; min-width: 0; }
.bk-my-main h3 { margin: 0 0 var(--s1); font-size: var(--t-lg); }
.bk-my-when { margin: 0; font-size: var(--t-md); color: var(--muted); font-variant-numeric: tabular-nums; }
.bk-my-no { display: block; margin-top: var(--s1); font-size: var(--t-sm); color: var(--muted);
    letter-spacing: .06em; font-variant-numeric: tabular-nums; }
.bk-my-side { display: flex; align-items: center; gap: var(--s4); margin-left: auto; }
.bk-my-price { font-size: var(--t-md); font-weight: 700; font-variant-numeric: tabular-nums; }
</style>
@endsection

@section('content')
<div class="bbs-head">
    <h2>예약 조회</h2>
    <div class="bbs-head-right"><a class="btn" href="{{ G5_URL }}/booking/">객실 목록</a></div>
</div>

@if ($is_member)
    @if (count($bookings))
    <div class="bk-my">
        @foreach ($bookings as $row)
        <section class="card bk-my-item">
            <div class="bk-my-main">
                <h3>{{ $row['br_subject'] }}</h3>
                <p class="bk-my-when">{{ $row['bk_checkin'] }} ~ {{ $row['bk_checkout'] }} ({{ $row['nights'] }}박) · {{ $row['bk_person'] }}명</p>
                <b class="bk-my-no">예약번호 {{ $row['bk_no'] }}</b>
            </div>
            <div class="bk-my-side">
                <span class="chip c3">{{ $row['status_text'] }}</span>
                <span class="bk-my-price">{{ number_format($row['bk_total_price']) }}원</span>
                <a class="btn" href="{{ G5_URL }}/booking/view.php?bk_no={{ $row['bk_no'] }}">상세보기</a>
            </div>
        </section>
        @endforeach
    </div>
    @else
    <p class="bbs-empty">예약 내역이 없습니다.</p>
    @endif
@else
<section class="card bk-find">
    <h3>비회원 예약 조회</h3>
    <p class="bk-note">예약할 때 받으신 <b>예약번호</b>와 직접 정하신 <b>확인 비밀번호</b>를 입력해 주세요.</p>
    <form name="fbklookup" id="bk-find-form" method="post" action="{{ G5_URL }}/booking/lookup.php" autocomplete="off">
    <input type="hidden" name="token" value="{{ $token }}">
        <div class="field">
            <label for="bk-no">예약번호</label>
            <input type="text" name="bk_no" id="bk-no" required maxlength="20" placeholder="ABCD123456">
        </div>
        <div class="field">
            <label for="bk-pw">확인 비밀번호</label>
            <input type="password" name="bk_password" id="bk-pw" required maxlength="20">
        </div>
        <button type="submit" class="btn btn-primary btn-block">예약 확인</button>
    </form>
    <p class="bk-note">회원으로 예약하셨다면 로그인 후 이 화면에서 예약 내역을 보실 수 있습니다.</p>
</section>
@endif
@endsection
