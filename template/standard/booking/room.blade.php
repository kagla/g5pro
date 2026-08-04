{{-- 객실 상세 + 잔여 캘린더 (booking/room.php) --}}
@extends('layout.default')

{{-- 스타일을 뷰가 지고 다닌다. 이 화면은 template/standard 에만 있고 다른 템플릿에서는
     폴백(extend/pro.10.extend.php 의 $views)으로 그려지므로, 그 템플릿의 style.css 에
     예약 규칙이 있으리라 기대할 수 없다. 색·여백은 어느 템플릿에나 있는 토큰만 쓴다 --}}
@section('head')
<style>
.bk-top { display: flex; gap: var(--s5); flex-wrap: wrap; align-items: flex-start; margin-bottom: var(--s5); }
.bk-gallery { flex: 1 1 360px; min-width: 0; }
.bk-info { flex: 1 1 300px; min-width: 0; }
.bk-main { display: block; width: 100%; aspect-ratio: 4 / 3; object-fit: cover;
    border-radius: var(--r-xl); background: var(--grad); box-shadow: var(--sh-md); }
.bk-thumbs { display: flex; flex-wrap: wrap; gap: var(--s2); margin-top: var(--s2); }
.bk-thumb { width: 66px; height: 50px; padding: 0; overflow: hidden; cursor: pointer;
    background: none; border: 2px solid transparent; border-radius: var(--r-sm); }
.bk-thumb img { display: block; width: 100%; height: 100%; object-fit: cover; }
.bk-thumb.on { border-color: var(--accent); }

.bk-facts { display: flex; flex-direction: column; gap: var(--s2); margin: 0; }
.bk-facts > div { display: flex; gap: var(--s3); font-size: var(--t-md); }
.bk-facts dt { flex: 0 0 86px; color: var(--muted); }
.bk-facts dd { margin: 0; font-variant-numeric: tabular-nums; }
/* 객실 설명·환불 약관은 관리자가 넣은 평문이다. 줄바꿈만 CSS 로 살린다 —
   HTML 로 내보내면 값 안의 태그가 그대로 먹는다 (두 값 모두 strip_tags 없이 저장된다) */
.bk-desc { white-space: pre-line; line-height: 1.7; margin: var(--s4) 0 0; }

.bk-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: var(--s5); align-items: start; }
.bk-side > section + section { margin-top: var(--s4); }
@media (max-width: 760px) {
    .bk-grid { grid-template-columns: 1fr; }
}

