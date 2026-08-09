@extends('layout.default')
@section('content')
<header class="bbs-head">
    <h2>주문서 작성</h2>

    @if ($blocked_count > 0)
    <div class="bbs-meta">구매할 수 없는 상품 {{ $blocked_count }}개는 <a href="{{ $cart_href }}">장바구니</a>에 남겨두었습니다.</div>
    @endif

</header>

<div class="cart-checkout">
{{-- novalidate: 우편번호·주소가 readonly 라 브라우저 required 검증에서 빠진다 — 검증은 아래 스크립트가 한 곳에서 --}}
<form method="post" action="{{ $action_url }}" class="cart-checkout-form" novalidate>
    <input type="hidden" name="token" value="{{ $token }}">
    <input type="hidden" name="expect_ct_ids" value="{{ $expect_ct_ids }}">
    <input type="hidden" name="expect_item_total" value="{{ $item_total }}">
    <input type="hidden" name="buy" value="{{ $buy }}">

    <section class="cart-co-sec">
        <h3>주문 상품</h3>

        @foreach ($lines as $l)
        <div class="cart-cart-row">
            <span class="cart-cart-thumb">

                @if ($l['img'])
                <img src="{{ $l['img'] }}" alt="">
                @endif

            </span>
            <div class="cart-cart-info">
                <span class="cart-cart-name">{{ $l['it_name'] }}</span>

                @if ($l['opt_label'] !== '')
                <span class="cart-cart-opt">{{ $l['opt_label'] }}</span>
                @endif

                <span class="cart-cart-opt">{{ number_format($l['sk_price']) }}원 × {{ $l['ct_qty'] }}개</span>
            </div>
            <span class="cart-cart-line">{{ number_format($l['line_total']) }}<em>원</em></span>
        </div>
        @endforeach

    </section>

    <section class="cart-co-sec">
        <h3>주문하시는 분</h3>
        <p class="cart-co-note">받는분은 주문하시는 분과 같습니다.</p>

        {{-- 회원이 전에 쓴 배송지 — 주문자(이름·연락처)까지 함께 저장돼 있어 한 번에 채워진다.
             목록이 비면 줄 전체를 감춘다(마지막 하나를 지운 뒤에도 이 자리가 남지 않게) --}}
        @if ($is_member)
        <p class="cart-addr-pick" style="{{ count($addresses) ? '' : 'display:none' }}">
            <select id="cart_addr_pick">
                <option value="">저장된 배송지 불러오기</option>

                @foreach ($addresses as $a)
                <option value="{{ $a['ad_id'] }}" data-name="{{ $a['ad_name'] }}" data-hp="{{ $a['ad_hp'] }}" data-email="{{ isset($a['ad_email']) ? $a['ad_email'] : '' }}" data-zip="{{ $a['ad_zip'] }}" data-addr1="{{ $a['ad_addr1'] }}" data-addr2="{{ $a['ad_addr2'] }}">{{ $a['ad_name'] !== '' ? $a['ad_name'].' · ' : '' }}[{{ $a['ad_zip'] }}] {{ $a['ad_addr1'] }} {{ $a['ad_addr2'] }}</option>
                @endforeach

            </select>
            <button type="button" id="cart_addr_del" class="cart-addr-del">삭제</button>
        </p>
        @endif

        <div class="cart-co-grid" style="margin-bottom: var(--s3)">
            <label><span class="req">이름</span> <input type="text" name="od_name" value="{{ $default_name }}" required data-req-msg="이름을 입력해 주세요."></label>
            <label><span class="req">연락처</span> <input type="text" name="od_hp" value="{{ $default_hp }}" placeholder="010-0000-0000" required data-req-msg="연락처를 입력해 주세요."></label>
            {{-- autocomplete 를 끄는 이유: 아래 "주문 비밀번호"가 password 칸이라 브라우저가 이 폼을
                 로그인 폼으로 보고, 이메일 칸에 저장된 아이디를(예: admin) 비밀번호 칸에 저장된
                 비밀번호를 채워 넣었다. new-password 는 저장된 자격증명을 채우지 말라는 표준 신호다. --}}
            <label><span class="req">이메일</span> <input type="email" name="od_email" value="{{ $default_email }}" autocomplete="off" required data-req-msg="이메일을 입력해 주세요."></label>

            @if (!$is_member)
            <label><span class="req">주문 비밀번호</span> <input type="password" name="guest_pw" minlength="4" placeholder="주문 조회에 사용 (4자 이상)" autocomplete="new-password" required data-req-msg="주문 비밀번호를 4자 이상 입력해 주세요."></label>
            @endif

        </div>

        <h4 class="cart-co-sub"><span class="req">배송지</span></h4>
        <div class="cart-co-addr">
            <div class="cart-co-zip">
                <input type="text" name="od_zip" id="od_zip" value="" placeholder="우편번호" readonly required data-req-msg="주소 검색으로 배송지를 입력해 주세요.">
                <button type="button" class="btn-ghost" id="cart_zip_btn">주소 검색</button>
            </div>
            <input type="text" name="od_addr1" id="od_addr1" value="" placeholder="주소" readonly required data-req-msg="주소 검색으로 배송지를 입력해 주세요.">
            <input type="text" name="od_addr2" id="od_addr2" value="" placeholder="상세 주소" required data-req-msg="상세 주소를 입력해 주세요.">
            {{-- 배송 요청사항 — 자주 쓰는 문구를 고르고, 그 밖의 내용만 직접 적는다.
                 고른 문구도 결국 od_memo 한 칸에 담겨 서버는 지금과 똑같이 받는다 --}}
            <select id="od_memo_pick">
                <option value="">배송 요청사항 (선택)</option>
                <option>부재 시 경비실에 맡겨 주세요</option>
                <option>부재 시 문 앞에 놓아 주세요</option>
                <option>부재 시 택배함에 넣어 주세요</option>
                <option>배송 전에 연락 주세요</option>
                <option>파손 위험 상품입니다. 조심히 다뤄 주세요</option>
                <option value="custom">직접 입력</option>
            </select>
            <input type="text" name="od_memo" id="od_memo" value="" placeholder="요청사항을 입력해 주세요" style="display:none">
        </div>
        <p class="cart-co-note">* 표시는 필수 입력입니다.</p>

        @if (!$is_member)
        <p class="cart-co-note">비회원 주문입니다 — 주문번호와 비밀번호로 주문을 조회할 수 있습니다.</p>
        @endif

    </section>

    <section class="cart-co-sec">
        <h3>결제 수단</h3>

        @foreach ($pay_methods as $mkey => $mlabel)
        <label class="cart-co-pay"><input type="radio" name="pay" value="{{ $mkey }}" {{ $mkey === 'bank' ? 'checked' : '' }}> {{ $mlabel }}</label>
        @endforeach

        <label id="cart_depositor_row">입금자명 <input type="text" name="od_depositor" value="" placeholder="비우면 주문자 이름"></label>
    </section>

    <aside class="cart-cart-sum">
        <dl>
            <dt>상품 합계</dt>
            <dd>{{ number_format($item_total) }}원</dd>
            <dt>배송비</dt>
            <dd id="cart_ship_fee">계산 중</dd>
            <dt>결제 예정</dt>
            <dd id="cart_pay_total">{{ number_format($item_total) }}원</dd>
        </dl>
        <button type="submit" class="cart-cta">주문하기</button>
    </aside>
