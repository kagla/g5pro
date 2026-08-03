{{-- 전체 1페이지면 wrapper 째로 렌더하지 않는다 (빈 .paging-wrap 이 여백만 차지하는 것 방지) --}}
@if ($total_page > 1)
<div class="paging-wrap">
<nav class="paging" aria-label="페이지">
    @for ($p = 1; $p <= $total_page; $p++)
        @php $cur = ($p == $page); @endphp
        @if ($cur)
        <strong class="paging-cur">{{ $p }}</strong>
        @else
        <a href="{{ $page_href }}{{ $p }}">{{ $p }}</a>
        @endif
    @endfor
</nav>
</div>
@endif
