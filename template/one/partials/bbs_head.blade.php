{{-- 게시판 상단: 이름·건수·카테고리 — 목록 변형 4종이 공용으로 쓴다 --}}
<header class="bbs-head">
    <h2>{{ $board['bo_subject'] }}</h2>
    <div class="bbs-meta">전체 {{ number_format($total_count) }}건 · {{ $page }} / {{ max($total_page, 1) }} 페이지</div>
</header>

@if (count($categories))
<nav class="bbs-cate">
    <a href="{{ $board_url }}">전체</a>
    @foreach ($categories as $c)
    @php $cls = $c['active'] ? 'active' : ''; @endphp
    <a href="{{ $c['href'] }}" class="{{ $cls }}">{{ $c['name'] }}</a>
    @endforeach
</nav>
@endif
