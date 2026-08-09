@extends('layout.default')
@section('content')
<header class="bbs-head">
    <h2>내 쿠폰함</h2>
    <div class="bbs-meta">쓸 수 있는 쿠폰 {{ number_format($live_count) }}장 / 전체 {{ number_format($total) }}장</div>
</header>

{{-- 코드 입력은 "쿠폰함에 담는 방법 중 하나" 다 — 담기고 나면 관리자가 지급한 장과 같은 길로
     흐르므로, 별도 화면을 만들지 않고 쿠폰함 맨 위에 둔다 --}}
<form method="post" action="{{ $action_url }}" class="cpn-redeem">
    <input type="hidden" name="token" value="{{ $token }}">
    <input type="hidden" name="mode" value="redeem">
    <label for="cpn_code">쿠폰 코드</label>
    <input type="text" name="code" id="cpn_code" maxlength="30" placeholder="받은 코드를 입력하세요"
           autocomplete="off" spellcheck="false">
    <button type="submit" class="btn btn-primary">등록</button>
</form>

@if (count($rows))
{{-- 쓴 것·기한 지난 것도 함께 보여 준다. 목록에서 빼 버리면 "내 쿠폰이 사라졌다" 로 읽히고,
     정작 왜 못 쓰는지는 어디에도 안 남는다 --}}
<ul class="cpn-list">

    @foreach ($rows as $r)
    <li class="cpn-card {{ $r['live'] ? '' : 'is-dead' }}">
        <div class="cpn-amount">{{ $r['label'] }}</div>
        <div class="cpn-body">
            <b class="cpn-name">{{ $r['cp_name'] }}</b>
            <span class="cpn-cond">{{ $r['target_label'] }}
                @if ((int)$r['cp_min'] > 0)
                · {{ number_format($r['cp_min']) }}원 이상
                @endif
            </span>
        </div>
        <div class="cpn-side">

            @if ($r['used'])
            <span class="chip">사용함</span>
            <span class="cpn-date">{{ substr($r['cm_used_at'], 2, 8) }}</span>
            @elseif ($r['expired'])
            <span class="chip">기한 지남</span>
            <span class="cpn-date">{{ substr($r['cm_end'], 2) }}까지</span>
            @elseif (!(int)$r['cp_use'])
            <span class="chip">중지됨</span>
            @else
            <span class="chip {{ $r['left_days'] <= 3 ? 'notice' : 'here' }}">
                @if ($r['left_days'] <= 0)
                오늘까지
                @else
                {{ $r['left_days'] }}일 남음
                @endif
            </span>
            <span class="cpn-date">{{ substr($r['cm_end'], 2) }}까지</span>
            @endif

        </div>
    </li>
    @endforeach

</ul>
@else
<p class="empty">아직 받은 쿠폰이 없습니다. 받은 코드가 있다면 위에 입력해 주세요.</p>
<p style="text-align:center"><a href="{{ $home_href }}" class="cart-cta">상품 보러 가기</a></p>
@endif
@endsection
