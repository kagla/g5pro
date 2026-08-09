{{-- 게시물 복사·이동 대상 고르기 (bbs/move.php) — 목록의 점 세 개 메뉴에서 팝업으로 열린다 --}}
@extends('layout.popup')
@section('content')
<p class="popup-lead">게시물 <b>{{ number_format($count) }}건</b>을 {{ $act }}할 게시판을 고르세요. 여러 곳을 함께 고를 수 있습니다.</p>

<form name="fboardmoveall" id="fboardmoveall" method="post" action="{{ $action }}">
<input type="hidden" name="sw" value="{{ $sw }}">
<input type="hidden" name="act" value="{{ $act }}">
<input type="hidden" name="wr_id_list" value="{{ $wr_id_list }}">
@foreach ($keep as $k => $v)
<input type="hidden" name="{{ $k }}" value="{{ $v }}">
@endforeach
<input type="hidden" name="url" value="{{ $referer }}">

<div class="move-head">
    <label class="chk-all-label"><input type="checkbox" class="chk-all"> 전체 선택</label>
    <span class="chk-count" aria-live="polite"></span>
</div>

<ul class="move-list">
    @foreach ($boards as $i => $b)
    @php $cls = $b['current'] ? 'current' : ''; @endphp
    <li class="{{ $cls }}">
        <label for="mv{{ $i }}">
            <input type="checkbox" id="mv{{ $i }}" name="chk_bo_table[]" value="{{ $b['bo_table'] }}">
            <span class="g">{{ $b['gr_subject'] }}</span>
            <span class="b">{{ $b['bo_subject'] }}</span>
            <span class="t">{{ $b['bo_table'] }}</span>
            @if ($b['current'])<span class="chip c3">현재</span>@endif
        </label>
    </li>
    @endforeach
</ul>

<div class="popup-btns">
    <button type="submit" id="btn_submit" class="btn btn-primary">{{ $act }}하기</button>
    <button type="button" class="btn" onclick="window.close();">창닫기</button>
</div>
</form>

<script>
(function () {
    var f = document.getElementById('fboardmoveall');
    var boxes = [].slice.call(f.querySelectorAll('input[name="chk_bo_table[]"]'));
    var all = f.querySelector('.chk-all');
    var count = f.querySelector('.chk-count');

    function sync() {
        var n = boxes.filter(function (c) { return c.checked; }).length;
        all.checked = (n > 0 && n === boxes.length);
        all.indeterminate = (n > 0 && n < boxes.length);
        count.textContent = n ? n + '곳 선택' : '';
    }
    all.addEventListener('change', function () {
        boxes.forEach(function (c) { c.checked = all.checked; });
        sync();
    });
    boxes.forEach(function (c) { c.addEventListener('change', sync); });
    sync();

    f.addEventListener('submit', function (e) {
        if (!boxes.some(function (c) { return c.checked; })) {
            alert('게시물을 {{ $act }}할 게시판을 한 곳 이상 고르세요.');
            e.preventDefault();
            return;
        }
        document.getElementById('btn_submit').disabled = true;
    });
})();
</script>
@endsection
