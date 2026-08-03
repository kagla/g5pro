@extends('layout.default')
@section('content')
<div class="ctt">
    <section class="hero ctt-hero">
        <h2>{!! $subject !!}</h2>
        @if ($admin_href)
        <a class="ctt-admin" href="{{ $admin_href }}">내용 수정</a>
        @endif
    </section>

    @if ($head_img)
    <div class="ctt-img"><img src="{{ $head_img }}" alt=""></div>
    @endif

    <div class="card ctt-body">
        {!! $content !!}
    </div>

    @if ($tail_img)
    <div class="ctt-img"><img src="{{ $tail_img }}" alt=""></div>
    @endif
</div>
@endsection
