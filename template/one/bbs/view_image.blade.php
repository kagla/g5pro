{{-- 이미지 크게보기 (bbs/view_image.php) — 순정 뷰어(끌어서 이동·더블클릭으로 닫기) 출력을
     그대로 담는다. 창 크기를 이미지에 맞추는 스크립트가 순정 계약이라 손대지 않는다 --}}
@extends('layout.popup')
@section('head')
<script src="{{ G5_JS_URL }}/jquery-1.12.4.min.js"></script>
@endsection
@section('popup_class', 'popup--bare')
@section('content')
{!! $body_html !!}
@endsection
