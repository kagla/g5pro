{{-- 쿠폰 등록·수정. 발급 방법에 따라 필요한 칸이 달라지므로(코드 입력만 코드가 필요하다)
     아래 스크립트가 해당 없는 칸을 감춘다 — 안 쓰는 칸이 남아 있으면 뭘 채워야 할지 헷갈린다. --}}
{{-- 안내 문장이 길어 칸 옆에 붙이면 앞말과 이어져 읽힌다 — 특히 "사용 안 함" 라디오 뒤에
     "사용 안 함으로 두면…" 이 붙으면 한 문장처럼 보인다. 이 화면에서만 아래 줄로 내린다. --}}
<style>
/* 순정 admin.css 는 셀렉트·입력칸을 둘 다 35px 로 두는데(admin.css:253,256), 셀렉트는
   테두리가 진하고 폭이 좁아 더 두툼해 보인다. 이 화면에서는 둘 다 30px 로 낮춘다 —
   검색줄(.local_sch)이 이미 쓰는 높이라 관리자 안에서 낯설지 않다. */
#cpn_adm .tbl_frm01 select,
#cpn_adm .tbl_frm01 .frm_input { height: 30px; line-height: 28px; }
#cpn_adm .tbl_frm01 td .txt_id { display: block; margin-top: 5px; }
#cpn_adm .tbl_frm01 td label { margin-right: 12px; }
/* 샘플 고르기 — 폼 위에 따로 떼어 둔다. 값을 채워 넣는 도구지 저장되는 값이 아니다 */
#cpn_sample_bar { margin-bottom: 10px; padding: 10px 12px; border: 1px solid #C9DCFA;
    border-radius: 6px; background: #F0F6FF; }
#cpn_sample_bar label { font-weight: bold; color: #2753B0; margin-right: 8px; }
#cpn_sample { height: 30px; line-height: 28px; min-width: 22em; }
#cpn_sample_bar .txt_id { margin-left: 8px; }
</style>
<div id="cpn_adm">

<div class="local_desc01 local_desc">
    <p>할인은 <b>대상 상품 합계</b>에만 적용됩니다(배송비는 깎지 않습니다).
       정률 할인은 10원 단위로 절사합니다. 한 쿠폰은 회원당 한 장, 한 주문에 한 장만 쓸 수 있습니다.</p>
</div>

{{-- 자주 쓰는 쿠폰 열 가지 — 고르면 아래 칸이 채워진다. 정답이 아니라 출발점이라
     이름·금액·기간을 고쳐 쓰는 것이 전제다. 수정 화면에서는 안 보인다(이미 쓰고 있는 값을
     실수로 덮어쓰는 사고가 난다). --}}
@if (!$cp_id)
<div id="cpn_sample_bar">
    <label for="cpn_sample">자주 쓰는 쿠폰</label>
    <select id="cpn_sample">
        <option value="">직접 입력</option>

        @foreach ($samples as $sp)
        <option value="{{ $sp['key'] }}">{{ $sp['title'] }}</option>
        @endforeach

    </select>
    <span class="txt_id">고르면 아래 칸이 채워집니다. 그대로 저장해도 되고 고쳐 써도 됩니다.</span>
</div>
@endif

<form method="post" action="{{ $action_url }}" id="cpn_form">
<input type="hidden" name="token" value="{{ $token }}">
<input type="hidden" name="mode" value="save">
<input type="hidden" name="cp_id" value="{{ $cp_id }}">

