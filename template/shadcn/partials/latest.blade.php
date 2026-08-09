@php
    $lt = g5_latest_rows($bo_table, $rows ?? 5);
    $chip = $chip ?? '';
    $label = $label ?? '';
@endphp
@if ($lt['board'])
<section class="card">
    {{-- 라벨 칩을 두지 않는다 — 칩과 제목이 같은 말을 한다("[공지] 공지사항").
         칩을 부르는 화면이 있을 수 있으므로 $label 을 넘기면 그때만 붙는다(기본은 없음) --}}
    <h3>
        @if ($label)<span class="chip {{ $chip }}">{{ $label }}</span>@endif
        <a href="{{ G5_BBS_URL }}/board.php?bo_table={{ $lt['board']['bo_table'] }}">{{ $lt['board']['bo_subject'] }}</a>
    </h3>
    <ul>
        @forelse ($lt['items'] as $it)
        <li>
            <a href="{{ $it['href'] }}">{!! $it['subject'] !!}</a>
            @if ($it['wr_comment'])<span class="n">{{ $it['wr_comment'] }}</span>@endif
            <span class="muted">{{ $it['datetime2'] }}</span>
        </li>
        @empty
        <li class="muted">아직 글이 없습니다.</li>
        @endforelse
    </ul>
</section>
@endif
