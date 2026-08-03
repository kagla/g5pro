{{-- 접속자 카드 — 순정 head.php 의 connect() 자리. 헤더에 상시로 두면 모든 화면을
     따라다녀 첫 화면에만 놓는다. 세는 조건은 bbs/current_connect.php 와 같다 --}}
@php $conn = g5_connect_summary(); @endphp
<section class="card connect-card">
    <h3>
        <span class="chip c3">접속</span>
        <a href="{{ $conn['href'] }}">현재접속자</a>
    </h3>

    <div class="connect-now">
        <span class="connect-num">{{ number_format($conn['total']) }}</span>
        <span class="muted">명이 보고 있습니다</span>
    </div>

    <div class="connect-split">
        <span class="chip">회원 {{ number_format($conn['members']) }}</span>
        <span class="chip c4">손님 {{ number_format($conn['guests']) }}</span>
    </div>
</section>
