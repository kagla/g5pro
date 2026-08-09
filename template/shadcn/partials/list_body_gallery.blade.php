{{-- list_body_gallery — 목록 본문. 게시판 목록 화면과, 전체목록보이기를 켠 읽기 화면이 함께 쓴다 --}}
{{-- 열 수는 게시판 설정(bo_gallery_cols)을 따른다. 좁은 화면에서는 CSS 가 줄인다 --}}
<ul class="gallery-grid" style="--cols: {{ $gallery_cols }}">
    @forelse ($items as $it)
    @php $cls = trim('gallery-card '.($it['is_notice'] ? 'notice ' : '').($it['is_current'] ? 'current' : '')); @endphp
    <li class="{{ $cls }}">
        <a href="{{ $it['href'] }}" class="gallery-thumb">
            @if ($it['thumb'] && $it['thumb']['src'])
            <img src="{{ $it['thumb']['src'] }}" alt="{{ $it['thumb']['alt'] }}" loading="lazy">
            @else
            <span class="gallery-noimg">이미지 없음</span>
            @endif
        </a>
        <div class="gallery-info">
            <div class="gallery-subject">
                @if ($it['is_notice'])<span class="chip notice">공지</span>@endif
                @if ($it['ca_name'])<a class="chip cate" href="{!! $it['ca_href'] !!}">{{ $it['ca_name'] }}</a>@endif
                <a href="{{ $it['href'] }}">{!! $it['subject'] !!}</a>
                @include('partials.list_flags', ['it' => $it])
            </div>
            @if ($use_content && $it['excerpt'])<p class="row-excerpt">{{ $it['excerpt'] }}</p>@endif
            <div class="m">
                <span>{!! $it['name'] !!}</span>
                <span>{{ $it['datetime'] }}</span>
                <span>조회 {{ $it['hit'] }}</span>
                @if ($is_good)<span>추천 {{ number_format($it['good']) }}</span>@endif
                @if ($is_nogood)<span>비추천 {{ number_format($it['nogood']) }}</span>@endif
            </div>
        </div>
    </li>
    @empty
    <li class="bbs-empty">게시물이 없습니다.</li>
    @endforelse
</ul>

@include('partials.bbs_toolbar')
