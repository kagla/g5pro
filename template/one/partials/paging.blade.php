@if ($total_page > 1)
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
@endif
