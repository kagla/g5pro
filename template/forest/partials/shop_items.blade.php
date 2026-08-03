{{-- 상품 카드 그리드 — 메인·분류·관련상품이 공용으로 쓴다 --}}
<ul class="item-grid">
    @forelse ($items as $it)
    <li class="item-card">
        <a class="item-thumb" href="{{ $it['href'] }}">
            @if ($it['img'])
            <img src="{{ $it['img'] }}" alt="{{ $it['name'] }}" loading="lazy">
            @else
            <span class="item-noimg">이미지 준비중</span>
            @endif
            @if ($it['is_soldout'])<span class="item-soldout">품절</span>@endif
            @if ($it['discount'])<span class="item-sale">{{ $it['discount'] }}%</span>@endif
        </a>
        <div class="item-info">
            <a class="item-name" href="{{ $it['href'] }}">{{ $it['name'] }}</a>
            <div class="item-price">
                @if ($it['cust_price'] > $it['price'])
                <del>{{ number_format($it['cust_price']) }}</del>
                @endif
                <strong>{{ number_format($it['price']) }}<span class="won">원</span></strong>
            </div>
        </div>
    </li>
    @empty
    <li class="bbs-empty">등록된 상품이 없습니다.</li>
    @endforelse
</ul>