</form>

{{-- PG 결제창용 — 중첩 폼은 안 되니 주문서 폼 밖에 둔다. 필드는 결제 시도마다 JS 가 채운다 --}}
<form id="cart_pg_form" method="post" style="display:none"></form>
</div>

<script src="https://t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<script>
// 배송비 미리보기 — 서버 규칙(cart_shipping_fee)의 JS 거울. 확정은 항상 서버가 한다.
var CART_SHIP = { base: {{ $ship['base'] }}, free: {{ $ship['free'] }}, jeju: {{ $ship['jeju'] }}, itemTotal: {{ $item_total }} };

function cartShipPreview() {
    var zip = $('#od_zip').val().replace(/[^0-9]/g, '');
    var fee = CART_SHIP.base;
    if (CART_SHIP.free > 0 && CART_SHIP.itemTotal >= CART_SHIP.free) fee = 0;
    if (zip.length === 5 && zip.substring(0, 2) === '63') fee += CART_SHIP.jeju;
    $('#cart_ship_fee').text(fee.toLocaleString() + '원');
    $('#cart_pay_total').text((CART_SHIP.itemTotal + fee).toLocaleString() + '원');
}

function cartSearchZip() {
    new daum.Postcode({
        oncomplete: function (data) {
            $('#od_zip').val(data.zonecode);
            $('#od_addr1').val(data.roadAddress || data.jibunAddress);
            $('#od_addr2').trigger('focus');
            cartShipPreview();
        }
    }).open();
}

