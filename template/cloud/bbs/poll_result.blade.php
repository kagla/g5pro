{{-- 설문조사 결과 (bbs/poll_result.php) — 백분율·막대 길이·기타의견·지난설문까지
     순정이 이미 계산해 둔 값을 그리기만 한다 --}}
@extends('layout.default')
@section('content')

<header class="bbs-head">
    <h2><span class="chip c2">투표</span>설문조사 결과</h2>
    <span class="muted">{{ number_format($total) }}명 참여</span>
</header>

<section class="card poll-result">
    <h3>
        <span>{{ $subject }}</span>
        @if ($admin_href)
        <a class="poll-admin" href="{{ $admin_href }}" title="설문관리">설문관리</a>
        @endif
    </h3>

    <ul class="poll-bars">
        @foreach ($options as $o)
        <li>
            <div class="poll-bar-head">
                <span class="poll-bar-label">{{ $o['content'] }}</span>
                <span class="muted">{{ number_format($o['cnt']) }}명 · {{ number_format($o['rate'], 1) }}%</span>
            </div>
            <div class="poll-bar"><span style="width: {{ $o['bar'] }}%"></span></div>
        </li>
        @endforeach
    </ul>
</section>

@if ($is_etc)
<section class="card poll-etc">
    <h3>이 설문에 대한 기타의견</h3>

    @if ($etc_can)
    <form name="fpollresult" method="post" action="{{ $etc_action }}" onsubmit="return poll_etc_check(this);" autocomplete="off">
        <input type="hidden" name="w" value="">
        <input type="hidden" name="po_id" value="{{ $po_id }}">
        {{-- 순정 poll_etc_update.php 는 돌아갈 주소를 만들 때 $skin_dir 을 그대로 쓴다.
             템플릿은 스킨을 안 쓰지만 값을 안 보내면 undefined 경고가 남는다 --}}
        <input type="hidden" name="skin_dir" value="">
        <div class="poll-etc-form">
            <span class="poll-etc-name">{!! $etc_name !!}</span>
            <input type="text" name="pc_idea" maxlength="255" class="frm_input" required placeholder="{{ $etc_label }}">
            <button type="submit" class="btn btn-primary">등록</button>
        </div>
        @if ($captcha_html)
        <div class="poll-etc-captcha">{!! $captcha_html !!}</div>
        @endif
    </form>
    @endif

    @if (count($etc_items))
    <ul class="list-simple poll-etc-list">
        @foreach ($etc_items as $e)
        <li>
            <div class="s">
                <span class="t">{!! $e['name'] !!}</span>
                <span class="muted">{{ $e['datetime'] }}</span>
                @if ($e['del'])
                {!! $e['del'] !!}삭제</a>
                @endif
            </div>
            <div>{{ $e['idea'] }}</div>
        </li>
        @endforeach
    </ul>
    @else
    <p class="muted">아직 기타의견이 없습니다.</p>
    @endif
</section>
<script>
function poll_etc_check(f) {
    if (!f.pc_idea.value.trim()) { alert("기타의견을 입력하세요."); f.pc_idea.focus(); return false; }
    if (f.pc_name && f.pc_name.type === 'text' && !f.pc_name.value.trim()) {
        alert("이름을 입력하세요."); f.pc_name.focus(); return false;
    }
    {!! $captcha_js !!}
    return true;
}
</script>
@endif

@if (count($others))
<section class="card poll-others">
    <h3>지난 설문</h3>
    <ul class="list-simple">
        @foreach ($others as $o)
        <li>
            <a href="{{ $o['href'] }}">{{ $o['subject'] }}</a>
            <span class="muted">{{ $o['date'] }}</span>
        </li>
        @endforeach
    </ul>
</section>
@endif

@endsection
