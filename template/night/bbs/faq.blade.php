{{-- 자주하시는 질문 (bbs/faq.php) — 분류 하나를 열고 그 안에서 질문을 접었다 편다.
     펼침은 <details> 로 처리한다. 자바스크립트 없이 동작하고 검색 결과도 그대로 열린다. --}}
@extends('layout.default')
@section('content')

<header class="bbs-head">
    <h2><span class="chip">FAQ</span>{{ $title }}</h2>
    @if ($is_admin && $admin_href)
    <a class="btn" href="{{ $admin_href }}">관리</a>
    @endif
</header>

@if (count($cates) > 1)
<nav class="bbs-cate">
    @foreach ($cates as $c)
    <a href="{{ $c['href'] }}" class="{{ $c['active'] ? 'active' : '' }}">{{ $c['name'] }}</a>
    @endforeach
</nav>
@endif

@if ($head_img)<p class="faq-img"><img src="{{ $head_img }}" alt=""></p>@endif
@if ($head_html)<div class="faq-html">{!! $head_html !!}</div>@endif

<form class="bbs-search faq-search" method="get" action="{{ $action }}">
    <input type="hidden" name="fm_id" value="{{ $fm_id }}">
    <label for="stx" class="sound_only">검색어</label>
    <input type="text" name="stx" id="stx" value="{{ $stx }}" placeholder="궁금한 내용을 검색하세요">
    <button type="submit" class="btn btn-primary">검색</button>
</form>

@if (!$items)
    <div class="bbs-empty">{{ $stx ? '검색 결과가 없습니다.' : '등록된 질문이 없습니다.' }}</div>
@else
<div class="faq-list">
    @foreach ($items as $it)
    <details class="faq-item"@if ($stx) open @endif>
        <summary>{!! $it['subject'] !!}</summary>
        <div class="faq-answer">{!! $it['content'] !!}</div>
    </details>
    @endforeach
</div>
@endif

@if ($tail_html)<div class="faq-html">{!! $tail_html !!}</div>@endif
@if ($tail_img)<p class="faq-img"><img src="{{ $tail_img }}" alt=""></p>@endif

@include('partials.paging', ['page' => $page, 'total_page' => $total_page, 'page_href' => $page_href])
@endsection