<div class="tbl_frm01 tbl_wrap">
<table>
    <caption>쿠폰 정보</caption>
    <tbody>
    <tr>
        <th scope="row">쿠폰 이름</th>
        <td><input type="text" name="cp_name" value="{{ $cp['cp_name'] }}" size="50" required
                   placeholder="예) 첫 주문 감사 10% 할인" class="frm_input">
            <span class="txt_id">회원 쿠폰함과 주문서에 이 이름이 보입니다.</span></td>
    </tr>
    <tr>
        <th scope="row">발급 방법</th>
        <td><select name="cp_issue" id="cp_issue">

            @foreach ($issues as $key => $label)
            <option value="{{ $key }}" {{ $cp['cp_issue'] === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach

            </select>
            <span class="txt_id">가입 축하·첫 구매는 자격이 되는 회원이 쿠폰함·주문서를 열 때 자동 발급됩니다.
            첫 구매 쿠폰의 자격은 <b>구매확정한 주문이 한 건 이상</b>입니다.</span></td>
    </tr>
    <tr id="cpn_code_row">
        <th scope="row">쿠폰 코드</th>
        <td><input type="text" name="cp_code" value="{{ $cp['cp_code'] }}" size="24" maxlength="30"
                   style="text-transform:uppercase" placeholder="WELCOME10" class="frm_input">
            <span class="txt_id">영문 대문자·숫자·- _ 로 3~30자. 코드 입력 쿠폰에만 씁니다.</span></td>
    </tr>
    <tr>
        <th scope="row">할인</th>
        <td><select name="cp_type" id="cp_type">

            @foreach ($types as $key => $label)
            <option value="{{ $key }}" {{ $cp['cp_type'] === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach

            </select>
            <input type="text" name="cp_value" value="{{ $cp['cp_value'] }}" size="8" style="text-align:right" class="frm_input">
            <span id="cpn_unit">%</span>
        </td>
    </tr>
    <tr id="cpn_max_row">
        <th scope="row">최대 할인액</th>
        <td><input type="text" name="cp_max" value="{{ $cp['cp_max'] }}" size="10" style="text-align:right" class="frm_input"> 원
            <span class="txt_id">정률 할인의 상한입니다. 0 이면 상한 없음.</span></td>
    </tr>
    <tr>
        <th scope="row">최소 주문금액</th>
        <td><input type="text" name="cp_min" value="{{ $cp['cp_min'] }}" size="10" style="text-align:right" class="frm_input"> 원 이상
            <span class="txt_id">할인 전 상품 합계 기준입니다. 0 이면 제한 없음.</span></td>
    </tr>
    <tr>
        <th scope="row">사용 대상</th>
        <td><select name="cp_target">
            <option value="" {{ $cp['cp_target'] === '' ? 'selected' : '' }}>전체 상품</option>

            @foreach ($categories as $ca)
            <option value="ca:{{ $ca['ca_code'] }}" {{ $cp['cp_target'] === 'ca:'.$ca['ca_code'] ? 'selected' : '' }}>{{ str_repeat('　', max(0, (int)$ca['ca_depth'] - 1)) }}{{ $ca['ca_name'] }} 분류</option>
            @endforeach

            </select>
            <span class="txt_id">분류를 고르면 그 아래 하위 분류의 상품까지 대상입니다.</span></td>
    </tr>
    <tr>
        <th scope="row">발급 기간</th>
        <td><input type="date" name="cp_begin" value="{{ $cp['cp_begin'] }}" class="frm_input"> ~
            <input type="date" name="cp_end" value="{{ $cp['cp_end'] }}" class="frm_input">
            <span class="txt_id">이 기간에만 발급됩니다.</span></td>
    </tr>
    <tr>
        <th scope="row">사용 기한</th>
        <td>받은 날부터 <input type="text" name="cp_days" value="{{ $cp['cp_days'] }}" size="5" style="text-align:right" class="frm_input"> 일
            <span class="txt_id">0 이면 위 발급 종료일까지 씁니다. 기한은 발급 시점에 굳어지므로,
            나중에 기간을 줄여도 이미 받은 회원의 기한은 줄지 않습니다.</span></td>
    </tr>
    <tr>
        <th scope="row">사용 여부</th>
        <td><label><input type="radio" name="cp_use" value="1" {{ (int)$cp['cp_use'] === 1 ? 'checked' : '' }}> 사용</label>
            <label><input type="radio" name="cp_use" value="0" {{ (int)$cp['cp_use'] === 0 ? 'checked' : '' }}> 사용 안 함</label>
            <span class="txt_id">사용 안 함으로 두면 새로 발급되지도, 이미 받은 장이 쓰이지도 않습니다.</span></td>
    </tr>
    <tr>
        <th scope="row">관리 메모</th>
        <td><input type="text" name="cp_memo" value="{{ $cp['cp_memo'] }}" size="60"
                   placeholder="어떤 이벤트의 쿠폰인지 등 (관리자만 봅니다)" class="frm_input"></td>
    </tr>
    </tbody>
</table>
</div>

<div class="btn_confirm01 btn_confirm">
    <a href="{{ $list_url }}" class="btn btn_02">목록</a>
    <button type="submit" class="btn_submit btn">{{ $cp_id ? '수정' : '등록' }}</button>
</div>
</form>

@if ($cp_id)
<div class="tbl_frm01 tbl_wrap">
<table>
    <caption>발급 현황</caption>
    <tbody>
    <tr>
        <th scope="row">발급 / 사용</th>
        <td>{{ number_format($stats['issued']) }}장 발급 · {{ number_format($stats['used']) }}장 사용
            @if ($stats['amount'] > 0)
            · 할인 합계 {{ number_format($stats['amount']) }}원
            @endif
        </td>
    </tr>
    </tbody>
</table>
</div>

{{-- 발급 내역 — "발급 12장" 만 있으면 정작 누구에게 나갔는지, 그 사람이 썼는지를 못 본다.
     안 쓴 장을 먼저(만료 임박 순) 보여 준다 — 곧 사라질 장이 눈에 먼저 들어와야 한다. --}}
<h2 class="h2_frm">발급 내역</h2>
<div class="tbl_head01 tbl_wrap">
    <table>
    <thead>
    <tr>
        <th scope="col">회원 아이디</th><th scope="col">발급일</th><th scope="col">사용 기한</th>
        <th scope="col">상태</th><th scope="col">사용한 주문</th><th scope="col">할인액</th>
    </tr>
    </thead>
    <tbody>

    @foreach ($holders as $h)
    <tr class="bg{{ $loop->index % 2 }}">
        <td>{{ $h['mb_id'] }}</td>
        <td class="td_datetime">{{ substr($h['cm_issued_at'], 2, 8) }}</td>
        <td class="td_datetime">{{ substr($h['cm_end'], 2) }}</td>
        <td>{{ $h['state'] }}</td>
        <td>

            @if ($h['used'])
            <a href="{{ $order_url }}?od_id={{ $h['cm_od_id'] }}">{{ $h['od_no'] }}</a>
            @else
            -
            @endif

        </td>
        <td class="td_num">{{ $h['used'] ? number_format($h['cm_amount']).'원' : '-' }}</td>
    </tr>
    @endforeach

    @if (!count($holders))
    <tr><td colspan="6" class="empty_table">아직 발급된 장이 없습니다.</td></tr>
    @endif

    </tbody>
    </table>
</div>

@if (count($holders) >= 100)
<p class="txt_id">최근 100장까지만 보여 줍니다.</p>
@endif

{{-- 일괄 지급 — 회원 아이디를 붙여 넣는다. 이미 가진 사람은 조용히 건너뛴다(한 회원 한 장). --}}
<form method="post" action="{{ $action_url }}">
<input type="hidden" name="token" value="{{ $token }}">
<input type="hidden" name="mode" value="grant">
<input type="hidden" name="cp_id" value="{{ $cp_id }}">
<div class="tbl_frm01 tbl_wrap">
<table>
    <caption>회원에게 지급</caption>
    <tbody>
    <tr>
        <th scope="row">회원 아이디</th>
        <td><textarea name="mb_ids" rows="4" style="width:100%" placeholder="아이디를 줄바꿈이나 쉼표로 구분해 붙여 넣으세요"></textarea>
            <span class="txt_id">이미 이 쿠폰을 가진 회원은 건너뜁니다. 발급 기간 안에서만 지급됩니다.</span></td>
    </tr>
    </tbody>
</table>
</div>
<div class="btn_confirm01 btn_confirm">
    <button type="submit" class="btn_submit btn">지급</button>
</div>
</form>

{{-- 삭제는 아직 한 장도 안 나간 쿠폰만 — 발급된 장이 있으면 서버가 막고 이유를 알린다.
     정의가 사라지면 회원 쿠폰함의 그 장이 이름도 조건도 없는 유령이 된다. --}}
<form method="post" action="{{ $action_url }}" onsubmit="return confirm('이 쿠폰을 지울까요? 되돌릴 수 없습니다.');">
<input type="hidden" name="token" value="{{ $token }}">
<input type="hidden" name="mode" value="delete">
<input type="hidden" name="cp_id" value="{{ $cp_id }}">
<div class="btn_confirm01 btn_confirm">
    <button type="submit" class="btn_submit btn btn_02">쿠폰 삭제</button>
</div>
</form>
@endif

<script>
var CPN_SAMPLES = {!! json_encode($samples, JSON_UNESCAPED_UNICODE) !!};

jQuery(function ($) {
    // 샘플 고르기 — 값을 칸에 넣고 나면 평범한 폼이다. 발급 기간은 안 건드린다:
    // 언제부터 언제까지 뿌릴지는 이벤트마다 다르고, 화면이 정할 일이 아니다.
    $('#cpn_sample').on('change', function () {
        var key = $(this).val(), sp = null;
        for (var i = 0; i < CPN_SAMPLES.length; i++) if (CPN_SAMPLES[i].key === key) sp = CPN_SAMPLES[i];
        if (!sp) return;
        $('[name=cp_name]').val(sp.cp_name);
        $('[name=cp_code]').val(sp.cp_code);
        $('#cp_issue').val(sp.cp_issue);
        $('#cp_type').val(sp.cp_type);
        $('[name=cp_value]').val(sp.cp_value);
        $('[name=cp_max]').val(sp.cp_max);
        $('[name=cp_min]').val(sp.cp_min);
        $('[name=cp_days]').val(sp.cp_days);
        paint();
    });

    // 발급 방법·할인 방식에 따라 해당 없는 칸을 감춘다 — 자동 지급 쿠폰에 코드 칸이 남아 있으면
    // 채워 넣게 되고, 그러면 "가입 축하 쿠폰" 을 아무나 코드로 받아 갈 수 있다(서버도 비운다).
    function paint() {
        $('#cpn_code_row').toggle($('#cp_issue').val() === 'code');
        var rate = $('#cp_type').val() === 'rate';
        $('#cpn_max_row').toggle(rate);
        $('#cpn_unit').text(rate ? '%' : '원');
    }
    $('#cp_issue, #cp_type').on('change', paint);
    paint();
});
</script>
</div>