function cartPayToggle() {
    $('#cart_depositor_row').toggle($('input[name="pay"]:checked').val() === 'bank');
}

// ── PG 결제를 주문서 화면에서 바로 연다 ──
// 결제 전에는 주문이 저장되지 않는다: 제출마다 ajax 로 초안을 만들고(이전 초안은 서버가 교체)
// pay.php ajax 로 새 oid 의 결제창 파라미터를 받아 연다. 결제창을 닫거나 실패해도
// 장바구니가 그대로라 주문서에서 값을 고쳐 다시 제출하면 된다.
var cartPgLoaded = {};

function cartPgScript(url, cb) {
    if (cartPgLoaded[url]) { cb(); return; }
    $.getScript(url).done(function () { cartPgLoaded[url] = 1; cb(); })
        .fail(function () { alert('결제 모듈을 불러오지 못했습니다. 잠시 후 다시 시도해 주세요.'); });
}

function cartPgOpen(odNo) {
    $.getJSON('{{ cart_url('pay.php') }}', {od_no: odNo, ajax: 1})
        .done(function (r) {
            if (r && r.redirect) { location.href = r.redirect; return; }
            if (!r || !r.ok) { alert((r && r.error) || '결제를 시작하지 못했습니다. 다시 시도해 주세요.'); return; }
            if (r.method === 'inicis') {
                cartPgScript(r.pg.js_url, function () {
                    var $pgf = $('#cart_pg_form').empty();
                    $.each(r.pg.fields, function (k, v) {
                        $('<input type="hidden">').attr('name', k).val(v).appendTo($pgf);
                    });
                    INIStdPay.pay('cart_pg_form');
                });
            } else {
                cartPgScript(r.pg.js_url, function () {
                    // 창을 닫으면 reject 로 돌아온다 — 주문서에 머물며 다시 열 수 있게 조용히 삼킨다
                    TossPayments(r.pg.ckey).requestPayment('카드', r.pg.params).catch(function () {});
                });
            }
        })
        .fail(function () { alert('결제를 시작하지 못했습니다. 잠시 후 다시 시도해 주세요.'); });
}

