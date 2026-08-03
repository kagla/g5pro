{{-- 기존 회원 본인인증 (bbs/member_cert_refresh.php)
     이미 가입한 회원이 본인인증을 다시 받는 화면. 인증 창을 여는 함수(call_sa·certify_win_open)는
     순정 js/certify.js 에 있고, 매핑이 그 파일을 싣는다.
     인증사 분기는 매핑에서 값으로 풀어 넘기므로 여기서는 기관 이름을 알 필요가 없다. --}}
@extends('layout.default')
@section('content')

<div class="member-box cert-box">
    <h2>본인인증</h2>
    <p class="muted">서비스 이용을 위해 본인확인이 필요합니다.</p>

    <form name="fcertrefreshform" id="member_cert_refresh" method="post" action="{{ $action }}" autocomplete="off">
        {{-- 순정 member_cert_refresh_update.php 계약 — 인증 결과가 cert_no 로 돌아온다 --}}
        <input type="hidden" name="w" value="{{ $w }}">
        <input type="hidden" name="url" value="{{ $url }}">
        <input type="hidden" name="cert_type" value="{{ $member['mb_certify'] }}">
        <input type="hidden" name="mb_id" value="{{ $member['mb_id'] }}">
        <input type="hidden" name="mb_hp" value="{{ $member['mb_hp'] }}">
        <input type="hidden" name="mb_name" value="{{ $member['mb_name'] }}">
        <input type="hidden" name="cert_no" value="">

        <section class="cert-terms">
            <h3>(필수) 추가 개인정보처리방침 안내</h3>
            <div class="list-panel">
                <div class="list-table-wrap">
                <table class="list-table">
                    <thead>
                    <tr><th scope="col">목적</th><th scope="col">항목</th><th scope="col">보유기간</th></tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>이용자 식별 및 본인여부 확인</td>
                        <td>생년월일@if (!$member['has_dupinfo']), 휴대폰 번호(아이핀 제외)@endif, 암호화된 개인식별부호(CI)</td>
                        <td>회원 탈퇴 시까지</td>
                    </tr>
                    </tbody>
                </table>
                </div>
            </div>
            <label class="inline-chk cert-agree">
                <input type="checkbox" name="agree2" value="1" id="agree21">
                추가 개인정보처리방침에 동의합니다.
            </label>
        </section>

        <section class="cert-ways">
            <h3>인증수단 선택</h3>
            @if (!$ways)
                <p class="bbs-empty">사용할 수 있는 인증수단이 없습니다. 관리자에게 문의하세요.</p>
            @else
            <div class="cert-btns">
                @foreach ($ways as $way)
                <button type="button" class="btn btn-primary cert-btn"
                        data-open="{{ $way['open'] }}" data-url="{{ $way['url'] }}" data-type="{{ $way['type'] }}">
                    {{ $way['label'] }}
                </button>
                @endforeach
            </div>
            <noscript><p class="muted">본인확인을 하려면 자바스크립트를 켜야 합니다.</p></noscript>
            @endif
        </section>
    </form>
</div>

<script>
(function () {
    var f = document.fcertrefreshform;
    if (!f) return;

    // 동의 없이는 인증 창을 열지 않는다 (순정과 같은 규칙)
    function agreed() {
        if (!f.agree2.checked) {
            alert("추가 개인정보처리방침에 동의하셔야 인증을 진행하실 수 있습니다.");
            f.agree2.focus();
            return false;
        }
        return true;
    }

    var params = "?pageType=register";

    document.querySelectorAll('.cert-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!agreed()) return;
            var url = btn.dataset.url, type = btn.dataset.type;
            if (btn.dataset.open === 'sa') {
                // 간편인증 — 기관은 directAgency 로 넘긴다 (빈 값이면 선택 화면이 뜬다)
                call_sa(url + "?directAgency=" + type + "&pageType=register");
            } else {
                certify_win_open(type, url + params);
            }
        });
    });
})();
</script>
@endsection
