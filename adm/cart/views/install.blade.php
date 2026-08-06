<div class="local_desc01 local_desc">
    <p>카트 모듈은 순정 설치와 무관하게 테이블을 자체 관리합니다. 버전을 올린 뒤에는 아래 버튼으로 업그레이드를 반영하세요.</p>
</div>

@if ($result !== null)
<div class="local_desc01 local_desc">
    <p>
        실행 완료 —
        새 테이블: {{ $result['created'] ? '생성함' : '없음(모두 존재)' }},
        추가 컬럼: {{ count($result['altered']) ? implode(', ', $result['altered']) : '없음' }}
    </p>
</div>
@endif

<table class="tbl_head01 tbl_wrap">
    <thead>
    <tr><th>테이블</th><th>상태</th></tr>
    </thead>
    <tbody>
    @foreach ($tables as $t)
    <tr>
        <td>{{ $t['name'] }}</td>
        <td>{{ $t['exists'] ? '설치됨' : '없음' }}</td>
    </tr>
    @endforeach
    <tr>
        <td>상품 검색 FULLTEXT(ngram)</td>
        <td>{{ $ft ? '사용 가능' : '미지원 — LIKE 폴백으로 동작' }}</td>
    </tr>
    </tbody>
</table>

<div class="btn_confirm01 btn_confirm">
    <a href="{{ $run_url }}" class="btn_submit">설치/업그레이드 실행</a>
</div>