.bk-cal-head { display: flex; align-items: center; justify-content: space-between; gap: var(--s3); margin-bottom: var(--s3); }
.bk-cal-head strong { font-size: var(--t-lg); font-variant-numeric: tabular-nums; }
.bk-cal-head .btn:disabled { opacity: .4; cursor: default; }
.bk-week, .bk-cal { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
.bk-week { margin-bottom: 4px; font-size: var(--t-sm); color: var(--muted); text-align: center; }
.bk-week span:first-child { color: var(--danger); }
.bk-day { display: flex; flex-direction: column; align-items: center; gap: 2px;
    min-height: 64px; padding: 6px 2px; font: inherit; cursor: pointer;
    color: var(--fg); background: var(--card); border: 1px solid var(--line); border-radius: var(--r-sm); }
.bk-day b { font-size: var(--t-md); font-weight: 700; }
.bk-day i { font-style: normal; font-size: var(--t-xs); color: var(--muted); font-variant-numeric: tabular-nums; }
.bk-day em { font-style: normal; font-size: var(--t-xs); color: var(--accent); }
.bk-blank { min-height: 0; background: none; border: 0; }
.bk-day.bk-off { color: var(--muted); background: var(--line); cursor: default; }
.bk-day.bk-off em { color: var(--muted); }
.bk-day.bk-in, .bk-day.bk-out { color: var(--accent-fg); background: var(--accent); border-color: var(--accent); }
.bk-day.bk-in i, .bk-day.bk-in em, .bk-day.bk-out i, .bk-day.bk-out em { color: var(--accent-fg); }
.bk-day.bk-mid { background: var(--accent-soft); border-color: var(--accent-soft); }

.bk-hint { grid-column: 1 / -1; margin: 0; padding: var(--s4) 0; text-align: center;
    font-size: var(--t-sm); color: var(--muted); }
.bk-sum { display: flex; flex-direction: column; gap: var(--s2); margin: 0 0 var(--s3); }
.bk-sum > div { display: flex; justify-content: space-between; gap: var(--s3); font-size: var(--t-md); }
.bk-sum dt { color: var(--muted); }
.bk-sum dd { margin: 0; font-variant-numeric: tabular-nums; }
.bk-sum b { font-size: var(--t-lg); color: var(--accent); }
.bk-note { margin: 0 0 var(--s3); font-size: var(--t-sm); color: var(--muted); }
.bk-list { margin: 0; }
.bk-list li { display: flex; justify-content: space-between; gap: var(--s3);
    padding: 6px 0; font-size: var(--t-md); border-bottom: 1px solid var(--line); }
.bk-list li:last-child { border-bottom: 0; }

/* 관리자 수정 바로가기 — 게시판·상품의 .icon-btn.bbs-admin-link 와 같은 모양이지만
   그 규칙은 standard 의 style.css 에 있다. 이 화면은 폴백으로 다른 템플릿에도 뜨므로
   기대지 않고 제 규칙을 지고 다닌다 (이 파일 머리말 참고) */
.bk-gear { display: inline-flex; align-items: center; justify-content: center; flex: none;
    width: 32px; height: 32px; border-radius: var(--r-md); color: var(--muted);
    transition: color .15s, background .15s; }
.bk-gear:hover { color: var(--accent); background: var(--accent-soft); }
.bk-gear svg { width: 17px; height: 17px; fill: none;
    stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
</style>
@endsection

@section('content')
<div class="bbs-head">
    {{-- 최고관리자에게만 채워진다 (booking/room.php). 아니면 톱니 자체가 안 나간다 --}}
    <h2>{{ $room['br_subject'] }}
        @if ($admin_edit_url)
        <a class="bk-gear" href="{{ $admin_edit_url }}" title="객실 수정" aria-label="객실 수정">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3.2"/><path d="M19.1 14.6a1.5 1.5 0 0 0 .3 1.7l.1.1a1.9 1.9 0 1 1-2.7 2.7l-.1-.1a1.5 1.5 0 0 0-1.7-.3 1.5 1.5 0 0 0-.9 1.4v.2a1.9 1.9 0 1 1-3.8 0v-.1a1.5 1.5 0 0 0-1-1.4 1.5 1.5 0 0 0-1.7.3l-.1.1a1.9 1.9 0 1 1-2.7-2.7l.1-.1a1.5 1.5 0 0 0 .3-1.7 1.5 1.5 0 0 0-1.4-.9h-.2a1.9 1.9 0 1 1 0-3.8h.1a1.5 1.5 0 0 0 1.4-1 1.5 1.5 0 0 0-.3-1.7l-.1-.1a1.9 1.9 0 1 1 2.7-2.7l.1.1a1.5 1.5 0 0 0 1.7.3h.1a1.5 1.5 0 0 0 .9-1.4v-.2a1.9 1.9 0 1 1 3.8 0v.1a1.5 1.5 0 0 0 .9 1.4 1.5 1.5 0 0 0 1.7-.3l.1-.1a1.9 1.9 0 1 1 2.7 2.7l-.1.1a1.5 1.5 0 0 0-.3 1.7v.1a1.5 1.5 0 0 0 1.4.9h.2a1.9 1.9 0 1 1 0 3.8h-.1a1.5 1.5 0 0 0-1.4.9Z"/></svg>
        </a>
        @endif
    </h2>
    <div class="bbs-head-right"><a class="btn" href="{{ G5_URL }}/booking/">객실 목록</a></div>
</div>

<div class="bk-top">
    <div class="bk-gallery">
        @if (count($images))
        <img class="bk-main" id="bk-main-img" src="{{ $images[0] }}" alt="{{ $room['br_subject'] }}">
        @if (count($images) > 1)
        <div class="bk-thumbs">
            @foreach ($images as $i => $src)
            @php $tcls = 'bk-thumb'.($i ? '' : ' on'); @endphp
            <button type="button" class="{{ $tcls }}" data-src="{{ $src }}">
                <img src="{{ $src }}" alt="">
            </button>
            @endforeach
        </div>
        @endif
        @else
        <div class="bk-main"></div>
        @endif
    </div>

    <div class="bk-info">
        <dl class="bk-facts">
            <div><dt>기준 인원</dt><dd>{{ $room['br_base_person'] }}명 (최대 {{ $room['br_max_person'] }}명)</dd></div>
            @if ($room['br_person_price'])
            <div><dt>인원 추가</dt><dd>1명 · 1박 {{ number_format($room['br_person_price']) }}원</dd></div>
            @endif
            <div><dt>주중 요금</dt><dd>{{ number_format($room['br_weekday_price']) }}원</dd></div>
            <div><dt>주말 요금</dt><dd>{{ number_format($room['br_weekend_price']) }}원 (금·토)</dd></div>
            <div><dt>입실 / 퇴실</dt><dd>{{ $conf['checkin_time'] }} / {{ $conf['checkout_time'] }}</dd></div>
            <div><dt>숙박</dt><dd>{{ $conf['min_nights'] }}박 ~ {{ $conf['max_nights'] }}박</dd></div>
        </dl>
        @if (trim($room['br_content']) !== '')
        <p class="bk-desc">{{ $room['br_content'] }}</p>
        @endif
    </div>
</div>

<div class="bk-grid">
    <section class="card">
        <h3>날짜 선택</h3>
        <div class="bk-cal-head">
            <button type="button" class="btn" id="bk-prev">이전 달</button>
            <strong id="bk-ym"></strong>
            <button type="button" class="btn" id="bk-next">다음 달</button>
        </div>
        <div class="bk-week" aria-hidden="true">
            <span>일</span><span>월</span><span>화</span><span>수</span><span>목</span><span>금</span><span>토</span>
        </div>
        <div class="bk-cal" id="bk-cal" aria-live="polite">
            <p class="bk-hint">달력을 불러오는 중입니다.</p>
        </div>
    </section>

    <div class="bk-side">
        <section class="card">
            <h3>선택한 일정</h3>
            <div id="bk-summary"><p class="bk-hint">체크인 날짜를 누르세요.</p></div>
        </section>

        @if (count($addons))
        <section class="card">
            <h3>부가상품</h3>
            <ul class="bk-list">
                @foreach ($addons as $addon)
                <li><span>{{ $addon['ba_subject'] }}</span><span>{{ number_format($addon['ba_price']) }}원</span></li>
                @endforeach
            </ul>
            <p class="bk-note">예약 단계에서 선택할 수 있습니다.</p>
        </section>
        @endif

        @if (count($conf['cancel_rules']))
        <section class="card">
            <h3>취소·환불 규정</h3>
            <ul class="bk-list">
                @foreach ($conf['cancel_rules'] as $days => $rate)
                <li><span>{{ $days ? '체크인 '.$days.'일 전까지' : '체크인 당일' }}</span><span>{{ $rate }}% 환불</span></li>
                @endforeach
            </ul>
            @if (trim($conf['refund_terms']) !== '')
            <p class="bk-desc bk-note">{{ $conf['refund_terms'] }}</p>
            @endif
        </section>
        @endif
    </div>
</div>

<script>
{{-- JSON_HEX_TAG — 값 안에 </script> 가 섞여도 문서가 끊기지 않게 (layout/base 와 같은 방식) --}}
var bkConf = {!! json_encode($js, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!};
</script>
<script>
jQuery(function ($) {
    var conf = bkConf;
    // 서버가 준 하루 정보. 달을 옮겨도 지우지 않는다 — 달을 걸치는 숙박도 검사해야 한다
    var dayMap = {};
    var curYm = conf.ym;
    var checkin = '';
    var checkout = '';
    var $cal = $('#bk-cal');

    function pad(n)
    {
        return (n < 10 ? '0' : '') + n;
    }
    function comma(n)
    {
        return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    function ymShift(ym, delta)
    {
        var p = ym.split('-');
        var d = new Date(+p[0], +p[1] - 1 + delta, 1);
        return d.getFullYear() + '-' + pad(d.getMonth() + 1);
    }
    function dayShift(date, delta)
    {
        var p = date.split('-');
        var d = new Date(+p[0], +p[1] - 1, +p[2] + delta);
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    }
    // 체크인~체크아웃 사이의 밤이 모두 팔 수 있고 박수 제한 안이면 박수, 아니면 0.
    // ISO 날짜는 사전순 비교가 곧 시간순 비교라 문자열로 견준다
    function nightsOf(a, b)
    {
        var n = 0;
        var d = a;
        while (d < b) {
            var info = dayMap[d];
            if (!info || !info.selectable) return 0;
            n++;
            if (n > conf.max_nights) return 0;
            d = dayShift(d, 1);
        }
        return (n >= conf.min_nights) ? n : 0;
    }
    function pickable(date, info)
    {
        if (!checkin || checkout) return info.selectable;   // 처음부터 다시 고르는 중
        if (date <= checkin) return info.selectable;        // 시작점을 앞으로 옮긴다
        return nightsOf(checkin, date) > 0;
    }

    function draw()
    {
        var p = curYm.split('-');
        var y = +p[0];
        var m = +p[1];
        var lead = new Date(y, m - 1, 1).getDay();
        var last = new Date(y, m, 0).getDate();
        var html = '';
        var i;
        $('#bk-ym').text(y + '년 ' + m + '월');
        for (i = 0; i < lead; i++) {
            html += '<div class="bk-day bk-blank"></div>';
        }
        for (i = 1; i <= last; i++) {
            var date = curYm + '-' + pad(i);
            var info = dayMap[date] || { price: 0, remain: 0, selectable: false };
            var ok = pickable(date, info);
            var cls = 'bk-day';
            if (!ok) cls += ' bk-off';
            if (date === checkin) cls += ' bk-in';
            if (date === checkout) cls += ' bk-out';
            if (checkin && checkout && date > checkin && date < checkout) cls += ' bk-mid';
            var body = '';
            if (info.selectable) {
                body = '<i>' + comma(info.price) + '</i><em>' + info.remain + '실</em>';
            } else if (date >= conf.today) {
                body = '<em>마감</em>';
            }
            html += '<button type="button" class="' + cls + '" data-date="' + date + '"'
                  + (ok ? '' : ' disabled') + '><b>' + i + '</b>' + body + '</button>';
        }
        $cal.html(html);
        $('#bk-prev').prop('disabled', curYm <= conf.ym);
        $('#bk-next').prop('disabled', curYm >= conf.limit_ym);
        summary();
    }

    function summary()
    {
        var $s = $('#bk-summary');
        if (!checkin) {
            $s.html('<p class="bk-hint">체크인 날짜를 누르세요.</p>');
            return;
        }
        if (!checkout) {
            $s.html('<p class="bk-hint">체크인 ' + checkin + ' — 이제 체크아웃 날짜를 누르세요.</p>');
            return;
        }
        var n = 0;
        var sum = 0;
        var d = checkin;
        while (d < checkout) {
            sum += dayMap[d] ? dayMap[d].price : 0;
            n++;
            d = dayShift(d, 1);
        }
        var href = conf.reserve_url + '?br_id=' + conf.br_id
                 + '&amp;checkin=' + checkin + '&amp;checkout=' + checkout;
        $s.html('<dl class="bk-sum">'
            + '<div><dt>체크인</dt><dd>' + checkin + ' ' + conf.checkin_time + '</dd></div>'
            + '<div><dt>체크아웃</dt><dd>' + checkout + ' ' + conf.checkout_time + '</dd></div>'
            + '<div><dt>숙박</dt><dd>' + n + '박</dd></div>'
            + '<div><dt>객실 요금</dt><dd><b>' + comma(sum) + '</b>원</dd></div>'
            + '</dl>'
            + '<p class="bk-note">인원 추가 요금과 부가상품은 다음 단계에서 더해집니다.</p>'
            + '<a class="btn btn-primary btn-block" href="' + href + '">예약하기</a>');
    }

    function load(ym)
    {
        $cal.html('<p class="bk-hint">달력을 불러오는 중입니다.</p>');
        $.getJSON(conf.ajax_url, { br_id: conf.br_id, ym: ym })
            .done(function (res) {
                if (!res || !res.days) {
                    $cal.html('<p class="bk-hint">달력을 불러오지 못했습니다.</p>');
                    return;
                }
                for (var i = 0; i < res.days.length; i++) {
                    dayMap[res.days[i].date] = res.days[i];
                }
                curYm = res.ym || ym;
                draw();
            })
            .fail(function () {
                $cal.html('<p class="bk-hint">달력을 불러오지 못했습니다.</p>');
            });
    }

    $cal.on('click', '.bk-day', function () {
        var date = $(this).attr('data-date');
        var info = date ? dayMap[date] : null;
        if (!info) return;
        if (!checkin || checkout || date <= checkin) {
            if (!info.selectable) return;
            checkin = date;
            checkout = '';
        } else {
            if (!nightsOf(checkin, date)) return;
            checkout = date;
        }
        draw();
    });
    $('#bk-prev').on('click', function () { load(ymShift(curYm, -1)); });
    $('#bk-next').on('click', function () { load(ymShift(curYm, 1)); });

    // 갤러리 — 썸네일을 누르면 큰 사진을 바꾼다
    $('.bk-thumb').on('click', function () {
        $('#bk-main-img').attr('src', $(this).attr('data-src'));
        $('.bk-thumb').removeClass('on');
        $(this).addClass('on');
    });

    load(conf.ym);
});
</script>
@endsection
