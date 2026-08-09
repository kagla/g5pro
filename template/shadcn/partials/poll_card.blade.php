{{-- 설문조사 카드 — 순정 tail.php 의 poll() 자리를 대신한다.
     값은 g5_poll_widget() 이 뽑고, 제출은 순정 bbs/poll_update.php 가 그대로 받는다.
     여기 판정(투표했나·권한되나)은 화면용이고 실제 방어는 순정이 다시 한다 --}}
@php $poll = g5_poll_widget(); @endphp
@if ($poll)
<section class="card poll-card">
    <h3>
        <span>설문조사</span>
        @if ($poll['admin_href'])
        <a class="poll-admin" href="{{ $poll['admin_href'] }}" title="설문관리">설문관리</a>
        @endif
    </h3>

    <p class="poll-subject">{{ $poll['subject'] }}</p>

    @if (!$poll['can_vote'])
    <p class="muted">권한 {{ $poll['level'] }} 이상 회원만 참여할 수 있습니다.</p>
    <div class="poll-acts"><a class="btn" href="{{ $poll['result_href'] }}">결과보기</a></div>
    @elseif ($poll['voted'])
    <p class="muted">이미 참여하셨습니다.</p>
    <div class="poll-acts"><a class="btn" href="{{ $poll['result_href'] }}">결과보기</a></div>
    @else
    <form name="fpoll" method="post" action="{{ G5_BBS_URL }}/poll_update.php" onsubmit="return poll_check(this);">
        <input type="hidden" name="po_id" value="{{ $poll['po_id'] }}">
        <ul class="poll-items">
            @foreach ($poll['items'] as $it)
            <li>
                <input type="radio" name="gb_poll" value="{{ $it['no'] }}" id="gb_poll_{{ $it['no'] }}">
                <label for="gb_poll_{{ $it['no'] }}">{{ $it['text'] }}</label>
            </li>
            @endforeach
        </ul>
        <div class="poll-acts">
            <button type="submit" class="btn btn-primary">투표하기</button>
            <a class="btn" href="{{ $poll['result_href'] }}">결과보기</a>
        </div>
    </form>
    <script>
    function poll_check(f) {
        var r = f.gb_poll;
        // 항목이 하나뿐이면 RadioNodeList 가 아니라 단일 요소로 온다
        if (r && typeof r.length === 'undefined') return r.checked || (alert("투표하실 설문항목을 선택하세요"), false);
        for (var i = 0; i < r.length; i++) if (r[i].checked) return true;
        alert("투표하실 설문항목을 선택하세요");
        return false;
    }
    </script>
    @endif
</section>
@endif
