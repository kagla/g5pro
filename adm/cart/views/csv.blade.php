<div class="local_desc01 local_desc">
    <p>흐름: <strong>내보내기 → 엑셀에서 수정 → 올리기 → 미리보기 확인 → 반영</strong>. 상품코드·SKU코드가 기준 키입니다(있으면 수정, 없으면 신규). 재고 칸을 바꾸면 그 값으로 설정되고 전 변경이 재고 이력에 남습니다. 키워드·상세 설명·이미지는 CSV 로 다루지 않습니다(폼에서).</p>
</div>

<div class="btn_confirm01 btn_confirm" style="text-align:left">
    <a href="{{ $export_url }}" class="btn_submit btn">전체 상품 CSV 내보내기</a>
</div>

<h2 class="h2_frm">가져오기</h2>
<form method="post" action="{{ $action_url }}" enctype="multipart/form-data">
    <input type="hidden" name="token" value="">
    <input type="hidden" name="mode" value="upload">
    <input type="file" name="csv_file" accept=".csv" required>
    <button type="submit" class="btn_submit btn">올려서 미리보기</button>
</form>

@if ($preview !== null)
<h2 class="h2_frm">미리보기 — 아직 반영 전</h2>
<table class="tbl_head01 tbl_wrap">
    <tbody>
    <tr><th>읽은 행</th><td>{{ number_format($preview['row_count']) }}</td></tr>
    <tr><th>신규 상품 / 수정 상품</th><td>{{ $preview['new_items'] }} / {{ $preview['upd_items'] }}</td></tr>
    <tr><th>신규 SKU / 수정 SKU</th><td>{{ $preview['new_skus'] }} / {{ $preview['upd_skus'] }}</td></tr>
    <tr><th>재고 변경</th><td>{{ $preview['stock_changes'] }}건</td></tr>
    </tbody>
</table>

@if (count($preview['parse_errors']) || count($preview['errors']))
<div class="local_desc02 local_desc">
    <p><strong>문제 행 — 이 행들은 건너뛰고 반영됩니다:</strong></p>

    @foreach (array_merge($preview['parse_errors'], $preview['errors']) as $e)
    <p>{{ $e }}</p>
    @endforeach

</div>
@endif

<form method="post" action="{{ $action_url }}">
    <input type="hidden" name="token" value="">
    <input type="hidden" name="mode" value="apply">
    <input type="hidden" name="key" value="{{ $preview['key'] }}">
    <button type="submit" class="btn_submit btn"
        onclick="return confirm('미리보기 내용대로 반영할까요?')">반영 실행</button>
</form>
@endif
