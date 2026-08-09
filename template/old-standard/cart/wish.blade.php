@extends('layout.default')
@section('content')
<header class="bbs-head">
    <h2>찜한 상품</h2>
    <div class="bbs-meta">전체 {{ number_format($total) }}개</div>
</header>

@if (count($items))
{{-- 목록 카드는 cart/list 와 같은 모양(.shop-grid/.shop-card)을 쓴다 — 같은 상품이 화면마다
     달라 보이면 같은 것으로 안 읽힌다. 다른 점은 카드 위 빼기 버튼 하나뿐이다.
     빼기는 카드 링크 안에 넣을 수 없다(a 안의 button 은 눌림이 서로 먹는다) — 형제로 둔다. --}}
<ul class="shop-grid">

    @foreach ($items as $it)
    <li class="shop-card wish-card">
        <a href="{{ $it['href'] }}">
            <span class="shop-thumb">

                @if ($it['img'])
                <img src="{{ $it['img'] }}" alt="{{ $it['it_name'] }}" loading="lazy">
                @endif

                @if (!$it['avail'])
                <span class="shop-soldout">판매중지</span>
                @elseif ($it['soldout'])
                <span class="shop-soldout">품절</span>
                @endif

            </span>
            <span class="shop-name">{{ $it['it_name'] }}</span>
            <span class="shop-price">{{ number_format($it['it_price']) }}<em>원</em></span>
            {{-- 언제 담았는지 — 최신순 목록이라 순서의 근거이기도 하고, 오래 묵은 찜을
                 정리하는 기준이 된다. 값이 곧 뜻이 되게 "찜" 을 앞에 붙인다
                 (상품 등록일·판매일로 오인될 자리다). 정확한 일시는 title 로 --}}
            <span class="wish-date" title="{{ $it['wi_full'] }} 찜">찜 {{ $it['wi_date'] }}</span>
        </a>
        <button type="button" class="wish-del" data-it-id="{{ $it['it_id'] }}"
                title="찜 빼기" aria-label="{{ $it['it_name'] }} 찜 빼기">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.4 4.5 13a4.7 4.7 0 0 1 6.6-6.7l.9.9.9-.9A4.7 4.7 0 0 1 19.5 13Z"/></svg>
        </button>
    </li>
    @endforeach

</ul>

@if ($total_page > 1)
<nav class="paging">

    @foreach ($pages as $p)
    <a href="{{ $p['href'] }}" class="{{ $p['current'] ? 'current' : '' }}">{{ $p['num'] }}</a>
    @endforeach

</nav>
@endif

@else
<p class="empty">아직 찜한 상품이 없습니다. 상품 상세의 하트를 누르면 여기에 모입니다.</p>
<p style="text-align:center"><a href="{{ $home_href }}" class="cart-cta">상품 보러 가기</a></p>
@endif

<script>
// 빼기는 그 카드만 지운다 — 페이지를 다시 읽으면 뒤 페이지 상품이 당겨 올라와 스크롤 자리가
// 흐트러진다. 마지막 한 장을 뺐을 때만 빈 화면 안내를 보러 다시 읽는다.
$(function () {
    $('.wish-del').on('click', function () {
        var $btn = $(this), $card = $btn.closest('.wish-card');
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true);

        $.post('{{ $wish_action }}', {
            token: '{{ $token }}',
            mode: 'del',
            it_id: $btn.data('it-id')
        }, null, 'json').done(function (res) {
            if (!res || !res.ok) {
                alert(res && res.msg ? res.msg : '잠시 후 다시 시도해 주세요.');
                $btn.prop('disabled', false);
                return;
            }
            $card.remove();
            if (!$('.wish-card').length) location.reload();
        }).fail(function () {
            alert('잠시 후 다시 시도해 주세요.');
            $btn.prop('disabled', false);
        });
    });
});
</script>
@endsection
