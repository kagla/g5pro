{{-- list_body_card — 목록 본문. 게시판 목록 화면과, 전체목록보이기를 켠 읽기 화면이 함께 쓴다 --}}
<ul class="list-card">
    @forelse ($items as $it)
    @php $cls = trim(($it['is_notice'] ? 'notice ' : '').($it['is_current'] ? 'current' : '')); @endphp
    <li class="{{ $cls }}">
        @if ($it['thumb'] && $it['thumb']['src'])
        <a class="thumb" href="{{ $it['href'] }}">
            <img src="{{ $it['thumb']['src'] }}" alt="{{ $it['thumb']['alt'] }}" loading="lazy">
        </a>
        @else
        @php $initial = mb_substr(strip_tags($it['name']), 0, 1, 'UTF-8'); @endphp
        <span class="ava" aria-hidden="true">{{ $initial }}</span>
        @endif
        <div class="info">
            <div class="s">
                @if ($it['is_notice'])<span class="chip notice">공지</span>@endif
                @if ($it['ca_name'])<a class="chip cate" href="{!! $it['ca_href'] !!}">{{ $it['ca_name'] }}</a>@endif
                @if ($it['depth'])<span class="reply-arrow" aria-hidden="true">↳</span>@endif
                <span class="t"><a href="{{ $it['href'] }}">{!! $it['subject'] !!}</a></span>
                @include('partials.list_flags', ['it' => $it])
            </div>
            @if ($use_content && $it['excerpt'])<p class="row-excerpt">{{ $it['excerpt'] }}</p>@endif
            @if ($use_file && count($it['files']))
            <p class="row-files">
                @foreach ($it['files'] as $f)<a href="{{ $f['href'] }}">{{ $f['source'] }} <span class="muted">{{ $f['size'] }}</span></a>@endforeach
            </p>
            @endif
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
