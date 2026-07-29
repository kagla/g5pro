@extends('layout.default')
@section('content')
<header class="bbs-head">
    <h2><span class="chip">주문</span>주문서 작성</h2>
    <a class="btn" href="{{ $cart_url }}">장바구니로</a>
</header>

{{-- 순정 orderform.sub.php 출력 그대로 — 결제수단·PG 연동 JS 가 얽혀 있어 새로 만들지 않는다 --}}
<div class="order-form">{!! $form_html !!}</div>

<script>
// 전화·연락처에 하이픈을 자동으로 넣는다. 값은 결제 모듈이 그대로 쓰므로
// (숫자만 필요한 곳은 순정이 알아서 replace 한다) 표시 형식만 손본다.
(function () {
    function format(v) {
        var n = v.replace(/[^0-9]/g, '').slice(0, 11);
        if (!n) return '';
        // 서울(02)만 지역번호가 두 자리, 나머지는 세 자리. 1588 같은 대표번호는 4-4.
        if (n.startsWith('02')) {
            if (n.length < 3)  return n;
            if (n.length < 7)  return n.slice(0, 2) + '-' + n.slice(2);
            if (n.length < 10) return n.slice(0, 2) + '-' + n.slice(2, 5) + '-' + n.slice(5);
            return n.slice(0, 2) + '-' + n.slice(2, 6) + '-' + n.slice(6, 10);
        }
        if (/^(15|16|18)/.test(n)) {                 // 1544·1600·1800 대표번호
            if (n.length < 5) return n;
            return n.slice(0, 4) + '-' + n.slice(4, 8);
        }
        if (n.length < 4)  return n;
        if (n.length < 8)  return n.slice(0, 3) + '-' + n.slice(3);
        if (n.length < 11) return n.slice(0, 3) + '-' + n.slice(3, 6) + '-' + n.slice(6);
        return n.slice(0, 3) + '-' + n.slice(3, 7) + '-' + n.slice(7, 11);
    }

    ['od_tel', 'od_hp', 'od_b_tel', 'od_b_hp'].forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.setAttribute('inputmode', 'tel');
        el.value = format(el.value);              // 회원정보에서 채워진 값도 정리
        el.addEventListener('input', function () {
            var end = el.selectionStart === el.value.length;   // 끝에서 입력 중인가
            el.value = format(el.value);
            if (end) el.setSelectionRange(el.value.length, el.value.length);
        });
    });

    // 배송지목록에서 값을 채워 넣는 순정 코드 뒤에도 형식을 맞춘다
    var f = document.forderform;
    if (f) f.addEventListener('change', function (e) {
        if (/^od_(b_)?(tel|hp)$/.test(e.target.id)) e.target.value = format(e.target.value);
    }, true);
})();
</script>
@endsection
