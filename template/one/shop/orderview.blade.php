{{-- 주문 상세 조회 (shop/orderinquiryview.php) --}}
@extends('layout.default')
@section('content')
<header class="bbs-head">
    <h2><span class="chip">주문</span>주문상세내역</h2>
    <div class="bbs-head-right">
        <div class="bbs-meta">{{ $od_time }}</div>
        @if ($admin_href)
        <a class="icon-btn bbs-admin-link" href="{!! $admin_href !!}" title="주문 관리" aria-label="주문 관리">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3.2"/><path d="M19.1 14.6a1.5 1.5 0 0 0 .3 1.7l.1.1a1.9 1.9 0 1 1-2.7 2.7l-.1-.1a1.5 1.5 0 0 0-1.7-.3 1.5 1.5 0 0 0-.9 1.4v.2a1.9 1.9 0 1 1-3.8 0v-.1a1.5 1.5 0 0 0-1-1.4 1.5 1.5 0 0 0-1.7.3l-.1.1a1.9 1.9 0 1 1-2.7-2.7l.1-.1a1.5 1.5 0 0 0 .3-1.7 1.5 1.5 0 0 0-1.4-.9h-.2a1.9 1.9 0 1 1 0-3.8h.1a1.5 1.5 0 0 0 1.4-1 1.5 1.5 0 0 0-.3-1.7l-.1-.1a1.9 1.9 0 1 1 2.7-2.7l.1.1a1.5 1.5 0 0 0 1.7.3h.1a1.5 1.5 0 0 0 .9-1.4v-.2a1.9 1.9 0 1 1 3.8 0v.1a1.5 1.5 0 0 0 .9 1.4 1.5 1.5 0 0 0 1.7-.3l.1-.1a1.9 1.9 0 1 1 2.7 2.7l-.1.1a1.5 1.5 0 0 0-.3 1.7v.1a1.5 1.5 0 0 0 1.4.9h.2a1.9 1.9 0 1 1 0 3.8h-.1a1.5 1.5 0 0 0-1.4.9Z"/></svg>
        </a>
        @endif
    </div>
</header>

{{-- 주문 상품 — 순정 표는 rowspan/colspan 2줄 머리라 반응형으로 다루기 어렵다.
     같은 데이터를 옵션 한 줄씩으로 펴서 우리 표/카드로 그린다 (순정 표는 CSS 로 감춘다).
     나머지(결제·배송 정보, 합계, 주문취소)는 순정 출력 그대로 — 폼·팝업 JS 가 얽혀 있다. --}}
<div class="odv" data-items="{{ count($items) }}">
{{-- 순정 #sod_fin_no 는 상품 아래에 놓이므로 감추고 여기서 먼저 보여준다 --}}
<div class="odv-no">주문번호 <strong>{{ $od_id }}</strong></div>

<section class="odv-items">
    <h2>주문하신 상품</h2>

    <div class="odv-items-table">
        <table>
            <thead>
                <tr>
                    <th class="col-subject">상품명</th>
                    <th>총수량</th><th>판매가</th><th>포인트</th><th>배송비</th><th>소계</th><th>상태</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $it)
                <tr>
                    <td class="col-subject">
                        <div class="odv-prd">
                            <a class="odv-thumb" href="{{ $it['href'] }}">{!! $it['img'] !!}</a>
                            <div class="odv-prd-info">
                                <a class="odv-name" href="{{ $it['href'] }}">{{ $it['name'] }}</a>
                                @if ($it['option'])<p class="odv-opt"><span class="chip">옵션</span> {{ $it['option'] }}</p>@endif
                            </div>
                        </div>
                    </td>
                    <td>{{ number_format($it['qty']) }}</td>
                    <td>{{ number_format($it['price']) }}</td>
                    <td>{{ number_format($it['point']) }}</td>
                    <td>{{ $it['send'] }}</td>
                    <td class="odv-sum">{{ number_format($it['sum']) }}</td>
                    <td><span class="chip c2">{{ $it['status'] }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- 좁은 화면 --}}
    <ul class="odv-items-cards">
        @foreach ($items as $it)
        <li>
            <div class="odv-prd">
                <a class="odv-thumb" href="{{ $it['href'] }}">{!! $it['img'] !!}</a>
                <div class="odv-prd-info">
                    <a class="odv-name" href="{{ $it['href'] }}">{{ $it['name'] }}</a>
                    <span class="chip c2">{{ $it['status'] }}</span>
                </div>
            </div>
            @if ($it['option'])<p class="odv-opt"><span class="chip">옵션</span> {{ $it['option'] }}</p>@endif
            <dl class="odv-facts">
                <dt>판매가</dt><dd>{{ number_format($it['price']) }}</dd>
                <dt>수량</dt><dd>{{ number_format($it['qty']) }}</dd>
                <dt>배송비</dt><dd>{{ $it['send'] }}</dd>
                <dt>적립포인트</dt><dd>{{ number_format($it['point']) }}</dd>
            </dl>
            <p class="odv-line"><span>주문금액</span><strong>{{ number_format($it['sum']) }}</strong></p>
        </li>
        @endforeach
    </ul>
</section>

{!! $body_html !!}</div>

<div class="bbs-toolbar">
    <a class="btn" href="{{ $list_href }}">주문 목록</a>
    <a class="btn btn-primary" href="{{ $shop_href }}">쇼핑 계속하기</a>