$(function () {
    var $form = $('.cart-checkout-form');

    $('#cart_zip_btn').on('click', cartSearchZip);
    $('input[name="pay"]').on('change', cartPayToggle);

    // 연락처 하이픈 자동 — 숫자만 남기고 02 는 2-х-4, 나머지는 3-х-4 로 끼워 넣는다
    $('input[name="od_hp"]').on('input', function () {
        var d = this.value.replace(/[^0-9]/g, '').substring(0, 11);
        var head = d.substring(0, 2) === '02' ? 2 : 3;
        var out = d;
        if (d.length > head + 4) {
            out = d.substring(0, head) + '-' + d.substring(head, d.length - 4) + '-' + d.substring(d.length - 4);
        } else if (d.length > head) {
            out = d.substring(0, head) + '-' + d.substring(head);
        }
        $(this).val(out);
    });

    // 저장된 배송지 선택 — 주문자(이름·연락처·이메일)와 주소를 한 번에 채우고 배송비 미리보기를
    // 갱신한다. 주문자 값은 저장돼 있을 때만 덮는다(옛 형식 주소록 보호).
    $('#cart_addr_pick').on('change', function () {
        var $o = $(this).find(':selected');
        if (!$o.val()) return;
        if ($o.data('name')) $('input[name="od_name"]').val($o.data('name')).removeClass('is-invalid');
        if ($o.data('hp')) $('input[name="od_hp"]').val($o.data('hp')).removeClass('is-invalid');
        if ($o.data('email')) $('input[name="od_email"]').val($o.data('email')).removeClass('is-invalid');
        $('#od_zip').val($o.data('zip'));
        $('#od_addr1').val($o.data('addr1'));
        $('#od_addr2').val($o.data('addr2')).removeClass('is-invalid');
        $('#od_zip, #od_addr1').removeClass('is-invalid');
        cartShipPreview();
    });
    // 배송 요청사항 — 고른 문구를 od_memo 에 담고, "직접 입력"일 때만 칸을 연다.
    // 값은 언제나 od_memo 하나로 나가므로 서버는 고름·직접입력을 구분할 필요가 없다.
    $('#od_memo_pick').on('change', function () {
        var v = $(this).val(), $memo = $('#od_memo');
        if (v === 'custom') {
            $memo.val('').show().trigger('focus');
            return;
        }
        // 고른 문구 그대로 담고 칸은 감춘다(감춰도 값은 함께 제출된다)
        $memo.val(v).hide();
    });

    // 저장된 배송지 삭제 — 고른 것만 지운다. 주문서에 이미 채워진 값은 건드리지 않는다
    // (지우는 것은 주소록일 뿐, 지금 쓰려던 배송지를 비우면 놀란다).
    $('#cart_addr_del').on('click', function () {
        var $sel = $('#cart_addr_pick'), id = $sel.val();
        if (!id) { alert('지울 배송지를 고르세요.'); return; }
        if (!confirm('저장된 배송지를 목록에서 지울까요?\n' + $.trim($sel.find(':selected').text()))) return;

        var $btn = $(this).prop('disabled', true);
        $.ajax({
            type: 'POST',
            url: '{{ $address_url }}',
            data: {token: '{{ $token }}', ad_id: id},
            dataType: 'json'
        }).done(function (res) {
            if (!res || !res.ok) { alert((res && res.msg) ? res.msg : '지우지 못했습니다.'); return; }
            $sel.find('option').not('[value=""]').remove();
            $.each(res.addresses, function (i, a) {
                // 값은 속성으로 넣는다 — 주소·이름이 사용자 입력이라 문자열로 이어 붙이지 않는다
                $('<option>').val(a.id)
                    .attr({'data-name': a.name, 'data-hp': a.hp, 'data-email': a.email,
                           'data-zip': a.zip, 'data-addr1': a.addr1, 'data-addr2': a.addr2})
                    .text((a.name ? a.name + ' · ' : '') + '[' + a.zip + '] ' + a.addr1 + ' ' + a.addr2)
                    .appendTo($sel);
            });
            $sel.val('');
            if (!res.addresses.length) $('.cart-addr-pick').hide();
        }).fail(function () {
            alert('지우지 못했습니다. 로그인 상태를 확인해 주세요.');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    $form.on('input change', '.is-invalid', function () { $(this).removeClass('is-invalid'); });

    $form.on('submit', function () {
        // 필수값은 화면에서 먼저 잡는다 — 폼이 novalidate 라 required 는 표시·선택자 용도다
        var ok = true;
        $form.find('input[required]').each(function () {
            var $el = $(this);
            var empty = $.trim($el.val()) === '';
            var short = !empty && this.minLength > 0 && $el.val().length < this.minLength;
            // 이메일은 형식까지 화면에서 잡는다 — 서버(checkout_update)와 같은 규칙
            var badmail = !empty && this.type === 'email' && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test($.trim($el.val()));
            if (!empty && !short && !badmail) return;
            alert(badmail ? '이메일을 올바르게 입력해 주세요.' : $el.data('req-msg'));
            $el.addClass('is-invalid');
            // readonly 주소칸은 직접 입력이 안 되니 검색창을 바로 열어준다
            if ($el.prop('readonly')) cartSearchZip();
            else $el.trigger('focus');
            ok = false;
            return false;
        });
        if (!ok) return false;

        // 무통장은 확인창으로 한 번 물은 뒤 일반 제출(완료 페이지로 이동)
        if ($('input[name="pay"]:checked').val() === 'bank') {
            return confirm('이 내용대로 주문하시겠습니까?');
        }

        // PG — 초안 접수부터 결제창까지 이 화면에서. 주문서는 결제가 끝나야 저장된다.
        $.post($form.attr('action'), $form.serialize() + '&ajax=1', null, 'json')
            .done(function (r) {
                if (r && r.error) { alert(r.error); return; }
                if (r && r.redirect) { location.href = r.redirect; return; }
                if (!r || !r.ok || !r.od_no) { alert('결제를 시작하지 못했습니다. 다시 시도해 주세요.'); return; }
                cartPgOpen(r.od_no);
            })
            .fail(function () { alert('결제를 시작하지 못했습니다. 잠시 후 다시 시도해 주세요.'); });
        return false;
    });

    cartShipPreview();
    cartPayToggle();
});
</script>
@endsection
