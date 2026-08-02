{{-- 1:1 문의 읽기 (bbs/qaview.php) — 질문 아래에 답변을 잇는다.
     답변은 순정에서 별도 행(qa_type=1)이라 없을 수도 있다. --}}
@extends('layout.default')
@section('content')

@if ($head)<div class="board-extra">{!! $head !!}</div>@endif

<article class="post qa-post">
    <header class="post-head">
        <h2>
            @if ($item['category'])<span class="chip c2">{{ $item['category'] }}</span>@endif
            {{ $item['subject'] }}
        </h2>
        <div class="post-meta">
            <span>{{ $item['name'] }}</span>
            <span>{{ $item['datetime'] }}</span>
            <span class="chip {{ $item['answered'] ? 'c3' : 'c4' }}">{{ $item['answered'] ? '답변완료' : '접수' }}</span>
        </div>
    </header>

    {{-- 이미지 첨부 — 순정과 같이 본문 위에 둔다 (qa/basic/view.skin.php 의 bo_v_img) --}}
    @if (count($images))
    <div class="qa-images">
        @foreach ($images as $img)
        <div class="qa-image">{!! $img !!}</div>
        @endforeach
    </div>
    @endif

    <div class="post-content">{!! $item['content'] !!}</div>

    @if (count($files))
    <div class="post-files">
        @foreach ($files as $f)
        <a href="{{ $f['href'] }}">{{ $f['source'] }}</a>
        @endforeach
    </div>
    @endif
</article>

@if ($answer)
<article class="post qa-answer">
    <header class="post-head">
        <h2><span class="chip c3">답변</span></h2>
        <div class="post-meta"><span>{{ $answer['datetime'] }}</span></div>
    </header>
    <div class="post-content">{!! $answer['content'] !!}</div>
    @if ($is_admin && $links['answer_update'])
    <div class="post-files">
        <a href="{!! $links['answer_update'] !!}">답변수정</a>
        <a href="{!! $links['answer_delete'] !!}" onclick="return confirm('답변을 삭제하시겠습니까?');">답변삭제</a>
    </div>
    @endif
</article>
@endif

{{-- 답변 등록 — 순정은 qawrite.php 가 w=a 를 거부해서 답변을 여기서만 받는다 --}}
@if ($answer_form['show'])
@if ($is_admin)
<article class="post qa-answer-form">
    <header class="post-head">
        <h2><span class="chip c3">답변등록</span></h2>
    </header>

    <form name="fanswer" method="post" action="{{ $answer_form['action'] }}"
          onsubmit="return qa_answer_submit(this);" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="w" value="a">
        <input type="hidden" name="qa_id" value="{{ $answer_form['qa_id'] }}">
        <input type="hidden" name="token" value="{{ $answer_form['token'] }}">
        @foreach ($answer_form['params'] as $k => $v)
        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endforeach
        {!! $answer_form['option_hidden'] !!}

        <div class="write-form">
            <div class="field">
                <label for="qa_subject">제목</label>
                <input type="text" name="qa_subject" id="qa_subject" required maxlength="255" value="">
            </div>

            <div class="field">
                <label>내용</label>
                {!! $answer_form['editor_html'] !!}
            </div>

            @if (count($answer_form['options']))
            <div class="field-inline">
                @foreach ($answer_form['options'] as $o)
                @php $chk = $o['checked'] ? 'checked' : ''; @endphp
                <label><input type="checkbox" name="{{ $o['name'] }}" value="{{ $o['value'] }}" {{ $chk }}> {{ $o['label'] }}</label>
                @endforeach
            </div>
            @endif

            @if ($answer_form['use_file'])
            <div class="field">
                <label for="bf_answer1">첨부파일</label>
                <input type="file" name="bf_file[1]" id="bf_answer1">
                <input type="file" name="bf_file[2]" id="bf_answer2">
            </div>
            @endif
        </div>

        <div class="bbs-toolbar active">
            <button type="submit" class="btn btn-primary">답변등록</button>
        </div>
    </form>

    <script>
    // editor_js 는 완성된 스크립트가 아니라 submit 검사 안에 들어가야 할 조각이다.
    // 내용 검사와 에디터 값을 폼필드로 옮기는 일을 함께 한다.
    function qa_answer_submit(f) {
        if (!f.qa_subject.value.trim()) { alert("제목을 입력하세요."); f.qa_subject.focus(); return false; }
        {!! $answer_form['editor_js'] !!}
        return true;
    }
    </script>
</article>
@else
<p class="qa-answer-wait">고객님의 문의에 대한 답변을 준비 중입니다.</p>
@endif
@endif

<div class="bbs-toolbar active">
    <div class="qa-nav">
        @if ($links['prev'])<a class="btn" href="{!! $links['prev'] !!}">이전</a>@endif
        @if ($links['next'])<a class="btn" href="{!! $links['next'] !!}">다음</a>@endif
    </div>
    <div class="qa-acts">
        @if ($links['update'])<a class="btn" href="{!! $links['update'] !!}">수정</a>@endif
        @if ($links['delete'])<a class="btn" href="{!! $links['delete'] !!}" onclick="return confirm('이 문의를 삭제하시겠습니까?');">삭제</a>@endif
        <a class="btn" href="{{ $links['list'] }}">목록</a>
        <a class="btn btn-primary" href="{{ $links['write'] }}">문의하기</a>
    </div>
</div>

@if ($tail)<div class="board-extra">{!! $tail !!}</div>@endif
@endsection
