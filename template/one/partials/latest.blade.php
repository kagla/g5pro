@php $lt = g5_latest_rows($bo_table, $rows ?? 6); @endphp
@if ($lt['board'])
<section class="latest">
    <h3><a href="{{ G5_BBS_URL }}/board.php?bo_table={{ $lt['board']['bo_table'] }}">{{ $lt['board']['bo_subject'] }}</a></h3>
    <ul>
        @forelse ($lt['items'] as $it)
        <li>
            <a href="{{ $it['href'] }}">{!! $it['subject'] !!}</a>
            @if ($it['wr_comment'])<span class="cmt-cnt">{{ $it['wr_comment'] }}</span>@endif
            <span class="muted">{{ $it['datetime2'] }}</span>
        </li>
        @empty
        <li class="muted">게시물이 없습니다.</li>
        @endforelse
    </ul>
</section>
@endif