</div>

{{-- 취소·반품·품절 내역 — 순정은 "내역이 있습니다" 한 줄뿐이라 목록으로 만든다.
     JS 가 결제합계 카드 아래로 옮긴다 --}}
@if (count($cancel_items))
<section class="odv-cancelled" id="odv-cancelled">
    <h2>취소·반품 내역</h2>

    @if (count($cancel_notes))
    <ul class="odv-why">
        @foreach ($cancel_notes as $n)
        <li>
            <p class="r">{{ $n['reason'] }}</p>
            <p class="m"><span class="chip c4">{{ $n['who'] }}</span> {{ $n['time'] }}</p>
        </li>
        @endforeach
    </ul>
    @endif

    <ul>
        @foreach ($cancel_items as $it)
        <li>
            <div class="s">
                <a class="t" href="{{ $it['href'] }}">{{ $it['name'] }}</a>
                <span class="chip c4">{{ $it['status'] }}</span>
            </div>
            @if ($it['option'])<p class="odv-opt">{{ $it['option'] }}</p>@endif
            <p class="odv-line"><span>{{ number_format($it['qty']) }}개</span><strong>{{ number_format($it['sum']) }}원</strong></p>
        </li>
        @endforeach
    </ul>
    @if ($cancel_price)
    <p class="odv-cancelled-tot"><span>취소 금액</span><strong>{{ number_format($cancel_price) }}원</strong></p>
    @endif
</section>
@endif

{{-- 순정이 취소 가능하다고 판단한 주문에서만 JS 가 결제합계 카드 아래로 옮겨 보여준다 --}}
<button type="button" class="linklike odv-cancel-open" id="odv-cancel-open" hidden>주문 취소</button>

{{-- 주문 취소 — 순정 폼(주문번호·토큰·검사 함수)을 그대로 이 안으로 옮겨 담는다 --}}
<div class="odv-modal" id="odv-cancel-modal" role="dialog" aria-modal="true" aria-labelledby="odv-cancel-title" hidden>
    <div class="odv-backdrop" data-close></div>
    <div class="odv-panel">
        <h2 id="odv-cancel-title">주문 취소</h2>
        <p class="odv-panel-lead">취소 사유를 남겨 주세요. 취소한 주문은 되돌릴 수 없습니다.</p>

        {{-- 자주 쓰는 사유를 고르면 아래 입력칸이 채워진다. 고른 뒤 고쳐 쓸 수 있다 --}}
        <label for="odv-cancel-preset" class="sound_only">자주 쓰는 취소 사유</label>
        <select id="odv-cancel-preset" class="odv-cancel-preset">
            <option value="">사유 선택 (직접 입력도 가능합니다)</option>
            <option>단순 변심</option>
            <option>다른 상품으로 다시 주문하려고</option>
            <option>주문을 잘못했어요 (수량·옵션)</option>
            <option>배송이 늦어져서</option>
            <option>상품 정보가 생각과 달라서</option>
            <option>결제 수단을 바꾸려고</option>
        </select>

        <div id="odv-cancel-slot"></div>
        <button type="button" class="btn odv-cancel-close" data-close>닫기</button>
    </div>
</div>

<script>
(function () {
    var sec = document.getElementById('sod_fin_cancel');
    var open = document.getElementById('odv-cancel-open');
    var modal = document.getElementById('odv-cancel-modal');
    var tot = document.getElementById('sod_fin_tot');
    var anchor = tot;           // 결제합계 카드 아래로 차례차례 붙인다

    // 취소·반품 내역 목록을 결제합계 아래로
    var cancelled = document.getElementById('odv-cancelled');
    if (cancelled && anchor) { anchor.insertAdjacentElement('afterend', cancelled); anchor = cancelled; }

    // 순정 취소 카드는 폼만 꺼내고 없앤다 (내역만 있는 경우엔 문장뿐이라 그냥 없앤다)
    var form = sec ? sec.querySelector('form') : null;
    if (sec) sec.parentNode.removeChild(sec);
    if (!form || !open || !modal) return;

    document.getElementById('odv-cancel-slot').appendChild(form);
    if (anchor) anchor.insertAdjacentElement('afterend', open);
    else document.querySelector('.odv').appendChild(open);
    open.hidden = false;

    function setOpen(on) {
        modal.hidden = !on;
        document.body.style.overflow = on ? 'hidden' : '';
        if (on) { var i = form.querySelector('input[type=text]'); if (i) i.focus(); }
        else open.focus();
    }
    // 고른 사유를 입력칸에 채운다 — 채운 뒤에도 고쳐 쓸 수 있다
    var preset = document.getElementById('odv-cancel-preset');
    var memo = form.querySelector('#cancel_memo') || form.querySelector('input[name=cancel_memo]');
    if (preset && memo) {
        preset.addEventListener('change', function () {
            memo.value = preset.value;
            memo.focus();
            memo.setSelectionRange(memo.value.length, memo.value.length);
        });
        // 직접 고쳐 쓰기 시작하면 선택은 풀어 둔다 (고른 것과 내용이 다를 수 있으므로)
        memo.addEventListener('input', function () {
            if (memo.value !== preset.value) preset.value = '';
        });
    }

    open.addEventListener('click', function () { setOpen(true); });
    modal.addEventListener('click', function (e) { if (e.target.closest('[data-close]')) setOpen(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) setOpen(false); });
})();
</script>
@endsection
