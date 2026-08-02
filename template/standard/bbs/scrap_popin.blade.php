{{-- win_scrap 팝업(600px 창) — 사이트 헤더 없는 독립 문서라 layout 을 상속하지 않는다 --}}
<!DOCTYPE html>
<html lang="ko" data-template="{{ $template['name'] }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>스크랩하기</title>
<script>
(function () {
    var t = null;
    try { t = localStorage.getItem('g5-theme'); } catch (e) {}
    document.documentElement.dataset.theme =
        (t === 'dark' || (t !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches)) ? 'dark' : 'light';
})();
if (window.name != 'win_scrap') {
    alert('올바른 방법으로 사용해 주십시오.');
    window.close();
}
</script>
<link rel="stylesheet" href="{{ g5_pro_asset('style.css') }}">
</head>
<body class="popup-body">
<div class="popup popup--card">
    <h1 class="popup-title">스크랩하기</h1>
    <form action="{{ $action }}" method="post">
        <input type="hidden" name="bo_table" value="{{ $bo_table }}">
        <input type="hidden" name="wr_id" value="{{ $wr_id }}">
        <p class="popup-subject">{!! $subject !!}</p>
        <label for="wr_content" class="popup-label">댓글 남기기 <span class="muted">(선택)</span></label>
        <textarea name="wr_content" id="wr_content" placeholder="스크랩하면서 감사 혹은 격려의 댓글을 남길 수 있습니다."></textarea>
        <div class="popup-btns">
            <button type="button" class="btn" onclick="window.close();">닫기</button>
            <button type="submit" class="btn btn-primary">스크랩</button>
        </div>
    </form>
</div>
</body>
</html>
